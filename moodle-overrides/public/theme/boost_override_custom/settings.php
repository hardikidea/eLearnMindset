<?php
// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Administration settings for Boost Override Custom.
 *
 * @package    theme_boost_override_custom
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {
    $settings = new theme_boost_admin_settingspage_tabs(
        'themesettingboost_override_custom',
        get_string('configtitle', 'theme_boost_override_custom')
    );

    $addsetting = static function(admin_settingpage $page, admin_setting $setting): void {
        $setting->set_updatedcallback('theme_reset_all_caches');
        $page->add($setting);
    };

    $branding = new admin_settingpage(
        'theme_boost_override_custom_branding',
        get_string('branding', 'theme_boost_override_custom')
    );
    $addsetting($branding, new admin_setting_configtext(
        'theme_boost_override_custom/institutionname',
        get_string('institutionname', 'theme_boost_override_custom'),
        get_string('institutionname_desc', 'theme_boost_override_custom'),
        '',
        PARAM_TEXT
    ));
    $addsetting($branding, new admin_setting_configtext(
        'theme_boost_override_custom/productname',
        get_string('productname', 'theme_boost_override_custom'),
        get_string('productname_desc', 'theme_boost_override_custom'),
        'Learning Management System',
        PARAM_TEXT
    ));
    $addsetting($branding, new admin_setting_configtext(
        'theme_boost_override_custom/tagline',
        get_string('tagline', 'theme_boost_override_custom'),
        get_string('tagline_desc', 'theme_boost_override_custom'),
        'Learn, teach, assess and grow from one secure digital campus.',
        PARAM_TEXT
    ));

    $imageoptions = ['maxfiles' => 1, 'accepted_types' => ['image']];
    foreach ([
        'mainlogo' => 'mainlogo',
        'compactlogo' => 'compactlogo',
        'lightlogo' => 'lightlogo',
        'darklogo' => 'darklogo',
        'pagebackgroundimage' => 'pagebackgroundimage',
        'loginbackgroundimage' => 'loginbackgroundimage',
    ] as $settingname => $filearea) {
        $addsetting($branding, new admin_setting_configstoredfile(
            'theme_boost_override_custom/' . $settingname,
            get_string($settingname, 'theme_boost_override_custom'),
            get_string($settingname . '_desc', 'theme_boost_override_custom'),
            $filearea,
            0,
            $imageoptions
        ));
    }
    $addsetting($branding, new admin_setting_configstoredfile(
        'theme_boost_override_custom/favicon',
        get_string('favicon', 'theme_boost_override_custom'),
        get_string('favicon_desc', 'theme_boost_override_custom'),
        'favicon',
        0,
        ['maxfiles' => 1, 'accepted_types' => ['.ico', '.png', '.svg']]
    ));
    $settings->add($branding);

    $colours = new admin_settingpage(
        'theme_boost_override_custom_colours',
        get_string('colours', 'theme_boost_override_custom')
    );
    $colourdefaults = [
        'primarycolor' => '#e4000f',
        'primarystrongcolor' => '#b6000c',
        'secondarycolor' => '#0c3759',
        'accentcolor' => '#ffd302',
        'highlightcolor' => '#e08405',
        'successcolor' => '#15803d',
        'warningcolor' => '#e08405',
        'dangercolor' => '#b6000c',
        'infocolor' => '#0c3759',
        'headerbackground' => '#ffffff',
        'headerforeground' => '#0c3759',
        'footerbackground' => '#0c3759',
        'footerforeground' => '#ffffff',
        'sidebarbackground' => '#ffffff',
        'sidebarforeground' => '#0c3759',
        'pagebackground' => '#ffffff',
        'surfacebackground' => '#ffffff',
        'textprimary' => '#0c3759',
        'textsecondary' => '#334155',
        'linkcolor' => '#0c3759',
        'linkhovercolor' => '#b6000c',
        'focuscolor' => '#e4000f',
        'bordercolor' => '#d6dee6',
    ];
    foreach ($colourdefaults as $settingname => $default) {
        $addsetting($colours, new admin_setting_configcolourpicker(
            'theme_boost_override_custom/' . $settingname,
            get_string($settingname, 'theme_boost_override_custom'),
            get_string($settingname . '_desc', 'theme_boost_override_custom'),
            $default
        ));
    }
    $settings->add($colours);

    $interface = new admin_settingpage(
        'theme_boost_override_custom_interface',
        get_string('interfacesettings', 'theme_boost_override_custom')
    );
    $addsetting($interface, new admin_setting_configselect(
        'theme_boost_override_custom/fontstack',
        get_string('fontstack', 'theme_boost_override_custom'),
        get_string('fontstack_desc', 'theme_boost_override_custom'),
        'system',
        [
            'system' => get_string('fontstacksystem', 'theme_boost_override_custom'),
            'humanist' => get_string('fontstackhumanist', 'theme_boost_override_custom'),
            'classic' => get_string('fontstackclassic', 'theme_boost_override_custom'),
        ]
    ));
    $addsetting($interface, new admin_setting_configselect(
        'theme_boost_override_custom/basefontsize',
        get_string('basefontsize', 'theme_boost_override_custom'),
        get_string('basefontsize_desc', 'theme_boost_override_custom'),
        16,
        [14 => '14 px', 15 => '15 px', 16 => '16 px', 17 => '17 px', 18 => '18 px']
    ));
    $addsetting($interface, new admin_setting_configselect(
        'theme_boost_override_custom/density',
        get_string('density', 'theme_boost_override_custom'),
        get_string('density_desc', 'theme_boost_override_custom'),
        'comfortable',
        [
            'compact' => get_string('densitycompact', 'theme_boost_override_custom'),
            'comfortable' => get_string('densitycomfortable', 'theme_boost_override_custom'),
        ]
    ));
    $addsetting($interface, new admin_setting_configselect(
        'theme_boost_override_custom/cardradius',
        get_string('cardradius', 'theme_boost_override_custom'),
        get_string('cardradius_desc', 'theme_boost_override_custom'),
        6,
        [0 => '0 px', 2 => '2 px', 4 => '4 px', 6 => '6 px']
    ));
    $addsetting($interface, new admin_setting_configselect(
        'theme_boost_override_custom/iconssize',
        get_string('iconssize', 'theme_boost_override_custom'),
        get_string('iconssize_desc', 'theme_boost_override_custom'),
        18,
        [16 => '16 px', 18 => '18 px', 20 => '20 px', 22 => '22 px']
    ));
    $addsetting($interface, new admin_setting_configselect(
        'theme_boost_override_custom/headerheight',
        get_string('headerheight', 'theme_boost_override_custom'),
        get_string('headerheight_desc', 'theme_boost_override_custom'),
        72,
        [64 => '64 px', 68 => '68 px', 72 => '72 px', 76 => '76 px', 80 => '80 px']
    ));
    $addsetting($interface, new admin_setting_configselect(
        'theme_boost_override_custom/headerlogowidth',
        get_string('headerlogowidth', 'theme_boost_override_custom'),
        get_string('headerlogowidth_desc', 'theme_boost_override_custom'),
        180,
        [120 => '120 px', 150 => '150 px', 180 => '180 px', 220 => '220 px', 260 => '260 px']
    ));
    $addsetting($interface, new admin_setting_configselect(
        'theme_boost_override_custom/contentwidth',
        get_string('contentwidth', 'theme_boost_override_custom'),
        get_string('contentwidth_desc', 'theme_boost_override_custom'),
        1600,
        [1200 => '1200 px', 1440 => '1440 px', 1600 => '1600 px', 1760 => '1760 px', 1920 => '1920 px']
    ));
    $addsetting($interface, new admin_setting_configselect(
        'theme_boost_override_custom/shadowlevel',
        get_string('shadowlevel', 'theme_boost_override_custom'),
        get_string('shadowlevel_desc', 'theme_boost_override_custom'),
        'subtle',
        [
            'none' => get_string('shadownone', 'theme_boost_override_custom'),
            'subtle' => get_string('shadowsubtle', 'theme_boost_override_custom'),
            'raised' => get_string('shadowraised', 'theme_boost_override_custom'),
        ]
    ));
    $addsetting($interface, new admin_setting_configcheckbox(
        'theme_boost_override_custom/animationsenabled',
        get_string('animationsenabled', 'theme_boost_override_custom'),
        get_string('animationsenabled_desc', 'theme_boost_override_custom'),
        1
    ));
    $addsetting($interface, new admin_setting_configcheckbox(
        'theme_boost_override_custom/highcontrastenhancement',
        get_string('highcontrastenhancement', 'theme_boost_override_custom'),
        get_string('highcontrastenhancement_desc', 'theme_boost_override_custom'),
        0
    ));
    $settings->add($interface);

    $footer = new admin_settingpage(
        'theme_boost_override_custom_footer',
        get_string('footersettings', 'theme_boost_override_custom')
    );
    $addsetting($footer, new admin_setting_configtextarea(
        'theme_boost_override_custom/contactdetails',
        get_string('contactdetails', 'theme_boost_override_custom'),
        get_string('contactdetails_desc', 'theme_boost_override_custom'),
        '',
        PARAM_TEXT
    ));
    foreach (['support', 'privacy', 'terms', 'accessibility', 'documentation'] as $linktype) {
        $addsetting($footer, new admin_setting_configtext(
            'theme_boost_override_custom/' . $linktype . 'url',
            get_string($linktype . 'url', 'theme_boost_override_custom'),
            get_string($linktype . 'url_desc', 'theme_boost_override_custom'),
            '',
            PARAM_URL
        ));
    }
    $addsetting($footer, new admin_setting_configtext(
        'theme_boost_override_custom/copyrighttext',
        get_string('copyrighttext', 'theme_boost_override_custom'),
        get_string('copyrighttext_desc', 'theme_boost_override_custom'),
        '',
        PARAM_TEXT
    ));
    $addsetting($footer, new admin_setting_configtext(
        'theme_boost_override_custom/poweredbytext',
        get_string('poweredbytext', 'theme_boost_override_custom'),
        get_string('poweredbytext_desc', 'theme_boost_override_custom'),
        'Powered by Moodle',
        PARAM_TEXT
    ));
    $addsetting($footer, new admin_setting_configcheckbox(
        'theme_boost_override_custom/showbrandfooter',
        get_string('showbrandfooter', 'theme_boost_override_custom'),
        get_string('showbrandfooter_desc', 'theme_boost_override_custom'),
        1
    ));
    $settings->add($footer);
}
