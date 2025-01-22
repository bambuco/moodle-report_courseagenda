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
 * Callback implementations for Course agenda
 *
 * @package    report_courseagenda
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

/**
 * This function extends the navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $course The course to object for the report
 * @param context $context The context of the course
 */
function report_courseagenda_extend_navigation_course($navigation, $course, $context) {
    if (has_capability('report/courseagenda:view', $context) || has_capability('report/courseagenda:viewall', $context)) {
        $url = new moodle_url('/report/courseagenda/index.php', ['id' => $course->id]);
        $navigation->add(get_string('pluginname', 'report_courseagenda'), $url,
                        navigation_node::TYPE_SETTING, null, null, new pix_icon('i/report', ''));
    }
}

/**
 * Is current user allowed to access this report
 *
 * @param stdClass $user
 * @param stdClass $course
 * @return bool
 */
function report_courseagenda_can_access_user_report($user, $course) {
    global $USER;

    $coursecontext = context_course::instance($course->id);

    if (has_capability('report/courseagenda:viewall', $coursecontext) ||
            ($user->id == $USER->id && has_capability('report/courseagenda:view', $coursecontext))) {
        return true;
    }

    return false;
}

/**
 * This function extends the course navigation with the report items
 *
 * @param navigation_node $navigation The navigation node to extend
 * @param stdClass $user
 * @param stdClass $course The course to object for the report
 */
function report_courseagenda_extend_navigation_user($navigation, $user, $course) {

    if (empty($course) || $course->id == SITEID) {
        return;
    }

    if (report_courseagenda_can_access_user_report($user, $course)) {

        $params = [];
        $params['id'] = $course->id;
        $params['user'] = $user->id;

        $url = new moodle_url('/report/courseagenda/index.php', $params);
        $navigation->add(get_string('pluginname', 'report_courseagenda'), $url);
    }
}

/**
 * Add nodes to myprofile page.
 *
 * @param \core_user\output\myprofile\tree $tree Tree object
 * @param stdClass $user user object
 * @param bool $iscurrentuser
 * @param stdClass $course Course object
 * @return bool
 */
function report_courseagenda_myprofile_navigation(core_user\output\myprofile\tree $tree, $user, $iscurrentuser, $course) {

    if (empty($course) || $course->id == SITEID) {
        return;
    }

    if (report_courseagenda_can_access_user_report($user, $course)) {

        $params = [];
        $params['id'] = $course->id;

        if (!$iscurrentuser) {
            $params['user'] = $user->id;
        }

        $url = new moodle_url('/report/courseagenda/index.php', $params);
        $node = new core_user\output\myprofile\node('reports', 'courseagenda',
                        get_string('pluginname', 'report_courseagenda'), null, $url);
        $tree->add_node($node);
    }
}

/**
 * Return a list of page types
 * @param string $pagetype current page type
 * @param stdClass $parentcontext Block's parent context
 * @param stdClass $currentcontext Current context of block
 * @return array
 */
function report_courseagenda_page_type_list($pagetype, $parentcontext, $currentcontext) {
    $array = [
        '*' => get_string('page-x', 'pagetype'),
        'report-*' => get_string('page-report-x', 'pagetype'),
        'report-courseagenda-*' => get_string('page-report-courseagenda-x',  'report_courseagenda'),
        'report-courseagenda-index' => get_string('page-report-courseagenda-index',  'report_courseagenda'),
    ];
    return $array;
}
