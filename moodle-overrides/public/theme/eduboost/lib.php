<?php
// This file is part of Moodle - http://moodle.org/.
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

defined('MOODLE_INTERNAL') || die();

/**
 * Sass variables applied before Boost compiles.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_eduboost_get_pre_scss($theme) {
    return <<<'SCSS'
$edb-primary: #0f4c5c;
$edb-primary-dark: #073b4c;
$edb-accent: #2563eb;
$edb-teal: #0f766e;
$edb-gold: #f59e0b;
$edb-ink: #152536;
$edb-muted: #617386;
$edb-surface: #f6f8fb;
$edb-border: #d7e2ea;

$primary: $edb-primary;
$secondary: $edb-muted;
$success: $edb-teal;
$info: $edb-accent;
$warning: $edb-gold;
$danger: #dc2626;
$light: $edb-surface;
$dark: $edb-primary-dark;

$body-bg: $edb-surface;
$body-color: $edb-ink;
$link-color: #1d4ed8;
$link-hover-color: $edb-primary;
$border-color: $edb-border;

$font-family-sans-serif: "Inter", "Aptos", "Segoe UI", "Noto Sans", "Helvetica Neue", Arial, sans-serif;
$headings-font-family: "Inter", "Aptos Display", "Segoe UI", "Noto Sans", "Helvetica Neue", Arial, sans-serif;
$headings-color: $edb-primary-dark;
$headings-font-weight: 700;
$letter-spacing-base: 0;

$border-radius: .5rem;
$border-radius-sm: .375rem;
$border-radius-lg: .625rem;
$card-border-radius: .5rem;
$btn-border-radius: .5rem;
$btn-font-weight: 700;

$box-shadow-sm: 0 .125rem .375rem rgba(15, 76, 92, .08);
$box-shadow: 0 .75rem 1.75rem rgba(15, 76, 92, .10);
$box-shadow-lg: 0 1rem 2.75rem rgba(15, 76, 92, .14);

$input-border-color: #b7c8d6;
$input-focus-border-color: $edb-primary;
$input-focus-box-shadow: 0 0 0 .2rem rgba(37, 99, 235, .18);

$navbar-light-color: $edb-ink;
$navbar-light-hover-color: $edb-primary;
$navbar-light-active-color: $edb-primary-dark;

$nav-tabs-link-active-color: $edb-primary-dark;
$nav-tabs-link-active-bg: #eef6f8;
$nav-tabs-link-active-border-color: transparent;
SCSS;
}

/**
 * Visual-only layer on top of Boost.
 *
 * @param theme_config $theme The theme config object.
 * @return string
 */
function theme_eduboost_get_extra_scss($theme) {
    return <<<'SCSS'
:root {
    --edb-primary: #0f4c5c;
    --edb-primary-dark: #073b4c;
    --edb-accent: #2563eb;
    --edb-teal: #0f766e;
    --edb-gold: #f59e0b;
    --edb-ink: #152536;
    --edb-muted: #617386;
    --edb-surface: #f6f8fb;
    --edb-card: #ffffff;
    --edb-border: #d7e2ea;
    --edb-focus: rgba(37, 99, 235, .22);
}

html {
    text-rendering: optimizeLegibility;
}

body {
    background:
        linear-gradient(180deg, rgba(15, 118, 110, .06) 0, rgba(15, 118, 110, 0) 15rem),
        var(--edb-surface);
    color: var(--edb-ink);
    letter-spacing: 0;
}

a {
    text-underline-offset: .18em;
}

a:focus-visible,
button:focus-visible,
.btn:focus-visible,
.form-control:focus,
.custom-select:focus,
.form-select:focus,
[tabindex]:focus-visible {
    outline: 3px solid var(--edb-focus);
    outline-offset: 2px;
}

.navbar.fixed-top,
.navbar.navbar-light {
    min-height: 64px;
    background: rgba(255, 255, 255, .98);
    border-bottom: 1px solid rgba(215, 226, 234, .9);
    box-shadow: 0 .5rem 1.25rem rgba(15, 76, 92, .07);
}

.navbar .navbar-brand,
.navbar .navbar-nav .nav-link,
.primary-navigation .navigation .nav-link {
    min-height: 44px;
    color: var(--edb-ink);
    font-weight: 700;
}

.navbar .navbar-nav .nav-link:hover,
.navbar .navbar-nav .nav-link:focus,
.primary-navigation .navigation .nav-link:hover,
.primary-navigation .navigation .nav-link:focus {
    color: var(--edb-primary);
    background: rgba(15, 118, 110, .08);
}

.navbar .navbar-nav .nav-link.active,
.primary-navigation .navigation .nav-link.active,
.moremenu .nav-link.active {
    color: var(--edb-primary-dark);
    background: #eef6f8;
    box-shadow: inset 0 -3px 0 var(--edb-teal);
}

#page.drawers .main-inner {
    background: rgba(255, 255, 255, .98);
    border: 1px solid rgba(215, 226, 234, .9);
    box-shadow: 0 .75rem 1.75rem rgba(15, 76, 92, .08);
}

.secondary-navigation,
.tertiary-navigation {
    background: var(--edb-card);
    border: 1px solid rgba(215, 226, 234, .9);
    border-radius: .5rem;
    box-shadow: 0 .375rem 1rem rgba(15, 76, 92, .06);
}

.secondary-navigation .moremenu .nav-link,
.nav-tabs .nav-link,
.nav-pills .nav-link {
    min-height: 44px;
    color: var(--edb-ink);
    font-weight: 700;
    border-radius: .5rem;
}

.secondary-navigation .moremenu .nav-link:hover,
.nav-tabs .nav-link:hover,
.nav-pills .nav-link:hover {
    color: var(--edb-primary);
    background: rgba(15, 118, 110, .08);
}

.secondary-navigation .moremenu .nav-link.active,
.nav-tabs .nav-link.active,
.nav-pills .nav-link.active {
    color: var(--edb-primary-dark);
    background: #eef6f8;
    border-color: transparent;
    box-shadow: inset 0 -3px 0 var(--edb-teal);
}

.card,
.block,
.modal-content,
.popover,
.dropdown-menu,
.coursebox,
.dashboard-card,
.que {
    border-color: var(--edb-border);
    border-radius: .5rem;
    box-shadow: 0 .375rem 1rem rgba(15, 76, 92, .06);
}

.card-header,
.block .card-title,
.modal-header,
.popover-header {
    background: #f8fbfd;
    border-color: var(--edb-border);
    color: var(--edb-primary-dark);
    font-weight: 700;
}

.btn-primary,
.btn-secondary {
    font-weight: 700;
}

.btn-primary {
    background: var(--edb-primary);
    border-color: var(--edb-primary);
}

.btn-primary:hover,
.btn-primary:focus {
    background: var(--edb-primary-dark);
    border-color: var(--edb-primary-dark);
}

.badge,
.tag {
    border-radius: 999px;
}

.alert-info {
    color: #0b3a4a;
    background: #eef6f8;
    border-color: #cbe4ea;
}

#page-footer.footer-popover {
    border-top: 1px solid var(--edb-border);
}

@media (prefers-reduced-motion: no-preference) {
    .btn,
    .card,
    .block,
    .coursebox,
    .dashboard-card,
    .dropdown-item,
    .nav-link {
        transition:
            background-color 180ms ease,
            border-color 180ms ease,
            box-shadow 180ms ease,
            color 180ms ease,
            transform 200ms cubic-bezier(.2, .8, .2, 1);
    }

    .btn:hover,
    .dashboard-card:hover,
    .coursebox:hover {
        transform: translateY(-1px);
    }
}

@media (max-width: 767.98px) {
    .navbar .navbar-nav .nav-link,
    .secondary-navigation .moremenu .nav-link,
    .nav-tabs .nav-link,
    .nav-pills .nav-link {
        min-height: 48px;
    }

    #page.drawers .main-inner {
        border-right: 0;
        border-left: 0;
        border-radius: 0;
        box-shadow: none;
    }
}
SCSS;
}
