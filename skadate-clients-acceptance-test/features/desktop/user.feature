Feature: User
    Test everything related with the main users functionality like join, login, search, etc

    @work
    Scenario: Login
        Given Created user with id "1" and username "tester" and password "tester" and email "test@test.com"
        When I am on "sign-in"
        And fill in the following:
            | Username/Email   | tester              |
            | Password         | tester              |
        And press "Sign In"
        And I wait for ".ow_console_item" is visible "true"
        Then I should see "MY DASHBOARD"

    Scenario: Logout
        Given I am a logged user with id "1" and username "tester"
        When I hover "Tester"
        And I follow "Sign Out"
        Then I should see "Sign In"

    Scenario: Basic join without any special adjustments
        Given Loaded sql data from "googlemap/join" with keys "[__api_key__]" and params "[AIzaSyBKARCA9bCuYmVt0WIJNvgZswUK_lp-QuY]"
        When I am on "join"
        And fill in the following:
            | Username         | test                |
            | Email            | test1@test.te       |
            | Password         | 1234                |
            | Repeat password  | 1234                |
            | Gender           | 1                   |
        And I select checkbox question with name "Looking for" and value "[Male,Female]"
        And press "Continue"
        #And I fill location question with name "Location" and location "USA"
        #And I select radio question with name "Does caste matter to you?" and value "Yes"
        #And I select checkbox question with name "Favourite ways to spend time" and value "[Sports,Nightlife,Books]"
        #And I select date question with name "His/Her Age" and month "3" and day "16" and year "1984"
        And fill in the following:
            | I agree with terms of use         | 1                     |
        And I attach the file "test.jpg" to "userPhoto"
        And I wait for "#avatar-crop-btn" is visible "true"
        And I press "Apply crop"
        And I wait for "#avatar-crop-btn" is visible "false"
        And press "Join"
        Then I should see "Registration successful"

    Scenario: Join by invitation
        Given Loaded sql data from "core/invite" with keys "[__code__]" and params "[12345]"
        When I am on "join?code=12345"
        And fill in the following:
            | Username         | test                |
            | Email            | test1@test.te       |
            | Password         | 1234                |
            | Repeat password  | 1234                |
            | Gender           | 1                   |
        And I select checkbox question with name "Looking for" and value "Male/Groom"
        And press "Continue"
        And fill in the following:
            | Full Name                         | test                  |
            | More information about our family | test                  |
            | I agree with terms of use         | 1                     |
        And I select date question with name "His/Her Age" and month "3" and day "16" and year "1984"
        And I attach the file "test.jpg" to "userPhoto"
        And I wait for "#avatar-crop-btn" is visible "true"
        And I press "Apply crop"
        And I wait for "#avatar-crop-btn" is visible "false"
        And press "Join"
        Then I should see "Registration successful"

    Scenario: Join by password
        When I am on the homepage
        And I fill in "password" with "demo"
        And press "Enter"
        And I wait for ".ow_signin_label" is visible "true"
        And I go to "join"
        And fill in the following:
            | Username         | test                |
            | Email            | test1@test.te       |
            | Password         | 1234                |
            | Repeat password  | 1234                |
            | Gender           | 1                   |
        And I select checkbox question with name "Looking for" and value "Male/Groom"
        And press "Continue"
        And fill in the following:
            | Full Name                         | test                  |
            | More information about our family | test                  |
            | I agree with terms of use         | 1                     |
        And I select date question with name "His/Her Age" and month "3" and day "16" and year "1984"
        And I attach the file "test.jpg" to "userPhoto"
        And I wait for "#avatar-crop-btn" is visible "true"
        And I press "Apply crop"
        And I wait for "#avatar-crop-btn" is visible "false"
        And press "Join"
        Then I should see "Registration successful"

    Scenario: Edit profile
        Given I am a logged user with id "1" and username "tester"
        When I am on "profile/edit"
        And fill in the following:
            | Full Name        | test2               |
            | Email            | test2@test.com      |
        And I select date question with name "Date of birth" and month "1" and day "1" and year "1984"
        And I select checkbox question with name "Favourite ways to spend time" and value "[Sports,Nightlife,Books]"
        And I press "Save"
        Then I should see "User data updated"

    Scenario: Search
        Given I am a logged user with id "1" and username "tester"
        Given Created user with id "2" and username "tester2" and password "tester2" and email "test2@test.com"
        When I am on "users/search"
        And I fill in "Looking for" with "2"
        And I wait for "input[name='SearchFormSubmit']" is visible "true"
        And press "Search"
        Then I should see "tester2"
