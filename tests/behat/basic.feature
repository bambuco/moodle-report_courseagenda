@report @report_courseagenda
Feature: Basic tests for Course agenda

  @javascript
  Scenario: Plugin report_courseagenda appears in the list of installed additional plugins
    Given I log in as "admin"
    When I navigate to "Plugins > Plugins overview" in site administration
    And I follow "Additional plugins"
    Then I should see "Course agenda"
    And I should see "report_courseagenda"
