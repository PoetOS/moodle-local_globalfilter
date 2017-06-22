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
        $enrolments = self::get_user_enrolments($userids);

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
                 'courseenrolments' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['courseid' => new \external_value(PARAM_INT, 'Course id.'),
                         'enroltime' => new \external_value(PARAM_INT, 'When enrolled in course timestamp.'),
                         'starttime' => new \external_value(PARAM_INT, 'When started activity in course timestamp.'),
                         'endtime' => new \external_value(PARAM_INT, 'When ended course timestamp.'),
                         'lastaccess' => new \external_value(PARAM_INT, 'When last accessed course timestamp.'),
                         'competencies' => self::competency_structure(true),
                         'outcomes' => self::outcome_structure(true),
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

        $cfields = 'id,fullname,summary,summaryformat,startdate,enddate';
        if (!array_key_exists('ids', $params['courses']) || empty($params['courses']['ids'])) {
            $coursesrs = $DB->get_recordset('course', null, 'id', $cfields);
            $courseids = [];
        } else {
            $courseids = $params['courses']['ids'];
            $coursesrs = $DB->get_recordset_list('course', 'id', $courseids, 'id', $cfields);
        }

        $tags = datatypes\tag::get_data($userids, 'course');
        $outcomes = self::get_course_outcomes($courseids);
        $competencies = self::get_course_competencies($courseids);
        $badges = datatypes\badge::get_data($userids, 'course');

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
                 'competencies' => self::competency_structure(),
                 'outcomes' => self::outcome_structure(),
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
                         'tags' => new \external_multiple_structure(datatypes\tag::structure(), 'tags'),
                         'competencies' => self::competency_structure(),
                         'outcomes' => self::outcome_structure(),
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

    /**
     * Internal function defining an outcomes structure for use in "_returns" definitions.
     *
     * @param boolean $withproficiency True if including proficiency.
     * @return \external_multiple_structure
     */

    private static function outcome_structure($withproficiency = false) {
        $structarray = ['id' => new \external_value(PARAM_INT, 'Outcome id.'),
            'name' => new \external_value(PARAM_TEXT, 'Name of this outcome.'),
            'description' => new \external_value(PARAM_RAW, 'Description of this outcome.')];
        if ($withproficiency) {
            $structarray['proficiency'] = new \external_value(PARAM_TEXT, 'User proficiency of this outcome.');
        }
        return new \external_multiple_structure(new \external_single_structure($structarray, 'outcome'), 'outcomes');
    }

    /**
     * Internal function defining a competencies structure for use in "_returns" definitions.
     *
     * @param boolean $withproficiency True if including proficiency.
     * @return \external_multiple_structure
     */

    private static function competency_structure($withproficiency = false) {
        $structarray = ['id' => new \external_value(PARAM_INT, 'Competency id.'),
            'name' => new \external_value(PARAM_TEXT, 'Name of this competency.'),
            'description' => new \external_value(PARAM_RAW, 'Description of this competency.')];
        if ($withproficiency) {
            $structarray['proficiency'] = new \external_value(PARAM_TEXT, 'User proficiency of this competency.');
        }
        return new \external_multiple_structure(new \external_single_structure($structarray, 'competency'), 'competencies');
    }

    /**
     * Internal function to return the user course enrolments structure for the user id list.
     *
     * @param array $userids The array of user id's to get enrolments for.
     * @return array The enrolments structure.
     */
    private static function get_user_enrolments($userids = []) {
        global $DB;

        $sql = '';
        $params = [];
        if (!empty($userids)) {
            list($sql, $params) = $DB->get_in_or_equal($userids);
            $sql = ' AND ue.userid ' . $sql;
        }

        $ccselect = ', ' . \context_helper::get_preload_record_columns_sql('ctx') . ' ';
        $select = 'SELECT en.id, c.id as courseid, en.userid, en.timecreated, en.timestart, en.timeend' . $ccselect;
        $from = 'FROM {course} c ';
        $join = 'JOIN (SELECT DISTINCT e.courseid, ue.id, ue.userid, ue.timecreated, ue.timestart, ue.timeend ' .
                    'FROM {enrol} e ' .
                    'JOIN {user_enrolments} ue ON (ue.enrolid = e.id' . $sql .') '.
                    ') en ON (en.courseid = c.id) ';
        $join .= 'LEFT JOIN {context} ctx ON (ctx.instanceid = c.id AND ctx.contextlevel = ?) ';
        $where = 'WHERE c.id != ? ';
        $order = 'ORDER BY en.userid ASC ';
        $params = array_merge($params, [CONTEXT_COURSE, SITEID]);
        $sql = $select . $from . $join . $where . $order;

        $enrolsrs = $DB->get_recordset_sql($sql, $params);

        $curritemid = -1;
        $enrols = [];
        foreach ($enrolsrs as $enrolrec) {
            if ($enrolrec->userid != $curritemid) {
                $curritemid = $enrolrec->userid;
            }
            $enrols[$curritemid][] = ['courseid' => $enrolrec->courseid,
                'enroltime' => $enrolrec->timecreated,
                'starttime' => $enrolrec->timestart,
                'endtime' => $enrolrec->timeend,
                'lastaccess' => 0,
                'competencies' => [],
                'outcomes' => [],
                ];
        }
        $enrolsrs->close();

        return $enrols;
        $outcomes = self::get_user_outcomes($userids, $enrolments);
        $competencies = self::get_user_competencies($userids, $enrolments);
    }

    /**
     * Internal function to return the course outcomes structure for the course id list.
     *
     * @param array $courseids The array of course id's to get outcomes for.
     * @return array The outcomes structure.
     */
    private static function get_course_outcomes($courseids = []) {
        global $DB;

        $sql = '';
        $params = [];
        if (!empty($courseids)) {
            list($sql, $params) = $DB->get_in_or_equal($courseids);
            $sql = 'oc.courseid ' . $sql . ' ';
        }

        $select = 'SELECT oc.id, oc.courseid, o.fullname, o.description, o.descriptionformat ';
        $from = 'FROM {grade_outcomes_courses} oc ';
        $join = 'INNER JOIN {grade_outcomes} o ON oc.outcomeid = o.id ';
        $where = !empty($sql) ? 'WHERE ' . $sql : '';
        $order = 'ORDER BY oc.courseid ASC ';
        $sql = $select . $from . $join . $where . $order;

        $outcomesrs = $DB->get_recordset_sql($sql, $params);

        $curritemid = -1;
        $outcomes = [];
        foreach ($outcomesrs as $outcomerec) {
            if ($outcomerec->courseid != $curritemid) {
                $curritemid = $outcomerec->courseid;
            }
            $outcomes[$curritemid][] = ['id' => $outcomerec->id,
                'name' => format_string($outcomerec->fullname, true),
                'description' => format_text($outcomerec->description, $outcomerec->descriptionformat)];
        }
        $outcomesrs->close();

        return $outcomes;
    }

    /**
     * Internal function to return the course competencies structure for the course id list.
     *
     * @param array $courseids The array of course id's to get competencies for.
     * @return array The competencies structure.
     */
    private static function get_course_competencies($courseids = []) {
        global $DB;

        $sql = '';
        $params = [];
        if (!empty($courseids)) {
            list($sql, $params) = $DB->get_in_or_equal($courseids);
            $sql = 'cc.courseid ' . $sql . ' ';
        }

        $select = 'SELECT cc.id, cc.courseid, c.shortname, c.description, c.descriptionformat ';
        $from = 'FROM {competency_coursecomp} cc ';
        $join = 'INNER JOIN {competency} c ON cc.competencyid = c.id ';
        $where = !empty($sql) ? 'WHERE ' . $sql : '';
        $order = 'ORDER BY cc.courseid ASC ';
        $sql = $select . $from . $join . $where . $order;

        $competenciesrs = $DB->get_recordset_sql($sql, $params);

        $curritemid = -1;
        $competencies = [];
        foreach ($competenciesrs as $competencyrec) {
            if ($competencyrec->courseid != $curritemid) {
                $curritemid = $competencyrec->courseid;
            }
            $competencies[$curritemid][] = ['id' => $competencyrec->id,
                'name' => format_string($competencyrec->shortname, true),
                'description' => format_text($competencyrec->description, $competencyrec->descriptionformat)];
        }
        $competenciesrs->close();

        return $competencies;
    }
}