@enrol @enrol_notificationeabc
Feature: Enrol Notification instances
  In order to notify users about enrolment events
  As an admin
  I need to add and configure a notificationeabc enrolment instance

  Background:
    Given the following "courses" exist:
      | fullname | shortname |
      | Course 1 | C1 |

  @javascript
  Scenario: Add a notificationeabc enrolment method and save it
    Given I am on the "Course 1" course page logged in as "admin"
    And I navigate to course participants
    And I click on "Enrolment methods" "link"
    And I select "Enrol Notification" from the "Add method" singleselect
    And I press "Add method"
    And I set the field "Custom instance name" to "Notify me"
    And I press "Save changes"
    Then I should see "Notify me"
    And I should see "Enrol Notification"