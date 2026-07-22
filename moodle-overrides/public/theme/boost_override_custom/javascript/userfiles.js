(function() {
    'use strict';

    var pageSelector = 'body.theme-boost-override-custom-userfiles';
    var state = {
        shell: null,
        metrics: {},
        guide: {},
        observer: null,
        scheduled: false
    };
    var svgSeed = 0;

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
        return 'boc-uf-' + name + '-' + svgSeed;
    };

    var iconSvg = function(name) {
        var id = uniqueId(name);
        var gradients = {
            files: ['#2563eb', '#06b6d4'],
            folder: ['#f97316', '#fbbf24'],
            limit: ['#7c3aed', '#22c55e'],
            shield: ['#0ea5e9', '#16a34a'],
            cloud: ['#0f6cbf', '#00a7c8'],
            action: ['#ef4444', '#f97316'],
            guide: ['#14b8a6', '#2563eb']
        };
        var pair = gradients[name] || gradients.files;
        var paths = {
            files: '<path d="M7.6 3.3h6.5l4 4.1v11a2.2 2.2 0 0 1-2.2 2.2H7.6a2.2 2.2 0 0 1-2.2-2.2V5.5a2.2 2.2 0 0 1 2.2-2.2Z" fill="url(#' + id + '-g)"/><path d="M13.8 3.6v4.2h4" fill="none" stroke="#fff" stroke-opacity=".76" stroke-width="1.5"/><path d="M8.7 12.3h6.7M8.7 15.5h5" stroke="#fff" stroke-width="1.55" stroke-linecap="round"/>',
            folder: '<path d="M3.3 7.3a2.2 2.2 0 0 1 2.2-2.2h4l1.9 2h7.1a2.2 2.2 0 0 1 2.2 2.2v1.2H3.3V7.3Z" fill="url(#' + id + '-g)"/><path d="M3.3 9.2h17.4v8.1a2.4 2.4 0 0 1-2.4 2.4H5.7a2.4 2.4 0 0 1-2.4-2.4V9.2Z" fill="url(#' + id + '-g)" opacity=".84"/><path d="M7.1 13.2h9.9" stroke="#fff" stroke-width="1.7" stroke-linecap="round"/>',
            limit: '<rect x="4" y="4" width="16" height="16" rx="4.6" fill="url(#' + id + '-g)"/><path d="M8.1 14.7h7.8M8.1 11.9h7.8M8.1 9.1h4.6" stroke="#fff" stroke-width="1.55" stroke-linecap="round"/><circle cx="16.3" cy="8.9" r="1.55" fill="#fff" opacity=".85"/>',
            shield: '<path d="M12 3.2 19 6v5.4c0 4.2-2.8 7.7-7 9.4-4.2-1.7-7-5.2-7-9.4V6l7-2.8Z" fill="url(#' + id + '-g)"/><path d="m8.9 12.1 2.1 2.1 4.2-5" fill="none" stroke="#fff" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
            cloud: '<path d="M8 18.8h8.6a4 4 0 0 0 .5-8 5.8 5.8 0 0 0-11.1-1.6 4.9 4.9 0 0 0 2 9.6Z" fill="url(#' + id + '-g)"/><path d="M12.2 8.8v6.1M9.8 11.2l2.4-2.4 2.4 2.4" fill="none" stroke="#fff" stroke-width="1.65" stroke-linecap="round" stroke-linejoin="round"/>',
            action: '<rect x="4" y="4" width="16" height="16" rx="5" fill="url(#' + id + '-g)"/><path d="M8.4 12h7.2M12 8.4v7.2" stroke="#fff" stroke-width="1.8" stroke-linecap="round"/>',
            guide: '<path d="M5 4.5h10.8A3.2 3.2 0 0 1 19 7.7v11.8H8.2A3.2 3.2 0 0 1 5 16.3V4.5Z" fill="url(#' + id + '-g)"/><path d="M8.4 8.6h6.8M8.4 11.8h6.8M8.4 15h4.2" stroke="#fff" stroke-width="1.55" stroke-linecap="round"/>'
        };

        return '<svg aria-hidden="true" viewBox="0 0 24 24" focusable="false">' +
            '<defs><linearGradient id="' + id + '-g" x1="4" y1="3" x2="20" y2="21" gradientUnits="userSpaceOnUse">' +
            '<stop stop-color="' + pair[0] + '"/><stop offset="1" stop-color="' + pair[1] + '"/></linearGradient></defs>' +
            '<g filter="drop-shadow(0 8px 10px rgba(15, 23, 42, .18))">' + (paths[name] || paths.files) + '</g></svg>';
    };

    var illustrationSvg = function() {
        var id = uniqueId('hero');
        return '<svg aria-hidden="true" viewBox="0 0 360 260" focusable="false">' +
            '<defs>' +
            '<linearGradient id="' + id + '-panel" x1="65" y1="32" x2="290" y2="218" gradientUnits="userSpaceOnUse"><stop stop-color="#e0f2fe"/><stop offset=".52" stop-color="#bfdbfe"/><stop offset="1" stop-color="#ccfbf1"/></linearGradient>' +
            '<linearGradient id="' + id + '-folder" x1="104" y1="72" x2="264" y2="178" gradientUnits="userSpaceOnUse"><stop stop-color="#2563eb"/><stop offset="1" stop-color="#06b6d4"/></linearGradient>' +
            '<linearGradient id="' + id + '-card" x1="86" y1="48" x2="300" y2="208" gradientUnits="userSpaceOnUse"><stop stop-color="#fff"/><stop offset="1" stop-color="#eff6ff"/></linearGradient>' +
            '<linearGradient id="' + id + '-accent" x1="70" y1="188" x2="312" y2="215" gradientUnits="userSpaceOnUse"><stop stop-color="#f97316"/><stop offset=".5" stop-color="#22c55e"/><stop offset="1" stop-color="#2563eb"/></linearGradient>' +
            '</defs>' +
            '<path class="boc-userfiles-svg-glow" d="M42 187c30 45 105 58 174 40 72-18 119-70 98-124-23-59-98-79-171-55C69 72 7 136 42 187Z" fill="url(#' + id + '-panel)" opacity=".9"/>' +
            '<g class="boc-userfiles-svg-float">' +
            '<rect x="82" y="48" width="210" height="142" rx="26" fill="url(#' + id + '-card)" opacity=".86" stroke="#fff" stroke-width="3"/>' +
            '<path d="M112 101c0-13 10-23 23-23h46l17 18h60c13 0 23 10 23 23v41c0 13-10 23-23 23H135c-13 0-23-10-23-23v-59Z" fill="url(#' + id + '-folder)"/>' +
            '<path d="M112 113h169v47c0 13-10 23-23 23H135c-13 0-23-10-23-23v-47Z" fill="#fff" opacity=".2"/>' +
            '<rect x="135" y="126" width="92" height="12" rx="6" fill="#fff" opacity=".9"/>' +
            '<rect x="135" y="148" width="64" height="10" rx="5" fill="#fff" opacity=".68"/>' +
            '<circle cx="247" cy="138" r="22" fill="#fff" opacity=".92"/>' +
            '<path d="m238 139 7 7 13-17" fill="none" stroke="#16a34a" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/>' +
            '</g>' +
            '<g class="boc-userfiles-svg-orbit" fill="#fff" stroke="#dbeafe" stroke-width="2">' +
            '<rect x="48" y="78" width="62" height="44" rx="16"/><rect x="251" y="47" width="70" height="48" rx="17"/><rect x="218" y="186" width="82" height="44" rx="17"/>' +
            '</g>' +
            '<path class="boc-userfiles-svg-line" d="M58 206c61-26 111-22 150-6 36 15 72 15 106-8" fill="none" stroke="url(#' + id + '-accent)" stroke-width="10" stroke-linecap="round" opacity=".55"/>' +
            '<text x="68" y="106" fill="#2563eb" font-size="16" font-weight="800">Files</text>' +
            '<text x="270" y="77" fill="#0891b2" font-size="15" font-weight="800">100 MB</text>' +
            '<text x="236" y="214" fill="#f97316" font-size="15" font-weight="800">Secure</text>' +
            '</svg>';
    };

    var readLimits = function() {
        var restriction = cleanText(document.querySelector('#userfilesform .fp-restrictions, #userfilesform .filemanager .fp-restrictions') &&
            document.querySelector('#userfilesform .fp-restrictions, #userfilesform .filemanager .fp-restrictions').textContent);
        var matches = restriction.match(/Maximum size for new files:\s*([^,]+),\s*overall limit:\s*([^-\n]+)/i);
        var dragSupported = !/drag and drop not supported/i.test(restriction);

        return {
            restriction: restriction || 'File limits are controlled by Moodle for this user.',
            maxFile: matches ? cleanText(matches[1]) : 'Moodle limit',
            overall: matches ? cleanText(matches[2]) : 'Moodle quota',
            dragDrop: dragSupported ? 'Drag and drop ready' : 'Picker upload available'
        };
    };

    var fileCounts = function() {
        var manager = document.querySelector('#userfilesform .filemanager');
        var files = [];
        var folders = [];

        if (!manager || manager.classList.contains('fm-nofiles')) {
            return { files: 0, folders: 0 };
        }

        Array.prototype.slice.call(manager.querySelectorAll('.fp-content .fp-filename, .fp-content [data-filename]')).forEach(function(node) {
            var name = cleanText(node.getAttribute('data-filename') || node.textContent);
            if (name) {
                files.push(name);
            }
        });

        Array.prototype.slice.call(manager.querySelectorAll('.fp-content .fp-folder, .fp-content [data-filetype="folder"]')).forEach(function(node) {
            var name = cleanText(node.getAttribute('data-filename') || node.textContent);
            if (name) {
                folders.push(name);
            }
        });

        return {
            files: Array.from(new Set(files)).length,
            folders: Array.from(new Set(folders)).length
        };
    };

    var controlLabel = function(button) {
        return cleanText(button.getAttribute('title') || button.getAttribute('aria-label') || button.textContent || button.value);
    };

    var controlSummary = function() {
        var controls = Array.prototype.slice.call(document.querySelectorAll(
            '#userfilesform .filemanager .btn, #userfilesform input[type="submit"]'
        )).map(function(button) {
            return controlLabel(button);
        }).filter(Boolean);

        return Array.from(new Set(controls)).slice(0, 5);
    };

    var metric = function(key, label, value, meta, icon) {
        var item = createNode('article', 'boc-userfiles-metric');
        var iconNode = createNode('span', 'boc-userfiles-metric-icon');
        var copy = createNode('span', 'boc-userfiles-metric-copy');
        var valueNode = createNode('strong', '', value);
        var labelNode = createNode('span', 'boc-userfiles-metric-label', label);
        var metaNode = createNode('span', 'boc-userfiles-metric-meta', meta);

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
        var item = createNode('article', 'boc-userfiles-guide-item');
        var iconNode = createNode('span', 'boc-userfiles-guide-icon');
        var copy = createNode('span', 'boc-userfiles-guide-copy');
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

    var buildShell = function() {
        var body = document.querySelector(pageSelector);
        var region = document.querySelector('#region-main');
        var generalbox = region && region.querySelector('.generalbox');
        var form = document.querySelector('#userfilesform');
        var heading = region && region.querySelector('h2');
        var title = cleanText(heading && heading.textContent) || 'Private files';
        var userName = cleanText(document.querySelector('#page-header h1, .page-header-headings h1, h1') &&
            document.querySelector('#page-header h1, .page-header-headings h1, h1').textContent);
        var shell;
        var hero;
        var heroCopy;
        var metrics;
        var visual;
        var layout;
        var workspace;
        var workspaceHeader;
        var aside;
        var guideList;

        if (!body || !region || !generalbox || !form) {
            return false;
        }

        if (state.shell) {
            updateLiveData();
            return true;
        }

        if (heading) {
            heading.classList.add('boc-userfiles-source-heading');
        }

        shell = createNode('section', 'boc-userfiles-shell');
        hero = createNode('section', 'boc-userfiles-hero');
        heroCopy = createNode('div', 'boc-userfiles-hero-copy');
        metrics = createNode('div', 'boc-userfiles-metrics');
        visual = createNode('div', 'boc-userfiles-visual');
        layout = createNode('div', 'boc-userfiles-layout');
        workspace = createNode('section', 'boc-userfiles-workspace');
        workspaceHeader = createNode('div', 'boc-userfiles-workspace-header');
        aside = createNode('aside', 'boc-userfiles-guide');
        guideList = createNode('div', 'boc-userfiles-guide-list');

        heroCopy.appendChild(createNode('span', 'boc-userfiles-eyebrow', 'Moodle private storage'));
        heroCopy.appendChild(createNode('h1', '', title + ' workspace'));
        heroCopy.appendChild(createNode('p', '', 'Upload, organise and reuse personal learning resources while Moodle keeps permissions, sessions and storage limits in control.'));

        metrics.appendChild(metric('files', 'Visible files', '0', 'Live file manager count', 'files'));
        metrics.appendChild(metric('folders', 'Folders', '0', 'Organised areas', 'folder'));
        metrics.appendChild(metric('maxFile', 'Max file', 'Moodle limit', 'From current permissions', 'limit'));
        metrics.appendChild(metric('overall', 'Library limit', 'Moodle quota', 'User storage policy', 'shield'));
        heroCopy.appendChild(metrics);

        visual.innerHTML = illustrationSvg();
        hero.appendChild(heroCopy);
        hero.appendChild(visual);

        workspaceHeader.appendChild(createNode('div', 'boc-userfiles-workspace-icon'));
        workspaceHeader.querySelector('.boc-userfiles-workspace-icon').innerHTML = iconSvg('cloud');
        workspaceHeader.appendChild(createNode('div', 'boc-userfiles-workspace-title'));
        workspaceHeader.querySelector('.boc-userfiles-workspace-title').appendChild(createNode('h2', '', 'Your file manager'));
        workspaceHeader.querySelector('.boc-userfiles-workspace-title').appendChild(createNode('p', '', 'Use Moodle’s native controls below for upload, folder, download, delete and display modes.'));
        workspace.appendChild(workspaceHeader);
        workspace.appendChild(generalbox);

        aside.appendChild(createNode('span', 'boc-userfiles-guide-kicker', 'Live storage guide'));
        aside.appendChild(createNode('h2', '', 'Ready for course resources'));
        aside.appendChild(createNode('p', 'boc-userfiles-guide-lede', 'This panel reflects the actual private-file settings rendered for the signed-in user.'));
        guideList.appendChild(guideItem('limit', 'limit', 'Upload policy', 'Reading Moodle limits...'));
        guideList.appendChild(guideItem('status', 'files', 'Library status', 'Reading file manager...'));
        guideList.appendChild(guideItem('actions', 'action', 'Available actions', 'Reading controls...'));
        guideList.appendChild(guideItem('drag', 'guide', 'Upload method', 'Reading upload support...'));
        aside.appendChild(guideList);

        layout.appendChild(workspace);
        layout.appendChild(aside);
        shell.appendChild(hero);
        shell.appendChild(layout);
        region.insertBefore(shell, region.firstChild);

        state.shell = shell;
        body.classList.add('boc-userfiles-ready');
        decorateControls();
        updateLiveData();
        observeFileManager();
        return true;
    };

    var decorateControls = function() {
        Array.prototype.slice.call(document.querySelectorAll('#userfilesform .btn, #userfilesform input[type="submit"]')).forEach(function(control) {
            if (control.classList.contains('boc-userfiles-control')) {
                return;
            }
            control.classList.add('boc-userfiles-control');
        });

        Array.prototype.slice.call(document.querySelectorAll('#userfilesform .filemanager .btn')).forEach(function(control) {
            var label = controlLabel(control).toLowerCase();
            if (label === 'add...' || label === 'add' || label === 'create folder') {
                control.classList.add('boc-userfiles-control-primary');
            }
        });

        Array.prototype.slice.call(document.querySelectorAll('#userfilesform .filemanager')).forEach(function(manager) {
            manager.classList.add('boc-userfiles-filemanager');
        });
    };

    var updateLiveData = function() {
        var limits = readLimits();
        var counts = fileCounts();
        var controls = controlSummary();
        var totalItems = counts.files + counts.folders;

        if (!state.metrics.files) {
            return;
        }

        state.metrics.files.value.textContent = String(counts.files);
        state.metrics.files.meta.textContent = totalItems ? 'Updated from visible file list' : 'No uploaded files yet';
        state.metrics.folders.value.textContent = String(counts.folders);
        state.metrics.folders.meta.textContent = counts.folders ? 'Folders in this workspace' : 'Create folders when needed';
        state.metrics.maxFile.value.textContent = limits.maxFile;
        state.metrics.overall.value.textContent = limits.overall;

        if (state.guide.limit) {
            state.guide.limit.textContent = limits.restriction;
        }
        if (state.guide.status) {
            state.guide.status.textContent = totalItems ?
                counts.files + ' files and ' + counts.folders + ' folders visible now.' :
                'Empty workspace. Use Add or drag-and-drop where supported.';
        }
        if (state.guide.actions) {
            state.guide.actions.textContent = controls.length ? controls.join(', ') : 'Moodle actions will appear with the file manager.';
        }
        if (state.guide.drag) {
            state.guide.drag.textContent = limits.dragDrop;
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
            if (!state.shell) {
                buildShell();
                return;
            }
            updateLiveData();
        });
    };

    var observeFileManager = function() {
        var manager = document.querySelector('#userfilesform .filemanager');

        if (!window.MutationObserver || !manager || state.observer) {
            return;
        }

        state.observer = new MutationObserver(scheduleUpdate);
        state.observer.observe(manager, {
            childList: true,
            subtree: true,
            attributes: true,
            attributeFilter: ['class', 'data-filename', 'aria-disabled']
        });
    };

    var init = function() {
        if (!buildShell()) {
            window.setTimeout(buildShell, 300);
        }

        document.addEventListener('click', function(event) {
            if (event.target.closest(pageSelector + ' #userfilesform')) {
                window.setTimeout(scheduleUpdate, 220);
            }
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
