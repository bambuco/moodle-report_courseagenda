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
 * English language pack for Course agenda
 *
 * @package    report_courseagenda
 * @category   string
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$string['allactivities'] = 'All activities';
$string['courseagenda:view'] = 'View Course agenda report';
$string['courseagenda:viewall'] = 'View Course agenda report for all users';
$string['coursedurationformat'] = 'Course duration format';
$string['coursedurationformat_help'] = 'Select the format to display the course duration.';
$string['daystograde'] = 'Days to grade';
$string['daystograde_help'] = 'The number of days that a teacher has to grade a student\'s activity.';
$string['daystosendactivity'] = 'Days to send activity';
$string['daystosendactivity_help'] = 'The number of days that a student has to send an activity.';
$string['excludemodules'] = 'Exclude modules';
$string['excludemodules_help'] = 'Select the modules to exclude from the course agenda report.';
$string['field_academiccredits'] = 'Academic credits';
$string['field_academiccredits_help'] = 'A custom field to use for the academic credits in the course.';
$string['fullstate_active'] = 'Active';
$string['fullstate_approved'] = 'Activity approved';
$string['fullstate_blocked'] = 'Blocked';
$string['fullstate_completed'] = 'Activity completed';
$string['fullstate_delivered'] = 'Pending feedback from the teacher';
$string['fullstate_failed'] = 'Activity failed';
$string['fullstate_pending'] = '{$a} days left';
$string['fullstate_retarded'] = 'Late {$a} days';
$string['fullstate_undelivered'] = 'Activity undelivered';
$string['gradetopass'] = 'Grade to pass';
$string['gradetopass_help'] = 'The minimum grade that a student must obtain to pass the course. Used when the module don\'t have configured.';
$string['hoursbycredit'] = 'Hours by credit';
$string['hoursbycredit_help'] = 'The number of hours that a student must dedicate to obtain an academic credit.';
$string['includesection0'] = 'Include section 0';
$string['includesection0_help'] = 'Include the section 0 in the course agenda report.';
$string['infodate_active'] = 'Available from <span>{$a}</span>';
$string['infodate_approved'] = 'Rated on <span>{$a}</span>';
$string['infodate_blocked'] = 'Available from <span>{$a->from} to {$a->until}</span>';
$string['infodate_blockedfrom'] = 'Available from <span>{$a}</span>';
$string['infodate_blockedone'] = 'Available on <span>{$a}</span>';
$string['infodate_completed'] = 'Completed on <span>{$a}</span>';
$string['infodate_delivered'] = 'Delivered on <span>{$a}</span>';
$string['infodate_failed'] = 'Rated on <span>{$a}</span>';
$string['infodate_pending'] = 'Available until <span>{$a}</span>';
$string['infodate_retarded'] = 'Available until <span>{$a}</span>';
$string['infodate_undelivered'] = 'Available until <span>{$a}</span>';
$string['noenddate'] = 'No end date';
$string['notdefined'] = 'Not defined';
$string['notweightedactivities'] = 'Not weighted activities';
$string['page-report-courseagenda-index'] = 'Course agenda report';
$string['page-report-courseagenda-x'] = 'Any course agenda report';
$string['pluginname'] = 'Course agenda';
$string['privacy:metadata'] = 'The Course agenda plugin doesn\'t store any personal data.';
$string['progresscolors'] = 'Progress colors';
$string['progresscolors_help'] = 'Select the colors list to use in the progress bar.
Use the format: <b>color|percentage</b> (one per line).<br>
Example:<br>#ff0000|33<br>#ffe600|66<br>#00ff00<br>';
$string['reportsummary'] = 'Hello <span>{$a->userfullname}</span>, this is a course with a duration of <b>{$a->duration} {$a->studytime}</b>, it will be guided by <b>{$a->teacher}</b>. It will begin on <b>{$a->coursestartdate}</b> and end on <b>{$a->courseenddate}</b>.';
$string['settingsappearanceheader'] = 'Appearance';
$string['settingsgeneralheader'] = 'General';
$string['state_active'] = 'Active';
$string['state_approved'] = 'Approved';
$string['state_blocked'] = 'Blocked';
$string['state_completed'] = 'Completed';
$string['state_delivered'] = 'Delivered';
$string['state_failed'] = 'Failed';
$string['state_pending'] = 'Pending';
$string['state_retarded'] = 'Retarded';
$string['state_undelivered'] = 'Undelivered';
$string['statesoptions'] = 'States options';
$string['statesoptions_help'] = 'Select the colors and icons to use in the resources states.
Use the format: <b>state|color|icon</b> (one per line).<br>
For the icons list see: <a href="https://componentlibrary.moodle.com/admin/tool/componentlibrary/docspage.php/moodle/components/moodle-icons/" target="_blank">Moodle icons</a><br>
<b>Available states:</b> active, blocked, pending, completed, approved, failed, delivered, undelivered, retarded.<br>
Example:<br>failed|#FF0000<br>pending|#ffe600|tool_policy:pending<br>approved|#00FF00|core:t/approve<br>';
$string['studytimelabel'] = '- {$a->dedication} hours of dedication ({$a->credits} academic credits)';
$string['timehoursrange'] = 'from {$a->from} to {$a->until}';
$string['weightedactivities'] = 'Weighted activities';
