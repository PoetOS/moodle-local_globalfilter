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
 * Plugin external services are defined here.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

$functions = [
    'local_globalfilter_get_user_profile' => [
        'classname'     => 'local_globalfilter\external',
        'methodname'    => 'get_user_profile',
        'description'   => 'Returns the user profile data for the specified user.',
        'type'          => 'read',
    ],
    'local_globalfilter_get_course_profile' => [
        'classname'     => 'local_globalfilter\external',
        'methodname'    => 'get_course_profile',
        'description'   => 'Returns the course profile data for the specified course.',
        'type'          => 'read',
    ],
];

$services = [
    'Globalfilter web services' => [
        'functions' => ['local_globalfilter_get_user_profile', 'local_globalfilter_get_course_profile'],
        'enabled' => 1,
        'restrictedusers' => 0,
    ]
];