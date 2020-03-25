<?php

namespace Skadate\Contexts;

use Behat\MinkExtension\Context\MinkContext;
use Behat\Behat\Hook\Scope\AfterStepScope;
use Exception;

abstract class SkadateContext extends MinkContext 
{
    /**
     * Step failed
     */
    const STEP_FAILED = 99;

    protected $baseFixturesPath;
    protected $params = [];
    protected $skadateConfig = [];

    protected $applyStartFixtures = [
        'core/change_configs.sql',
        'core/questions.sql',
        'core/clean_basic_tables.sql'
    ];

    protected $applyEndFixtures = [];

    /**
     * Initializes context.
     *
     * @param string $paramsJson
     */
    public function __construct($paramsJson)
    {
        $this->params = json_decode(file_get_contents($paramsJson), true);
        $this->baseFixturesPath = $this->params['fixturesPath'];

        // parse the skadate config file
        $skadateConfigFile = file_get_contents($this->params['skadate_config']);

        preg_match_all(
            "/define\('(?<configName>.*?)',\s*(?<configValue>.*)\);/",
            $skadateConfigFile,
            $configMatches
        );

        // process the one
        for ($i = 0; $i < count($configMatches['configName']); $i++) {
            $this->skadateConfig[$configMatches['configName'][$i]] = trim($configMatches['configValue'][$i], "'");
        }

        // set db necessary settings
        $this->setDbParams($this->skadateConfig['OW_DB_NAME'], 
            $this->skadateConfig['OW_DB_HOST'], 
            $this->skadateConfig['OW_DB_USER'], 
            $this->skadateConfig['OW_DB_PASSWORD'],
            $this->skadateConfig['OW_DB_PREFIX']);
    }

    /**
     * @BeforeScenario
     */
    public function applyStartFixtures()
    {
        if (count($this->applyStartFixtures)) {
            foreach($this->applyStartFixtures as $fixture) {
                $this->executeSqlFile($this->baseFixturesPath . $fixture);
            }

            $this->applyStartFixtures = [];
        }
    }

    /**
     * @AfterScenario
     */
    public function applyEndFixtures()
    {
        if (count($this->applyEndFixtures)) {
            foreach($this->applyEndFixtures as $fixture) {
                $this->executeSqlFile($this->baseFixturesPath . $fixture);
            }

            $this->applyEndFixtures = [];
        }
    }

    /**
     * @AfterStep
     */
    public function takeJSErrorsAfterFailedStep(AfterStepScope $event)
    {
        $code = $event->getTestResult()->getResultCode();

        if ($code === self::STEP_FAILED) {
            // Fetch errors from the window variable.
            try {
                $json = $this->getSession()->evaluateScript('return JSON.stringify(window.behatErrors);');
            } catch (Exception $e) {
                return;
            }

            if ($json) {
                $errors = json_decode($json);

                if ($errors) {
                    foreach ($errors as $error) {
                        $messages[] = "- {$error->message} ({$error->location})";
                    }

                    printf("JavaScript errors:\n\n" . implode("\n\n", $messages));
                }
            }
        }
    }

    /**
     * Confirm popups
     * @When /^(?:|I )confirm the popup$/
     * @Example: And I confirm the popup
     */
    public function confirmPopup()
    {
        $this->getSession()->getDriver()->getWebDriverSession()->accept_alert();
    }

    /**
     * Cancel popups
     * @When /^(?:|I )cancel the popup$/
     * @Example: And I cancel the popup
     */
    public function cancelPopup()
    {
        $this->getSession()->getDriver()->getWebDriverSession()->dismiss_alert();
    }

    /**
     * Clicks elements
     * @When /^(?:|I )click element "(?P<element>(?:[^"]|\\")*)"$/
     * @Example: When I click element ".my-element"
     * @Example: When I click element ".my-element:nth-child(5)"
     */
    public function clickElement($elementSelector)
    {
        $element = $this->getSession()->getPage()->find('css', $elementSelector);

        if (null === $element) {
            throw new Exception('Element with css selector "'  . $elementSelector . '" not found');
        }

        $element->click();

        $this->getSession()->wait(3000);
    }

    /**
     * Loads sql dump
     * @Given /^Loaded sql data from "([^"]*)"$/
     * @Given /^Loaded sql data from "([^"]*)" with keys "([^"]*)" and params "([^"]*)"$/
     * @Example: Given Loaded sql data from "core/test" with keys "[__key1__, __key2__]" and params "[1, 2]"
     */
    public function loadedSqlDataFrom($fixture, $keys = [], $params = [])
    {
        $combinedParams = array_combine($keys, $params);

        $this->executeSqlFile($this->baseFixturesPath . $fixture . '.sql', $combinedParams);
    }

    /**
     * Creates new users
     * @Given Created user with id :id and username :username and password :password and email :email
     * @Given Created user with id :id and username :username and password :password and email :email and sex :sex
     * @Given Created user with id :id and username :username and password :password and email :email and sex :sex and role :role
     * @Example: Given Created user with id "1" and username "tester" and password "tester" and email "test@test.com"
     * @Example: Given Created user with id "1" and username "tester" and password "tester" and email "test@test.com" and sex "male"
     * @Example: Given Created user with id "1" and username "tester" and password "tester" and email "test@test.com" and sex "male" and role "32"
     */
    public function createdUserWithIdAndUsernameAndPasswordAndEmail($id, $username, $password, $email, $sex = '', $role = '')
    {
        $sexOptions =  !$sex
            ? $this->params['account_types'][$this->params['default_profile']['sex']] // use the default account
            : $this->params['account_types'][$sex];

        // create a user
        $this->executeSqlFile($this->baseFixturesPath . 'core/user.sql', [
            '__id__' => $id,
            '__email__' => $email,
            '__username__' => $username,
            '__account_type__' => $sexOptions['hash'],
            '__password__' => hash('sha256', $this->skadateConfig['OW_PASSWORD_SALT'] . $password),
            '__email_verified__' => 1
        ]);

        // create the user's questions data
        $this->executeSqlFile($this->baseFixturesPath . 'core/question_data.sql', [
            '__id__' => $id,
            '__sex__' => $sexOptions['sex'],
            '__match_sex__' => $this->params['default_profile']['match_sex'],
            '__match_age__' => $this->params['default_profile']['match_age'],
            '__birthdate__' => $this->params['default_profile']['birthdate'],
            '__realname__' => $username,
            '__aboutme__' => $this->params['default_profile']['about'],
        ]);

        // create the user's role
        $this->executeSqlFile($this->baseFixturesPath . 'core/user_role.sql', [
            '__user_id__' => $id,
            '__role_id__' => !$role ? $this->params['default_profile']['role'] : $role
        ]);
    }

    /**
     * Hovers link with specified id|title|alt|text
     * @When /^(?:|I )hover "(?P<link>(?:[^"]|\\")*)"$/
     * @Example: When I hover "Log In"
     * @Example: And I hover "Log In"
     */
    public function hoverLink($link)
    {
        $link = $this->fixStepArgument($link);
        $locator = $this->getSession()->getPage()->findLink($link);

        if (null === $locator) {
            throw new Exception('Link "' . $link . '" with "id|title|alt|text" was not found');
        }

        $locator->mouseOver();
    }

    /**
     * Finds one of the following texts
     * @Then I should see the one of the following texts :texts
     * @Example: Then I should see the one of the following texts [test1, test2]
     */
    public function iShouldSeeTheOneOfTheFollowingTexts(array $texts)
    {
        foreach ($texts as $text) {
            try {
                $this->assertPageContainsText($text);

                return;
            }
            catch(Exception $e) {}
        }

        throw new Exception('No one from "' . implode(',', $texts) . '" was found');
    }

    /**
     * Waits for clickable element
     * @Given /^I wait for "([^"]*)" is clickable$/
     * @Example: And I wait for ".spinner" is clickable
     */
    public function iWaitForClickableElement($element, $maxWaitTimeSeconds = 30)
    {
        $startTime = time();
        $delayBeforeAnimationIsDone = 300;
        $checkForElementsDelay = 100;

        while (time() - $startTime <= $maxWaitTimeSeconds) {
            try {
                $node = $this->getSession()->getPage()->find('css', $element);

                if ($node) {
                    $node->click();

                    return;
                }
            } catch (Exception $e) {
                usleep($checkForElementsDelay * 1000); // wait 100 miliseconds
            }
        }

        throw new Exception("The element '$element' was not clickable after a $maxWaitTimeSeconds seconds timeout");
    }

    /**
     * Waits for visible or hidden elements
     * @Given /^I wait for "([^"]*)" is visible "(true|false)"$/
     * @Example: And I wait for ".spinner" is visible "false"
     * @Example: And I wait for ".spinner .test" is visible "true"
     */
    public function iWaitForIsVisible($element, $isVisible = true, $maxWaitTimeSeconds = 30)
    {
        $startTime = time();
        $delayBeforeAnimationIsDone = 300;
        $checkForElementsDelay = 100;

        while (time() - $startTime <= $maxWaitTimeSeconds) {
            try {
                $isVisible 
                    ? $this->assertElementOnPage($element) // element should be available on a page
                    : $this->assertElementNotOnPage($element); // element should be hidden on a page

                $this->getSession()->wait($delayBeforeAnimationIsDone); // wait before all animations are done

                return;
            } catch (Exception $e) {
                usleep($checkForElementsDelay * 1000); // wait 100 miliseconds
            }
        }

        // we didn't find anything
        if ($isVisible) {
            throw new Exception("The element '$element' was not found after a $maxWaitTimeSeconds seconds timeout");
        }

        throw new Exception("The element '$element' was not hidden after a $maxWaitTimeSeconds seconds timeout");
    }


    /**
     * Presses a key on elements
     * @Given /^I key press "(\d+)" on "([^"]*)"$/
     * @Example: And I key press "13" on "#dialogTextarea"
     */
    public function iKeyPressOn($keyCode, $element)
    {
        $$element = $this->getSession()->getPage()->find('css', $element);
        $$element->focus();

        $this->getSession()->getDriver()->keyPress($$element->getXPath(), $keyCode);
    }

    /**
     * Transforms params to bool
     * @Transform /^(true|false)$/
     */
    public function castStringToBoolean($booleanValue)
    {
        return $booleanValue === 'true';
    }

    /**
     * Transforms params to number
     * @Transform /^(\d+)$/
     */
    public function castStringToNumber($numberValue)
    {
        return intval($numberValue);
    }

    /**
     * Transforms params to array
     * @Transform /^\[(.*)\]$/
     */
    public function castStringToArray($string)
    {
        return array_map('trim', explode(',', $string));
    }
}
