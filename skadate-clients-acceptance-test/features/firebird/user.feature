Feature: User
    Test everything related with the main users functionality like join, login, search, etc

    @work
    Scenario: Login
        Given Created user with id "1" and username "tester" and password "tester" and email "test@test.com"
        When I am on the app
        And fill in the following:
            | Username/Email   | tester              |
            | Password         | tester              |
        And press "Login"
        And I wait for ".sk-about" is visible "true"
        Then I should see "About tester"

    Scenario: Logout
        When I am on the app and logged with id "1" and username "tester"
        And I wait for ".sk-settings-button" is visible "true"
        And I press "Settings"
        And I wait for ".sk-app-settings-page" is visible "true"
        And I press "Logout"
        And I wait for ".sk-login" is visible "true"
        Then I should see "Forgot password"


    Scenario: Join
        Given Loaded sql data from "skmobileapp/join" with keys "[__api_key__]" and params "[AIzaSyBKARCA9bCuYmVt0WIJNvgZswUK_lp-QuY]"
        When I am on the app
        And I press "Sign up"
        And I wait for ".sk-add-avatar" is visible "true"
        And I attach the file "test.jpg" to uploader with css class "sk-avatar-uploader"
        And I wait for ".spinner" is visible "false"
        And fill in the following:
            | Type your username         | test                |
            | Type the password          | test                |
            | Repeat your password       | test                |
            | Type your email            | test@test.com       |
        And I select radio question with name "Gender" and value "Male"
        And I select checkbox question with name "Looking For:" and value "[Male,Female]"
        And I wait for ".sk-question-pending" is visible "false"
        And press "Next"
        And I wait for ".sk-join-questions-page .sk-join-fields" is visible "true"
        And fill in the following:
            | Real name                         | test                  |
        And I select date question with name "Birthday" and month "0" and day "0" and year "3"
        # And I select radio question with name "Religion" and value "Buddhist - Mahayana"
        # And I select checkbox question with name "Favourite ways to spend time" and value "[Books,Music]"
        # And I fill location question with name "Location" and location "USA"
        And I accept terms of use
        And press "Done"
        And I wait for ".sk-user-info" is visible "true"


    Scenario: Edit profile
        When I am on the app and logged with id "1" and username "tester"
        And press "Edit profile"
        And I wait for ".sk-user-edit-page .sk-edit-fields" is visible "true"
        And fill in the following:
            | Real name        | test2               |
            | Email            | test2@test.com      |
        # And I select date question with name "Date of birth" and month "1" and day "1" and year "3"
        # And I select checkbox question with name "Favourite ways to spend time" and value "[Sports,Nightlife,Books]"
        And I wait for ".spinner" is visible "false"
        And I press "Done"
        And I wait for ".spinner" is visible "false"
        Then I should see "Profile has been updated"

    Scenario: Search
        Given Created user with id "2" and username "tester2" and password "tester2" and email "test2@test.com"
        When I am on the app and logged with id "1" and username "tester"
        And I click element ".sk-tab-toggle-search"
        And I wait for ".sk-search-filter" is visible "true"
        And I click element ".sk-search-filter"
        And I wait for ".sk-user-search-filter-page .sk-user-search-fields" is visible "true"
        And I select radio question with name "Looking For:" and value "Female"
        And I press "Done"
        And I wait for ".sk-card-wrap" is visible "true"
        Then I should see "tester2"
