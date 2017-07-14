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
 * Webservice helper class containing functions for the event data type.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_globalfilter\datatypes;

defined('MOODLE_INTERNAL') || die();

/**
 * Event functions.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class event extends datatype_base {

    /**
     * Internal function defining a event structure for use in "_returns" definitions.
     *
     * @return \external_multiple_structure
     */

    public static function structure() {
        return new \external_single_structure(
            ['eventname' => new \external_value(PARAM_TEXT, 'Event name.'),
             'component' => new \external_value(PARAM_TEXT, 'Component name.'),
             'action' => new \external_value(PARAM_TEXT, 'The event action.'),
             'courseobjectid' => new \external_value(PARAM_INT, 'Course object id.'),
             'courseid' => new \external_value(PARAM_INT, 'Course id.'),
             'eventtime' => new \external_value(PARAM_INT, 'Event activity timestamp.'),
            ],
            'event'
        );
    }

    /**
     * Internal function to return the events structure for the data id list.
     *
     * @param array $dataids The array of user id's to get events for.
     * @param string $type Optional type to delegate other functions for.
     * @param array $extra Optional extra parameters to be used by the implementation.
     * @return array The datafields structure.
     */
    public static function get_data($dataids = [], $type = null, $extra = null) {
        global $DB;

        $actions = ['loggedin', 'loggedout', 'searched', 'started', 'submitted', 'updated', 'uploaded', 'viewed'];
        list($cnd, $params) = $DB->get_in_or_equal($actions);
        $cnd = '(l.action ' . $cnd . ') ';

        if (!empty($dataids)) {
            list($cnd2, $params2) = $DB->get_in_or_equal($dataids);
            $cnd .= ' AND (l.userid ' . $cnd2 . ') ';
            $params = array_merge($params, $params2);
        }

        $select = 'SELECT l.id, l.userid, l.eventname, l.component, l.action, l.contextinstanceid, l.courseid, l.timecreated ';
        $from = 'FROM {logstore_standard_log} l ';
        $join = '';
        $where = 'WHERE (l.userid > 0) AND ' . $cnd;
        $order = 'ORDER BY l.userid, l.timecreated ASC ';
        $sql = $select . $from . $join . $where . $order;

        $eventsrs = $DB->get_recordset_sql($sql, $params);
        $fields = ['eventname' => 'string', 'component' => 'string', 'action' => 'string',
            'courseobjectid' => 'int:contextinstanceid', 'courseid' => 'int', 'eventtime' => 'int:timecreated'];
        $events = self::create_data_structure($eventsrs, 'userid', $fields);
        $eventsrs->close();

        return $events;
    }
}