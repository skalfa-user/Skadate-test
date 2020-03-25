Feature: Mailbox
    Test everything related with the mailbox plugin

    Background:
        Given Loaded sql data from "mailbox/clean_tables"

    Scenario: Sending chat messages on the profile view page
        Given Created user with id "2" and username "tester2" and password "tester2" and email "test2@test.com" and sex "male"
        When I am on the app and logged with id "1" and username "tester"
        And I click element ".sk-tab-toggle-search"
        And I wait for ".sk-card-wrap" is visible "true"
        And I click element ".sk-card"
        And I wait for ".sk-profile-message-btn" is visible "true"
        And I click element ".sk-profile-message-btn"
        And I wait for ".sk-new-conversation" is visible "true"
        And I fill the chat message "test message"
        And I press "Send"
        And I wait for ".sk-messages" is visible "true"
        Then I should see "test message"
