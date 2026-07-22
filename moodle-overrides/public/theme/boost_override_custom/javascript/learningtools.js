(function() {
    'use strict';

    var pageSelector = 'body.theme-boost-override-custom-learningtools';
    var iconSeed = 0;

    var clean = function(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    };

    var create = function(tag, className, text) {
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
        iconSeed += 1;
        return 'boc-lt-' + name + '-' + iconSeed;
    };

    var pagePath = function() {
        return window.location.pathname.replace(/\/+$/, '') || '/';
    };

    var iconSvg = function(name) {
        var id = uniqueId(name);
        var palettes = {
            blog: ['#f97316', '#ec4899'],
            badges: ['#7c3aed', '#2563eb'],
            backpack: ['#0891b2', '#22c55e'],
            reports: ['#2563eb', '#06b6d4'],
            certificate: ['#f59e0b', '#ef4444'],
            forum: ['#14b8a6', '#0f6cbf'],
            plans: ['#22c55e', '#0891b2'],
            sessions: ['#0f6cbf', '#7c3aed'],
            privacy: ['#10b981', '#0f6cbf'],
            tools: ['#0f6cbf', '#0891b2']
        };
        var pair = palettes[name] || palettes.tools;
        var glyphs = {
            blog: '<path d="M7 6.6h10v10.8H7z" fill="#fff"/><path d="M9.2 9.3h5.6M9.2 12h4.6M9.2 14.7h5.6" stroke="#0f172a" stroke-width="1.25" stroke-linecap="round" opacity=".62"/><circle cx="17.2" cy="6.8" r="2.1" fill="#fef3c7"/>',
            badges: '<path d="m12 4.2 2.5 4.3 4.8 1.1-3.3 3.7.4 4.9-4.4-2-4.4 2 .4-4.9-3.3-3.7 4.8-1.1L12 4.2Z" fill="#fff"/><path d="m9.8 11.6 1.6 1.6 3.2-3.8" fill="none" stroke="#0f172a" stroke-width="1.45" stroke-linecap="round" stroke-linejoin="round" opacity=".58"/>',
            backpack: '<path d="M7.2 8.2h9.6a2 2 0 0 1 2 2v7.1a2 2 0 0 1-2 2H7.2a2 2 0 0 1-2-2v-7.1a2 2 0 0 1 2-2Z" fill="#fff"/><path d="M9 8.2V6.8A2.8 2.8 0 0 1 11.8 4h.4A2.8 2.8 0 0 1 15 6.8v1.4M8.3 12.1h7.4M8.3 15.1h5" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".6"/>',
            reports: '<path d="M6 17.8h12v1.8H6zM7.1 12.2h2.6v5.6H7.1zM10.7 8.6h2.6v9.2h-2.6zM14.3 5.5h2.6v12.3h-2.6z" fill="#fff"/><path d="m7.1 8.2 3.2-2.1 2.8 1.6 3.8-3.2" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round"/>',
            certificate: '<rect x="5" y="6.2" width="14" height="11.6" rx="2.4" fill="#fff"/><path d="M8 10h8M8 13h5.2" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".6"/><path d="m14.8 15.6 1.1 3.7 1.3-1 1.5.4-1.2-3.7" fill="#fef3c7"/>',
            forum: '<path d="M5.2 6.8h10.2a2.4 2.4 0 0 1 2.4 2.4v4.4a2.4 2.4 0 0 1-2.4 2.4h-3.5l-4.3 3v-3H5.2V6.8Z" fill="#fff"/><path d="M8.2 10h6.2M8.2 12.8h4.5" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".6"/>',
            plans: '<path d="M6 6.4h12v12H6z" fill="#fff"/><path d="M8.6 9.2h6.8M8.6 12.1h6.8M8.6 15h4.2" stroke="#0f172a" stroke-width="1.25" stroke-linecap="round" opacity=".62"/><path d="m15.4 4.7 1.8 1.7-5.3 5.4-2.4.5.5-2.4 5.4-5.2Z" fill="#dbeafe"/>',
            sessions: '<rect x="5.2" y="6.6" width="13.6" height="9.7" rx="2.4" fill="#fff"/><path d="M9 19.2h6M12 16.3v2.9M8.1 9.4h7.8" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".62"/><circle cx="16.4" cy="13.2" r="1.4" fill="#bfdbfe"/>',
            privacy: '<path d="M12 3.7 18.5 6v5.1c0 4-2.6 7.2-6.5 8.8-3.9-1.6-6.5-4.8-6.5-8.8V6L12 3.7Z" fill="#fff"/><path d="m8.8 12.2 2 2 4.4-5.1" fill="none" stroke="#0f172a" stroke-width="1.55" stroke-linecap="round" stroke-linejoin="round" opacity=".62"/>',
            tools: '<path d="M7.6 6.8h8.8v10.4H7.6z" fill="#fff"/><path d="M9.6 9.2h4.8M9.6 12h4.8M9.6 14.8h3" stroke="#0f172a" stroke-width="1.25" stroke-linecap="round" opacity=".6"/>'
        };

        return '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">' +
            '<defs><linearGradient id="' + id + 'a" x1="3" y1="3" x2="21" y2="21" gradientUnits="userSpaceOnUse">' +
            '<stop stop-color="' + pair[0] + '"/><stop offset="1" stop-color="' + pair[1] + '"/></linearGradient></defs>' +
            '<rect x="2.6" y="2.6" width="18.8" height="18.8" rx="5.8" fill="url(#' + id + 'a)"/>' +
            '<path d="M6.1 4.5h8.4c3.2 0 5.4 2 5.4 4.8-4.2-1.3-9.7-1.9-15.6-1 .3-2.1 1-3.8 1.8-3.8Z" fill="#fff" opacity=".24"/>' +
            '<g filter="drop-shadow(0 7px 8px rgba(15,23,42,.18))">' + (glyphs[name] || glyphs.tools) + '</g></svg>';
    };

    var heroSvg = function(icon) {
        var id = uniqueId('hero');
        return '<svg viewBox="0 0 300 188" aria-hidden="true" focusable="false">' +
            '<defs><linearGradient id="' + id + 'a" x1="34" y1="24" x2="268" y2="165" gradientUnits="userSpaceOnUse"><stop stop-color="#eff6ff"/><stop offset=".52" stop-color="#dbeafe"/><stop offset="1" stop-color="#ccfbf1"/></linearGradient>' +
            '<linearGradient id="' + id + 'b" x1="54" y1="36" x2="229" y2="150" gradientUnits="userSpaceOnUse"><stop stop-color="#fff"/><stop offset="1" stop-color="#edf9ff"/></linearGradient>' +
            '<linearGradient id="' + id + 'c" x1="37" y1="150" x2="264" y2="150" gradientUnits="userSpaceOnUse"><stop stop-color="#2563eb"/><stop offset=".5" stop-color="#14b8a6"/><stop offset="1" stop-color="#f59e0b"/></linearGradient></defs>' +
            '<path class="boc-lt-svg-float" d="M35 139c26 37 96 47 154 25 59-21 94-71 68-108C231 19 158 12 100 35 43 58 9 101 35 139Z" fill="url(#' + id + 'a)"/>' +
            '<rect class="boc-lt-svg-card" x="62" y="41" width="158" height="92" rx="23" fill="url(#' + id + 'b)" stroke="#fff" stroke-width="3"/>' +
            '<foreignObject x="91" y="54" width="72" height="72"><div xmlns="http://www.w3.org/1999/xhtml" class="boc-lt-hero-icon">' + iconSvg(icon) + '</div></foreignObject>' +
            '<rect x="160" y="67" width="48" height="11" rx="6" fill="#bfdbfe"/><rect x="160" y="90" width="36" height="10" rx="5" fill="#bae6fd"/><rect x="160" y="111" width="50" height="10" rx="5" fill="#bbf7d0"/>' +
            '<rect x="203" y="28" width="58" height="34" rx="14" fill="#fff" stroke="#dbeafe" stroke-width="2"/><text x="216" y="50" fill="#0f6cbf" font-size="12" font-weight="800">Live</text>' +
            '<path class="boc-lt-svg-line" d="M43 152c45-21 88-20 122-6 31 12 61 6 92-12" fill="none" stroke="url(#' + id + 'c)" stroke-width="8" stroke-linecap="round" opacity=".72"/></svg>';
    };

    var metaForPage = function() {
        var path = pagePath();
        var query = new URLSearchParams(window.location.search);
        var forumMode = query.get('mode') === 'discussions';
        var map = {
            '/blog/preferences.php': ['blog', 'Blog preferences', 'Publishing settings', 'Control how many blog entries appear per page and keep your Moodle journal easier to scan.'],
            '/blog/external_blogs.php': ['blog', 'External blogs', 'Connected journals', 'Manage external learning journal feeds registered with this Moodle account.'],
            '/blog/external_blog_edit.php': ['blog', 'Register external blog', 'RSS connection', "Add a real RSS feed, name, description and tags using Moodle's original form controls."],
            '/blog/edit.php': ['blog', 'Add blog entry', 'Learning journal editor', "Write a Moodle blog post using the original title, editor, attachment, publish and tag controls."],
            '/badges/mybadges.php': ['badges', 'My badges', 'Achievements', 'Review earned badges, backpack connection status and badge search tools.'],
            '/badges/preferences.php': ['badges', 'Badge preferences', 'Achievement privacy', 'Choose how earned badges appear on your public Moodle profile.'],
            '/badges/mybackpack.php': ['backpack', 'Backpack settings', 'External badge wallet', "Connect your badge backpack provider using Moodle's secure backpack form."],
            '/reportbuilder/index.php': ['reports', 'Custom reports', 'Report builder', 'Filter and review custom Moodle reports from one compact workspace.'],
            '/blog/index.php': ['blog', 'User blog', 'Learning journal', 'Read and manage Moodle blog entries connected to this user profile.'],
            '/mod/customcert/my_certificates.php': ['certificate', 'My certificates', 'Issued records', 'Review certificates issued by download or email for this Moodle user.'],
            '/admin/tool/lp/plans.php': ['plans', 'Learning plans', 'Competency planning', 'Track learning plans and evidence available for this user.'],
            '/report/usersessions/user.php': ['sessions', 'Browser sessions', 'Security activity', 'Review active browser sessions and sign out sessions when needed.'],
            '/admin/tool/dataprivacy/summary.php': ['privacy', 'Data retention summary', 'Privacy registry', 'Review Moodle retention categories and purposes configured for the site.']
        };

        if (path === '/mod/forum/user.php') {
            return {
                icon: 'forum',
                title: forumMode ? 'Forum discussions' : 'Forum posts',
                eyebrow: forumMode ? 'Started discussions' : 'Community activity',
                lead: forumMode ? 'Review discussions started by this user across Moodle forums.' :
                    'Review forum posts made by this user across Moodle courses.'
            };
        }

        var item = map[path] || ['tools', 'Learning tools', 'Moodle workspace', 'Review this Moodle area using the original live data and controls.'];
        return {
            icon: item[0],
            title: item[1],
            eyebrow: item[2],
            lead: item[3]
        };
    };

    var visibleMainChildren = function(main) {
        return Array.prototype.slice.call(main.childNodes).filter(function(node) {
            return node.nodeType === 1 || clean(node.textContent);
        });
    };

    var sourceTitle = function(main, meta) {
        var heading = main.querySelector('h1, h2');
        var value = clean(heading && heading.textContent);

        if (heading) {
            heading.classList.add('boc-learningtools-source-heading');
        }

        return meta.title || value || clean(document.title.replace(/\s*\|.*$/, '')) || 'Moodle tools';
    };

    var collectStats = function(content) {
        var forms = content.querySelectorAll('form').length;
        var controls = content.querySelectorAll('input, select, textarea, button').length;
        var tables = Array.prototype.slice.call(content.querySelectorAll('table'));
        var rows = tables.reduce(function(total, table) {
            return total + Math.max(0, table.querySelectorAll('tbody tr, tr').length - (table.querySelector('thead') ? 0 : 1));
        }, 0);
        var alerts = content.querySelectorAll('.alert, [role="alert"]').length;
        var links = content.querySelectorAll('a[href]').length;

        return {
            actions: controls + links,
            forms: forms,
            records: rows,
            alerts: alerts,
            links: links,
            tables: tables.length
        };
    };

    var metric = function(icon, label, value, helper) {
        var item = create('article', 'boc-learningtools-metric');
        var iconNode = create('span', 'boc-learningtools-metric-icon');
        var copy = create('span', 'boc-learningtools-metric-copy');

        iconNode.innerHTML = iconSvg(icon);
        copy.appendChild(create('strong', '', value));
        copy.appendChild(create('span', '', label));
        if (helper) {
            copy.appendChild(create('small', '', helper));
        }
        item.appendChild(iconNode);
        item.appendChild(copy);
        return item;
    };

    var buildHero = function(meta, title, stats) {
        var hero = create('section', 'boc-learningtools-hero');
        var copy = create('div', 'boc-learningtools-hero-copy');
        var visual = create('div', 'boc-learningtools-visual');
        var badge = create('span', 'boc-learningtools-eyebrow', meta.eyebrow);
        var heading = create('h1', '', title);
        var lead = create('p', '', meta.lead);
        var metrics = create('div', 'boc-learningtools-metrics');

        metrics.appendChild(metric(meta.icon, 'actions', String(stats.actions), 'real controls'));
        metrics.appendChild(metric('reports', 'records', String(stats.records), stats.tables ? stats.tables + ' tables' : 'live page'));
        metrics.appendChild(metric('privacy', 'notices', String(stats.alerts), stats.alerts ? 'needs review' : 'clear'));

        copy.appendChild(badge);
        copy.appendChild(heading);
        copy.appendChild(lead);
        copy.appendChild(metrics);
        visual.innerHTML = heroSvg(meta.icon);
        hero.appendChild(copy);
        hero.appendChild(visual);
        return hero;
    };

    var buildGuide = function(meta, stats, content) {
        var guide = create('aside', 'boc-learningtools-guide');
        var title = create('h2', '', 'Page controls');
        var list = create('div', 'boc-learningtools-guide-list');
        var primaryLinks = Array.prototype.slice.call(content.querySelectorAll('a[href]')).filter(function(link) {
            var text = clean(link.textContent);
            if (!text || link.classList.contains('icons-collapse-expand') || link.classList.contains('collapseexpand')) {
                return false;
            }
            if (link.closest('.filemanager, .fp-navbar, .tox-statusbar, .tox-toolbar, .editor_atto_toolbar')) {
                return false;
            }
            if (/Build with TinyMCE/i.test(text)) {
                return false;
            }
            if (link.getAttribute('href') === '#') {
                return false;
            }
            return true;
        }).slice(0, 4);

        guide.appendChild(title);
        [
            ['tools', stats.forms ? stats.forms + ' form area' + (stats.forms > 1 ? 's' : '') : 'No form required', 'Moodle submission logic is unchanged.'],
            ['reports', stats.tables ? stats.tables + ' data table' + (stats.tables > 1 ? 's' : '') : stats.records + ' records', 'Tables keep sorting, links and actions.'],
            [meta.icon, stats.links + ' live link' + (stats.links === 1 ? '' : 's'), 'Existing destinations are preserved.']
        ].forEach(function(row) {
            var item = create('div', 'boc-learningtools-guide-item');
            var icon = create('span', 'boc-learningtools-guide-icon');
            var copy = create('span', 'boc-learningtools-guide-copy');
            icon.innerHTML = iconSvg(row[0]);
            copy.appendChild(create('strong', '', row[1]));
            copy.appendChild(create('span', '', row[2]));
            item.appendChild(icon);
            item.appendChild(copy);
            list.appendChild(item);
        });

        if (primaryLinks.length) {
            var actions = create('div', 'boc-learningtools-guide-actions');
            primaryLinks.forEach(function(link) {
                var clone = link.cloneNode(true);
                clone.classList.add('boc-learningtools-primary-link');
                actions.appendChild(clone);
            });
            guide.appendChild(actions);
        }

        guide.appendChild(list);
        return guide;
    };

    var decorateForms = function(content) {
        Array.prototype.slice.call(content.querySelectorAll('form')).forEach(function(form) {
            form.classList.add('boc-learningtools-form');
            Array.prototype.slice.call(form.querySelectorAll('.fitem, .form-group, fieldset, .form-inline')).forEach(function(item) {
                item.classList.add('boc-learningtools-form-row');
            });
        });
    };

    var decorateTables = function(content) {
        Array.prototype.slice.call(content.querySelectorAll('table')).forEach(function(table) {
            if (table.closest('.boc-learningtools-table-scroll')) {
                return;
            }
            var wrapper = create('div', 'boc-learningtools-table-scroll');
            table.parentNode.insertBefore(wrapper, table);
            wrapper.appendChild(table);
            table.classList.add('boc-learningtools-table');
        });
    };

    var decorateAlerts = function(content, iconName) {
        Array.prototype.slice.call(content.querySelectorAll('.alert, [role="alert"]')).forEach(function(alert) {
            if (alert.querySelector('.boc-learningtools-alert-icon')) {
                return;
            }
            var icon = create('span', 'boc-learningtools-alert-icon');
            icon.innerHTML = iconSvg(iconName);
            alert.classList.add('boc-learningtools-alert');
            alert.insertBefore(icon, alert.firstChild);
        });
    };

    var decorateLinks = function(content) {
        Array.prototype.slice.call(content.querySelectorAll('a[href]')).forEach(function(link) {
            if (link.closest('.boc-learningtools-guide-actions')) {
                return;
            }
            if (clean(link.textContent)) {
                link.classList.add('boc-learningtools-link');
            }
        });
    };

    var buildWorkspace = function(meta, content, stats) {
        var workspace = create('section', 'boc-learningtools-workspace');
        var header = create('div', 'boc-learningtools-workspace-header');
        var title = create('h2', '', 'Live Moodle content');
        var count = create('span', 'boc-learningtools-count', stats.actions + ' actions');

        header.appendChild(title);
        header.appendChild(count);
        workspace.appendChild(header);
        workspace.appendChild(content);

        if (!clean(content.textContent)) {
            var empty = create('div', 'boc-learningtools-empty');
            var icon = create('span', 'boc-learningtools-empty-icon');
            icon.innerHTML = iconSvg(meta.icon);
            empty.appendChild(icon);
            empty.appendChild(create('strong', '', 'Nothing to display'));
            empty.appendChild(create('span', '', 'Moodle did not return visible content for this page.'));
            content.appendChild(empty);
        }

        return workspace;
    };

    var enhance = function() {
        var body = document.querySelector(pageSelector);
        var main = document.querySelector('#region-main');
        var meta = metaForPage();
        var shell;
        var content;
        var nodes;
        var title;
        var stats;
        var layout;

        if (!body || !main || main.getAttribute('data-boc-learningtools-ready') === '1') {
            return;
        }

        main.setAttribute('data-boc-learningtools-ready', '1');
        body.classList.add('boc-learningtools-page-' + meta.icon);
        nodes = visibleMainChildren(main);
        title = sourceTitle(main, meta);
        content = create('div', 'boc-learningtools-content');
        nodes.forEach(function(node) {
            content.appendChild(node);
        });

        decorateForms(content);
        decorateTables(content);
        decorateAlerts(content, meta.icon);
        decorateLinks(content);
        stats = collectStats(content);

        shell = create('div', 'boc-learningtools-shell');
        layout = create('div', 'boc-learningtools-layout');
        layout.appendChild(buildWorkspace(meta, content, stats));
        layout.appendChild(buildGuide(meta, stats, content));
        shell.appendChild(buildHero(meta, title, stats));
        shell.appendChild(layout);
        main.appendChild(shell);
        document.body.classList.add('boc-learningtools-ready');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhance);
    } else {
        enhance();
    }
})();
