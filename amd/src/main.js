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

        var $moduleUnit = $(this);

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

        e.stopPropagation();
        e.preventDefault();
    });
};

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
