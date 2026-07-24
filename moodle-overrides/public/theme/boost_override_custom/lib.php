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
 * Boost Override Custom theme callbacks.
 *
 * @package    theme_boost_override_custom
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * Return a configured theme value without bypassing Moodle's config cache.
 *
 * @param string $name Setting name.
 * @param mixed $default Default value.
 * @return mixed
 */
function theme_boost_override_custom_get_setting(string $name, $default = '') {
    $value = get_config('theme_boost_override_custom', $name);

    return $value === false || $value === null || $value === '' ? $default : $value;
}

/**
 * Return a validated six-digit hexadecimal colour.
 *
 * @param string $name Setting name.
 * @param string $default Default colour.
 * @return string
 */
function theme_boost_override_custom_get_colour(string $name, string $default): string {
    $value = (string)theme_boost_override_custom_get_setting($name, $default);

    return preg_match('/^#[0-9a-f]{6}$/i', $value) ? $value : $default;
}

/**
 * Return the URL for a file uploaded through the theme settings.
 *
 * @param string $setting Setting name.
 * @param string $filearea File area.
 * @return moodle_url|null
 */
function theme_boost_override_custom_get_setting_file_url(string $setting, string $filearea) {
    global $CFG;

    $theme = theme_config::load('boost_override_custom');
    $url = $theme->setting_file_url($setting, $filearea);
    if (!$url) {
        return null;
    }

    if (str_starts_with($url, '//')) {
        $scheme = parse_url($CFG->wwwroot, PHP_URL_SCHEME) ?: 'http';
        $url = $scheme . ':' . $url;
    }

    return new moodle_url($url);
}

/**
 * Build the reusable white-label context used by headers and footers.
 *
 * @param renderer_base $output Page renderer.
 * @return array
 */
function theme_boost_override_custom_get_brand_context(renderer_base $output): array {
    global $SITE;

    $sitecontext = context_course::instance(SITEID);
    $institution = (string)theme_boost_override_custom_get_setting('institutionname', '');
    if ($institution === '') {
        $institution = format_string($SITE->fullname, true, ['context' => $sitecontext]);
    } else {
        $institution = format_string($institution, true, ['context' => $sitecontext]);
    }

    $mainlogo = theme_boost_override_custom_get_setting_file_url('mainlogo', 'mainlogo');
    $compactlogo = theme_boost_override_custom_get_setting_file_url('compactlogo', 'compactlogo');
    $lightlogo = theme_boost_override_custom_get_setting_file_url('lightlogo', 'lightlogo');
    $darklogo = theme_boost_override_custom_get_setting_file_url('darklogo', 'darklogo');
    $renderermainlogo = $output->get_logo_url();
    $renderercompactlogo = $output->get_compact_logo_url();
    $mainlogo = $mainlogo ?: ($renderermainlogo ?: null);
    $compactlogo = $compactlogo ?: ($renderercompactlogo ?: $mainlogo);
    $lightlogo = $lightlogo ?: $mainlogo;
    $darklogo = $darklogo ?: $mainlogo;

    $links = [];
    foreach ([
        'support' => 'footerlinksupport',
        'privacy' => 'footerlinkprivacy',
        'terms' => 'footerlinkterms',
        'accessibility' => 'footerlinkaccessibility',
        'documentation' => 'footerlinkdocumentation',
    ] as $key => $labelstring) {
        $url = (string)theme_boost_override_custom_get_setting($key . 'url', '');
        if ($url !== '') {
            $links[] = [
                'label' => get_string($labelstring, 'theme_boost_override_custom'),
                'url' => $url,
            ];
        }
    }

    $copyright = (string)theme_boost_override_custom_get_setting('copyrighttext', '');
    if ($copyright === '') {
        $copyright = $institution . '. ' . get_string('allrightsreserved', 'theme_boost_override_custom');
    }

    return [
        'institutionname' => $institution,
        'productname' => format_string((string)theme_boost_override_custom_get_setting(
            'productname',
            'Learning Management System'
        )),
        'tagline' => format_string((string)theme_boost_override_custom_get_setting(
            'tagline',
            'Learn, teach, assess and grow from one secure digital campus.'
        )),
        'hasmainlogo' => !empty($mainlogo),
        'mainlogourl' => $mainlogo ? $mainlogo->out(false) : '',
        'hascompactlogo' => !empty($compactlogo),
        'compactlogourl' => $compactlogo ? $compactlogo->out(false) : '',
        'haslightlogo' => !empty($lightlogo),
        'lightlogourl' => $lightlogo ? $lightlogo->out(false) : '',
        'hasdarklogo' => !empty($darklogo),
        'darklogourl' => $darklogo ? $darklogo->out(false) : '',
        'contactdetails' => format_string((string)theme_boost_override_custom_get_setting('contactdetails', '')),
        'hascontactdetails' => (string)theme_boost_override_custom_get_setting('contactdetails', '') !== '',
        'links' => $links,
        'haslinks' => !empty($links),
        'copyrighttext' => format_string($copyright),
        'poweredbytext' => format_string((string)theme_boost_override_custom_get_setting(
            'poweredbytext',
            'Powered by Moodle'
        )),
        'showbrandfooter' => !empty(theme_boost_override_custom_get_setting('showbrandfooter', 1)),
        'year' => userdate(time(), '%Y'),
    ];
}

/**
 * Get the shared white-label palette SCSS.
 *
 * @return string
 */
function theme_boost_override_custom_get_palette_scss(): string {
    global $CFG;

    $palettefile = $CFG->dirroot . '/theme/boost_override_custom/scss/_palette.scss';

    return file_exists($palettefile) ? file_get_contents($palettefile) : '';
}

/**
 * Returns Boost's main SCSS content.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_boost_override_custom_get_main_scss_content($theme): string {
    global $CFG;

    require_once($CFG->dirroot . '/theme/boost/lib.php');
    return theme_boost_get_main_scss_content($theme);
}

/**
 * Get SCSS to prepend.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_boost_override_custom_get_pre_scss($theme): string {
    global $CFG;

    require_once($CFG->dirroot . '/theme/boost/lib.php');
    $fontchoices = [
        'system' => 'system-ui, -apple-system, BlinkMacSystemFont, "Segoe UI", sans-serif',
        'humanist' => '"Trebuchet MS", "Segoe UI", system-ui, sans-serif',
        'classic' => 'Georgia, "Times New Roman", serif',
    ];
    $fontkey = (string)theme_boost_override_custom_get_setting('fontstack', 'system');
    $fontstack = $fontchoices[$fontkey] ?? $fontchoices['system'];
    $variables = [
        'boc-palette-primary' => theme_boost_override_custom_get_colour('primarycolor', '#e4000f'),
        'boc-palette-primary-strong' => theme_boost_override_custom_get_colour('primarystrongcolor', '#b6000c'),
        'boc-palette-secondary' => theme_boost_override_custom_get_colour('secondarycolor', '#0c3759'),
        'boc-palette-accent' => theme_boost_override_custom_get_colour('accentcolor', '#ffd302'),
        'boc-palette-success' => theme_boost_override_custom_get_colour('successcolor', '#15803d'),
        'boc-palette-warning' => theme_boost_override_custom_get_colour('warningcolor', '#e08405'),
        'boc-palette-warning-bright' => theme_boost_override_custom_get_colour('highlightcolor', '#e08405'),
        'boc-palette-danger' => theme_boost_override_custom_get_colour('dangercolor', '#b6000c'),
        'boc-palette-info' => theme_boost_override_custom_get_colour('infocolor', '#0c3759'),
        'boc-palette-ink' => theme_boost_override_custom_get_colour('textprimary', '#0c3759'),
        'boc-palette-text' => theme_boost_override_custom_get_colour('textsecondary', '#334155'),
        'boc-palette-surface' => theme_boost_override_custom_get_colour('surfacebackground', '#ffffff'),
        'boc-palette-border' => theme_boost_override_custom_get_colour('bordercolor', '#d6dee6'),
        'boc-palette-font-family' => $fontstack,
    ];
    $settingsscss = '';
    foreach ($variables as $name => $value) {
        $settingsscss .= '$' . $name . ': ' . $value . ";\n";
    }

    return $settingsscss . theme_boost_override_custom_get_palette_scss() . "\n" .
        theme_boost_get_pre_scss($theme);
}

/**
 * Inject additional SCSS.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_boost_override_custom_get_extra_scss($theme): string {
    global $CFG;

    require_once($CFG->dirroot . '/theme/boost/lib.php');

    $primary = theme_boost_override_custom_get_colour('primarycolor', '#e4000f');
    $primarystrong = theme_boost_override_custom_get_colour('primarystrongcolor', '#b6000c');
    $secondary = theme_boost_override_custom_get_colour('secondarycolor', '#0c3759');
    $accent = theme_boost_override_custom_get_colour('accentcolor', '#ffd302');
    $highlight = theme_boost_override_custom_get_colour('highlightcolor', '#e08405');
    $success = theme_boost_override_custom_get_colour('successcolor', '#15803d');
    $warning = theme_boost_override_custom_get_colour('warningcolor', '#e08405');
    $danger = theme_boost_override_custom_get_colour('dangercolor', '#b6000c');
    $info = theme_boost_override_custom_get_colour('infocolor', '#0c3759');
    $headerbackground = theme_boost_override_custom_get_colour('headerbackground', '#ffffff');
    $headerforeground = theme_boost_override_custom_get_colour('headerforeground', '#0c3759');
    $footerbackground = theme_boost_override_custom_get_colour('footerbackground', '#0c3759');
    $footerforeground = theme_boost_override_custom_get_colour('footerforeground', '#ffffff');
    $sidebarbackground = theme_boost_override_custom_get_colour('sidebarbackground', '#ffffff');
    $sidebarforeground = theme_boost_override_custom_get_colour('sidebarforeground', '#0c3759');
    $pagebackground = theme_boost_override_custom_get_colour('pagebackground', '#ffffff');
    $surfacebackground = theme_boost_override_custom_get_colour('surfacebackground', '#ffffff');
    $textprimary = theme_boost_override_custom_get_colour('textprimary', '#0c3759');
    $textsecondary = theme_boost_override_custom_get_colour('textsecondary', '#334155');
    $linkcolor = theme_boost_override_custom_get_colour('linkcolor', '#0c3759');
    $linkhover = theme_boost_override_custom_get_colour('linkhovercolor', '#b6000c');
    $focus = theme_boost_override_custom_get_colour('focuscolor', '#e4000f');
    $border = theme_boost_override_custom_get_colour('bordercolor', '#d6dee6');

    $basefontsize = max(14, min(18, (int)theme_boost_override_custom_get_setting('basefontsize', 16)));
    $radius = max(0, min(6, (int)theme_boost_override_custom_get_setting('cardradius', 6)));
    $iconsize = max(16, min(22, (int)theme_boost_override_custom_get_setting('iconssize', 18)));
    $headerheight = max(64, min(80, (int)theme_boost_override_custom_get_setting('headerheight', 72)));
    $headerlogowidth = max(120, min(260, (int)theme_boost_override_custom_get_setting('headerlogowidth', 180)));
    $contentwidth = max(1200, min(1920, (int)theme_boost_override_custom_get_setting('contentwidth', 1600)));
    $density = (string)theme_boost_override_custom_get_setting('density', 'comfortable');
    $controlheight = $density === 'compact' ? 38 : 42;
    $contentgutter = $density === 'compact' ? 'clamp(12px, 1.5vw, 24px)' : 'clamp(14px, 2vw, 30px)';
    $shadowchoices = [
        'none' => 'none',
        'subtle' => '0 10px 28px rgba(12, 55, 89, .08)',
        'raised' => '0 16px 38px rgba(12, 55, 89, .13)',
    ];
    $shadowkey = (string)theme_boost_override_custom_get_setting('shadowlevel', 'subtle');
    $shadow = $shadowchoices[$shadowkey] ?? $shadowchoices['subtle'];
    $highcontrast = !empty(theme_boost_override_custom_get_setting('highcontrastenhancement', 0));
    $animations = !empty(theme_boost_override_custom_get_setting('animationsenabled', 1));
    $borderwidth = $highcontrast ? 2 : 1;
    $focuswidth = $highcontrast ? 4 : 3;

    $extrascss = <<<SCSS
:root {
    --color-primary: {$primary};
    --color-primary-strong: {$primarystrong};
    --color-secondary: {$secondary};
    --color-accent: {$accent};
    --color-highlight: {$highlight};
    --color-success: {$success};
    --color-warning: {$warning};
    --color-danger: {$danger};
    --color-info: {$info};
    --header-background: {$headerbackground};
    --header-foreground: {$headerforeground};
    --footer-background: {$footerbackground};
    --footer-foreground: {$footerforeground};
    --sidebar-background: {$sidebarbackground};
    --sidebar-foreground: {$sidebarforeground};
    --page-background: {$pagebackground};
    --surface-background: {$surfacebackground};
    --text-primary: {$textprimary};
    --text-secondary: {$textsecondary};
    --link-color: {$linkcolor};
    --link-hover-color: {$linkhover};
    --focus-color: {$focus};
    --border-color: {$border};
    --boc-theme-primary: {$primary};
    --boc-theme-primary-strong: {$primarystrong};
    --boc-theme-secondary: {$secondary};
    --boc-theme-accent: {$accent};
    --boc-theme-warning: {$warning};
    --boc-theme-warning-bright: {$highlight};
    --boc-theme-success: {$success};
    --boc-theme-danger: {$danger};
    --boc-theme-info: {$info};
    --boc-theme-ink: {$textprimary};
    --boc-theme-text: {$textsecondary};
    --boc-theme-surface: {$surfacebackground};
    --boc-theme-page-bg: {$pagebackground};
    --boc-theme-border: {$border};
    --boc-theme-focus: {$focus};
    --boc-theme-gradient-primary: {$primary};
    --boc-theme-gradient-warm: {$warning};
    --boc-theme-base-font-size: {$basefontsize}px;
    --boc-theme-radius-md: {$radius}px;
    --boc-theme-icon-size: {$iconsize}px;
    --boc-theme-header-height: {$headerheight}px;
    --boc-theme-header-logo-width: {$headerlogowidth}px;
    --boc-theme-content-width: {$contentwidth}px;
    --boc-theme-control-height: {$controlheight}px;
    --boc-theme-content-gutter: {$contentgutter};
    --boc-theme-shadow-soft: {$shadow};
    --boc-theme-border-width: {$borderwidth}px;
    --boc-theme-focus-width: {$focuswidth}px;
}
SCSS;

    $pagebackgroundimage = theme_boost_override_custom_get_setting_file_url(
        'pagebackgroundimage',
        'pagebackgroundimage'
    );
    if ($pagebackgroundimage) {
        $url = str_replace(['\\', '"'], ['\\\\', '\\"'], $pagebackgroundimage->out(false));
        $extrascss .= "\nbody.theme-boost-override-custom-platform { --boc-page-background-image: url(\"{$url}\"); }\n";
    }
    $loginbackgroundimage = theme_boost_override_custom_get_setting_file_url(
        'loginbackgroundimage',
        'loginbackgroundimage'
    );
    if ($loginbackgroundimage) {
        $url = str_replace(['\\', '"'], ['\\\\', '\\"'], $loginbackgroundimage->out(false));
        $extrascss .= "\nbody.theme-boost-override-custom-login { --boc-login-background-image: url(\"{$url}\"); }\n";
    }
    if (!$animations) {
        $extrascss .= <<<'SCSS'

body.theme-boost-override-custom-platform *,
body.theme-boost-override-custom-login * {
    animation-duration: .001ms !important;
    animation-iteration-count: 1 !important;
    transition-duration: .001ms !important;
}
SCSS;
    }

    return $extrascss . "\n" . theme_boost_get_extra_scss($theme);
}

/**
 * Get compiled Boost CSS as the base stylesheet.
 *
 * @return string
 */
function theme_boost_override_custom_get_precompiled_css(): string {
    global $CFG;

    return file_get_contents($CFG->dirroot . '/theme/boost/style/moodle.css');
}

/**
 * Serve files uploaded through Boost Override Custom settings.
 *
 * @param stdClass $course Course record.
 * @param stdClass|null $cm Course module.
 * @param context $context File context.
 * @param string $filearea File area.
 * @param array $args File arguments.
 * @param bool $forcedownload Whether to force download.
 * @param array $options File options.
 * @return bool
 */
function theme_boost_override_custom_pluginfile($course, $cm, $context, $filearea, $args, $forcedownload,
        array $options = []) {
    $allowedareas = [
        'mainlogo',
        'compactlogo',
        'lightlogo',
        'darklogo',
        'favicon',
        'pagebackgroundimage',
        'loginbackgroundimage',
    ];

    if ($context->contextlevel !== CONTEXT_SYSTEM || !in_array($filearea, $allowedareas, true)) {
        send_file_not_found();
    }

    $theme = theme_config::load('boost_override_custom');
    $options['cacheability'] = 'public';

    return $theme->setting_file_serve($filearea, $args, $forcedownload, $options);
}
