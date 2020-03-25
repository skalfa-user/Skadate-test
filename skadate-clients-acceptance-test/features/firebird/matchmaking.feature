Feature: Matchmaking
    Test everything related with the mathmaking plugin

    Scenario: Browse matched profiles
        Given Created user with id "2" and username "tester2" and password "tester2" and email "test2@test.com" and sex "male"
        When I am on the app and logged with id "1" and username "tester"
        And I wait for ".sk-user" is visible "true"
        And I press "My compatible users"
        Then I should see "Tester2"
