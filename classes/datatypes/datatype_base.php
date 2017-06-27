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
 * Webservice elper class containing functions for the datafield data type.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_globalfilter\datatypes;

defined('MOODLE_INTERNAL') || die();

/**
 * Globalfilter external functions
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class datatype_base {

    /**
     * Function defining the type's structure for use in "_returns" definitions.
     * This must be overriden.
     *
     * @return \external_multiple_structure
     */

    public static function structure() {
        return new \external_single_structure([], '--UNDEFINED--');
    }

    /**
     * Function to return the datatype data for the calling service.
     * This must be overridden, and can use the $type parameter to have different return data based on the type.
     *
     * @param array $dataids The array of data id's to get data for.
     * @param string $type Optional type to delegate other functions for.
     * @return array The datafields structure.
     */
    public static function get_data($dataids = [], $type = null) {
        $dataids[] = $type;
        return $dataids;
    }

    /**
     * Function to iterate over the data set and create the necessary data structure containing the data.
     *
     * @param recordset $datars The data recordset to walk.
     * @param string $groupfield The name of the field to collect data by.
     * @param array $fields Array of "fieldname => type:dataname" pairs, defining the return data.
     * @return array The data structure.
     */
    protected static function create_data_structure($datars, $groupfield, $fields) {
        $currfieldid = -1;
        $datastructure = [];
        foreach ($datars as $datarec) {
            if ($datarec->{$groupfield} != $currfieldid) {
                $currfieldid = $datarec->{$groupfield};
            }
            $recarr = [];
            foreach ($fields as $field => $typedef) {
                // The $typedef can have type followed by the data field name. If not present, it is the same as $field. Append an
                // extra array element in case its not present, to avoid a PHP warning of the list being too short.
                list($type, $dataname) = array_merge(explode(':', $typedef), ['']);
                if (empty($dataname)) {
                    $dataname = $field;
                }
                if ($type == 'string') {
                    $recarr[$field] = format_string($datarec->{$dataname}, true);
                } else if ($type == 'text') {
                    $format = isset($datrec->{$dataname.'format'}) ? $datrec->{$dataname.'format'} : 0;
                    $recarr[$field] = format_text($datarec->{$dataname}, $format);
                } else {
                    $recarr[$field] = $datarec->{$dataname};
                }
            }
            $datastructure[$currfieldid][] = $recarr;
        }

        return $datastructure;
    }
}