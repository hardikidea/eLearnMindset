(function() {
    'use strict';

    var pageSelector = '.theme-boost-override-custom-userpreferences';
    var readyAttr = 'data-boc-preferences-ready';
    var iconCounter = 0;

    var cleanText = function(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
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

    var uniqueId = function(prefix) {
        iconCounter += 1;
        return 'boc-pref-' + iconCounter + '-' + prefix;
    };

    var iconNameForText = function(text) {
        var value = cleanText(text).toLowerCase();

        if (value.indexOf('badge') !== -1 || value.indexOf('backpack') !== -1) {
            return 'badge';
        }
        if (value.indexOf('blog') !== -1) {
            return 'blog';
        }
        if (value.indexOf('calendar') !== -1) {
            return 'calendar';
        }
        if (value.indexOf('message') !== -1 || value.indexOf('notification') !== -1 || value.indexOf('forum') !== -1) {
            return 'message';
        }
        if (value.indexOf('password') !== -1 || value.indexOf('account') !== -1) {
            return 'shield';
        }
        if (value.indexOf('language') !== -1) {
            return 'language';
        }
        if (value.indexOf('editor') !== -1 || value.indexOf('content') !== -1 || value.indexOf('profile') !== -1) {
            return 'profile';
        }
        return 'settings';
    };

    var iconSvg = function(name) {
        var id = uniqueId(name);
        var icons = {
            settings: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="16" x2="80" y1="14" y2="82"><stop stop-color="#60a5fa"/><stop offset=".55" stop-color="#0f6cbf"/><stop offset="1" stop-color="#0891b2"/></linearGradient><filter id="' + id + 's" x="-24%" y="-24%" width="148%" height="148%"><feDropShadow dx="0" dy="14" stdDeviation="9" flood-color="#0f6cbf" flood-opacity=".22"/></filter></defs><rect x="18" y="18" width="60" height="60" rx="18" fill="url(#' + id + 'a)" filter="url(#' + id + 's)"/><path d="M49 27v8M49 61v8M27 49h8M61 49h8M34 34l6 6M58 58l6 6M64 34l-6 6M40 58l-6 6" stroke="#fff" stroke-width="5" stroke-linecap="round"/><circle cx="49" cy="49" r="11" fill="#fff"/><circle cx="49" cy="49" r="5" fill="#0f6cbf"/></svg>',
            profile: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="15" x2="78" y1="14" y2="82"><stop stop-color="#38bdf8"/><stop offset=".55" stop-color="#2563eb"/><stop offset="1" stop-color="#7c3aed"/></linearGradient></defs><rect x="17" y="16" width="62" height="64" rx="18" fill="url(#' + id + 'a)"/><circle cx="48" cy="40" r="13" fill="#fff"/><path d="M27 70c5-13 36-13 42 0" fill="#dbeafe"/><path d="M68 23h9v20" stroke="#fef3c7" stroke-width="6" stroke-linecap="round"/></svg>',
            shield: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="20" x2="76" y1="12" y2="84"><stop stop-color="#22c55e"/><stop offset=".52" stop-color="#0891b2"/><stop offset="1" stop-color="#0f6cbf"/></linearGradient></defs><path d="M48 12 76 24v20c0 19-11 32-28 40-17-8-28-21-28-40V24l28-12Z" fill="url(#' + id + 'a)"/><rect x="33" y="43" width="30" height="23" rx="8" fill="#fff"/><path d="M39 43v-7c0-13 18-13 18 0v7" fill="none" stroke="#0f172a" stroke-width="6" stroke-linecap="round"/></svg>',
            blog: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="18" x2="78" y1="15" y2="82"><stop stop-color="#f59e0b"/><stop offset=".55" stop-color="#f97316"/><stop offset="1" stop-color="#ec4899"/></linearGradient></defs><rect x="18" y="18" width="60" height="60" rx="17" fill="url(#' + id + 'a)"/><path d="M31 35h35M31 48h26M31 61h18" stroke="#fff" stroke-width="6" stroke-linecap="round"/><circle cx="67" cy="61" r="7" fill="#fef3c7"/></svg>',
            badge: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="17" x2="78" y1="12" y2="82"><stop stop-color="#a78bfa"/><stop offset=".55" stop-color="#7c3aed"/><stop offset="1" stop-color="#0f6cbf"/></linearGradient></defs><path d="M48 13 60 31l21 6-13 17 1 22-21-8-21 8 1-22-13-17 21-6 12-18Z" fill="url(#' + id + 'a)"/><circle cx="48" cy="45" r="15" fill="#fff"/><path d="m41 45 5 5 10-12" fill="none" stroke="#7c3aed" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            calendar: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="18" x2="78" y1="15" y2="82"><stop stop-color="#60a5fa"/><stop offset=".55" stop-color="#0ea5e9"/><stop offset="1" stop-color="#0891b2"/></linearGradient></defs><rect x="18" y="20" width="60" height="58" rx="17" fill="url(#' + id + 'a)"/><path d="M18 37h60" stroke="#dbeafe" stroke-width="6"/><path d="M34 15v15M62 15v15" stroke="#0f172a" stroke-width="6" stroke-linecap="round"/><rect x="31" y="48" width="10" height="10" rx="3" fill="#fff"/><rect x="48" y="48" width="10" height="10" rx="3" fill="#fef3c7"/><rect x="31" y="64" width="10" height="8" rx="3" fill="#ccfbf1"/></svg>',
            message: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="18" x2="78" y1="17" y2="80"><stop stop-color="#22c55e"/><stop offset=".55" stop-color="#14b8a6"/><stop offset="1" stop-color="#0f6cbf"/></linearGradient></defs><rect x="17" y="22" width="62" height="46" rx="16" fill="url(#' + id + 'a)"/><path d="M25 34 48 50l23-16" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/><path d="M34 68 25 80V66" fill="#14b8a6"/></svg>',
            language: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="16" x2="80" y1="15" y2="82"><stop stop-color="#38bdf8"/><stop offset=".52" stop-color="#0f6cbf"/><stop offset="1" stop-color="#f97316"/></linearGradient></defs><rect x="17" y="17" width="62" height="62" rx="18" fill="url(#' + id + 'a)"/><path d="M30 34h27M43 28v32M33 60c11-8 17-17 19-26" fill="none" stroke="#fff" stroke-width="5" stroke-linecap="round"/><path d="m59 64 5-17 7 17M61 58h8" fill="none" stroke="#fef3c7" stroke-width="5" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };

        return icons[name] || icons.settings;
    };

    var iconNode = function(name, className) {
        var node = createElement('span', className || 'boc-preferences-icon');
        node.innerHTML = iconSvg(name);
        return node;
    };

    var cardSummary = function(title, linkCount) {
        var lower = cleanText(title).toLowerCase();

        if (lower.indexOf('user') !== -1 || lower.indexOf('account') !== -1) {
            return 'Profile, password, language, calendar, messaging and content preferences.';
        }
        if (lower.indexOf('blog') !== -1) {
            return 'Publishing defaults and external learning journal connections.';
        }
        if (lower.indexOf('badge') !== -1) {
            return 'Achievement display, backpack sync and badge visibility settings.';
        }
        return linkCount + ' configurable preference actions available.';
    };

    var getUserName = function() {
        return cleanText(document.querySelector('#page-header h1, .page-header-headings h1') &&
            document.querySelector('#page-header h1, .page-header-headings h1').textContent) ||
            cleanText(document.querySelector('.usertext, [data-userfullname]') &&
            document.querySelector('.usertext, [data-userfullname]').textContent) ||
            cleanText(document.title.replace(/\s*\|.*$/, '')) ||
            'User';
    };

    var initialsForName = function(name) {
        var initials = cleanText(name).split(' ').filter(Boolean).slice(0, 2).map(function(part) {
            return part.charAt(0).toUpperCase();
        }).join('');

        return initials || 'U';
    };

    var decorateCard = function(card, index) {
        var title = card.querySelector('.card-title');
        var list = card.querySelector('ul');
        var links = Array.prototype.slice.call(card.querySelectorAll('a'));
        var titleText = title ? cleanText(title.textContent) : 'Preferences';
        var iconName = iconNameForText(titleText);
        var body = card.querySelector('.card-body') || card;
        var header;
        var copy;
        var count;
        var summary;

        if (card.hasAttribute('data-boc-preferences-card-ready')) {
            return;
        }

        card.setAttribute('data-boc-preferences-card-ready', 'true');
        card.classList.add('boc-preferences-card', 'boc-preferences-card-' + iconName);
        card.style.setProperty('--boc-preferences-stagger', String(index * 80) + 'ms');
        body.classList.add('boc-preferences-card-body');

        if (title) {
            header = createElement('div', 'boc-preferences-card-header');
            copy = createElement('div', 'boc-preferences-card-copy');
            count = createElement('span', 'boc-preferences-count', links.length + ' actions');
            summary = createElement('p', 'boc-preferences-summary', cardSummary(titleText, links.length));

            body.insertBefore(header, title);
            header.appendChild(iconNode(iconName, 'boc-preferences-card-icon'));
            header.appendChild(copy);
            copy.appendChild(title);
            copy.appendChild(count);
            body.insertBefore(summary, list || null);
        }

        if (list) {
            list.classList.add('boc-preferences-link-list');
        }

        links.forEach(function(link, linkIndex) {
            var item = link.closest('li');
            var linkIconName = iconNameForText(link.textContent);

            if (item) {
                item.classList.add('boc-preferences-link-item');
                item.style.setProperty('--boc-preferences-link-stagger', String(linkIndex * 30) + 'ms');
            }
            link.classList.add('boc-preferences-link');
            link.setAttribute('data-boc-preferences-search', cleanText(titleText + ' ' + link.textContent).toLowerCase());
            if (!link.querySelector('.boc-preferences-link-icon')) {
                var arrow = createElement('span', 'boc-preferences-link-arrow', '->');

                arrow.setAttribute('aria-hidden', 'true');
                link.prepend(iconNode(linkIconName, 'boc-preferences-link-icon'));
                link.appendChild(arrow);
            }
        });
    };

    var buildMetric = function(icon, label, value, meta) {
        var metric = createElement('article', 'boc-preferences-metric');

        metric.appendChild(iconNode(icon, 'boc-preferences-metric-icon'));
        metric.appendChild(createElement('span', 'boc-preferences-metric-label', label));
        metric.appendChild(createElement('strong', 'boc-preferences-metric-value', value));
        metric.appendChild(createElement('small', 'boc-preferences-metric-meta', meta));
        return metric;
    };

    var buildHero = function(titleText, userName, cards, links) {
        var hero = createElement('section', 'boc-preferences-hero');
        var copy = createElement('div', 'boc-preferences-hero-copy');
        var visual = createElement('div', 'boc-preferences-visual');
        var badge = createElement('span', 'boc-preferences-eyebrow', 'Personal control centre');
        var title = createElement('h1', '', titleText || 'Preferences');
        var lead = createElement('p', '', 'Manage your account, notifications, calendar, content tools and learning identity from one Moodle-powered settings hub.');
        var user = createElement('div', 'boc-preferences-user');
        var metrics = createElement('div', 'boc-preferences-metrics');
        var cube = createElement('div', 'boc-preferences-orbit');
        var panel = createElement('div', 'boc-preferences-visual-panel');

        user.appendChild(createElement('span', 'boc-preferences-avatar', initialsForName(userName)));
        user.appendChild(createElement('strong', '', userName));
        user.appendChild(createElement('span', '', 'Signed-in Moodle profile'));

        metrics.appendChild(buildMetric('settings', 'Preference groups', String(cards.length), 'Live permissions'));
        metrics.appendChild(buildMetric('profile', 'Available actions', String(links.length), 'Real Moodle links'));
        metrics.appendChild(buildMetric('shield', 'Access scope', 'Secure', 'Session protected'));

        copy.appendChild(badge);
        copy.appendChild(title);
        copy.appendChild(lead);
        copy.appendChild(user);
        copy.appendChild(metrics);

        cube.appendChild(createElement('span', 'boc-preferences-orbit-chip boc-preferences-orbit-chip-one', 'Profile'));
        cube.appendChild(createElement('span', 'boc-preferences-orbit-chip boc-preferences-orbit-chip-two', 'Privacy'));
        cube.appendChild(createElement('span', 'boc-preferences-orbit-chip boc-preferences-orbit-chip-three', 'Learning'));
        panel.appendChild(iconNode('settings', 'boc-preferences-visual-icon'));
        panel.appendChild(createElement('strong', '', 'Settings'));
        panel.appendChild(createElement('span', '', links.length + ' available actions'));
        visual.appendChild(cube);
        visual.appendChild(panel);

        hero.appendChild(copy);
        hero.appendChild(visual);
        return hero;
    };

    var buildToolbar = function(cards) {
        var toolbar = createElement('section', 'boc-preferences-toolbar');
        var search = createElement('label', 'boc-preferences-search');
        var input = document.createElement('input');
        var chips = createElement('div', 'boc-preferences-chips');
        var clear = createElement('button', 'boc-preferences-clear', 'Clear');
        var status = createElement('span', 'boc-preferences-status');

        input.type = 'search';
        input.autocomplete = 'off';
        input.placeholder = 'Search preferences';
        input.setAttribute('aria-label', 'Search preferences');
        search.appendChild(iconNode('settings', 'boc-preferences-search-icon'));
        search.appendChild(input);

        chips.appendChild(chipButton('All', 'all', true));
        cards.forEach(function(card) {
            var title = card.querySelector('.card-title');
            chips.appendChild(chipButton(title ? cleanText(title.textContent) : 'Preferences', title ? cleanText(title.textContent) : 'Preferences', false));
        });

        toolbar.appendChild(search);
        toolbar.appendChild(chips);
        toolbar.appendChild(clear);
        toolbar.appendChild(status);
        return toolbar;
    };

    var chipButton = function(label, value, pressed) {
        var button = createElement('button', 'boc-preferences-chip', label);

        button.type = 'button';
        button.setAttribute('data-boc-preferences-chip', value);
        button.setAttribute('aria-pressed', pressed ? 'true' : 'false');
        return button;
    };

    var buildGuide = function(links) {
        var guide = createElement('aside', 'boc-preferences-guide');
        var featured = links.filter(function(link) {
            var text = cleanText(link.textContent).toLowerCase();
            return text.indexOf('notification') !== -1 ||
                text.indexOf('calendar') !== -1 ||
                text.indexOf('profile') !== -1 ||
                text.indexOf('password') !== -1;
        }).slice(0, 4);
        var list = createElement('div', 'boc-preferences-guide-list');

        if (!featured.length) {
            featured = links.slice(0, 4);
        }

        guide.appendChild(createElement('h2', '', 'Quick access'));
        guide.appendChild(createElement('p', '', 'Frequently used preference areas stay visible while the full list remains searchable.'));

        featured.forEach(function(link) {
            var item = document.createElement('a');
            var text = cleanText(link.textContent);

            item.className = 'boc-preferences-guide-link';
            item.href = link.href;
            item.appendChild(iconNode(iconNameForText(text), 'boc-preferences-guide-icon'));
            item.appendChild(createElement('span', '', text));
            list.appendChild(item);
        });

        guide.appendChild(list);
        return guide;
    };

    var applyFilter = function(shell, query, group) {
        var cards = Array.prototype.slice.call(shell.querySelectorAll('.boc-preferences-card'));
        var normalizedQuery = cleanText(query).toLowerCase();
        var normalizedGroup = cleanText(group).toLowerCase();
        var visibleLinks = 0;
        var visibleCards = 0;
        var empty = shell.querySelector('.boc-preferences-empty');
        var status = shell.querySelector('.boc-preferences-status');

        cards.forEach(function(card) {
            var title = cleanText(card.querySelector('.card-title') && card.querySelector('.card-title').textContent).toLowerCase();
            var groupMatches = !normalizedGroup || normalizedGroup === 'all' || title === normalizedGroup;
            var cardHasVisible = false;

            card.querySelectorAll('.boc-preferences-link-item').forEach(function(item) {
                var link = item.querySelector('a');
                var text = link ? cleanText(link.getAttribute('data-boc-preferences-search') || link.textContent).toLowerCase() : '';
                var linkMatches = !normalizedQuery || text.indexOf(normalizedQuery) !== -1;
                var show = groupMatches && linkMatches;

                item.hidden = !show;
                if (show) {
                    visibleLinks += 1;
                    cardHasVisible = true;
                }
            });

            card.hidden = !cardHasVisible;
            if (cardHasVisible) {
                visibleCards += 1;
            }
        });

        if (empty) {
            empty.hidden = visibleLinks !== 0;
        }
        if (status) {
            status.textContent = visibleLinks + ' actions in ' + visibleCards + ' groups';
        }
    };

    var bindFilters = function(shell) {
        var input = shell.querySelector('.boc-preferences-search input');
        var clear = shell.querySelector('.boc-preferences-clear');
        var chips = Array.prototype.slice.call(shell.querySelectorAll('.boc-preferences-chip'));
        var currentGroup = 'all';

        var update = function() {
            applyFilter(shell, input ? input.value : '', currentGroup);
        };

        if (input) {
            input.addEventListener('input', update);
        }
        chips.forEach(function(chip) {
            chip.addEventListener('click', function() {
                currentGroup = chip.getAttribute('data-boc-preferences-chip') || 'all';
                chips.forEach(function(item) {
                    item.setAttribute('aria-pressed', item === chip ? 'true' : 'false');
                });
                update();
            });
        });
        if (clear) {
            clear.addEventListener('click', function() {
                if (input) {
                    input.value = '';
                    input.focus();
                }
                currentGroup = 'all';
                chips.forEach(function(item) {
                    item.setAttribute('aria-pressed', item.getAttribute('data-boc-preferences-chip') === 'all' ? 'true' : 'false');
                });
                update();
            });
        }
        update();
    };

    var getPreferenceCards = function(main) {
        return Array.prototype.slice.call(main.querySelectorAll('.card.mb-3, .card')).filter(function(card) {
            return card.querySelector('.card-title') && card.querySelectorAll('a').length;
        });
    };

    var getSourceHeading = function(main) {
        return main.querySelector('h1, h2, h3');
    };

    var getDirectMainChild = function(main, node) {
        var current = node;

        while (current && current.parentElement && current.parentElement !== main) {
            current = current.parentElement;
        }

        return current && current.parentElement === main ? current : null;
    };

    var enhance = function() {
        var body = document.querySelector(pageSelector);
        var main = document.querySelector('#region-main');
        var cards = main ? getPreferenceCards(main) : [];
        var links = [];
        var sourceHeading;
        var insertionPoint;
        var shell;
        var layout;
        var grid;
        var titleText;

        if (!body || !main || main.hasAttribute(readyAttr) || !cards.length) {
            return;
        }

        main.setAttribute(readyAttr, 'true');
        sourceHeading = getSourceHeading(main);
        titleText = sourceHeading ? cleanText(sourceHeading.textContent) : 'Preferences';
        links = Array.prototype.slice.call(main.querySelectorAll('.card a'));
        insertionPoint = getDirectMainChild(main, cards[0]) || main.firstElementChild;
        shell = createElement('div', 'boc-preferences-shell');
        layout = createElement('div', 'boc-preferences-layout');
        grid = createElement('section', 'boc-preferences-grid');

        if (sourceHeading) {
            sourceHeading.classList.add('boc-preferences-source-heading');
        }

        main.insertBefore(shell, insertionPoint);
        shell.appendChild(buildHero(titleText, getUserName(), cards, links));
        shell.appendChild(buildToolbar(cards));

        cards.forEach(function(card, index) {
            decorateCard(card, index);
            grid.appendChild(card);
        });

        layout.appendChild(grid);
        layout.appendChild(buildGuide(links));
        shell.appendChild(layout);

        var empty = createElement('div', 'boc-preferences-empty');
        empty.hidden = true;
        empty.appendChild(iconNode('settings', 'boc-preferences-empty-icon'));
        empty.appendChild(createElement('strong', '', 'No matching preferences'));
        empty.appendChild(createElement('span', '', 'Try a different keyword or clear the filters.'));
        shell.appendChild(empty);

        bindFilters(shell);

        window.setTimeout(function() {
            shell.classList.add('boc-preferences-animations-settled');
        }, 900);
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhance);
    } else {
        enhance();
    }
})();
