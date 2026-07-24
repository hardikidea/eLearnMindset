(function() {
    'use strict';

    var pageSelector = 'body.theme-boost-override-custom-platform, body.theme-boost-override-custom-login';
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

    var fontAwesomeIcon = function(name, extraClass) {
        return '<i class="fa fa-fw ' + name + ' boc-product-icon' +
            (extraClass ? ' ' + extraClass : '') + '" aria-hidden="true"></i>';
    };

    var referencedIconClass = function(container) {
        var use = container.querySelector('use');
        var reference = use ? (use.getAttribute('href') || use.getAttribute('xlink:href') || '') : '';
        var referenceName = reference.replace(/^.*#/, '');
        var references = {
            bocIconHome: 'fa-home',
            bocIconDiscover: 'fa-compass',
            bocIconProgrammes: 'fa-university',
            bocIconAdmissions: 'fa-id-card',
            bocIconBoards: 'fa-language',
            bocIconGrade: 'fa-bar-chart',
            bocIconAbout: 'fa-info-circle',
            bocIconContact: 'fa-envelope',
            bocIconBolt: 'fa-bolt',
            bocIconCourses: 'fa-book',
            bocIconCatalogue: 'fa-folder-open',
            bocIconActivity: 'fa-tasks',
            bocIconQuiz: 'fa-question-circle',
            bocIconCertificate: 'fa-certificate',
            bocIconAttendance: 'fa-calendar-check-o',
            bocIconParent: 'fa-user-plus',
            bocIconNotice: 'fa-bullhorn',
            bocIconCalendar: 'fa-calendar',
            bocIconSecure: 'fa-shield',
            bocIconUsers: 'fa-users',
            bocIconStudent: 'fa-graduation-cap',
            bocIconTeacher: 'fa-briefcase'
        };

        return references[referenceName] || '';
    };

    var loginComponentIcon = function(container) {
        var item;
        var rules = [
            ['.boc-login-nav a', {
                Home: 'fa-home',
                Discover: 'fa-compass',
                Programmes: 'fa-university',
                Admissions: 'fa-id-card',
                Boards: 'fa-language',
                'Grade System': 'fa-bar-chart',
                About: 'fa-info-circle',
                Contact: 'fa-envelope'
            }],
            ['.boc-login-kicker', {
                'Secure LMS access': 'fa-shield',
                'Student learning': 'fa-graduation-cap',
                'Teacher workspace': 'fa-briefcase',
                'Parent connect': 'fa-user-plus',
                'Course catalogue': 'fa-book',
                'Interactive activities': 'fa-tasks',
                'Assessment engine': 'fa-check-square-o',
                'Grade system': 'fa-bar-chart',
                Certificates: 'fa-certificate',
                'Notices and support': 'fa-bullhorn'
            }]
        ];
        var cardClasses = {
            'boc-role-users': 'fa-users',
            'boc-role-students': 'fa-graduation-cap',
            'boc-role-teachers': 'fa-briefcase',
            'boc-role-parents': 'fa-user-plus',
            'boc-feature-course': 'fa-book',
            'boc-feature-activity': 'fa-tasks',
            'boc-feature-quiz': 'fa-question-circle',
            'boc-feature-grade': 'fa-bar-chart',
            'boc-feature-cert': 'fa-certificate',
            'boc-feature-attendance': 'fa-calendar-check-o',
            'boc-feature-parent': 'fa-user-plus',
            'boc-feature-notice': 'fa-bullhorn',
            'boc-feature-calendar': 'fa-calendar',
            'boc-feature-secure': 'fa-shield'
        };
        var className;
        var match;

        for (var index = 0; index < rules.length; index++) {
            item = container.closest(rules[index][0]);
            if (item) {
                match = rules[index][1][clean(item.textContent)];
                if (match) {
                    return match;
                }
            }
        }

        item = container.closest('.boc-login-role-card, .boc-login-feature-card');
        if (item) {
            className = Object.keys(cardClasses).find(function(candidate) {
                return item.classList.contains(candidate);
            });
            if (className) {
                return cardClasses[className];
            }
        }

        return referencedIconClass(container);
    };

    var semanticIcon = function(value, fallback) {
        var descriptor = clean(value).toLowerCase();
        var rules = [
            [/(log\s*out|sign\s*out)/, 'fa-sign-out'],
            [/(log\s*in|sign\s*in|secure access)/, 'fa-sign-in'],
            [/(forgot|reset).*(password)|password.*(forgot|reset)/, 'fa-key'],
            [/(password|lock|security)/, 'fa-lock'],
            [/(show|hide|visibility|reveal).*(password)?/, 'fa-eye'],
            [/\b(cookies?|consent)\b/, 'fa-cookie-bite'],
            [/\b(search|find|lookup)\b/, 'fa-search'],
            [/\b(filter|funnel)\b/, 'fa-filter'],
            [/\b(sort|order by)\b/, 'fa-sort'],
            [/\b(upload|import)\b/, 'fa-upload'],
            [/\b(download|export)\b/, 'fa-download'],
            [/\bshare\b/, 'fa-share-alt'],
            [/(copy link|copy url|\bcopy\b)/, 'fa-link'],
            [/\b(star|favourite|favorite)\b/, 'fa-star'],
            [/\b(view|open|preview|details)\b/, 'fa-eye'],
            [/\b(edit|update|change|write)\b/, 'fa-pencil'],
            [/\b(delete|remove|trash)\b/, 'fa-trash'],
            [/\b(add|create|new)\b/, 'fa-plus'],
            [/\b(save|apply|submit)\b/, 'fa-check'],
            [/\bsend\b/, 'fa-paper-plane'],
            [/\b(refresh|reload|reset)\b/, 'fa-refresh'],
            [/\b(expand|fullscreen)\b/, 'fa-expand'],
            [/\b(collapse|minimise|minimize)\b/, 'fa-compress'],
            [/(previous|back|older|left|(?:^|\s)(?:<|<<|←)(?:\s|$))/, 'fa-chevron-left'],
            [/(next|forward|newer|right|(?:^|\s)(?:>|>>|→)(?:\s|$))/, 'fa-chevron-right'],
            [/\b(close|cancel|dismiss|clear)\b/, 'fa-times'],
            [/\b(more|actions|menu|ellipsis)\b/, 'fa-ellipsis-v'],
            [/\b(help|support|question)\b/, 'fa-question-circle'],
            [/\b(info|about|guide)\b/, 'fa-info-circle'],
            [/(browser sessions?|usersessions|\b(session|device|browser|computer)s?\b)/, 'fa-desktop'],
            [/(dashboard|\/my\/|#home|\bhome\b)/, 'fa-home'],
            [/\b(explore courses?|courses?|classes?|students?|teachers?|teaching)\b/, 'fa-graduation-cap'],
            [/\b(discover|explore|browse)\b/, 'fa-compass'],
            [/\b(programmes?|programs?|admissions?|university|college|campus)\b/, 'fa-university'],
            [/\b(boards?|mediums?|languages?|translation)\b/, 'fa-language'],
            [/\b(grade|result|performance|report|analytics|chart)\b/, 'fa-bar-chart'],
            [/\b(calendars?|dates?|events?|schedules?|deadlines?)\b/, 'fa-calendar'],
            [/\b(notifications?|reminders?|alerts?)\b/, 'fa-bell'],
            [/\b(messages?|contacts?|emails?|mail)\b/, 'fa-envelope'],
            [/\b(forums?|discussions?|comments?|feedback)\b/, 'fa-comments'],
            [/\b(blogs?|journals?|posts?)\b/, 'fa-pencil-square-o'],
            [/\b(certificates?|badges?|achievements?|awards?)\b/, 'fa-certificate'],
            [/(learning plans?|\bcompetenc|\bchecklists?\b)/, 'fa-list-alt'],
            [/\b(privacy|policy|shield|protection|retention)\b/, 'fa-shield'],
            [/\b(file|folder|document)\b|content bank/, 'fa-folder-open'],
            [/\b(profile|account|user|parent|principal)\b/, 'fa-user'],
            [/\b(preference|setting|configure|manage)\b/, 'fa-sliders']
        ];
        var match = rules.find(function(rule) {
            return rule[0].test(descriptor);
        });

        return match ? match[1] : (fallback || 'fa-arrow-right');
    };

    window.BoostOverrideCustomIcons = {
        classFor: semanticIcon,
        markup: function(name, extraClass) {
            return fontAwesomeIcon(semanticIcon(name, 'fa-book'), extraClass);
        }
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

    var pageAreaIcon = function(area) {
        var icons = {
            course: 'fa-graduation-cap',
            activity: 'fa-play-circle',
            report: 'fa-bar-chart',
            calendar: 'fa-calendar',
            message: 'fa-comments',
            user: 'fa-user',
            admin: 'fa-shield',
            content: 'fa-folder-open',
            learning: 'fa-book'
        };

        return fontAwesomeIcon(icons[area] || icons.learning, 'boc-platform-symbol-glyph');
    };

    var hasSpecialPageDesign = function(body) {
        if (body.classList.contains('pagelayout-mycourses')) {
            return true;
        }

        return [
            'theme-boost-override-custom-frontpage',
            'theme-boost-override-custom-courseindex',
            'theme-boost-override-custom-userprofile',
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
        symbol.innerHTML = pageAreaIcon(area);
        headings.prepend(symbol);
    };

    var controlDescriptor = function(control) {
        return [
            control.getAttribute('href'),
            control.getAttribute('data-action'),
            control.getAttribute('aria-label'),
            control.getAttribute('title'),
            control.getAttribute('name'),
            control.getAttribute('value'),
            control.className,
            clean(control.textContent)
        ].join(' ');
    };

    var isExcludedControl = function(control) {
        return control.matches(
            '.carousel-indicators button, .boc-login-dots button, .navbar-toggler, .toggle-sensitive-btn, ' +
            '[role="switch"], .custom-switch *, .form-check-input, .potentialidp a, .potentialidp button, ' +
            '.usermenu .dropdown-toggle, .userinitials'
        );
    };

    var visibleControlText = function(control) {
        var clone = control.cloneNode(true);

        clone.querySelectorAll(
            '.fa, .icon, svg, img, .accesshide, .sr-only, .visually-hidden, [aria-hidden="true"]'
        ).forEach(function(item) {
            item.remove();
        });

        return clean(clone.textContent);
    };

    var classifyActionControl = function(control) {
        var hasIcon = Boolean(control.querySelector(
            '.fa, [class^="fa-"], [class*=" fa-"], img.icon, svg.icon, .icon'
        ));

        control.classList.remove('boc-icon-only-control', 'boc-icon-text-control');
        if (!hasIcon) {
            return;
        }

        control.classList.add(visibleControlText(control) ? 'boc-icon-text-control' : 'boc-icon-only-control');
    };

    var decorateActionControls = function() {
        var selectors = [
            '.primary-navigation .nav-link',
            '.secondary-navigation .nav-link',
            '.navbar .login a',
            '.boc-login-nav a',
            '.btn',
            '.btn-link',
            'button',
            'a[role="button"]',
            '.dropdown-item',
            '.page-link',
            '.action-menu-item',
            '.list-group-item-action',
            '.drawer a.list-group-item',
            '#region-main .profile_tree a[href]',
            '#region-main .boc-learningtools-link',
            '#region-main .boc-learningtools-primary-link',
            '#region-main .boc-preferences-link',
            '#region-main .boc-accountprefs-nav-link',
            '#region-main .boc-category-cta',
            '#region-main .boc-course-hover-cta',
            '[class*="boc-hero"] a[href]',
            '.boc-white-label-footer a[href]'
        ].join(',');

        document.querySelectorAll(selectors).forEach(function(control) {
            var visibleLabel = clean(control.textContent);
            var descriptor;
            var icon;
            var customIcon;

            if (isExcludedControl(control)) {
                return;
            }
            if (control.dataset.bocProductIconReady === '1' &&
                    control.querySelector('.fa, [class^="fa-"], [class*=" fa-"]')) {
                classifyActionControl(control);
                return;
            }
            if (!visibleLabel && !clean(control.getAttribute('aria-label')) && !clean(control.getAttribute('title'))) {
                return;
            }
            if (control.classList.contains('page-link') && (
                    control.hasAttribute('aria-current') ||
                    /^\d+$/.test(visibleControlText(control))
            )) {
                control.dataset.bocProductIconReady = '1';
                return;
            }

            control.dataset.bocProductIconReady = '1';
            control.classList.add('boc-product-action');
            if (control.querySelector(
                '.fa, [class^="fa-"], [class*=" fa-"], img.icon, svg.icon, .icon'
            )) {
                classifyActionControl(control);
                return;
            }
            descriptor = controlDescriptor(control);
            if (/^(?:x|×|…|\.{3}|<|>|<<|>>|←|→)$/i.test(visibleLabel)) {
                control.textContent = '';
            }

            customIcon = control.querySelector(':scope > svg[class*="boc-"], :scope > span[class*="boc-"] > svg');
            if (customIcon) {
                if (customIcon.parentElement !== control && customIcon.parentElement &&
                        customIcon.parentElement.children.length === 1) {
                    customIcon.parentElement.remove();
                } else {
                    customIcon.remove();
                }
            }
            icon = document.createElement('i');
            icon.className = 'fa fa-fw ' + semanticIcon(
                descriptor,
                control.matches('button, .btn, [role="button"]') ? 'fa-check-circle' : 'fa-arrow-right'
            ) + ' boc-product-icon';
            icon.setAttribute('aria-hidden', 'true');
            control.insertBefore(icon, control.firstChild);
            classifyActionControl(control);
        });
    };

    var normaliseSmallCustomIcons = function() {
        var selector = [
            'span[class*="boc-"][class*="-icon"]',
            'div[class*="boc-"][class*="-icon"]',
            'svg[class*="boc-"][class*="-icon"]'
        ].join(',');

        document.querySelectorAll(selector).forEach(function(container) {
            var className = typeof container.className === 'string' ? container.className : container.getAttribute('class');
            var descriptor;
            var iconClass;
            var preservedClasses;
            var replacement;
            var context;

            if (!className || /product-icon|logo|avatar|visual|scene|illustration|art|defs|spinner|loader/i.test(className)) {
                return;
            }
            if (container.dataset && container.dataset.bocProductIconReady === '1') {
                return;
            }
            if (container.tagName.toLowerCase() !== 'svg' && !container.querySelector('svg')) {
                return;
            }

            context = container.closest(
                'a, button, h1, h2, h3, h4, article, section, li, .alert, .card, [class*="metric"], [class*="stat"]'
            );
            descriptor = (context ? clean(context.textContent).slice(0, 180) : '') + ' ' +
                className.replace(/\bboc-login(?:-[\w-]+)?\b/g, '');
            iconClass = loginComponentIcon(container) || semanticIcon(descriptor, 'fa-book');
            preservedClasses = className.split(/\s+/).filter(function(candidate) {
                return /^boc-/.test(candidate) && !/^boc-product/.test(candidate);
            }).join(' ');
            replacement = document.createElement('i');
            replacement.className = 'fa fa-fw ' + iconClass +
                ' boc-product-icon boc-product-standalone-icon' +
                (preservedClasses ? ' ' + preservedClasses : '');
            replacement.setAttribute('aria-hidden', 'true');

            if (container.tagName.toLowerCase() === 'svg') {
                container.replaceWith(replacement);
            } else {
                container.dataset.bocProductIconReady = '1';
                container.classList.add('boc-product-icon-surface');
                container.textContent = '';
                container.appendChild(replacement);
            }
        });
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
        normaliseSmallCustomIcons();
        decorateActionControls();
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
        document.querySelectorAll(
            'body.theme-boost-override-custom-platform .dropdown-menu.show, ' +
            'body.theme-boost-override-custom-login .dropdown-menu.show'
        ).forEach(fitDropdown);
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
