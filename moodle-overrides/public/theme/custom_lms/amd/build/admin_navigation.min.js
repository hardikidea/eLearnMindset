// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Converts Moodle's site-administration secondary tabs into an expandable
 * sidebar tree on the admin search page.
 *
 * @module     theme_custom_lms/admin_navigation
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define([], function() {
    const SELECTORS = {
        body: 'body.custom-lms-admin-page',
        secondaryTabs: '.secondary-navigation .nav-link[href^="#"]',
        desktopTree: '[data-region="admin-section-tree"]',
        mobileTree: '[data-region="admin-mobile-section-tree"]',
        paneRow: '.tab-pane > .container-fluid > .row',
        sectionTitle: '.col-sm-3 h4, h4',
        sectionLinks: '.col-sm-9, .col-md-9, .col-lg-9'
    };

    const DASHBOARD_TARGET = 'dashboard';
    const ICON_KEYS = new Map([
        ['general', 'general'],
        ['users', 'users'],
        ['courses', 'courses'],
        ['grades', 'grades'],
        ['plugins', 'plugins'],
        ['appearance', 'appearance'],
        ['server', 'server'],
        ['reports', 'reports'],
        ['development', 'development']
    ]);

    const CACHE_VERSION = 'v3';
    const CACHE_MAX_AGE = 10 * 60 * 1000;

    /**
     * Normalise visible text from Moodle-generated navigation.
     *
     * @param {String} value Raw value.
     * @returns {String}
     */
    const normaliseText = value => (value || '').replace(/\s+/g, ' ').trim();

    /**
     * Build a safe class suffix from a section label.
     *
     * @param {String} label Section label.
     * @returns {String}
     */
    const getIconKey = label => {
        const key = normaliseText(label).toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '');
        return ICON_KEYS.get(key) || 'default';
    };

    /**
     * Build an element without using HTML string interpolation.
     *
     * @param {String} tag Element tag.
     * @param {String} className Class name.
     * @param {String} text Optional text.
     * @returns {HTMLElement}
     */
    const createElement = (tag, className, text = '') => {
        const element = document.createElement(tag);
        if (className) {
            element.className = className;
        }
        if (text) {
            element.textContent = text;
        }
        return element;
    };

    /**
     * Render the static dashboard entry that sits before Moodle's generated admin groups.
     *
     * @param {HTMLElement} root Render root.
     * @returns {HTMLAnchorElement|null}
     */
    const renderDashboardLink = root => {
        const url = root.getAttribute('data-dashboard-url');
        if (!url) {
            return null;
        }

        const link = createElement('a', 'custom-lms-admin-dashboard-link');
        link.setAttribute('href', url);
        link.setAttribute('data-admin-dashboard-link', '1');
        link.setAttribute('data-admin-label', DASHBOARD_TARGET);

        const icon = createElement('span', 'custom-lms-admin-dashboard-link-icon');
        icon.setAttribute('aria-hidden', 'true');
        const label = createElement('span', 'custom-lms-admin-dashboard-link-label', 'Dashboard');
        link.append(icon, label);

        return link;
    };

    /**
     * Return a tab target id without the hash.
     *
     * @param {HTMLAnchorElement} tab Tab link.
     * @returns {String}
     */
    const getTabTarget = tab => {
        const href = tab.getAttribute('href') || '';
        return href.charAt(0) === '#' ? href.substring(1) : '';
    };

    /**
     * Extract links from an admin-search tab row.
     *
     * @param {HTMLElement} row Admin section row.
     * @returns {Array}
     */
    const collectRowLinks = row => {
        const linkRoot = row.querySelector(SELECTORS.sectionLinks) || row;
        const seen = new Set();

        return Array.from(linkRoot.querySelectorAll('a[href]')).map(link => {
            const label = normaliseText(link.textContent);
            const href = link.href || link.getAttribute('href') || '';
            return {label, href};
        }).filter(item => {
            const key = `${item.label}|${item.href}`;
            if (!item.label || !item.href || item.href.charAt(0) === '#' || seen.has(key)) {
                return false;
            }
            seen.add(key);
            return true;
        });
    };

    /**
     * Extract nested section headings and links from a tab pane.
     *
     * @param {HTMLElement} pane Tab pane.
     * @returns {Array}
     */
    const collectPaneSections = pane => {
        const rows = Array.from(pane.querySelectorAll(SELECTORS.paneRow));
        const sourceRows = rows.length ? rows : [pane];

        return sourceRows.map(row => {
            const titleNode = row.querySelector(SELECTORS.sectionTitle);
            const title = normaliseText(titleNode ? titleNode.textContent : '');
            const links = collectRowLinks(row);
            return {title, links};
        }).filter(section => section.title || section.links.length);
    };

    /**
     * Read Moodle's secondary tabs and matching tab pane links.
     *
     * @returns {Array}
     */
    const collectGroups = (source = document) => {
        const seenTargets = new Set();

        return Array.from(source.querySelectorAll(SELECTORS.secondaryTabs)).map(tab => {
            const target = getTabTarget(tab);
            const pane = target && source.getElementById ? source.getElementById(target) : null;
            const label = normaliseText(tab.textContent);

            if (!target || !pane || !label || seenTargets.has(target)) {
                return null;
            }

            seenTargets.add(target);
            return {
                label,
                target,
                icon: getIconKey(label),
                tab: tab.isConnected && tab.ownerDocument === document ? tab : null,
                sections: collectPaneSections(pane)
            };
        }).filter(Boolean).filter(group => group.sections.length);
    };

    /**
     * Return a stable cache key for this Moodle site.
     *
     * @returns {String}
     */
    const getCacheKey = () => {
        const wwwroot = window.M && M.cfg && M.cfg.wwwroot ? M.cfg.wwwroot : window.location.origin;
        return `theme_custom_lms_admin_navigation_${CACHE_VERSION}_${wwwroot}`;
    };

    /**
     * Strip live DOM references before caching groups.
     *
     * @param {Array} groups Admin section groups.
     * @returns {Array}
     */
    const serialiseGroups = groups => groups.map(group => ({
        label: group.label,
        target: group.target,
        icon: group.icon,
        tab: null,
        sections: group.sections.map(section => ({
            title: section.title,
            links: section.links.map(link => ({
                label: link.label,
                href: link.href
            }))
        }))
    }));

    /**
     * Read cached admin groups if they are still fresh.
     *
     * @returns {Array}
     */
    const readCachedGroups = () => {
        try {
            const cached = window.sessionStorage.getItem(getCacheKey());
            if (!cached) {
                return [];
            }

            const payload = JSON.parse(cached);
            if (!payload || Date.now() - payload.created > CACHE_MAX_AGE || !Array.isArray(payload.groups)) {
                return [];
            }

            return payload.groups;
        } catch (error) {
            return [];
        }
    };

    /**
     * Cache generated admin groups for fast page-to-page navigation.
     *
     * @param {Array} groups Admin section groups.
     */
    const writeCachedGroups = groups => {
        try {
            window.sessionStorage.setItem(getCacheKey(), JSON.stringify({
                created: Date.now(),
                groups: serialiseGroups(groups)
            }));
        } catch (error) {
            // Storage can be disabled; the live menu should still work.
        }
    };

    /**
     * Mark the active sidebar group in every rendered tree.
     *
     * @param {Array} roots Render roots.
     * @param {String} target Active tab target.
     */
    const activateRenderedGroup = (roots, target) => {
        roots.forEach(root => {
            const search = root.querySelector('[data-region="admin-section-search"]');
            const query = search ? search.value : '';

            if (query) {
                filterRenderedTree(root, query, target);
                return;
            }

            root.querySelectorAll('[data-admin-dashboard-link]').forEach(link => {
                const active = target === DASHBOARD_TARGET;
                link.classList.toggle('active', active);
                link.setAttribute('aria-current', active ? 'page' : 'false');
            });

            root.querySelectorAll('[data-admin-section-target]').forEach(group => {
                const active = Boolean(target) && group.getAttribute('data-admin-section-target') === target;
                group.classList.toggle('active', active);
                group.open = active;
                const summary = group.querySelector('summary');
                if (summary) {
                    summary.setAttribute('aria-current', active ? 'page' : 'false');
                }
            });
        });
    };

    /**
     * Pick an icon class for a nested admin link.
     *
     * @param {String} label Link label.
     * @param {String} href Link URL.
     * @returns {String}
     */
    const getLinkIconKey = (label, href) => {
        const value = `${label} ${href}`.toLowerCase();

        if (/user|account|cohort|profile|role|permission|auth/.test(value)) {
            return 'users';
        }
        if (/course|category|enrol|activity|module|assignment|question|h5p/.test(value)) {
            return 'courses';
        }
        if (/grade|badge|competenc|outcome|scale/.test(value)) {
            return 'grades';
        }
        if (/plugin|install|browse|filter|repository|portfolio/.test(value)) {
            return 'plugins';
        }
        if (/report|log|event|comment|monitor|analytics|statistics/.test(value)) {
            return 'reports';
        }
        if (/backup|restore|import/.test(value)) {
            return 'backup';
        }
        if (/security|antivirus|spam|privacy|policy|capabilit/.test(value)) {
            return 'security';
        }
        if (/theme|appearance|logo|calendar|template|html|navigation/.test(value)) {
            return 'appearance';
        }
        if (/language|location|timezone/.test(value)) {
            return 'language';
        }
        if (/payment|pay|gateway/.test(value)) {
            return 'payments';
        }
        if (/server|cache|session|database|task|cron|performance|status/.test(value)) {
            return 'server';
        }
        if (/\bai\b|provider|placement/.test(value)) {
            return 'ai';
        }

        return 'default';
    };

    /**
     * Render one nested admin group.
     *
     * @param {Object} group Group metadata.
     * @param {Array} roots All render roots.
     * @returns {HTMLElement}
     */
    const renderGroup = (group, roots) => {
        const details = createElement('details', 'custom-lms-admin-section-group');
        details.setAttribute('data-admin-section-target', group.target);
        details.setAttribute('data-admin-label', group.label.toLowerCase());
        details.setAttribute('data-admin-search', [
            group.label,
            ...group.sections.map(section => section.title),
            ...group.sections.flatMap(section => section.links.map(link => link.label))
        ].join(' ').toLowerCase());

        const summary = createElement('summary', 'custom-lms-admin-section-summary');
        const icon = createElement('span', `custom-lms-admin-section-icon custom-lms-admin-section-icon-${group.icon}`);
        icon.setAttribute('aria-hidden', 'true');
        const label = createElement('span', 'custom-lms-admin-section-label', group.label);
        const count = group.sections.reduce((total, section) => total + section.links.length, 0);
        const badge = createElement('span', 'custom-lms-admin-section-count', String(count));
        badge.setAttribute('aria-label', `${count} links`);

        summary.append(icon, label, badge);
        summary.addEventListener('click', event => {
            event.preventDefault();
            const isOpen = details.open && details.classList.contains('active');
            activateRenderedGroup(roots, isOpen ? '' : group.target);
            if (!isOpen && group.tab) {
                group.tab.click();
            }
        });

        const list = createElement('div', 'custom-lms-admin-section-list');
        group.sections.forEach(section => {
            const sectionNode = createElement('div', 'custom-lms-admin-section-subgroup');

            if (section.title) {
                sectionNode.append(createElement('strong', 'custom-lms-admin-section-subtitle', section.title));
            }

            section.links.forEach(item => {
                const link = createElement('a',
                    `custom-lms-admin-section-link custom-lms-admin-section-link-${getLinkIconKey(item.label, item.href)}`,
                    item.label
                );
                link.setAttribute('href', item.href);
                link.setAttribute('data-admin-link-search', item.label.toLowerCase());
                sectionNode.append(link);
            });

            sectionNode.setAttribute('data-admin-section-search',
                [section.title, ...section.links.map(item => item.label)].join(' ').toLowerCase()
            );

            list.append(sectionNode);
        });

        details.append(summary, list);
        return details;
    };

    /**
     * Filter one rendered admin tree.
     *
     * @param {HTMLElement} root Render root.
     * @param {String} query Search text.
     * @param {String} fallbackTarget Target to reopen when search is empty.
     */
    const filterRenderedTree = (root, query, fallbackTarget = '') => {
        const searchTerm = normaliseText(query).toLowerCase();
        const groups = Array.from(root.querySelectorAll('[data-admin-section-target]'));
        let firstVisible = null;
        const dashboardLink = root.querySelector('[data-admin-dashboard-link]');
        const dashboardVisible = !searchTerm || DASHBOARD_TARGET.includes(searchTerm);

        if (dashboardLink) {
            dashboardLink.hidden = !dashboardVisible;
            dashboardLink.classList.toggle('active', fallbackTarget === DASHBOARD_TARGET && dashboardVisible);
            dashboardLink.setAttribute('aria-current', fallbackTarget === DASHBOARD_TARGET && dashboardVisible ? 'page' : 'false');
        }

        groups.forEach(group => {
            const groupLabelMatch = (group.getAttribute('data-admin-label') || '').includes(searchTerm);
            let hasVisibleContent = false;

            group.querySelectorAll('[data-admin-section-search]').forEach(section => {
                const sectionMatch = (section.getAttribute('data-admin-section-search') || '').includes(searchTerm);
                let hasVisibleLinks = false;

                section.querySelectorAll('[data-admin-link-search]').forEach(link => {
                    const linkMatch = !searchTerm ||
                        groupLabelMatch ||
                        sectionMatch ||
                        (link.getAttribute('data-admin-link-search') || '').includes(searchTerm);
                    link.hidden = !linkMatch;
                    hasVisibleLinks = hasVisibleLinks || linkMatch;
                });

                const visibleSection = !searchTerm || groupLabelMatch || sectionMatch || hasVisibleLinks;
                section.hidden = !visibleSection;
                hasVisibleContent = hasVisibleContent || visibleSection;
            });

            const visible = !searchTerm ||
                groupLabelMatch ||
                hasVisibleContent ||
                (group.getAttribute('data-admin-search') || '').includes(searchTerm);
            group.hidden = !visible;
            if (visible && !firstVisible) {
                firstVisible = group;
            }
        });

        const openTarget = searchTerm && firstVisible ?
            firstVisible.getAttribute('data-admin-section-target') :
            fallbackTarget;

        groups.forEach(group => {
            const active = Boolean(openTarget) &&
                !group.hidden &&
                group.getAttribute('data-admin-section-target') === openTarget;
            group.open = active;
            group.classList.toggle('active', active);
            const summary = group.querySelector('summary');
            if (summary) {
                summary.setAttribute('aria-current', active ? 'page' : 'false');
            }
        });

        const empty = root.querySelector('[data-region="admin-section-empty"]');
        if (empty) {
            empty.hidden = !searchTerm || Boolean(firstVisible) || dashboardVisible;
        }
    };

    /**
     * Load admin groups from the current page or from the real admin search page.
     *
     * @returns {Promise<Array>}
     */
    const loadGroups = async() => {
        const localGroups = collectGroups(document);
        if (localGroups.length) {
            return localGroups;
        }

        const wwwroot = window.M && M.cfg && M.cfg.wwwroot ? M.cfg.wwwroot : '';
        const response = await fetch(`${wwwroot}/admin/search.php`, {
            credentials: 'same-origin'
        });

        if (!response.ok) {
            return [];
        }

        const html = await response.text();
        const adminDocument = new DOMParser().parseFromString(html, 'text/html');
        return collectGroups(adminDocument);
    };

    /**
     * Find the admin group that owns the current page URL.
     *
     * @param {Array} groups Admin section groups.
     * @returns {String}
     */
    const getCurrentPageTarget = groups => {
        const currentUrl = new URL(window.location.href);

        for (const group of groups) {
            for (const section of group.sections) {
                for (const link of section.links) {
                    const linkUrl = new URL(link.href, window.location.href);
                    if (linkUrl.pathname === currentUrl.pathname && linkUrl.search === currentUrl.search) {
                        return group.target;
                    }
                }
            }
        }

        return '';
    };

    /**
     * Render the full admin section tree into a root element.
     *
     * @param {HTMLElement} root Root node.
     * @param {Array} groups Admin section groups.
     * @param {Array} roots All render roots.
     * @param {Function} getActiveTarget Active target getter.
     */
    const renderTree = (root, groups, roots, getActiveTarget) => {
        root.replaceChildren();
        root.setAttribute('aria-busy', 'false');

        const dashboardLink = renderDashboardLink(root);
        if (dashboardLink) {
            root.append(dashboardLink);
        }

        const searchWrap = createElement('label', 'custom-lms-admin-section-search');
        const searchIcon = createElement('span', 'custom-lms-admin-section-search-icon');
        searchIcon.setAttribute('aria-hidden', 'true');
        const searchInput = createElement('input', 'custom-lms-admin-section-search-input');
        searchInput.setAttribute('type', 'search');
        searchInput.setAttribute('placeholder', 'Search admin menu');
        searchInput.setAttribute('aria-label', 'Search admin menu');
        searchInput.setAttribute('autocomplete', 'off');
        searchInput.setAttribute('data-region', 'admin-section-search');
        searchWrap.append(searchIcon, searchInput);

        root.append(searchWrap);
        root.append(createElement('span', 'custom-lms-admin-section-tree-title', 'Site administration'));
        groups.forEach(group => root.append(renderGroup(group, roots)));
        const empty = createElement('span', 'custom-lms-admin-section-empty', 'No matching menu items');
        empty.hidden = true;
        empty.setAttribute('data-region', 'admin-section-empty');
        root.append(empty);

        searchInput.addEventListener('input', () => filterRenderedTree(root, searchInput.value, getActiveTarget()));
    };

    /**
     * Initialise the enhanced admin navigation.
     */
    const init = () => {
        const body = document.querySelector(SELECTORS.body);
        if (!body) {
            return;
        }

        const desktopTree = document.querySelector(SELECTORS.desktopTree);
        const mobileTree = document.querySelector(SELECTORS.mobileTree);
        const roots = [desktopTree, mobileTree].filter(Boolean);

        if (!roots.length) {
            return;
        }

        let activeTarget = '';
        let rendered = false;
        const setActiveTarget = target => {
            activeTarget = target;
            activateRenderedGroup(roots, target);
        };

        const applyGroups = groups => {
            if (!groups.length) {
                return;
            }

            body.classList.add('custom-lms-admin-nav-enhanced');

            roots.forEach(root => renderTree(root, groups, roots, () => activeTarget));
            rendered = true;

            groups.forEach(group => {
                if (group.tab) {
                    group.tab.addEventListener('click', () => setActiveTarget(group.target));
                }
            });

            const hashTarget = window.location.hash ? window.location.hash.substring(1) : '';
            const activeTab = document.querySelector(`${SELECTORS.secondaryTabs}.active`);
            const activeTabTarget = activeTab ? getTabTarget(activeTab) : '';
            const currentPageTarget = getCurrentPageTarget(groups);
            const dashboardActive = roots.some(root => root.getAttribute('data-dashboard-active') === '1');
            const initialTarget = dashboardActive ?
                DASHBOARD_TARGET :
                (groups.some(group => group.target === hashTarget) ?
                hashTarget :
                (groups.some(group => group.target === activeTabTarget) ?
                    activeTabTarget :
                    (currentPageTarget || groups[0].target)));

            setActiveTarget(initialTarget);
        };

        const cachedGroups = readCachedGroups();
        if (cachedGroups.length) {
            applyGroups(cachedGroups);
        }

        loadGroups().then(groups => {
            if (!groups.length) {
                return;
            }

            writeCachedGroups(groups);
            applyGroups(groups);
        }).catch(() => {
            if (!rendered) {
                roots.forEach(root => root.setAttribute('aria-busy', 'false'));
            }
        });
    };

    return {
        init: init
    };
});
