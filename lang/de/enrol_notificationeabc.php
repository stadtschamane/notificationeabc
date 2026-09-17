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

$string['emailsender'] = 'E-Mail-Adresse des Absenders';
$string['emailsender_help'] = 'E-Mail-Adresse, die als Absender verwendet wird, wenn kein Absender auf Instanzebene festgelegt ist. Leer lassen, um den Support-Benutzer von Moodle zu verwenden.';
$string['enrolalert'] = 'Einschreibbenachrichtigung aktivieren';
$string['enrolalert_help'] = 'Aktiviert die Benachrichtigung bei der Einschreibung.';
$string['enrolmessage'] = 'Individuelle Einschreibnachricht';
$string['enrolmessage_help'] = 'Passen Sie die Nachricht an, die Benutzerinnen und Benutzer bei ihrer Einschreibung erhalten. In diesem Feld können die folgenden Platzhalter verwendet werden, die dynamisch durch die entsprechenden Werte ersetzt werden:
<pre>
{COURSEFULLNAME} = Vollständiger Kursname
{USERNAME} = Benutzername
{FIRSTNAME} = Vorname
{LASTNAME} = Nachname
{URL} = Kurs-URL
Felder der Einschreibung: {ENROLTIMECREATED}, {ENROLTIMEMODIFIED}, {ENROLTIMESTART}, {ENROLTIMEEND}
Weitere Kursfelder: {COURSESHORTNAME}, {COURSEIDNUMBER}, {COURSESTARTDATE}, {COURSEENDDATE}
Weitere Benutzerfelder: {IDNUMBER}, {EMAIL}, {COUNTRY}, {CITY}
Felder für Altkompatibilität: {COURSENAME}, {NOMBRE}, {APELLIDO}
</pre>
Sie können die Benutzerprofilfelder mit folgender Syntax verwenden: {PROFILEFIELD_SHORTNAME}. Wenn Sie beispielsweise ein Profilfeld mit dem Kurznamen "office" haben, verwenden Sie es als {PROFILEFIELD_OFFICE}';
$string['enrolmessagedefault'] = 'Sie wurden in den Kurs {$a->fullname} eingeschrieben ({$a->url})';
$string['enrolupdatealert'] = 'Benachrichtigung bei Aktualisierung der Einschreibung aktivieren';
$string['enrolupdatealert_help'] = 'Aktiviert die Benachrichtigung bei der Aktualisierung der Einschreibung.';
$string['enrolupdatemessage'] = 'Individuelle Nachricht zur Aktualisierung der Einschreibung';
$string['enrolupdatemessage_help'] = 'Passen Sie die Nachricht an, die Benutzerinnen und Benutzer erhalten, wenn ihre Einschreibung aktualisiert wird. In diesem Feld können die folgenden Platzhalter verwendet werden, die dynamisch durch die entsprechenden Werte ersetzt werden:
<pre>
{COURSEFULLNAME} = Vollständiger Kursname
{USERNAME} = Benutzername
{FIRSTNAME} = Vorname
{LASTNAME} = Nachname
{URL} = Kurs-URL
Felder der Einschreibung: {ENROLTIMECREATED}, {ENROLTIMEMODIFIED}, {ENROLTIMESTART}, {ENROLTIMEEND}
Weitere Kursfelder: {COURSESHORTNAME}, {COURSEIDNUMBER}, {COURSESTARTDATE}, {COURSEENDDATE}
Weitere Benutzerfelder: {IDNUMBER}, {EMAIL}, {COUNTRY}, {CITY}
Felder für Altkompatibilität: {COURSENAME}, {NOMBRE}, {APELLIDO}
</pre>
Sie können die Benutzerprofilfelder mit folgender Syntax verwenden: {PROFILEFIELD_SHORTNAME}. Wenn Sie beispielsweise ein Profilfeld mit dem Kurznamen "office" haben, verwenden Sie es als {PROFILEFIELD_OFFICE}';
$string['enrolupdatemessagedefault'] = 'Ihre Einschreibung in den Kurs {$a->fullname} wurde aktualisiert ({$a->url})';
$string['failsend'] = 'WARNUNG: Die Benutzerin bzw. der Benutzer {$a->username} konnte nicht über die Einschreibung in den Kurs {$a->coursename} benachrichtigt werden'."\n";
$string['globalenrolalert'] = 'Globale Einschreibbenachrichtigung aktivieren';
$string['globalenrolalert_help'] = 'Aktiviert die Einschreibbenachrichtigung für die gesamte Website.';
$string['globalenrolupdatealert'] = 'Globale Benachrichtigung bei Aktualisierung der Einschreibung aktivieren';
$string['globalenrolupdatealert_help'] = 'Aktiviert die Benachrichtigung bei der Aktualisierung der Einschreibung für die gesamte Website.';
$string['globalunenrolalert'] = 'Globale Abmeldebenachrichtigung aktivieren';
$string['globalunenrolalert_help'] = 'Aktiviert die Abmeldebenachrichtigung für die gesamte Website.';
$string['includehiddencourses'] = 'Verborgene Kurse einschließen';
$string['includehiddencourses_help'] = 'Verborgene Kurse bei der Verarbeitung von Einschreibungsänderungen für den Versand von Nachrichten einschließen.';
$string['messageprovider:notificationeabc_enrolment'] = 'Einschreibungs-Benachrichtigungen per E-Mail';
$string['namesender'] = 'Name des Absenders';
$string['namesender_help'] = 'Name, der als Absender verwendet wird, wenn kein Absendername auf Instanzebene festgelegt ist. Leer lassen, um den Namen des Support-Benutzers von Moodle zu verwenden.';
$string['notificationeabc:config'] = 'Instanzen der E-Mail-Benachrichtigung konfigurieren';
$string['notificationeabc:manage'] = 'E-Mail-Benachrichtigungen verwalten';
$string['pluginname'] = 'Einschreibungsbenachrichtigung';
$string['privacy:metadata'] = 'Das Plugin enrol_notificationeabc speichert keine personenbezogenen Daten.';
$string['status'] = 'E-Mail-Benachrichtigung aktivieren';
$string['subject'] = 'Benachrichtigung über die Einschreibung per E-Mail';
$string['subjectenrol'] = 'Betreff der Einschreibnachricht';
$string['subjectenrol_help'] = 'Betreff für Einschreibbenachrichtigungen. Leer lassen, um den Standardbetreff zu verwenden.';
$string['subjectunenrol'] = 'Betreff der Abmeldenachricht';
$string['subjectunenrol_help'] = 'Betreff für Abmeldebenachrichtigungen. Leer lassen, um den Standardbetreff zu verwenden.';
$string['subjectupdate'] = 'Betreff der Nachricht zur Aktualisierung der Einschreibung';
$string['subjectupdate_help'] = 'Betreff für Benachrichtigungen zur Aktualisierung der Einschreibung. Leer lassen, um den Standardbetreff zu verwenden.';
$string['succefullsend'] = 'Die Benutzerin bzw. der Benutzer {$a->username} wurde über die Einschreibung in den Kurs {$a->coursename} benachrichtigt'."\n";
$string['unenrolalert'] = 'Abmeldebenachrichtigung aktivieren';
$string['unenrolalert_help'] = 'Aktiviert die Benachrichtigung bei der Abmeldung.';
$string['unenrolmessage'] = 'Individuelle Abmeldenachricht';
$string['unenrolmessage_help'] = 'Passen Sie die Nachricht an, die Benutzerinnen und Benutzer bei ihrer Abmeldung erhalten. In diesem Feld können die folgenden Platzhalter verwendet werden, die dynamisch durch die entsprechenden Werte ersetzt werden:
<pre>
{COURSEFULLNAME} = Vollständiger Kursname
{USERNAME} = Benutzername
{FIRSTNAME} = Vorname
{LASTNAME} = Nachname
{URL} = Kurs-URL
Felder der Einschreibung: {ENROLTIMECREATED}, {ENROLTIMEMODIFIED}, {ENROLTIMESTART}, {ENROLTIMEEND}
Weitere Kursfelder: {COURSESHORTNAME}, {COURSEIDNUMBER}, {COURSESTARTDATE}, {COURSEENDDATE}
Weitere Benutzerfelder: {IDNUMBER}, {EMAIL}, {COUNTRY}, {CITY}
Felder für Altkompatibilität: {COURSENAME}, {NOMBRE}, {APELLIDO}
</pre>
Sie können die Benutzerprofilfelder mit folgender Syntax verwenden: {PROFILEFIELD_SHORTNAME}. Wenn Sie beispielsweise ein Profilfeld mit dem Kurznamen "office" haben, verwenden Sie es als {PROFILEFIELD_OFFICE}';
$string['unenrolmessagedefault'] = 'Sie wurden aus dem Kurs {$a->fullname} abgemeldet ({$a->url})';
