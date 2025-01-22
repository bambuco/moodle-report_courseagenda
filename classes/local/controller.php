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

namespace report_courseagenda\local;

/**
 * Class controller
 *
 * @package    report_courseagenda
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class controller {

    /**
     * @var int Course duration in days.
     */
    public const COURSEDURATION_DAYS = 1;

    /**
     * @var int Course duration in weeks.
     */
    public const COURSEDURATION_WEEKS = 2;

    /**
     * @var string State blocked.
     */
    public const STATE_BLOCKED = 'blocked';

    /**
     * @var string State pending.
     */
    public const STATE_PENDING = 'pending';

    /**
     * @var string State completed.
     */
    public const STATE_COMPLETED = 'completed';

    /**
     * Completed without a grade.
     *
     * @var string State delivered.
     */
    public const STATE_DELIVERED = 'delivered';

    /**
     * @var string State approved.
     */
    public const STATE_APPROVED = 'approved';

    /**
     * @var string State failed.
     */
    public const STATE_FAILED = 'failed';

    /**
     * @var string State undelivered.
     */
    public const STATE_UNDELIVERED = 'undelivered';

    /**
     * Undelivered but still deliverable.
     *
     * @var string State retarded.
     */
    public const STATE_RETARDED = 'retarded';

    /**
     * Return the course teachers in a formated string.
     *
     * @param int $courseid The course id.
     * @return string The course teachers.
     */
    public static function course_teachers(int $courseid): string {
        global $DB;

        $managersnames = [];
        if ($managerroles = get_config('', 'coursecontact')) {

            $context = \context_course::instance($courseid);
            $coursecontactroles = explode(',', $managerroles);
            $allmanagers = [];

            foreach ($coursecontactroles as $roleid) {
                $managers = get_role_users($roleid, $context, true, 'u.id', 'u.id ASC');

                if (!empty($managers)) {
                    $allmanagers = array_merge($allmanagers, $managers);
                }
            }

            foreach ($allmanagers as $manager) {
                $managersnames[] = fullname($DB->get_record('user', ['id' => $manager->id]));
            }
        }

        return implode(', ', $managersnames);
    }

    /**
     * Return the course credits if the course has the academic credits custom field.
     *
     * @param int $courseid The course id.
     * @return float|null The course credits or null if the course does not have the academic credits custom field.
     */
    public static function get_course_credits(int $courseid): ?float {
        global $DB;

        $fieldid = get_config('report_courseagenda', 'field_academiccredits');

        if (empty($fieldid)) {
            return null;
        }

        $credits = $DB->get_field('customfield_data', 'value', ['fieldid' => $fieldid, 'instanceid' => $courseid]);

        if (empty($credits) && $credits !== '0' && $credits !== 0) {
            return null;
        }

        return (float)$credits;
    }

    /**
     * Return the course progress of a student.
     *
     * @param mixed $course A course object or the course id.
     * @param int $userid The user id.
     * @return float|null The student progress in the course.
     */
    public static function get_student_progress($course, int $userid = 0): ?float {
        global $DB, $USER;

        if (is_numeric($course)) {
            $course = $DB->get_record('course', ['id' => $course], '*', MUST_EXIST);
        }

        if (empty($userid)) {
            $userid = $USER->id;
        }

        $completioninfo = new \completion_info($course);
        if (!$completioninfo->has_activities() || !$completioninfo->is_tracked_user($userid)) {
            return null;
        }

        $params = [];
        $excludein = null;

        $excludemodules = get_config('report_courseagenda', 'excludemodules');
        if (empty($excludemodules)) {
            $excludemodules = explode(',', $excludemodules);
            list($excludein, $params) = $DB->get_in_or_equal($excludemodules);
        }

        $params['courseid'] = $course->id;
        $params['userid'] = $userid;

        $sql = "SELECT cm.id, cmc.completionstate
                  FROM {course_modules} cm
                  LEFT JOIN {course_modules_completion} cmc ON cmc.userid = :userid AND cmc.coursemoduleid = cm.id
                 WHERE cm.course = :courseid
                    AND cm.completion > 0
                    AND cm.visible = 1";

        if (!empty($excludein)) {
            $sql .= " AND cm.module $excludein";
        }

        $completionmodules = $DB->get_records_sql($sql, $params);
        $all = count($completionmodules);

        if ($all == 0) {
            return null;
        }

        $completed = 0;
        foreach ($completionmodules as $module) {
            if ($module->completionstate != COMPLETION_INCOMPLETE) {
                $completed++;
            }
        }

        if ($completed == 0) {
            return 0;
        }

        return round(($completed / $all) * 100, 1);
    }

    /**
     * Return the color for the progress bar based on the progress value.
     *
     * @param float $progress The progress value.
     * @return string The color for the progress bar.
     */
    public static function get_progress_color(float $progress): string {

        static $loaded = false;

        $list = [
            25 => '#e27085',
            50 => '#ff9B52',
            99 => '#ffb950',
            100 => '#50b447',
        ];

        if (!$loaded) {
            $loaded = true;

            $colorslist = get_config('report_courseagenda', 'progresscolors');
            $colorslist = trim($colorslist);
            if (!empty($colorslist)) {
                $colorslist = explode("\n", $colorslist);
                $list = [];
                foreach ($colorslist as $color) {
                    $color = explode('|', $color);
                    if (count($color) == 2) {
                        $list[(int)$color[1]] = trim($color[0]);
                    } else {
                        $list[100] = trim($color[0]);
                    }
                }

                ksort($list);
            }
        }

        foreach ($list as $limit => $color) {
            if ($progress <= $limit) {
                return $color;
            }
        }

        return '#50b447';
    }

    /**
     * Return the state options.
     *
     * @return array The state options.
     */
    private static function get_state_options(): array {
        global $OUTPUT;

        static $loaded = false;

        static $list = [
            self::STATE_APPROVED => [
                'icon' => 'core:t/approve',
                'color' => '#50b447',
            ],
            self::STATE_BLOCKED => [
                'icon' => 'core:t/locked',
                'color' => '#3f3f3f',
            ],
            self::STATE_COMPLETED => [
                'icon' => 'core:i/overriden_grade',
                'color' => '#3593ab',
            ],
            self::STATE_DELIVERED => [
                'icon' => 'tool_policy:pending',
                'color' => '#3593ab',
            ],
            self::STATE_FAILED => [
                'icon' => 'core:i/gradingnotifications',
                'color' => '#d04961',
            ],
            self::STATE_PENDING => [
                'icon' => 'tool_policy:pending',
                'color' => '#e09523',
            ],
            self::STATE_RETARDED => [
                'icon' => 'core:e/cancel_solid_circle',
                'color' => '#d04961',
            ],
            self::STATE_UNDELIVERED => [
                'icon' => 'core:i/unlock',
                'color' => '#d04961',
            ],
        ];

        if (!$loaded) {
            $loaded = true;

            $statesoptions = get_config('report_courseagenda', 'statesoptions');
            $statesoptions = trim($statesoptions);
            if (!empty($statesoptions)) {
                $statesoptions = explode("\n", $statesoptions);
                $arraystates = array_keys($list);
                foreach ($statesoptions as $option) {
                    $option = explode('|', $option);
                    $option = array_map('trim', $option);

                    if (!in_array($option[0], $arraystates)) {
                        continue;
                    }

                    if (count($option) >= 2) {
                        $list[$option[0]]['color'] = $option[1];
                    }

                    if (count($option) == 3) {
                        $list[$option[0]]['icon'] = $option[2];
                    }
                }
            }

            foreach ($list as $state => $data) {
                $list[$state]['iconhtml'] = '';

                if (!empty($data['icon'])) {
                    $iconsource = explode(':', $data['icon']);

                    if (count($iconsource) !== 2) {
                        continue;
                    }

                    // Mdlcode assume: $state ['blocked', 'pending', 'completed', 'approved', 'failed', 'delivered', 'undelivered', 'retarded']
                    $title = get_string('state_' . $state, 'report_courseagenda');
                    $list[$state]['iconhtml'] = $OUTPUT->pix_icon($iconsource[1], $title, $iconsource[0]);
                }
            }
        }

        return $list;
    }

    /**
     * Return the state icon in HTML code.
     *
     * @param string $state The state.
     * @return string The state icon in HTML code.
     */
    public static function get_state_iconhtml(string $state): string {

        $list = self::get_state_options();

        if (!isset($list[$state]) || !isset($list[$state]['iconhtml'])) {
            return '';
        }

        return $list[$state]['iconhtml'];
    }

    /**
     * Return the course sections.
     *
     * @param mixed $course A course object or the course id.
     * @param stdClass $user The user object.
     * @return array The course sections.
     */
    public static function get_course_sections($course, $user): array {
        global $DB, $USER, $OUTPUT;

        if (is_numeric($course)) {
            $course = $DB->get_record('course', ['id' => $course], '*', MUST_EXIST);
        }

        $modinfo = get_fast_modinfo($course);
        $coursesections = $modinfo->get_section_info_all();

        $context = \context_course::instance($course->id);
        $courseformat = course_get_format($course->id);
        $course = $courseformat->get_course();
        $hiddensections = property_exists($course, 'hiddensections') && $course->hiddensections != 1;

        if (!$hiddensections) {
            $hiddensections = has_capability('moodle/course:viewhiddensections', $context, $USER);
        }

        $reportconfig = get_config('report_courseagenda');
        $includesection0 = $reportconfig->includesection0;

        $sections = [];
        foreach ($coursesections as $coursesection) {

            if (!$includesection0 && $coursesection->section == 0) {
                continue;
            }

            if (!$coursesection->visible && !$hiddensections) {
                continue;
            }
            $section = new \stdClass();

            // Check the availability by date.
            $availabilityclass = $courseformat->get_output_classname('content\\section\\availability');
            $availability = new $availabilityclass($courseformat, $coursesection);
            $availabledata = $availability->export_for_template($OUTPUT);

            if ($availabledata->hasavailability) {
                $section->availablemessage = $OUTPUT->render($availability);
            } else if (!$coursesection->available) {
                // Section is not available and the availability is hidden.
                continue;
            }

            $section->blocked = !$coursesection->uservisible;

            $section->order = $coursesection->section;
            $section->orderlabel = $courseformat->get_default_section_name($coursesection);

            // Format general values.
            if (!empty($section->name)) {
                $section->name = format_string($coursesection->name, true, ['context' => $context]);
            } else {
                $section->name = $courseformat->get_section_name($coursesection);
                if ($section->orderlabel == $section->name) {
                    $section->name = '';
                }
            }

            // Load activities from the modules.
            $section->activities = [];
            if (!empty($coursesection->sequence)) {
                // Casting $course->modinfo to string prevents one notice when the field is null.
                $modinfo = $courseformat->get_modinfo();

                $sectionmods = explode(",", $coursesection->sequence);

                $completioninfo = new \completion_info($course);

                $excludemodules = $reportconfig->excludemodules;
                $excludemodules = explode(',', $excludemodules);

                foreach ($sectionmods as $modnumber) {

                    if (empty($modinfo->cms[$modnumber])) {
                        continue;
                    }

                    $mod = $modinfo->cms[$modnumber];

                    if (in_array($mod->modname, $excludemodules)) {
                        continue;
                    }

                    $cmdata = new \stdClass();
                    $cmdata->instancename = format_string($modinfo->cms[$modnumber]->name, true, $course->id);
                    $cmdata->uniqueid = 'cm_' . $mod->id . '_' . time() . '_' . rand(0, 1000);
                    $cmdata->url = $mod->url;
                    $cmdata->hascompletion = false;
                    $cmdata->state = 'pending';
                    $cmdata->enabled = $completioninfo->is_enabled($mod);

                    if ($mod->available == 0) {
                        $cmdata->state = 'blocked';
                    }

                    $displayoptions = [];
                    //$cm = new \cm_base($courseformat, $coursesection, $mod, $displayoptions);

                    if ($completioninfo->is_enabled($mod) !== COMPLETION_TRACKING_NONE) {
                        $cmdata->hascompletion = true;
                        $cmdata->completed = $DB->get_record('course_modules_completion',
                                                    ['coursemoduleid' => $mod->id, 'userid' => $user->id, 'completionstate' => 1]);

                        $cmdata->showcompletionconditions = $course->showcompletionconditions == COMPLETION_SHOW_CONDITIONS;

                    }

                    $cmdata->stateicon = self::get_state_iconhtml($cmdata->state);

                    $section->activities[] = $cmdata;
                }
            }

            $sections[] = $section;
        }

        return $sections;
    }
}
