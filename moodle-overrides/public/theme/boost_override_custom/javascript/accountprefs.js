(function() {
    'use strict';

    var bodySelector = 'body.theme-boost-override-custom-accountprefs';
    var state = {
        shell: null,
        metrics: {},
        guide: {},
        scheduled: false,
        observer: null
    };
    var svgSeed = 0;

    var pageMeta = {
        '/user/edit.php': {
            key: 'profile',
            title: 'Edit profile',
            eyebrow: 'Profile identity',
            description: 'Keep user identity, contact details, profile image and optional account information accurate for Moodle workflows.',
            visual: 'profile',
            accent: 'blue'
        },
        '/user/language.php': {
            key: 'language',
            title: 'Preferred language',
            eyebrow: 'Language access',
            description: 'Choose the interface language used across Moodle, messages, courses and learning tools.',
            visual: 'language',
            accent: 'teal'
        },
        '/user/editor.php': {
            key: 'editor',
            title: 'Editor preferences',
            eyebrow: 'Content creation',
            description: 'Select the default text editor used when creating course content, forum posts and learning resources.',
            visual: 'editor',
            accent: 'violet'
        },
        '/user/contentbank.php': {
            key: 'contentbank',
            title: 'Content bank preferences',
            eyebrow: 'Reusable content',
            description: 'Control default visibility for content-bank resources while Moodle preserves permission checks.',
            visual: 'content',
            accent: 'orange'
        },
        '/login/change_password.php': {
            key: 'password',
            title: 'Change password',
            eyebrow: 'Secure account',
            description: 'Update account credentials using Moodle password rules, session protection and validation.',
            visual: 'lock',
            accent: 'red'
        },
        '/user/forum.php': {
            key: 'forum',
            title: 'Forum preferences',
            eyebrow: 'Discussion settings',
            description: 'Tune forum email digests, subscriptions, tracking and notification read behaviour.',
            visual: 'forum',
            accent: 'green'
        },
        '/user/calendar.php': {
            key: 'calendar',
            title: 'Calendar preferences',
            eyebrow: 'Planning settings',
            description: 'Set time display, week start, upcoming-event limits and calendar filter persistence.',
            visual: 'calendar',
            accent: 'blue'
        },
        '/message/edit.php': {
            key: 'message',
            title: 'Message preferences',
            eyebrow: 'Communication',
            description: 'Review Moodle message preference availability for this user account.',
            visual: 'message',
            accent: 'teal'
        }
    };

    var cleanText = function(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    };

    var createNode = function(tag, className, text) {
        var node = document.createElement(tag);
        if (className) {
            node.className = className;
        }
        if (typeof text === 'string') {
            node.textContent = text;
        }
        return node;
    };

    var uniqueId = function(name) {
        svgSeed += 1;
        return 'boc-ap-' + name + '-' + svgSeed;
    };

    var pagePath = function() {
        return window.location.pathname;
    };

    var currentMeta = function() {
        var meta = pageMeta[pagePath()];
        var docTitle;

        if (meta) {
            return meta;
        }

        docTitle = cleanText(document.title.split('|')[0]).replace(/^elearnmindset:\s*/i, '');
        return {
            key: 'account',
            title: docTitle || cleanText(document.querySelector('#region-main h2, h2') && document.querySelector('#region-main h2, h2').textContent) || 'Account preferences',
            eyebrow: 'User account',
            description: 'Manage this Moodle account preference while preserving all platform controls and validation.',
            visual: 'profile',
            accent: 'blue'
        };
    };

    var pageUrl = function(path) {
        var params = new URLSearchParams(window.location.search);
        var userId = params.get('id') || params.get('userid') || '20';
        var courseId = params.get('course') || '1';
        var map = {
            '/user/edit.php': '?id=' + encodeURIComponent(userId) + '&course=' + encodeURIComponent(courseId),
            '/user/language.php': '?id=' + encodeURIComponent(userId) + '&course=' + encodeURIComponent(courseId),
            '/user/editor.php': '?id=' + encodeURIComponent(userId) + '&course=' + encodeURIComponent(courseId),
            '/user/contentbank.php': '?id=' + encodeURIComponent(userId),
            '/message/notificationpreferences.php': '?userid=' + encodeURIComponent(userId),
            '/login/change_password.php': '?id=' + encodeURIComponent(courseId),
            '/user/forum.php': '?id=' + encodeURIComponent(userId) + '&course=' + encodeURIComponent(courseId),
            '/user/calendar.php': '?id=' + encodeURIComponent(userId),
            '/message/edit.php': '?id=' + encodeURIComponent(userId)
        };

        return path + (map[path] || '');
    };

    var iconSvg = function(name) {
        var id = uniqueId(name);
        var gradients = {
            profile: ['#2563eb', '#06b6d4'],
            language: ['#14b8a6', '#22c55e'],
            editor: ['#7c3aed', '#2563eb'],
            content: ['#f97316', '#fbbf24'],
            lock: ['#ef4444', '#f97316'],
            forum: ['#16a34a', '#0891b2'],
            calendar: ['#0f6cbf', '#38bdf8'],
            message: ['#0891b2', '#7c3aed'],
            fields: ['#2563eb', '#06b6d4'],
            actions: ['#f97316', '#ef4444'],
            sections: ['#7c3aed', '#22c55e'],
            status: ['#14b8a6', '#0f6cbf']
        };
        var pair = gradients[name] || gradients.profile;
        var paths = {
            profile: '<circle cx="12" cy="8" r="3.7" fill="#fff" opacity=".95"/><path d="M5.2 19.2c.9-4 3.4-6.1 6.8-6.1s5.9 2.1 6.8 6.1" fill="#fff" opacity=".78"/>',
            language: '<path d="M4.2 6.2h8.2v7.3H4.2z" fill="#fff" opacity=".95"/><path d="M11.7 10.7h8.1v7.1h-8.1z" fill="#fff" opacity=".72"/><path d="M6.1 10.8c1.5-1.1 2.5-2.8 2.8-4.6M5.8 8h5M15 15.5l1-2.7 1 2.7M14.5 16.5h3" stroke="#0f172a" stroke-width="1.25" stroke-linecap="round" opacity=".72"/>',
            editor: '<path d="M6 5.5h12v9.6H6z" fill="#fff" opacity=".9"/><path d="M8.4 8.2h7.2M8.4 10.9h5.6M6 17.4h12" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".65"/>',
            content: '<path d="M5.3 7.5 12 4l6.7 3.5V16L12 19.6 5.3 16V7.5Z" fill="#fff" opacity=".9"/><path d="M5.9 7.9 12 11l6.1-3.1M12 11v8" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".62"/>',
            lock: '<rect x="5.2" y="10" width="13.6" height="9" rx="3" fill="#fff" opacity=".9"/><path d="M8.4 10V8a3.6 3.6 0 0 1 7.2 0v2" fill="none" stroke="#fff" stroke-width="2.1" stroke-linecap="round"/><circle cx="12" cy="14.4" r="1.2" fill="#0f172a" opacity=".64"/>',
            forum: '<path d="M5 6.5h14v8.2H9.6L6 18.1v-3.4H5V6.5Z" fill="#fff" opacity=".92"/><path d="M8.3 9.3h7.4M8.3 12h5.4" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".62"/>',
            calendar: '<rect x="5.2" y="6.4" width="13.6" height="12" rx="3" fill="#fff" opacity=".9"/><path d="M5.2 10h13.6M8.3 4.8v3M15.7 4.8v3" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/><path d="M8.5 13h2M13.5 13h2M8.5 15.8h2" stroke="#0f172a" stroke-width="1.25" stroke-linecap="round" opacity=".6"/>',
            message: '<path d="M5 6.4h14v9.4a2.2 2.2 0 0 1-2.2 2.2H7.2A2.2 2.2 0 0 1 5 15.8V6.4Z" fill="#fff" opacity=".92"/><path d="m6 8 6 4.4L18 8" fill="none" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".62"/>',
            fields: '<path d="M6.3 6.3h11.4v3.5H6.3zM6.3 12.1h11.4v5.6H6.3z" fill="#fff" opacity=".88"/><path d="M8.2 8h3.7M8.2 14h7.2" stroke="#0f172a" stroke-width="1.25" stroke-linecap="round" opacity=".58"/>',
            actions: '<path d="M12 4.5 14.2 9l5 .7-3.6 3.6.9 5-4.5-2.4-4.5 2.4.9-5L4.8 9.7l5-.7L12 4.5Z" fill="#fff" opacity=".9"/>',
            sections: '<path d="M5.5 5.5h5v5h-5zM13.5 5.5h5v5h-5zM5.5 13.5h5v5h-5zM13.5 13.5h5v5h-5z" fill="#fff" opacity=".88"/>',
            status: '<path d="M12 3.4 18.7 6v5.2c0 4-2.6 7.2-6.7 9-4.1-1.8-6.7-5-6.7-9V6L12 3.4Z" fill="#fff" opacity=".9"/><path d="m8.8 12 2 2 4.4-5" fill="none" stroke="#0f172a" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" opacity=".62"/>'
        };

        return '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">' +
            '<defs><linearGradient id="' + id + '-g" x1="4" y1="3" x2="20" y2="21" gradientUnits="userSpaceOnUse">' +
            '<stop stop-color="' + pair[0] + '"/><stop offset="1" stop-color="' + pair[1] + '"/></linearGradient></defs>' +
            '<rect x="2.8" y="2.8" width="18.4" height="18.4" rx="5.4" fill="url(#' + id + '-g)"/>' +
            '<path d="M6 4.6h8.6c2.9 0 5.1 2.1 5.1 4.8v.7C16.3 8.7 11 8.1 4.3 8.9c.2-2.4 1-4.3 1.7-4.3Z" fill="#fff" opacity=".22"/>' +
            '<path d="M6.4 19c4.1 1.7 9.4 1 12.2-2.1-1.5 2.8-4 4.3-7 4.3H8.2c-1 0-1.6-.8-1.8-2.2Z" fill="#0f172a" opacity=".08"/>' +
            '<g filter="drop-shadow(0 7px 8px rgba(15, 23, 42, .17))">' + (paths[name] || paths.profile) + '</g></svg>';
    };

    var heroSvg = function(meta) {
        var id = uniqueId('hero');
        var visualIcon = iconSvg(meta.visual);

        return '<svg aria-hidden="true" viewBox="0 0 380 270" focusable="false">' +
            '<defs>' +
            '<linearGradient id="' + id + '-panel" x1="46" y1="36" x2="314" y2="226" gradientUnits="userSpaceOnUse"><stop stop-color="#eff6ff"/><stop offset=".48" stop-color="#dbeafe"/><stop offset="1" stop-color="#ccfbf1"/></linearGradient>' +
            '<linearGradient id="' + id + '-card" x1="86" y1="56" x2="292" y2="210" gradientUnits="userSpaceOnUse"><stop stop-color="#fff"/><stop offset="1" stop-color="#eef7ff"/></linearGradient>' +
            '<linearGradient id="' + id + '-line" x1="64" y1="214" x2="326" y2="214" gradientUnits="userSpaceOnUse"><stop stop-color="#2563eb"/><stop offset=".5" stop-color="#14b8a6"/><stop offset="1" stop-color="#f59e0b"/></linearGradient>' +
            '</defs>' +
            '<path class="boc-accountprefs-svg-glow" d="M47 195c34 52 124 63 198 36 75-28 121-94 86-145C297 36 206 24 132 55 59 86 13 144 47 195Z" fill="url(#' + id + '-panel)" opacity=".92"/>' +
            '<g class="boc-accountprefs-svg-float">' +
            '<rect x="86" y="55" width="208" height="144" rx="28" fill="url(#' + id + '-card)" stroke="#fff" stroke-width="3"/>' +
            '<rect x="115" y="88" width="150" height="20" rx="10" fill="#bfdbfe"/>' +
            '<rect x="115" y="124" width="118" height="16" rx="8" fill="#bae6fd"/>' +
            '<rect x="115" y="154" width="92" height="16" rx="8" fill="#bbf7d0"/>' +
            '<foreignObject x="226" y="103" width="74" height="74"><div xmlns="http://www.w3.org/1999/xhtml" class="boc-accountprefs-foreign-icon">' + visualIcon + '</div></foreignObject>' +
            '</g>' +
            '<g class="boc-accountprefs-svg-orbit" fill="#fff" stroke="#dbeafe" stroke-width="2">' +
            '<rect x="48" y="75" width="64" height="44" rx="16"/><rect x="264" y="42" width="70" height="48" rx="17"/><rect x="226" y="196" width="88" height="44" rx="18"/>' +
            '</g>' +
            '<text x="62" y="103" fill="#2563eb" font-size="15" font-weight="800">Secure</text>' +
            '<text x="283" y="72" fill="#0891b2" font-size="15" font-weight="800">Live</text>' +
            '<text x="244" y="224" fill="#f97316" font-size="15" font-weight="800">Moodle</text>' +
            '<path class="boc-accountprefs-svg-line" d="M62 224c58-28 112-26 156-8 39 16 74 11 109-13" fill="none" stroke="url(#' + id + '-line)" stroke-width="10" stroke-linecap="round" opacity=".58"/>' +
            '</svg>';
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

    var collectData = function() {
        var region = document.querySelector('#region-main');
        var drawer = pagePath() === '/message/edit.php' ? document.querySelector('.message-app') : null;
        var roots = [region, drawer].filter(Boolean);
        var findAll = function(selector) {
            var seen = [];

            roots.forEach(function(root) {
                Array.prototype.slice.call(root.querySelectorAll(selector)).forEach(function(item) {
                    if (seen.indexOf(item) === -1) {
                        seen.push(item);
                    }
                });
            });
            return seen;
        };
        var forms = findAll('form');
        var fields = findAll('input:not([type="hidden"]), select, textarea');
        var buttons = findAll(
            'input[type="submit"], button[type="submit"], input[type="button"], button.btn-primary, button.btn-secondary'
        );
        var fieldsets = findAll('fieldset');
        var tables = findAll('table');
        var selects = findAll('select');
        var values = selects.map(function(select) {
            var labelNode = select.id ? document.querySelector('label[for="' + select.id + '"]') : null;
            var label = textWithoutHidden(labelNode) || cleanText(select.name || select.id);
            var selected = select.options[select.selectedIndex] ? cleanText(select.options[select.selectedIndex].textContent) : cleanText(select.value);
            return {
                label: label,
                value: selected
            };
        }).filter(function(item) {
            return item.label || item.value;
        }).slice(0, 5);

        return {
            forms: forms.length,
            fields: fields.length,
            actions: buttons.map(function(button) {
                return cleanText(button.value || button.getAttribute('title') || button.getAttribute('aria-label') || button.textContent);
            }).filter(Boolean),
            sections: fieldsets.length || Math.max(0, Array.prototype.slice.call(region ? region.querySelectorAll('.fcontainer, .clearfix.fitem') : []).length - fields.length),
            tables: tables.length,
            rows: tables.reduce(function(total, table) {
                return total + table.querySelectorAll('tr').length;
            }, 0),
            selects: selects.length,
            checkboxes: findAll('input[type="checkbox"]').length,
            values: values,
            alerts: findAll('.alert, .notifyproblem, .error').map(function(alert) {
                return cleanText(alert.textContent);
            }).filter(Boolean).slice(0, 3)
        };
    };

    var metric = function(key, label, value, meta, icon) {
        var item = createNode('article', 'boc-accountprefs-metric');
        var iconNode = createNode('span', 'boc-accountprefs-metric-icon');
        var copy = createNode('span', 'boc-accountprefs-metric-copy');
        var valueNode = createNode('strong', '', value);
        var labelNode = createNode('span', 'boc-accountprefs-metric-label', label);
        var metaNode = createNode('span', 'boc-accountprefs-metric-meta', meta);

        iconNode.innerHTML = iconSvg(icon);
        copy.appendChild(valueNode);
        copy.appendChild(labelNode);
        copy.appendChild(metaNode);
        item.appendChild(iconNode);
        item.appendChild(copy);
        state.metrics[key] = {
            value: valueNode,
            meta: metaNode
        };
        return item;
    };

    var guideItem = function(key, icon, title, text) {
        var item = createNode('article', 'boc-accountprefs-guide-item');
        var iconNode = createNode('span', 'boc-accountprefs-guide-icon');
        var copy = createNode('span', 'boc-accountprefs-guide-copy');
        var titleNode = createNode('strong', '', title);
        var textNode = createNode('span', '', text);

        iconNode.innerHTML = iconSvg(icon);
        copy.appendChild(titleNode);
        copy.appendChild(textNode);
        item.appendChild(iconNode);
        item.appendChild(copy);
        state.guide[key] = textNode;
        return item;
    };

    var preferenceNav = function() {
        var nav = createNode('nav', 'boc-accountprefs-nav');
        var title = createNode('span', 'boc-accountprefs-nav-title', 'User account');
        var list = createNode('div', 'boc-accountprefs-nav-list');
        var current = pagePath();
        var items = [
            ['/user/edit.php', 'Profile', 'profile'],
            ['/user/language.php', 'Language', 'language'],
            ['/user/editor.php', 'Editor', 'editor'],
            ['/user/contentbank.php', 'Content bank', 'content'],
            ['/message/notificationpreferences.php', 'Notifications', 'message'],
            ['/login/change_password.php', 'Password', 'lock'],
            ['/user/forum.php', 'Forum', 'forum'],
            ['/user/calendar.php', 'Calendar', 'calendar'],
            ['/message/edit.php', 'Messages', 'message']
        ];

        nav.setAttribute('aria-label', 'User account preference links');
        nav.appendChild(title);
        items.forEach(function(item) {
            var link = createNode('a', 'boc-accountprefs-nav-link');
            var icon = createNode('span', 'boc-accountprefs-nav-icon');

            link.href = pageUrl(item[0]);
            link.textContent = item[1];
            icon.innerHTML = iconSvg(item[2]);
            link.insertBefore(icon, link.firstChild);
            if (current === item[0]) {
                link.setAttribute('aria-current', 'page');
            }
            list.appendChild(link);
        });
        nav.appendChild(list);
        return nav;
    };

    var buildShell = function() {
        var body = document.querySelector(bodySelector);
        var region = document.querySelector('#region-main');
        var meta = currentMeta();
        var data;
        var heading;
        var pageHeader;
        var contentNodes;
        var shell;
        var hero;
        var heroCopy;
        var metrics;
        var visual;
        var layout;
        var workspace;
        var workspaceHeader;
        var workspaceTitle;
        var aside;
        var guideList;
        var empty;

        if (!body || !region) {
            return false;
        }

        if (body.classList.contains('theme-boost-override-custom-notificationprefs')) {
            body.classList.add('boc-accountprefs-notification-integrated');
            return true;
        }

        if (state.shell) {
            updateLiveData();
            return true;
        }

        heading = region.querySelector('h2');
        pageHeader = document.querySelector('#page-header h1, .page-header-headings h1, h1');
        data = collectData();

        if (heading) {
            heading.classList.add('boc-accountprefs-source-heading');
        }

        contentNodes = Array.prototype.slice.call(region.childNodes).filter(function(node) {
            return !(node.nodeType === 1 && node.classList.contains('boc-accountprefs-shell'));
        });

        shell = createNode('section', 'boc-accountprefs-shell boc-accountprefs-' + meta.key);
        body.classList.add('boc-accountprefs-page-' + meta.key);

        if (['language', 'editor', 'contentbank', 'password', 'forum', 'calendar', 'message'].indexOf(meta.key) !== -1) {
            shell.classList.add('boc-accountprefs-shell-compact');
        }
        hero = createNode('section', 'boc-accountprefs-hero boc-accountprefs-accent-' + meta.accent);
        heroCopy = createNode('div', 'boc-accountprefs-hero-copy');
        metrics = createNode('div', 'boc-accountprefs-metrics');
        visual = createNode('div', 'boc-accountprefs-visual');
        layout = createNode('div', 'boc-accountprefs-layout');
        workspace = createNode('section', 'boc-accountprefs-workspace');
        workspaceHeader = createNode('div', 'boc-accountprefs-workspace-header');
        workspaceTitle = createNode('div', 'boc-accountprefs-workspace-title');
        aside = createNode('aside', 'boc-accountprefs-guide');
        guideList = createNode('div', 'boc-accountprefs-guide-list');

        heroCopy.appendChild(createNode('span', 'boc-accountprefs-eyebrow', meta.eyebrow));
        heroCopy.appendChild(createNode('h1', '', meta.title));
        heroCopy.appendChild(createNode('p', '', meta.description));
        if (pageHeader) {
            heroCopy.appendChild(createNode('span', 'boc-accountprefs-user-pill', cleanText(pageHeader.textContent)));
        }
        metrics.appendChild(metric('fields', 'Live fields', String(data.fields), 'Controls rendered by Moodle', 'fields'));
        metrics.appendChild(metric('sections', 'Sections', String(data.sections || data.tables), 'Collapsible groups or tables', 'sections'));
        metrics.appendChild(metric('actions', 'Actions', String(data.actions.length), 'Available page actions', 'actions'));
        metrics.appendChild(metric('status', 'Status', data.alerts.length ? String(data.alerts.length) : 'Ready', data.alerts.length ? 'Messages need review' : 'No warnings detected', 'status'));
        heroCopy.appendChild(metrics);
        visual.innerHTML = heroSvg(meta);
        hero.appendChild(heroCopy);
        hero.appendChild(visual);

        workspaceHeader.appendChild(createNode('span', 'boc-accountprefs-workspace-icon'));
        workspaceHeader.querySelector('.boc-accountprefs-workspace-icon').innerHTML = iconSvg(meta.visual);
        workspaceTitle.appendChild(createNode('h2', '', meta.title + ' controls'));
        workspaceTitle.appendChild(createNode('p', '', meta.key === 'message' ?
            'Use Moodle\'s original message settings panel. Privacy, notification and delivery controls remain handled by core messaging.' :
            (data.forms ? 'Use the original Moodle form below. Field names, validation, tokens, editor widgets and submit actions are unchanged.' : 'Moodle has not rendered editable controls for this preference route.')));
        workspaceHeader.appendChild(workspaceTitle);
        workspace.appendChild(workspaceHeader);

        contentNodes.forEach(function(node) {
            workspace.appendChild(node);
        });

        if (meta.key === 'message') {
            empty = createNode('div', 'boc-accountprefs-empty');
            empty.innerHTML = '<span class="boc-accountprefs-empty-icon">' + iconSvg(meta.visual) + '</span>' +
                '<strong>Message settings panel is active</strong>' +
                '<span>Use the Moodle settings drawer on the right. The page background is styled only as visual context.</span>';
            workspace.appendChild(empty);
        } else if (!data.forms && !data.tables && !data.fields) {
            empty = createNode('div', 'boc-accountprefs-empty');
            empty.innerHTML = '<span class="boc-accountprefs-empty-icon">' + iconSvg(meta.visual) + '</span>' +
                '<strong>No editable controls on this page</strong>' +
                '<span>Moodle rendered this preference page without a form for the current user and configuration.</span>';
            workspace.appendChild(empty);
        }

        aside.appendChild(preferenceNav());
        aside.appendChild(createNode('span', 'boc-accountprefs-guide-kicker', 'Live preference guide'));
        aside.appendChild(createNode('h2', '', 'Current page summary'));
        aside.appendChild(createNode('p', 'boc-accountprefs-guide-lede', 'These values are read from the actual Moodle controls visible on this page.'));
        guideList.appendChild(guideItem('fields', 'fields', 'Rendered controls', 'Reading fields...'));
        guideList.appendChild(guideItem('actions', 'actions', 'Page actions', 'Reading actions...'));
        guideList.appendChild(guideItem('values', 'status', 'Selected values', 'Reading current selections...'));
        guideList.appendChild(guideItem('validation', 'lock', 'Functional safety', 'Moodle form submission and validation remain unchanged.'));
        aside.appendChild(guideList);

        layout.appendChild(workspace);
        layout.appendChild(aside);
        shell.appendChild(hero);
        shell.appendChild(layout);
        region.appendChild(shell);
        state.shell = shell;

        body.classList.add('boc-accountprefs-ready');
        decorateControls();
        updateLiveData();
        observeRegion();
        return true;
    };

    var decorateControls = function() {
        var region = document.querySelector('#region-main');
        if (!region || document.body.classList.contains('theme-boost-override-custom-notificationprefs')) {
            return;
        }

        Array.prototype.slice.call(region.querySelectorAll('form.mform')).forEach(function(form) {
            form.classList.add('boc-accountprefs-form');
        });
        Array.prototype.slice.call(region.querySelectorAll('fieldset')).forEach(function(fieldset, index) {
            fieldset.classList.add('boc-accountprefs-fieldset');
            fieldset.style.setProperty('--boc-accountprefs-delay', (index * 35) + 'ms');
        });
        Array.prototype.slice.call(region.querySelectorAll('input:not([type="hidden"]), select, textarea')).forEach(function(input) {
            input.classList.add('boc-accountprefs-input');
        });
        Array.prototype.slice.call(region.querySelectorAll('input[type="submit"], button[type="submit"]')).forEach(function(button) {
            button.classList.add('boc-accountprefs-submit');
        });
    };

    var updateLiveData = function() {
        var data = collectData();
        var actions = Array.from(new Set(data.actions)).slice(0, 6);
        var valueSummary = data.values.map(function(item) {
            return item.label + ': ' + item.value;
        }).join(' | ');

        if (!state.metrics.fields) {
            return;
        }

        state.metrics.fields.value.textContent = String(data.fields);
        state.metrics.sections.value.textContent = String(data.sections || data.tables || 0);
        state.metrics.actions.value.textContent = String(actions.length);
        state.metrics.status.value.textContent = data.alerts.length ? String(data.alerts.length) : 'Ready';
        state.metrics.status.meta.textContent = data.alerts.length ? 'Messages need review' : 'No warnings detected';

        if (state.guide.fields) {
            state.guide.fields.textContent = data.fields + ' input controls, ' + data.selects + ' dropdowns and ' + data.checkboxes + ' checkboxes are currently rendered.';
        }
        if (state.guide.actions) {
            state.guide.actions.textContent = actions.length ? actions.join(', ') : 'No submit actions are available on this route.';
        }
        if (state.guide.values) {
            state.guide.values.textContent = valueSummary || 'No dropdown selections are visible on this page.';
        }
        if (state.guide.validation && data.alerts.length) {
            state.guide.validation.textContent = data.alerts[0];
        }

        decorateControls();
    };

    var scheduleUpdate = function() {
        if (state.scheduled) {
            return;
        }
        state.scheduled = true;
        window.requestAnimationFrame(function() {
            state.scheduled = false;
            updateLiveData();
        });
    };

    var getCollapseContainer = function(fieldset, toggle) {
        var targetId;

        if (toggle && toggle.getAttribute('href') && toggle.getAttribute('href').charAt(0) === '#') {
            targetId = toggle.getAttribute('href').slice(1);
            if (targetId) {
                return document.getElementById(targetId);
            }
        }

        return fieldset ? fieldset.querySelector('.fcontainer.collapseable, .fcontainer.collapse') : null;
    };

    var setFieldsetExpanded = function(fieldset, expanded) {
        var toggle;
        var container;

        if (!fieldset || !fieldset.classList.contains('collapsible')) {
            return;
        }

        toggle = fieldset.querySelector('.fheader[href^="#"], .ftoggler a[href^="#"]');
        container = getCollapseContainer(fieldset, toggle);

        fieldset.classList.toggle('collapsed', !expanded);
        if (toggle) {
            toggle.classList.toggle('collapsed', !expanded);
            toggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
        }
        if (container) {
            container.classList.toggle('show', expanded);
            if (expanded && container.style.display === 'none') {
                container.style.display = '';
            }
            if (!expanded) {
                container.style.display = '';
            }
        }
    };

    var updateCollapseMenuState = function() {
        var region = document.querySelector('#region-main');
        var collapsibles;
        var allExpanded;

        if (!region) {
            return;
        }

        collapsibles = Array.prototype.slice.call(region.querySelectorAll('fieldset.collapsible'));
        if (!collapsibles.length) {
            return;
        }

        allExpanded = collapsibles.every(function(fieldset) {
            return !fieldset.classList.contains('collapsed');
        });

        Array.prototype.slice.call(region.querySelectorAll('.collapsible-actions .collapseexpand')).forEach(function(control) {
            control.classList.toggle('collapsed', !allExpanded);
            control.setAttribute('aria-expanded', allExpanded ? 'true' : 'false');
        });
    };

    var handleCollapseFallback = function(event) {
        var target = event.target;
        var menu = target.closest(bodySelector + ' #region-main .collapsible-actions .collapseexpand');
        var header;
        var fieldset;
        var wasExpanded;
        var shouldExpandAll;
        var container;

        if (menu) {
            shouldExpandAll = menu.getAttribute('aria-expanded') !== 'true' || menu.classList.contains('collapsed');
            window.setTimeout(function() {
                var region = document.querySelector('#region-main');
                if (!region) {
                    return;
                }
                Array.prototype.slice.call(region.querySelectorAll('fieldset.collapsible')).forEach(function(item) {
                    setFieldsetExpanded(item, shouldExpandAll);
                });
                updateCollapseMenuState();
                scheduleUpdate();
            }, 160);
            return;
        }

        fieldset = target.closest(bodySelector + ' #region-main fieldset.collapsible');
        if (!fieldset) {
            return;
        }

        header = fieldset.querySelector('.fheader[href^="#"], .ftoggler a[href^="#"]');
        if (!header || !target.closest('legend, .ftoggler, .fheader')) {
            return;
        }

        container = getCollapseContainer(fieldset, header);
        wasExpanded = header.getAttribute('aria-expanded') === 'true' ||
            (container && container.classList.contains('show')) ||
            (fieldset && !fieldset.classList.contains('collapsed'));

        window.setTimeout(function() {
            setFieldsetExpanded(fieldset, !wasExpanded);
            updateCollapseMenuState();
            scheduleUpdate();
        }, 160);
    };

    var observeRegion = function() {
        var target = document.querySelector('#region-main');
        if (!window.MutationObserver || !target || state.observer) {
            return;
        }
        state.observer = new MutationObserver(scheduleUpdate);
        state.observer.observe(target, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'aria-expanded', 'checked', 'selected']
        });
    };

    var init = function() {
        if (!buildShell()) {
            window.setTimeout(buildShell, 300);
        }
        document.addEventListener('change', function(event) {
            if (event.target.closest(bodySelector + ' #region-main')) {
                scheduleUpdate();
            }
        });
        document.addEventListener('click', handleCollapseFallback, true);
        document.addEventListener('click', function(event) {
            if (event.target.closest(bodySelector + ' #region-main')) {
                window.setTimeout(scheduleUpdate, 160);
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
