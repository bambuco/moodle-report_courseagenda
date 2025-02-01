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
     * Return the modules in the platform.
     *
     * @param bool $onlyvisible If true, only the visible modules will be returned.
     * @return array The modules.
     */
    public static function get_modules(bool $onlyvisible = false): array {
        global $DB;

        if (empty(self::$modules)) {
            self::$modules = $DB->get_records('modules');
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
            list($excludein, $params) = $DB->get_in_or_equal($excludemodules, SQL_PARAMS_NAMED, 'param', false);
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

        $coursesections = $modinfo->get_section_info_all();
        $hiddensections = property_exists($course, 'hiddensections') && $course->hiddensections != 1;

        $usergroups = groups_get_all_groups($course->id, $user->id);

        if (!$hiddensections) {
            $hiddensections = has_capability('moodle/course:viewhiddensections', $context, $USER);
        }

        $reportconfig = get_config('report_courseagenda');
        $includesection0 = $reportconfig->includesection0;

        $completioninfo = new \completion_info($course);

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
            list($excludein, $params) = $DB->get_in_or_equal($excludemodules, SQL_PARAMS_NAMED, 'param', false);
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

                $sectionmods = explode(",", $coursesection->sequence);

                foreach ($sectionmods as $modnumber) {
                    $notcompletion = false;

                    if (empty($modinfo->cms[$modnumber])) {
                        continue;
                    }

                    $mod = $modinfo->cms[$modnumber];
                    $moduletype = $modules[$mod->module]->name;

                    if (in_array($mod->modname, $excludemodules)) {
                        continue;
                    }

                    // The activity don't have completion tracking.
                    if ($completioninfo->is_enabled($mod) === COMPLETION_TRACKING_NONE) {
                        $notcompletion = true;
                    }

                    $gradeitem = $activitiesgrades[$moduletype . '-' . $mod->instance] ?? null;

                    $cmdata = new \stdClass();
                    $cmdata->instancename = format_string($mod->name, true, $course->id);
                    $cmdata->uniqueid = 'cm_' . $mod->id . '_' . time() . '_' . rand(0, 1000);
                    $cmdata->url = $mod->url;
                    $cmdata->hascompletion = false;
                    $cmdata->state = $notcompletion ? self::STATE_ACTIVE : self::STATE_PENDING;
                    $cmdata->enabled = $completioninfo->is_enabled($mod);
                    $cmdata->activityinfodatelabel = '';
                    $cmdata->modurl = new \moodle_url('/mod/' . $mod->modname . '/view.php', ['id' => $mod->id]);

                    // phpcs:ignore
                    // Mdlcode assume: $moduletype ['assign', 'book', 'data'].
                    $cmdata->activitytype = get_string('pluginname', $moduletype);

                    $infodates = self::activity_enabledate($mod);
                    $cmdata->infodates = $infodates;

                    if (!$infodates->until) {
                        $infodates->until = $course->enddate;
                    }
                    $infodates->untilformated = userdate($infodates->until, $dateshort);

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
                        switch ($completionmodules[$mod->id]) {
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

                    $infostate = null;
                    $infodatefrom = $infodates->from;
                    $infodateuntil = $infodates->until;
                    switch ($cmdata->state) {
                        case self::STATE_PENDING:
                        case self::STATE_RETARDED:
                            $infostate = ($infodates->until - time()) / (60 * 60 * 24);
                            $infostate = round($infostate);

                            if ($infostate <= $reportconfig->daystosendactivity) {
                                $cmdata->fullstatename = get_string('fullstate_pendingdays', 'report_courseagenda', $infostate);
                            }
                            break;
                        case self::STATE_UNDELIVERED:
                            break;
                        case self::STATE_BLOCKED:
                            break;
                        case self::STATE_DELIVERED:
                            break;
                        case self::STATE_APPROVED:
                            break;
                        case self::STATE_FAILED:
                            break;
                        case self::STATE_COMPLETED:
                            $infodateuntil = userdate($completionmodules[$mod->id]->timemodified);
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

                    if ($completioninfo->is_enabled($mod) !== COMPLETION_TRACKING_NONE) {
                        $cmdata->hascompletion = true;
                        $cmdata->completed = $DB->get_record('course_modules_completion',
                                                    ['coursemoduleid' => $mod->id, 'userid' => $user->id, 'completionstate' => 1]);

                        $cmdata->showcompletionconditions = $course->showcompletionconditions == COMPLETION_SHOW_CONDITIONS;

                    }

                    $cmdata->stateicon = self::get_state_iconhtml($cmdata->state);

                    // Start Grade information.
                    $cmdata->gradeitem = $gradeitem;
                    $cmdata->showgrade = false;
                    $cmdata->weighing = '0%';

                    $modgraded = false;
                    if ($cmdata->gradeitem) {
                        $cmdata->currentgrade = [];
                        $weighing = 0;
                        foreach ($cmdata->gradeitem as $gradeitem) {
                            $currentgrade = new \stdClass();
                            $currentgrade->weighing = $gradeitem->info->weightformatted;
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
                            }

                            $cmdata->currentgrade[] = $currentgrade;
                        }

                        $cmdata->weighing = !empty($cmdata->gradeitem) ? $weighing : 0;
                        $cmdata->weighing = round($cmdata->weighing * 100, 0);
                        $cmdata->weighing .= '%';

                    }

                    if (!$modgraded) {
                        if (self::require_feedbackgrade($mod, $user)) {
                            $infodates->feedbackdate = $infodates->until + $daystograde;
                            $infodates->feedbackdateformated = userdate($infodates->feedbackdate, $dateoneday);
                        } else {
                            $infodates->feedbackdate = 0;
                            $infodates->feedbackdateformated = get_string('automaticgrade', 'report_courseagenda');
                        }
                    } else {
                        $infodates->feedbackdate = 0;
                        $infodates->feedbackdateformated = get_string('graded', 'report_courseagenda');
                    }
                    // End of Grade information.

                    // Module availability.
                    $cmdata->restrictions = self::get_activityrestrictions($mod);

                    $section->activities[] = $cmdata;
                }
            }

            $sections[] = $section;
        }

        return $sections;
    }

    /**
     * Return the enable date of an activity.
     *
     * @param \cm_info $mod The course module info.
     * @return object The enable dates of the activity: from, until.
     */
    public static function activity_enabledate(\cm_info $mod): object {
        $dates = (object)[
            'from' => 0,
            'until' => 0,
        ];

        if (!empty($mod->availablefrom)) {
            $dates->from = $mod->availablefrom;
            return $dates;
        }

        $cmdata = $mod->customdata;

        if (is_string($cmdata)) {
            return $dates;
        }

        if (is_array($cmdata)) {
            $cmdata = (object)$cmdata;
        }

        switch ($mod->modname) {
            case 'assign':
                $dates->from = $cmdata->allowsubmissionsfromdate ?? '';
                $dates->until = $cmdata->duedate ?? '';
                break;
            case 'data':
                $dates->from = $cmdata->timeavailablefrom ?? '';
                $dates->until = $cmdata->timeavailableto ?? '';
                break;
            case 'feedback':
                $dates->from = $cmdata->timeopen ?? '';
                $dates->until = $cmdata->timeclose ?? '';
                break;
            case 'forum':
                $dates->until = $cmdata->duedate ?? '';
                break;
            case 'lesson':
                $dates->from = $cmdata->available ?? '';
                $dates->until = $cmdata->deadline ?? '';
                break;
            case 'quiz':
                $dates->from = $cmdata->timeopen ?? '';
                $dates->until = $cmdata->timeclose ?? '';
                break;
            case 'scorm':
                $dates->from = $cmdata->timeopen ?? '';
                $dates->until = $cmdata->timeclose ?? '';
                break;
            case 'workshop':
                $dates->from = $cmdata->submissionstart ?? '';
                $dates->until = $cmdata->submissionend ?? '';
                break;
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

            if (in_array($gradeitem->itemmodule, $excludemodules)) {
                continue;
            }

            // Sort by module and instance id to make it easier to access them.
            $gradeinfokey = $gradeitem->itemmodule . '-' . $gradeitem->iteminstance;
            if (!isset($gradesinfo[$gradeinfokey])) {
                $gradesinfo[$gradeinfokey] = [];
            }
            $customgradeinfo = new \stdClass();
            $gradesinfo[$gradeinfokey][] = $customgradeinfo;
            $customgradeinfo->info = new \stdClass();
            $customgradeinfo->info->visible = true;

            // Process the grade type.
            if (!$gradegrade = \grade_grade::fetch(['itemid' => $gradeitem->id, 'userid' => $user->id])) {
                $gradegrade = new \grade_grade();
                $gradegrade->userid = $user->id;
                $gradegrade->itemid = $gradeitem->id;
            }

            $gradegrade->load_grade_item();
            $gradeitem = $gradegrade->grade_item;
            $customgradeinfo->item = $gradeitem;

            // Hidden Items.
            if ($gradeitem->is_hidden()) {
                $customgradeinfo->info->visible = false;
                continue;
            }

            // If this is a hidden grade item, hide it completely from the user.
            if ($gradegrade->is_hidden() && !$canviewhidden && (
                $showhiddenitems == GRADE_REPORT_USER_HIDE_HIDDEN ||
                ($showhiddenitems == GRADE_REPORT_USER_HIDE_UNTIL && !$gradegrade->is_hiddenuntil()))) {
                    $customgradeinfo->info->visible = false;
                    continue;
            }

            // Actual Grade - We need to calculate this whether.
            $gradeval = $gradegrade->finalgrade;
            $hint = $gradegrade->get_aggregation_hint();
            if (!$canviewhidden) {
                // Virtual Grade (may be calculated excluding hidden items etc).
                $adjustedgrade = $report->get_blank_hidden_total_and_adjust_bounds($course->id,
                    $gradegrade->grade_item,
                    $gradeval);

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

            // Basic grade item information.
            $customgradeinfo->info->locked = $canviewall ? $gradegrade->grade_item->is_locked() : null;
            $customgradeinfo->info->overridden = $gradegrade->is_overridden();
            $customgradeinfo->info->excluded = $gradegrade->is_excluded();
            $customgradeinfo->info->gradestatus = '';
            $customgradeinfo->info->gradecontent = '';
            $customgradeinfo->info->grademethod = '';
            $customgradeinfo->info->weightformatted = '0%';
            $customgradeinfo->info->weightraw = 0;

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

            // This obliterates the weight because it provides a more informative description.
            if (is_numeric($hint['weight'])) {
                $customgradeinfo->info->weightraw = $hint['weight'];
                $customgradeinfo->info->weightformatted = format_float($hint['weight'] * 100.0, 0) . '%';
            }
            if ($hint['status'] != 'used' && $hint['status'] != 'unknown') {
                $customgradeinfo->info->status = $hint['status'];
            }

            $gradestatus = '';

            $context = [
                'hidden' => $gradegrade->is_hidden(),
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
                && $gradegrade->is_hidden()
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
            } else if ($gradegrade->is_hidden()) {
                $customgradeinfo->info->gradecontent = '-';

                if ($canviewhidden) {
                    $customgradeinfo->info->graderaw = $gradeval;
                    $customgradeinfo->info->gradecontent = grade_format_gradevalue($gradeval,
                        $gradegrade->grade_item,
                        true) . $gradestatus;
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
                $customgradeinfo->info->gradecontent = $gradepassicon . grade_format_gradevalue($gradeval,
                        $gradegrade->grade_item, true) . $gradestatus;
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
        global $DB;

        $extensions = [];

        $idgroupslist = '';
        if (!empty($groups)) {
            $groupids = array_keys($groups);
            $idgroupslist = implode(',', $groupids);
        }

        switch ($mod->modname) {
            case 'assign':
                $params = [
                    'userid' => $user->id,
                    'assignment' => $mod->instance,
                ];
                $extensions = $DB->get_records_menu('assign_user_flags', $params, 'extensionduedate', 'id, extensionduedate');

                $sql = "SELECT id, duedate
                        FROM {assign_overrides}
                        WHERE assignid = :assignment AND ";

                if (!empty($idgroupslist)) {
                    $sql .= "(userid = :userid OR groupid IN ($idgroupslist))";
                } else {
                    $sql .= "userid = :userid";
                }

                $sql .= " ORDER BY duedate";

                $extensionsassign = $DB->get_records_sql($sql, $params);

                foreach ($extensionsassign as $extension) {
                    $extensions[] = $extension->duedate;
                }
                break;
            case 'lesson':
                $params = [
                    'userid' => $user->id,
                    'lessonid' => $mod->instance,
                ];
                $sql = "SELECT id, timelimit
                        FROM {lesson_overrides}
                        WHERE lessonid = :lessonid AND ";

                if (!empty($idgroupslist)) {
                    $sql .= "(userid = :userid OR groupid IN ($idgroupslist))";
                } else {
                    $sql .= "userid = :userid";
                }

                $sql .= " ORDER BY timelimit";

                $extensionslesson = $DB->get_records_sql($sql, $params);

                foreach ($extensionslesson as $extension) {
                    $extensions[] = $extension->timelimit;
                }
                break;
            case 'quiz':
                $params = [
                    'userid' => $user->id,
                    'quiz' => $mod->instance,
                ];
                $sql = "SELECT id, timeclose
                        FROM {quiz_overrides}
                        WHERE quiz = :quiz AND ";

                if (!empty($idgroupslist)) {
                    $sql .= "(userid = :userid OR groupid IN ($idgroupslist))";
                } else {
                    $sql .= "userid = :userid";
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
        $strftimerecent = get_string('strftimerecent', 'langconfig');
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

}
