<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

namespace mod_interactivevideo\courseformat;

use cm_info;
use cache;
use core\output\action_link;
use core\output\local\properties\text_align;
use core_courseformat\local\overview\overviewitem;
use core\output\local\properties\button;
use core\url;

/**
 * Interactive Video overview integration (for Moodle 5.1+)
 *
 * @package    mod_interactivevideo
 * @copyright  2026 Sokunthearith Makara <sokunthearithmakara@gmail.com>
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class overview extends \core_courseformat\activityoverviewbase {
    /** @var array The interactive video items. */
    private array $ivitems;

    /** @var int The total number of students. */
    private int $allstudents;

    /**
     * Constructor.
     *
     * @param \cm_info $cm the course module instance.
     * @param \core\output\renderer_helper $rendererhelper the renderer helper.
     */
    public function __construct(
        \cm_info $cm,
        \core\output\renderer_helper $rendererhelper
    ) {
        global $DB;
        parent::__construct($cm);
        $this->allstudents = count_enrolled_users($cm->context);
        $customdata = $cm->get_custom_data();
        $startend = explode('-', $customdata['startendtime']);
        $interactivevideo = [
            'id' => $cm->instance,
            'name' => $cm->name,
            'starttime' => $startend[0],
            'endtime' => $startend[1],
            'type' => $customdata['type'],
            'completionpercentage' => isset($customdata['customcompletionrules'])
                ? $customdata['customcompletionrules']['completionpercentage'] : 0,
        ];

        $interactivevideo = (object) $interactivevideo;
        $contenttypes = get_config('mod_interactivevideo', 'enablecontenttypes');
        $enabledcontenttypes = explode(',', $contenttypes);
        $includeanalytics = in_array('local_ivanalytics', $enabledcontenttypes);

        $cache = cache::make('mod_interactivevideo', 'iv_items_by_cmid');

        $items = $cache->get($cm->instance);
        if (empty($items)) {
            $items = $DB->get_records(
                'interactivevideo_items',
                ['annotationid' => $cm->instance]
            );
            $cache->set($cm->instance, $items);
        }
        // What if $enabedcontenttypes changes.
        if (!$items || empty($items)) {
            $this->ivitems = [];
            return;
        }

        $items = array_filter($items, function ($item) use ($contenttypes) {
            return strpos($contenttypes, $item->type) !== false;
        });

        $relevantitems = array_filter($items, function ($item) use ($interactivevideo) {
            return (($item->timestamp >= $interactivevideo->starttime
                && $item->timestamp <= $interactivevideo->endtime) || $item->timestamp < 0)
                && ($item->hascompletion == 1 || $item->type == 'skipsegment' || $item->type == 'analytics');
        });

        if (!$includeanalytics) {
            $relevantitems = array_filter($relevantitems, function ($item) {
                return $item->type != 'analytics';
            });
        }

        $skipsegment = array_filter($relevantitems, function ($item) {
            return $item->type === 'skipsegment';
        });

        $analytics = array_filter($relevantitems, function ($item) {
            return $item->type === 'analytics';
        });
        $analytics = reset($analytics);

        $relevantitems = array_filter($relevantitems, function ($item) use ($skipsegment) {
            foreach ($skipsegment as $ss) {
                if ($item->timestamp > $ss->timestamp && $item->timestamp < $ss->title && $item->timestamp >= 0) {
                    return false;
                }
            }
            if ($item->type === 'skipsegment') {
                return false;
            }
            if ($item->type === 'analytics' && $item->hascompletion != 1) {
                return false;
            }
            return true;
        });

        $this->ivitems = $relevantitems;
    }

    #[\Override]
    public function get_actions_overview(): ?overviewitem {
        if (!has_capability('mod/interactivevideo:viewreport', $this->cm->context)) {
            return null;
        }

        $viewresults = get_string('fullreport', 'mod_interactivevideo');
        $reporturl = new url('/mod/interactivevideo/report.php', ['id' => $this->cm->id]);
        $content = new action_link(
            $reporturl,
            $viewresults,
            null,
            ['class' => 'btn btn-outline-secondary'],
        );

        return new overviewitem(
            get_string('actions'),
            '',
            $content,
            text_align::CENTER,
        );
    }

    #[\Override]
    public function get_extra_overview_items(): array {
        return [
            'interactions' => $this->get_extra_interactions(),
            'userxp' => $this->get_extra_user_xp(),
            'usercompletion' => $this->get_extra_user_completion(),
            'studentstarted' => $this->get_extra_students_started(),
            'studentcompleted' => $this->get_extra_students_completed(),
            'studentended' => $this->get_extra_students_ended(),
        ];
    }

    /**
     * Get the number of interactions for the given module instance.
     *
     * @return overviewitem|null An overview item or null if the user lacks the required capability.
     */
    private function get_extra_interactions(): ?overviewitem {
        if (has_capability('mod/interactivevideo:viewreport', $this->cm->context)) {
            return null;
        }

        return new overviewitem(
            get_string('interactions', 'mod_interactivevideo'),
            count($this->ivitems),
            count($this->ivitems),
        );
    }

    /**
     * Get the number of students who started the activity.
     *
     * @return overviewitem|null An overview item or null if the user lacks the required capability.
     */
    private function get_extra_user_xp(): ?overviewitem {
        if (has_capability('mod/interactivevideo:viewreport', $this->cm->context)) {
            return null;
        }

        global $DB, $USER;
        $xp = $DB->get_field_sql("SELECT c.xp FROM {interactivevideo_completion} c
                WHERE c.cmid = :cmid AND c.userid = :userid", ['cmid' => $this->cm->instance, 'userid' => $USER->id]);

        if (empty($xp)) {
            return new overviewitem(
                get_string('xp', 'mod_interactivevideo'),
                '-',
                '-'
            );
        }

        return new overviewitem(
            get_string('xp', 'mod_interactivevideo'),
            $xp,
            $xp,
        );
    }

    /**
     * Get the number of students who completed the activity.
     *
     * @return overviewitem|null An overview item or null if the user lacks the required capability.
     */
    private function get_extra_user_completion(): ?overviewitem {
        if (has_capability('mod/interactivevideo:viewreport', $this->cm->context)) {
            return null;
        }

        global $DB, $USER;
        $completion = $DB->get_field_sql("SELECT c.completionpercentage FROM {interactivevideo_completion} c
                WHERE c.cmid = :cmid AND c.userid = :userid", ['cmid' => $this->cm->instance, 'userid' => $USER->id]);

        if (empty($completion)) {
            return new overviewitem(
                get_string('completionpercentage', 'mod_interactivevideo'),
                '-',
                '-'
            );
        }

        return new overviewitem(
            get_string('completionpercentage', 'mod_interactivevideo'),
            $completion,
            $completion,
        );
    }

    /**
     * Get the students started overview item.
     *
     * @return overviewitem|null An overview item or null if the user lacks the required capability.
     */
    private function get_extra_students_started(): ?overviewitem {
        if (!has_capability('mod/interactivevideo:viewreport', $this->cm->context)) {
            return null;
        }

        global $DB;

        $sql = "SELECT COUNT(DISTINCT c.userid) FROM {interactivevideo_completion} c
                WHERE c.cmid = :cmid AND c.timecreated > 0";
        $started = $DB->count_records_sql($sql, ['cmid' => $this->cm->instance]);

        return new overviewitem(
            get_string('studentsstarted', 'mod_interactivevideo'),
            $started,
            $started . " / " . $this->allstudents,
        );
    }

    /**
     * Get the number of students who completed the activity.
     *
     * @return overviewitem|null An overview item or null if the user lacks the required capability.
     */
    private function get_extra_students_completed(): ?overviewitem {
        if (!has_capability('mod/interactivevideo:viewreport', $this->cm->context)) {
            return null;
        }

        if (count($this->ivitems) == 0) {
            return new overviewitem(
                get_string('studentscompleted', 'mod_interactivevideo'),
                '-',
                '-'
            );
        }

        global $DB;
        $sql = "SELECT COUNT(DISTINCT c.userid) FROM {interactivevideo_completion} c
                WHERE c.cmid = :cmid AND c.timecompleted > 0";
        $completed = $DB->count_records_sql($sql, ['cmid' => $this->cm->instance]);

        return new overviewitem(
            get_string('studentscompleted', 'mod_interactivevideo'),
            $completed,
            $completed . " / " . $this->allstudents,
        );
    }

    /**
     * Get the number of students who completed the activity.
     *
     * @return overviewitem|null An overview item or null if the user lacks the required capability.
     */
    private function get_extra_students_ended(): ?overviewitem {
        if (!has_capability('mod/interactivevideo:viewreport', $this->cm->context)) {
            return null;
        }

        global $DB;
        // Student count.
        $sql = "SELECT COUNT(DISTINCT c.userid) FROM {interactivevideo_completion} c
                WHERE c.cmid = :cmid AND c.timeended > 0";
        $ended = $DB->count_records_sql($sql, ['cmid' => $this->cm->instance]);

        return new overviewitem(
            get_string('studentsended', 'mod_interactivevideo'),
            $ended,
            $ended . " / " . $this->allstudents,
        );
    }
}
