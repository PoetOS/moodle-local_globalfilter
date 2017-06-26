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
require_once($CFG->dirroot.'/lib/externallib.php');

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
            ['users' => new \external_single_structure(
                ['ids' => new \external_multiple_structure(
                    new \external_value(PARAM_INT, 'User id'), 'Array of user ids', VALUE_OPTIONAL),
                ], 'Users - operator OR is used', VALUE_DEFAULT, []),
            ]
        );
    }

    /**
     * Returns the profile data for the requested users.
     * if no users are provided, all users' profile data will be returned.
     *
     * @param array $users The users parameters.
     * @return array The user(s) details.
     */
    public static function get_user_profile($users = []) {
        global $DB;

        $params = self::validate_parameters(self::get_user_profile_parameters(), ['users' => $users]);
        $userprofiles = [];

        $ufields = 'id,lang,firstaccess,lastaccess,lastlogin,description,descriptionformat';
        if (!array_key_exists('ids', $params['users']) || empty($params['users']['ids'])) {
            $userrs = $DB->get_recordset('user', null, 'id', $ufields);
            $userids = [];
        } else {
            $userids = $params['users']['ids'];
            $userrs = $DB->get_recordset_list('user', 'id', $userids, 'id', $ufields);
        }

        $datafields = datatypes\datafield::get_data($userids, 'user');
        $tags = datatypes\tag::get_data($userids, 'user');
        $badges = datatypes\badge_instance::get_data($userids, 'user');
        $enrolments = datatypes\enrolment::get_data($userids, 'user');

        foreach ($userrs as $user) {
            $userprofile = [];
            $userprofile['userid'] = $user->id;
            $userprofile['language'] = $user->lang;
            $userprofile['firstaccess'] = $user->firstaccess;
            $userprofile['lastaccess'] = $user->lastaccess;
            $userprofile['lastlogin'] = $user->lastlogin;
            $userprofile['description'] = format_text($user->description, $user->descriptionformat);
            $userprofile['datafields'] = isset($datafields[$user->id]) ? $datafields[$user->id] : [];
            $userprofile['tags'] = isset($tags[$user->id]) ? $tags[$user->id] : [];
            $userprofile['badges'] = isset($badges[$user->id]) ? $badges[$user->id] : [];
            $userprofile['courseenrolments'] = isset($enrolments[$user->id]) ? $enrolments[$user->id] : [];
            $userprofiles[] = $userprofile;
        }
        $userrs->close();

        return $userprofiles;
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
                 'description' => new \external_value(PARAM_RAW, 'User summary of themselves.'),
                 'datafields' => new \external_multiple_structure(datatypes\datafield::structure(), 'datafields'),
                 'tags' => new \external_multiple_structure(datatypes\tag::structure(), 'tags'),
                 'badges' => new \external_multiple_structure(datatypes\badge_instance::structure(), 'badges'),
                 'courseenrolments' => new \external_multiple_structure(datatypes\enrolment::structure(), 'courseenrolments'),
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

        $cfields = 'id,fullname,summary,summaryformat,startdate,enddate';
        if (!array_key_exists('ids', $params['courses']) || empty($params['courses']['ids'])) {
            $coursesrs = $DB->get_recordset('course', null, 'id', $cfields);
            $courseids = [];
        } else {
            $courseids = $params['courses']['ids'];
            $coursesrs = $DB->get_recordset_list('course', 'id', $courseids, 'id', $cfields);
        }

        $tags = datatypes\tag::get_data($courseids, 'course');
        $outcomes = datatypes\outcome::get_data($courseids, 'course');
        $competencies = datatypes\competency::get_data($courseids, 'course');
        $badges = datatypes\badge::get_data($courseids, 'course');

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
            list($courseprofile['description']) = external_format_text($course->summary, $course->summaryformat, $context->id,
                'course', 'summary', 0);
            $courseprofile['starttime'] = $course->startdate;
            $courseprofile['endtime'] = $course->enddate;

            $courseprofile['tags'] = isset($tags[$course->id]) ? $tags[$course->id] : [];
            $courseprofile['competencies'] = isset($competencies[$course->id]) ? $competencies[$course->id] : [];
            $courseprofile['outcomes'] = isset($outcomes[$course->id]) ? $outcomes[$course->id] : [];
            $courseprofile['badges'] = isset($badges[$course->id]) ? $badges[$course->id] : [];
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
                 'description' => new \external_value(PARAM_RAW, 'Course description.'),
                 'starttime' => new \external_value(PARAM_INT, 'Start of the course timestamp.'),
                 'endtime' => new \external_value(PARAM_INT, 'End of the course timestamp.'),
                 'tags' => new \external_multiple_structure(datatypes\tag::structure(), 'tags'),
                 'competencies' => new \external_multiple_structure(datatypes\competency::structure(), 'competencies'),
                 'outcomes' => new \external_multiple_structure(datatypes\outcome::structure(), 'outcomes'),
                 'badges' => new \external_multiple_structure(datatypes\badge::structure(), 'badges'),
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
     * Describes the get_course_object_profile return value.
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
                         'tags' => new \external_multiple_structure(datatypes\tag::structure(), 'tags'),
                         'competencies' => new \external_multiple_structure(datatypes\competency::structure(), 'competencies'),
                         'outcomes' => new \external_multiple_structure(datatypes\outcome::structure(), 'outcomes'),
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
     * Describes the get_user_activity return value.
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