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

        $datafields = self::get_user_datafields($userids);
        $tags = self::get_user_tags($userids);
        $badges = self::get_user_badges($userids);
        $enrolments = self::get_user_enrolments($userids);

        foreach ($userrs as $user) {
            $userprofile = [];
            $userprofile['userid'] = $user->id;
            $userprofile['language'] = $user->lang;
            $userprofile['firstaccess'] = $user->firstaccess;
            $userprofile['lastaccess'] = $user->lastaccess;
            $userprofile['lastlogin'] = $user->lastlogin;
            $userprofile['description'] = format_text($user->description, $user->descriptionformat);

            $userprofile['datafields'] = [];
            if (isset($datafields[$user->id])) {
                foreach ($datafields[$user->id] as $datafield) {
                    $userprofile['datafields'][] = $datafield;
                }
            }

            $userprofile['tags'] = [];
            if (isset($tags[$user->id])) {
                foreach ($tags[$user->id] as $tag) {
                    $userprofile['tags'][] = $tag;
                }
            }

            $userprofile['badges'] = [];
            if (isset($badges[$user->id])) {
                foreach ($badges[$user->id] as $badge) {
                    $userprofile['badges'][] = $badge;
                }
            }

            $userprofile['courseenrolments'] = [];
            if (isset($enrolments[$user->id])) {
                foreach ($enrolments[$user->id] as $enrolment) {
                    $userprofile['courseenrolments'][] = $enrolment;
                }
            }

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
                 'datafields' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['name' => new \external_value(PARAM_TEXT, 'Name of this field.'),
                         'description' => new \external_value(PARAM_RAW, 'Description of this field.'),
                         'value' => new \external_value(PARAM_RAW, 'Value of this field for this user.'),
                        ],
                        'datafield'
                    ),
                    'datafields'
                 ),
                 'tags' => self::tag_structure(),
                 'badges' => self::badge_structure(true),
                 'courseenrolments' => new \external_multiple_structure(
                    new \external_single_structure(
                        ['courseid' => new \external_value(PARAM_INT, 'Course id.'),
                         'enroltime' => new \external_value(PARAM_INT, 'When enrolled in course timestamp.'),
                         'startime' => new \external_value(PARAM_INT, 'When started activity in course timestamp.'),
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

        $tags = self::get_course_tags($courseids);
        $outcomes = self::get_course_outcomes($courseids);
        $competencies = self::get_course_competencies($courseids);
        $badges = self::get_course_badges($courseids);

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

            $courseprofile['tags'] = [];
            if (isset($tags[$course->id])) {
                foreach ($tags[$course->id] as $tag) {
                    $courseprofile['tags'][] = $tag;
                }
            }

            $courseprofile['competencies'] = [];
            if (isset($competencies[$course->id])) {
                foreach ($competencies[$course->id] as $competency) {
                    $courseprofile['competencies'][] = $competency;
                }
            }

            $courseprofile['outcomes'] = [];
            if (isset($outcomes[$course->id])) {
                foreach ($outcomes[$course->id] as $outcome) {
                    $courseprofile['outcomes'][] = $outcome;
                }
            }

            $courseprofile['badges'] = [];
            if (isset($badges[$course->id])) {
                foreach ($badges[$course->id] as $badge) {
                    $courseprofile['badges'][] = $badge;
                }
            }

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
                 'tags' => self::tag_structure(),
                 'competencies' => self::competency_structure(),
                 'outcomes' => self::outcome_structure(),
                 'badges' => self::badge_structure(),
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
                         'tags' => self::tag_structure(),
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

    /**
     * Internal function defining a tag structure for use in "_returns" definitions.
     *
     * @return \external_multiple_structure
     */

    private static function tag_structure() {
        return new \external_multiple_structure(
            new \external_single_structure(
                ['name' => new \external_value(PARAM_TEXT, 'Name of this tag.'),
                 'description' => new \external_value(PARAM_RAW, 'Description of this tag.'),
                ],
                'tag'
            ),
            'tags'
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
     * Internal function defining a badges structure for use in "_returns" definitions.
     *
     * @param boolean $withissuedtime True if including issued time.
     * @return \external_multiple_structure
     */

    private static function badge_structure($withissuedtime = false) {
        $structarray = ['id' => new \external_value(PARAM_INT, 'Badge id.'),
            'name' => new \external_value(PARAM_TEXT, 'Name of this badge.'),
            'description' => new \external_value(PARAM_RAW, 'Description of this badge.')];
        if ($withissuedtime) {
            $structarray['issuedtime'] = new \external_value(PARAM_INT, 'Issued timestamp.');
        }
        return new \external_multiple_structure(new \external_single_structure($structarray, 'badge'), 'badges');
    }

    /**
     * Internal function to return the user datafields structure for the user id list.
     *
     * @param array $userids The array of user id's to get datafields for.
     * @return array The datafields structure.
     */
    private static function get_user_datafields($userids = []) {
        global $DB;

        $sql = '';
        $params = [];
        if (!empty($userids)) {
            list($sql, $params) = $DB->get_in_or_equal($userids);
            $sql = 'uid.userid ' . $sql . ' ';
        }

        $select = 'SELECT uid.id,uid.userid,uid.fieldid,uid.data,uid.dataformat,uif.name,uif.description,uif.descriptionformat ';
        $from = 'FROM {user_info_data} uid ';
        $join = 'INNER JOIN {user_info_field} uif ON uid.fieldid = uif.id ';
        $where = !empty($sql) ? 'WHERE ' . $sql : '';
        $order = 'ORDER BY uid.userid ASC ';
        $sql = $select . $from . $join . $where . $order;

        $dfsrs = $DB->get_recordset_sql($sql, $params);

        $curritemid = -1;
        $datafields = [];
        foreach ($dfsrs as $datafieldrec) {
            if ($datafieldrec->userid != $curritemid) {
                $curritemid = $datafieldrec->userid;
            }
            $datafields[$curritemid][] = ['name' => format_string($datafieldrec->name, true),
                'description' => format_text($datafieldrec->description, $datfieldrec->descriptionformat),
                'value' => format_text($datafieldrec->data, $datafieldrec->dataformat)];
        }
        $dfsrs->close();

        return $datafields;
    }

    /**
     * Internal function to return the course tags structure for the course id list.
     *
     * @param array $courseids The array of course id's to get tags for.
     * @return array The tags structure.
     */
    private static function get_course_tags($courseids = []) {
        return self::get_tags('course', $courseids);
    }

    /**
     * Internal function to return the user tags structure for the user id list.
     *
     * @param array $userids The array of user id's to get tags for.
     * @return array The tags structure.
     */
    private static function get_user_tags($userids = []) {
        return self::get_tags('user', $userids);
    }

    /**
     * Internal function to return the tags structure for the specified type and itemid list.
     *
     * @param string $type The tag 'itemtype' value.
     * @param array $userids The array of user id's to get tags for.
     * @return array The tags structure.
     */
    private static function get_tags($type, $itemids = []) {
        global $DB;

        $sql = '';
        $params = [];
        if (!empty($itemids)) {
            list($sql, $params) = $DB->get_in_or_equal($itemids);
            $sql = 'AND ti.itemid ' . $sql . ' ';
        }
        $params = array_merge([$type], $params);

        $select = 'SELECT ti.id, ti.tagid, ti.itemid, t.rawname, t.description, t.descriptionformat ';
        $from = 'FROM {tag_instance} ti ';
        $join = 'INNER JOIN {tag} t ON ti.tagid = t.id ';
        $where = 'WHERE ti.itemtype = ? ' . $sql;
        $order = 'ORDER BY ti.itemid ASC ';
        $sql = $select . $from . $join . $where . $order;

        $tagsrs = $DB->get_recordset_sql($sql, $params);

        $curritemid = -1;
        $tags = [];
        foreach ($tagsrs as $tagrec) {
            if ($tagrec->itemid != $curritemid) {
                $curritemid = $tagrec->itemid;
            }
            $tags[$curritemid][] = ['name' => format_string($tagrec->rawname, true),
                'description' => format_text($tagrec->description, $tagrec->descriptionformat)];
        }
        $tagsrs->close();

        return $tags;
    }

    /**
     * Internal function to return the user badges structure for the user id list.
     *
     * @param array $userids The array of user id's to get badges for.
     * @return array The badges structure.
     */
    private static function get_user_badges($userids = []) {
        global $DB;

        $sql = '';
        $params = [];
        if (!empty($userids)) {
            list($sql, $params) = $DB->get_in_or_equal($userids);
            $sql = 'bi.userid ' . $sql . ' ';
        }

        $select = 'SELECT bi.id, bi.userid, bi.badgeid, bi.dateissued, b.name, b.description ';
        $from = 'FROM {badge_issued} bi ';
        $join = 'INNER JOIN {badge} b ON bi.badgeid = b.id ';
        $where = !empty($sql) ? 'WHERE ' . $sql : '';
        $order = 'ORDER BY bi.userid ASC ';
        $sql = $select . $from . $join . $where . $order;

        $badgesrs = $DB->get_recordset_sql($sql, $params);

        $curritemid = -1;
        $badges = [];
        foreach ($badgesrs as $badgerec) {
            if ($badgerec->userid != $curritemid) {
                $curritemid = $badgerec->userid;
            }
            $badges[$curritemid][] = ['id' => $badgerec->badgeid,
                'name' => format_string($badgerec->name, true),
                'description' => format_text($badgerec->description, 0),
                'issuedtime' => $badgerec->dateissued];
        }
        $badgesrs->close();

        return $badges;
    }

    /**
     * Internal function to return the user course enrolments structure for the user id list.
     *
     * @param array $userids The array of user id's to get enrolments for.
     * @return array The enrolments structure.
     */
    private static function get_user_enrolments($userids = []) {
        return [];
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

        return [];
    }

    /**
     * Internal function to return the course competencies structure for the course id list.
     *
     * @param array $courseids The array of course id's to get competencies for.
     * @return array The competencies structure.
     */
    private static function get_course_competencies($courseids = []) {
        global $DB;

        return [];
    }

    /**
     * Internal function to return the course badges structure for the course id list.
     *
     * @param array $courseids The array of course id's to get badges for.
     * @return array The badges structure.
     */
    private static function get_course_badges($courseids = []) {
        global $DB;

        return [];
    }
}