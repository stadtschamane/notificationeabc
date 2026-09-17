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
 * Observer tests for enrol_notificationeabc.
 *
 * @package    enrol_notificationeabc
 * @category   test
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_notificationeabc;

use core\event\user_enrolment_created;
use core\event\user_enrolment_deleted;
use core\event\user_enrolment_updated;
use enrol_notificationeabc_observer;
use enrol_notificationeabc_plugin;

/**
 * Observer decision-logic tests: instance toggles, global fallback, dedupe.
 *
 * @package    enrol_notificationeabc
 * @category   test
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \enrol_notificationeabc_observer
 */
final class observer_test extends \advanced_testcase {

    /** @var \stdClass test course */
    protected $course;

    /** @var \stdClass enrolled student */
    protected $student;

    /** @var \stdClass notificationeabc enrol instance */
    protected $instance;

    /**
     * Fixtures: enabled plugin, student, course with a configured instance.
     */
    protected function setUp(): void {
        global $DB;

        parent::setUp();

        $this->resetAfterTest();

        $generator = $this->getDataGenerator();
        $this->course = $generator->create_course();
        $this->student = $generator->create_user();

        // Enable the plugin in site administration ("enrol_plugins_enabled").
        $enabled = enrol_get_plugins(true);
        $enabled['notificationeabc'] = true;
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));

        // Site level switches default to ON (settings.php default), instance off until configured.
        set_config('enrolalert', '1', 'enrol_notificationeabc');
        set_config('unenrolalert', '1', 'enrol_notificationeabc');
        set_config('enrolupdatealert', '1', 'enrol_notificationeabc');
        set_config('globalenrolalert', '0', 'enrol_notificationeabc');
        set_config('globalunenrolalert', '0', 'enrol_notificationeabc');
        set_config('globalenrolupdatealert', '0', 'enrol_notificationeabc');

        // Instance with all three toggles on.
        $plugin = enrol_get_plugin('notificationeabc');
        $fields = $plugin->get_instance_defaults();
        $fields['customtext1'] = 'Enrol message for {FIRSTNAME} {LASTNAME} in {COURSEFULLNAME} ({URL})';
        $fields['customtext2'] = 'Unenrol message for {FIRSTNAME} in {COURSEFULLNAME} (<b>{COURSEFULLNAME}</b>)';
        $fields['customtext3'] = 'Update message for {FIRSTNAME} in {COURSEFULLNAME}';
        $fields['customint1'] = 1;
        $fields['customint2'] = 1;
        $fields['customint3'] = 1;
        $fields['customchar1'] = '';
        $fields['customchar2'] = '';
        // The instance must be ENABLED for the observers to notify (the
        // notificationeabc instance is a passive block, but the send decision
        // requires status == ENROL_INSTANCE_ENABLED).
        $fields['status'] = ENROL_INSTANCE_ENABLED;
        $id = $plugin->add_instance($this->course, $fields);
        $this->instance = $DB->get_record('enrol', ['id' => $id], '*', MUST_EXIST);

        // Enrol the student manually (this fires user_enrolment_created; sink it).
        $manual = enrol_get_plugin('manual');
        $manualinstance = $DB->get_record('enrol', ['courseid' => $this->course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        $sink = $this->redirectMessages();
        $manual->enrol_user($manualinstance, $this->student->id);
        $sink->close();

        // The manual enrolment above fired a real user_enrolment_created event and
        // therefore marked (student, course, type 1) in the per-request dedupe guard.
        // Clear the guard so each test method starts with a clean notification state
        // (core resets loaded class statics between tests, but setUp runs within the
        // same request as the enrolment it just performed).
        $ref = new \ReflectionClass(enrol_notificationeabc_plugin::class);
        $prop = $ref->getProperty('sentnotifications');
        $prop->setAccessible(true);
        $prop->setValue(null, []);
    }

    /**
     * Fire user_enrolment_created and return the single notificationeabc message (or null).
     */
    protected function fire_enrolled(int $ueid, ?\stdClass $user = null, ?int $courseid = null): ?\stdClass {
        $sink = $this->redirectMessages();
        $event = user_enrolment_created::create([
            'objectid' => $ueid,
            'courseid' => $courseid ?? $this->course->id,
            'context' => \context_course::instance($courseid ?? $this->course->id),
            'relateduserid' => ($user ?? $this->student)->id,
            'other' => ['enrol' => 'notificationeabc'],
        ]);
        $event->trigger();
        $messages = $sink->get_messages_by_component_and_type('enrol_notificationeabc', 'notificationeabc_enrolment');
        $sink->close();
        return $messages ? reset($messages) : null;
    }

    /**
     * Fire user_enrolment_deleted and return the single notificationeabc message (or null).
     */
    protected function fire_unenrolled(int $ueid, array $ue, ?\stdClass $user = null, ?int $courseid = null): ?\stdClass {
        $sink = $this->redirectMessages();
        $event = user_enrolment_deleted::create([
            'objectid' => $ueid,
            'courseid' => $courseid ?? $this->course->id,
            'context' => \context_course::instance($courseid ?? $this->course->id),
            'relateduserid' => ($user ?? $this->student)->id,
            'other' => ['userenrolment' => array_merge((array)$ue, [
                'courseid' => $courseid ?? $this->course->id,
                'enrol' => 'notificationeabc',
                'lastenrol' => true,
            ]), 'enrol' => 'notificationeabc'],
        ]);
        $event->trigger();
        $messages = $sink->get_messages_by_component_and_type('enrol_notificationeabc', 'notificationeabc_enrolment');
        $sink->close();
        return $messages ? reset($messages) : null;
    }

    /**
     * Fire user_enrolment_updated and return the single notificationeabc message (or null).
     */
    protected function fire_updated(int $ueid, ?\stdClass $user = null, ?int $courseid = null): ?\stdClass {
        $sink = $this->redirectMessages();
        $event = user_enrolment_updated::create([
            'objectid' => $ueid,
            'courseid' => $courseid ?? $this->course->id,
            'context' => \context_course::instance($courseid ?? $this->course->id),
            'relateduserid' => ($user ?? $this->student)->id,
            'other' => ['enrol' => 'notificationeabc'],
        ]);
        $event->trigger();
        $messages = $sink->get_messages_by_component_and_type('enrol_notificationeabc', 'notificationeabc_enrolment');
        $sink->close();
        return $messages ? reset($messages) : null;
    }

    /**
     * A user_enrolments row for the test student on the manual enrol instance
     * (the event objectid only needs to reference an existing enrolment row;
     * the observer resolves the notificationeabc instance by course).
     */
    protected function ue_record(): \stdClass {
        global $DB;
        $manual = $DB->get_record('enrol', ['courseid' => $this->course->id, 'enrol' => 'manual'], '*', MUST_EXIST);
        return $DB->get_record('user_enrolments',
            ['enrolid' => $manual->id, 'userid' => $this->student->id], '*', MUST_EXIST);
    }

    /**
     * Instance toggle on + instance enabled: enrol message is sent.
     */
    public function test_enrol_message_sent_when_instance_toggle_on(): void {
        $ue = $this->ue_record();
        $message = $this->fire_enrolled((int)$ue->id);

        $this->assertNotNull($message);
        $this->assertSame($this->student->id, $message->useridto);
        $this->assertSame('Enrolment email notification', $message->subject);
    }

    /**
     * Instance toggle off (customint1 = 0): enrol message is NOT sent.
     */
    public function test_enrol_message_not_sent_when_instance_toggle_off(): void {
        global $DB;
        $DB->set_field('enrol', 'customint1', 0, ['id' => $this->instance->id]);
        $this->instance = $DB->get_record('enrol', ['id' => $this->instance->id], '*', MUST_EXIST);

        $ue = $this->ue_record();
        $this->assertNull($this->fire_enrolled((int)$ue->id));
    }

    /**
     * Instance disabled (hidden): no send even with the toggle on.
     */
    public function test_enrol_message_not_sent_when_instance_disabled(): void {
        global $DB;
        $DB->set_field('enrol', 'status', ENROL_INSTANCE_DISABLED, ['id' => $this->instance->id]);
        $this->instance = $DB->get_record('enrol', ['id' => $this->instance->id], '*', MUST_EXIST);

        $ue = $this->ue_record();
        $this->assertNull($this->fire_enrolled((int)$ue->id));
    }

    /**
     * Site master switch off: no send regardless of instance toggles.
     */
    public function test_enrol_message_not_sent_when_site_switch_off(): void {
        set_config('enrolalert', '0', 'enrol_notificationeabc');

        $ue = $this->ue_record();
        $this->assertNull($this->fire_enrolled((int)$ue->id));
    }

    /**
     * Global fallback: a course WITHOUT a notificationeabc instance notifies
     * only when the global switch is on. Uses a fresh course without any
     * instance (no FK-hazardous instance deletion).
     */
    public function test_global_fallback_when_no_instance(): void {
        global $DB;

        // A second course: generator gives it a manual instance but no
        // notificationeabc instance.
        $course2 = $this->getDataGenerator()->create_course();
        $manual2 = $DB->get_record('enrol', ['courseid' => $course2->id, 'enrol' => 'manual'], '*', MUST_EXIST);

        // Global fallback off: nothing.
        set_config('globalenrolalert', '0', 'enrol_notificationeabc');
        $sink = $this->redirectMessages();
        $this->fire_enrolled((int)$manual2->id, null, (int)$course2->id);
        $this->assertCount(0, $sink->get_messages_by_component_and_type('enrol_notificationeabc', 'notificationeabc_enrolment'));
        $sink->clear();

        // Global fallback on: message for the same course (dedupe state is
        // clean because the first event did not send).
        set_config('globalenrolalert', '1', 'enrol_notificationeabc');
        $message = $this->fire_enrolled((int)$manual2->id, null, (int)$course2->id);
        $this->assertNotNull($message);
        $this->assertSame((int)$this->student->id, (int)$message->useridto);
        $sink->clear();
        $sink->close();
    }

    /**
     * Dedupe: the same user+course+type is only notified once per request.
     */
    public function test_dedupe_suppresses_second_enrol_event(): void {
        $ue = $this->ue_record();

        // First event sends, second is silently suppressed.
        $this->assertNotNull($this->fire_enrolled((int)$ue->id));
        $this->assertNull($this->fire_enrolled((int)$ue->id));
    }

    /**
     * Dedupe scope: a different type still sends.
     */
    public function test_dedupe_scope_is_per_type(): void {
        $ue = $this->ue_record();

        $this->assertNotNull($this->fire_enrolled((int)$ue->id));
        // Update type for the same user+course is a different combination and must send.
        $message = $this->fire_updated((int)$ue->id);
        $this->assertNotNull($message);
        $this->assertSame('Enrolment email notification', $message->subject); // Subject default (no subjectupdate configured).
    }

    /**
     * Unenrol: message built from the instance unenrol message when toggle on.
     */
    public function test_unenrol_message_sent_when_toggle_on(): void {
        $ue = $this->ue_record();
        $uerow = (array)$ue;

        $message = $this->fire_unenrolled((int)$ue->id, $uerow);
        $this->assertNotNull($message);
        $this->assertSame('Enrolment email notification', $message->subject);
    }

    /**
     * Unenrol: toggle off suppresses.
     */
    public function test_unenrol_message_not_sent_when_toggle_off(): void {
        global $DB;
        $DB->set_field('enrol', 'customint2', 0, ['id' => $this->instance->id]);
        $this->instance = $DB->get_record('enrol', ['id' => $this->instance->id], '*', MUST_EXIST);

        $ue = $this->ue_record();
        $this->assertNull($this->fire_unenrolled((int)$ue->id, (array)$ue));
    }

    /**
     * Unenrol: site master switch off suppresses.
     */
    public function test_unenrol_message_not_sent_when_site_switch_off(): void {
        set_config('unenrolalert', '0', 'enrol_notificationeabc');

        $ue = $this->ue_record();
        $this->assertNull($this->fire_unenrolled((int)$ue->id, (array)$ue));
    }

    /**
     * Update: message sent with toggle on.
     */
    public function test_update_message_sent_when_toggle_on(): void {
        $ue = $this->ue_record();
        $message = $this->fire_updated((int)$ue->id);

        $this->assertNotNull($message);
        $this->assertSame($this->student->id, $message->useridto);
    }

    /**
     * Update: toggle off suppresses.
     */
    public function test_update_message_not_sent_when_toggle_off(): void {
        global $DB;
        $DB->set_field('enrol', 'customint3', 0, ['id' => $this->instance->id]);
        $this->instance = $DB->get_record('enrol', ['id' => $this->instance->id], '*', MUST_EXIST);

        $ue = $this->ue_record();
        $this->assertNull($this->fire_updated((int)$ue->id));
    }

    /**
     * Hidden courses are skipped unless includehiddencourses is set.
     */
    public function test_hidden_course_skipped_unless_configured(): void {
        global $DB;
        $DB->set_field('course', 'visible', 0, ['id' => $this->course->id]);

        $ue = $this->ue_record();
        $this->assertNull($this->fire_enrolled((int)$ue->id));

        set_config('includehiddencourses', '1', 'enrol_notificationeabc');
        $message = $this->fire_enrolled((int)$ue->id);
        $this->assertNotNull($message);
    }

    /**
     * Deleted users get no notification.
     */
    public function test_deleted_user_not_notified(): void {
        global $DB;
        $DB->set_field('user', 'deleted', 1, ['id' => $this->student->id]);
        $user = $DB->get_record('user', ['id' => $this->student->id], '*', MUST_EXIST);

        $ue = $this->ue_record();
        $this->assertNull($this->fire_enrolled((int)$ue->id, $user));
    }

    /**
     * Suspended users get no notification.
     */
    public function test_suspended_user_not_notified(): void {
        global $DB;
        $DB->set_field('user', 'suspended', 1, ['id' => $this->student->id]);
        $user = $DB->get_record('user', ['id' => $this->student->id], '*', MUST_EXIST);

        $ue = $this->ue_record();
        $this->assertNull($this->fire_enrolled((int)$ue->id, $user));
    }

    /**
     * Plugin not enabled site-wide: no notification.
     */
    public function test_plugin_not_enabled_skips_send(): void {
        $enabled = enrol_get_plugins(true);
        unset($enabled['notificationeabc']);
        set_config('enrol_plugins_enabled', implode(',', array_keys($enabled)));

        $ue = $this->ue_record();
        $this->assertNull($this->fire_enrolled((int)$ue->id));
    }

    /**
     * Dedupe guard helpers (pure, no DB).
     */
    public function test_dedupe_guard_helpers(): void {
        $this->assertFalse(enrol_notificationeabc_plugin::is_duplicate_notification(1, 2, 3));
        enrol_notificationeabc_plugin::mark_notification_sent(1, 2, 3);
        $this->assertTrue(enrol_notificationeabc_plugin::is_duplicate_notification(1, 2, 3));
        $this->assertFalse(enrol_notificationeabc_plugin::is_duplicate_notification(1, 2, 4));
        $this->assertFalse(enrol_notificationeabc_plugin::is_duplicate_notification(2, 2, 3));
    }
}