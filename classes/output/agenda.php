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

namespace report_courseagenda\output;

use renderable;
use core\output\renderer_base;
use templatable;
use context_course;
use report_courseagenda\local\controller;
use stdClass;

/**
 * Class agenda
 *
 * @package    report_courseagenda
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class agenda implements renderable, templatable {

    /**
     * @var \stdClass $course The course object
     */
    private $course;

    /**
     * @var \stdClass $user The user object
     */
    private $user;

    /**
     * Constructor
     *
     * @param \stdClass $course The course object
     * @param \stdClass $user The user object
     */
    public function __construct(\stdClass $course, \stdClass $user) {
        $this->course = $course;
        $this->user = $user;
    }

    /**
     * Export this data so it can be used as the context for a mustache template.
     *
     * @param renderer_base $output
     * @return array Context variables for the template
     */
    public function export_for_template(renderer_base $output) {
        global $CFG, $PAGE, $USER;

        $reportconfig = get_config('report_courseagenda');

        $course = $this->course;
        $context = context_course::instance($course->id);
        $course->fullname = format_string($course->fullname, true, ['context' => $context]);
        $course->summary = format_text($course->summary, $course->summaryformat);

        $a = new \stdClass();
        $a->userfullname = fullname($this->user);
        $a->coursestartdate = userdate($course->startdate);
        $a->courseenddate = !empty($course->enddate) ? userdate($course->enddate) : get_string('noenddate', 'report_courseagenda');
        $a->teacher = controller::course_teachers($course->id);

        if (!empty($course->enddate)) {
            $days = ($course->enddate - $course->startdate) / (60 * 60 * 24);
            $weeks = round($days / 7, 1);
            $days = round($days, 1);
            $a->duration = controller::COURSEDURATION_DAYS == $reportconfig->coursedurationformat ?
                            $days . ' ' . get_string('days') :
                            $weeks . ' ' . get_string('weeks');
        } else {
            $a->duration = get_string('notdefined', 'report_courseagenda');
        }

        $a->studytime = '';
        if (!empty($reportconfig->field_academiccredits) && !empty($reportconfig->field_academiccredits)) {
            $credits = controller::get_course_credits($course->id);

            if ($credits) {
                $a->studytime = ' ' . get_string('studytimelabel', 'report_courseagenda', [
                    'dedication' => $reportconfig->hoursbycredit * $credits,
                    'credits' => $credits,
                ]);
            }
        }

        $reportsummary = get_string('reportsummary', 'report_courseagenda', $a);

        // Is the user gradable in the course?
        $gradable = controller::is_user_gradable_in_course($course->id, $USER->id);
        $alertnotgradable = new stdClass();
        if (!$gradable) {
            $alertnotgradable->message = get_string('alertnotgradable', 'report_courseagenda');
        }

        $progress = controller::get_student_progress($course, $this->user->id);
        $progresscolor = '';
        $strokedashoffset = 0;

        if ($progress !== null) {
            // Calculate progress values to show in the progress bar/circle.
            $radius = 35; // Value in attr: circle.r.baseVal.value in the agenda template.
            $circumference = 2 * pi() * $radius;
            $strokedashoffset = $circumference - ($progress / 100 * $circumference);

            $progresscolor = controller::get_progress_color($progress);
        }

        $coursesections = controller::get_course_sections($course, $this->user);

        $userselectorcontent = null;
        if (has_capability('moodle/grade:viewall', $context)) {
            $userid = $this->user->id;
            $gradableusers = \grade_report::get_gradable_users($course->id);
            // Validate whether the requested user is a valid gradable user in this course. If, not display the user select
            // zero state.
            if (empty($gradableusers) || ($userid && !array_key_exists($userid, $gradableusers))) {
                $userid = null;
            }

            $resetlink = new \moodle_url('/report/courseagenda/index.php', ['id' => $course->id]);
            $baseurl = new \moodle_url('/report/courseagenda/index.php', ['id' => $course->id]);
            $PAGE->requires->js_call_amd('gradereport_user/user', 'init', [$baseurl->out(false)]);

            $userselector = new \core_course\output\actionbar\user_selector(
                course: $course,
                resetlink: $resetlink,
                userid: $userid,
                groupid: null,
                usersearch: '',
            );

            $userselectorcontent = $userselector->export_for_template($output);
        }

        $specialcolors = [];
        $stateoptions = controller::get_state_options();
        foreach ($stateoptions as $key => $stateoption) {
            $specialcolors[] = (object)[
                'state' => $key,
                'value' => $stateoption['color'],
            ];
        }

        $defaultvariables = [
            'baseurl' => $CFG->wwwroot,
            'course' => $course,
            'courseid' => $course->id,
            'reportsummary' => $reportsummary,
            'showprogress' => $progress !== null,
            'progress' => $progress,
            'strokedashoffset' => $strokedashoffset,
            'progresscolors' => $progresscolor,
            'coursesections' => $coursesections,
            'gradable' => $gradable,
            'alertnotgradable' => $alertnotgradable,
            'userselector' => $userselectorcontent,
            'specialcolors' => $specialcolors,
        ];

        $PAGE->requires->js_call_amd('report_courseagenda/main', 'init', []);

        return $defaultvariables;

    }
}
