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
 * Course object functions
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class courseobject extends datatype_base {

    /**
     * Internal function defining a enrolment structure for use in "_returns" definitions.
     *
     * @return \external_multiple_structure
     */

    public static function structure() {
        return new \external_single_structure(
            ['courseid' => new \external_value(PARAM_INT, 'Course id'),
             'courseobjects' => new \external_multiple_structure(
                new \external_single_structure(
                    ['courseobjectid' => new \external_value(PARAM_INT, 'Course object id'),
                     'name' => new \external_value(PARAM_TEXT, 'Course object name.'),
                     'description' => new \external_value(PARAM_RAW, 'Course object description.'),
                     'type' => new \external_value(PARAM_TEXT, 'Course object type.'),
                     'tags' => new \external_multiple_structure(tag::structure(), 'tags'),
                     'competencies' => new \external_multiple_structure(competency::structure(), 'competencies'),
                     'outcomes' => new \external_multiple_structure(outcome::structure(), 'outcomes'),
                    ],
                    'course object'
                ),
                'courseobjects'
             ),
            ],
            'course'
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
            throw new \coding_exception('Unknown data type: '.$type);
        }

        $cnd = '';
        $params = [];
        if (!empty($dataids)) {
            list($cnd, $params) = $DB->get_in_or_equal($dataids);
            $cnd = ' cm.course ' . $cnd . ' ';
        }

        $tags = tag::get_data([], 'courseobject');
        $competencies = competency::get_data([], 'courseobject');
        $outcomes = outcome::get_data([], 'courseobject');

        $select = 'SELECT cm.id, cm.course, mm.name as type, m.name, m.intro, m.introformat ';
        $from = 'FROM {course_modules} cm ';
        $join = 'INNER JOIN {modules} mm ON mm.name = ? ';
        $join .= 'INNER JOIN {' . $type . '} m ON cm.instance = m.id AND cm.course = m.course AND cm.module = mm.id ';
        $where = empty($cnd) ? $cnd : 'WHERE ' . $cnd;
        $order = 'ORDER BY cm.course ASC ';
        $sql = $select . $from . $join . $where . $order;
        $params = array_merge([$type], $params);
        $moduleinfors = $DB->get_recordset_sql($sql, $params);
        $fields = ['courseobjectid' => 'int:id', 'name' => 'string', 'description' => 'text:intro', 'type' => 'string'];
        $courseobjects = self::create_data_structure($moduleinfors, 'course', $fields);
        foreach ($courseobjects as $courseid => $courseobject) {
            foreach ($courseobject as $objidx => $objectinstance) {
                $objectinstance['tags'] = isset(
                    $tags[$objectinstance['courseobjectid']]) ? $tags[$objectinstance['courseobjectid']] : [];
                $objectinstance['competencies'] = isset(
                    $competencies[$objectinstance['courseobjectid']]) ? $competencies[$objectinstance['courseobjectid']] : [];
                $objectinstance['outcomes'] = isset(
                    $outcomes[$objectinstance['courseobjectid']]) ? $outcomes[$objectinstance['courseobjectid']] : [];
                $courseobjects[$courseid][$objidx] = $objectinstance;
            }
        }
        $moduleinfors->close();
        return $courseobjects;
    }
}