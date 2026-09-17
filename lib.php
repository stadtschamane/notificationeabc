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

use core\context\user;

defined('MOODLE_INTERNAL') || die();

require_once($CFG->dirroot . '/lib/moodlelib.php');
require_once($CFG->dirroot . '/user/profile/lib.php');

/**
 * Lib class
 *
 * @package    enrol_notificationeabc
 * @copyright  2017 e-ABC Learning
 * @license    http://www.gnu.org/copyleft/gpl.html GNU GPL v3 or later
 * @author     Osvaldo Arriola <osvaldo@e-abclearning.com>
 */
class enrol_notificationeabc_plugin extends enrol_plugin
{
    /** @var log.*/
    private $log = '';

    /**
     * Static per-request guard holding the user|course|type combinations that have
     * already been notified during the current request/CLI run.
     *
     * Bulk operations (e.g. the user enrolment bulk import) fire both
     * user_enrolment_created and user_enrolment_updated events for the same
     * enrolment, which would otherwise produce duplicate notifications.
     *
     * @var array
     */
    private static $sentnotifications = [];

    /**
     * Check whether a notification for this user+course+type has already been sent
     * during the current request/CLI run. Pure check, does not register anything.
     *
     * @param int $userid
     * @param int $courseid
     * @param int $type
     * @return bool true if the combination was already notified
     */
    public static function is_duplicate_notification(int $userid, int $courseid, int $type): bool {
        return isset(self::$sentnotifications[$userid . '|' . $courseid . '|' . $type]);
    }

    /**
     * Register that a notification for this user+course+type has been sent during
     * the current request/CLI run, so any duplicate event is silently suppressed.
     *
     * @param int $userid
     * @param int $courseid
     * @param int $type
     */
    public static function mark_notification_sent(int $userid, int $courseid, int $type): void {
        self::$sentnotifications[$userid . '|' . $courseid . '|' . $type] = true;
    }

    // Funcion que envia las notificaciones a los usuarios.

    /**
     * Send mail method
     * @param stdClass $user user instance
     * @param stdClass $course course instance
     * @param int $type notice enrollment, update or unenrollment
     * @param stdClass $enrol enrol instance
     * @param stdClass $enrollment enrollment instance, only for update and enrollment type
     * @return bool
     */
    public function send_email(stdClass $user, stdClass $course, int $type, stdClass $enrol = null, stdClass $enrollment = null) {
        global $CFG;

        // Deduplicate: the same user+course+type must not be notified twice within a
        // single request/CLI run (bulk operations fire several enrolment events).
        // The check happens BEFORE any message building so the suppressed duplicate
        // does no work. Returning false here intentionally does NOT log a failure:
        // this is a silent skip, distinct from a real send error.
        if (self::is_duplicate_notification($user->id, $course->id, (int)$type)) {
            return false;
        }
        self::mark_notification_sent($user->id, $course->id, (int)$type);

        $course->url = $CFG->wwwroot . '/course/view.php?id=' . $course->id;

        $pluginconfig = get_config('enrol_notificationeabc');

        $enrolmessage = '';
        $unenrolmessage = '';
        $enrolupdatemessage = '';

        if ($enrol) {
            // Course level.
            $enrolmessage = $enrol->customtext1;
            $unenrolmessage = $enrol->customtext2;
            $enrolupdatemessage = $enrol->customtext3;
        } else {
            // Plugin site configuration level.

            if (!$enrolmessage = ($pluginconfig->enrolmessage ?? '')) {
                $enrolmessage = get_string('enrolmessagedefault', 'enrol_notificationeabc', $course);
            }

            if (!$unenrolmessage = ($pluginconfig->unenrolmessage ?? '')) {
                $unenrolmessage = get_string('unenrolmessagedefault', 'enrol_notificationeabc', $course);
            }

            if (!$enrolupdatemessage = ($pluginconfig->enrolupdatemessage ?? '')) {
                $enrolupdatemessage = get_string('enrolupdatemessagedefault', 'enrol_notificationeabc', $course);
            }
        }

        $message = null;
        switch ((int)$type) {
            case 1:
                $message = $this->get_message($enrolmessage, $user, $course, $enrollment);
                break;
            case 2:
                $message = $this->get_message($unenrolmessage, $user, $course, $enrollment);
                break;
            case 3:
                $message = $this->get_message($enrolupdatemessage, $user, $course, $enrollment);
                break;
        }

        if (empty($message)) {
            return false;
        }

        $supportuser = \core_user::get_support_user();

        // Never mutate the support user: build the sender on a clone.
        $sender = clone $supportuser;

        // Sender email precedence: instance customchar1 override, then the site
        // emailsender setting, then the support user email. An empty/absent
        // override must never produce an empty From address.
        $emailsender = get_config('enrol_notificationeabc', 'emailsender');
        if ($enrol && !empty($enrol->customchar1)) {
            $sender->email = $enrol->customchar1;
        } else if (!empty($emailsender)) {
            $sender->email = $emailsender;
        }

        // Sender name precedence: instance customchar2 override, then the site
        // namesender setting, then the support user name.
        $namesender = get_config('enrol_notificationeabc', 'namesender');
        if ($enrol && !empty($enrol->customchar2)) {
            $sendername = $enrol->customchar2;
        } else if (!empty($namesender)) {
            $sendername = $namesender;
        } else {
            $sendername = fullname($supportuser);
        }
        $nameparts = explode(' ', trim($sendername), 2);
        $sender->firstname = $nameparts[0];
        $sender->lastname = isset($nameparts[1]) ? $nameparts[1] : '';

        $strdata = new stdClass();
        $strdata->username = $user->username;
        $strdata->coursename = $course->fullname;

        // Per type subject: use the site level subjectenrol/subjectunenrol/
        // subjectupdate settings, falling back to the generic subject string.
        switch ((int)$type) {
            case 1:
                $subject = !empty($pluginconfig->subjectenrol)
                    ? $pluginconfig->subjectenrol : get_string('subject', 'enrol_notificationeabc');
                break;
            case 2:
                $subject = !empty($pluginconfig->subjectunenrol)
                    ? $pluginconfig->subjectunenrol : get_string('subject', 'enrol_notificationeabc');
                break;
            case 3:
                $subject = !empty($pluginconfig->subjectupdate)
                    ? $pluginconfig->subjectupdate : get_string('subject', 'enrol_notificationeabc');
                break;
            default:
                $subject = get_string('subject', 'enrol_notificationeabc');
        }

        // Mobile message: short plain text summary derived from the type.
        // The *messagedefault strings expect {$a->fullname} and {$a->url}, so the
        // course object is passed as $a (course->url is set at the top of this
        // method); passing the send-log data here would render empty placeholders.
        switch ((int)$type) {
            case 1:
                $smallmessage = get_string('enrolmessagedefault', 'enrol_notificationeabc', $course);
                break;
            case 2:
                $smallmessage = get_string('unenrolmessagedefault', 'enrol_notificationeabc', $course);
                break;
            case 3:
                $smallmessage = get_string('enrolupdatemessagedefault', 'enrol_notificationeabc', $course);
                break;
            default:
                $smallmessage = '';
        }
        if (empty($smallmessage)) {
            $smallmessage = $course->fullname;
        }

        $eventdata = new \core\message\message();
        $eventdata->courseid = $course->id;
        $eventdata->modulename = 'moodle';
        $eventdata->component = 'enrol_notificationeabc';
        $eventdata->name = 'notificationeabc_enrolment';
        $eventdata->userfrom = $sender;
        $eventdata->userto = $user->id;
        $eventdata->subject = $subject;
        $eventdata->fullmessage = function_exists('html_to_text') ? html_to_text($message) : strip_tags($message);
        $eventdata->fullmessageformat = FORMAT_HTML;
        $eventdata->fullmessagehtml = $message;
        $eventdata->smallmessage = $smallmessage;

        if (message_send($eventdata)) {
            $this->log .= get_string('succefullsend', 'enrol_notificationeabc', $strdata);
            return true;
        } else {
            $this->log .= get_string('failsend', 'enrol_notificationeabc', $strdata);
            return false;
        }
    } // End of function.

    // Procesa el mensaje para aceptar marcadores.
    /**
     * Proccess message method
     * @param String $message the raw message
     * @param stdClass $user user instance
     * @param stdClass $course course instance
     * @param stdClass $enrollment enrollment instance
     * @return String the processed message
     */
    public function get_message($message, stdClass $user, stdClass $course, stdClass $enrollment = null) {
        global $CFG;

        $m = $message;
        $url = new moodle_url($CFG->wwwroot . '/course/view.php', ['id' => $course->id]);
        $m = str_replace('{URL}', $url, $m);

        // Course name fields: render/escape for HTML context.
        $coursenamefields = ['fullname', 'shortname'];
        foreach ($coursenamefields as $field) {
            $m = str_replace('{COURSE' . strtoupper($field) . '}', format_string($course->$field), $m);
        }

        // Other course fields.
        $m = str_replace('{COURSEIDNUMBER}', s($course->idnumber), $m);

        // Course date fields (userdate output is already safe plain text).
        $coursedatefields = ['startdate', 'enddate'];
        foreach ($coursedatefields as $field) {
            $value = $course->$field;
            if ($value > 0) {
                $value = userdate($value);
            } else {
                $value = get_string('never');
            }
            $m = str_replace('{COURSE' . strtoupper($field) . '}', $value, $m);
        }

        // User fields: escape for HTML context.
        $userfields = ['username', 'idnumber', 'firstname', 'lastname', 'email', 'city', 'country'];
        foreach ($userfields as $field) {
            $m = str_replace('{' . strtoupper($field) . '}', s($user->$field), $m);
        }

        $userprofilefields = profile_get_custom_fields($user->id);
        profile_load_custom_fields($user);

        foreach ($userprofilefields as $field) {
            $value = '';
            if (isset($user->profile[$field->shortname])) {
                $value = $user->profile[$field->shortname];
            }

            if ($field->datatype == 'datetime') {
                if ($value > 0) {
                    $value = userdate($value);
                }
            }
            $m = str_replace('{PROFILEFIELD_' . strtoupper($field->shortname) . '}', s($value), $m);
        }

        // Enrol fields.
        if ($enrollment) {
            $enrolfields = ['timecreated', 'timemodified', 'timestart', 'timeend'];
            foreach ($enrolfields as $field) {
                if ($enrollment->$field > 0) {
                    $value = userdate($enrollment->$field);
                } else {
                    $value = get_string('never');
                }
                $m = str_replace('{ENROL' . strtoupper($field) . '}', $value, $m);
            }
        }

        // To old code compatibility.
        $m = str_replace('{COURSENAME}', format_string($course->fullname), $m);
        $m = str_replace('{NOMBRE}', s($user->firstname), $m);
        $m = str_replace('{APELLIDO}', s($user->lastname), $m);

        return $m;
    }


    /**
     * Returns link to page which may be used to add new instance of enrolment plugin in course.
     * @param int $courseid
     * @return moodle_url page url
     */
    public function get_newinstance_link($courseid) {
        global $DB;

        $numenrol = $DB->count_records('enrol', array('courseid' => $courseid, 'enrol' => 'notificationeabc'));
        $context = context_course::instance($courseid, MUST_EXIST);

        if (!has_capability('enrol/notificationeabc:manage', $context) or $numenrol >= 1) {
            return null;
        }

        return new moodle_url('/enrol/notificationeabc/edit.php', array('courseid' => $courseid));
    }


    /**
     * Returns defaults for new instances.
     * @return array
     */
    public function get_instance_defaults() {

        $fields = [];
        $fields['enrolmessage'] = $this->get_config('enrolmessage');
        $fields['unenrolmessage'] = $this->get_config('unenrolmessage');
        $fields['enrolupdatemessage'] = $this->get_config('enrolupdatemessage');
        $fields['status'] = 1;
        $fields['enrolalert'] = $this->get_config('enrolalert');
        $fields['unenrolalert'] = $this->get_config('unenrolalert');
        $fields['enrolupdatealert'] = $this->get_config('enrolupdatealert');

        return $fields;
    }

    /**
     * Get icons
     * @param stdClass $instance
     * @return array
     * @throws coding_exception
     */
    public function get_action_icons(stdClass $instance) {
        global $OUTPUT;

        $context = context_course::instance($instance->courseid);

        $icons = [];

        if (has_capability('enrol/notificationeabc:manage', $context)) {
            $editlink = new moodle_url(
                '/enrol/editinstance.php',
                array('courseid' => $instance->courseid, 'id' => $instance->id, 'type' => 'notificationeabc')
            );
            $icons[] = $OUTPUT->action_icon(
                $editlink,
                new pix_icon( 't/edit', get_string('edit'), 'core', array('class' => 'icon iconsmall'))
            );
        }

        return $icons;
    }

    /**
     * Is it possible to delete enrol instance via standard UI?
     *
     * @param object $instance
     * @return bool
     */
    public function can_delete_instance($instance) {
        $context = context_course::instance($instance->courseid);
        if (!has_capability('enrol/notificationeabc:manage', $context)) {
            return false;
        }

        return true;
    }

    /**
     * Is it possible to hide/show enrol instance via standard UI?
     *
     * @param stdClass $instance
     * @return bool
     */
    public function can_hide_show_instance($instance) {
        $context = context_course::instance($instance->courseid);
        return has_capability('enrol/notificationeabc:config', $context);
    }

    /**
     * get info icons method
     * @param array $instances
     * @return array
     */
    public function get_info_icons(array $instances) {
        $icons = array();
        $icons[] = new pix_icon('email', get_string('pluginname', 'enrol_notificationeabc'), 'enrol_notificationeabc');
        return $icons;
    }

    /**
     * We are a good plugin and don't invent our own UI/validation code path.
     *
     * @return boolean
     */
    public function use_standard_editing_ui() {
        return true;
    }

    /**
     * Return an array of valid options for the status.
     *
     * @return array
     */
    protected function get_status_options() {
        $options = array(ENROL_INSTANCE_ENABLED  => get_string('yes'),
                         ENROL_INSTANCE_DISABLED => get_string('no'));
        return $options;
    }

    /**
     * Add elements to the edit instance form.
     *
     * @param stdClass $instance
     * @param MoodleQuickForm $mform
     * @param context $context
     * @return bool
     */
    public function edit_instance_form($instance, MoodleQuickForm $mform, $context) {

        // Bloody hack!
        if (!property_exists($instance, 'customtext1') || !is_array($instance->customtext1)) {
            $temp = empty($instance->customtext1) ? '' : $instance->customtext1;
            $instance->customtext1 = [];
            $instance->customtext1['text'] = $temp;
            $instance->customtext1['format'] = '1';

            $temp = empty($instance->customtext2) ? '' : $instance->customtext2;
            $instance->customtext2 = [];
            $instance->customtext2['text'] = $temp;
            $instance->customtext2['format'] = '1';

            $temp = empty($instance->customtext3) ? '' : $instance->customtext3;
            $instance->customtext3 = [];
            $instance->customtext3['text'] = $temp;
            $instance->customtext3['format'] = '1';
        }
        // End of bloody hack!

        $textareaparams = ['rows' => 12, 'cols' => 60];

        $mform->addElement('text', 'name', get_string('custominstancename', 'enrol'));
        $mform->setType('name', PARAM_RAW);
        $mform->setDefault('name', get_string('pluginname', 'enrol_notificationeabc'));

        $options = $this->get_status_options();
        $mform->addElement('select', 'status', get_string('status', 'enrol_notificationeabc'), $options);
        $mform->setDefault('status', $this->get_config('status'));

        // Enrol notifications -> 'enrolalert'.
        $mform->addElement('advcheckbox', 'customint1', get_string('enrolalert', 'enrol_notificationeabc'));
        $mform->addHelpButton('customint1', 'enrolalert', 'enrol_notificationeabc');
        $mform->setDefault('customint1', $this->get_config('enrolalert'));

        // Enrol notifications -> 'enrolmessage'
        $mform->addElement('editor', 'customtext1', get_string('enrolmessage', 'enrol_notificationeabc'), $textareaparams);
        $mform->addHelpButton('customtext1', 'enrolmessage', 'enrol_notificationeabc');
        $mform->setType('customtext1', PARAM_RAW);
        $mform->setDefault('customtext1', $this->get_config('enrolmessage'));

        // Unenrol notifications -> 'unenrolalert'.
        $mform->addElement('advcheckbox', 'customint2', get_string('unenrolalert', 'enrol_notificationeabc'));
        $mform->addHelpButton('customint2', 'unenrolalert', 'enrol_notificationeabc');
        $mform->setDefault('customint2', $this->get_config('unenrolalert'));

        // Unenrol notifications -> 'unenrolmessage'.
        $mform->addElement('editor', 'customtext2', get_string('unenrolmessage', 'enrol_notificationeabc'), $textareaparams);
        $mform->addHelpButton('customtext2', 'unenrolmessage', 'enrol_notificationeabc');
        $mform->setType('customtext2', PARAM_RAW);
        $mform->setDefault('customtext2', $this->get_config('unenrolmessage'));

        // Update enrolment notifications -> 'enrolupdatealert'.
        $mform->addElement('advcheckbox', 'customint3', get_string('enrolupdatealert', 'enrol_notificationeabc'));
        $mform->addHelpButton('customint3', 'enrolupdatealert', 'enrol_notificationeabc');
        $mform->setDefault('customint3', $this->get_config('enrolupdatealert'));

        // Update enrolment -> 'enrolupdatemessage'.
        $mform->addElement('editor', 'customtext3', get_string('enrolupdatemessage', 'enrol_notificationeabc'), $textareaparams);
        $mform->addHelpButton('customtext3', 'enrolupdatemessage', 'enrol_notificationeabc');
        $mform->setType('customtext3', PARAM_RAW);
        $mform->setDefault('customtext3', $this->get_config('enrolupdatemessage'));

        if (enrol_accessing_via_instance($instance)) {
            $warntext = get_string('instanceeditselfwarningtext', 'core_enrol');
            $mform->addElement('static', 'selfwarn', get_string('instanceeditselfwarning', 'core_enrol'), $warntext);
        }
    }

    /**
     * Normalise editor content: accept both the editor array form
     * (['text' => string, 'format' => ...]) and a plain string, because
     * programmatic callers (CLI, web services, tests) pass plain strings
     * while the standard edit form passes the editor array.
     *
     * @param string|array $value Raw customtext value
     * @return string Plain text content
     */
    protected static function editor_text($value): string {
        if (is_array($value)) {
            return (string)($value['text'] ?? '');
        }
        return (string)$value;
    }

    /**
     * Add instance method
     *
     * @param stdClass $course
     * @param array $fields
     * @return int Instance id
     */
    public function add_instance($course, array $fields = NULL) {
        global $DB;

        if ($course->id == SITEID) {
            throw new coding_exception('Invalid request to add enrol instance to frontpage.');
        }

        $instance = new stdClass();
        $instance->enrol          = $this->get_name();
        $instance->status         = ENROL_INSTANCE_ENABLED;
        $instance->courseid       = $course->id;
        $instance->enrolstartdate = 0;
        $instance->enrolenddate   = 0;
        $instance->timemodified   = time();
        $instance->timecreated    = $instance->timemodified;
        $instance->sortorder      = $DB->get_field('enrol', 'COALESCE(MAX(sortorder), -1) + 1', ['courseid' => $course->id]);

        $fields = (array)$fields;
        unset($fields['enrol']);
        unset($fields['courseid']);
        unset($fields['sortorder']);
        foreach($fields as $field=>$value) {
            $instance->$field = $value;
        }

        $instance->customtext1 = self::editor_text($fields['customtext1'] ?? '');
        $instance->customtext2 = self::editor_text($fields['customtext2'] ?? '');
        $instance->customtext3 = self::editor_text($fields['customtext3'] ?? '');

        $instance->id = $DB->insert_record('enrol', $instance);

        \core\event\enrol_instance_created::create_from_record($instance)->trigger();

        return $instance->id;
    }

    /**
     * Update instance method
     *
     * @param stdClass $instance
     * @param stdClass $data
     * @return bool
     */
    public function update_instance($instance, $data) {
        global $DB;

        $properties = ['status', 'name', 'password', 'customint1', 'customint2', 'customint3',
                            'customint4', 'customint5', 'customint6', 'customint7', 'customint8',
                            'customchar1', 'customchar2', 'customchar3', 'customdec1', 'customdec2',
                            'customtext4', 'roleid',
                            'enrolperiod', 'expirynotify', 'notifyall', 'expirythreshold',
                            'enrolstartdate', 'enrolenddate', 'cost', 'currency'];

        foreach ($properties as $key) {
            if (isset($data->$key)) {
                $instance->$key = $data->$key;
            }
        }
        $instance->timemodified = time();

        $instance->customtext1 = self::editor_text($data->customtext1 ?? '');
        $instance->customtext2 = self::editor_text($data->customtext2 ?? '');
        $instance->customtext3 = self::editor_text($data->customtext3 ?? '');

        $update = $DB->update_record('enrol', $instance);
        if ($update) {
            \core\event\enrol_instance_updated::create_from_record($instance)->trigger();
        }

        return $update;
    }

    /**
     * Perform custom validation of the data used to edit the instance.
     *
     * @param array $data array of ("fieldname"=>value) of submitted data
     * @param array $files array of uploaded files "element_name"=>tmp_file_path
     * @param object $instance The instance loaded from the DB
     * @param context $context The context of the instance we are editing
     * @return array of "element_name"=>"error_description" if there are errors,
     *         or an empty array if everything is OK.
     * @return void
     */
    public function edit_instance_validation($data, $files, $instance, $context) {
        $errors = [];
        $validstatus = array_keys($this->get_status_options());
        $tovalidate = array('status' => $validstatus);

        $typeerrors = $this->validate_param_types($data, $tovalidate);
        $errors = array_merge($errors, $typeerrors);

        return $errors;
    }

} // End of class.
