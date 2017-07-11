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
 * External globalfilter functions unit tests.
 *
 * @package     local_globalfilter
 * @category    external
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

global $CFG;

require_once($CFG->dirroot . '/webservice/tests/helpers.php');

/**
 * External globalfilter functions unit tests.
 * Unit tests for {@link local_globalfilter_external_testcase}.
 * @group local_globalfilter
 *
 * @package     local_globalfilter
 * @category    external
 * @author      Mike Churchward
 * @copyright   2017 onwards Mike Churchward (mike.churchward@poetopensource.org)
 * @license     http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */
class local_globalfilter_external_testcase extends externallib_advanced_testcase {

    /**
     * Test get user profile.
     *
     * @param array $users It contains an array (list of ids).
     * @return array
     */
    public function test_get_user_profile() {
        global $DB, $CFG, $USER;
        require_once($CFG->dirroot . '/user/editlib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $this->resetAfterTest(true);

        // Create a course to use.
        $course = self::getDataGenerator()->create_course();

        // Addsome custom data fields.
        $DB->insert_record('user_info_field',
            ['shortname' => 'frogdesc', 'name' => 'Description of frog', 'categoryid' => 1, 'datatype' => 'textarea']);
        $DB->insert_record('user_info_field',
            ['shortname' => 'frogname', 'name' => 'Name of frog', 'categoryid' => 1, 'datatype' => 'text']);

        // Add a user.
        $user1 = array(
            'username' => 'usernametest1',
            'firstname' => 'First Name User Test 1',
            'lastname' => 'Last Name User Test 1',
            'email' => 'usertest1@email.com',
            'lang' => 'en',
            'description' => 'This is a description for user 1',
            'descriptionformat' => FORMAT_MOODLE,
            );
        $user1 = self::getDataGenerator()->create_user($user1);

        // Add extra profile data.
        profile_save_data((object)['id' => $user1->id, 'profile_field_frogdesc' => 'Green and wet.']);
        profile_save_data((object)['id' => $user1->id, 'profile_field_frogname' => 'Leopard']);

        $usertestdata = [];
        $usertestdata[$user1->id]['language'] = 'en';
        $usertestdata[$user1->id]['description'] = format_text('This is a description for user 1', FORMAT_MOODLE);

        $usertestdata[$user1->id]['datafields'][] = 'Description of frog';
        $usertestdata[$user1->id]['datafields'][] = 'Name of frog';

        // Add tags.
        $usertestdata[$user1->id]['tags'][] = 'Cinema';
        $usertestdata[$user1->id]['tags'][] = 'Tennis';
        $usertestdata[$user1->id]['tags'][] = 'Dance';
        $usertestdata[$user1->id]['tags'][] = 'Guitar';
        $usertestdata[$user1->id]['tags'][] = 'Cooking';
        $user1->interests = $usertestdata[$user1->id]['tags'];
        useredit_update_interests($user1, $user1->interests);

        // Enrol the users in the course.
        $usertestdata[$user1->id]['enrolments'][] = $course->id;
        $context = context_course::instance($course->id);
        $roleid = $this->assignUserCapability('moodle/user:viewdetails', $context->id);
        $this->getDataGenerator()->enrol_user($user1->id, $course->id, $roleid, 'manual');

        // Add competencies.
        $compsettings = new \core_competency\course_competency_settings(0,
            (object)['courseid' => $course->id, 'pushratingstouserplans' => false]);
        $compsettings->create();
        $coursecontext = context_course::instance($course->id);

        // Create a competency for the course.
        $lpg = self::getDataGenerator()->get_plugin_generator('core_competency');
        $framework = $lpg->create_framework();
        // Do not push ratings from course to user plans.
        $comp = $lpg->create_competency(['competencyframeworkid' => $framework->get('id')]);
        $lpg->create_course_competency(['courseid' => $course->id, 'competencyid' => $comp->get('id')]);

        // Add evidence that sets a grade to the course.
        \core_competency\api::add_evidence($user1->id, $comp, $coursecontext,
            \core_competency\evidence::ACTION_OVERRIDE, 'commentincontext', 'core', null, false, null, 2, $USER->id);

        $usertestdata[$user1->id]['competencies'][] = 'Competency shortname 2';
        // Call the external function.
        $returnedprofiles = \local_globalfilter\external::get_user_profile(['ids' => [$user1->id]]);
        $returnedprofiles = external_api::clean_returnvalue(\local_globalfilter\external::get_user_profile_returns(),
            $returnedprofiles);

        // Check that we retrieve the expected number of profiles.
        $this->assertEquals(1, count($returnedprofiles));

        // Now check we have the expected data. Don't need to test for presence of key items, sinc the external services does this.
        foreach ($returnedprofiles as $profiledata) {
            $userid = $profiledata['userid'];
            $this->assertArrayHasKey($userid, $usertestdata);

            // Verify standard profile data.
            $this->assertEquals($usertestdata[$userid]['language'], $profiledata['language']);
            $this->assertEquals($usertestdata[$userid]['description'], $profiledata['description']);

            // Verify extra profile data.
            $this->data_contains($profiledata['datafields'], 'name', $usertestdata[$userid]['datafields'], 2);

            // Verify tag data.
            $this->data_contains($profiledata['tags'], 'name', $usertestdata[$userid]['tags'], 5);

            // Verify enrolment data.
            $this->data_contains($profiledata['courseenrolments'], 'courseid', $usertestdata[$userid]['enrolments'], 1);

            // Verify enrolment competency data.
            $this->data_contains($profiledata['courseenrolments'][0]['competencies'], 'name',
                $usertestdata[$userid]['competencies'], 1);
        }
    }

    /**
     * Helper function for common "contains" checking.
     * @param array $externaldata The data to test against.
     * @param string $extindex The external data array index to look for.
     * @param array $expecteddata The expected data.
     * @param int $expectedcount The number of items expected.
     */
    public function data_contains($externaldata, $extindex, $expecteddata, $expectedcount) {
        $testdata = [];
        $this->assertCount($expectedcount, $externaldata);
        foreach ($externaldata as $data) {
            $testdata[] = $data[$extindex];
        }
        foreach ($expecteddata as $testvalue) {
            $this->assertContains($testvalue, $testdata);
        }
    }
}