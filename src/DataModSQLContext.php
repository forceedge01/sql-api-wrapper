<?php

namespace Genesis\SQLExtensionWrapper;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Genesis\SQLExtensionWrapper\Exception\DataModNotFoundException;
use Genesis\SQLExtension\Context\Debugger;

/**
 * DecoratedSQLContext class. This class gives you context step definitions out of the box that work with your
 * data modules mapping. To use set the appropriate mapping i.e dataMod => namespacedClass and give it a spin.
 */
class DataModSQLContext implements Context
{
    const DEFAULT_NAMESPACE = '\\DataMod\\';

    /**
     * @var array
     */
    private static $dataModMapping = [];

    /**
     * @var string
     */
    private static $userUniqueRef;

    /**
     * @param array $dataModMapping
     * @param boolean $debug
     * @param string $userUniqueRef Will be appended to new data created to separate data based on users.
     * Best to limit it to 2 characters.
     */
    public function __construct($debug = false, $userUniqueRef = null)
    {
        if ($debug) {
            Debugger::enable($debug);
        }

        self::$userUniqueRef = $userUniqueRef;
    }

    /**
     * @Given I have a/an :dataModRef fixture
     * @Given I have a/an :dataModRef fixture with the following data set:
     *
     * Note: The first row value in the TableNode is considered the unique key.
     *
     * @param string $dataModRef
     * @param TableNode $where
     */
    public function givenIACreateFixture($dataModRef, TableNode $where = null)
    {
        $dataMod = $this->getDataMod($dataModRef);

        // You don't need to necessarily have a where clause to create a fixture.
        $uniqueKey = null;
        $dataSet = array();
        if ($where) {
            $dataSet = DataRetriever::transformTableNodeToSingleDataSet($where);
            $uniqueKey = key($dataSet);

            if (! is_numeric($dataSet[$uniqueKey])) {
                $dataSet[$uniqueKey] .= self::$userUniqueRef;
            }
        }

        $dataMod::createFixture(
            $dataSet,
            $uniqueKey
        );
    }

    /**
     * @Given I have multiple :dataModRef fixtures with the following data set(s):
     *
     * Note: The first column value in the TableNode is considered the unique key.
     *
     * @param string $dataModRef
     * @param TableNode $where
     */
    public function givenIMultipleCreateFixtures($dataModRef, TableNode $where)
    {
        $dataMod = $this->getDataMod($dataModRef);
        $dataSets = DataRetriever::transformTableNodeToMultiDataSets($where);

        foreach ($dataSets as $dataSet) {
            $uniqueKey = key($dataSet);

            if (! is_numeric($dataSet[$uniqueKey])) {
                $dataSet[$uniqueKey] .= self::$userUniqueRef;
            }

            $dataMod::createFixture(
                $dataSet,
                $uniqueKey
            );
        }
    }

    /**
     * @Given I do not have a/any :dataModRef fixture(s)
     * @Given I do not have a/any :dataModRef fixture(s) with the following data set:
     */
    public function iDoNotHaveAFixtureWithTheFollowingDataSet($dataModRef, TableNode $where = null)
    {
        $dataMod = $this->getDataMod($dataModRef);
        $dataSet = [];
        if ($where) {
            $dataSet = DataRetriever::transformTableNodeToSingleDataSet($where);
        }

        $dataMod::delete($dataSet);
    }

    /**
     * Useful when testing against API's. Not recommended to be used else where.
     *
     * @Then I should have a :dataModRef
     * @Then I should have a :dataModRef with the following data set:
     */
    public function iShouldHaveAWithTheFollowingDataSet($dataModRef, TableNode $where = null)
    {
        $dataMod = $this->getDataMod($dataModRef);
        $dataSet = [];
        if ($where) {
            $dataSet = DataRetriever::transformTableNodeToSingleDataSet($where);
        }

        $dataMod::assertExists($dataSet);
    }

    /**
     * Useful when testing against API's. Not recommended to be used else where.
     *
     * @Then I should not have a :dataModRef
     * @Then I should not have a :dataModRef with the following data set:
     */
    public function iShouldNotHaveAWithTheFollowingDataSet($dataModRef, TableNode $where = null)
    {
        $dataMod = $this->getDataMod($dataModRef);
        $dataSet = [];
        if ($where) {
            $dataSet = DataRetriever::transformTableNodeToSingleDataSet($where);
        }

        $dataMod::assertNotExists($dataSet);
    }

    /**
     * @Given I save the id as :key
     */
    public function iSaveTheIdAs($key)
    {
        BaseProvider::getApi()->setKeyword($key, BaseProvider::getApi()->getLastId());

        return $this;
    }

    /**
     * @param array $dataModMapping
     */
    private static function setDataModMappingFromBehatYamlFile(array $dataModMapping = array())
    {
        if (! $dataModMapping) {
            return false;
        }

        self::setDataModMapping($dataModMapping);
    }

    /**
     * @param array $mapping
     */
    public static function setDataModMapping(array $mapping)
    {
        self::$dataModMapping = $mapping;
    }

    /**
     * @param string $dataModRef
     *
     * @return DataModInterface
     */
    private function getDataMod($dataModRef)
    {
        $dataMod = $this->resolveDataMod($dataModRef);

        if (! class_exists($dataMod)) {
            throw new DataModNotFoundException($dataMod, self::$dataModMapping);
        }

        return $dataMod;
    }

    /**
     * @param string $dataModRef
     *
     * @return string
     */
    private function resolveDataMod($dataModRef)
    {
        // If we found a custom datamod mapping use that.
        if (isset(self::$dataModMapping[$dataModRef])) {
            return self::$dataModMapping[$dataModRef];
        }

        // If we've got a global namespace where all the datamods reside, just use that.
        if (isset(self::$dataModMapping['*'])) {
            return self::$dataModMapping['*'] . $dataModRef;
        }

        return self::DEFAULT_NAMESPACE . $dataModRef;
    }
}
