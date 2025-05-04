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
 * Display Course agenda report
 *
 * @package    report_courseagenda
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

use core\report_helper;

require('../../config.php');
require_once($CFG->libdir . '/adminlib.php');

$id = required_param('id', PARAM_INT);
$userid = optional_param('userid', 0, PARAM_INT);

$course = $DB->get_record('course', ['id' => $id], '*', MUST_EXIST);
$context = context_course::instance($course->id);

require_login($course);

if ($userid) {
    require_capability('report/courseagenda:viewall', $context);
    $user = $DB->get_record('user', ['id' => $userid], '*', MUST_EXIST);
} else {
    require_capability('report/courseagenda:view', $context);
    $user = $USER;
}

$url = new moodle_url('/report/courseagenda/index.php', ['id' => $id]);
$title = format_string($course->shortname, true, ['context' => $context]) .': '. get_string('pluginname', 'report_courseagenda');
$PAGE->set_url($url);
$PAGE->set_pagelayout('report');
$PAGE->set_heading(format_string($course->fullname, true, ['context' => $context]));
$PAGE->set_title($title);

echo $OUTPUT->header();

// Print the selector dropdown.
$pluginname = get_string('pluginname', 'report_courseagenda');
report_helper::print_report_selector($pluginname);

$renderable = new \report_courseagenda\output\agenda($course, $user);
$renderer = $PAGE->get_renderer('report_courseagenda');

echo $renderer->render($renderable);

echo $OUTPUT->footer();
