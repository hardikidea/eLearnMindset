(function() {
    'use strict';

    var pageSelector = 'body.theme-boost-override-custom-platform';
    var scheduled = false;

    var clean = function(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    };

    var translatedString = function(identifier, fallback) {
        try {
            if (window.M && M.util && typeof M.util.get_string === 'function') {
                return M.util.get_string(identifier, 'theme_boost_override_custom');
            }
        } catch (error) {
            return fallback;
        }
        return fallback;
    };

    var routeArea = function(path) {
        var areas = [
            [/^\/course\//, 'course'],
            [/^\/mod\//, 'activity'],
            [/^\/grade\//, 'report'],
            [/^\/(report|reportbuilder)\//, 'report'],
            [/^\/calendar\//, 'calendar'],
            [/^\/message\//, 'message'],
            [/^\/(user|my)\//, 'user'],
            [/^\/admin\//, 'admin'],
            [/^\/(blog|badges)\//, 'content']
        ];
        var match = areas.find(function(item) {
            return item[0].test(path);
        });

        return match ? match[1] : 'learning';
    };

    var uniqueId = function(area) {
        var path = window.location.pathname;
        var hash = 0;
        var index;

        for (index = 0; index < path.length; index += 1) {
            hash = ((hash << 5) - hash) + path.charCodeAt(index);
            hash |= 0;
        }

        return 'boc-platform-' + area + '-' + Math.abs(hash);
    };

    var iconSvg = function(area) {
        var id = uniqueId(area);
        var glyphs = {
            course: '<path d="M12 5 4.5 8.8 12 12.6l7.5-3.8L12 5Z"/><path d="M7.1 11.2v3.4c0 1.7 2.2 3 4.9 3s4.9-1.3 4.9-3v-3.4"/>',
            activity: '<path d="M7 5.2h10a2 2 0 0 1 2 2v9.6a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V7.2a2 2 0 0 1 2-2Z"/><path d="m10.2 9.1 4.5 2.9-4.5 2.9V9.1Z"/>',
            report: '<path d="M6 18.5V13h3v5.5H6Zm4.5 0V9h3v9.5h-3Zm4.5 0V5.5h3v13h-3Z"/><path d="M5 20h14"/>',
            calendar: '<path d="M6 5h12a2 2 0 0 1 2 2v11a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2Z"/><path d="M8 3v4m8-4v4M4 9h16M8 13h2m4 0h2m-8 3h2m4 0h2"/>',
            message: '<path d="M5.5 5.5h13a2 2 0 0 1 2 2v7.2a2 2 0 0 1-2 2h-7l-4.7 3v-3H5.5a2 2 0 0 1-2-2V7.5a2 2 0 0 1 2-2Z"/><path d="M7.5 9.5h9m-9 3h6"/>',
            user: '<circle cx="12" cy="8.2" r="3.6"/><path d="M5.2 20c.7-4 3-6 6.8-6s6.1 2 6.8 6"/>',
            admin: '<path d="M12 3.5 19 6v5.3c0 4.2-2.8 7.4-7 9.2-4.2-1.8-7-5-7-9.2V6l7-2.5Z"/><path d="m8.7 12 2.1 2.1 4.5-5"/>',
            content: '<path d="M6 4.5h9l3 3v12H6v-15Z"/><path d="M14.8 4.8v3h3M8.8 11h6.4m-6.4 3h6.4m-6.4 3h4"/>',
            learning: '<path d="M6.2 5.5h11.6v13H6.2v-13Z"/><path d="M9 8.5h6m-6 3h6m-6 3h4"/>'
        };

        return '<svg viewBox="0 0 48 48" aria-hidden="true" focusable="false">' +
            '<defs><linearGradient id="' + id + '-surface" x1="8" y1="5" x2="40" y2="43" gradientUnits="userSpaceOnUse">' +
            '<stop stop-color="var(--boc-theme-primary, #0f6cbf)"/><stop offset=".55" stop-color="var(--boc-theme-accent, #0891b2)"/><stop offset="1" stop-color="var(--boc-theme-warning-bright, #f59e0b)"/></linearGradient>' +
            '<filter id="' + id + '-shadow" x="-30%" y="-30%" width="160%" height="170%"><feDropShadow dx="0" dy="5" stdDeviation="4" flood-color="#0f172a" flood-opacity=".2"/></filter></defs>' +
            '<g class="boc-platform-symbol-depth" filter="url(#' + id + '-shadow)"><rect x="5" y="4" width="38" height="38" rx="10" fill="url(#' + id + '-surface)"/>' +
            '<path d="M8 8h24c4 0 7 2.5 7 6.5-9-2.8-19.5-2.5-31 .8V8Z" fill="#fff" opacity=".24"/>' +
            '<g fill="none" stroke="#fff" stroke-width="2.1" stroke-linecap="round" stroke-linejoin="round">' + (glyphs[area] || glyphs.learning) + '</g></g></svg>';
    };

    var hasSpecialPageDesign = function(body) {
        return [
            'theme-boost-override-custom-frontpage',
            'theme-boost-override-custom-courseindex',
            'theme-boost-override-custom-userprofile',
            'theme-boost-override-custom-mycourses',
            'theme-boost-override-custom-gradeoverview',
            'theme-boost-override-custom-notificationprefs',
            'theme-boost-override-custom-calendarview',
            'theme-boost-override-custom-userpreferences',
            'theme-boost-override-custom-userfiles',
            'theme-boost-override-custom-accountprefs',
            'theme-boost-override-custom-learningtools'
        ].some(function(className) {
            return body.classList.contains(className);
        });
    };

    var decoratePageHeader = function(body, area) {
        var headings = document.querySelector('#page-header .page-header-headings');
        var symbol;

        if (!headings || headings.querySelector('.boc-platform-page-symbol') || hasSpecialPageDesign(body)) {
            return;
        }

        symbol = document.createElement('span');
        symbol.className = 'boc-platform-page-symbol';
        symbol.setAttribute('aria-hidden', 'true');
        symbol.innerHTML = iconSvg(area);
        headings.prepend(symbol);
    };

    var decorateAccessibleActions = function() {
        document.querySelectorAll('button[data-bs-toggle="dropdown"]').forEach(function(button) {
            if (clean(button.getAttribute('aria-label')) || clean(button.textContent)) {
                return;
            }
            if (!button.querySelector('.fa-ellipsis, .fa-ellipsis-v, .fa-ellipsis-vertical')) {
                return;
            }

            var label = translatedString('moreactions', 'More actions');
            button.setAttribute('aria-label', label);
            if (!button.getAttribute('title')) {
                button.setAttribute('title', label);
            }
        });
    };

    var setMessageDetailState = function(active) {
        if (!document.body || document.body.id !== 'page-message-index') {
            return;
        }
        document.body.classList.toggle('boc-message-detail-active', active);
    };

    var initialiseMessageView = function() {
        if (!document.body || document.body.id !== 'page-message-index') {
            return;
        }

        var selectedUser = new URLSearchParams(window.location.search).get('id');
        setMessageDetailState(Boolean(selectedUser && selectedUser !== '0'));
    };

    var decorateContent = function() {
        var body = document.querySelector(pageSelector);
        var path;
        var area;

        if (!body) {
            return;
        }

        path = window.location.pathname;
        area = routeArea(path);
        body.dataset.bocPageArea = area;
        body.classList.add('boc-platform-ready');

        decoratePageHeader(body, area);
        decorateAccessibleActions();
        document.querySelectorAll('#region-main .mform').forEach(function(form) {
            form.classList.add('boc-platform-form');
        });
        document.querySelectorAll('#region-main table.generaltable, #region-main table.table').forEach(function(table) {
            table.classList.add('boc-platform-table');
        });
        document.querySelectorAll('.dropdown-menu').forEach(function(menu) {
            menu.classList.add('boc-platform-dropdown');
        });
        document.querySelectorAll('.filemanager, .filepicker-filelist').forEach(function(manager) {
            manager.classList.add('boc-platform-file-area');
        });
    };

    var fitDropdown = function(menu) {
        var viewportGap = 12;
        var rect;
        var shiftX = 0;
        var shiftY = 0;

        if (!menu || !menu.classList.contains('show')) {
            return;
        }

        menu.style.translate = 'none';
        menu.style.maxHeight = Math.max(220, window.innerHeight - (viewportGap * 2)) + 'px';
        rect = menu.getBoundingClientRect();

        if (rect.right > window.innerWidth - viewportGap) {
            shiftX = (window.innerWidth - viewportGap) - rect.right;
        } else if (rect.left < viewportGap) {
            shiftX = viewportGap - rect.left;
        }

        if (rect.bottom > window.innerHeight - viewportGap) {
            shiftY = (window.innerHeight - viewportGap) - rect.bottom;
        } else if (rect.top < viewportGap) {
            shiftY = viewportGap - rect.top;
        }

        menu.style.translate = shiftX + 'px ' + shiftY + 'px';
    };

    var fitOpenDropdowns = function() {
        document.querySelectorAll(pageSelector + ' .dropdown-menu.show').forEach(fitDropdown);
    };

    var schedule = function() {
        if (scheduled) {
            return;
        }
        scheduled = true;
        window.requestAnimationFrame(function() {
            scheduled = false;
            decorateContent();
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', decorateContent);
    } else {
        decorateContent();
    }

    if (window.MutationObserver) {
        new MutationObserver(schedule).observe(document.body, {childList: true, subtree: true});
    }

    document.addEventListener('visibilitychange', function() {
        document.body.classList.toggle('boc-platform-paused', document.hidden);
    });

    document.addEventListener('click', function(event) {
        var target = event.target instanceof Element ? event.target : null;
        if (!target || !document.body || document.body.id !== 'page-message-index') {
            return;
        }

        if (target.closest('.message-app.main .conversationcontainer a[data-conversation-id], ' +
                '.message-app.main .conversationcontainer [data-route="view-settings"], ' +
                '.message-app.main .conversationcontainer [data-route="view-contacts"]')) {
            setMessageDetailState(true);
        } else if (target.closest('.message-app.main [data-route-back]')) {
            setMessageDetailState(false);
        }
    });

    document.addEventListener('shown.bs.dropdown', function(event) {
        var menu = event.target && event.target.parentElement ? event.target.parentElement.querySelector('.dropdown-menu.show') : null;
        window.requestAnimationFrame(function() {
            fitDropdown(menu);
        });
    });

    document.addEventListener('hidden.bs.dropdown', function(event) {
        var menu = event.target && event.target.parentElement ? event.target.parentElement.querySelector('.dropdown-menu') : null;
        if (menu) {
            menu.style.removeProperty('translate');
            menu.style.removeProperty('max-height');
        }
    });

    window.addEventListener('resize', function() {
        window.requestAnimationFrame(fitOpenDropdowns);
    });
    window.addEventListener('scroll', function() {
        window.requestAnimationFrame(fitOpenDropdowns);
    }, {passive: true});
    window.addEventListener('popstate', initialiseMessageView);
    initialiseMessageView();
})();
