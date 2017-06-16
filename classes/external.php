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
 * Plugin external services are located here.
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace local_globalfilter;

defined('MOODLE_INTERNAL') || die();

/**
 * Globalfilter external functions
 *
 * @package     local_globalfilter
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

class external extends \external_api {

    /**
     * Describes the parameters for get_user_profile.
     *
     * @return \external_function_parameters
     */
    public static function get_user_profile_parameters() {
        return new \external_function_parameters (
            ['userids' => new \external_multiple_structure(
                new \external_value(PARAM_INT, 'user id'), 'Array of user ids', VALUE_DEFAULT, []),
            ]
        );
    }

    /**
     * Returns the profile data for the requested users.
     * if no users are provided, all users' profile data will be returned.
     *
     * @param array $userids the user ids
     * @return array the user(s) details
     */
    public static function get_user_profile($userids = []) {
        $result = [];
        return $result;
    }

    /**
     * Describes the get_user_profile return value.
     *
     * @return \external_single_structure
     */
    public static function get_user_profile_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                ['id' => new \external_value(PARAM_INT, 'User id.'),
                 'language' => new \external_value(PARAM_TEXT, 'Communication language.'),
                 'firstaccess' => new \external_value(PARAM_INT, 'Original site access timestamp.'),
                 'lastaccess' => new \external_value(PARAM_INT, 'Last known site access timestamp.'),
                 'lastlogin' => new \external_value(PARAM_INT, 'Last login timestamp.'),
                 'description' => new \external_value(PARAM_TEXT, 'User summary of themselves.'),
                 'datafields' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['name' => new \external_value(PARAM_TEXT, 'Name of this field.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this field.'),
                         'value' => new \external_value(PARAM_TEXT, 'Value of this field for this user.'),
                        ],
                        'datafield'
                    )
                 ),
                 'tags' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['name' => new \external_value(PARAM_TEXT, 'Name of this tag.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this tag.'),
                        ],
                        'tag'
                    )
                 ),
                 'badges' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['id' => new \external_value(PARAM_INT, 'Badge id.'),
                         'name' => new \external_value(PARAM_TEXT, 'Name of this badge.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this badge.'),
                         'issuedtime' => new \external_value(PARAM_INT, 'Issued timestamp.'),
                        ],
                        'badge'
                    )
                 ),
                 'courseenrolments' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['id' => new \external_value(PARAM_INT, 'Outcome id.'),
                         'enroltime' => new \external_value(PARAM_INT, 'When enrolled in course timestamp.'),
                         'startime' => new \external_value(PARAM_INT, 'When started activity in course timestamp.'),
                         'endtime' => new \external_value(PARAM_INT, 'When ended course timestamp.'),
                         'lastaccess' => new \external_value(PARAM_INT, 'When last accessed course timestamp.'),
                         'competencies' => new \external_multiple_structure(
                            new \external_single_structure(
                                ['id' => new \external_value(PARAM_INT, 'Competency id.'),
                                 'name' => new \external_value(PARAM_TEXT, 'Name of this competency.'),
                                 'description' => new \external_value(PARAM_TEXT, 'Description of this competency.'),
                                 'proficiency' => new \external_value(PARAM_TEXT, 'User proficiency of this competency.'),
                                ],
                                'competency'
                            )
                         ),
                         'outcomes' => new \external_multiple_structure(
                            new \external_single_structure(
                                ['id' => new \external_value(PARAM_INT, 'Outcome id.'),
                                 'name' => new \external_value(PARAM_TEXT, 'Name of this outcome.'),
                                 'description' => new \external_value(PARAM_TEXT, 'Description of this outcome.'),
                                 'proficiency' => new \external_value(PARAM_TEXT, 'User proficiency of this outcome.'),
                                ],
                                'outcome'
                            )
                         ),
                        ],
                        'courseenrolment'
                    )
                 ),
                ],
                'user'
            )
        );
    }

    /**
     * Describes the parameters for get_course_profile.
     *
     * @return \external_function_parameters
     */
    public static function get_course_profile_parameters() {
        return new \external_function_parameters (
            ['courseids' => new \external_multiple_structure(
                new \external_value(PARAM_INT, 'course id'), 'Array of course ids', VALUE_DEFAULT, []),
            ]
        );
    }

    /**
     * Returns the profile data for the requested courses.
     * if no courses are provided, all courses' profile data will be returned.
     *
     * @param array $courseids the course ids
     * @return array the course(s) details
     */
    public static function get_course_profile($courseids = []) {
        $result = [];
        return $result;
    }

    /**
     * Describes the get_course_profile return value.
     *
     * @return \external_single_structure
     */
    public static function get_course_profile_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                ['id' => new \external_value(PARAM_INT, 'Course id'),
                 'name' => new \external_value(PARAM_TEXT, 'Course name.'),
                 'description' => new \external_value(PARAM_TEXT, 'Course description.'),
                 'starttime' => new \external_value(PARAM_INT, 'Start of the course timestamp.'),
                 'endtime' => new \external_value(PARAM_INT, 'End of the course timestamp.'),
                 'tags' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['name' => new \external_value(PARAM_TEXT, 'Name of this tag.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this tag.'),
                        ],
                        'tag'
                    )
                 ),
                 'outcomes' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['id' => new \external_value(PARAM_INT, 'Outcome id.'),
                         'name' => new \external_value(PARAM_TEXT, 'Name of this outcome.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this outcome.'),
                        ],
                        'outcome'
                    )
                 ),
                 'competencies' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['id' => new \external_value(PARAM_INT, 'Competency id.'),
                         'name' => new \external_value(PARAM_TEXT, 'Name of this competency.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this competency.'),
                        ],
                        'competency'
                    )
                 ),
                 'badges' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['id' => new \external_value(PARAM_INT, 'Badge id.'),
                         'name' => new \external_value(PARAM_TEXT, 'Name of this badge.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this badge.'),
                        ],
                        'badge'
                    )
                 ),
                ],
                'course'
            )
        );
    }
}