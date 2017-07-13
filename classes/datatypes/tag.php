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
 * Webservice helper class containing functions for the tag data type.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_globalfilter\datatypes;

defined('MOODLE_INTERNAL') || die();

/**
 * Tag functions.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class tag extends datatype_base {

    /**
     * Internal function defining a tag structure for use in "_returns" definitions.
     *
     * @return \external_multiple_structure
     */

    public static function structure() {
        return new \external_single_structure(
            ['name' => new \external_value(PARAM_TEXT, 'Name of this tag.'),
             'description' => new \external_value(PARAM_RAW, 'Description of this tag.'),
            ],
            'tag'
        );
    }

    /**
     * Internal function to return the tags structure for the tag item id list.
     *
     * @param array $dataids The array of tag item id's to get tags for.
     * @param string $type Optional type to delegate other functions for.
     * @return array The datafields structure.
     */
    public static function get_data($dataids = [], $type = null) {
        global $DB;

        $validtypes = ['user', 'course', 'courseobject'];
        if ($type === null) {
            $type = 'user';
        } else if (!in_array($type, $validtypes)) {
            throw new \coding_exception('Unknown tag type: '.$type);
            return false;
        }

        $cnd = '';
        $params = [];
        if (!empty($dataids)) {
            list($cnd, $params) = $DB->get_in_or_equal($dataids);
            $cnd = 'AND ti.itemid ' . $cnd . ' ';
        }
        $params = array_merge([$type], $params);

        $select = 'SELECT ti.id, ti.tagid, ti.itemid, t.rawname, t.description, t.descriptionformat ';
        $from = 'FROM {tag_instance} ti ';
        $join = 'INNER JOIN {tag} t ON ti.tagid = t.id ';
        $where = 'WHERE ti.itemtype = ? ' . $cnd;
        $order = 'ORDER BY ti.itemid ASC ';
        $sql = $select . $from . $join . $where . $order;

        $tagsrs = $DB->get_recordset_sql($sql, $params);
        $fields = ['name' => 'string:rawname', 'description' => 'text:description'];
        $tags = self::create_data_structure($tagsrs, 'itemid', $fields);
        $tagsrs->close();

        return $tags;
    }
}