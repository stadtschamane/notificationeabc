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
 * Notificationeabc enrolment plugin.
 *
 * This plugin notifies users when an event occurs on their enrolments (enrol, unenrol, update enrolment)
 *
 * @package    enrol_notificationeabc
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Osvaldo Arriola <osvaldo@e-abclearning.com>
 */

defined('MOODLE_INTERNAL') || die();
require_once($CFG->dirroot . '/enrol/notificationeabc/lib.php');

/**
 * Observer definition
 *
 * @package    enrol_notificationeabc
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Osvaldo Arriola <osvaldo@e-abclearning.com>
 */
class enrol_notificationeabc_observer
{

    /**
     * hook enrol event
     * @param \core\event\user_enrolment_deleted $event
     */
    public static function user_unenrolled(\core\event\user_enrolment_deleted $event) {
        global $DB;

        $pluginconfig = get_config('enrol_notificationeabc');
        $unenrolalert = $pluginconfig->unenrolalert;

        // Site level switch: master on/off for unenrol notifications.
        if (!$unenrolalert) {
            return;
        }

        // Skip duplicates for the same user+course+type within this request/CLI run.
        if (enrol_notificationeabc_plugin::is_duplicate_notification($event->relateduserid, $event->courseid, 2)) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $event->courseid]);
        if (empty($course)) {
            return;
        }
        // Not set message for hidden courses.
        if (!$course->visible && !$pluginconfig->includehiddencourses) {
            return;
        }

        $user = $DB->get_record('user', ['id' => $event->relateduserid]);
        if (empty($user) || $user->deleted || $user->suspended) {
            return;
        }

        // Validate status plugin.
        $enableplugins = get_config(null, 'enrol_plugins_enabled');
        $enableplugins = explode(',', $enableplugins);
        $enabled = false;
        foreach ($enableplugins as $enableplugin) {
            if ($enableplugin === 'notificationeabc') {
                $enabled = true;
            }
        }
        if ($enabled) {

            $notificationeabc = new enrol_notificationeabc_plugin();

            $enrol = $DB->get_record('enrol', ['enrol' => 'notificationeabc', 'courseid' => $event->courseid]);

            if (!empty($enrol)) {
                // Per instance decision: send only when the instance is enabled and
                // the matching customint2 toggle is set. Otherwise the message was
                // disabled at course level and no notification is sent.
                if ($enrol->status == ENROL_INSTANCE_ENABLED && !empty($enrol->customint2)) {
                    $notificationeabc->send_email($user, $course, 2, $enrol);
                }
            } else {
                // No instance in the course: fall back to the global site setting.
                $activeglobal = $pluginconfig->globalunenrolalert;
                if ($activeglobal == 1) {
                    $notificationeabc->send_email($user, $course, 2);
                }
            }
        }
    }

    /**
     * hook user update event
     * @param \core\event\user_enrolment_updated $event
     */
    public static function user_updated(\core\event\user_enrolment_updated $event) {
        global $DB;

        $pluginconfig = get_config('enrol_notificationeabc');
        $enrolupdatealert = $pluginconfig->enrolupdatealert;

        // Site level switch: master on/off for enrol update notifications.
        if (!$enrolupdatealert) {
            return;
        }

        // Skip duplicates for the same user+course+type within this request/CLI run.
        if (enrol_notificationeabc_plugin::is_duplicate_notification($event->relateduserid, $event->courseid, 3)) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $event->courseid]);
        if (empty($course)) {
            return;
        }
        // Not set message for hidden courses.
        if (!$course->visible && !$pluginconfig->includehiddencourses) {
            return;
        }

        $user = $DB->get_record('user', ['id' => $event->relateduserid]);
        if (empty($user) || $user->deleted || $user->suspended) {
            return;
        }

        // Validate plugin status in system context.
        $enableplugins = get_config(null, 'enrol_plugins_enabled');
        $enableplugins = explode(',', $enableplugins);
        $enabled = false;
        foreach ($enableplugins as $enableplugin) {
            if ($enableplugin === 'notificationeabc') {
                $enabled = true;
            }
        }
        if ($enabled) {

            $notificationeabc = new enrol_notificationeabc_plugin();

            // Plugin instance in course.
            $enrol = $DB->get_record('enrol', ['enrol' => 'notificationeabc', 'courseid' => $event->courseid]);
            $enrollment = $DB->get_record('user_enrolments', ['id' => $event->objectid]) ?: null;

            if (!empty($enrol)) {
                // Per instance decision: send only when the instance is enabled and
                // the matching customint3 toggle is set (customint3 = 1 notification
                // enabled, customint3 = 0 notification disabled at course level).
                if ($enrol->status == ENROL_INSTANCE_ENABLED && !empty($enrol->customint3)) {
                    $notificationeabc->send_email($user, $course, 3, $enrol, $enrollment);
                }
            } else {
                // No instance in the course: fall back to the global site setting.
                $activeglobal = $pluginconfig->globalenrolupdatealert;
                if ($activeglobal == 1) {
                    $notificationeabc->send_email($user, $course, 3, null, $enrollment);
                }
            }
        }
    }

    /**
     * hook enrolment event
     * @param \core\event\user_enrolment_created $event
     */
    public static function user_enrolled(\core\event\user_enrolment_created $event) {
        global $DB;

        $pluginconfig = get_config('enrol_notificationeabc');
        $enrolalert = $pluginconfig->enrolalert;

        // Site level switch: master on/off for enrol notifications.
        if (!$enrolalert) {
            return;
        }

        // Skip duplicates for the same user+course+type within this request/CLI run.
        if (enrol_notificationeabc_plugin::is_duplicate_notification($event->relateduserid, $event->courseid, 1)) {
            return;
        }

        $course = $DB->get_record('course', ['id' => $event->courseid]);
        if (empty($course)) {
            return;
        }
        // Not set message for hidden courses.
        if (!$course->visible && !$pluginconfig->includehiddencourses) {
            return;
        }

        $user = $DB->get_record('user', ['id' => $event->relateduserid]);
        if (empty($user) || $user->deleted || $user->suspended) {
            return;
        }

        // Validate plugin status in system context.
        $enableplugins = get_config(null, 'enrol_plugins_enabled');
        $enableplugins = explode(',', $enableplugins);
        $enabled = false;
        foreach ($enableplugins as $enableplugin) {
            if ($enableplugin === 'notificationeabc') {
                $enabled = true;
            }
        }

        if ($enabled) {

            $notificationeabc = new enrol_notificationeabc_plugin();

            $enrol = $DB->get_record('enrol', ['enrol' => 'notificationeabc', 'courseid' => $event->courseid]);
            $enrollment = $DB->get_record('user_enrolments', ['id' => $event->objectid]) ?: null;

            if (!empty($enrol)) {
                // Per instance decision: send only when the instance is enabled and
                // the matching customint1 toggle is set (customint1 = 1 notification
                // enabled, customint1 = 0 notification disabled at course level).
                if ($enrol->status == ENROL_INSTANCE_ENABLED && !empty($enrol->customint1)) {
                    $notificationeabc->send_email($user, $course, 1, $enrol, $enrollment);
                }

            } else {
                // No instance in the course: fall back to the global site setting.
                $activeglobal = $pluginconfig->globalenrolalert;
                if ($activeglobal == 1) {
                    $notificationeabc->send_email($user, $course, 1, null, $enrollment);
                }
            }
        }
    }
}
