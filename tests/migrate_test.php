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
 * Legacy settings migration tests for enrol_notificationeabc.
 *
 * @package    enrol_notificationeabc
 * @category   test
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 */

namespace enrol_notificationeabc;

/**
 * Tests for the one-shot legacy layout repair in db/migrate.php.
 *
 * @package    enrol_notificationeabc
 * @category   test
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 *
 * @coversDefaultClass \enrol_notificationeabc_migrate_legacy_settings
 */
final class migrate_test extends \advanced_testcase {

    /**
     * Insert an enrol row with explicit customint values.
     */
    protected function make_instance(array $ints, bool $legacy = true): \stdClass {
        global $DB;

        $course = $this->getDataGenerator()->create_course();
        $record = [
            'enrol' => 'notificationeabc',
            'courseid' => $course->id,
            'status' => ENROL_INSTANCE_ENABLED,
            'customint1' => $ints[0],
            'customint2' => $ints[1],
            'customint3' => $ints[2],
            'customint4' => $ints[3],
            'customint5' => $ints[4],
        ];
        $id = $DB->insert_record('enrol', (object)$record);
        return $DB->get_record('enrol', ['id' => $id], '*', MUST_EXIST);
    }

    /**
     * Legacy rows (e-ABC layout) are remapped and archived.
     *
     * @covers ::enrol_notificationeabc_migrate_legacy_settings
     */
    public function test_legacy_rows_are_remapped_and_archived(): void {
        global $DB;

        // e-ABC signature: customint1/2 = 0 (unused), toggles in 3/4/5.
        $a = $this->make_instance([0, 0, 1, 0, 0]); // enrol only.
        $b = $this->make_instance([0, 0, 1, 1, 0]); // enrol + unenrol.
        $c = $this->make_instance([0, 0, 0, 0, 1]); // update only.

        enrol_notificationeabc_migrate_legacy_settings();

        $a = $DB->get_record('enrol', ['id' => $a->id]);
        $this->assertSame(1, (int)$a->customint1);
        $this->assertSame(0, (int)$a->customint2);
        $this->assertSame(0, (int)$a->customint3);
        $this->assertSame(1, (int)$a->customint6); // Archive.

        $b = $DB->get_record('enrol', ['id' => $b->id]);
        $this->assertSame(1, (int)$b->customint1);
        $this->assertSame(1, (int)$b->customint2);
        $this->assertSame(0, (int)$b->customint3);
        $this->assertSame(1, (int)$b->customint6);
        $this->assertSame(1, (int)$b->customint7);

        $c = $DB->get_record('enrol', ['id' => $c->id]);
        $this->assertSame(0, (int)$c->customint1);
        $this->assertSame(0, (int)$c->customint2);
        $this->assertSame(1, (int)$c->customint3);
        $this->assertSame(1, (int)$c->customint8); // Archive.
    }

    /**
     * Rows already using the new layout are left untouched.
     *
     * @covers ::enrol_notificationeabc_migrate_legacy_settings
     */
    public function test_new_layout_rows_are_untouched(): void {
        global $DB;

        // 4.x layout: toggles in customint1/2/3, customint4/5 stay 0.
        $a = $this->make_instance([1, 1, 0, 0, 0]);
        $b = $this->make_instance([0, 0, 0, 0, 0]);

        enrol_notificationeabc_migrate_legacy_settings();

        $a = $DB->get_record('enrol', ['id' => $a->id]);
        $this->assertSame(1, (int)$a->customint1);
        $this->assertSame(1, (int)$a->customint2);
        $this->assertSame(0, (int)$a->customint3);
        $this->assertSame(0, (int)$a->customint6);
        $this->assertSame(0, (int)$a->customint7);
        $this->assertSame(0, (int)$a->customint8);

        $b = $DB->get_record('enrol', ['id' => $b->id]);
        $this->assertSame(0, (int)$b->customint6);
    }

    /**
     * The migration runs exactly once (sentinel), so re-running it does not
     * re-shuffle rows that have already been remapped.
     *
     * @covers ::enrol_notificationeabc_migrate_legacy_settings
     */
    public function test_migration_is_idempotent(): void {
        global $DB;

        $a = $this->make_instance([0, 0, 1, 0, 0]);

        enrol_notificationeabc_migrate_legacy_settings();
        enrol_notificationeabc_migrate_legacy_settings(); // Second run: no-op.

        $a = $DB->get_record('enrol', ['id' => $a->id]);
        $this->assertSame(1, (int)$a->customint1);
        $this->assertSame(0, (int)$a->customint2);
        $this->assertSame(0, (int)$a->customint3);
    }

    /**
     * Renamed site-level config keys are copied to the new names, but never
     * overwriting values the admin already entered under the new keys.
     *
     * @covers ::enrol_notificationeabc_migrate_legacy_settings
     */
    public function test_site_config_keys_are_migrated_without_overwrites(): void {
        // Old keys present (as a site upgraded from e-ABC would have).
        set_config('activeenrolalert', '1', 'enrol_notificationeabc');
        set_config('activarglobal', '1', 'enrol_notificationeabc');
        set_config('location', '<p>Legacy enrol text</p>', 'enrol_notificationeabc');
        set_config('updatedenrolmessage', 'Legacy update text', 'enrol_notificationeabc');

        // Admin already set one of the new keys after upgrading.
        set_config('enrolmessage', '<p>Admin new text</p>', 'enrol_notificationeabc');

        enrol_notificationeabc_migrate_legacy_settings();

        $this->assertSame('1', get_config('enrol_notificationeabc', 'enrolalert'));
        $this->assertSame('1', get_config('enrol_notificationeabc', 'globalenrolalert'));
        $this->assertSame('<p>Admin new text</p>', get_config('enrol_notificationeabc', 'enrolmessage'));
        $this->assertSame('Legacy update text', get_config('enrol_notificationeabc', 'enrolupdatemessage'));
    }
}