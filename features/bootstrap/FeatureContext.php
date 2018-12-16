<?php

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\Mink\Mink;
use DataMod\User;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, MinkAwareContext
{
    private $mink;
    private $minkParameters;

    public function setMink(Mink $mink)
    {
        $this->mink = $mink;
    }

    public function setMinkParameters(array $parameters)
    {
        $this->parameters = $parameters;
    }

    /**
     * @Then I should see the users age on the page
     */
    public function iShouldSeeTheUsersAgeOnThePage()
    {
        $age = User::getValue('age');

        $this->mink->assertSession()->pageTextContains('age: ' . $age . ' years');
    }
}
