<?php

namespace Skadate\Traits;

use Behat\Mink\Driver\Selenium2Driver; 
use Behat\Behat\Hook\Scope\AfterStepScope;
use Behat\Gherkin\Node\StepNode;
use Behat\Gherkin\Node\ScenarioInterface;
use LogicException;

trait Screenshot
{
    /** 
     * @AfterStep 
     */ 
    public function takeScreenShotAfterFailedStep(afterStepScope $scope) {
        if ($scope->getTestResult()->isPassed()) {
            return;
        }

        $suiteName      = urlencode(str_replace(' ', '_', $scope->getSuite()->getName()));
        $featureName    = urlencode(str_replace(' ', '_', $scope->getFeature()->getTitle()));

        $scenario       = $this->getScenario($scope);
        $scenarioName   = urlencode(str_replace(' ', '_', $scenario->getTitle()));

        $filename = sprintf('fail_%s_%s_%s.png', $suiteName, $featureName, $scenarioName);

        file_put_contents('./screenshots/' . $filename, $this->getSession()->getDriver()->getScreenshot());
    }

    /**
     * @param AfterStepScope $scope
     * @return ScenarioInterface
     */
    private function getScenario(AfterStepScope $scope)
    {
        $scenarios = $scope->getFeature()->getScenarios();

        foreach ($scenarios as $scenario) {
            $stepLinesInScenario = array_map(function (StepNode $step) {
                    return $step->getLine();
                },
                $scenario->getSteps()
            );

            if (in_array($scope->getStep()->getLine(), $stepLinesInScenario)) {
                return $scenario;
            }
        }
 
        throw new LogicException('Unable to find the scenario');
    }
}
