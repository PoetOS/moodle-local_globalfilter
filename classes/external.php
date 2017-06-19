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
                ['userid' => new \external_value(PARAM_INT, 'User id.'),
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
                    ),
                    'datafields'
                 ),
                 'tags' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['name' => new \external_value(PARAM_TEXT, 'Name of this tag.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this tag.'),
                        ],
                        'tag'
                    ),
                    'tags'
                 ),
                 'badges' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['id' => new \external_value(PARAM_INT, 'Badge id.'),
                         'name' => new \external_value(PARAM_TEXT, 'Name of this badge.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this badge.'),
                         'issuedtime' => new \external_value(PARAM_INT, 'Issued timestamp.'),
                        ],
                        'badge'
                    ),
                    'badges'
                 ),
                 'courseenrolments' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['courseid' => new \external_value(PARAM_INT, 'Course id.'),
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
                            ),
                            'competencies'
                         ),
                         'outcomes' => new \external_multiple_structure(
                            new \external_single_structure(
                                ['id' => new \external_value(PARAM_INT, 'Outcome id.'),
                                 'name' => new \external_value(PARAM_TEXT, 'Name of this outcome.'),
                                 'description' => new \external_value(PARAM_TEXT, 'Description of this outcome.'),
                                 'proficiency' => new \external_value(PARAM_TEXT, 'User proficiency of this outcome.'),
                                ],
                                'outcome'
                            ),
                            'outcomes'
                         ),
                        ],
                        'courseenrolment'
                    ),
                    'courseenrolments'
                 ),
                ],
                'user'
            ),
            'users'
        );
    }

    /**
     * Describes the parameters for get_course_profile.
     *
     * @return \external_function_parameters
     */
    public static function get_course_profile_parameters() {
        return new \external_function_parameters(
            ['courses' => new \external_single_structure(
                ['ids' => new \external_multiple_structure(
                    new \external_value(PARAM_INT, 'Course id'), 'Array of course ids', VALUE_OPTIONAL),
                ], 'courses - operator OR is used', VALUE_DEFAULT, []),
            ]
        );
    }

    /**
     * Returns the profile data for the requested courses.
     * if no courses are provided, all courses' profile data will be returned.
     *
     * @param array $courses the courses parameters
     * @return array the course(s) details
     */
    public static function get_course_profile($courses = []) {
        global $DB;
        $params = self::validate_parameters(self::get_course_profile_parameters(), ['courses' => $courses]);
        $courseprofiles = [];

        //retrieve courses
        $cfields = 'id,fullname,summary,summaryformat,startdate,enddate';
        //retrieve courses
        if (!array_key_exists('ids', $params['courses']) || empty($params['courses']['ids'])) {
            $coursesrs = $DB->get_recordset('course', null, 'id', $cfields);
        } else {
            $coursesrs = $DB->get_recordset_list('course', 'id', $params['courses']['ids'], 'id', $cfields);
        }

        foreach ($coursesrs as $course) {
            $courseprofile = [];
            $context = \context_course::instance($course->id, IGNORE_MISSING);
            try {
                self::validate_context($context);
            } catch (Exception $e) {
                $exceptionparam = new stdClass();
                $exceptionparam->message = $e->getMessage();
                $exceptionparam->courseid = $course->id;
                throw new moodle_exception('errorcoursecontextnotvalid', 'webservice', '', $exceptionparam);
            }
            $courseprofile['courseid'] = $course->id;
            $courseprofile['name'] = external_format_string($course->fullname, $context->id);
            list($courseprofile['description']) =
                external_format_text($course->summary, $course->summaryformat, $context->id, 'course', 'summary', 0);
            $courseprofile['starttime'] = $course->startdate;
            $courseprofile['endtime'] = $course->enddate;
            $courseprofile['tags'] = [];
            $courseprofile['outcomes'] = [];
            $courseprofile['competencies'] = [];
            $courseprofile['badges'] = [];
            $courseprofiles[] = $courseprofile;
        }
        $coursesrs->close();

        return $courseprofiles;
    }

    /**
     * Describes the get_course_profile return value.
     *
     * @return \external_single_structure
     */
    public static function get_course_profile_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                ['courseid' => new \external_value(PARAM_INT, 'Course id'),
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
                    ),
                    'tags'
                 ),
                 'outcomes' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['id' => new \external_value(PARAM_INT, 'Outcome id.'),
                         'name' => new \external_value(PARAM_TEXT, 'Name of this outcome.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this outcome.'),
                        ],
                        'outcome'
                    ),
                    'outcomes'
                 ),
                 'competencies' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['id' => new \external_value(PARAM_INT, 'Competency id.'),
                         'name' => new \external_value(PARAM_TEXT, 'Name of this competency.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this competency.'),
                        ],
                        'competency'
                    ),
                    'competencies'
                 ),
                 'badges' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['id' => new \external_value(PARAM_INT, 'Badge id.'),
                         'name' => new \external_value(PARAM_TEXT, 'Name of this badge.'),
                         'description' => new \external_value(PARAM_TEXT, 'Description of this badge.'),
                        ],
                        'badge'
                    ),
                    'badges'
                 ),
                ],
                'course'
            ),
            'courses'
        );
    }

    /**
     * Describes the parameters for get_course_object_profile.
     *
     * @return \external_function_parameters
     */
    public static function get_course_object_profile_parameters() {
        return new \external_function_parameters (
            ['courseids' => new \external_multiple_structure(
                new \external_value(PARAM_INT, 'course id'), 'Array of course ids', VALUE_DEFAULT, []),
             'courseobjectids' => new \external_multiple_structure(
                new \external_value(PARAM_INT, 'course id'), 'Array of course object ids', VALUE_DEFAULT, []),
            ]
        );
    }

    /**
     * Returns the profile data for the requested course objects for the specified courses.
     * If no courses are provided, all courses' object data will be returned.
     * If no course objects are provided, all course objects wull be returned.
     *
     * @param array $courseids the course ids
     * @param array $courseobjectids the course object ids
     * @return array the course objects details
     */
    public static function get_course_object_profile($courseids = [], $courseobjectids = []) {
        $result = [];
        return $result;
    }

    /**
     * Describes the get_course_profile return value.
     *
     * @return \external_single_structure
     */
    public static function get_course_object_profile_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                ['courseid' => new \external_value(PARAM_INT, 'Course id'),
                 'courseobjects' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['courseobjectid' => new \external_value(PARAM_INT, 'Course object id'),
                         'name' => new \external_value(PARAM_TEXT, 'Course object name.'),
                         'description' => new \external_value(PARAM_TEXT, 'Course object description.'),
                         'type' => new \external_value(PARAM_TEXT, 'Course object type.'),
                         'tags' => new \external_multiple_structure(
                            new \external_single_structure(
                                ['name' => new \external_value(PARAM_TEXT, 'Name of this tag.'),
                                 'description' => new \external_value(PARAM_TEXT, 'Description of this tag.'),
                                ],
                                'tag'
                            ),
                            'tags'
                         ),
                         'competencies' => new \external_multiple_structure(
                            new \external_single_structure(
                                ['id' => new \external_value(PARAM_INT, 'Competency id.'),
                                 'name' => new \external_value(PARAM_TEXT, 'Name of this competency.'),
                                 'description' => new \external_value(PARAM_TEXT, 'Description of this competency.'),
                                ],
                                'competency'
                            ),
                            'competencies'
                         ),
                         'outcomes' => new \external_multiple_structure(
                            new \external_single_structure(
                                ['id' => new \external_value(PARAM_INT, 'Outcome id.'),
                                 'name' => new \external_value(PARAM_TEXT, 'Name of this outcome.'),
                                 'description' => new \external_value(PARAM_TEXT, 'Description of this outcome.'),
                                ],
                                'outcome'
                            ),
                            'outcomes'
                         ),
                        ],
                        'course object'
                    ),
                    'courseobjects'
                 ),
                ],
                'course'
            ),
            'courses'
        );
    }

    /**
     * Describes the parameters for get_user_activity.
     *
     * @return \external_function_parameters
     */
    public static function get_user_activity_parameters() {
        return new \external_function_parameters (
            ['userids' => new \external_multiple_structure(
                new \external_value(PARAM_INT, 'user id'), 'Array of user ids', VALUE_DEFAULT, []),
            ]
        );
    }

    /**
     * Returns the user activity data for the requested users.
     * if no userss are provided, all users' activity data will be returned.
     *
     * @param array $userids the user ids
     * @return array the user activity
     */
    public static function get_user_activity($userids = []) {
        $result = [];
        return $result;
    }

    /**
     * Describes the get_course_profile return value.
     *
     * @return \external_single_structure
     */
    public static function get_user_activity_returns() {
        return new \external_multiple_structure(
            new \external_single_structure(
                ['userid' => new \external_value(PARAM_INT, 'User id'),
                 'events' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['eventname' => new \external_value(PARAM_TEXT, 'Event name.'),
                         'component' => new \external_value(PARAM_TEXT, 'Component name.'),
                         'action' => new \external_value(PARAM_TEXT, 'The event action.'),
                         'courseobjectid' => new \external_value(PARAM_INT, 'Course object id.'),
                         'courseid' => new \external_value(PARAM_INT, 'Course id.'),
                         'eventtime' => new \external_value(PARAM_INT, 'Event activity timestamp.'),
                        ],
                        'event'
                    ),
                    'events'
                 ),
                ],
                'user'
            ),
            'users'
        );
    }
}