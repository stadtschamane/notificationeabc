@enrol @enrol_notificationeabc
Feature: Enrol Notification instances
  In order to notify users about enrolment events
  As an admin
  I need to add and configure a notificationeabc enrolment instance

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |
    And the following config values are set as admin:
      | config                | value                                    |
      | enrol_plugins_enabled | manual,self,guest,cohort,notificationeabc |

  @javascript
  Scenario: Add a notificationeabc enrolment method and save it
    Given I am on the "Course 1" course page logged in as "admin"
    And I add "Enrol Notification" enrolment method in "Course 1" with:
      | Custom instance name | Notify me |
    Then I should see "Notify me"
    And I should see "Enrol Notification"