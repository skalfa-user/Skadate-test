<?php

use Skadate\Contexts\SkadateContext;
use WebDriver\Key;
use Skadate\Traits\Db;
use Skadate\Traits\Screenshot;

/**
 * Desktop context.
 */
class DesktopContext extends SkadateContext
{
    use Db;
    use Screenshot;

    protected $applyStartFixtures = [
        'core/change_configs.sql',
        'core/questions.sql',
        'core/clean_basic_tables.sql',
        'core/register_desktop_js_error_handler.sql'
    ];

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
     * Set base url
     * 
     * @BeforeScenario
     */
    public function setBaseUrl()
    {
        $this->setMinkParameter('base_url', $this->skadateConfig['OW_URL_HOME']);
    }

    /**
     * @When I fill html text area with label :fieldLabel and text :text
     * @Example: And I fill html text area with label "Test" and text "test"
     */
    public function iFillHtmlTextAreaWith($fieldLabel, $text)
    {
        // search the iframe inside the question
        $iframe = $this->getSession()->
            getPage()->find('xpath', "//td[not(contains(@style,'display:none'))]//label[.='{$fieldLabel}']//ancestor::tr[1]/descendant::iframe");

        if (null === $iframe) {
            throw new Exception('Html textarea question with label '  . $fieldLabel . ' not found');
        }

        // generate and random id for the iframe
        $iFrameId = 'iframe_' . rand(100, 10000);
 
        // set the id for the found iframe
        $function = "
            function getElementByXpath(path) {
                var res = document.evaluate(path, document, null, XPathResult.FIRST_ORDERED_NODE_TYPE, null)
                return res.singleNodeValue;
            }

            var element = getElementByXpath(\"{$iframe->getXpath()}\");
            element.id = '{$iFrameId}';
        ";
 
        $this->getSession()->executeScript($function);
        $this->getSession()->getDriver()->switchToIFrame($iFrameId);

        $windowNames = $this->getSession()->getDriver()->getWindowNames();

        $el = $this->getSession()->getPage()->find('xpath', '//body');
        $el->setValue($text);

        $this->getSession()->getDriver()->switchToWindow(array_pop($windowNames));
    }

    /**
     * @Given /^I fill location question with name "([^"]*)" and location "([^"]*)"$/
     * @Example And I fill location question with name "Location" and location "USA"
     */
    public function iFillLocationQuestionWithNameAndLocation($fieldName, $location)
    {
        $field = $this->getSession()->getPage()->findField($fieldName);

        if (null === $field) {
            throw new Exception('Location question with name '  . $fieldName . ' not found');
        }

        $element = $this->getSession()->
                getDriver()->getWebDriverSession()->element('xpath', $field->getXpath());
 
        // enter the location
        $existingValueLength = strlen($element->attribute('value'));
        $value = str_repeat(Key::BACKSPACE.Key::DELETE, $existingValueLength) . $location;
        $element->postValue(['value' => [$value]]);

        // wait for arriving the autocomplite menu
        $this->iWaitForIsVisible('.googlelocation_autocomplite_menu');

        // click a first location
        $locations = $this->getSession()->getPage()->findAll('css', '.ui-menu-item>a');
        current($locations)->click();
    }

    /**
     * @Given /^I select radio question with name "([^"]*)" and value "([^"]*)"$/
     * @Given /^I select checkbox question with name "([^"]*)" and value "([^"]*)"$/
     * @Example And I select radio question with name "Gender" and value "Male"
     * @Example And I select checkbox question with name "Looking for" and value "Male/Groom"
     */
    public function iSelectQuestionWithNameAndValue($fieldName, $value)
    {
        $values = is_array($value) ? $value : [$value];

        foreach($values as $questionValue) {
            // search for the question by a label
            $fieldLabel = $this->getSession()->getPage()->
                    find('xpath', "//div[not(contains(@style,'display:none'))]//label[.='{$fieldName}']//ancestor::tr[1]/descendant::label[text()='{$questionValue}']");

            if (null === $fieldLabel) {
                throw new Exception('The question label with name "'  . $fieldName . '" and value "' . $questionValue . '" not found');
            }

            $element = $this->getSession()->
                    getPage()->find('css', "#{$fieldLabel->getAttribute('for')}");

            $element->click();
        }
    }

    /**
     * @Given /^I select date question with name "([^"]*)" and month "(\d+)" and day "(\d+)" and year "(\d+)"$/
     * @Example And I select date question with name "Birthday" and month "3" and day "16" and year "1984"
     */
    public function iSelectDateQuestionWithNameAndMonthAndDayAndYear($fieldName, $month, $day, $year)
    {
        // search for the question by a label
        $dateFieldLabel = $this->getSession()->
                getPage()->find('xpath', "//div[not(contains(@style,'display:none'))]//label[.='{$fieldName}']");

        if (null === $dateFieldLabel) {
            throw new Exception('Date question with name '  . $fieldName . ' not found');
        }

        // search for the hidden date field
        $hiddenDateField = $this->getSession()->getPage()->
                find('css', 'input[id="' . $dateFieldLabel->getAttribute('for') .'"]', false);

        $hiddenQuestionName = $hiddenDateField->getAttribute('name');

        // enter the date
        $this->getSession()->getPage()->
                find('css', 'select[name="' . 'month_' . $hiddenQuestionName .'"]')->selectOption($month);

        $this->getSession()->getPage()->
                find('css', 'select[name="' . 'day_' . $hiddenQuestionName .'"]')->selectOption($day);

        $this->getSession()->getPage()->
                find('css', 'select[name="' . 'year_' . $hiddenQuestionName .'"]')->selectOption($year);
    }

    /**
     * @Given I am a logged user with id :id and username :username
     * @Given I am a logged user with id :id and username :username and sex :sex
     * @Given I am a logged user with id :id and username :username and sex :sex and role :role
     * @Example And I am a logged user with id "1" and username "tester"
     * @Example And I am a logged user with id "1" and username "tester" and sex "male"
     * @Example And I am a logged user with id "1" and username "tester" and sex "male" and role "34"
     */
    public function iAmALoggedUserWithIdAndUsername($id, $username, $sex = '', $role = '')
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

        $loginHash =  md5(time());

        // login user
        $this->executeSqlFile($this->baseFixturesPath . 'core/login_cookie.sql', [
            '__id__' => $id,
            '__hash__' => $loginHash
        ]);

        // create a login cookie
        $this->getSession()->visit($this->locatePath('/'));
        $this->getSession()->setCookie('ow_login', $loginHash);
        $this->getSession()->reload();

        $this->getSession()->wait(10000, '(0 === jQuery.active && 0 === jQuery(\':animated\').length)');
    }
}
