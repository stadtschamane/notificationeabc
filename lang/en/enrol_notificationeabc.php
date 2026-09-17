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

$string['emailsender'] = 'Sender email address';
$string['emailsender_help'] = 'Email address used as the sender when no per-instance sender is set. Leave empty to use the Moodle support user.';
$string['enrolalert'] = 'Enable enrol message';
$string['enrolalert_help'] = 'Enable the enrolment notification message.';
$string['enrolmessage'] = 'Custom enrol message';
$string['enrolmessage_help'] = 'Personalize the message that users will receive when they are enrolled. This field accepts the following markers, which are replaced dynamically by the corresponding values:
<pre>
{COURSEFULLNAME} = course fullname
{USERNAME} = username
{FIRSTNAME} = firstname
{LASTNAME} = lastname
{URL} = course url
Enrolment fields: {ENROLTIMECREATED}, {ENROLTIMEMODIFIED}, {ENROLTIMESTART}, {ENROLTIMEEND}
Other course fields: {COURSESHORTNAME}, {COURSEIDNUMBER}, {COURSESTARTDATE}, {COURSEENDDATE}
Other user fields: {IDNUMBER}, {EMAIL}, {COUNTRY}, {CITY}
Old compatibility fields: {COURSENAME}, {NOMBRE}, {APELLIDO}
</pre>
You can use the user profile fields using the following syntax: {PROFILEFIELD_SHORTNAME}. For example, if you have a profile field with the shortname "office", you can use it as {PROFILEFIELD_OFFICE}';
$string['enrolmessagedefault'] = 'You have been enrolled in {$a->fullname} ({$a->url})';
$string['enrolupdatealert'] = 'Enable enrol update message';
$string['enrolupdatealert_help'] = 'Enable the enrolment update notification message.';
$string['enrolupdatemessage'] = 'Custom enrol update message';
$string['enrolupdatemessage_help'] = 'Personalize the message that users will receive when their enrolment is updated. This field accepts the following markers, which are replaced dynamically by the corresponding values:
<pre>
{COURSEFULLNAME} = course fullname
{USERNAME} = username
{FIRSTNAME} = firstname
{LASTNAME} = lastname
{URL} = course url
Enrolment fields: {ENROLTIMECREATED}, {ENROLTIMEMODIFIED}, {ENROLTIMESTART}, {ENROLTIMEEND}
Other course fields: {COURSESHORTNAME}, {COURSEIDNUMBER}, {COURSESTARTDATE}, {COURSEENDDATE}
Other user fields: {IDNUMBER}, {EMAIL}, {COUNTRY}, {CITY}
Old compatibility fields: {COURSENAME}, {NOMBRE}, {APELLIDO}
</pre>
You can use the user profile fields using the following syntax: {PROFILEFIELD_SHORTNAME}. For example, if you have a profile field with the shortname "office", you can use it as {PROFILEFIELD_OFFICE}';
$string['enrolupdatemessagedefault'] = 'Your enrolment to {$a->fullname} has been updated ({$a->url})';
$string['failsend'] = 'WARNING: the user {$a->username} could not be notified about their enrolment in the course {$a->coursename}'."\n";
$string['globalenrolalert'] = 'Enable global enrol message';
$string['globalenrolalert_help'] = 'Enable the enrol message for the whole site.';
$string['globalenrolupdatealert'] = 'Enable global enrol update message';
$string['globalenrolupdatealert_help'] = 'Enable the enrol update message for the whole site.';
$string['globalunenrolalert'] = 'Enable global unenrol message';
$string['globalunenrolalert_help'] = 'Enable the unenrol message for the whole site.';
$string['includehiddencourses'] = 'Include hidden courses';
$string['includehiddencourses_help'] = 'Include hidden courses when handling registration changes for sending messages.';
$string['messageprovider:notificationeabc_enrolment'] = 'Enrol email notification messages';
$string['namesender'] = 'Sender name';
$string['namesender_help'] = 'Name used as the sender when no per-instance sender name is set. Leave empty to use the Moodle support user name.';
$string['notificationeabc:config'] = 'Configure email notification instances';
$string['notificationeabc:manage'] = 'Manage email notifications';
$string['pluginname'] = 'Enrol notification';
$string['privacy:metadata'] = 'The enrol_notificationeabc plugin does not store any personal data.';
$string['status'] = 'Active email notification';
$string['subject'] = 'Enrolment email notification';
$string['subjectenrol'] = 'Enrol message subject';
$string['subjectenrol_help'] = 'Subject used for enrol notifications. Leave empty to use the default subject.';
$string['subjectunenrol'] = 'Unenrol message subject';
$string['subjectunenrol_help'] = 'Subject used for unenrol notifications. Leave empty to use the default subject.';
$string['subjectupdate'] = 'Enrol update message subject';
$string['subjectupdate_help'] = 'Subject used for enrol update notifications. Leave empty to use the default subject.';
$string['succefullsend'] = 'The user {$a->username} has been notified about their enrolment in the course {$a->coursename}'."\n";
$string['unenrolalert'] = 'Enable unenrol message';
$string['unenrolalert_help'] = 'Enable the unenrol notification message.';
$string['unenrolmessage'] = 'Custom unenrol message';
$string['unenrolmessage_help'] = 'Personalize the message that users will receive when they are unenrolled. This field accepts the following markers, which are replaced dynamically by the corresponding values:
<pre>
{COURSEFULLNAME} = course fullname
{USERNAME} = username
{FIRSTNAME} = firstname
{LASTNAME} = lastname
{URL} = course url
Enrolment fields: {ENROLTIMECREATED}, {ENROLTIMEMODIFIED}, {ENROLTIMESTART}, {ENROLTIMEEND}
Other course fields: {COURSESHORTNAME}, {COURSEIDNUMBER}, {COURSESTARTDATE}, {COURSEENDDATE}
Other user fields: {IDNUMBER}, {EMAIL}, {COUNTRY}, {CITY}
Old compatibility fields: {COURSENAME}, {NOMBRE}, {APELLIDO}
</pre>
You can use the user profile fields using the following syntax: {PROFILEFIELD_SHORTNAME}. For example, if you have a profile field with the shortname "office", you can use it as {PROFILEFIELD_OFFICE}';
$string['unenrolmessagedefault'] = 'You have been unenrolled from {$a->fullname} ({$a->url})';
