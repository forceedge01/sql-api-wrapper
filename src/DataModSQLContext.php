<?php

namespace Genesis\SQLExtensionWrapper;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;
use Exception;

/**
 * DecoratedSQLContext class. This class gives you context step definitions out of the box that work with your
 * data modules mapping. To use set the appropriate mapping i.e dataMod => namespacedClass and give it a spin.
 */
class DataModSQLContext implements Context
{
    /**
     * @var array
     */
    private static $dataModMapping;

    /**
     * @param array $dataModMapping
     * @param boolean $debug
     */
    public function __construct(array $dataModMapping = array(), $debug = false)
    {
        if ($debug) {
            define('DEBUG_MODE', 1);
        }

        self::setDataModMappingFromBehatYamlFile($dataModMapping);
    }

    /**
     * @Given I have a :dataModRef fixture with the following data set:
     *
     * Note: The first row value in the TableNode is considered the unique key.
     *
     * @param string $dataModRef
     * @param TableNode $where
     */
    public function givenIACreateFixture($dataModRef, TableNode $where)
    {
        $dataMod = $this->resolveDataMod($dataModRef);
        $dataSet = DataRetriever::transformTableNodeToSingleDataSet($where);

        $dataMod::createFixture(
            $dataSet,
            key($dataSet)
        );
    }

    /**
     * @Given I have multiple :dataModRef fixtures with the following data sets:
     *
     * Note: The first column value in the TableNode is considered the unique key.
     *
     * @param string $dataModRef
     * @param TableNode $where
     */
    public function givenIMultipleCreateFixtures($dataModRef, TableNode $where)
    {
        $dataMod = $this->resolveDataMod($dataModRef);
        $dataSets = DataRetriever::transformTableNodeToMultiDataSets($where);

        foreach ($dataSets as $dataSet) {
            $dataMod::createFixture(
                $dataSet,
                key($dataSet)
            );
        }
    }

    /**
     * @param array $dataModMapping
     */
    private static function setDataModMappingFromBehatYamlFile(array $dataModMapping = array())
    {
        if (! $dataModMapping) {
            return false;
        }

        $normalisedMapping = [];
        foreach ($dataModMapping as $mapping) {
            $key = key($mapping);
            $normalisedMapping[$key] = $mapping[$key];
        }

        self::setDataModMapping($normalisedMapping);
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

        throw new Exception(
            'DataMod ' . $dataModRef . ' not configured in data mod mapping, mapping available: ' .
            print_r(self::$dataModMapping, true)
        );
    }
}
