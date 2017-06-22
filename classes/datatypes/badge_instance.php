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
 * Webservice helper class containing functions for the badge instance data type.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_globalfilter\datatypes;

defined('MOODLE_INTERNAL') || die();

/**
 * Badge instance functions.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class badge_instance extends badge {

    /**
     * Internal function defining a badge instance structure for use in "_returns" definitions.
     *
     * @return \external_multiple_structure
     */

    public static function structure() {
        $structarray = parent::basic_structure();
        $structarray['issuedtime'] = new \external_value(PARAM_INT, 'Issued timestamp.');
        return new \external_single_structure($structarray, 'badge');
    }
}