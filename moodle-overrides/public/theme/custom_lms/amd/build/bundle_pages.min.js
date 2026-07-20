// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.

/**
 * Behaviour for the generated Custom LMS role-flex bundle pages.
 *
 * @module     theme_custom_lms/bundle_pages
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
define(['theme_custom_lms/loader'], function() {
    /**
     * Initialise mobile navigation drawers.
     */
    const initDrawer = () => {
        const sidebar = document.querySelector('.sidebar');
        let overlay = document.querySelector('.mobile-overlay');
        let trigger = null;

        if (sidebar && !overlay) {
            sidebar.id = sidebar.id || 'custom-lms-mobile-navigation';
            overlay = document.createElement('div');
            overlay.className = 'mobile-overlay';
            document.body.appendChild(overlay);
        }

        const setDrawer = open => {
            if (!sidebar) {
                return;
            }
            sidebar.classList.toggle('open', open);
            overlay?.classList.toggle('show', open);
            document.body.style.overflow = open ? 'hidden' : '';
            document.querySelectorAll('.menu').forEach(button => {
                button.setAttribute('aria-expanded', String(open));
            });
            if (open) {
                sidebar.querySelector('a')?.focus();
            } else if (trigger) {
                trigger.focus();
            }
        };

        document.querySelectorAll('.menu').forEach(button => {
            button.setAttribute('aria-label', button.getAttribute('aria-label') || 'Toggle navigation');
            button.setAttribute('aria-controls', sidebar?.id || 'custom-lms-mobile-navigation');
            button.setAttribute('aria-expanded', 'false');
            button.addEventListener('click', () => {
                trigger = button;
                setDrawer(!sidebar?.classList.contains('open'));
            });
        });

        overlay?.addEventListener('click', () => setDrawer(false));
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape' && sidebar?.classList.contains('open')) {
                setDrawer(false);
            }
        });
        document.querySelectorAll('.nav a').forEach(link => link.addEventListener('click', () => {
            if (window.matchMedia('(max-width: 760px)').matches) {
                setDrawer(false);
            }
        }));
    };

    /**
     * Initialise safe demo interactions.
     */
    const initDemoControls = () => {
        document.querySelectorAll('[data-demo]').forEach(button => {
            button.addEventListener('click', () => {
                window.alert('Prototype action: connect this control to the matching Moodle workflow before production use.');
            });
        });

        document.querySelectorAll('form[data-drona-demo-form]').forEach(form => {
            form.addEventListener('submit', event => event.preventDefault());
        });
    };

    /**
     * Initialise course overview menus and view switchers.
     */
    const initCourseControls = () => {
        const closeCourseMenus = () => {
            document.querySelectorAll('.course-menu-popover.open').forEach(item => {
                item.classList.remove('open');
                item.parentElement?.querySelector('.course-menu-button')?.setAttribute('aria-expanded', 'false');
            });
        };

        document.querySelectorAll('.course-menu-button').forEach(button => {
            button.addEventListener('click', event => {
                event.stopPropagation();
                const popover = button.parentElement.querySelector('.course-menu-popover');
                document.querySelectorAll('.course-menu-popover.open').forEach(item => {
                    if (item !== popover) {
                        item.classList.remove('open');
                        item.parentElement?.querySelector('.course-menu-button')?.setAttribute('aria-expanded', 'false');
                    }
                });
                const open = popover?.classList.toggle('open');
                button.setAttribute('aria-expanded', String(Boolean(open)));
            });
        });

        document.addEventListener('click', closeCourseMenus);
        document.addEventListener('keydown', event => {
            if (event.key === 'Escape') {
                closeCourseMenus();
            }
        });

        const grid = document.querySelector('[data-course-grid]');
        const viewButtons = [...document.querySelectorAll('[data-course-view]')];
        const supportedViews = ['grid', 'list', 'summary'];
        const storageKey = 'theme_custom_lms_course_view';
        const setCourseView = view => {
            const selectedView = supportedViews.includes(view) ? view : 'grid';
            viewButtons.forEach(item => {
                const active = item.dataset.courseView === selectedView;
                item.classList.toggle('active', active);
                item.setAttribute('aria-pressed', String(active));
            });
            grid?.classList.remove('list', 'summary');
            if (selectedView !== 'grid') {
                grid?.classList.add(selectedView);
            }
        };

        let savedView = 'grid';
        try {
            savedView = window.localStorage.getItem(storageKey) || 'grid';
        } catch (error) {
            savedView = 'grid';
        }
        setCourseView(savedView);

        viewButtons.forEach(button => {
            button.addEventListener('click', () => {
                const view = button.dataset.courseView || 'grid';
                setCourseView(view);
                try {
                    window.localStorage.setItem(storageKey, view);
                } catch (error) {
                    // The selected view still works when persistent browser storage is unavailable.
                }
            });
        });

        const search = document.querySelector('[data-course-search]');
        const empty = document.querySelector('[data-course-search-empty]');
        search?.addEventListener('input', () => {
            const query = search.value.trim().toLocaleLowerCase();
            let visible = 0;
            grid?.querySelectorAll('[data-course-card]').forEach(card => {
                const matches = !query || card.textContent.toLocaleLowerCase().includes(query);
                card.hidden = !matches;
                visible += matches ? 1 : 0;
            });
            if (empty) {
                empty.hidden = visible !== 0;
            }
        });
    };

    /**
     * Initialise administrator course card interactions.
     */
    const initAdminCourseControls = () => {
        document.querySelectorAll('.admin-course-menu-button').forEach(button => {
            button.addEventListener('click', event => {
                event.stopPropagation();
                const menu = button.parentElement.querySelector('.admin-course-menu');
                document.querySelectorAll('.admin-course-menu.open').forEach(item => {
                    if (item !== menu) {
                        item.classList.remove('open');
                    }
                });
                menu?.classList.toggle('open');
                button.setAttribute('aria-expanded', String(menu?.classList.contains('open')));
            });
        });

        document.addEventListener('click', () => {
            document.querySelectorAll('.admin-course-menu.open').forEach(item => item.classList.remove('open'));
            document.querySelectorAll('.admin-course-menu-button[aria-expanded="true"]').forEach(item => {
                item.setAttribute('aria-expanded', 'false');
            });
        });

        const grid = document.querySelector('[data-admin-course-grid]');
        document.querySelectorAll('[data-admin-course-view]').forEach(button => {
            button.addEventListener('click', () => {
                document.querySelectorAll('[data-admin-course-view]').forEach(item => item.classList.remove('active'));
                button.classList.add('active');
                grid?.classList.toggle('list', button.dataset.adminCourseView === 'list');
                try {
                    window.localStorage.setItem('drona-admin-course-view', button.dataset.adminCourseView || 'grid');
                } catch (error) {
                    // Local storage can be unavailable in private browsing modes.
                }
            });
        });

        if (grid) {
            let saved = 'grid';
            try {
                saved = window.localStorage.getItem('drona-admin-course-view') || 'grid';
            } catch (error) {
                saved = 'grid';
            }
            document.querySelector(`[data-admin-course-view="${saved}"]`)?.click();
        }
    };

    /**
     * Initialise public landing and catalogue interactions used in bundle pages.
     */
    const initPublicControls = () => {
        const publicMenu = document.querySelector('.public-menu');
        const publicLinks = document.querySelector('.modern-nav .public-links');
        publicMenu?.addEventListener('click', () => {
            const open = publicLinks?.classList.toggle('open');
            publicMenu.setAttribute('aria-expanded', String(Boolean(open)));
        });

        const revealItems = Array.from(document.querySelectorAll('.reveal'));
        if ('IntersectionObserver' in window) {
            const observer = new IntersectionObserver(entries => entries.forEach(entry => {
                if (entry.isIntersecting) {
                    entry.target.classList.add('is-visible');
                    observer.unobserve(entry.target);
                }
            }), {threshold: 0.12});
            revealItems.forEach(item => observer.observe(item));
        } else {
            revealItems.forEach(item => item.classList.add('is-visible'));
        }

        const backTop = document.querySelector('.back-top');
        backTop?.addEventListener('click', () => window.scrollTo({
            top: 0,
            behavior: window.matchMedia('(prefers-reduced-motion: reduce)').matches ? 'auto' : 'smooth'
        }));
    };

    /**
     * Animate public metric counters without leaking private data.
     */
    const initPublicCounters = () => {
        document.querySelectorAll('[data-counter]').forEach(counter => {
            const target = Number(counter.dataset.counter || 0);
            let started = false;

            const render = value => {
                counter.textContent = Math.round(value).toLocaleString();
            };

            const run = () => {
                if (started) {
                    return;
                }
                started = true;

                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    render(target);
                    return;
                }

                const start = performance.now();
                const tick = now => {
                    const progress = Math.min(1, (now - start) / 1300);
                    const eased = 1 - Math.pow(1 - progress, 3);
                    render(target * eased);
                    if (progress < 1) {
                        requestAnimationFrame(tick);
                    }
                };
                requestAnimationFrame(tick);
            };

            if ('IntersectionObserver' in window) {
                const observer = new IntersectionObserver((entries, activeObserver) => {
                    if (entries[0].isIntersecting) {
                        run();
                        activeObserver.disconnect();
                    }
                }, {threshold: 0.12});
                observer.observe(counter);
            } else {
                run();
            }
        });
    };

    /**
     * Initialise the public role experience carousel.
     */
    const initPublicSlider = () => {
        document.querySelectorAll('[data-slider]').forEach(slider => {
            const slides = Array.from(slider.querySelectorAll('.experience-slide'));
            const dots = Array.from(slider.querySelectorAll('[data-slide]'));
            let index = 0;
            let timer = null;

            if (!slides.length) {
                return;
            }

            const show = next => {
                index = (next + slides.length) % slides.length;
                slides.forEach((slide, slideIndex) => {
                    slide.classList.toggle('active', slideIndex === index);
                });
                dots.forEach((dot, dotIndex) => {
                    dot.classList.toggle('active', dotIndex === index);
                });
            };

            const restart = () => {
                if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                    return;
                }
                window.clearInterval(timer);
                timer = window.setInterval(() => show(index + 1), 7000);
            };

            slider.querySelector('[data-prev]')?.addEventListener('click', () => {
                show(index - 1);
                restart();
            });
            slider.querySelector('[data-next]')?.addEventListener('click', () => {
                show(index + 1);
                restart();
            });
            dots.forEach(dot => {
                dot.addEventListener('click', () => {
                    show(Number(dot.dataset.slide || 0));
                    restart();
                });
            });

            show(0);
            restart();
        });
    };

    /**
     * Initialise the student dashboard highlight carousel.
     */
    const initStudentWelcomeSlider = () => {
        document.querySelectorAll('[data-student-welcome-slider]').forEach(slider => {
            if (slider.dataset.sliderInitialised === 'true') {
                return;
            }

            const slides = Array.from(slider.querySelectorAll('[data-student-welcome-slide]'));
            const dots = Array.from(slider.querySelectorAll('[data-student-welcome-dot]'));
            const reducedMotion = window.matchMedia('(prefers-reduced-motion: reduce)');
            let index = Math.max(0, slides.findIndex(slide => slide.classList.contains('active')));
            let timer = null;
            let pointerStart = null;

            if (slides.length < 2 || slides.length !== dots.length) {
                return;
            }
            slider.dataset.sliderInitialised = 'true';

            const show = next => {
                index = (next + slides.length) % slides.length;
                slides.forEach((slide, slideIndex) => {
                    const active = slideIndex === index;
                    slide.classList.toggle('active', active);
                    slide.setAttribute('aria-hidden', String(!active));
                });
                dots.forEach((dot, dotIndex) => {
                    const active = dotIndex === index;
                    dot.classList.toggle('active', active);
                    dot.setAttribute('aria-current', String(active));
                });
            };

            const stop = () => {
                window.clearInterval(timer);
                timer = null;
            };

            const start = () => {
                stop();
                if (!reducedMotion.matches && !document.hidden
                        && !slider.matches(':hover') && !slider.contains(document.activeElement)) {
                    timer = window.setInterval(() => show(index + 1), 7000);
                }
            };

            dots.forEach(dot => dot.addEventListener('click', () => {
                show(Number(dot.dataset.slide || 0));
                start();
            }));

            slider.addEventListener('keydown', event => {
                if (event.key === 'ArrowLeft' || event.key === 'ArrowRight') {
                    event.preventDefault();
                    show(index + (event.key === 'ArrowRight' ? 1 : -1));
                    dots[index].focus();
                } else if (event.key === 'Home' || event.key === 'End') {
                    event.preventDefault();
                    show(event.key === 'Home' ? 0 : slides.length - 1);
                    dots[index].focus();
                }
            });

            slider.addEventListener('pointerdown', event => {
                pointerStart = event.clientX;
                stop();
            });
            slider.addEventListener('pointerup', event => {
                if (pointerStart !== null && Math.abs(event.clientX - pointerStart) >= 45) {
                    show(index + (event.clientX < pointerStart ? 1 : -1));
                }
                pointerStart = null;
                start();
            });
            slider.addEventListener('pointercancel', () => {
                pointerStart = null;
                start();
            });
            slider.addEventListener('mouseenter', stop);
            slider.addEventListener('mouseleave', start);
            slider.addEventListener('focusin', stop);
            slider.addEventListener('focusout', event => {
                if (!slider.contains(event.relatedTarget)) {
                    start();
                }
            });
            document.addEventListener('visibilitychange', () => document.hidden ? stop() : start());
            reducedMotion.addEventListener?.('change', start);

            show(index);
            start();
        });
    };

    /**
     * Module entry point.
     */
    const init = () => {
        initDrawer();
        initDemoControls();
        initCourseControls();
        initAdminCourseControls();
        initPublicControls();
        initPublicCounters();
        initPublicSlider();
        initStudentWelcomeSlider();
    };

    return {
        init: init
    };
});
