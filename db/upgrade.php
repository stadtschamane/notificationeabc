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

/**
 * Upgrade script
 * @param int $oldversion
 * @return bool
 */
function xmldb_enrol_notificationeabc_upgrade($oldversion) {
    global $CFG;

    // v4.5.0.2 (2026091701): repair the settings layout that silently changed
    // across the 4.x maintenance. The e-ABC original (3.2.0.1) stored the
    // per-instance alert toggles in customint3/4/5; the 4.x base reads them
    // from customint1/2/3. Without this step, existing instances showed the
    // old enrol-alert value as the update-alert toggle and vice versa.
    // The same applies to renamed site-level config keys.
    if ($oldversion < 2026091701) {
        require_once($CFG->dirroot . '/enrol/notificationeabc/db/migrate.php');
        enrol_notificationeabc_migrate_legacy_settings();
        upgrade_plugin_savepoint(true, 2026091701, 'enrol', 'notificationeabc');
    }

    return true;
}