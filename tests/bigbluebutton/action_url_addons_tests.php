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

namespace bbbext_bnurl\bigbluebuttonbn;

use bbbext_bnurl\utils;
use mod_bigbluebuttonbn\extension;
use mod_bigbluebuttonbn\external\get_join_url;
use mod_bigbluebuttonbn\instance;

/**
 * Action URL addons tests
 *
 * @package   bbbext_bnurl
 * @copyright 2023 onwards, Blindside Networks Inc
 * @license   http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author    Laurent David (laurent@call-learning.fr)
 */
class action_url_addons_tests extends \advanced_testcase {
    /**
     * @var \stdClass $bbb
     */
    protected $bbb;
    /**
     * @var \stdClass $course
     */
    protected $course;
    /**
     * @var \stdClass $user
     */
    protected $user;

    /**
     * Setup
     */
    public function setUp(): void {
        global $DB;
        $this->resetAfterTest();
        $datagenerator = $this->getDataGenerator();
        $this->course = $datagenerator->create_course(['fullname' => 'BBBCourse FULL', 'shortname' => 'BBBCourse']);
        $this->user = $datagenerator->create_user(['firstname' => 'BBB User FN', 'lastname' => 'BBB LN',
            'email' => 'bbb@blindsidenetworks.com', ]);
        $bbbgenerator = $datagenerator->get_plugin_generator('mod_bigbluebuttonbn');
        $this->bbb = $bbbgenerator->create_instance(['name' => 'BBB Activity', 'course' => $this->course->id]);
        set_config('available_info', 'user, courseinfo, activityinfo', 'bbbext_bnurl');
        $datagenerator->enrol_user($this->user->id, $this->course->id);
        $DB->insert_record('bbbext_bnurl',
            ['bigbluebuttonbnid' => $this->bbb->id, 'eventtype' => utils::ACTION_CODES['create'], 'paramname' => 'firstname',
                'paramvalue' => 'user.firstname', ]);
        $DB->insert_record('bbbext_bnurl',
            ['bigbluebuttonbnid' => $this->bbb->id, 'eventtype' => utils::ACTION_CODES['join'], 'paramname' => 'lastname',
                'paramvalue' => 'user.lastname', ]);
        $DB->insert_record('bbbext_bnurl',
            ['bigbluebuttonbnid' => $this->bbb->id, 'eventtype' => utils::ACTION_CODES['all'], 'paramname' => 'coursename',
                'paramvalue' => 'courseinfo.fullname', ]);
    }

    /**
     * Test join URL
     *
     * @return void
     */
    public function test_join_url_has_options() {
        $this->setUser($this->user);
        $instance = instance::get_from_instanceid($this->bbb->id);
        $joinurl = get_join_url::execute($instance->get_cm_id());
        $this->assertStringContainsString('lastname', $joinurl['join_url']);
        $this->assertStringContainsString('coursename', $joinurl['join_url']);
        $this->assertStringContainsString('BBB+LN', $joinurl['join_url']);
    }

    /**
     * Test create URL
     *
     * @return void
     */
    public function test_create_url_has_options() {
        $this->setUser($this->user);
        instance::get_from_instanceid($this->bbb->id);
        $addons = extension::action_url_addons('create', [], ['bbb-meta' => 'Test'], $this->bbb->id);
        $this->assertContains('firstname', array_keys($addons['metadata']));
        $this->assertContains('coursename', array_keys($addons['metadata']));
        $this->assertNotContains('lastname', array_keys($addons['metadata']));
    }
}
