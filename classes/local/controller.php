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

use core_grades\component_gradeitems;

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
     * @var string State active.
     */
    public const STATE_ACTIVE = 'active';

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
     * @var array All modules in the platform.
     */
    private static $modules = null;

    /**
     * @var array The courses modules.
     */
    private static $coursemodules = [];

    /**
     * @var array The activities grades.
     */
    private static $activitiesgrades = [];

    /**
     * @var array The deliverable modules supported list.
     */
    private const DELIVERABLE_ACTIVITIES = [
        'assign',
        'choice',
        'data',
        'feedback',
        'forum',
        'glossary',
        'h5pactivity',
        'lesson',
        'quiz',
        'scorm',
        'survey',
        'wiki',
        'workshop',
    ];

    /**
     * Return the modules in the platform.
     *
     * @param bool $onlyvisible If true, only the visible modules will be returned.
     * @return array The modules.
     */
    public static function get_modules(bool $onlyvisible = false): array {
        global $DB;

        if (empty(self::$modules)) {
            self::$modules = $DB->get_records('modules');

            foreach (self::$modules as $module) {
                $module->gradable = (bool)plugin_supports('mod', $module->name, FEATURE_GRADE_HAS_GRADE);
                $module->deliverable = in_array($module->name, self::DELIVERABLE_ACTIVITIES);
            }
        }

        if ($onlyvisible) {
            $modules = [];
            foreach (self::$modules as $module) {
                if ($module->visible) {
                    $modules[$module->id] = $module;
                }
            }

            return $modules;
        }

        return self::$modules;
    }

    /**
     * Return a module by its id.
     *
     * @param int $moduleid The module id.
     * @return object|null The module.
     */
    public static function get_modulebyid(int $moduleid): ?object {
        $modules = self::get_modules();

        return $modules[$moduleid] ?? null;
    }

    /**
     * Return the module name by its id.
     *
     * @param int $moduleid The module id.
     * @return string The module name.
     */
    public static function get_modulename(int $moduleid): string {
        $module = self::get_modulebyid($moduleid);

        return $module ? $module->name : '';
    }

    /**
     * Return the modules in a course.
     *
     * @param int $courseid The course id.
     * @return array The course modules.
     */
    public static function get_coursemodules($courseid): array {
        global $DB;

        if (empty(self::$coursemodules[$courseid])) {
            $modules = self::get_modules();
            $cms = $DB->get_records('course_modules', ['course' => $courseid]);
            foreach ($cms as $cm) {
                $cm->modulename = $modules[$cm->module]->name;

                $component = 'mod_' . $cm->modulename;
                $cm->gradeitemnamemapping = component_gradeitems::get_itemname_mapping_for_component($component);
                if (component_gradeitems::defines_advancedgrading_itemnames_for_component($component)) {
                    $cm->advancedgradingitemnames = component_gradeitems::get_advancedgrading_itemnames_for_component($component);
                } else {
                    $cm->advancedgradingitemnames = [];
                }

                $gradeitemnamemappingbyid = array_flip($cm->gradeitemnamemapping);
                $cm->advancedgradingitemids = [];
                foreach ($cm->advancedgradingitemnames as $itemname) {
                    $cm->advancedgradingitemids[] = $gradeitemnamemappingbyid[$itemname];
                }

                $key = $cm->modulename . '_' . $cm->instance;
                self::$coursemodules[$courseid][$key] = $cm;
            }
        }

        return self::$coursemodules[$courseid];
    }

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

        if (!$course->enablecompletion) {
            return null;
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
        $excludemodules = trim($excludemodules);
        if (!empty($excludemodules)) {
            $excludemodules = explode(',', $excludemodules);
            [$excludein, $params] = $DB->get_in_or_equal($excludemodules, SQL_PARAMS_NAMED, 'param', false);
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
    public static function get_state_options(): array {
        global $OUTPUT;

        static $loaded = false;

        static $list = [
            self::STATE_ACTIVE => [
                'icon' => 'core:t/go',
                'color' => '#9c27b0',
            ],
            self::STATE_APPROVED => [
                'icon' => 'core:t/approve',
                'color' => '#50b447',
            ],
            self::STATE_BLOCKED => [
                'icon' => 'core:t/locked',
                'color' => '#646464',
            ],
            self::STATE_COMPLETED => [
                'icon' => 'core:i/overriden_grade',
                'color' => '#3593ab',
            ],
            self::STATE_DELIVERED => [
                'icon' => 'core:e/insert_time',
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

                    // phpcs:ignore
                    // Mdlcode assume: $state ['active', 'blocked', 'pending', 'completed', 'approved', 'failed', 'delivered', 'undelivered', 'retarded'].
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

        $context = \context_course::instance($course->id);
        $courseformat = course_get_format($course->id);
        $course = $courseformat->get_course();
        // phpcs:ignore
        // $modinfo = $courseformat->get_modinfo();
        $modinfo = get_fast_modinfo($course, $user->id);
        $modinfooriginal = get_fast_modinfo($course, -1);

        $coursesections = $modinfo->get_section_info_all();
        $hiddensections = property_exists($course, 'hiddensections') && $course->hiddensections != 1;

        $usergroups = groups_get_all_groups($course->id, $user->id);

        if (!$hiddensections) {
            $hiddensections = has_capability('moodle/course:viewhiddensections', $context, $USER);
        }

        $viewhiddenactivities = has_capability('moodle/course:viewhiddenactivities', $context, $USER);

        $reportconfig = get_config('report_courseagenda');
        $includesection0 = $reportconfig->includesection0;

        $excludesections = trim($reportconfig->excludesections);
        $excludesections = explode("\n", $excludesections);
        $excludesections = array_map('trim', $excludesections);
        $excludesections = array_filter($excludesections, function ($sectionname) {
            return !empty($sectionname);
        });

        $completioninfo = new \completion_info($course);
        $hascompletion = $course->enablecompletion && $completioninfo->is_tracked_user($user->id);

        $excludemodules = trim($reportconfig->excludemodules);
        $excludemodules = explode(',', $excludemodules);

        $activitiesgrades = self::activities_grades($course, $user);
        $availablemethods = \grading_manager::available_methods();

        // Load available modules.
        $modules = self::get_modules(true);

        // Preload the modules completion information.
        $params = [];
        $excludein = null;

        if (!empty($excludemodules)) {
            [$excludein, $params] = $DB->get_in_or_equal($excludemodules, SQL_PARAMS_NAMED, 'param', false);
        }

        $params['courseid'] = $course->id;
        $params['userid'] = $user->id;

        $sql = "SELECT cmc.coursemoduleid AS id, cmc.completionstate
                FROM {course_modules} cm
                INNER JOIN {course_modules_completion} cmc ON cmc.userid = :userid AND cmc.coursemoduleid = cm.id
                WHERE cm.course = :courseid
                    AND cm.completion > 0";

        if (!empty($excludein)) {
            $sql .= " AND cm.module $excludein";
        }

        $completionmodules = $DB->get_records_sql($sql, $params);
        // End of Preload the modules completion information.

        $dateshort = get_string('strftimedatetimeshort', 'langconfig');
        $dateoneday = get_string('strftimedate', 'langconfig');
        $daystograde = (get_config('report_courseagenda', 'daystograde') * 24 * 60 * 60);
        $includehiddensectionsmods = !empty(get_config('report_courseagenda', 'includehiddensectionsmods'));
        $sections = [];
        foreach ($coursesections as $coursesection) {
            if (!$includesection0 && $coursesection->section == 0) {
                continue;
            }

            foreach ($excludesections as $excludesection) {
                if (self::section_name_matches_exclusion($coursesection->name, $excludesection)) {
                    continue 2;
                }
            }

            if (!$coursesection->visible && !$hiddensections) {
                continue;
            }
            $section = new \stdClass();

            // Check the availability by date.
            $availabilityclass = $courseformat->get_output_classname('content\\section\\availability');
            $availability = new $availabilityclass($courseformat, $coursesection);
            $availabledata = $availability->export_for_template($OUTPUT);
            $section->availabledata = $availabledata;

            if (isset($availabledata->info)) {
                $availabledata->info->isfullinfo = false;
            }

            // If section is unavailable and there is no availability info, it is hidden by restriction.
            // When availability info exists, restriction is configured to show the section as unavailable.
            if (!$hiddensections && !$coursesection->available && empty($availabledata->hasavailability)) {
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
                $sectionmods = explode(",", $coursesection->sequence);

                foreach ($sectionmods as $modnumber) {
                    $notcompletion = false;

                    if (empty($modinfo->cms[$modnumber])) {
                        continue;
                    }

                    $mod = $modinfo->cms[$modnumber];
                    $originalmod = $modinfooriginal->cms[$modnumber];
                    $moduletype = $modules[$mod->module];

                    if (in_array($mod->modname, $excludemodules)) {
                        continue;
                    }

                    // Check if module is visible. If not, continue with the next one.
                    if (!$mod->uservisible && !$viewhiddenactivities) {
                        // When the activity is not visible for students, check if the section is available.
                        // If the section is not available, the activity will be shown as unavailable instead of hidden.
                        if ($coursesection->available || !$includehiddensectionsmods) {
                            continue;
                        }
                    }

                    // The activity don't have completion tracking.
                    if ($completioninfo->is_enabled($mod) === COMPLETION_TRACKING_NONE) {
                        $notcompletion = true;
                    }

                    $gradeitem = $activitiesgrades[$moduletype->name . '-' . $mod->instance] ?? null;

                    $cmdata = new \stdClass();
                    $cmdata->instancename = format_string($mod->name, true, $course->id);
                    $cmdata->uniqueid = 'cm_' . $mod->id . '_' . time() . '_' . rand(0, 1000);
                    $cmdata->url = $mod->url;
                    $cmdata->hascompletion = false;
                    $cmdata->state = $notcompletion ? self::STATE_ACTIVE : self::STATE_PENDING;
                    $cmdata->enabled = $completioninfo->is_enabled($mod);
                    $cmdata->activityinfodatelabel = '';
                    $cmdata->modurl = new \moodle_url('/mod/' . $mod->modname . '/view.php', ['id' => $mod->id]);
                    $cmdata->pass = null;
                    $cmdata->deliverable = $moduletype->deliverable;
                    $cmdata->delivered = false;
                    $cmdata->delivereddate = 0;
                    $cmdata->showlink = true;

                    // phpcs:ignore
                    // Mdlcode assume: $moduletype->name ['assign', 'book', 'data'].
                    $cmdata->activitytype = get_string('pluginname', $moduletype->name);

                    $infodates = self::activity_enabledate($mod, $originalmod);
                    $cmdata->infodates = $infodates;

                    if (!$infodates->until) {
                        $infodates->until = $course->enddate;
                    }
                    $infodates->untilformated = empty($infodates->originaluntil) ? get_string('notuntil', 'report_courseagenda')
                                                                         : userdate($infodates->originaluntil, $dateshort);

                    // Check activity extension dates.
                    $extensions = self::get_activityextensions($mod, $user, $usergroups);

                    $cmdata->extensions = [];
                    foreach ($extensions as $extension) {
                        $extension = userdate($extension, $dateshort);
                        $cmdata->extensions[] = get_string('extensiondate', 'report_courseagenda', $extension);
                    }

                    if ($mod->available == 0 || $infodates->from > time()) {
                        $cmdata->state = self::STATE_BLOCKED;
                    }

                    if (!$notcompletion && isset($completionmodules[$mod->id])) {
                        switch ($completionmodules[$mod->id]->completionstate) {
                            case COMPLETION_INCOMPLETE:
                                $cmdata->state = self::STATE_PENDING;
                                break;
                            case COMPLETION_COMPLETE:
                                $cmdata->state = self::STATE_COMPLETED;
                                break;
                            case COMPLETION_COMPLETE_PASS:
                                $cmdata->state = self::STATE_APPROVED;
                                break;
                            case COMPLETION_COMPLETE_FAIL_HIDDEN:
                            case COMPLETION_COMPLETE_FAIL:
                                $cmdata->state = self::STATE_FAILED;
                                break;
                        }
                    }

                    if (empty($infodates->from)) {
                        $infodates->from = $course->startdate;
                    }

                    if ($hascompletion && $completioninfo->is_enabled($mod) !== COMPLETION_TRACKING_NONE) {
                        $cmdata->hascompletion = true;
                        $cmdata->completed = $DB->get_record(
                            'course_modules_completion',
                            ['coursemoduleid' => $mod->id, 'userid' => $user->id, 'completionstate' => 1]
                        );

                        $cmdata->showcompletionconditions = $course->showcompletionconditions == COMPLETION_SHOW_CONDITIONS;
                    }

                    // Start Grade information.
                    $cmdata->gradeitem = $gradeitem;
                    $cmdata->showgrade = false;
                    $cmdata->weighing = '0%';

                    $modgraded = false;
                    $requiregrade = false;
                    if ($cmdata->gradeitem) {
                        $cmdata->currentgrade = [];
                        $weighing = 0;
                        $pass = [];
                        foreach ($cmdata->gradeitem as $gradeitem) {
                            $currentgrade = new \stdClass();
                            $currentgrade->weighing = $gradeitem->info->weightformatted;
                            $currentgrade->itemname = self::itemname($gradeitem, count($cmdata->gradeitem) > 1);
                            $currentgrade->grademethod = $availablemethods[$gradeitem->info->grademethod] ?? '';

                            // To calculate the general activity weighing.
                            $weighing += $gradeitem->info->weightraw;

                            if ($gradeitem->info->gradeformatted != '-') {
                                $cmdata->showgrade = true;
                                $currentgrade->value = $gradeitem->info->visible ? $gradeitem->info->gradeformatted : '-';

                                if ($gradeitem->item->gradepass && (float)$gradeitem->item->gradepass > 0) {
                                    $currentgrade->pass = $gradeitem->info->graderaw >= (float)$gradeitem->item->gradepass;
                                } else {
                                    $currentgrade->pass = $gradeitem->info->graderaw >= $reportconfig->gradetopass;
                                }
                                $modgraded = true;

                                $pass[] = $currentgrade->pass;
                            }

                            $cmdata->currentgrade[] = $currentgrade;
                        }

                        // If all the grades are passed.
                        if (count($pass) == count($cmdata->currentgrade)) {
                            $cmdata->pass = array_sum($pass) == count($pass);
                        } else {
                            $requiregrade = true;
                        }

                        $cmdata->weighing = !empty($cmdata->gradeitem) ? $weighing : 0;
                        $cmdata->weighing = self::format_number($cmdata->weighing * 100);
                        $cmdata->weighing .= '%';
                    }

                    // Delivered information.
                    if ($moduletype->deliverable) {
                        $cmdata->delivereddate = self::activitydelivered($mod, $user);
                        if ($cmdata->delivereddate) {
                            $cmdata->delivered = true;
                        }
                    }

                    if (!$modgraded || $requiregrade) {
                        if ($requiregrade && self::require_feedbackgrade($mod, $user)) {
                            if ($infodates->until > 0) {
                                $infodates->feedbackdate = $infodates->until + $daystograde;
                                $infodates->feedbackdateformated = userdate($infodates->feedbackdate, $dateoneday);
                                $infodates->feedbackdateexpired = $infodates->feedbackdate < time();
                            } else {
                                $infodates->feedbackdate = 0;
                                $infodates->feedbackdateformated = get_string('notapplicable', 'report_courseagenda');
                            }
                            $requiregrade = true;
                        } else {
                            $infodates->feedbackdate = 0;
                            $infodates->feedbackdateformated = get_string('automaticgrade', 'report_courseagenda');
                        }

                        // Not graded but delivered.
                        if ($cmdata->delivered) {
                            $cmdata->state = $requiregrade ? self::STATE_DELIVERED : self::STATE_COMPLETED;
                        }
                    } else {
                        $infodates->feedbackdate = 0;
                        $infodates->feedbackdateformated = get_string('graded', 'report_courseagenda');

                        if ($cmdata->pass === true) {
                            $cmdata->state = self::STATE_APPROVED;
                        } else if ($cmdata->pass === false) {
                            $cmdata->state = self::STATE_FAILED;
                        } else if ($cmdata->delivered) {
                            $cmdata->state = self::STATE_COMPLETED;
                        }
                    }
                    // End of Grade information.

                    if (
                        $moduletype->deliverable
                        && ($cmdata->state == self::STATE_PENDING || $cmdata->state == self::STATE_ACTIVE)
                        && $infodates->until > 0
                        && $infodates->until < time()
                    ) {
                        $cmdata->state = self::STATE_UNDELIVERED;

                        if ($infodates->close) {
                            $cmdata->state = self::STATE_RETARDED;
                        }
                    }

                    // Module availability.
                    $cmdata->restrictions = self::get_activityrestrictions($mod);

                    // Module completion conditions.
                    $cmdata->completion = self::get_activitycompletionconditions($mod, $user);

                    // Changes according to the state.
                    $infostate = null;
                    $infodatefrom = $infodates->from;
                    $infodateuntil = $infodates->until;
                    switch ($cmdata->state) {
                        case self::STATE_PENDING:
                        case self::STATE_RETARDED:
                        case self::STATE_UNDELIVERED:
                            $infostate = $infodates->until > 0 ? ($infodates->until - time()) / (60 * 60 * 24) : 0;
                            $infostate = round($infostate);

                            if ($infodates->until == 0) {
                                $cmdata->fullstatename = get_string('notuntil', 'report_courseagenda');
                            } else if ($infostate < 0 && $infodates->close) {
                                $closedatef = userdate($infodates->close, $dateoneday);
                                $cmdata->fullstatename = get_string('fullstate_retardedactive', 'report_courseagenda', $closedatef);
                            } else if ($infostate <= $reportconfig->daystosendactivity) {
                                if ($infostate < 0) {
                                    $days = abs($infostate);
                                    $cmdata->fullstatename = get_string('fullstate_retarded', 'report_courseagenda', $days);
                                } else {
                                    $cmdata->fullstatename = get_string('fullstate_pendingdays', 'report_courseagenda', $infostate);
                                }
                            }
                            break;
                        case self::STATE_DELIVERED:
                        case self::STATE_APPROVED:
                        case self::STATE_FAILED:
                            if ($cmdata->delivered) {
                                $infodatefrom = $cmdata->delivereddate;
                                $infodateuntil = 0;
                            }
                            break;
                        case self::STATE_BLOCKED:
                            $cmdata->showlink = $viewhiddenactivities;
                            break;
                    }

                    if (empty($cmdata->fullstatename)) {
                        // phpcs:ignore
                        // Mdlcode assume: $cmdata->state ['active', 'blocked', 'pending', 'completed', 'approved', 'failed', 'delivered', 'undelivered', 'retarded'].
                        $cmdata->fullstatename = get_string('fullstate_' . $cmdata->state, 'report_courseagenda');
                    }

                    // phpcs:ignore
                    // Mdlcode assume: $cmdata->state ['active', 'blocked', 'pending', 'completed', 'approved', 'failed', 'delivered', 'undelivered', 'retarded'].
                    $cmdata->statename = get_string('state_' . $cmdata->state, 'report_courseagenda');

                    $label = self::get_activityinfodatelabel($cmdata->state, $infodatefrom, $infodateuntil);
                    $cmdata->activityinfodatelabel = $label;

                    $cmdata->stateicon = self::get_state_iconhtml($cmdata->state);

                    $section->activities[] = $cmdata;
                }
            }

            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * Check whether a section name matches an exclusion rule.
     *
     * Plain text rules require an exact match. Regex behavior is only enabled
     * when the configured value contains regex metacharacters.
     *
     * @param string $sectionname The course section name.
     * @param string $rule The configured exclusion rule.
     * @return bool True when the section must be excluded.
     */
    protected static function section_name_matches_exclusion(string $sectionname, string $rule): bool {
        if (!self::has_regex_metacharacters($rule)) {
            return \core_text::strtolower($sectionname) === \core_text::strtolower($rule);
        }

        return @preg_match('/' . $rule . '/iu', $sectionname) === 1;
    }

    /**
     * Detect whether an exclusion rule should be interpreted as regex.
     *
     * @param string $rule The configured exclusion rule.
     * @return bool True when the rule contains regex metacharacters.
     */
    protected static function has_regex_metacharacters(string $rule): bool {
        return preg_match('/[\\\\.\+\*\?\[\^\]\$\(\)\{\}\|]/', $rule) === 1;
    }

    /**
     * Return the enable date of an activity.
     *
     * @param \cm_info $mod The course module info.
     * @param \cm_info $originalmod The original course module info. Not user customdata.
     * @return object The enable dates of the activity: from, until.
     */
    public static function activity_enabledate(\cm_info $mod, \cm_info $originalmod): object {
        $dates = (object)[
            'from' => 0,
            'until' => 0,
            'originaluntil' => 0,
            'close' => 0,
        ];

        // Check the availability by date. Activity is not available yet.
        if (!empty($mod->availablefrom) && $mod->availablefrom > time()) {
            $dates->from = $mod->availablefrom;
            return $dates;
        }

        $cmdata = $mod->customdata;
        $cmdataoriginal = $originalmod->customdata;

        if (is_string($cmdata)) {
            return $dates;
        }

        $course = $mod->get_course();

        if (is_array($cmdata)) {
            $cmdata = (object)$cmdata;
        }

        if (is_array($cmdataoriginal)) {
            $cmdataoriginal = (object)$cmdataoriginal;
        }

        switch ($mod->modname) {
            case 'assign':
                $dates->from = $cmdata->allowsubmissionsfromdate ?? '';
                $dates->until = $cmdata->duedate ?? '';
                $dates->originaluntil = $cmdataoriginal->duedate ?? '';
                $dates->close = $cmdata->cutoffdate ?? $course->enddate;
                break;
            case 'data':
                $dates->from = $cmdata->timeavailablefrom ?? '';
                $dates->until = $cmdata->timeavailableto ?? '';
                $dates->originaluntil = $cmdataoriginal->timeavailableto ?? '';
                break;
            case 'feedback':
                $dates->from = $cmdata->timeopen ?? '';
                $dates->until = $cmdata->timeclose ?? '';
                $dates->originaluntil = $cmdataoriginal->timeclose ?? '';
                break;
            case 'forum':
                $dates->until = $cmdata->duedate ?? '';
                $dates->originaluntil = $cmdataoriginal->duedate ?? '';
                $dates->close = $cmdata->cutoffdate ?? $course->enddate;
                break;
            case 'lesson':
                $dates->from = $cmdata->available ?? '';
                $dates->until = $cmdata->deadline ?? '';
                $dates->originaluntil = $cmdataoriginal->deadline ?? '';
                break;
            case 'quiz':
                $dates->from = $cmdata->timeopen ?? '';
                $dates->until = $cmdata->timeclose ?? '';
                $dates->originaluntil = $cmdataoriginal->timeclose ?? '';
                break;
            case 'scorm':
                $dates->from = $cmdata->timeopen ?? '';
                $dates->until = $cmdata->timeclose ?? '';
                $dates->originaluntil = $cmdataoriginal->timeclose ?? '';
                break;
            case 'workshop':
                $dates->from = $cmdata->submissionstart ?? '';
                $dates->until = $cmdata->submissionend ?? '';
                $dates->originaluntil = $cmdataoriginal->submissionend ?? '';
                break;
        }

        // If the close date is in the past, the activity is considered closed.
        if ($dates->close < time()) {
            $dates->close = 0;
        }

        return $dates;
    }

    /**
     * Return the course grades by activities.
     *
     * @param object $course The course object.
     * @param object $user The user object.
     * @return array The course grades by activities.
     */
    public static function activities_grades(object $course, object $user): array {
        global $DB, $CFG, $OUTPUT;

        if (isset(self::$activitiesgrades[$course->id])) {
            return self::$activitiesgrades[$course->id];
        }

        $gradesinfo = [];

        $coursecontext = \context_course::instance($course->id);
        $canviewhidden = has_capability('moodle/grade:viewhidden', $coursecontext);
        $canviewall = has_capability('moodle/grade:viewall', $coursecontext);

        $showhiddenitems = grade_get_setting(
            $course->id,
            'report_user_showhiddenitems',
            $CFG->grade_report_user_showhiddenitems
        );

        // Load the user report for use some methods.
        $gpr = new \grade_plugin_return(['type' => 'report', 'plugin' => 'user', 'courseid' => $course->id, 'userid' => $user->id]);
        $report = new userreportextended($course->id, $gpr, $coursecontext, $user->id);

        $excludemodules = trim(get_config('report_courseagenda', 'excludemodules'));
        $excludemodules = explode(',', $excludemodules);

        $gradeitems = $DB->get_records('grade_items', ['courseid' => $course->id, 'itemtype' => 'mod']);

        $coursemodules = self::get_coursemodules($course->id);

        foreach ($gradeitems as $gradeitem) {
            if (
                in_array($gradeitem->itemmodule, $excludemodules)
                || (property_exists($gradeitem, 'gradetype') && $gradeitem->gradetype == GRADE_TYPE_NONE)
            ) {
                continue;
            }

            // Sort by module and instance id to make it easier to access them.
            $gradeinfokey = $gradeitem->itemmodule . '-' . $gradeitem->iteminstance;
            if (!isset($gradesinfo[$gradeinfokey])) {
                $gradesinfo[$gradeinfokey] = [];
            }
            $customgradeinfo = new \stdClass();
            $gradesinfo[$gradeinfokey][] = $customgradeinfo;

            // Basic grade item information.
            $customgradeinfo->info = new \stdClass();
            $customgradeinfo->info->visible = true;
            $customgradeinfo->info->gradestatus = '';
            $customgradeinfo->info->gradecontent = '';
            $customgradeinfo->info->grademethod = '';
            $customgradeinfo->info->weightformatted = '0%';
            $customgradeinfo->info->weightraw = 0;
            $customgradeinfo->info->locked = null;
            $customgradeinfo->info->overridden = false;
            $customgradeinfo->info->excluded = false;
            $customgradeinfo->info->gradehiddenbydate = false;
            $customgradeinfo->info->gradedatesubmitted = null;
            $customgradeinfo->info->gradedategraded = null;
            $customgradeinfo->info->gradeformatted = '-';
            $customgradeinfo->info->graderaw = 0;

            // Process the grade type.
            if (!$gradegrade = \grade_grade::fetch(['itemid' => $gradeitem->id, 'userid' => $user->id])) {
                $gradegrade = new \grade_grade();
                $gradegrade->userid = $user->id;
                $gradegrade->itemid = $gradeitem->id;
            }

            $gradegrade->load_grade_item();
            $gradeitem = $gradegrade->grade_item;
            $customgradeinfo->item = $gradeitem;

            $gradehidden = $gradegrade->is_hidden();

            // Hidden Items.
            if ($gradehidden) {
                $customgradeinfo->info->visible = false;
                continue;
            }

            // If this is a hidden grade item, hide it completely from the user.
            if (
                $gradehidden &&
                !$canviewhidden &&
                (
                    $showhiddenitems == GRADE_REPORT_USER_HIDE_HIDDEN ||
                    ($showhiddenitems == GRADE_REPORT_USER_HIDE_UNTIL && !$gradegrade->is_hiddenuntil())
                )
            ) {
                $customgradeinfo->info->visible = false;
                continue;
            }

            // Actual Grade - We need to calculate this whether.
            $gradeval = $gradegrade->finalgrade;
            $hint = $gradegrade->get_aggregation_hint();
            if (!$canviewhidden) {
                // Virtual Grade (may be calculated excluding hidden items etc).
                $adjustedgrade = $report->get_blank_hidden_total_and_adjust_bounds(
                    $course->id,
                    $gradegrade->grade_item,
                    $gradeval
                );

                $gradeval = $adjustedgrade['grade'];

                // We temporarily adjust the view of this grade item - because the min and
                // max are affected by the hidden values in the aggregation.
                $gradegrade->grade_item->grademax = $adjustedgrade['grademax'];
                $gradegrade->grade_item->grademin = $adjustedgrade['grademin'];
                $hint['status'] = $adjustedgrade['aggregationstatus'];
                $hint['weight'] = $adjustedgrade['aggregationweight'];
            } else {
                // The max and min for an aggregation may be different to the grade_item.
                if (!is_null($gradeval)) {
                    $gradegrade->grade_item->grademax = $gradegrade->get_grade_max();
                    $gradegrade->grade_item->grademin = $gradegrade->get_grade_min();
                }
            }

            $customgradeinfo->info->locked = $canviewall ? $gradegrade->grade_item->is_locked() : null;
            $customgradeinfo->info->overridden = $gradegrade->is_overridden();
            $customgradeinfo->info->excluded = $gradegrade->is_excluded();

            // Get the grading area.
            $cm = $coursemodules[$gradeitem->itemmodule . '_' . $gradeitem->iteminstance];
            if (in_array($gradeitem->itemnumber, $cm->advancedgradingitemids)) {
                $cmcontext = \context_module::instance($cm->id);
                $params = ['component' => 'mod_' . $gradeitem->itemmodule, 'contextid' => $cmcontext->id];
                $gradingareas = $DB->get_record('grading_areas', $params);

                if ($gradingareas) {
                    $customgradeinfo->info->grademethod = $gradingareas->activemethod ?? '';
                }
            }

            // Fallback: calculate weight from grade_item config when no grade exists yet.
            if (!is_numeric($hint['weight'])) {
                $hint['weight'] = self::get_item_configured_weight($gradeitem);
            }

            // This obliterates the weight because it provides a more informative description.
            if (is_numeric($hint['weight'])) {
                $customgradeinfo->info->weightraw = $hint['weight'];
                $customgradeinfo->info->weightformatted = self::format_number($hint['weight'] * 100.0) . '%';
            }
            if ($hint['status'] != 'used' && $hint['status'] != 'unknown') {
                $customgradeinfo->info->status = $hint['status'];
            }

            $gradestatus = '';

            $context = [
                'hidden' => $gradehidden,
                'locked' => $gradegrade->is_locked(),
                'overridden' => $gradegrade->is_overridden(),
                'excluded' => $gradegrade->is_excluded(),
            ];

            if (in_array(true, $context)) {
                $context['classes'] = 'gradestatus';
                $customgradeinfo->info->gradestatus = $OUTPUT->render_from_template('core_grades/status_icons', $context);
            }

            $customgradeinfo->info->gradehiddenbydate = false;
            $customgradeinfo->info->gradedatesubmitted = $gradegrade->get_datesubmitted();
            $customgradeinfo->info->gradedategraded = $gradegrade->get_dategraded();

            if (
                !empty($CFG->grade_hiddenasdate)
                && $gradegrade->get_datesubmitted()
                && !$canviewhidden
                && $gradehidden
            ) {
                // The problem here is that we do not have the time when grade value was modified
                // 'timemodified' is general modification date for grade_grades records.
                $customgradeinfo->info->gradecontent = get_string(
                    'submittedon',
                    'grades',
                    userdate(
                        $gradegrade->get_datesubmitted(),
                        get_string('strftimedatetimeshort')
                    ) . $gradestatus
                );
                $customgradeinfo->info->gradehiddenbydate = true;
            } else if ($gradehidden) {
                $customgradeinfo->info->gradecontent = '-';

                if ($canviewhidden) {
                    $customgradeinfo->info->graderaw = $gradeval;
                    $customgradeinfo->info->gradecontent = grade_format_gradevalue(
                        $gradeval,
                        $gradegrade->grade_item,
                        true
                    ) . $gradestatus;
                }
            } else {
                $gradestatusclass = '';
                $gradepassicon = '';
                $ispassinggrade = $gradegrade->is_passed($gradegrade->grade_item);
                if (!is_null($gradeval) && !is_null($ispassinggrade)) {
                    $gradestatusclass = $ispassinggrade ? 'gradepass' : 'gradefail';
                    if ($ispassinggrade) {
                        $gradepassicon = $OUTPUT->pix_icon(
                            'i/valid',
                            get_string('pass', 'grades'),
                            null,
                            ['class' => 'inline']
                        );
                    } else {
                        $gradepassicon = $OUTPUT->pix_icon(
                            'i/invalid',
                            get_string('fail', 'grades'),
                            null,
                            ['class' => 'inline']
                        );
                    }
                }

                $customgradeinfo->info->gradeclass = $gradestatusclass;
                $gradeformatgradevalue = grade_format_gradevalue($gradeval, $gradegrade->grade_item, true);
                $customgradeinfo->info->gradecontent = $gradepassicon . $gradeformatgradevalue . $gradestatus;
                $customgradeinfo->info->graderaw = $gradeval;
            }

            $customgradeinfo->info->gradeformatted = $customgradeinfo->info->gradecontent;
        }

        self::$activitiesgrades[$course->id] = $gradesinfo;

        return $gradesinfo;
    }

    /**
     * Return if the activity requires a feedback grade.
     *
     * @param \cm_info $mod The course module info.
     * @param object $user The user object.
     * @return bool If the activity requires a feedback grade.
     */
    public static function require_feedbackgrade(\cm_info $mod, object $user): bool {
        global $DB;

        $coursemodules = self::get_coursemodules($mod->course);
        $cm = $coursemodules[$mod->modname . '_' . $mod->instance];

        $hasadvancedgrading = (count($cm->advancedgradingitemids) > 0);

        switch ($mod->modname) {
            case 'assign':
                return true;
            case 'data':
                $data = $DB->get_record('data', ['id' => $mod->instance]);
                return $data->approval == 1;
            case 'forum':
                return $hasadvancedgrading;
            case 'lesson':
                // ToDo: validar si la lección tiene algo que deba ser valificado por el profesor.
                return $hasadvancedgrading;
            case 'quiz':
                $params = [
                    'userid' => $user->id,
                    'quiz' => $mod->instance,
                    'sumgrades' => null,
                    'state' => 'finished',
                ];
                $attempts = $DB->count_records('quiz_attempts', $params);
                return $attempts > 0;
            case 'workshop':
                return $hasadvancedgrading;
        }

        return false;
    }

    /**
     * Return the conditions to complete the activity.
     *
     * @param \cm_info $mod The course module info.
     * @param object $user The user object.
     * @return array The course module restrictions.
     */
    public static function get_activitycompletionconditions(\cm_info $mod, $user): array {
        global $OUTPUT;

        $completiondetails = \core_completion\cm_completion_details::get_instance($mod, $user->id);

        $activitycompletion = new \core_course\output\activity_completion($mod, $completiondetails);
        $activitycompletiondata = (array) $activitycompletion->export_for_template($OUTPUT);

        if (!$activitycompletiondata['hascompletion']) {
            return [];
        }

        return $activitycompletiondata;
    }

    /**
     * Return the activity dates.
     *
     * Not used at the moment, the functionality was implemented with activity_enabledate but Moodle's own functions are used here.
     * This should be considered later in case inconsistencies in dates are found.
     *
     * @param \cm_info $mod The course module info.
     * @param object $user The user object.
     * @return array The activity dates.
     */
    public static function get_activitydates(\cm_info $mod, $user): array {
        global $OUTPUT;

        $activitydates = \core\activity_dates::get_dates_for_module($mod, $user->id);
        $activitydates = new \core_course\output\activity_dates($activitydates);
        $activitydatesdata = (array) $activitydates->export_for_template($OUTPUT);

        return $activitydatesdata;
    }

    /**
     * Return the course module restrictions.
     *
     * @param \cm_info $mod The course module info.
     * @return string The course module restrictions html.
     */
    public static function get_activityrestrictions(\cm_info $mod): string {

        if (empty($mod->availableinfo)) {
            return '';
        }

        $course = $mod->get_course();

        $text = \core_availability\info::format_info($mod->availableinfo, $course);

        return $text;
    }

    /**
     * Return the course module restrictions with course format output.
     *
     * @param \cm_info $mod The course module info.
     * @return string The course module restrictions html.
     */
    public static function get_activityrestrictionsfull(\cm_info $mod): string {
        global $OUTPUT;

        if (empty($mod->availableinfo)) {
            return '';
        }

        $course = $mod->get_course();
        $section = $mod->get_section_info($mod->section);

        $courseformat = course_get_format($course->id);
        $availabilityclass = $courseformat->get_output_classname('content\\cm\\availability');
        $availability = new $availabilityclass($courseformat, $section, $mod);

        return $OUTPUT->render($availability);
    }

    /**
     * Return the activity extensions for the user (by user and by group), if any.
     *
     * @param \cm_info $mod The course module info.
     * @param object $user The user object.
     * @param array $groups The user groups.
     * @return array The activity extensions for the user.
     */
    public static function get_activityextensions(\cm_info $mod, object $user, array $groups): array {
        global $DB, $USER;

        $extensions = [];

        $idgroupslist = '';
        if (!empty($groups)) {
            $groupids = array_keys($groups);
            $idgroupslist = implode(',', $groupids);
        }

        // Check if the user is not the current user. Get the extensions for all users in the current instance.
        $allusers = $user->id != $USER->id;

        $params = [];
        if (!$allusers) {
            $params['userid'] = $user->id;
        }

        switch ($mod->modname) {
            case 'assign':
                $params['assignment'] = $mod->instance;

                $extensions = $DB->get_records_menu('assign_user_flags', $params, 'extensionduedate', 'id, extensionduedate');

                $sql = "SELECT id, duedate
                        FROM {assign_overrides}
                        WHERE assignid = :assignment AND ";

                if (!$allusers) {
                    if (!empty($idgroupslist)) {
                        $sql .= "(userid = :userid OR groupid IN ($idgroupslist))";
                    } else {
                        $sql .= "userid = :userid";
                    }
                } else {
                    if (!empty($idgroupslist)) {
                        $sql .= "groupid IN ($idgroupslist)";
                    }
                }

                $sql .= " ORDER BY duedate";

                $extensionsassign = $DB->get_records_sql($sql, $params);

                foreach ($extensionsassign as $extension) {
                    $extensions[] = $extension->duedate;
                }
                break;
            case 'lesson':
                $params['lessonid'] = $mod->instance;

                $sql = "SELECT id, timelimit
                        FROM {lesson_overrides}
                        WHERE lessonid = :lessonid AND ";

                if (!$allusers) {
                    if (!empty($idgroupslist)) {
                        $sql .= "(userid = :userid OR groupid IN ($idgroupslist))";
                    } else {
                        $sql .= "userid = :userid";
                    }
                } else {
                    if (!empty($idgroupslist)) {
                        $sql .= "groupid IN ($idgroupslist)";
                    }
                }

                $sql .= " ORDER BY timelimit";

                $extensionslesson = $DB->get_records_sql($sql, $params);

                foreach ($extensionslesson as $extension) {
                    $extensions[] = $extension->timelimit;
                }
                break;
            case 'quiz':
                $params['quiz'] = $mod->instance;
                $sql = "SELECT id, timeclose
                        FROM {quiz_overrides}
                        WHERE quiz = :quiz AND ";

                if (!$allusers) {
                    if (!empty($idgroupslist)) {
                        $sql .= "(userid = :userid OR groupid IN ($idgroupslist))";
                    } else {
                        $sql .= "userid = :userid";
                    }
                } else {
                    if (!empty($idgroupslist)) {
                        $sql .= "groupid IN ($idgroupslist)";
                    }
                }

                $sql .= " ORDER BY timeclose";

                $extensionsquiz = $DB->get_records_sql($sql, $params);

                foreach ($extensionsquiz as $extension) {
                    $extensions[] = $extension->timeclose;
                }

                break;
        }

        return $extensions;
    }

    /**
     * Return the information about the activity state dates.
     *
     * @param string $state The state.
     * @param int $from The from date.
     * @param int $until The until date.
     * @return string The information about the activity state dates.
     */
    public static function get_activityinfodatelabel(string $state, int $from, int $until): string {
        $label = '';

        $strftimedate = get_string('strftimedateshort', 'langconfig');
        $strftimerecent = get_string('strftimedatetime', 'langconfig');
        $timeformat = get_string('strftimetime24', 'langconfig');

        if (!empty($until) && $until < time()) {
            $langkey = 'infodate_expired';
        } else {
            switch ($state) {
                case 'active':
                case 'pending':
                case 'blocked':
                case 'retarded':
                    $langkey = 'infodate_available';
                    break;
                case 'approved':
                case 'completed':
                case 'delivered':
                    $langkey = 'infodate_delivered';
                    break;
                case 'failed':
                case 'undelivered':
                    $langkey = 'infodate_expired';
                    break;
            }
        }

        if (!$langkey) {
            return '';
        }

        if (empty($until)) {
            $from = userdate($from, $strftimerecent);
            // phpcs:ignore
            // Mdlcode assume: $state ['infodate_available', 'infodate_delivered', 'infodate_expired'].
            $label = get_string($langkey . '_from', 'report_courseagenda', $from);
        } else if ($from < time()) {
            $until = userdate($until, $strftimerecent);
            // phpcs:ignore
            // Mdlcode assume: $state ['infodate_available', 'infodate_delivered', 'infodate_expired'].
            $label = get_string($langkey . '_until', 'report_courseagenda', $until);
        } else if (date('Y-m-d', $from) === date('Y-m-d', $until)) {
            $b = new \stdClass();
            $b->from = userdate($from, $timeformat);
            $b->until = userdate($until, $timeformat);
            $hours = get_string('timehoursrange', 'report_courseagenda', $b);
            $a = userdate($from, $strftimedate) . ' ' . $hours;
            // phpcs:ignore
            // Mdlcode assume: $state ['infodate_available', 'infodate_delivered', 'infodate_expired'].
            $label = get_string($langkey . '_on', 'report_courseagenda', $a);
        } else {
            $a = new \stdClass();
            $a->from = userdate($from, $strftimedate);
            $a->until = userdate($until, $strftimedate);
            // phpcs:ignore
            // Mdlcode assume: $state ['infodate_available', 'infodate_delivered', 'infodate_expired'].
            $label = get_string($langkey . '_between', 'report_courseagenda', $a);
        }

        return $label;
    }

    /**
     * Check if the activity was delivered by the user.
     *
     * @param \cm_info $mod The course module info.
     * @param object $user The user object.
     * @return int The activity delivered time or null.
     */
    public static function activitydelivered($mod, $user): ?int {
        global $DB;

        $delivered = null;

        switch ($mod->modname) {
            case 'assign':
                $params = [
                    'userid' => $user->id,
                    'assignmentid' => $mod->instance,
                ];
                $sql = "SELECT MAX(asb.timemodified) AS timecompleted
                        FROM {assign} a
                        INNER JOIN {assign_submission} asb ON a.id = :assignmentid AND a.id = asb.assignment
                        WHERE asb.userid = :userid AND asb.status = 'submitted'";
                $delivered = $DB->get_field_sql($sql, $params);
                break;
            case 'data':
                $params = [
                    'userid' => $user->id,
                    'dataid' => $mod->instance,
                ];
                $sql = "SELECT MAX(timemodified) AS timecompleted
                        FROM {data_records}
                        WHERE userid = :userid AND dataid = :dataid";
                $delivered = $DB->get_field_sql($sql, $params);
                break;
            case 'feedback':
                $params = [
                    'userid' => $user->id,
                    'feedback' => $mod->instance,
                ];
                $delivered = $DB->get_field('feedback_completed', 'timemodified', $params, IGNORE_MULTIPLE);
                break;
            case 'forum':
                $params = [
                    'userid' => $user->id,
                    'forumid' => $mod->instance,
                ];
                $sql = "SELECT MAX(p.modified) AS timecompleted
                        FROM {forum_posts} p
                        JOIN {forum_discussions} d ON d.forum = :forumid AND d.id = p.discussion AND p.userid = :userid";
                $delivered = $DB->get_field_sql($sql, $params);
                break;
            case 'glossary':
                $params = [
                    'userid' => $user->id,
                    'glossaryid' => $mod->instance,
                ];
                $sql = "SELECT MAX(timemodified) AS timecompleted
                        FROM {glossary_entries}
                        WHERE userid = :userid AND glossaryid = :glossaryid";
                $delivered = $DB->get_field_sql($sql, $params);
                break;
            case 'lesson':
                $params = [
                    'userid' => $user->id,
                    'lessonid' => $mod->instance,
                ];
                $sql = "SELECT MAX(completed) AS timecompleted
                        FROM {lesson_grades}
                        WHERE userid = :userid AND lessonid = :lessonid";
                $delivered = $DB->get_field_sql($sql, $params);
                break;
            case 'quiz':
                $params = [
                    'userid' => $user->id,
                    'quiz' => $mod->instance,
                    'state' => 'finished',
                ];
                $sql = "SELECT MAX(timemodified) AS timecompleted
                        FROM {quiz_attempts}
                        WHERE userid = :userid AND quiz = :quiz AND state = :state";
                $delivered = $DB->get_field_sql($sql, $params);
                break;
            case 'scorm':
                $elementid = $DB->get_field('scorm_element', 'id', ['element' => 'cmi.core.lesson_status']);
                if ($elementid) {
                    $params = [
                        'userid' => $user->id,
                        'scormid' => $mod->instance,
                        'elementid' => $elementid,
                    ];
                    $sql = "SELECT MAX(sv.timemodified) AS timecompleted
                            FROM {scorm_attempt} sa
                            INNER JOIN {scorm_scoes_value} sv ON sa.scormid = :scormid
                                AND sv.elementid = :elementid
                                AND sv.value IN ('passed', 'completed', 'failed')
                                AND sa.userid = :userid
                                AND sa.id = sv.attemptid";
                    $delivered = $DB->get_field_sql($sql, $params);
                } else {
                    $delivered = false;
                }
                break;
            case 'workshop':
                $params = [
                    'authorid' => $user->id,
                    'workshopid' => $mod->instance,
                ];
                $sql = "SELECT MAX(timemodified) AS timecompleted
                        FROM {workshop_submissions}
                        WHERE authorid = :authorid AND workshopid = :workshopid";
                $delivered = $DB->get_field_sql($sql, $params);
                break;
        }

        if (!$delivered) {
            $delivered = null;
        }

        return $delivered;
    }

    /**
     * Calculate the configured weight for a grade item from its parent category settings.
     *
     * This is used as a fallback when the user has no grades yet and the aggregation
     * weight has not been stored in grade_grades.
     *
     * @param \grade_item $gradeitem The grade item.
     * @return float|null The weight as a decimal (0-1), or null if it cannot be determined.
     */
    public static function get_item_configured_weight(\grade_item $gradeitem): ?float {
        $parent = $gradeitem->get_parent_category();
        if (!$parent) {
            return null;
        }

        $parent->load_grade_item();

        // Get all children grade items in the category (excluding the category item itself).
        $children = $parent->get_children();
        $siblings = [];
        foreach ($children as $child) {
            $childitem = $child['object'] instanceof \grade_item ? $child['object'] : $child['object']->get_grade_item();
            if ($childitem->id) {
                $siblings[$childitem->id] = $childitem;
            }
        }

        if (empty($siblings)) {
            return null;
        }

        switch ($parent->aggregation) {
            case GRADE_AGGREGATE_SUM:
                // Natural: weight is aggregationcoef2, normalized by the sum of all sibling weights.
                $sumweights = 0;
                foreach ($siblings as $sibling) {
                    if ($sibling->aggregationcoef <= 0 && $sibling->aggregationcoef2 > 0) {
                        $sumweights += $sibling->aggregationcoef2;
                    }
                }
                if ($sumweights > 0) {
                    return $gradeitem->aggregationcoef2 / $sumweights;
                }
                return 0;

            case GRADE_AGGREGATE_WEIGHTED_MEAN:
                // Weighted mean: weight is aggregationcoef, normalized.
                $totalcoef = 0;
                foreach ($siblings as $sibling) {
                    $totalcoef += $sibling->aggregationcoef;
                }
                if ($totalcoef > 0) {
                    return $gradeitem->aggregationcoef / $totalcoef;
                }
                return 0;

            case GRADE_AGGREGATE_WEIGHTED_MEAN2:
                // Simple weighted mean: weight is based on grade range.
                if ($gradeitem->aggregationcoef > 0) {
                    // Extra credit item.
                    return null;
                }
                $totalrange = 0;
                foreach ($siblings as $sibling) {
                    if ($sibling->aggregationcoef <= 0) {
                        $totalrange += ($sibling->grademax - $sibling->grademin);
                    }
                }
                $range = $gradeitem->grademax - $gradeitem->grademin;
                if ($totalrange > 0) {
                    return $range / $totalrange;
                }
                return 0;

            case GRADE_AGGREGATE_MEAN:
            case GRADE_AGGREGATE_EXTRACREDIT_MEAN:
                // Simple mean: equal weight for all items.
                $count = 0;
                foreach ($siblings as $sibling) {
                    if ($parent->aggregation == GRADE_AGGREGATE_MEAN || $sibling->aggregationcoef == 0) {
                        $count++;
                    }
                }
                if ($count > 0) {
                    return 1.0 / $count;
                }
                return 0;

            default:
                return null;
        }
    }

    /**
     * Format the number with the configured decimal points.
     *
     * @param float $number The number to format.
     * @return string The formatted number.
     */
    public static function format_number($number): string {
        global $CFG;

        $decimals = 2;
        if (property_exists($CFG, 'grade_decimalpoints')) {
            $decimals = $CFG->grade_decimalpoints;
        }

        if ($number === 0.0 || $number === 0 || $number === '0') {
            return '0';
        }

        return format_float($number, $decimals, true);
    }

    /**
     * Return the name of the grade item.
     *
     * @param object $gradeitem The grade item object.
     * @param bool $multi If false, return the generic weighing name, if true return the name of the item with the module name.
     * @return string The name of the grade item.
     */
    public static function itemname(object $gradeitem, $multi = false): string {

        $name = get_string('weighing', 'report_courseagenda');
        if (!$multi) {
            return $name;
        }

        switch ($gradeitem->item->itemmodule) {
            case 'forum':
                $name = get_string(
                    ($gradeitem->item->itemnumber == 0 ? 'forum_rating' : 'forum_wholeforum'),
                    'report_courseagenda'
                );
                break;
            case 'workshop':
                $name = get_string(
                    ($gradeitem->item->itemnumber == 0 ? 'workshopname_submission' : 'workshopname_assessment'),
                    'report_courseagenda'
                );
                break;
        }

        return $name;
    }

    /**
     * Check if a user can be graded in a course.
     * Based in LTI Gradebook Services.
     *
     * @param int $courseid The course
     * @param int $userid The user
     * @return bool
     */
    public static function is_user_gradable_in_course($courseid, $userid) {
        global $CFG;

        $gradableuser = false;
        $coursecontext = \context_course::instance($courseid);
        if (is_enrolled($coursecontext, $userid, '', false)) {
            $roles = get_user_roles($coursecontext, $userid);
            $gradebookroles = explode(',', $CFG->gradebookroles);
            foreach ($roles as $role) {
                foreach ($gradebookroles as $gradebookrole) {
                    if ($role->roleid === $gradebookrole) {
                        $gradableuser = true;
                    }
                }
            }
        }

        return $gradableuser;
    }
}
