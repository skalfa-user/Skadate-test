Feature: Mailbox
    Test everything related with the mailbox plugin

    Background:
        Given Loaded sql data from "mailbox/clean_tables"

    Scenario: Sending chat messages on the profile view page
        Given I am a logged user with id "1" and username "tester" and sex "male"
        Given Created user with id "2" and username "tester2" and password "tester2" and email "test2@test.com"
        When I am on "user/tester2"
        And I follow "Send message"
        And I wait for "#dialogTextarea" is visible "true"
        And I fill in "Text Message" with "test message"
        And I key press "13" on "#dialogTextarea"
        Then I wait for "#dialogMessageText" is visible "true"
        And I should see "test message"
