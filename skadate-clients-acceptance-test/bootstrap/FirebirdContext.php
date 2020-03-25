<?php

use Skadate\Contexts\SkadateContext;
use Skadate\Traits\Db;
use Skadate\Traits\Screenshot;
use Firebase\JWT\JWT;

/**
 * Firebird context.
 */
class FirebirdContext extends SkadateContext {
    use Db;
    use Screenshot;

    /**
     * Initializes context.
     *
     * @param string $paramsJson
     */
    public function __construct($paramsJson)
    {
        parent::__construct($paramsJson);
    }

    /**
     * @BeforeScenario
     */
    public function setBaseUrl()
    {
        $this->setMinkParameter('base_url', 'http://localhost:8100/?disable_push=true');
    }

    /**
     * @AfterScenario
     */
    public function cleanBrowser()
    {
        $this->getSession()->executeScript("window.sessionStorage.clear();");
        $this->getSession()->executeScript("window.localStorage.clear();");
    }

    /**
     * @When I defined location with  latitude :latitude and longitude :longitude
     */
    public function iDefinedLocationWithLatitudeAndLongitude($latitude, $longitude)
    {
        $this->getSession()->executeScript('window.navigator.geolocation.getCurrentPosition = function(success, error) {
            success({
                coords : {
                    latitude: ' . $latitude . ',
                    longitude: ' . $longitude . '
                }
            });
        };');
    }
 
    /**
     * @When I fill the chat message :message
     */
    public function iFillTheChatMessage($message)
    {
        $field = $this->getSession()->getPage()->find('css', 'ion-textarea textarea.text-input');

        if (null === $field) {
            throw new Exception('The chat textarea not found');
        }

        $field->setValue($message);
    }

    /**
     * @When I am on the app
     */
    public function iAmOnTheApp()
    {
        $this->visitPath('/');

        // wait before angular is loading
        $this->waitForAngular();
    }

    /**
     * Accepts terms of use
     * @When I accept terms of use
     * @Example: And I accept terms of use
     */
    public function iAcceptTermsOfUse()
    {
        $field = $this->getSession()->getPage()->find('css', '.sk-tos .item-cover');

        if (null === $field) {
            throw new Exception('The terms of use button not found');
        }

        $field->click();
    }
 
    /**
     * Fills location questions by name
     * @When I fill location question with name :fieldName and location :location
     * @Example: When I fill location question with name "Location" and location "USA"
     */
    public function iFillLocationQuestionWithNameAndLocation($fieldName, $location, $timeOut = 500)
    {
        $field = $this->getSession()->getPage()->find('xpath', 
            "//ion-item//ion-label//span[normalize-space(text()) = '{$fieldName}']/ancestor::div[(contains(@class, 'sk-base-question-presentation'))]");

        if (null === $field) {
            throw new Exception('Location with name "'  . $fieldName . '" not found');
        }

        $field->click();

        $this->getSession()->wait($timeOut);

        $this->fillField('Search', $location);
        $this->iWaitForIsVisible('.sk-autocomplete-result');

        $locations = $this->getSession()->getPage()->findAll('css', '.sk-autocomplete-results .sk-autocomplete-result');

        if (null === $locations || empty($locations[0])) {
            throw new Exception('Locations are empty');
        }

        // click a first location
        $locations[0]->click();

        $this->getSession()->wait($timeOut);
    }

    /**
     * Fills date questions by name
     * @When I select date question with name :fieldName and month :monthIndex and day :dayIndex and year :yearIndex
     * @Example: When I select date question with name "His/Her Age" and month "0" and day "0" and year "3"
     */
    public function iSelectDateQuestionWithNameAndMonthAndDayAndYear($fieldName, $monthIndex, $dayIndex, $yearIndex, $timeOut = 500)
    {
        $field = $this->getSession()->getPage()->find('xpath', 
            "//ion-item//ion-label//span[normalize-space(text()) = '{$fieldName}']/ancestor::div[(contains(@class, 'sk-base-question-presentation'))]");

        if (null === $field) {
            throw new Exception('The date question with name "'  . $fieldName . '" not found');
        }

        $field->click();

        $this->getSession()->wait($timeOut);

        // select a month
        $monthList = $this->getSession()->getPage()->findAll('xpath', 
            "//div[contains(@class, 'picker-columns')]//div[contains(@class, 'picker-col')][1]//div[contains(@class, 'picker-opts')]//button[contains(@class, 'picker-opt')]");

        if (null === $monthList) {
            throw new Exception('The month list is missing');
        }

        for($i = 0; $i < count($monthList); $i++) {
            if ($i <= $monthIndex) {
                $monthList[$i]->click();
                $this->getSession()->wait(10);
            }
        }

        // select a day
        $dayList = $this->getSession()->getPage()->findAll('xpath', 
            "//div[contains(@class, 'picker-columns')]//div[contains(@class, 'picker-col')][2]//div[contains(@class, 'picker-opts')]//button[contains(@class, 'picker-opt')]");

        if (null === $dayList) {
            throw new Exception('The day list is missing');
        }

        for($i = 0; $i < count($dayList); $i++) {
            if ($i <= $dayIndex) {
                $dayList[$i]->click();
                $this->getSession()->wait(10);
            }
        }

        // select a year
        $yearList = $this->getSession()->getPage()->findAll('xpath', 
            "//div[contains(@class, 'picker-columns')]//div[contains(@class, 'picker-col')][3]//div[contains(@class, 'picker-opts')]//button[contains(@class, 'picker-opt')]");

        if (null === $yearList) {
            throw new Exception('The year list is missing');
        }

        for($i = 0; $i < count($yearList); $i++) {
            if ($i <= $yearIndex) {
                $yearList[$i]->click();
                $this->getSession()->wait(10);
            }
        }

        $okButton = $this->getSession()->getPage()->findAll('css', '.picker-button');

        if (null === $okButton || empty($okButton[1])) {
            throw new Exception('The ok button is missing');
        }

        // click the "ok" button
        $okButton[1]->click();

        $this->getSession()->wait($timeOut);
    }

    /**
     * Selects both the radio and select questions by name
     * @Given /^I select radio question with name "([^"]*)" and value "([^"]*)"$/
     * @Given /^I select checkbox question with name "([^"]*)" and value "([^"]*)"$/
     * @Example: When I select radio question with name "Gender" and value "Male/Groom"
     * @Example: When I select checkbox question with name "lookingFor" and value "[Male/Groom,Female/Bride]"
     */
    public function iSelectQuestionWithNameAndValue($fieldName, $value, $timeOut = 500)
    {
        $values = is_array($value) ? $value : [$value];

        $field = $this->getSession()->getPage()->find('xpath', 
            "//ion-item//ion-label//span[normalize-space(text()) = '{$fieldName}']/ancestor::div[(contains(@class, 'sk-base-question-presentation'))]");

        if (null === $field) {
            throw new Exception('The question with name "'  . $fieldName . '" not found');
        }

        $field->click();

        $this->getSession()->wait($timeOut);

        foreach($values as $questionValue) {
            // search for the question by a label
            $fieldLabel = $this->getSession()->getPage()->find('xpath', 
                "//button[contains(@class, 'alert-tappable')]//div[(contains(@class, 'alert-checkbox-label') or contains(@class, 'alert-radio-label')) and normalize-space(text()) = '{$questionValue}']");

            if (null === $fieldLabel) {
                throw new Exception('The question label with name "'  . $fieldName . '" and value "' . $questionValue . '" not found');
            }

            $fieldLabel->click();
        }

        $okButton = $this->getSession()->getPage()->findAll('css', '.alert-button');

        if (null === $okButton || empty($okButton[1])) {
            throw new Exception('The ok button is missing');
        }

        // click the "ok" button
        $okButton[1]->click();

        $this->getSession()->wait($timeOut);
    }

    /**
     * Attaches file to field with specified css class name
     * @When I attach the file :path to uploader with css class :uploaderCssClassName
     * @Example: When I attach the file "test.jpg" to uploader with css class "sk-avatar-uploader"
     */
    public function iAttachTheFileToUploaderWithCssClass($path, $uploaderCssClassName)
    {
        $field = $this->getSession()->
            getPage()->find('css', '.' . $uploaderCssClassName . ' > .sk-file-uploader-input', false);

        if (null === $field) {
            throw new Exception('File uploader with css class name "'  . $uploaderCssClassName . '" not found');
        }

        if ($this->getMinkParameter('files_path')) {
            $fullPath = rtrim(realpath($this->getMinkParameter('files_path')), DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$path;
            if (is_file($fullPath)) {
                $path = $fullPath;
            }
        }

        $field->attachFile($path);
    }

    /**
     * Logins in the app
     * @When I am on the app and logged with id :id and username :username
     * @When I am on the app and logged with id :id and username :username and sex :sex
     *  @When I am on the app and logged with id :id and username :username and sex :sex and role :role
     * @Example: When I am on the app and logged with id "1" and username "tester"
     * @Example: When I am on the app and logged with id "1" and username "tester" and sex "male"
     * @Example: When I am on the app and logged with id "1" and username "tester" and sex "male" and role "34"
     */
    public function iAmOnTheAppAndLoggedWithIdAndUsername($id, $username, $sex = '', $role = '')
    {
        $sexOptions =  !$sex
            ? $this->params['account_types'][$this->params['default_profile']['sex']] // use the default account
            : $this->params['account_types'][$sex];

        // create a user
        $this->executeSqlFile($this->baseFixturesPath . 'core/user.sql', [
            '__id__' => $id,
            '__email__' => $this->params['default_profile']['email'],
            '__username__' => $username,
            '__account_type__' => $sexOptions['hash'],
            '__password__' => hash('sha256', $this->skadateConfig['OW_PASSWORD_SALT'] . 'tester'),
            '__email_verified__' => 1
        ]);

        // create the user's questions data
        $this->executeSqlFile($this->baseFixturesPath . 'core/question_data.sql', [
            '__id__' => $id,
            '__sex__' => $sexOptions['sex'],
            '__match_sex__' => $this->params['default_profile']['match_sex'],
            '__match_age__' => $this->params['default_profile']['match_age'],
            '__birthdate__' => $this->params['default_profile']['birthdate'],
            '__realname__' => $this->params['default_profile']['realname'],
            '__aboutme__' => $this->params['default_profile']['about'],
        ]);

        // create the user's role
        $this->executeSqlFile($this->baseFixturesPath . 'core/user_role.sql', [
            '__user_id__' => $id,
            '__role_id__' => !$role ? $this->params['default_profile']['role'] : $role
        ]);

        $jwtToken = JWT::encode([
            'id' => $id,
            'name' => $username,
            'email' => $this->params['default_profile']['email'],
            'exp' => time() + 3600
        ], $this->skadateConfig['OW_PASSWORD_SALT']);

        $this->getSession()->visit($this->locatePath('/'));
        $this->setValueToLocalStore('token', $jwtToken);

        $this->getSession()->reload();
        $this->waitForAngular();
    }

    /**
     * Set value to local store
     * 
     * @param string $name
     * @param mixed $value
     * @return void
     */
    protected function setValueToLocalStore($name, $value) {
        $this->getSession()->executeScript("window.localStorage.setItem('{$name}', '" . json_encode($value) . "')");
    }

    /**
     * Wait for angular
     */
    protected function waitForAngular() 
    {
        // wait before app is loading
        $this->getSession()->wait(60000, '(function() {
            // check if the angular is ready
            var result = window.getAllAngularTestabilities().every((testability) => testability.isStable() === true);

            // check if the splash screen is not active
            if (result) {
                var images = document.getElementsByTagName("img");
            
                for (i=0; i < images.length; i++) {
                    if (images[i].src.indexOf("pwa_splash_icon.png") !== -1) {
                        result = false;

                        break;
                    }
                }
            }

            return result;
        })();');
    }
}
