<?php
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
 * Settings for Course agenda report
 *
 * @package    report_courseagenda
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

if ($ADMIN->fulltree) {

    // Get custom course fields.
    $fields = [];

    $sql = "SELECT cf.id, cf.name, cf.type FROM {customfield_field} cf " .
            " INNER JOIN {customfield_category} cc ON cc.id = cf.categoryid AND cc.component = 'core_course'" .
            " ORDER BY cf.name";
    $customfields = $DB->get_records_sql($sql);

    foreach ($customfields as $k => $v) {
        $fields[$k] = format_string($v->name, true);
    }

    $fieldswithempty = [0 => ''] + $fields;

    // Course modules.
    $modules = $DB->get_records_menu('modules', ['visible' => 1], 'name', 'name AS id, name');

    foreach ($modules as $k => $v) {
        $modules[$k] = get_string('pluginname', $k);
    }

    // General settings.
    $name = 'report_courseagenda/settingsgeneralheader';
    $heading = get_string('settingsgeneralheader', 'report_courseagenda');
    $setting = new admin_setting_heading($name, $heading, '');
    $settings->add($setting);

    // Default course duration format.
    $name = 'report_courseagenda/coursedurationformat';
    $title = get_string('coursedurationformat', 'report_courseagenda');
    $help = get_string('coursedurationformat_help', 'report_courseagenda');
    $options = [
        \report_courseagenda\local\controller::COURSEDURATION_DAYS => get_string('days'),
        \report_courseagenda\local\controller::COURSEDURATION_WEEKS => get_string('weeks'),
    ];
    $setting = new admin_setting_configselect($name, $title, $help, 0, $options);
    $settings->add($setting);

    // Custom field: academic credits.
    $name = 'report_courseagenda/field_academiccredits';
    $title = get_string('field_academiccredits', 'report_courseagenda');
    $help = get_string('field_academiccredits_help', 'report_courseagenda');
    $setting = new admin_setting_configselect($name, $title, $help, 0, $fieldswithempty);
    $settings->add($setting);

    // Hours by credit.
    $name = 'report_courseagenda/hoursbycredit';
    $title = get_string('hoursbycredit', 'report_courseagenda');
    $help = get_string('hoursbycredit_help', 'report_courseagenda');
    $setting = new admin_setting_configtext($name, $title, $help, 0, PARAM_FLOAT);
    $settings->add($setting);

    // Include section 0.
    $name = 'report_courseagenda/includesection0';
    $title = get_string('includesection0', 'report_courseagenda');
    $help = get_string('includesection0_help', 'report_courseagenda');
    $setting = new admin_setting_configcheckbox($name, $title, $help, 0);
    $settings->add($setting);

    // Modules to exclude.
    $name = 'report_courseagenda/excludemodules';
    $title = get_string('excludemodules', 'report_courseagenda');
    $help = get_string('excludemodules_help', 'report_courseagenda');
    $setting = new admin_setting_configmultiselect($name, $title, $help, [], $modules);
    $settings->add($setting);

    // Default grade to pass.
    $name = 'report_courseagenda/gradetopass';
    $title = get_string('gradetopass', 'report_courseagenda');
    $help = get_string('gradetopass_help', 'report_courseagenda');
    $setting = new admin_setting_configtext($name, $title, $help, 0, PARAM_FLOAT);
    $settings->add($setting);

    // Days to grade.
    $name = 'report_courseagenda/daystograde';
    $title = get_string('daystograde', 'report_courseagenda');
    $help = get_string('daystograde_help', 'report_courseagenda');
    $setting = new admin_setting_configtext($name, $title, $help, 0, PARAM_INT);
    $settings->add($setting);

    // Days to send the activity.
    $name = 'report_courseagenda/daystosendactivity';
    $title = get_string('daystosendactivity', 'report_courseagenda');
    $help = get_string('daystosendactivity_help', 'report_courseagenda');
    $setting = new admin_setting_configtext($name, $title, $help, 0, PARAM_INT);
    $settings->add($setting);

    // Appearance settings.
    $name = 'report_courseagenda/settingsappearanceheader';
    $heading = get_string('settingsappearanceheader', 'report_courseagenda');
    $setting = new admin_setting_heading($name, $heading, '');
    $settings->add($setting);

    // Custom colors in progress bar/circle.
    $name = 'report_courseagenda/progresscolors';
    $title = get_string('progresscolors', 'report_courseagenda');
    $help = get_string('progresscolors_help', 'report_courseagenda');
    $setting = new admin_setting_configtextarea($name, $title, $help, '');
    $settings->add($setting);

    // Custom states configuration.
    $name = 'report_courseagenda/statesoptions';
    $title = get_string('statesoptions', 'report_courseagenda');
    $help = get_string('statesoptions_help', 'report_courseagenda');
    $setting = new admin_setting_configtextarea($name, $title, $help, '');
    $settings->add($setting);
}
