<?php

use Behat\Behat\Context\Context;
use Behat\MinkExtension\Context\MinkAwareContext;
use Behat\Mink\Mink;
use Behat\Testwork\Hook\Scope\BeforeSuiteScope;
use DataMod\User;
use Genesis\SQLExtensionWrapper\BaseProvider;
use Genesis\SQLExtensionWrapper\DataModSQLContext;

/**
 * Defines application features from the specific context.
 */
class FeatureContext implements Context, MinkAwareContext
{
    private $mink;
    private $minkParameters;

    /**
     * Initializes context.
     *
     * Every scenario gets its own context instance.
     * You can also pass arbitrary arguments to the
     * context constructor through behat.yml.
     */
    public function __construct()
    {
    }

    /**
     * @BeforeSuite
     */
    public static function loadDataModSQLContext(BeforeSuiteScope $scope)
    {
        BaseProvider::setCredentials([
            'engine' => 'sqlite',
            'name' => __DIR__ . '/../../app/db/database.db',
            'schema' => '',
            'prefix' => '',
            'host' => 'localhost',
            'port' => '',
            'username' => '',
            'password' => ''
        ]);

        $scope->getEnvironment()->registerContextClass(
            DataModSQLContext::class,
            ['debug' => false]
        );
    }

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
