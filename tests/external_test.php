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
    public function test_get_user_profile($users = []) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/user/editlib.php');
        require_once($CFG->dirroot . '/user/profile/lib.php');

        $this->resetAfterTest(true);

        // Create a course to use.
        $course = self::getDataGenerator()->create_course();

        // Add a custom field of textarea type.
        $cfid1 = $DB->insert_record('user_info_field', array(
                'shortname' => 'frogdesc', 'name' => 'Description of frog', 'categoryid' => 1,
                'datatype' => 'textarea'));

        // Add another custom field, this time of normal text type.
        $cfid2 = $DB->insert_record('user_info_field', array(
                'shortname' => 'frogname', 'name' => 'Name of frog', 'categoryid' => 1,
                'datatype' => 'text'));

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
            $this->assertCount(2, $profiledata['datafields']);
            foreach ($profiledata['datafields'] as $datafield) {
                $testdata[] = $datafield['name'];
            }
            foreach ($usertestdata[$userid]['datafields'] as $testvalue) {
                $this->assertContains($testvalue, $testdata);
            }

            // Verify tag data.
            $this->assertCount(5, $profiledata['tags']);
            foreach ($profiledata['tags'] as $tag) {
                $testdata[] = $tag['name'];
            }
            foreach ($usertestdata[$userid]['tags'] as $testvalue) {
                $this->assertContains($testvalue, $testdata);
            }

            // Verify enrolment data.
            $this->assertCount(1, $profiledata['courseenrolments']);
            foreach ($profiledata['courseenrolments'] as $enrolment) {
                $testdata[] = $enrolment['courseid'];
            }
            foreach ($usertestdata[$userid]['enrolments'] as $testvalue) {
                $this->assertContains($testvalue, $testdata);
            }
        }
    }
}