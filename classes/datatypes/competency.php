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
 * Webservice helper class containing functions for the competency data type.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_globalfilter\datatypes;

defined('MOODLE_INTERNAL') || die();

/**
 * Competency functions.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class competency extends datatype_base {

    /**
     * Internal function defining a outcome structure for use in "_returns" definitions.
     *
     * @return \external_multiple_structure
     */

    public static function structure() {
        $structarray = self::basic_structure();
        return new \external_single_structure($structarray, 'competency');
    }

    /**
     * Allow extended classes to use the basic structure, and add-on before returning.
     * @return array The basic structure.
     */
    protected static function basic_structure() {
        return ['id' => new \external_value(PARAM_INT, 'Competency id.'),
                'name' => new \external_value(PARAM_TEXT, 'Name of this competency.'),
                'description' => new \external_value(PARAM_RAW, 'Description of this competency.'),
               ];
    }

    /**
     * Internal function to return the competencies structure for the data id list.
     *
     * @param array $dataids The array of user id's to get datafields for.
     * @param string $type Optional type to delegate other functions for.
     * @return array The datafields structure.
     */
    public static function get_data($dataids = [], $type = null) {
        if ($type === null) {
            $type = 'user';
        }

        if ($type == 'user') {
            return self::get_user_data($dataids);
        } else if ($type == 'course') {
            return self::get_course_data($dataids);
        } else {
            throw new \coding_exception('Unknown outcome type: '.$type);
            return false;
        }
    }

    /**
     * Internal function to return the user competencies structure for the user id list.
     *
     * @param array $dataids The array of user id's to get competencies for.
     * @return array The datafields structure.
     */
    private static function get_user_data($dataids) {
        return [];
    }

    /**
     * Internal function to return the competencies structure for the course id list.
     *
     * @param array $dataids The array of course id's to get competencies for.
     * @return array The badges structure.
     */
    private static function get_course_data($dataids) {
        global $DB;

        $cnd = '';
        $params = [];
        if (!empty($dataids)) {
            list($cnd, $params) = $DB->get_in_or_equal($dataids);
            $cnd = 'cc.courseid ' . $cnd . ' ';
        }

        $select = 'SELECT cc.id, cc.courseid, c.shortname, c.description, c.descriptionformat ';
        $from = 'FROM {competency_coursecomp} cc ';
        $join = 'INNER JOIN {competency} c ON cc.competencyid = c.id ';
        $where = !empty($sql) ? 'WHERE ' . $cnd : '';
        $order = 'ORDER BY cc.courseid ASC ';
        $sql = $select . $from . $join . $where . $order;

        $competenciesrs = $DB->get_recordset_sql($sql, $params);
        $fields = ['id' => 'int', 'name' => 'string:shortname', 'description' => 'text'];
        $competencies = self::create_data_structure($competenciesrs, 'courseid', $fields);
        $competenciesrs->close();

        return $competencies;
    }
}