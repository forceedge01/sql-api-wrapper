Feature:
    In order to verify the sql api wrapper
    As a maintainer
    I want to try it out in real time as an integration test

    Scenario: Test it out
        Given I am on "/"
        And I do not have any "Address" fixtures
        And I do not have a "User" fixture
        And I have a "User" fixture
        And I have a "User" fixture with the following data set:
            | name          | Wahab Qureshi |
            | date of birth | 10-05-1989    |
            | age           | 29            |
            | hobby         | swimming      |
        And I have an "Address" fixture

        When I reload the page
        Then I should see "name: Wahab Qureshi"
        And I should see "dob: 10-05-1989"
        And I should see the users age on the page
        And I should see text matching "address: behat-[0-9]+-test-string"
        And I take a screenshot
