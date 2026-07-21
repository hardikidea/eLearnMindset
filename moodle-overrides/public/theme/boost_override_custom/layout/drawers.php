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

/**
 * Drawer layout for the Boost Override Custom theme.
 *
 * This intentionally keeps Boost's drawer template and only adds a body class
 * plus front-page stylesheet when Moodle renders the site home layout.
 *
 * @package    theme_boost_override_custom
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

require_once($CFG->libdir . '/behat/lib.php');
require_once($CFG->dirroot . '/course/lib.php');

$addblockbutton = $OUTPUT->addblockbutton();

if (isloggedin()) {
    $courseindexopen = (get_user_preferences('drawer-open-index', true) == true);
    $blockdraweropen = (get_user_preferences('drawer-open-block') == true);
} else {
    $courseindexopen = false;
    $blockdraweropen = false;
}

if (defined('BEHAT_SITE_RUNNING') && get_user_preferences('behat_keep_drawer_closed') != 1) {
    $blockdraweropen = true;
}

$isfrontpage = $PAGE->pagelayout === 'frontpage';
$isdashboard = $PAGE->pagelayout === 'mydashboard';
$iscourseindex = ($PAGE->pagelayout === 'coursecategory' && $PAGE->pagetype === 'course-index-category') ||
        $PAGE->pagetype === 'course-search';
$iscourseindexroot = $PAGE->pagetype === 'course-index-category' && optional_param('categoryid', 0, PARAM_INT) === 0;
$currentpath = $PAGE->url->get_path();
$ismycourses = $currentpath === '/my/courses.php';
$usesmodernheader = $isdashboard || in_array($currentpath, [
    '/user/profile.php',
    '/my/courses.php',
    '/grade/report/overview/index.php',
    '/calendar/view.php',
    '/user/files.php',
    '/reportbuilder/index.php',
    '/user/preferences.php',
], true);
$extraclasses = ['uses-drawers'];
if ($isfrontpage) {
    $extraclasses[] = 'theme-boost-override-custom-frontpage';
    $PAGE->requires->css(new \moodle_url('/theme/boost_override_custom/style/frontpage.css'));
}
if ($usesmodernheader) {
    $extraclasses[] = 'theme-boost-override-custom-dashboard-header';
    $PAGE->requires->css(new \moodle_url('/theme/boost_override_custom/style/dashboardheader.css'));
}
if ($ismycourses) {
    $extraclasses[] = 'theme-boost-override-custom-mycourses';
    $PAGE->requires->css(new \moodle_url('/theme/boost_override_custom/style/mycourses.css'));
    $PAGE->requires->js_init_code(<<<'JS'
        (function() {
            var pageSelector = '.theme-boost-override-custom-mycourses';
            var scheduled = false;

            var cleanText = function(value) {
                return (value || '').replace(/\s+/g, ' ').trim();
            };

            var textWithoutHidden = function(node) {
                var clone;

                if (!node) {
                    return '';
                }

                clone = node.cloneNode(true);
                clone.querySelectorAll('.visually-hidden, .sr-only, .accesshide').forEach(function(hidden) {
                    hidden.remove();
                });
                return cleanText(clone.textContent);
            };

            var getCourseTitle = function(card) {
                var titleNode = card.querySelector('.coursename .multiline[title]');
                var title = titleNode ? cleanText(titleNode.getAttribute('title')) : '';

                if (title) {
                    return title;
                }

                return textWithoutHidden(card.querySelector('.coursename')) ||
                    textWithoutHidden(card.querySelector('a[href*="/course/view.php"]')) ||
                    'Course';
            };

            var displayFullCourseTitle = function(card, title) {
                var titleNode = card.querySelector('.coursename .multiline[title]');
                var visibleTitle;

                if (!titleNode || !title) {
                    return;
                }

                titleNode.setAttribute('title', title);
                visibleTitle = titleNode.querySelector('[aria-hidden="true"]');
                if (!visibleTitle) {
                    visibleTitle = document.createElement('span');
                    visibleTitle.setAttribute('aria-hidden', 'true');
                    titleNode.appendChild(visibleTitle);
                }
                visibleTitle.textContent = title;
            };

            var courseLink = function(card) {
                return card.querySelector('.coursename[href*="/course/view.php"], .coursename a[href*="/course/view.php"], a[href*="/course/view.php"]');
            };

            var parseCourseDetails = function(title) {
                var parts = cleanText(title).split(' - ').map(function(part) {
                    return cleanText(part);
                }).filter(Boolean);
                var details = {
                    board: '',
                    medium: '',
                    standard: '',
                    stream: '',
                    subject: '',
                    year: ''
                };

                parts.forEach(function(part) {
                    if (!details.medium && /medium/i.test(part)) {
                        details.medium = part;
                    } else if (!details.standard && /(standard|nursery|preschool|lkg|ukg|year\s*\d|semester|sem\s*\d)/i.test(part)) {
                        details.standard = part;
                    } else if (!details.year && /\b20\d{2}\s*[-/]\s*20\d{2}\b|\b20\d{2}\b/.test(part)) {
                        details.year = part;
                    }
                });

                if (parts.length > 1) {
                    details.board = parts[1];
                }
                if (parts.length > 4) {
                    details.stream = parts[4];
                }
                if (parts.length > 5) {
                    details.subject = parts.slice(5).filter(function(part) {
                        return part !== details.year;
                    }).join(' - ');
                }

                return details;
            };

            var categoryTone = function(label, title) {
                var value = (label + ' ' + title).toLowerCase();
                var tones = [
                    { pattern: /physical|sports|health|medical|nursing/, name: 'health', icon: 'pulse' },
                    { pattern: /science|math|computer|technology|polytechnic|engineering|data|ai|coding/, name: 'science', icon: 'flask' },
                    { pattern: /commerce|finance|business|management|account|marketing/, name: 'commerce', icon: 'briefcase' },
                    { pattern: /law|legal/, name: 'law', icon: 'scale' },
                    { pattern: /art|craft|creative|design|humanities/, name: 'arts', icon: 'palette' },
                    { pattern: /teacher|training|education|school|standard|nursery|preschool|lkg|ukg/, name: 'school', icon: 'cap' }
                ];
                return tones.find(function(tone) {
                    return tone.pattern.test(value);
                }) || { name: 'general', icon: 'book' };
            };

            var iconSvg = function(name) {
                var paths = {
                    book: '<path d="M7 5.5A2.5 2.5 0 0 1 9.5 3H18v15.5H9.25A2.25 2.25 0 0 0 7 20.75V5.5Z"/><path d="M6 5.5A2.5 2.5 0 0 0 3.5 3H3v15.5h.75A2.25 2.25 0 0 1 6 20.75V5.5Z" opacity=".56"/>',
                    cap: '<path d="M12 4 3 8.4l9 4.4 9-4.4L12 4Z"/><path d="M6.2 11.4v3.15c0 1.65 2.62 3.1 5.8 3.1s5.8-1.45 5.8-3.1V11.4L12 14.25 6.2 11.4Z" opacity=".64"/>',
                    flask: '<path d="M9 3h6v2l-1.15.8v3.05l4.95 8.2A2.55 2.55 0 0 1 16.62 21H7.38a2.55 2.55 0 0 1-2.18-3.95l4.95-8.2V5.8L9 5V3Z"/><path d="M8.25 16h7.5" opacity=".58"/>',
                    briefcase: '<path d="M9 6V4.8A1.8 1.8 0 0 1 10.8 3h2.4A1.8 1.8 0 0 1 15 4.8V6h3.5A1.5 1.5 0 0 1 20 7.5v3.1a19.6 19.6 0 0 1-16 0V7.5A1.5 1.5 0 0 1 5.5 6H9Z"/><path d="M4 12.8v5.7A1.5 1.5 0 0 0 5.5 20h13a1.5 1.5 0 0 0 1.5-1.5v-5.7a21.6 21.6 0 0 1-16 0Z" opacity=".58"/>',
                    scale: '<path d="M12 4v15"/><path d="M6 7h12"/><path d="M7 7 4.5 13h5L7 7Z"/><path d="M17 7l-2.5 6h5L17 7Z"/><path d="M8.5 20h7"/>',
                    palette: '<path d="M12 3.5a8.5 8.5 0 0 0 0 17h1.2a1.75 1.75 0 0 0 .85-3.28 1.6 1.6 0 0 1 .78-3h1.35A4.2 4.2 0 0 0 20.4 10 6.9 6.9 0 0 0 12 3.5Z"/><circle cx="8.1" cy="10.2" r="1.1"/><circle cx="11" cy="7.8" r="1.1"/><circle cx="14.4" cy="8.4" r="1.1"/>',
                    pulse: '<path d="M20 12.1c0 5-6.8 8.2-8 8.2s-8-3.2-8-8.2A4.6 4.6 0 0 1 8.6 7.5c1.45 0 2.55.7 3.4 1.78.85-1.08 1.95-1.78 3.4-1.78A4.6 4.6 0 0 1 20 12.1Z"/><path d="M7 13h2.4l1-2.6 1.8 5.1 1.2-2.5H17" opacity=".7"/>'
                };

                return '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">' +
                    '<g fill="none" stroke="currentColor" stroke-width="1.9" stroke-linecap="round" stroke-linejoin="round">' +
                    (paths[name] || paths.book) +
                    '</g></svg>';
            };

            var addCardChips = function(card, details, category) {
                var body = card.querySelector('.course-info-container');
                var target = body ? body.querySelector('.text-muted') : null;
                var existing = card.querySelector('.boc-mycourse-chips');
                var values = [
                    details.standard,
                    details.medium,
                    details.stream,
                    details.year
                ].filter(function(value, index, list) {
                    return value && list.indexOf(value) === index;
                }).slice(0, 4);
                var chips;

                if (existing) {
                    existing.remove();
                }

                if (!target || !values.length) {
                    return;
                }

                chips = document.createElement('div');
                chips.className = 'boc-mycourse-chips';
                values.forEach(function(value) {
                    var chip = document.createElement('span');
                    chip.textContent = value;
                    chips.appendChild(chip);
                });
                target.insertAdjacentElement('afterend', chips);

                if (category) {
                    card.setAttribute('data-boc-category-label', category);
                }
            };

            var decorateCategory = function(card, tone) {
                var category = card.querySelector('.categoryname');
                var icon;

                if (!category || category.querySelector('.boc-mycourse-category-icon')) {
                    return;
                }

                icon = document.createElement('span');
                icon.className = 'boc-mycourse-category-icon';
                icon.innerHTML = iconSvg(tone.icon);
                category.prepend(icon);
            };

            var buildHoverPanel = function(card, title, details, category) {
                var link = courseLink(card);
                var panel = card.querySelector('.boc-mycourse-hover-panel');
                var facts = [
                    ['Category', category || 'General'],
                    ['Level', details.standard || details.stream || 'Learning space'],
                    ['Medium', details.medium || 'Moodle course'],
                    ['Academic', details.year || 'Active course']
                ];

                if (!panel) {
                    panel = document.createElement('div');
                    panel.className = 'boc-mycourse-hover-panel';
                    card.appendChild(panel);
                }

                panel.innerHTML = '<strong></strong><p></p><div class="boc-mycourse-hover-facts"></div><a class="boc-mycourse-hover-cta"></a>';
                panel.querySelector('strong').textContent = title;
                panel.querySelector('p').textContent = 'Open the course workspace for activities, resources, submissions, announcements and grade tracking.';
                facts.forEach(function(item) {
                    var fact = document.createElement('span');
                    var label = document.createElement('b');
                    var value = document.createElement('em');
                    label.textContent = item[0];
                    value.textContent = item[1];
                    fact.appendChild(label);
                    fact.appendChild(value);
                    panel.querySelector('.boc-mycourse-hover-facts').appendChild(fact);
                });

                panel.querySelector('.boc-mycourse-hover-cta').textContent = 'View course';
                if (link && link.href) {
                    panel.querySelector('.boc-mycourse-hover-cta').href = link.href;
                    panel.querySelector('.boc-mycourse-hover-cta').tabIndex = -1;
                }
            };

            var bindTitleHover = function(card) {
                var title = card.querySelector('.coursename');

                if (!title || title.getAttribute('data-boc-title-hover-ready') === '1') {
                    return;
                }

                title.addEventListener('mouseenter', function() {
                    card.classList.add('boc-mycourse-preview-open');
                });
                title.addEventListener('focus', function() {
                    card.classList.add('boc-mycourse-preview-open');
                });
                card.addEventListener('mouseleave', function() {
                    card.classList.remove('boc-mycourse-preview-open');
                });
                card.addEventListener('focusout', function() {
                    window.setTimeout(function() {
                        if (!card.contains(document.activeElement)) {
                            card.classList.remove('boc-mycourse-preview-open');
                        }
                    }, 0);
                });
                title.setAttribute('data-boc-title-hover-ready', '1');
            };

            var enhanceCard = function(card, index) {
                var title = getCourseTitle(card);
                var category = cleanText(card.querySelector('.categoryname') ? card.querySelector('.categoryname').textContent : '');
                var details = parseCourseDetails(title);
                var tone = categoryTone(category, title);

                card.setAttribute('data-boc-mycourse-ready', '1');
                card.setAttribute('data-boc-tone', tone.name);
                card.style.setProperty('--boc-course-index', index % 8);
                displayFullCourseTitle(card, title);
                addCardChips(card, details, category);
                decorateCategory(card, tone);
                buildHoverPanel(card, title, details, category);
                bindTitleHover(card);
            };

            var enhanceMyCourses = function() {
                document.querySelectorAll(pageSelector + ' .block-myoverview [data-region="course-content"].course-card').forEach(function(card, index) {
                    if (card.getAttribute('data-boc-mycourse-ready') !== '1') {
                        enhanceCard(card, index);
                    }
                });
            };

            var scheduleEnhancement = function() {
                if (scheduled) {
                    return;
                }
                scheduled = true;
                window.requestAnimationFrame(function() {
                    scheduled = false;
                    enhanceMyCourses();
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', enhanceMyCourses);
            } else {
                enhanceMyCourses();
            }

            if (window.MutationObserver) {
                new MutationObserver(scheduleEnhancement).observe(document.querySelector(pageSelector + ' [data-region="courses-view"]') || document.body, {
                    childList: true,
                    subtree: true
                });
            }

            document.addEventListener('click', function(event) {
                if (event.target.closest(pageSelector + ' [data-region="filter"], ' + pageSelector + ' [data-region="paging-control"]')) {
                    window.setTimeout(scheduleEnhancement, 160);
                }
            });
        })();
JS
    );
}
if ($iscourseindex) {
    $extraclasses[] = 'theme-boost-override-custom-courseindex';
    $PAGE->requires->css(new \moodle_url('/theme/boost_override_custom/style/courseindex.css'));
}
if ($iscourseindexroot) {
    $extraclasses[] = 'theme-boost-override-custom-courseindex-root';
}
if ($isfrontpage || $iscourseindex) {
    $PAGE->requires->js_init_code(<<<'JS'
        (function() {
            var navSelector = '.primary-navigation .nav-link:not(.dropdown-toggle)';
            var sectionIds = ['discover', 'programmes', 'admissions', 'boards-mediums', 'grade-system', 'about', 'contact'];
            var scheduled = false;

            var normalisePath = function(path) {
                return (path || '').replace(/\/index\.php$/, '/').replace(/\/+$/, '/') || '/';
            };

            var links = function() {
                return Array.prototype.slice.call(document.querySelectorAll(navSelector));
            };

            var linkHash = function(link) {
                try {
                    return new URL(link.href, window.location.href).hash;
                } catch (exception) {
                    return link.getAttribute('href') || '';
                }
            };

            var isHomeLink = function(link) {
                try {
                    var url = new URL(link.href, window.location.href);
                    return !url.hash && normalisePath(url.pathname) === '/';
                } catch (exception) {
                    return false;
                }
            };

            var setActiveLink = function(target) {
                links().forEach(function(link) {
                    var active = typeof target === 'string' ?
                        (target === 'home' ? isHomeLink(link) : linkHash(link) === '#' + target) :
                        link === target;

                    link.classList.toggle('active', active);
                    if (active) {
                        link.setAttribute('aria-current', 'true');
                    } else {
                        link.removeAttribute('aria-current');
                    }
                });
            };

            var activeSectionId = function() {
                var header = document.querySelector('.navbar.fixed-top');
                var offset = (header ? header.getBoundingClientRect().height : 72) + 32;
                var probe = offset + Math.min(180, Math.round(window.innerHeight * .22));
                var current = 'home';

                sectionIds.forEach(function(id) {
                    var section = document.getElementById(id);
                    var rect;

                    if (!section) {
                        return;
                    }

                    rect = section.getBoundingClientRect();
                    if (rect.top <= probe && rect.bottom > offset) {
                        current = id;
                    }
                });

                if ((window.innerHeight + window.scrollY) >= (document.documentElement.scrollHeight - 8)) {
                    current = sectionIds.filter(function(id) {
                        return document.getElementById(id);
                    }).pop() || current;
                }

                return current;
            };

            var updateFrontpageActiveLink = function() {
                setActiveLink(activeSectionId());
            };

            var scheduleFrontpageUpdate = function() {
                if (scheduled) {
                    return;
                }
                scheduled = true;
                window.requestAnimationFrame(function() {
                    scheduled = false;
                    updateFrontpageActiveLink();
                });
            };

            var setupFrontpageNav = function() {
                setupHeroDepth();

                links().forEach(function(link) {
                    link.addEventListener('click', function() {
                        var hash = linkHash(link);
                        if (hash && hash.length > 1) {
                            setActiveLink(hash.substring(1));
                        }
                    });
                });

                window.addEventListener('scroll', scheduleFrontpageUpdate, { passive: true });
                window.addEventListener('resize', scheduleFrontpageUpdate);
                window.addEventListener('hashchange', scheduleFrontpageUpdate);
                updateFrontpageActiveLink();
                window.setTimeout(updateFrontpageActiveLink, 240);
            };

            var setupHeroDepth = function() {
                var hero = document.querySelector('.boc-hero');
                var reduceMotion = window.matchMedia &&
                    window.matchMedia('(prefers-reduced-motion: reduce)').matches;

                if (!hero || reduceMotion || hero.getAttribute('data-boc-depth-ready') === '1') {
                    return;
                }

                hero.setAttribute('data-boc-depth-ready', '1');
                hero.style.setProperty('--boc-depth-x', '0px');
                hero.style.setProperty('--boc-depth-y', '0px');
                hero.style.setProperty('--boc-depth-tilt-x', '0deg');
                hero.style.setProperty('--boc-depth-tilt-y', '0deg');

                hero.addEventListener('pointermove', function(event) {
                    var rect = hero.getBoundingClientRect();
                    var x = ((event.clientX - rect.left) / rect.width) - .5;
                    var y = ((event.clientY - rect.top) / rect.height) - .5;

                    hero.style.setProperty('--boc-depth-x', (x * 18).toFixed(2) + 'px');
                    hero.style.setProperty('--boc-depth-y', (y * 14).toFixed(2) + 'px');
                    hero.style.setProperty('--boc-depth-tilt-x', (x * 5).toFixed(2) + 'deg');
                    hero.style.setProperty('--boc-depth-tilt-y', (y * -4).toFixed(2) + 'deg');
                }, { passive: true });

                hero.addEventListener('pointerleave', function() {
                    hero.style.setProperty('--boc-depth-x', '0px');
                    hero.style.setProperty('--boc-depth-y', '0px');
                    hero.style.setProperty('--boc-depth-tilt-x', '0deg');
                    hero.style.setProperty('--boc-depth-tilt-y', '0deg');
                });
            };

            var setupCourseCatalogueNav = function() {
                setActiveLink('programmes');
            };

            var setup = function() {
                var body = document.body;
                if (!body) {
                    return;
                }

                if (body.classList.contains('theme-boost-override-custom-frontpage')) {
                    setupFrontpageNav();
                    return;
                }

                if (body.classList.contains('theme-boost-override-custom-courseindex')) {
                    setupCourseCatalogueNav();
                }
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', setup);
            } else {
                setup();
            }
        })();
JS
    );
}
if ($PAGE->pagetype === 'course-search') {
    $extraclasses[] = 'theme-boost-override-custom-coursesearch';
    $PAGE->requires->js_init_code(<<<'JS'
        (function() {
            var pageSelector = '.theme-boost-override-custom-coursesearch';
            var scheduled = false;
            var dropdownCloseBound = false;
            var courseActionBound = false;

            var cleanText = function(value) {
                return (value || '').replace(/\s+/g, ' ').trim();
            };

            var updateTeacherInitials = function() {
                document.querySelectorAll(pageSelector + ' .course-search-result .coursebox .teachers li').forEach(function(item) {
                    var link = item.querySelector('a');
                    var name = cleanText((link ? link.textContent : item.textContent || '').replace(/^\s*Teacher:\s*/i, ''));
                    var parts = name.split(/[\s-]+/).filter(function(part) {
                        return part && !/^(dr|mr|mrs|ms|prof)\.?$/i.test(part);
                    });
                    var initials = '';

                    if (parts.length > 1) {
                        initials = parts[0].charAt(0) + parts[parts.length - 1].charAt(0);
                    } else if (parts.length === 1) {
                        initials = parts[0].slice(0, 2);
                    }

                    item.setAttribute('data-boc-initials', (initials || 'T').toUpperCase());
                });
            };

            var categoryStyles = [
                { pattern: /pure sciences?|science/i, icon: 0xf0c3, tone: ['#0b6df6', '#04a9d8', '#e8f4ff', '#bfdbfe', '#0751c6'] },
                { pattern: /commerce|finance|biz/i, icon: 0xf0b1, tone: ['#f5a300', '#f47d20', '#fff4d9', '#fed7aa', '#9a4f00'] },
                { pattern: /humanities|arts|creative/i, icon: 0xf1fc, tone: ['#7d4df3', '#f05b72', '#f3ebff', '#e9d5ff', '#6d28d9'] },
                { pattern: /engineering|tech|polytechnic|industrial/i, icon: 0xf085, tone: ['#0f766e', '#14b8a6', '#e6fffb', '#99f6e4', '#0f766e'] },
                { pattern: /computer|applications|blockchain|web3|mlops|full stack/i, icon: 0xf108, tone: ['#2563eb', '#7c3aed', '#eef2ff', '#c7d2fe', '#3730a3'] },
                { pattern: /artificial intelligence|data science|analytics|research/i, icon: 0xf1de, tone: ['#7c3aed', '#06b6d4', '#f0f9ff', '#bae6fd', '#6d28d9'] },
                { pattern: /medical|health|nursing|healthcare|physiotherapy/i, icon: 0xf0fa, tone: ['#dc2626', '#f97316', '#fff1f2', '#fecdd3', '#b91c1c'] },
                { pattern: /law|legal/i, icon: 0xf0e3, tone: ['#4f46e5', '#0ea5e9', '#eef2ff', '#c7d2fe', '#4338ca'] },
                { pattern: /architecture|building/i, icon: 0xf1ad, tone: ['#475569', '#0ea5e9', '#f1f5f9', '#cbd5e1', '#334155'] },
                { pattern: /management|business|marketing|agile|scrum/i, icon: 0xf201, tone: ['#16a34a', '#84cc16', '#f0fdf4', '#bbf7d0', '#15803d'] },
                { pattern: /hospitality|tourism/i, icon: 0xf0f5, tone: ['#db2777', '#f97316', '#fff1f2', '#fbcfe8', '#be185d'] },
                { pattern: /general|foundation/i, icon: 0xf02d, tone: ['#0b6df6', '#04a9d8', '#eaf5ff', '#bfdbfe', '#0751c6'] }
            ];

            var categoryFallback = { icon: 0xf07b, tone: ['#64748b', '#0ea5e9', '#f8fafc', '#cbd5e1', '#475569'] };

            var applyCategoryBadges = function() {
                document.querySelectorAll(pageSelector + ' .course-search-result .coursebox .coursecat').forEach(function(badge) {
                    var label = cleanText(badge.textContent);
                    var match = categoryStyles.find(function(item) {
                        return item.pattern.test(label);
                    }) || categoryFallback;

                    badge.setAttribute('data-boc-category-icon', String.fromCharCode(match.icon));
                    badge.style.setProperty('--boc-cat-start', match.tone[0]);
                    badge.style.setProperty('--boc-cat-end', match.tone[1]);
                    badge.style.setProperty('--boc-cat-bg', match.tone[2]);
                    badge.style.setProperty('--boc-cat-border', match.tone[3]);
                    badge.style.setProperty('--boc-cat-ink', match.tone[4]);
                });
            };

            var setCourseActionOpen = function(actions, open) {
                var card = actions.closest('.coursebox');
                var button = actions.querySelector('[data-boc-course-actions-button]');

                actions.classList.toggle('is-open', open);
                if (card) {
                    card.classList.toggle('boc-card-menu-open', open);
                }
                if (button) {
                    button.setAttribute('aria-expanded', open ? 'true' : 'false');
                }
            };

            var closeCourseActionMenus = function(except) {
                document.querySelectorAll(pageSelector + ' [data-boc-course-actions].is-open').forEach(function(actions) {
                    if (actions !== except) {
                        setCourseActionOpen(actions, false);
                    }
                });
            };

            var setActionFeedback = function(button, label) {
                var original = button.getAttribute('data-boc-original-label') || cleanText(button.textContent);
                button.setAttribute('data-boc-original-label', original);
                button.querySelector('span').textContent = label;
                window.setTimeout(function() {
                    button.querySelector('span').textContent = original;
                }, 1600);
            };

            var copyCourseLink = function(url, button) {
                var fallbackCopy = function() {
                    var input = document.createElement('input');
                    input.value = url;
                    input.setAttribute('readonly', 'readonly');
                    input.style.position = 'fixed';
                    input.style.opacity = '0';
                    document.body.appendChild(input);
                    input.select();
                    document.execCommand('copy');
                    input.remove();
                    setActionFeedback(button, 'Link copied');
                };

                if (navigator.clipboard && navigator.clipboard.writeText) {
                    navigator.clipboard.writeText(url).then(function() {
                        setActionFeedback(button, 'Link copied');
                    }).catch(fallbackCopy);
                    return;
                }

                fallbackCopy();
            };

            var buildCourseActionMenus = function() {
                document.querySelectorAll(pageSelector + ' .course-search-result .coursebox').forEach(function(card) {
                    if (card.getAttribute('data-boc-actions-ready') === '1') {
                        return;
                    }

                    var titleNode = card.querySelector('.coursename a');
                    var title = cleanText(titleNode ? titleNode.textContent : 'Course');
                    var url = titleNode && titleNode.href ? titleNode.href : window.location.href;
                    var actions = document.createElement('div');
                    var trigger = document.createElement('button');
                    var menu = document.createElement('div');
                    var view = document.createElement('a');
                    var share = document.createElement('button');
                    var copy = document.createElement('button');

                    actions.className = 'boc-course-actions';
                    actions.setAttribute('data-boc-course-actions', '1');
                    trigger.type = 'button';
                    trigger.className = 'boc-course-actions-button';
                    trigger.setAttribute('data-boc-course-actions-button', '1');
                    trigger.setAttribute('aria-haspopup', 'menu');
                    trigger.setAttribute('aria-expanded', 'false');
                    trigger.setAttribute('aria-label', 'Course actions for ' + title);
                    trigger.innerHTML = '<span></span><span></span><span></span>';

                    menu.className = 'boc-course-actions-menu';
                    menu.setAttribute('role', 'menu');

                    view.className = 'boc-course-action-item';
                    view.href = url;
                    view.setAttribute('role', 'menuitem');
                    view.innerHTML = '<i class="fa fa-eye" aria-hidden="true"></i><span>View course</span>';

                    share.type = 'button';
                    share.className = 'boc-course-action-item';
                    share.setAttribute('role', 'menuitem');
                    share.innerHTML = '<i class="fa fa-share-alt" aria-hidden="true"></i><span>Share course</span>';

                    copy.type = 'button';
                    copy.className = 'boc-course-action-item';
                    copy.setAttribute('role', 'menuitem');
                    copy.innerHTML = '<i class="fa fa-link" aria-hidden="true"></i><span>Copy link</span>';

                    trigger.addEventListener('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();
                        var isopen = actions.classList.contains('is-open');
                        closeCourseActionMenus(actions);
                        setCourseActionOpen(actions, !isopen);
                    });

                    share.addEventListener('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();
                        if (navigator.share) {
                            navigator.share({ title: title, url: url }).catch(function() {});
                            return;
                        }
                        copyCourseLink(url, share);
                    });

                    copy.addEventListener('click', function(event) {
                        event.preventDefault();
                        event.stopPropagation();
                        copyCourseLink(url, copy);
                    });

                    menu.addEventListener('click', function(event) {
                        event.stopPropagation();
                    });

                    menu.appendChild(view);
                    menu.appendChild(share);
                    menu.appendChild(copy);
                    actions.appendChild(trigger);
                    actions.appendChild(menu);
                    card.appendChild(actions);
                    card.setAttribute('data-boc-actions-ready', '1');
                });

                if (!courseActionBound) {
                    courseActionBound = true;
                    document.addEventListener('click', function(event) {
                        if (!event.target.closest(pageSelector + ' [data-boc-course-actions]')) {
                            closeCourseActionMenus();
                        }
                    });
                    document.addEventListener('keydown', function(event) {
                        if (event.key === 'Escape') {
                            closeCourseActionMenus();
                        }
                    });
                }
            };

            var getSelectedFilterOptions = function(form) {
                return Array.prototype.slice.call(form.querySelectorAll('[data-boc-filter-option]:checked')).map(function(option) {
                    return {
                        label: cleanText(option.value),
                        searchable: option.getAttribute('data-boc-searchable') === '1',
                        group: option.getAttribute('data-boc-filter-group') || ''
                    };
                }).filter(function(option) {
                    return option.label !== '';
                });
            };

            var updateFilterState = function(form) {
                var selected = getSelectedFilterOptions(form);
                var chips = form.querySelector('[data-boc-selected-filters]');

                form.querySelectorAll('[data-boc-filter-dropdown]').forEach(function(dropdown) {
                    var checked = dropdown.querySelectorAll('[data-boc-filter-option]:checked').length;
                    var counter = dropdown.querySelector('[data-boc-dropdown-count]');
                    if (counter) {
                        counter.textContent = checked > 0 ? checked + ' selected' : 'Select';
                    }
                    dropdown.classList.toggle('has-selection', checked > 0);
                });

                if (!chips) {
                    return;
                }

                chips.innerHTML = '';
                if (!selected.length) {
                    var empty = document.createElement('span');
                    empty.className = 'boc-filter-empty';
                    empty.textContent = 'Select one or more filters';
                    chips.appendChild(empty);
                    return;
                }

                selected.forEach(function(option) {
                    var chip = document.createElement('button');
                    var text = document.createElement('span');
                    var icon = document.createElement('i');
                    chip.type = 'button';
                    chip.setAttribute('data-boc-filter-remove', option.label);
                    text.textContent = option.label;
                    icon.className = 'fa fa-times';
                    icon.setAttribute('aria-hidden', 'true');
                    chip.appendChild(text);
                    chip.appendChild(icon);
                    chips.appendChild(chip);
                });
            };

            var filterVisibleCourseCards = function() {
                var form = document.querySelector(pageSelector + ' [data-boc-filter-form]');
                var result = document.querySelector(pageSelector + ' .course-search-result');
                var heading = document.querySelector(pageSelector + ' #region-main div[role="main"] > h2');

                if (!form || !result) {
                    return;
                }

                var clientTerms = getSelectedFilterOptions(form).filter(function(option) {
                    return !option.searchable;
                }).map(function(option) {
                    return option.label.toLowerCase();
                });
                var cards = Array.prototype.slice.call(result.querySelectorAll('.coursebox'));
                var visible = 0;

                cards.forEach(function(card) {
                    var text = cleanText(card.textContent).toLowerCase();
                    var matches = clientTerms.every(function(term) {
                        return text.indexOf(term) !== -1;
                    });
                    card.hidden = !matches;
                    if (matches) {
                        visible++;
                    }
                });

                var empty = result.querySelector('.boc-client-empty');
                if (!empty) {
                    empty = document.createElement('div');
                    empty.className = 'boc-client-empty';
                    empty.textContent = 'No visible courses match the selected page filters.';
                    result.appendChild(empty);
                }
                empty.hidden = !clientTerms.length || visible > 0;

                if (heading) {
                    if (!heading.getAttribute('data-boc-original-heading')) {
                        heading.setAttribute('data-boc-original-heading', cleanText(heading.textContent));
                    }
                    heading.textContent = clientTerms.length ?
                        heading.getAttribute('data-boc-original-heading') + ' · ' + visible + ' visible on this page' :
                        heading.getAttribute('data-boc-original-heading');
                }
            };

            var closeSiblingDropdowns = function(activeDropdown) {
                document.querySelectorAll(pageSelector + ' [data-boc-filter-dropdown][open]').forEach(function(dropdown) {
                    if (dropdown !== activeDropdown) {
                        dropdown.removeAttribute('open');
                    }
                });
            };

            var closeAllDropdowns = function() {
                document.querySelectorAll(pageSelector + ' [data-boc-filter-dropdown][open]').forEach(function(dropdown) {
                    dropdown.removeAttribute('open');
                });
            };

            var setupFilterForm = function() {
                var form = document.querySelector(pageSelector + ' [data-boc-filter-form]');

                if (!form) {
                    return;
                }

                updateFilterState(form);

                if (form.getAttribute('data-boc-ready') === '1') {
                    filterVisibleCourseCards();
                    return;
                }

                form.setAttribute('data-boc-ready', '1');

                form.querySelectorAll('[data-boc-filter-dropdown] > summary').forEach(function(summary) {
                    summary.addEventListener('click', function() {
                        var dropdown = summary.closest('[data-boc-filter-dropdown]');
                        closeSiblingDropdowns(dropdown);
                    });
                });

                form.addEventListener('change', function(event) {
                    if (event.target && event.target.matches('[data-boc-filter-option]')) {
                        updateFilterState(form);
                        filterVisibleCourseCards();
                    }
                });

                form.addEventListener('click', function(event) {
                    var remove = event.target.closest('[data-boc-filter-remove]');
                    if (!remove) {
                        return;
                    }
                    var value = remove.getAttribute('data-boc-filter-remove');
                    form.querySelectorAll('[data-boc-filter-option]').forEach(function(option) {
                        if (option.value === value) {
                            option.checked = false;
                        }
                    });
                    updateFilterState(form);
                    filterVisibleCourseCards();
                });

                form.addEventListener('submit', function() {
                    var input = form.querySelector('[data-boc-free-search]');
                    var terms = [];
                    if (input) {
                        terms.push(cleanText(input.value));
                    }
                    getSelectedFilterOptions(form).forEach(function(option) {
                        if (option.searchable) {
                            terms.push(option.label);
                        }
                    });

                    var unique = [];
                    terms.forEach(function(term) {
                        var lower = term.toLowerCase();
                        if (term !== '' && unique.map(function(item) {
                            return item.toLowerCase();
                        }).indexOf(lower) === -1) {
                            unique.push(term);
                        }
                    });

                    if (input) {
                        input.value = unique.join(' ');
                    }
                });

                if (!dropdownCloseBound) {
                    dropdownCloseBound = true;
                    document.addEventListener('click', function(event) {
                        document.querySelectorAll(pageSelector + ' [data-boc-filter-dropdown][open]').forEach(function(dropdown) {
                            if (!dropdown.contains(event.target)) {
                                dropdown.removeAttribute('open');
                            }
                        });
                    });
                    document.addEventListener('keydown', function(event) {
                        if (event.key === 'Escape') {
                            closeAllDropdowns();
                        }
                    });
                }

                filterVisibleCourseCards();
            };

            var buildCourseHoverPanels = function() {
                document.querySelectorAll(pageSelector + ' .course-search-result .coursebox').forEach(function(card) {
                    if (card.getAttribute('data-boc-hover-ready') === '1') {
                        return;
                    }

                    var titleNode = card.querySelector('.coursename a');
                    var summaryNode = card.querySelector('.summary');
                    var teacherNode = card.querySelector('.teachers a');
                    var categoryNode = card.querySelector('.coursecat a');
                    var title = cleanText(titleNode ? titleNode.textContent : '');
                    var summary = cleanText(summaryNode ? summaryNode.textContent : '');
                    var teacher = cleanText(teacherNode ? teacherNode.textContent : '');
                    var category = cleanText(categoryNode ? categoryNode.textContent : '');
                    var panel = document.createElement('div');
                    var heading = document.createElement('strong');
                    var body = document.createElement('p');
                    var facts = document.createElement('div');
                    var cta = document.createElement(titleNode && titleNode.href ? 'a' : 'span');

                    panel.className = 'boc-course-hover-panel';
                    heading.textContent = title || 'Course preview';
                    body.textContent = summary || 'Structured Moodle course space with learning resources, activities, assessment and progress tracking.';
                    facts.className = 'boc-course-hover-facts';
                    [
                        ['Teacher', teacher || 'Assigned faculty'],
                        ['Category', category || 'General'],
                        ['Learning', 'Activities, resources, grades']
                    ].forEach(function(item) {
                        var fact = document.createElement('span');
                        var label = document.createElement('b');
                        var value = document.createElement('em');
                        label.textContent = item[0];
                        value.textContent = item[1];
                        fact.appendChild(label);
                        fact.appendChild(value);
                        facts.appendChild(fact);
                    });
                    cta.className = 'boc-course-hover-cta';
                    cta.textContent = 'View course';
                    if (titleNode && titleNode.href) {
                        cta.href = titleNode.href;
                        cta.tabIndex = -1;
                    }

                    if (titleNode) {
                        titleNode.addEventListener('mouseenter', function() {
                            card.classList.add('boc-course-preview-open');
                        });
                        titleNode.addEventListener('focus', function() {
                            card.classList.add('boc-course-preview-open');
                        });
                        card.addEventListener('mouseleave', function() {
                            card.classList.remove('boc-course-preview-open');
                        });
                        card.addEventListener('focusout', function() {
                            window.setTimeout(function() {
                                if (!card.contains(document.activeElement)) {
                                    card.classList.remove('boc-course-preview-open');
                                }
                            }, 0);
                        });
                    }

                    panel.appendChild(heading);
                    panel.appendChild(body);
                    panel.appendChild(facts);
                    panel.appendChild(cta);
                    card.appendChild(panel);
                    card.setAttribute('data-boc-hover-ready', '1');
                });
            };

            var enhanceCourseSearch = function() {
                setupFilterForm();
                updateTeacherInitials();
                applyCategoryBadges();
                buildCourseActionMenus();
                buildCourseHoverPanels();
                filterVisibleCourseCards();
            };

            var scheduleEnhancement = function() {
                if (scheduled) {
                    return;
                }
                scheduled = true;
                window.requestAnimationFrame(function() {
                    scheduled = false;
                    enhanceCourseSearch();
                });
            };

            if (document.readyState === 'loading') {
                document.addEventListener('DOMContentLoaded', enhanceCourseSearch);
            } else {
                enhanceCourseSearch();
            }

            if (window.MutationObserver) {
                new MutationObserver(scheduleEnhancement).observe(document.body, {
                    childList: true,
                    subtree: true
                });
            }
        })();
JS
    );
}
if ($courseindexopen) {
    $extraclasses[] = 'drawer-open-index';
}

$blockshtml = $OUTPUT->blocks('side-pre');
$hasblocks = (strpos($blockshtml, 'data-block=') !== false || !empty($addblockbutton));
if (!$hasblocks) {
    $blockdraweropen = false;
}
$courseindex = core_course_drawer();
if (!$courseindex) {
    $courseindexopen = false;
}

$bodyattributes = $OUTPUT->body_attributes($extraclasses);
$forceblockdraweropen = $OUTPUT->firstview_fakeblocks();

$secondarynavigation = false;
$overflow = '';
if ($PAGE->has_secondary_navigation()) {
    $tablistnav = $PAGE->has_tablist_secondary_navigation();
    $moremenu = new \core\navigation\output\more_menu($PAGE->secondarynav, 'nav-tabs', true, $tablistnav);
    $secondarynavigation = $moremenu->export_for_template($OUTPUT);
    $overflowdata = $PAGE->secondarynav->get_overflow_menu_data();
    if (!is_null($overflowdata)) {
        $selectmenu = new \core\output\select_menu(
            'tertiarynavigation',
            $overflowdata->urls,
            $overflowdata->selected,
        );
        $selectmenu->set_label($overflowdata->label, $overflowdata->labelattributes);
        $overflow = $selectmenu->export_for_template($OUTPUT);
    }
}

$primary = new core\navigation\output\primary($PAGE);
$renderer = $PAGE->get_renderer('core');
$primarymenu = $primary->export_for_template($renderer);
if (($isfrontpage || $iscourseindex) && !empty($primarymenu['moremenu'])) {
    $fragmentnavitems = [
        ['key' => 'boc-fragment-discover', 'text' => 'Discover', 'title' => 'Discover', 'anchor' => 'discover'],
        ['key' => 'boc-fragment-programmes', 'text' => 'Programmes', 'title' => 'Programmes', 'anchor' => 'programmes'],
        ['key' => 'boc-fragment-admissions', 'text' => 'Admissions', 'title' => 'Admissions', 'anchor' => 'admissions'],
        ['key' => 'boc-fragment-boards', 'text' => 'Boards', 'title' => 'Boards and mediums', 'anchor' => 'boards-mediums'],
        ['key' => 'boc-fragment-grade-system', 'text' => 'Grade System', 'title' => 'Grade system', 'anchor' => 'grade-system'],
        ['key' => 'boc-fragment-about', 'text' => 'About', 'title' => 'About', 'anchor' => 'about'],
        ['key' => 'boc-fragment-contact', 'text' => 'Contact', 'title' => 'Contact', 'anchor' => 'contact'],
    ];
    $fragmentnodes = [];

    foreach ($fragmentnavitems as $fragmentnavitem) {
        $fragmentnodes[] = [
            'key' => $fragmentnavitem['key'],
            'text' => $fragmentnavitem['text'],
            'title' => $fragmentnavitem['title'],
            'url' => '',
            'action' => $isfrontpage ? '#' . $fragmentnavitem['anchor'] :
                    (new \moodle_url('/', [], $fragmentnavitem['anchor']))->out(false),
            'haschildren' => false,
            'isactive' => false,
            'classes' => ['boc-primary-fragment'],
        ];
    }

    $primarymenu['moremenu']['nodearray'] = array_merge($primarymenu['moremenu']['nodearray'] ?? [], $fragmentnodes);
}
$buildregionmainsettings = !$PAGE->include_region_main_settings_in_header_actions() && !$PAGE->has_secondary_navigation();
$regionmainsettingsmenu = $buildregionmainsettings ? $OUTPUT->region_main_settings_menu() : false;

$header = $PAGE->activityheader;
$headercontent = $header->export_for_template($renderer);

$coursefullname = ($PAGE->course?->fullname) ? format_string(
    $PAGE->course->fullname,
    true,
    ['context' => context_course::instance($PAGE->course->id), 'escape' => false],
) : '';
$courseurl = $PAGE->course ? new \core\url('/course/view.php', ['id' => $PAGE->course->id]) : null;

$templatecontext = [
    'sitename' => format_string($SITE->shortname, true, ['context' => context_course::instance(SITEID), 'escape' => false]),
    'coursefullname' => $coursefullname,
    'courseurl' => $courseurl ? $courseurl->out(false) : null,
    'output' => $OUTPUT,
    'sidepreblocks' => $blockshtml,
    'hasblocks' => $hasblocks,
    'bodyattributes' => $bodyattributes,
    'courseindexopen' => $courseindexopen,
    'blockdraweropen' => $blockdraweropen,
    'courseindex' => $courseindex,
    'primarymoremenu' => $primarymenu['moremenu'],
    'secondarymoremenu' => $secondarynavigation ?: false,
    'mobileprimarynav' => $primarymenu['mobileprimarynav'],
    'usermenu' => $primarymenu['user'],
    'langmenu' => $primarymenu['lang'],
    'forceblockdraweropen' => $forceblockdraweropen,
    'regionmainsettingsmenu' => $regionmainsettingsmenu,
    'hasregionmainsettingsmenu' => !empty($regionmainsettingsmenu),
    'overflow' => $overflow,
    'headercontent' => $headercontent,
    'addblockbutton' => $addblockbutton,
];

echo $OUTPUT->render_from_template('theme_boost/drawers', $templatecontext);
