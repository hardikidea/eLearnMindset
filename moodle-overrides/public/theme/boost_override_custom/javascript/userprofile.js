(function() {
    'use strict';

    var bodySelector = 'body.theme-boost-override-custom-userprofile';
    var seed = 0;

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

    var id = function(name) {
        seed += 1;
        return 'boc-up-' + name + '-' + seed;
    };

    var iconSvg = function(name) {
        var iconId = id(name);
        var palette = {
            user: ['#2563eb', '#06b6d4'],
            privacy: ['#14b8a6', '#22c55e'],
            courses: ['#f97316', '#ef4444'],
            misc: ['#7c3aed', '#2563eb'],
            reports: ['#0f6cbf', '#38bdf8'],
            login: ['#f59e0b', '#fb7185'],
            metric: ['#0891b2', '#2563eb']
        };
        var pair = palette[name] || palette.metric;
        var paths = {
            user: '<circle cx="12" cy="8.1" r="3.6" fill="#fff"/><path d="M5.2 19.2c1-4 3.5-6.1 6.8-6.1s5.8 2.1 6.8 6.1" fill="#dbeafe"/>',
            privacy: '<path d="M12 3.4 18.8 6v5.1c0 4.1-2.7 7.4-6.8 9.1-4.1-1.7-6.8-5-6.8-9.1V6L12 3.4Z" fill="#fff"/><path d="m8.8 12.1 2.1 2.1 4.5-5.1" fill="none" stroke="#0f172a" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" opacity=".62"/>',
            courses: '<path d="M4.8 7.4 12 4l7.2 3.4-7.2 3.5-7.2-3.5Z" fill="#fff"/><path d="M6.5 10.5v4.4c3.5 2.2 7.5 2.2 11 0v-4.4M12 11v6.7" fill="none" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>',
            misc: '<path d="M6 6.2h12v11.6H6z" fill="#fff"/><path d="M8.8 9.1h6.4M8.8 12h4.8M8.8 14.9h6.4" stroke="#0f172a" stroke-width="1.25" stroke-linecap="round" opacity=".62"/>',
            reports: '<path d="M6 17h12v2H6zM7.2 12.3h2.6V17H7.2zM10.7 9h2.6v8h-2.6zM14.2 5.8h2.6V17h-2.6z" fill="#fff"/><path d="m7 8.3 3.1-2.1 2.6 1.7L17.4 4" fill="none" stroke="#fff" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/>',
            login: '<rect x="5.2" y="5.2" width="13.6" height="13.6" rx="4" fill="#fff"/><path d="M8.2 9.2h7.6M8.2 12h7.6M8.2 14.8h4.4" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".62"/>',
            metric: '<path d="M5.2 6.7h13.6v10.6H5.2z" fill="#fff"/><path d="M8 10h8M8 13.6h5.5" stroke="#0f172a" stroke-width="1.35" stroke-linecap="round" opacity=".6"/>'
        };

        return '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">' +
            '<defs><linearGradient id="' + iconId + '-g" x1="3" y1="3" x2="21" y2="21" gradientUnits="userSpaceOnUse">' +
            '<stop stop-color="' + pair[0] + '"/><stop offset="1" stop-color="' + pair[1] + '"/></linearGradient></defs>' +
            '<rect x="2.7" y="2.7" width="18.6" height="18.6" rx="5.6" fill="url(#' + iconId + '-g)"/>' +
            '<path d="M6.2 4.6h8.6c2.9 0 5.1 2 5.1 4.7-4.1-1.4-9.5-1.9-15.6-1 .3-2.1 1-3.7 1.9-3.7Z" fill="#fff" opacity=".24"/>' +
            '<g filter="drop-shadow(0 7px 8px rgba(15,23,42,.18))">' + (paths[name] || paths.metric) + '</g></svg>';
    };

    var heroSvg = function() {
        var heroId = id('hero');
        return '<svg aria-hidden="true" viewBox="0 0 360 220" focusable="false">' +
            '<defs><linearGradient id="' + heroId + '-a" x1="43" y1="21" x2="316" y2="197" gradientUnits="userSpaceOnUse"><stop stop-color="#eff6ff"/><stop offset=".52" stop-color="#dbeafe"/><stop offset="1" stop-color="#ccfbf1"/></linearGradient>' +
            '<linearGradient id="' + heroId + '-b" x1="72" y1="43" x2="269" y2="173" gradientUnits="userSpaceOnUse"><stop stop-color="#fff"/><stop offset="1" stop-color="#edf9ff"/></linearGradient>' +
            '<linearGradient id="' + heroId + '-c" x1="40" y1="177" x2="312" y2="177" gradientUnits="userSpaceOnUse"><stop stop-color="#2563eb"/><stop offset=".52" stop-color="#14b8a6"/><stop offset="1" stop-color="#f59e0b"/></linearGradient></defs>' +
            '<path class="boc-userprofile-svg-float" d="M40 162c30 44 113 54 179 30 67-25 109-83 78-126C266 23 184 14 118 41 53 68 9 119 40 162Z" fill="url(#' + heroId + '-a)"/>' +
            '<rect class="boc-userprofile-svg-card" x="76" y="45" width="190" height="112" rx="25" fill="url(#' + heroId + '-b)" stroke="#fff" stroke-width="3"/>' +
            '<circle cx="119" cy="88" r="24" fill="#bfdbfe"/><path d="M100 129c7-19 32-24 49 0" fill="#a7f3d0"/>' +
            '<rect x="164" y="73" width="78" height="14" rx="7" fill="#bfdbfe"/><rect x="164" y="100" width="58" height="12" rx="6" fill="#bae6fd"/><rect x="164" y="124" width="76" height="12" rx="6" fill="#bbf7d0"/>' +
            '<rect x="242" y="29" width="70" height="42" rx="16" fill="#fff" stroke="#dbeafe" stroke-width="2"/><text x="258" y="56" fill="#0f6cbf" font-size="15" font-weight="800">Live</text>' +
            '<rect x="44" y="56" width="70" height="42" rx="16" fill="#fff" stroke="#dbeafe" stroke-width="2"/><text x="60" y="83" fill="#2563eb" font-size="15" font-weight="800">Profile</text>' +
            '<path class="boc-userprofile-svg-line" d="M54 179c54-26 103-24 144-7 36 15 71 8 108-14" fill="none" stroke="url(#' + heroId + '-c)" stroke-width="9" stroke-linecap="round" opacity=".7"/></svg>';
    };

    var keyForTitle = function(title) {
        var value = title.toLowerCase();
        if (value.indexOf('user') !== -1) {
            return 'user';
        }
        if (value.indexOf('privacy') !== -1) {
            return 'privacy';
        }
        if (value.indexOf('course') !== -1) {
            return 'courses';
        }
        if (value.indexOf('report') !== -1) {
            return 'reports';
        }
        if (value.indexOf('login') !== -1) {
            return 'login';
        }
        return 'misc';
    };

    var extractTags = function(text) {
        var tags = [];
        var standard = text.match(/Standard\s+\d+(?:\s*\([^)]+\))?/i);
        var medium = text.match(/(?:Gujarati|English|Hindi)\s+Medium/i);
        var stream = text.match(/\s-\s([A-Z]{2,5})\s-\s/);
        var year = text.match(/20\d{2}-20\d{2}/);

        [standard, medium, stream, year].forEach(function(match) {
            if (match && match[1]) {
                tags.push(match[1]);
            } else if (match && match[0]) {
                tags.push(match[0].replace(/^\s-\s|\s-\s$/g, ''));
            }
        });
        return tags.slice(0, 4);
    };

    var decorateCourseLinks = function(card) {
        Array.prototype.slice.call(card.querySelectorAll('dd > ul > li > a')).forEach(function(link) {
            var title;
            var titleNode;
            var tagsNode;

            if (link.querySelector('.boc-userprofile-course-title')) {
                return;
            }

            title = clean(link.textContent);
            link.setAttribute('title', title);
            link.textContent = '';

            titleNode = create('span', 'boc-userprofile-course-title', title);
            tagsNode = create('span', 'boc-userprofile-course-tags');
            extractTags(title).forEach(function(tag) {
                tagsNode.appendChild(create('span', 'boc-userprofile-course-tag', tag));
            });

            link.appendChild(titleNode);
            if (tagsNode.children.length) {
                link.appendChild(tagsNode);
            }
        });
    };

    var buildMetric = function(label, value, icon) {
        var item = create('article', 'boc-userprofile-metric');
        var iconNode = create('span', 'boc-userprofile-metric-icon');
        var copy = create('span', 'boc-userprofile-metric-copy');

        iconNode.innerHTML = iconSvg(icon);
        copy.appendChild(create('strong', '', value));
        copy.appendChild(create('span', '', label));
        item.appendChild(iconNode);
        item.appendChild(copy);
        return item;
    };

    var getDefinitionValue = function(tree, label, partial) {
        var fields = tree ? Array.prototype.slice.call(tree.querySelectorAll('dt')) : [];
        var target = label.toLowerCase();
        var field = fields.find(function(dt) {
            var value = clean(dt.textContent).toLowerCase();
            return partial ? value.indexOf(target) !== -1 : value === target;
        });

        return clean(field && field.nextElementSibling && field.nextElementSibling.textContent);
    };

    var decorateHeader = function() {
        var header = document.querySelector('#page-header .w-100');
        var tree = document.querySelector('#region-main .profile_tree');
        var courseCount = tree ? tree.querySelectorAll('.boc-userprofile-card-courses dd > ul > li > a').length : 0;
        var actionCount = tree ? tree.querySelectorAll('a').length : 0;
        var timezone = getDefinitionValue(tree, 'timezone', false);
        var lastAccess = getDefinitionValue(tree, 'last access', true);
        var art;
        var metrics;

        if (!header || header.querySelector('.boc-userprofile-hero-art')) {
            return;
        }

        art = create('div', 'boc-userprofile-hero-art');
        art.innerHTML = heroSvg();
        metrics = create('div', 'boc-userprofile-metrics');
        metrics.appendChild(buildMetric('course profiles', courseCount ? String(courseCount) : '0', 'courses'));
        metrics.appendChild(buildMetric('profile links', String(actionCount), 'metric'));
        metrics.appendChild(buildMetric('timezone', timezone || 'Default', 'login'));
        metrics.appendChild(buildMetric('last access', lastAccess ? lastAccess.replace(/\s*\(.+?\)\s*$/, '') : 'Active now', 'reports'));

        header.appendChild(art);
        header.appendChild(metrics);
    };

    var decorateTree = function() {
        var tree = document.querySelector('#region-main .profile_tree');

        if (!tree || tree.classList.contains('boc-userprofile-ready')) {
            return;
        }

        tree.classList.add('boc-userprofile-ready');
        Array.prototype.slice.call(tree.querySelectorAll('.node_category')).forEach(function(card, index) {
            var title = clean(card.querySelector('h3') && card.querySelector('h3').textContent);
            var key = keyForTitle(title);
            var heading = card.querySelector('h3');
            var icon;

            card.classList.add('boc-userprofile-card', 'boc-userprofile-card-' + key);
            card.style.setProperty('--boc-userprofile-delay', (index * 45) + 'ms');
            if (heading && !heading.querySelector('.boc-userprofile-card-icon')) {
                icon = create('span', 'boc-userprofile-card-icon');
                icon.innerHTML = iconSvg(key);
                heading.insertBefore(icon, heading.firstChild);
            }
            if (key === 'courses') {
                decorateCourseLinks(card);
            }
        });
    };

    var init = function() {
        if (!document.querySelector(bodySelector)) {
            return;
        }
        decorateTree();
        decorateHeader();
        document.body.classList.add('boc-userprofile-ready');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
