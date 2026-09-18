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
 * Legacy settings layout repair for enrol_notificationeabc.
 *
 * The e-ABC original (3.2.0.1) stored the per-instance alert toggles in
 * customint3 (enrol), customint4 (unenrol) and customint5 (update), with
 * customint1/2 unused (0). The 4.x maintenance base reads them from
 * customint1/2/3 instead. This migration moves existing legacy values into
 * the layout the current code reads, without inventing, zeroing or resetting
 * any value. Legacy values are archived into the unused columns
 * customint6/7/8 before the remap.
 *
 * Site-level config keys renamed by the 4.x base are copied to their new
 * names, but only when the new key has never been set - anything the site
 * admin entered after upgrading is never overwritten.
 *
 * @package    enrol_notificationeabc
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

defined('MOODLE_INTERNAL') || die();

/**
 * One-shot, idempotent repair of the settings layout (sentinel-gated).
 */
function enrol_notificationeabc_migrate_legacy_settings() {
    global $DB;

    if (get_config('enrol_notificationeabc', 'legacylayoutmigrated')) {
        return;
    }

    // 1) Per-instance toggles.
    $instances = $DB->get_recordset('enrol', ['enrol' => 'notificationeabc']);
    foreach ($instances as $row) {
        $c1 = (int)$row->customint1;
        $c2 = (int)$row->customint2;
        $c3 = (int)$row->customint3;
        $c4 = (int)$row->customint4;
        $c5 = (int)$row->customint5;

        // Legacy signature: the columns the 4.x base reads are unused while
        // the legacy columns carry state. Rows already on the new layout keep
        // customint4/5 at their defaults (0) and use customint1/2 actively.
        $islegacy = ($c4 !== 0) || ($c5 !== 0) || ($c1 === 0 && $c2 === 0 && $c3 !== 0);
        if (!$islegacy) {
            continue;
        }

        $update = new stdClass();
        $update->id = $row->id;
        // Archive the legacy values into the unused columns 6/7/8 first.
        $update->customint6 = $c3;
        $update->customint7 = $c4;
        $update->customint8 = $c5;
        // Remap into the layout the current code reads.
        $update->customint1 = $c3;
        $update->customint2 = $c4;
        $update->customint3 = $c5;

        $DB->update_record('enrol', $update);
    }
    $instances->close();

    // 2) Site-level config keys (old key => new key).
    $pairs = [
        'activeenrolalert'          => 'enrolalert',
        'activeunenrolalert'        => 'unenrolalert',
        'activeenrolupdatedalert'   => 'enrolupdatealert',
        'activarglobal'             => 'globalenrolalert',
        'activarglobalunenrolalert' => 'globalunenrolalert',
        'activarglobalenrolupdated' => 'globalenrolupdatealert',
        'location'                  => 'enrolmessage',
        'updatedenrolmessage'       => 'enrolupdatemessage',
    ];
    foreach ($pairs as $oldkey => $newkey) {
        $oldvalue = get_config('enrol_notificationeabc', $oldkey);
        if ($oldvalue === false || $oldvalue === null || $oldvalue === '') {
            continue;
        }
        $newvalue = get_config('enrol_notificationeabc', $newkey);
        if ($newvalue === false || $newvalue === null || $newvalue === '') {
            set_config($newkey, $oldvalue, 'enrol_notificationeabc');
        }
    }

    set_config('legacylayoutmigrated', 1, 'enrol_notificationeabc');
}