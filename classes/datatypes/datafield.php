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
 * Webservice helper class containing functions for the datafield data type.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_globalfilter\datatypes;

defined('MOODLE_INTERNAL') || die();

/**
 * Datafield functions
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class datafield extends datatype_base {

    /**
     * Internal function defining a datafield structure for use in "_returns" definitions.
     *
     * @return \external_multiple_structure
     */

    public static function structure() {
        return new \external_single_structure(
            ['name' => new \external_value(PARAM_TEXT, 'Name of this field.'),
             'description' => new \external_value(PARAM_RAW, 'Description of this field.'),
             'value' => new \external_value(PARAM_RAW, 'Value of this field for this user.'),
            ],
            'datafield'
        );
    }

    /**
     * Internal function to return the user datafields structure for the user id list.
     *
     * @param array $dataids The array of user id's to get datafields for.
     * @param string $type Optional type to delegate other functions for.
     * @param array $extra Optional extra parameters to be used by the implementation.
     * @return array The datafields structure.
     */
    public static function get_data($dataids = [], $type = null, $extra = null) {
        global $DB;

        if ($type === null) {
            $type = 'user';
        }

        if ($type != 'user') {
            throw new \coding_exception('Unknown data type: '.$type);
        }

        $cnd = '';
        $params = [];
        if (!empty($extra) && !empty($extra['lastuserid'])) {
            $cnd = 'uid.userid > ? ';
            $params = [$extra['lastuserid']];
        } else if (!empty($dataids)) {
            list($cnd, $params) = $DB->get_in_or_equal($dataids);
            $cnd = 'uid.userid ' . $cnd . ' ';
        }

        $select = 'SELECT uid.id,uid.userid,uid.fieldid,uid.data,uid.dataformat,uif.name,uif.description,uif.descriptionformat ';
        $from = 'FROM {user_info_data} uid ';
        $join = 'INNER JOIN {user_info_field} uif ON uid.fieldid = uif.id ';
        $where = !empty($cnd) ? 'WHERE ' . $cnd : '';
        $order = 'ORDER BY uid.userid ASC ';
        $sql = $select . $from . $join . $where . $order;

        $dfsrs = $DB->get_recordset_sql($sql, $params);
        $fields = ['name' => 'string:name', 'description' => 'text:description', 'value' => 'text:data'];
        $datafields = self::create_data_structure($dfsrs, 'userid', $fields);
        $dfsrs->close();

        return $datafields;
    }
}