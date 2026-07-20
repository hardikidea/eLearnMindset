// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
// GNU General Public License for more details.

/**
 * Student course workspace enhancements.
 *
 * @module     theme_custom_lms/student_course
 * @copyright  2026 eLearn Mindset
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

const OUTLINE_SELECTOR = '.custom-lms-student-course-index';

/**
 * Keep Moodle's course-index positioning inside the right rail.
 */
const containInitialCourseIndexScroll = () => {
    if (window.location.hash) {
        return;
    }

    const navigation = window.performance?.getEntriesByType('navigation')[0];
    if (navigation && navigation.type !== 'navigate') {
        return;
    }

    let userInteracted = false;
    const markInteraction = () => {
        userInteracted = true;
    };

    ['keydown', 'pointerdown', 'touchstart', 'wheel'].forEach(eventName => {
        window.addEventListener(eventName, markInteraction, {once: true, passive: true});
    });

    const restoreDocumentPosition = () => {
        if (!userInteracted && window.scrollY > 0) {
            window.scrollTo({top: 0, left: 0, behavior: 'auto'});
        }
    };

    window.requestAnimationFrame(restoreDocumentPosition);
    window.setTimeout(restoreDocumentPosition, 100);
    window.setTimeout(restoreDocumentPosition, 300);
};

/**
 * Mark and reveal the current standalone course section.
 */
const markCurrentSection = () => {
    if (!window.location.pathname.endsWith('/course/section.php')) {
        return;
    }

    const sectionId = new URLSearchParams(window.location.search).get('id');
    if (!sectionId || !/^\d+$/.test(sectionId)) {
        return;
    }

    const section = document.querySelector(`${OUTLINE_SELECTOR} [data-for="section"][data-id="${sectionId}"]`);
    const item = section?.querySelector(':scope > [data-for="section_item"]');
    const link = item?.querySelector('[data-for="section_title"]');
    const outline = section?.closest(OUTLINE_SELECTOR);
    if (!section || !item || !outline) {
        return;
    }

    item.classList.add('custom-lms-current-section');
    section.setAttribute('aria-selected', 'true');
    link?.setAttribute('aria-current', 'page');

    const sectionTop = section.offsetTop;
    const centeredTop = sectionTop - ((outline.clientHeight - section.offsetHeight) / 2);
    outline.scrollTop = Math.max(0, centeredTop);
};

/**
 * Initialize the student course workspace.
 */
export const init = () => {
    containInitialCourseIndexScroll();
    markCurrentSection();
    window.setTimeout(markCurrentSection, 150);
    window.setTimeout(markCurrentSection, 500);
};
