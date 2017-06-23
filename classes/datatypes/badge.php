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
 * Webservice helper class containing functions for the badge data type.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_globalfilter\datatypes;

defined('MOODLE_INTERNAL') || die();

/**
 * Badge functions.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class badge extends datatype_base {

    /**
     * Internal function defining a badge structure for use in "_returns" definitions.
     *
     * @return \external_multiple_structure
     */

    public static function structure() {
        $structarray = self::basic_structure();
        return new \external_single_structure($structarray, 'badge');
    }

    /**
     * Allow extended classes to use the basic structure, and add-on before returning.
     * @return array The basic structure.
     */
    protected static function basic_structure() {
        return ['id' => new \external_value(PARAM_INT, 'Badge id.'),
                'name' => new \external_value(PARAM_TEXT, 'Name of this badge.'),
                'description' => new \external_value(PARAM_RAW, 'Description of this badge.')
               ];
    }

    /**
     * Internal function to return the badges structure for the data id list.
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
            throw new \coding_exception('Unknown badge type: '.$type);
            return false;
        }
    }

    /**
     * Internal function to return the user badges structure for the user id list.
     *
     * @param array $dataids The array of user id's to get badges for.
     * @return array The datafields structure.
     */
    private static function get_user_data($dataids) {
        global $DB;

        $cnd = '';
        $params = [];
        if (!empty($dataids)) {
            list($cnd, $params) = $DB->get_in_or_equal($dataids);
            $cnd = 'bi.userid ' . $cnd . ' ';
        }

        $select = 'SELECT bi.id, bi.userid, bi.badgeid, bi.dateissued, b.name, b.description ';
        $from = 'FROM {badge_issued} bi ';
        $join = 'INNER JOIN {badge} b ON bi.badgeid = b.id ';
        $where = !empty($cnd) ? 'WHERE ' . $cnd : '';
        $order = 'ORDER BY bi.userid ASC ';
        $sql = $select . $from . $join . $where . $order;

        $badgesrs = $DB->get_recordset_sql($sql, $params);
        $fields = ['id' => 'int', 'name' => 'string', 'description' => 'text', 'issuedtime' => 'int:dateissued'];
        $badges = self::create_data_structure($badgesrs, 'userid', $fields);
        $badgesrs->close();

        return $badges;
    }

    /**
     * Internal function to return the course badges structure for the course id list.
     *
     * @param array $dataids The array of course id's to get badges for.
     * @return array The badges structure.
     */
    private static function get_course_data($dataids) {
        global $DB;

        $cnd2 = '';
        $params = [];
        if (!empty($dataids)) {
            list($cnd2, $params) = $DB->get_in_or_equal($dataids);
            $cnd2 = 'AND bcp.value ' . $cnd2 . ' ';
        }
        $cnd1 = $DB->sql_like('bcp.name', '?') . ' ';
        $params = array_merge(['course_%'], $params);

        $select = 'SELECT bcp.id, bcp.value, bc.badgeid, b.name, b.description ';
        $from = 'FROM {badge_criteria_param} bcp ';
        $join = 'INNER JOIN {badge_criteria} bc ON bcp.critid = bc.id ' .
                'INNER JOIN {badge} b ON bc.badgeid = b.id ';
        $where = 'WHERE ' . $cnd1 . $cnd2;
        $order = 'ORDER BY bcp.value ASC ';
        $sql = $select . $from . $join . $where . $order;

        $badgesrs = $DB->get_recordset_sql($sql, $params);
        $fields = ['id' => 'int', 'name' => 'string', 'description' => 'text'];
        $badges = self::create_data_structure($badgesrs, 'value', $fields);
        $badgesrs->close();

        return $badges;
    }
}