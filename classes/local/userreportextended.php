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
 * Class userreportextended
 *
 * @package    report_courseagenda
 * @copyright  2025 David Herney @ BambuCo
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class userreportextended extends \gradereport_user\report\user {

    /**
     * Optionally blank out course/category totals if they contain any hidden items
     *
     * Only used to make public the protected method blank_hidden_total_and_adjust_bounds.
     *
     * @param string $courseid the course id
     * @param string $course_item an instance of grade_item
     * @param string $finalgrade the grade for the course_item
     * @return array[] containing values for 'grade', 'grademax', 'grademin', 'aggregationstatus' and 'aggregationweight'
     */
    public function get_blank_hidden_total_and_adjust_bounds($courseid, $course_item, $finalgrade) {
        return parent::blank_hidden_total_and_adjust_bounds($courseid, $course_item, $finalgrade);
    }
}
