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
 * send_email() tests for enrol_notificationeabc.
 *
 * @package    enrol_notificationeabc
 * @category   test
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_notificationeabc;

use enrol_notificationeabc_plugin;

/**
 * Message-building tests: per-type subjects, placeholders, escaping, sender.
 *
 * @package    enrol_notificationeabc
 * @category   test
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \enrol_notificationeabc_plugin
 */
final class message_test extends \advanced_testcase {

    /** @var \stdClass test course */
    protected $course;

    /** @var \stdClass enrolled student */
    protected $student;

    /** @var \stdClass notificationeabc enrol instance */
    protected $instance;

    /**
     * Fixtures: plugin enabled, course, student, instance with all toggles on.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->student = $generator->create_user();

        $enabled = enrol_get_plugins(true);
        $enabled['notificationeabc'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));

        set_config('enrolalert', '1', 'enrol_notificationeabc');
        set_config('unenrolalert', '1', 'enrol_notificationeabc');
        set_config('enrolupdatealert', '1', 'enrol_notificationeabc');

        $plugin = enrol_get_plugin('notificationeabc');
        $fields = $plugin->get_instance_defaults();
        $fields['customtext1'] = 'Hi {FIRSTNAME} {LASTNAME} in {COURSEFULLNAME} at {URL}';
        $fields['customtext2'] = 'Bye {FIRSTNAME} from {COURSEFULLNAME}';
        $fields['customtext3'] = 'Changed: {USERNAME} in {COURSEFULLNAME}';
        $fields['customint1'] = 1;
        $fields['customint2'] = 1;
        $fields['customint3'] = 1;
        $fields['customchar1'] = '';
        $fields['customchar2'] = '';
        $id = $plugin->add_instance($this->course, $fields);
        $this->instance = $DB->get_record('enrol', ['id' => $id], '*', MUST_EXIST);

        $this->reset_guard();
    }

    /**
     * Clear the per-request dedupe guard between direct send_email calls.
     */
    protected function reset_guard(): void {
        $ref = new \ReflectionClass(enrol_notificationeabc_plugin::class);
        $prop = $ref->getProperty('sentnotifications');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    /**
     * Call send_email for a type and return the captured message (or null).
     */
    protected function send(int $type): ?\stdClass {
        $this->reset_guard();
        $sink = $this->redirectMessages();
        $plugin = new enrol_notificationeabc_plugin();
        $plugin->send_email($this->student, $this->course, $type, $this->instance);
        $messages = $sink->get_messages_by_component_and_type('enrol_notificationeabc', 'notificationeabc_enrolment');
        $sink->close();
        return $messages ? reset($messages) : null;
    }

    /**
     * Default subject is the generic subject string for all three types.
     */
    public function test_default_subject_for_all_types(): void {
        foreach ([1, 2, 3] as $type) {
            $message = $this->send($type);
            $this->assertNotNull($message);
            $this->assertSame('Enrolment email notification', $message->subject, "Type $type subject");
        }
    }

    /**
     * Per-type subject settings override the generic subject.
     */
    public function test_per_type_subject_settings(): void {
        set_config('subjectenrol', 'Custom in', 'enrol_notificationeabc');
        set_config('subjectunenrol', 'Custom out', 'enrol_notificationeabc');
        set_config('subjectupdate', 'Custom changed', 'enrol_notificationeabc');

        $this->assertSame('Custom in', $this->send(1)->subject);
        $this->assertSame('Custom out', $this->send(2)->subject);
        $this->assertSame('Custom changed', $this->send(3)->subject);
    }

    /**
     * Placeholders (incl. the new English ones) are replaced in the HTML body.
     */
    public function test_placeholders_replaced(): void {
        $message = $this->send(1);

        $this->assertStringContainsString(s($this->student->firstname), $message->fullmessagehtml);
        $this->assertStringContainsString(s($this->student->lastname), $message->fullmessagehtml);
        $this->assertStringContainsString(format_string($this->course->fullname), $message->fullmessagehtml);
        $this->assertStringContainsString('/course/view.php?id=' . $this->course->id, $message->fullmessagehtml);
        $this->assertStringNotContainsString('{FIRSTNAME}', $message->fullmessagehtml);
        $this->assertStringNotContainsString('{COURSENAME}', $message->fullmessagehtml);
    }

    /**
     * User data is escaped for the HTML context (no raw HTML from user data).
     */
    public function test_user_data_escaped(): void {
        global $DB;
        $DB->set_field('user', 'firstname', 'R&d <b>bold</b>', ['id' => $this->student->id]);
        $this->student = $DB->get_record('user', ['id' => $this->student->id], '*', MUST_EXIST);

        $message = $this->send(1);
        $this->assertNotNull($message);
        $this->assertStringContainsString(s('R&d <b>bold</b>'), $message->fullmessagehtml);
        $this->assertStringContainsString('&lt;b&gt;bold&lt;/b&gt;', $message->fullmessagehtml);
        $this->assertStringNotContainsString('<b>bold</b>', $message->fullmessagehtml);
    }

    /**
     * The support user record is never mutated by the sender build (A6).
     */
    public function test_support_user_not_mutated(): void {
        $support = \core_user::get_support_user();
        $namebefore = fullname($support);
        $emailbefore = $support->email;

        $this->send(1);

        $after = \core_user::get_support_user();
        $this->assertSame($namebefore, fullname($after));
        $this->assertSame($emailbefore, $after->email);
    }

    /**
     * Mobile message is a non-empty plain-text summary; fullmessage carries
     * a plaintext derivation instead of an empty string (A4).
     */
    public function test_mobile_and_plain_message_populated(): void {
        foreach ([1, 2, 3] as $type) {
            $message = $this->send($type);
            $this->assertNotNull($message, "Type $type sent");
            $this->assertNotEmpty($message->smallmessage, "Type $type smallmessage");
            $this->assertNotEmpty($message->fullmessage, "Type $type fullmessage");
        }
    }
}