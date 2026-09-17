# Notificationeabc plugin (Enrol Notification)

Send notifications to users when enrolment events are executed: enrol, unenrol and enrolment update. Built on the modern standard editing UI (one instance per course, per-instance alert toggles and message templates, site-wide defaults).

Maintained continuation of the unmaintained e-ABC plugin (original release Dec 2017, last upstream code change Mar 2022).

## Requirements

- Moodle 4.5 (build 2022112808) or newer — supported range `[401, 502]`, CI covers Moodle 4.5 / 5.0 / 5.1 / 5.2 with PHP 8.1–8.3.
- One enrol instance per course.

## Installation

1. Download the release package (`notificationeabc.zip`).
2. Unzip it inside the `enrol` directory of your Moodle installation (the folder name must be `notificationeabc`).
3. Go to Site administration → Notifications and press "Upgrade Moodle database now".
4. Enable the plugin under Site administration → Plugins → Enrolment → Manage enrol plugins if it is not already enabled.

## Upgrading from the e-ABC original (2.7–3.3)

Replace the **complete** plugin directory — do not mix files. The 4.x maintenance base renamed language identifiers and config keys; global (site-level) message templates must be re-entered once after upgrading. Per-instance course texts survive the upgrade. The version number is above all known forks, so the installer offers the upgrade automatically.

## Notification flow

- Site-level master switches per event type, then per-course instance: enabled instance + per-instance toggle → send with instance message/subject/sender overrides.
- Courses without a notificationeabc instance use the global fallback switches.
- Duplicate notifications are suppressed per request/CLI run (bulk operations).
- Placeholders: `{COURSENAME}`, `{COURSEFULLNAME}`, `{USERNAME}`, `{FIRSTNAME}`, `{LASTNAME}`, `{NOMBRE}`, `{APELLIDO}`, `{URL}`, plus user profile fields (`{PROFILEFIELD_SHORTNAME}`), course and enrolment fields.

## Tests

- PHPUnit: observer decision logic and message building (`tests/`).
- CI: moodle-plugin-ci v4 across Moodle 4.5/5.0/5.1/5.2 and PHP 8.1/8.2/8.3.

## Versions by: David Herney - [BambuCo](https://bambuco.co/):
2024092002:
- Setting to Include hidden courses.

2024092001:
- Use HTML editor in messages.
- Fixed: instance enable/disable message.
- Additional course fields, enrollment fields, and user profile fields have been added to the message template.

## Credits

- Original author: e-ABC Learning (Osvaldo Arriola), 2017 — GNU GPL v3
- Moodle 4.x maintenance base: David Herney (BambuCo), 2024/2025
- Community contributions: kordan (2018 review), jimsihk (name placeholders, configurable subjects)