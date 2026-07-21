(function() {
    'use strict';

    var pageSelector = '.theme-boost-override-custom-gradeoverview';
    var readyAttr = 'data-boc-grade-ready';

    var cleanText = function(value) {
        return (value || '').replace(/\s+/g, ' ').trim();
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

    var iconCounter = 0;

    var iconSvg = function(name) {
        var icons = {
            book: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="boc-grade-book-a" x1="8" x2="56" y1="8" y2="56"><stop stop-color="#2563eb"/><stop offset="1" stop-color="#06b6d4"/></linearGradient></defs><rect x="12" y="10" width="34" height="44" rx="8" fill="url(#boc-grade-book-a)"/><path d="M22 20h18M22 29h16M22 38h12" stroke="#fff" stroke-width="4" stroke-linecap="round"/><path d="M46 16h6v36h-6z" fill="#bfdbfe"/></svg>',
            users: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="boc-grade-users-a" x1="10" x2="54" y1="10" y2="54"><stop stop-color="#14b8a6"/><stop offset="1" stop-color="#22c55e"/></linearGradient></defs><rect x="10" y="10" width="44" height="44" rx="12" fill="url(#boc-grade-users-a)"/><circle cx="25" cy="27" r="7" fill="#fff"/><circle cx="42" cy="29" r="6" fill="#ccfbf1"/><path d="M16 46c2.4-7 14.9-8.4 20-2.8 1 1.1 1.3 2.8.5 2.8H16zM34 45c1.5-5.5 10.8-6.4 14.5-2 .9 1 .8 2-.2 2H34z" fill="#ecfeff"/></svg>',
            clipboard: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="boc-grade-clip-a" x1="10" x2="54" y1="8" y2="56"><stop stop-color="#f97316"/><stop offset="1" stop-color="#ef4444"/></linearGradient></defs><rect x="14" y="12" width="36" height="44" rx="10" fill="url(#boc-grade-clip-a)"/><rect x="24" y="8" width="16" height="10" rx="4" fill="#fed7aa"/><path d="M23 28h18M23 37h14M23 46h10" stroke="#fff" stroke-width="4" stroke-linecap="round"/></svg>',
            chart: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="boc-grade-chart-a" x1="10" x2="54" y1="10" y2="54"><stop stop-color="#8b5cf6"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs><rect x="10" y="10" width="44" height="44" rx="12" fill="url(#boc-grade-chart-a)"/><path d="M20 42l10-11 8 7 10-17" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round" stroke-linejoin="round"/><path d="M44 21h4v4" fill="none" stroke="#fff" stroke-width="4" stroke-linecap="round"/></svg>',
            cap: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="boc-grade-cap-a" x1="8" x2="56" y1="12" y2="52"><stop stop-color="#3b82f6"/><stop offset="1" stop-color="#1d4ed8"/></linearGradient></defs><path d="M32 10 6 24l26 14 26-14L32 10z" fill="url(#boc-grade-cap-a)"/><path d="M17 32v11c9 8 21 8 30 0V32L32 40 17 32z" fill="#60a5fa"/><path d="M52 27v16" stroke="#f59e0b" stroke-width="4" stroke-linecap="round"/><circle cx="52" cy="47" r="4" fill="#f59e0b"/></svg>',
            calendar: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="boc-grade-cal-a" x1="10" x2="54" y1="10" y2="54"><stop stop-color="#f59e0b"/><stop offset="1" stop-color="#fb7185"/></linearGradient></defs><rect x="10" y="14" width="44" height="40" rx="10" fill="url(#boc-grade-cal-a)"/><path d="M10 26h44" stroke="#fff" stroke-width="4"/><path d="M22 10v10M42 10v10" stroke="#0f172a" stroke-width="4" stroke-linecap="round"/><rect x="20" y="34" width="8" height="7" rx="2" fill="#fff"/><rect x="36" y="34" width="8" height="7" rx="2" fill="#fff"/></svg>',
            trophy: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="boc-grade-trophy-a" x1="12" x2="52" y1="8" y2="56"><stop stop-color="#facc15"/><stop offset="1" stop-color="#f97316"/></linearGradient></defs><path d="M20 12h24v14c0 9-5 16-12 16S20 35 20 26V12z" fill="url(#boc-grade-trophy-a)"/><path d="M20 18H10c0 9 4 15 12 16M44 18h10c0 9-4 15-12 16" fill="none" stroke="#f59e0b" stroke-width="5" stroke-linecap="round"/><path d="M32 42v8M22 54h20" stroke="#92400e" stroke-width="5" stroke-linecap="round"/></svg>',
            inbox: '<svg viewBox="0 0 64 64" aria-hidden="true"><defs><linearGradient id="boc-grade-inbox-a" x1="10" x2="54" y1="10" y2="54"><stop stop-color="#38bdf8"/><stop offset="1" stop-color="#2563eb"/></linearGradient></defs><path d="M12 24h40l-6 28H18L12 24z" fill="url(#boc-grade-inbox-a)"/><path d="M20 18h24l5 18H39c-1 5-13 5-14 0H15l5-18z" fill="#dbeafe"/><circle cx="47" cy="18" r="8" fill="#ef4444"/><text x="47" y="21" text-anchor="middle" font-size="9" font-family="Arial" fill="#fff" font-weight="700">!</text></svg>',
            search: '<svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2.5"/><path d="m20 20-3.5-3.5" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"/></svg>',
            external: '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M8 8h8v8M16 8 7 17" fill="none" stroke="currentColor" stroke-width="2.4" stroke-linecap="round" stroke-linejoin="round"/></svg>'
        };

        iconCounter += 1;
        return (icons[name] || icons.book).replace(/boc-grade-/g, 'boc-grade-' + iconCounter + '-');
    };

    var iconNode = function(name, className) {
        var node = createElement('span', className || 'boc-grade-icon');
        node.innerHTML = iconSvg(name);
        return node;
    };

    var parseCourseId = function(url) {
        try {
            return new URL(url, window.location.href).searchParams.get('id') || '';
        } catch (exception) {
            return '';
        }
    };

    var parseDetails = function(title) {
        var parts = cleanText(title).split(' - ').map(cleanText).filter(Boolean);
        var details = {
            board: '',
            medium: '',
            standard: '',
            stream: '',
            subject: '',
            year: ''
        };

        parts.forEach(function(part) {
            if (!details.medium && /medium/i.test(part)) {
                details.medium = part;
            } else if (!details.standard && /(standard|nursery|preschool|lkg|ukg|year\s*\d|semester|sem\s*\d)/i.test(part)) {
                details.standard = part;
            } else if (!details.year && /\b20\d{2}\s*[-/]\s*20\d{2}\b|\b20\d{2}\b/.test(part)) {
                details.year = part;
            } else if (!details.board && /board/i.test(part)) {
                details.board = part;
            }
        });

        if (parts.length > 4) {
            details.stream = parts[4];
        }
        if (parts.length > 5) {
            details.subject = parts.slice(5).filter(function(part) {
                return part !== details.year;
            }).join(' - ');
        }
        return details;
    };

    var standardRank = function(value) {
        var text = (value || '').toLowerCase();
        var match = text.match(/\d+/);

        if (/nursery/.test(text)) {
            return -4;
        }
        if (/preschool/.test(text)) {
            return -3;
        }
        if (/\blkg\b/.test(text)) {
            return -2;
        }
        if (/\bukg\b/.test(text)) {
            return -1;
        }
        return match ? parseInt(match[0], 10) : 999;
    };

    var toneForCourse = function(course) {
        var value = (course.title + ' ' + course.details.subject + ' ' + course.details.stream).toLowerCase();

        if (/physical|sports|health|medical|nursing/.test(value)) {
            return {name: 'health', icon: 'trophy'};
        }
        if (/science|math|computer|technology|engineering|polytechnic|data|ai|coding/.test(value)) {
            return {name: 'science', icon: 'chart'};
        }
        if (/commerce|finance|business|management|account|marketing/.test(value)) {
            return {name: 'commerce', icon: 'clipboard'};
        }
        if (/law|legal/.test(value)) {
            return {name: 'law', icon: 'book'};
        }
        return {name: 'school', icon: 'cap'};
    };

    var uniqueValues = function(items, getter) {
        var seen = {};
        var values = [];

        items.forEach(function(item) {
            var value = cleanText(getter(item));
            var key = value.toLowerCase();

            if (value && !seen[key]) {
                seen[key] = true;
                values.push(value);
            }
        });
        return values;
    };

    var findReportTable = function(region) {
        var overview = region.querySelector('#overview-grade');
        var tables;

        if (overview) {
            return overview;
        }

        tables = Array.prototype.slice.call(region.querySelectorAll('table.generaltable, table'));
        return tables.find(function(table) {
            return table.querySelector('a[href*="/grade/report/"], a[href*="/course/user.php"], a[href*="/course/view.php"]');
        }) || null;
    };

    var findReportHeading = function(region, table) {
        var headings = Array.prototype.slice.call(region.querySelectorAll('h1, h2, h3, h4'));
        var target = headings.reverse().find(function(heading) {
            return heading.compareDocumentPosition(table) & Node.DOCUMENT_POSITION_FOLLOWING;
        });

        return target || null;
    };

    var reportMode = function(table, heading) {
        var headingText = cleanText(heading ? heading.textContent : '').toLowerCase();
        var headers = Array.prototype.slice.call(table.querySelectorAll('th')).map(function(cell) {
            return cleanText(cell.textContent).toLowerCase();
        }).join(' ');

        if (/teaching/.test(headingText) || (headers === 'course name' && table.querySelectorAll('th').length <= 1)) {
            return 'teaching';
        }
        return 'grades';
    };

    var getUserName = function(mode, heading) {
        var pageHeading = document.querySelector('.page-header-headings h1, #page-header h1, h1');
        var userMenu = document.querySelector('.usermenu .usertext, [data-region="usermenu"] .usertext');
        var value = cleanText(pageHeading ? pageHeading.textContent : '');

        value = value.replace(/^grades\s*[-:]\s*/i, '')
            .replace(/^grade overview\s*[-:]\s*/i, '')
            .replace(/^courses i am (teaching|taking)$/i, '');

        if (!value || /^courses/i.test(value)) {
            value = cleanText(userMenu ? userMenu.textContent : '');
        }
        if (!value && heading) {
            value = cleanText(heading.textContent).replace(/^courses i am (teaching|taking)$/i, '');
        }
        return value || (mode === 'teaching' ? 'Teacher' : 'Learner');
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

    var findMessageAction = function() {
        return Array.prototype.slice.call(document.querySelectorAll('a, button')).find(function(action) {
            var text = cleanText(action.textContent).toLowerCase();
            var href = action.getAttribute('href') || '';
            return text === 'message' || href.indexOf('/message/') !== -1;
        }) || null;
    };

    var extractCourses = function(table) {
        var rows = Array.prototype.slice.call(table.querySelectorAll('tbody tr'));
        if (!rows.length) {
            rows = Array.prototype.slice.call(table.querySelectorAll('tr')).slice(1);
        }

        var courses = rows.map(function(row, index) {
            var cells = Array.prototype.slice.call(row.children).filter(function(cell) {
                return /^(td|th)$/i.test(cell.tagName);
            });
            var link = row.querySelector('a[href]');
            var title = textWithoutHidden(link) || textWithoutHidden(cells[0]) || 'Course';
            var grade = cells[1] ? textWithoutHidden(cells[1]) : '';
            var rank = cells[2] ? textWithoutHidden(cells[2]) : '';
            var href = link ? link.href : '';
            var courseId = parseCourseId(href);
            var wwwroot = window.M && M.cfg && M.cfg.wwwroot ? M.cfg.wwwroot : window.location.origin;
            var details = parseDetails(title);
            var course = {
                index: index,
                row: row,
                link: link,
                title: title,
                grade: grade,
                rank: rank,
                href: href,
                courseId: courseId,
                courseUrl: courseId ? (wwwroot + '/course/view.php?id=' + encodeURIComponent(courseId)) : href,
                details: details
            };

            course.tone = toneForCourse(course);
            course.searchText = cleanText([
                title,
                grade,
                rank,
                details.board,
                details.medium,
                details.standard,
                details.stream,
                details.subject,
                details.year
            ].join(' ')).toLowerCase();
            return course;
        }).filter(function(course) {
            return course.title && course.href;
        });

        courses.forEach(function(course, index) {
            course.index = index;
        });
        return courses;
    };

    var addOptions = function(select, values, allLabel) {
        var option = document.createElement('option');
        option.value = '';
        option.textContent = allLabel;
        select.appendChild(option);

        values.forEach(function(value) {
            option = document.createElement('option');
            option.value = value;
            option.textContent = value;
            select.appendChild(option);
        });
    };

    var makeMetric = function(icon, label, value, helper) {
        var card = createElement('article', 'boc-grade-metric');
        var body = createElement('span', 'boc-grade-metric-body');
        var labelNode = createElement('span', 'boc-grade-metric-label', label);
        var valueNode = createElement('strong', '', String(value));
        var helperNode = createElement('em', '', helper);

        card.appendChild(iconNode(icon, 'boc-grade-metric-icon'));
        body.appendChild(labelNode);
        body.appendChild(valueNode);
        body.appendChild(helperNode);
        card.appendChild(body);
        return card;
    };

    var makeWidget = function(icon, title, value, text, tone) {
        var card = createElement('article', 'boc-grade-widget');
        var content = createElement('span', 'boc-grade-widget-content');
        var titleNode = createElement('strong', '', title);
        var textNode = createElement('span', '', text);
        var valueNode = createElement('b', '', value);

        card.setAttribute('data-tone', tone || 'blue');
        card.appendChild(iconNode(icon, 'boc-grade-widget-icon'));
        content.appendChild(titleNode);
        content.appendChild(valueNode);
        content.appendChild(textNode);
        card.appendChild(content);
        return card;
    };

    var makeChip = function(label, type) {
        var chip = createElement('span', 'boc-grade-chip');
        chip.setAttribute('data-chip', type || 'default');
        chip.textContent = label;
        return chip;
    };

    var makeCourseCard = function(course, mode) {
        var card = createElement('article', 'boc-grade-course-card');
        var icon = iconNode(course.tone.icon, 'boc-grade-course-icon');
        var main = createElement('div', 'boc-grade-course-main');
        var title = createElement('a', 'boc-grade-course-title', course.title);
        var chips = createElement('div', 'boc-grade-course-chips');
        var actions = createElement('div', 'boc-grade-course-actions');
        var gradeMeta;
        var viewGrades = createElement('a', 'boc-grade-btn boc-grade-btn-outline', 'View grades');
        var openCourse = createElement('a', 'boc-grade-btn boc-grade-btn-primary', 'Open course');

        card.setAttribute('data-tone', course.tone.name);
        card.setAttribute('data-index', String(course.index));
        title.href = course.href;

        [
            [course.details.medium, 'medium'],
            [course.details.standard, 'standard'],
            [course.details.stream, 'stream'],
            [course.details.subject, 'subject'],
            [course.details.year, 'year']
        ].forEach(function(item) {
            if (item[0]) {
                chips.appendChild(makeChip(item[0], item[1]));
            }
        });

        if (mode === 'grades' && (course.grade || course.rank)) {
            gradeMeta = createElement('div', 'boc-grade-course-grade');
            if (course.grade) {
                gradeMeta.appendChild(makeChip('Grade: ' + course.grade, 'grade'));
            }
            if (course.rank) {
                gradeMeta.appendChild(makeChip('Rank: ' + course.rank, 'rank'));
            }
            main.appendChild(gradeMeta);
        }

        viewGrades.href = course.href;
        viewGrades.insertAdjacentHTML('afterbegin', iconSvg('book'));
        openCourse.href = course.courseUrl;
        openCourse.insertAdjacentHTML('beforeend', iconSvg('external'));

        actions.appendChild(viewGrades);
        actions.appendChild(openCourse);
        main.appendChild(title);
        main.appendChild(chips);
        card.appendChild(icon);
        card.appendChild(main);
        card.appendChild(actions);
        return card;
    };

    var buildShell = function(region, table, heading, courses, mode) {
        var name = getUserName(mode, heading);
        var standards = uniqueValues(courses, function(course) {
            return course.details.standard;
        }).sort(function(a, b) {
            return standardRank(a) - standardRank(b) || a.localeCompare(b);
        });
        var mediums = uniqueValues(courses, function(course) {
            return course.details.medium;
        }).sort();
        var years = uniqueValues(courses, function(course) {
            return course.details.year;
        }).sort();
        var shell = createElement('section', 'boc-grade-shell');
        var hero = createElement('section', 'boc-grade-hero');
        var profile = createElement('div', 'boc-grade-profile');
        var avatar = createElement('span', 'boc-grade-avatar', initials(name));
        var profileText = createElement('div', 'boc-grade-profile-copy');
        var nameNode = createElement('h2', '', name);
        var role = createElement('span', 'boc-grade-role', mode === 'teaching' ? 'Teacher grade workspace' : 'Grade overview');
        var message = findMessageAction();
        var messageClone;
        var metrics = createElement('div', 'boc-grade-metrics');
        var widgets = createElement('section', 'boc-grade-widgets');
        var courseSection = createElement('section', 'boc-grade-course-section');
        var courseHead = createElement('div', 'boc-grade-course-head');
        var courseTitleWrap = createElement('div', 'boc-grade-section-title');
        var courseTitle = createElement('h2', '', mode === 'teaching' ? 'Courses I am teaching' : 'Course grade overview');
        var courseSub = createElement('p', '', courses.length + ' real Moodle course' + (courses.length === 1 ? '' : 's') + ' found from this report.');
        var toolbar = createElement('div', 'boc-grade-toolbar');
        var searchWrap = createElement('label', 'boc-grade-search');
        var search = document.createElement('input');
        var standard = document.createElement('select');
        var medium = document.createElement('select');
        var sort = document.createElement('select');
        var resultText = createElement('span', 'boc-grade-result-count');
        var list = createElement('div', 'boc-grade-course-list');
        var empty = createElement('div', 'boc-grade-empty', 'No courses match the selected filters.');
        var cards = courses.map(function(course) {
            return makeCourseCard(course, mode);
        });
        var state = {
            search: '',
            standard: '',
            medium: '',
            sort: 'standard'
        };

        profile.appendChild(avatar);
        profileText.appendChild(role);
        profileText.appendChild(nameNode);
        if (message && message.href) {
            messageClone = message.cloneNode(true);
            messageClone.className = 'boc-grade-message';
            messageClone.removeAttribute('id');
            profileText.appendChild(messageClone);
        }
        profile.appendChild(profileText);

        metrics.appendChild(makeMetric('book', mode === 'teaching' ? 'Courses teaching' : 'Courses listed', courses.length, 'from Moodle report'));
        metrics.appendChild(makeMetric('cap', 'Standards', standards.length || '-', 'detected from course names'));
        metrics.appendChild(makeMetric('users', 'Mediums', mediums.length || '-', 'active learning mediums'));
        metrics.appendChild(makeMetric('calendar', 'Academic years', years.length || '-', years.join(', ') || 'available report data'));
        hero.appendChild(profile);
        hero.appendChild(metrics);

        widgets.appendChild(makeWidget('clipboard', 'Gradebook readiness', courses.length + ' links', 'Grade report links are available from Moodle permissions.', 'green'));
        widgets.appendChild(makeWidget('calendar', 'Assessment timeline', years.length || '0', 'Academic year values detected from course names.', 'orange'));
        widgets.appendChild(makeWidget('chart', 'Course coverage', standards.length || '0', 'Standards available for filtering and review.', 'blue'));
        widgets.appendChild(makeWidget('inbox', 'Review workspace', courses.length, 'Courses visible for this signed-in user.', 'violet'));

        courseTitleWrap.appendChild(iconNode('cap', 'boc-grade-section-icon'));
        courseTitleWrap.appendChild(courseTitle);
        courseTitleWrap.appendChild(courseSub);
        courseHead.appendChild(courseTitleWrap);

        search.type = 'search';
        search.className = 'boc-grade-filter-search';
        search.placeholder = 'Search course...';
        search.setAttribute('aria-label', 'Search courses');
        searchWrap.appendChild(iconNode('search', 'boc-grade-search-icon'));
        searchWrap.appendChild(search);
        standard.className = 'boc-grade-standard-filter';
        standard.setAttribute('aria-label', 'Filter by standard');
        medium.className = 'boc-grade-medium-filter';
        medium.setAttribute('aria-label', 'Filter by medium');
        sort.className = 'boc-grade-sort-filter';
        sort.setAttribute('aria-label', 'Sort courses');
        addOptions(standard, standards, 'All Standards');
        addOptions(medium, mediums, 'All Mediums');
        [
            ['standard', 'Sort: Standard'],
            ['az', 'Sort: Course Name (A-Z)'],
            ['za', 'Sort: Course Name (Z-A)']
        ].forEach(function(item) {
            var option = document.createElement('option');
            option.value = item[0];
            option.textContent = item[1];
            sort.appendChild(option);
        });
        toolbar.appendChild(searchWrap);
        toolbar.appendChild(standard);
        toolbar.appendChild(medium);
        toolbar.appendChild(sort);
        courseHead.appendChild(toolbar);
        courseSection.appendChild(courseHead);
        cards.forEach(function(card) {
            list.appendChild(card);
        });
        courseSection.appendChild(resultText);
        courseSection.appendChild(list);
        courseSection.appendChild(empty);

        var applyFilters = function() {
            var visible = courses.filter(function(course) {
                var matchesSearch = !state.search || course.searchText.indexOf(state.search) !== -1;
                var matchesStandard = !state.standard || course.details.standard === state.standard;
                var matchesMedium = !state.medium || course.details.medium === state.medium;

                return matchesSearch && matchesStandard && matchesMedium;
            });

            visible.sort(function(a, b) {
                if (state.sort === 'za') {
                    return b.title.localeCompare(a.title);
                }
                if (state.sort === 'az') {
                    return a.title.localeCompare(b.title);
                }
                return standardRank(a.details.standard) - standardRank(b.details.standard) || a.title.localeCompare(b.title);
            });

            cards.forEach(function(card) {
                card.hidden = true;
            });
            visible.forEach(function(course) {
                var card = cards[course.index];
                if (card) {
                    card.hidden = false;
                    list.appendChild(card);
                }
            });
            empty.hidden = visible.length > 0;
            resultText.textContent = visible.length + ' of ' + courses.length + ' courses shown';
        };

        search.addEventListener('input', function() {
            state.search = search.value.trim().toLowerCase();
            applyFilters();
        });
        standard.addEventListener('change', function() {
            state.standard = standard.value;
            applyFilters();
        });
        medium.addEventListener('change', function() {
            state.medium = medium.value;
            applyFilters();
        });
        sort.addEventListener('change', function() {
            state.sort = sort.value;
            applyFilters();
        });

        shell.appendChild(hero);
        shell.appendChild(widgets);
        shell.appendChild(courseSection);
        applyFilters();
        return shell;
    };

    var hideOriginalReport = function(table, heading) {
        var previous = table.previousSibling;

        table.classList.add('boc-grade-original-hidden');
        if (heading) {
            heading.classList.add('boc-grade-original-hidden');
        }
        while (previous && previous.nodeType === Node.TEXT_NODE && !cleanText(previous.textContent)) {
            previous = previous.previousSibling;
        }
        if (previous && previous.nodeName === 'BR') {
            previous.classList.add('boc-grade-original-hidden');
        }
    };

    var enhance = function() {
        var page = document.querySelector(pageSelector);
        var region = page ? page.querySelector('#region-main') : null;
        var table;
        var heading;
        var courses;
        var mode;
        var shell;

        if (!region || region.getAttribute(readyAttr) === '1') {
            return;
        }

        table = findReportTable(region);
        if (!table) {
            return;
        }

        heading = findReportHeading(region, table);
        courses = extractCourses(table);
        if (!courses.length) {
            return;
        }

        mode = reportMode(table, heading);
        shell = buildShell(region, table, heading, courses, mode);
        table.parentNode.insertBefore(shell, heading && heading.parentNode === table.parentNode ? heading : table);
        hideOriginalReport(table, heading);
        region.setAttribute(readyAttr, '1');
    };

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', enhance);
    } else {
        enhance();
    }

    if (window.MutationObserver) {
        new MutationObserver(function() {
            window.requestAnimationFrame(enhance);
        }).observe(document.body, {
            childList: true,
            subtree: true
        });
    }
})();
