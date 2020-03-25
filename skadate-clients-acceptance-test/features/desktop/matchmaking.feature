Feature: Matchmaking
    Test everything related with the mathmaking plugin

    Scenario: Browse matched profiles
        Given I am a logged user with id "1" and username "tester" and sex "male"
        Given Created user with id "2" and username "tester2" and password "tester2" and email "test2@test.com" and sex "female"
        When I am on "profile/matches"
        Then I should see "Tester2"
        And I should see "female"