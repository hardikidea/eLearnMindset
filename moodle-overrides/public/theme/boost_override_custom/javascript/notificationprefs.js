(function() {
    'use strict';

    var pageSelector = '.theme-boost-override-custom-notificationprefs';
    var readyAttr = 'data-boc-notification-ready';
    var iconCounter = 0;

    var cleanText = function(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    };

    var textWithoutHidden = function(node) {
        var clone;

        if (!node) {
            return '';
        }

        clone = node.cloneNode(true);
        clone.querySelectorAll('.visually-hidden, .sr-only, .accesshide, .hover-tooltip').forEach(function(hidden) {
            hidden.remove();
        });
        return cleanText(clone.textContent);
    };

    var createElement = function(tag, className, text) {
        var node = document.createElement(tag);

        if (className) {
            node.className = className;
        }
        if (typeof text === 'string') {
            node.textContent = text;
        }
        return node;
    };

    var svg = function(name) {
        var id = 'boc-np-' + (++iconCounter) + '-';
        var icons = {
            avatar: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="16" x2="80" y1="12" y2="84"><stop stop-color="#e0f2fe"/><stop offset="1" stop-color="#dbeafe"/></linearGradient><filter id="' + id + 's" x="-20%" y="-20%" width="140%" height="140%"><feDropShadow dx="0" dy="10" stdDeviation="8" flood-color="#0f6cbf" flood-opacity=".18"/></filter></defs><circle cx="48" cy="48" r="42" fill="url(#' + id + 'a)" filter="url(#' + id + 's)"/><path d="M48 19c8 0 14 6 14 14s-6 14-14 14-14-6-14-14 6-14 14-14Zm0 35c15 0 27 9 27 21H21c0-12 12-21 27-21Z" fill="#0f172a" opacity=".9"/></svg>',
            bell: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="16" x2="50" y1="8" y2="56"><stop stop-color="#60a5fa"/><stop offset=".55" stop-color="#2563eb"/><stop offset="1" stop-color="#0f6cbf"/></linearGradient><filter id="' + id + 's" x="-35%" y="-35%" width="170%" height="170%"><feDropShadow dx="0" dy="8" stdDeviation="6" flood-color="#0f6cbf" flood-opacity=".28"/></filter></defs><path d="M32 57a7 7 0 0 0 7-7H25a7 7 0 0 0 7 7Z" fill="#0f172a" opacity=".78"/><path d="M16 45h32l-4-7V26c0-7-4-13-10-15V8a2 2 0 0 0-4 0v3c-6 2-10 8-10 15v12l-4 7Z" fill="url(#' + id + 'a)" filter="url(#' + id + 's)"/><path d="M23 43h18M25 26c1-5 4-8 9-9" stroke="#fff" stroke-width="4" stroke-linecap="round" opacity=".72"/></svg>',
            mail: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="11" x2="53" y1="13" y2="51"><stop stop-color="#67e8f9"/><stop offset=".55" stop-color="#06b6d4"/><stop offset="1" stop-color="#0f766e"/></linearGradient><filter id="' + id + 's" x="-25%" y="-25%" width="150%" height="150%"><feDropShadow dx="0" dy="8" stdDeviation="5" flood-color="#0891b2" flood-opacity=".27"/></filter></defs><rect x="10" y="16" width="44" height="32" rx="7" fill="url(#' + id + 'a)" filter="url(#' + id + 's)"/><path d="m14 21 18 15 18-15M16 46l12-12M48 46 36 34" fill="none" stroke="#ecfeff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round" opacity=".76"/></svg>',
            category: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="14" x2="52" y1="9" y2="55"><stop stop-color="#fdba74"/><stop offset=".55" stop-color="#f59e0b"/><stop offset="1" stop-color="#ea580c"/></linearGradient><filter id="' + id + 's" x="-25%" y="-25%" width="150%" height="150%"><feDropShadow dx="0" dy="8" stdDeviation="5" flood-color="#d97706" flood-opacity=".24"/></filter></defs><rect x="15" y="10" width="34" height="44" rx="8" fill="url(#' + id + 'a)" filter="url(#' + id + 's)"/><path d="M25 21h14M25 30h14M25 39h10" stroke="#fff7ed" stroke-width="4" stroke-linecap="round"/><path d="M49 18h4v30h-4z" fill="#fed7aa" opacity=".78"/></svg>',
            globe: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="10" x2="54" y1="10" y2="54"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#06b6d4"/></linearGradient></defs><circle cx="32" cy="32" r="22" fill="none" stroke="url(#' + id + 'a)" stroke-width="5"/><path d="M10 32h44M32 10c8 7 8 37 0 44M32 10c-8 7-8 37 0 44" fill="none" stroke="url(#' + id + 'a)" stroke-width="4" stroke-linecap="round"/></svg>',
            assignment: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="12" x2="52" y1="9" y2="55"><stop stop-color="#60a5fa"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs><rect x="14" y="10" width="36" height="44" rx="9" fill="url(#' + id + 'a)"/><path d="M24 28h16M24 37h12M25 18h14" stroke="#fff" stroke-width="4" stroke-linecap="round"/><path d="m23 44 4 4 10-12" fill="none" stroke="#bbf7d0" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            feedback: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="10" x2="54" y1="12" y2="52"><stop stop-color="#2dd4bf"/><stop offset="1" stop-color="#0891b2"/></linearGradient></defs><rect x="10" y="14" width="44" height="36" rx="12" fill="url(#' + id + 'a)"/><path d="M22 28h20M22 37h12" stroke="#fff" stroke-width="4" stroke-linecap="round"/><path d="m24 50-6 7v-9" fill="#0f766e"/></svg>',
            forum: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="10" x2="54" y1="12" y2="52"><stop stop-color="#fb923c"/><stop offset="1" stop-color="#f59e0b"/></linearGradient></defs><circle cx="24" cy="26" r="10" fill="url(#' + id + 'a)"/><circle cx="42" cy="28" r="9" fill="#fdba74"/><path d="M10 50c2-10 22-12 28-3 2-7 15-8 19 1" fill="none" stroke="#fff7ed" stroke-width="5" stroke-linecap="round"/></svg>',
            lesson: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="12" x2="52" y1="10" y2="54"><stop stop-color="#a78bfa"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs><path d="M16 13h26a8 8 0 0 1 8 8v31H21a5 5 0 0 0-5 5V13Z" fill="url(#' + id + 'a)"/><path d="M24 24h17M24 33h13" stroke="#fff" stroke-width="4" stroke-linecap="round"/></svg>',
            quiz: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="14" x2="52" y1="10" y2="54"><stop stop-color="#38bdf8"/><stop offset="1" stop-color="#4f46e5"/></linearGradient></defs><rect x="14" y="10" width="36" height="44" rx="12" fill="url(#' + id + 'a)"/><path d="M27 25a6 6 0 1 1 8 6c-3 1-3 3-3 5" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/><circle cx="32" cy="44" r="3" fill="#fff"/></svg>',
            system: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="10" x2="54" y1="10" y2="54"><stop stop-color="#94a3b8"/><stop offset="1" stop-color="#475569"/></linearGradient></defs><rect x="13" y="13" width="38" height="38" rx="12" fill="url(#' + id + 'a)"/><path d="M32 20v24M20 32h24M24 24l16 16M40 24 24 40" stroke="#f8fafc" stroke-width="4" stroke-linecap="round"/></svg>',
            shield: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="14" x2="50" y1="8" y2="56"><stop stop-color="#22c55e"/><stop offset="1" stop-color="#15803d"/></linearGradient></defs><path d="M32 8 51 16v14c0 13-8 22-19 27-11-5-19-14-19-27V16l19-8Z" fill="url(#' + id + 'a)"/><path d="m23 32 6 6 13-15" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };

        return icons[name] || icons.system;
    };

    var iconNode = function(name, className) {
        var node = createElement('span', className || 'boc-notification-icon');
        node.innerHTML = svg(name);
        return node;
    };

    var initials = function(name) {
        var parts = cleanText(name).split(' ').filter(Boolean);

        if (!parts.length) {
            return 'EM';
        }
        if (parts.length === 1) {
            return parts[0].slice(0, 2).toUpperCase();
        }
        return (parts[0][0] + parts[parts.length - 1][0]).toUpperCase();
    };

    var categorySlug = function(name) {
        var text = cleanText(name).toLowerCase();

        if (/assignment|submission|assign/.test(text)) {
            return 'assignment';
        }
        if (/feedback/.test(text)) {
            return 'feedback';
        }
        if (/forum|message/.test(text)) {
            return 'forum';
        }
        if (/lesson|book/.test(text)) {
            return 'lesson';
        }
        if (/quiz|question|test/.test(text)) {
            return 'quiz';
        }
        if (/enrol|privacy|monitor|system|badge|competenc|login/.test(text)) {
            return 'system';
        }
        return 'category';
    };

    var countFollowingPreferences = function(row) {
        var count = 0;
        var next = row.nextElementSibling;

        while (next && next.classList.contains('preference-row')) {
            count++;
            next = next.nextElementSibling;
        }
        return count;
    };

    var setText = function(root, selector, value) {
        var target = root.querySelector(selector);

        if (target) {
            target.textContent = value;
        }
    };

    var preferenceStats = function(table) {
        var toggles = Array.prototype.slice.call(table.querySelectorAll('.preference-row input.notification_enabled'));
        var web = toggles.filter(function(toggle) {
            return toggle.closest('[data-processor-name="popup"]');
        });
        var email = toggles.filter(function(toggle) {
            return toggle.closest('[data-processor-name="email"]');
        });
        var categories = Array.prototype.slice.call(table.querySelectorAll('tbody tr:not(.preference-row)')).filter(function(row) {
            return row.querySelector('th[colspan]');
        });

        return {
            webEnabled: web.filter(function(toggle) {
                return toggle.checked && !toggle.disabled;
            }).length,
            webTotal: web.length,
            emailEnabled: email.filter(function(toggle) {
                return toggle.checked && !toggle.disabled;
            }).length,
            emailTotal: email.length,
            categories: categories.length
        };
    };

    var findMessageAction = function() {
        return Array.prototype.slice.call(document.querySelectorAll('#page-header a, #page-header button')).find(function(action) {
            var text = cleanText(action.textContent).toLowerCase();
            var href = action.getAttribute('href') || '';

            return text === 'message' || href.indexOf('/message/') !== -1 || action.hasAttribute('data-conversationid');
        }) || null;
    };

    var sanitizeClone = function(node) {
        node.querySelectorAll('[id]').forEach(function(item) {
            item.removeAttribute('id');
        });
        return node;
    };

    var buildBreadcrumbs = function(shell) {
        var source = document.querySelector('#page-navbar .breadcrumb');
        var wrap;

        if (!source) {
            return null;
        }

        wrap = createElement('div', 'boc-notification-breadcrumbs');
        wrap.appendChild(sanitizeClone(source.cloneNode(true)));
        shell.appendChild(wrap);
        return wrap;
    };

    var buildHero = function(name, table) {
        var hero = createElement('section', 'boc-notification-hero');
        var profile = createElement('div', 'boc-notification-profile');
        var avatar = createElement('span', 'boc-notification-avatar', initials(name));
        var copy = createElement('div', 'boc-notification-profile-copy');
        var eyebrow = createElement('span', 'boc-notification-eyebrow', 'Notification centre');
        var heading = createElement('h2', '', name);
        var action = findMessageAction();
        var message = action ? sanitizeClone(action.cloneNode(true)) : createElement('span', 'boc-notification-message', 'Message');
        var stats = createElement('div', 'boc-notification-stats');
        var cards = [
            { key: 'web', icon: 'bell', label: 'Web alerts', meta: 'enabled in Moodle' },
            { key: 'email', icon: 'mail', label: 'Email alerts', meta: 'enabled in preferences' },
            { key: 'categories', icon: 'category', label: 'Categories managed', meta: 'live notification groups' }
        ];

        message.classList.add('boc-notification-message');
        copy.appendChild(eyebrow);
        copy.appendChild(heading);
        copy.appendChild(message);
        profile.appendChild(avatar);
        profile.appendChild(copy);

        cards.forEach(function(card) {
            var stat = createElement('article', 'boc-notification-stat boc-notification-stat-' + card.key);
            var value = createElement('strong', 'boc-notification-stat-value', '0');
            var label = createElement('span', 'boc-notification-stat-label', card.label);
            var meta = createElement('small', 'boc-notification-stat-meta', card.meta);

            stat.appendChild(iconNode(card.icon, 'boc-notification-stat-icon'));
            stat.appendChild(label);
            stat.appendChild(value);
            stat.appendChild(meta);
            stats.appendChild(stat);
        });

        hero.appendChild(profile);
        hero.appendChild(stats);
        updateHero(hero, table);
        return hero;
    };

    var updateHero = function(hero, table) {
        var stats = preferenceStats(table);
        var webValue = stats.webTotal ? stats.webEnabled + '/' + stats.webTotal : '0';
        var emailValue = stats.emailTotal ? stats.emailEnabled + '/' + stats.emailTotal : '0';

        setText(hero, '.boc-notification-stat-web .boc-notification-stat-value', webValue);
        setText(hero, '.boc-notification-stat-email .boc-notification-stat-value', emailValue);
        setText(hero, '.boc-notification-stat-categories .boc-notification-stat-value', String(stats.categories));
    };

    var buildGuide = function() {
        var guide = createElement('aside', 'boc-notification-guide');
        var title = createElement('h3', '', 'Delivery guide');
        var items = [
            { icon: 'globe', title: 'Web alerts', body: 'Instant in-app updates while the Moodle session is active.' },
            { icon: 'mail', title: 'Email digest', body: 'Course messages and reminders delivered to the registered email.' },
            { icon: 'bell', title: 'Course reminders', body: 'Keep assessment, forum and course activity notifications visible.' }
        ];

        guide.appendChild(title);
        items.forEach(function(item) {
            var row = createElement('div', 'boc-notification-guide-item');
            var copy = createElement('div');

            copy.appendChild(createElement('strong', '', item.title));
            copy.appendChild(createElement('p', '', item.body));
            row.appendChild(iconNode(item.icon, 'boc-notification-guide-icon'));
            row.appendChild(copy);
            guide.appendChild(row);
        });

        return guide;
    };

    var decorateTable = function(table) {
        var headers = Array.prototype.slice.call(table.querySelectorAll('thead [data-processor-name]'));
        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));

        headers.forEach(function(header) {
            var processor = header.getAttribute('data-processor-name') || '';
            var label = textWithoutHidden(header).replace(/^Requires configuration\s*/i, '') || processor;

            header.classList.add('boc-notification-processor-head', 'boc-notification-processor-' + processor);
            header.setAttribute('data-boc-label', label);
            if (!header.querySelector('.boc-notification-head-icon')) {
                header.insertBefore(iconNode(processor === 'email' ? 'mail' : 'globe', 'boc-notification-head-icon'), header.firstChild);
            }
        });

        rows.forEach(function(row) {
            var cell;
            var label;
            var slug;
            var count;

            if (row.classList.contains('preference-row') || !row.querySelector('th[colspan]')) {
                if (row.classList.contains('preference-row')) {
                    row.classList.add('boc-notification-preference-row');
                }
                return;
            }

            cell = row.querySelector('th');
            label = textWithoutHidden(cell);
            slug = categorySlug(label);
            count = countFollowingPreferences(row);
            row.classList.add('boc-notification-category-row', 'boc-notification-category-' + slug);
            cell.innerHTML = '';
            cell.appendChild(iconNode(slug, 'boc-notification-category-icon'));
            cell.appendChild(createElement('span', 'boc-notification-category-label', label));
            cell.appendChild(createElement('span', 'boc-notification-category-count', count + (count === 1 ? ' setting' : ' settings')));
        });
    };

    var enhance = function() {
        var body = document.querySelector(pageSelector);
        var region = document.querySelector('#region-main');
        var original = region ? region.querySelector('.preferences-page-container') : null;
        var table = original ? original.querySelector('table.preference-table') : null;
        var name = cleanText(document.querySelector('#page-header h1, .page-header-headings h1') ?
            document.querySelector('#page-header h1, .page-header-headings h1').textContent : '');
        var shell;
        var layout;
        var main;
        var hero;
        var sectionHead;
        var heading;
        var disable;
        var preferences;

        if (!body || !region || !original || !table || original.hasAttribute(readyAttr)) {
            return;
        }

        original.setAttribute(readyAttr, 'true');
        name = name || cleanText(document.querySelector('.usermenu .usertext') ?
            document.querySelector('.usermenu .usertext').textContent : '') || 'Notification preferences';

        decorateTable(table);

        shell = createElement('div', 'boc-notification-shell');
        buildBreadcrumbs(shell);
        layout = createElement('div', 'boc-notification-layout');
        main = createElement('main', 'boc-notification-main');
        hero = buildHero(name, table);
        sectionHead = createElement('div', 'boc-notification-section-head');
        heading = original.querySelector('#notificationpreferencesheading');
        disable = original.querySelector('[data-region="disable-notification-container"]');
        preferences = original.querySelector('.preferences-container');

        if (heading) {
            sectionHead.appendChild(heading);
        }
        if (disable) {
            sectionHead.appendChild(disable);
        }
        if (preferences) {
            preferences.classList.add('boc-notification-table-shell');
        }

        original.insertBefore(sectionHead, original.firstChild);
        main.appendChild(hero);
        main.appendChild(original);
        layout.appendChild(main);
        layout.appendChild(buildGuide());
        shell.appendChild(layout);
        region.appendChild(shell);

        region.addEventListener('change', function(event) {
            if (event.target.matches('input.notification_enabled, [data-disable-notifications]')) {
                window.setTimeout(function() {
                    updateHero(hero, table);
                }, 220);
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhance);
    } else {
        enhance();
    }
})();
