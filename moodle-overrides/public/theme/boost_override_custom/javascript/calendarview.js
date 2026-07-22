(function() {
    'use strict';

    var pageSelector = '.theme-boost-override-custom-calendarview';
    var readyAttr = 'data-boc-calendar-ready';
    var iconCounter = 0;
    var scheduled = false;

    var cleanText = function(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
    };

    var textWithoutHidden = function(node) {
        var clone;

        if (!node) {
            return '';
        }

        clone = node.cloneNode(true);
        clone.querySelectorAll('.visually-hidden, .sr-only, .accesshide, .hide').forEach(function(hidden) {
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

    var uniqueId = function(prefix) {
        iconCounter += 1;
        return 'boc-cal-' + iconCounter + '-' + prefix;
    };

    var iconSvg = function(name) {
        var id = uniqueId(name);
        var icons = {
            calendar: '<svg viewBox="0 0 96 96" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="18" x2="76" y1="12" y2="84"><stop stop-color="#60a5fa"/><stop offset=".52" stop-color="#0f6cbf"/><stop offset="1" stop-color="#0891b2"/></linearGradient><filter id="' + id + 's" x="-24%" y="-24%" width="148%" height="148%"><feDropShadow dx="0" dy="14" stdDeviation="9" flood-color="#0f6cbf" flood-opacity=".22"/></filter></defs><rect x="18" y="18" width="60" height="58" rx="16" fill="url(#' + id + 'a)" filter="url(#' + id + 's)"/><path d="M18 34h60" stroke="#dbeafe" stroke-width="6"/><path d="M32 13v16M64 13v16" stroke="#0f172a" stroke-width="7" stroke-linecap="round"/><rect x="30" y="45" width="10" height="10" rx="3" fill="#fff"/><rect x="46" y="45" width="10" height="10" rx="3" fill="#bfdbfe"/><rect x="62" y="45" width="10" height="10" rx="3" fill="#fef3c7"/><rect x="30" y="61" width="10" height="10" rx="3" fill="#ccfbf1"/><rect x="46" y="61" width="10" height="10" rx="3" fill="#fff"/></svg>',
            event: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="12" x2="52" y1="10" y2="54"><stop stop-color="#f97316"/><stop offset="1" stop-color="#f59e0b"/></linearGradient></defs><rect x="12" y="14" width="40" height="38" rx="12" fill="url(#' + id + 'a)"/><path d="M20 28h24M20 38h17" stroke="#fff" stroke-width="4" stroke-linecap="round"/><path d="M24 10v12M40 10v12" stroke="#0f172a" stroke-width="4" stroke-linecap="round"/></svg>',
            course: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="10" x2="54" y1="10" y2="54"><stop stop-color="#22c55e"/><stop offset="1" stop-color="#0891b2"/></linearGradient></defs><path d="M32 9 8 21l24 12 24-12L32 9Z" fill="url(#' + id + 'a)"/><path d="M17 28v14c9 8 21 8 30 0V28L32 36 17 28Z" fill="#67e8f9"/><path d="M52 24v18" stroke="#f59e0b" stroke-width="4" stroke-linecap="round"/><circle cx="52" cy="46" r="4" fill="#f59e0b"/></svg>',
            today: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="12" x2="52" y1="10" y2="54"><stop stop-color="#a78bfa"/><stop offset="1" stop-color="#4f46e5"/></linearGradient></defs><circle cx="32" cy="32" r="24" fill="url(#' + id + 'a)"/><path d="m21 33 7 7 16-18" fill="none" stroke="#fff" stroke-width="6" stroke-linecap="round" stroke-linejoin="round"/></svg>',
            filter: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="12" x2="52" y1="10" y2="54"><stop stop-color="#38bdf8"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs><rect x="12" y="12" width="40" height="40" rx="12" fill="url(#' + id + 'a)"/><path d="M22 24h20M26 32h12M30 40h4" stroke="#fff" stroke-width="5" stroke-linecap="round"/></svg>',
            empty: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="' + id + 'a" x1="11" x2="53" y1="12" y2="52"><stop stop-color="#e0f2fe"/><stop offset="1" stop-color="#cbd5e1"/></linearGradient></defs><rect x="12" y="14" width="40" height="36" rx="10" fill="url(#' + id + 'a)"/><path d="M22 29h20M22 39h12" stroke="#0f6cbf" stroke-width="4" stroke-linecap="round"/><circle cx="48" cy="18" r="7" fill="#22c55e"/></svg>'
        };

        return icons[name] || icons.calendar;
    };

    var iconNode = function(name, className) {
        var node = createElement('span', className || 'boc-calendar-icon');
        node.innerHTML = iconSvg(name);
        return node;
    };

    var metric = function(name, label, value, meta) {
        var card = createElement('article', 'boc-calendar-metric boc-calendar-metric-' + name);

        card.appendChild(iconNode(name, 'boc-calendar-metric-icon'));
        card.appendChild(createElement('span', 'boc-calendar-metric-label', label));
        card.appendChild(createElement('strong', 'boc-calendar-metric-value', value));
        card.appendChild(createElement('small', 'boc-calendar-metric-meta', meta));
        return card;
    };

    var calendarStats = function(calendar) {
        var wrapper = calendar.querySelector('.calendarwrapper');
        var table = calendar.querySelector('.calendarmonth');
        var days = Array.prototype.slice.call(calendar.querySelectorAll('.calendarmonth td[data-region="day"]'));
        var events = Array.prototype.slice.call(calendar.querySelectorAll('[data-region="event-item"]'));
        var visibleEvents = events.filter(function(event) {
            return event.offsetParent !== null && !event.hidden && event.style.display !== 'none';
        });
        var hasevents = days.filter(function(day) {
            return day.classList.contains('hasevent');
        });
        var today = calendar.querySelector('.calendarmonth td.today .day-number');
        var courseFilter = calendar.querySelector('select.cal_courses_flt');
        var selectedCourse = courseFilter && courseFilter.selectedOptions.length ? cleanText(courseFilter.selectedOptions[0].textContent) : 'All courses';
        var courses = courseFilter ? Math.max(courseFilter.options.length - 1, 0) : 0;
        var viewText = textWithoutHidden(calendar.querySelector('[data-active-item-text]')) ||
            (wrapper ? cleanText(wrapper.getAttribute('data-view')) : 'Month');
        var period = textWithoutHidden(calendar.querySelector('.calendar-controls .current')) ||
            cleanText(document.title.replace(/^.*Detailed month view:\s*/i, '').replace(/\s*\|.*$/i, '')) ||
            'Calendar';

        return {
            period: period,
            view: viewText || 'Month',
            days: days.length || (table ? table.querySelectorAll('td').length : 0),
            eventCount: visibleEvents.length,
            eventDays: hasevents.length,
            today: today ? cleanText(today.textContent) : '-',
            course: selectedCourse,
            courseCount: courses
        };
    };

    var buildHero = function(calendar) {
        var stats = calendarStats(calendar);
        var hero = createElement('section', 'boc-calendar-hero');
        var copy = createElement('div', 'boc-calendar-hero-copy');
        var visual = createElement('div', 'boc-calendar-visual');
        var badge = createElement('span', 'boc-calendar-eyebrow', 'Live LMS calendar');
        var title = createElement('h1', '', 'Calendar');
        var lead = createElement('p', '', 'Plan classes, assessments, reminders and course activity from one Moodle-powered schedule.');
        var metrics = createElement('div', 'boc-calendar-metrics');
        var orbit = createElement('div', 'boc-calendar-orbit');
        var panel = createElement('div', 'boc-calendar-visual-panel');

        panel.appendChild(iconNode('calendar', 'boc-calendar-visual-icon'));
        panel.appendChild(createElement('span', 'boc-calendar-visual-month', stats.period));
        panel.appendChild(createElement('strong', 'boc-calendar-visual-today', stats.today));
        orbit.appendChild(createElement('span', 'boc-calendar-orbit-chip boc-calendar-orbit-chip-one', 'Classes'));
        orbit.appendChild(createElement('span', 'boc-calendar-orbit-chip boc-calendar-orbit-chip-two', 'Exams'));
        orbit.appendChild(createElement('span', 'boc-calendar-orbit-chip boc-calendar-orbit-chip-three', 'Reminders'));
        visual.appendChild(orbit);
        visual.appendChild(panel);

        metrics.appendChild(metric('event', 'Visible events', String(stats.eventCount), stats.eventDays + ' active days'));
        metrics.appendChild(metric('course', 'Course scope', stats.courseCount ? String(stats.courseCount) : 'All', stats.course));
        metrics.appendChild(metric('today', 'Today', stats.today, stats.period));

        copy.appendChild(badge);
        copy.appendChild(title);
        copy.appendChild(lead);
        copy.appendChild(metrics);
        hero.appendChild(copy);
        hero.appendChild(visual);
        return hero;
    };

    var buildAside = function(calendar) {
        var stats = calendarStats(calendar);
        var aside = createElement('aside', 'boc-calendar-aside');
        var title = createElement('h2', '', 'Schedule insight');
        var list = createElement('div', 'boc-calendar-insight-list');
        var upcoming = createElement('div', 'boc-calendar-upcoming');
        var eventItems = Array.prototype.slice.call(calendar.querySelectorAll('[data-region="event-item"] a[data-action="view-event"]')).slice(0, 4);

        [
            { icon: 'filter', label: 'Current view', value: stats.view },
            { icon: 'event', label: 'Month events', value: stats.eventCount + ' visible' },
            { icon: 'course', label: 'Courses in filter', value: stats.courseCount ? String(stats.courseCount) : 'All courses' }
        ].forEach(function(item) {
            var row = createElement('div', 'boc-calendar-insight');
            var copy = createElement('div');

            row.appendChild(iconNode(item.icon, 'boc-calendar-insight-icon'));
            copy.appendChild(createElement('span', '', item.label));
            copy.appendChild(createElement('strong', '', item.value));
            row.appendChild(copy);
            list.appendChild(row);
        });

        upcoming.appendChild(createElement('h3', '', 'This month'));
        if (eventItems.length) {
            eventItems.forEach(function(link) {
                var item = sanitizeClone(link.cloneNode(true));
                item.classList.add('boc-calendar-upcoming-link');
                upcoming.appendChild(item);
            });
        } else {
            var empty = createElement('div', 'boc-calendar-empty');
            empty.appendChild(iconNode('empty', 'boc-calendar-empty-icon'));
            empty.appendChild(createElement('strong', '', 'No visible events'));
            empty.appendChild(createElement('span', '', 'Use New event or course filters to plan this month.'));
            upcoming.appendChild(empty);
        }

        aside.appendChild(title);
        aside.appendChild(list);
        aside.appendChild(upcoming);
        return aside;
    };

    var sanitizeClone = function(node) {
        node.querySelectorAll('[id]').forEach(function(item) {
            item.removeAttribute('id');
        });
        return node;
    };

    var setMetric = function(hero, name, value, meta) {
        var valueNode = hero.querySelector('.boc-calendar-metric-' + name + ' .boc-calendar-metric-value');
        var metaNode = hero.querySelector('.boc-calendar-metric-' + name + ' .boc-calendar-metric-meta');

        if (valueNode) {
            valueNode.textContent = value;
        }
        if (metaNode) {
            metaNode.textContent = meta;
        }
    };

    var updateEnhancement = function(calendar) {
        var shell = calendar.closest('.boc-calendar-shell');
        var stats;
        var hero;
        var visualMonth;
        var visualToday;
        var aside;

        decorateCalendar(calendar);
        if (!shell) {
            return;
        }

        stats = calendarStats(calendar);
        hero = shell.querySelector('.boc-calendar-hero');
        aside = shell.querySelector('.boc-calendar-aside');
        visualMonth = hero ? hero.querySelector('.boc-calendar-visual-month') : null;
        visualToday = hero ? hero.querySelector('.boc-calendar-visual-today') : null;

        if (hero) {
            setMetric(hero, 'event', String(stats.eventCount), stats.eventDays + ' active days');
            setMetric(hero, 'course', stats.courseCount ? String(stats.courseCount) : 'All', stats.course);
            setMetric(hero, 'today', stats.today, stats.period);
        }
        if (visualMonth) {
            visualMonth.textContent = stats.period;
        }
        if (visualToday) {
            visualToday.textContent = stats.today;
        }
        if (aside) {
            aside.replaceWith(buildAside(calendar));
        }
    };

    var decorateCalendar = function(calendar) {
        var days = Array.prototype.slice.call(calendar.querySelectorAll('.calendarmonth td[data-region="day"]'));
        var header = calendar.querySelector('.heightcontainer .header');
        var controls = calendar.querySelector('.calendarwrapper .controls');
        var bottom = calendar.querySelector('.bottom');

        calendar.classList.add('boc-calendar-main');
        if (header) {
            header.classList.add('boc-calendar-toolbar');
        }
        if (controls) {
            controls.classList.add('boc-calendar-period');
        }
        if (bottom) {
            bottom.classList.add('boc-calendar-footer');
        }
        days.forEach(function(day, index) {
            day.classList.add('boc-calendar-day');
            day.style.setProperty('--boc-calendar-stagger', String(Math.min(index, 34) * 12) + 'ms');
        });
        calendar.querySelectorAll('[data-region="event-item"]').forEach(function(item) {
            var type = item.getAttribute('data-event-eventtype') || 'other';
            item.classList.add('boc-calendar-event-item', 'boc-calendar-event-' + type);
        });
    };

    var enhance = function() {
        var body = document.querySelector(pageSelector);
        var calendar = document.querySelector('[data-region="calendar"].maincalendar');
        var parent;
        var shell;

        if (!body || !calendar) {
            return;
        }

        if (!calendar.hasAttribute(readyAttr)) {
            parent = calendar.parentNode;
            shell = createElement('div', 'boc-calendar-shell');
            calendar.setAttribute(readyAttr, 'true');
            parent.insertBefore(shell, calendar);
            shell.appendChild(buildHero(calendar));
            shell.appendChild(calendar);
            shell.appendChild(buildAside(calendar));

            if (window.MutationObserver) {
                new MutationObserver(function() {
                    scheduleUpdate(calendar);
                }).observe(calendar, {
                    childList: true,
                    subtree: true
                });
            }

            document.body.addEventListener('calendar-view-updated', function() {
                scheduleUpdate(calendar);
            });
            document.body.addEventListener('click', function(event) {
                if (event.target.closest(pageSelector + ' [data-action], ' + pageSelector + ' .arrow_link')) {
                    window.setTimeout(function() {
                        scheduleUpdate(calendar);
                    }, 500);
                }
            });
            document.body.addEventListener('change', function(event) {
                if (event.target.closest(pageSelector + ' select.cal_courses_flt')) {
                    window.setTimeout(function() {
                        scheduleUpdate(calendar);
                    }, 700);
                }
            });
        }

        updateEnhancement(calendar);
    };

    var scheduleUpdate = function(calendar) {
        if (scheduled) {
            return;
        }
        scheduled = true;
        window.requestAnimationFrame(function() {
            scheduled = false;
            updateEnhancement(calendar);
        });
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhance);
    } else {
        enhance();
    }
})();
