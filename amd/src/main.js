// This file is part of Moodle - http://moodle.org/
//
// Moodle is free software: you can redistribute it and/or modify
// it under the terms of the GNU General Public License as published by
// the Free Software Foundation, either version 3 of the License, or
// (at your option) any later version.
//
// Moodle is distributed in the hope that it will be useful,
// but WITHOUT ANY WARRANTY; without even the implied warranty of
// MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
// GNU General Public License for more details.
//
// You should have received a copy of the GNU General Public License
// along with Moodle.  If not, see <http://www.gnu.org/licenses/>.

/**
 * General plugin JS functions.
 *
 * @module     report_courseagenda/main
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

import $ from 'jquery';

/**
 * Component initialization.
 *
 * @method init
 *
 */
export const init = () => {

    $('.mag-section_unit').on('click', function(e) {
        clickSectionUnit(e, $(this));
    });

    $('.mag-activity').on('click', function(e) {
        clickActivity(e, $(this));
    });
};

/**
 * Click on a module/section.
 *
 * @method clickSectionUnit
 * @param {Object} e
 * @param {Object} $moduleUnit
 */
function clickSectionUnit(e, $moduleUnit) {
    if (!$moduleUnit.hasClass('mag-unit-bloqued')) {
        return;
    }

    const $conditionsInfo = $moduleUnit.find('.mag-unit_conditions-info');

    if ($moduleUnit.hasClass('active')) {
        $moduleUnit.removeClass('active');
        $conditionsInfo.css('max-height', '0');
    } else {
        closeAllModules();
        $moduleUnit.addClass('active');
        $conditionsInfo.css('max-height', $conditionsInfo[0].scrollHeight + 'px');
    }

    e.preventDefault();
}

/**
 * Click on an activity.
 *
 * @method clickActivity
 * @param {Object} e
 * @param {Object} $activity
 */
function clickActivity(e, $activity) {

    const $info = $activity.find('.mag-agenda_activity-info');
    const $hoverInfo = $activity.find('.mag-activity_border-text');
    const $hoverContent = $activity.find('.mag-activity_content');
    const $icons = $activity.find('.mag-container_icons-show-more');

    if ($activity.hasClass('active')) {
        $activity.removeClass('active');
        $info.css('height', '0');
        $hoverInfo.show();
        $hoverContent.css('padding', '');
        $icons.find('[data-angleop="more"]').show();
        $icons.find('[data-angleop="collapse"]').hide();
    } else {
        closeAllAgendaActivities();
        $activity.addClass('active');
        $info.show();
        $info.css('height', $info[0].scrollHeight + 'px');
        $hoverContent.css('padding', '0');
        $hoverInfo.hide();
        $icons.find('[data-angleop="more"]').hide();
        $icons.find('[data-angleop="collapse"]').show();
    }
}

/**
 * Close all activities.
 *
 * @method closeAllAgendaActivities
 */
function closeAllAgendaActivities() {
    $('.mag-section_activity > div').each(function() {
        var $this = $(this);
        $this.removeClass('no-hover active');
        $this.find('.mag-agenda_activity-info').css('height', '0');
        $this.find('.mag-activity_border-text').show();
        $this.find('.mag-activity_content').css('padding', '');
        $this.find('.mag-container_icons-show-more [data-angleop="more"]').show();
        $this.find('.mag-container_icons-show-more [data-angleop="collapse"]').hide();
    });
}

/**
 * Close all modules.
 */
function closeAllModules() {
    const allModuleUnits = document.querySelectorAll('.mag-unit-bloqued');
    allModuleUnits.forEach(moduleUnit => {
        const conditionsInfo = moduleUnit.querySelector('.mag-unit_conditions-info');
        moduleUnit.classList.remove('active');
        conditionsInfo.style.maxHeight = '0';
    });
}
