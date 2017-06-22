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
 * Webservice helper class containing functions for the enrolment data type.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_globalfilter\datatypes;

defined('MOODLE_INTERNAL') || die();

/**
 * Enrolment functions
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class enrolment extends datatype_base {

    /**
     * Internal function defining a enrolment structure for use in "_returns" definitions.
     *
     * @return \external_multiple_structure
     */

    public static function structure() {
        return new \external_single_structure(
            ['courseid' => new \external_value(PARAM_INT, 'Course id.'),
             'enroltime' => new \external_value(PARAM_INT, 'When enrolled in course timestamp.'),
             'starttime' => new \external_value(PARAM_INT, 'When started activity in course timestamp.'),
             'endtime' => new \external_value(PARAM_INT, 'When ended course timestamp.'),
             'lastaccess' => new \external_value(PARAM_INT, 'When last accessed course timestamp.'),
             'competencies' => new \external_multiple_structure(competency_instance::structure(), 'competencies'),
             'outcomes' => new \external_multiple_structure(outcome_instance::structure(), 'outcomes'),
            ],
            'courseenrolment'
        );
    }

    /**
     * Internal function to return the user enrolments structure for the user id list.
     *
     * @param array $dataids The array of user id's to get enrolments for.
     * @param string $type Optional type to delegate other functions for.
     * @return array The enrolments structure.
     */
    public static function get_data($dataids = [], $type = null) {
        global $DB;

        if ($type === null) {
            $type = 'user';
        }

        if ($type != 'user') {
            throw new coding_exception('Unknown data type: '.$type);
        }

        $cnd = '';
        $params = [];
        if (!empty($dataids)) {
            list($cnd, $params) = $DB->get_in_or_equal($dataids);
            $cnd = ' AND ue.userid ' . $cnd;
        }

        $ccselect = ', ' . \context_helper::get_preload_record_columns_sql('ctx') . ' ';
        $select = 'SELECT en.id, c.id as courseid, en.userid, en.timecreated, en.timestart, en.timeend' . $ccselect;
        $from = 'FROM {course} c ';
        $join = 'JOIN (SELECT DISTINCT e.courseid, ue.id, ue.userid, ue.timecreated, ue.timestart, ue.timeend ' .
                    'FROM {enrol} e ' .
                    'JOIN {user_enrolments} ue ON (ue.enrolid = e.id' . $cnd .') '.
                    ') en ON (en.courseid = c.id) ';
        $join .= 'LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = ?) ';
        $where = 'WHERE c.id != ? ';
        $order = 'ORDER BY en.userid ASC ';
        $params = array_merge($params, [CONTEXT_COURSE, SITEID]);
        $sql = $select . $from . $join . $where . $order;

        $enrolsrs = $DB->get_recordset_sql($sql, $params);
        $fields = ['courseid' => 'int', 'enroltime' => 'int:timecreated',
            'starttime' => 'int:timestart', 'endtime' => 'int:timeend'];
        $enrols = self::create_data_structure($enrolsrs, 'userid', $fields);
        $enrolsrs->close();

        foreach ($enrols as $useridx => $enrolrecs) {
            foreach ($enrolrecs as $courseidx => $courserecs) {
                $enrols[$useridx][$courseidx]['lastaccess'] = 0;
                $enrols[$useridx][$courseidx]['competencies'] = [];
                $enrols[$useridx][$courseidx]['outcomes'] = [];
            }
        }

        return $enrols;
    }
}