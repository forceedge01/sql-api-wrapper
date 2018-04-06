<?php

namespace Genesis\SQLExtensionWrapper;

use Behat\Behat\Context\Context;
use Behat\Gherkin\Node\TableNode;

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
     */
    public function __construct(array $dataModMapping = array())
    {
        self::setDataModMappingFromBehatYamlFile($normalisedMapping);
    }

    /**
     * @Given I have a :entity fixture with the following data set:
     *
     * @param string $entity
     * @param TableNode $where
     */
    public function givenIACreateFixture($entity, TableNode $where)
    {
        $dataMod = $this->resolveEntity($entity);
        $dataSet = DataRetriever::transformTableNodeToSingleDataSet($where);

        $dataMod::createFixture(
            $dataSet
        );
    }

    /**
     * @Given I have multiple :entity fixtures with the following data sets:
     *
     * @param string $entity
     * @param TableNode $where
     */
    public function givenIMultipleCreateFixtures($entity, TableNode $where)
    {
        $dataMod = $this->resolveEntity($entity);
        $dataSets = DataRetriever::transformTableNodeToMultiDataSets($where);

        foreach ($dataSets as $dataSet) {
            $dataMod::createFixture(
                $dataSet
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
     * @param string $entity
     *
     * @return string
     */
    private function resolveEntity($entity)
    {
        // If we've got a global namespace where all the datamods reside, just use that.
        if (isset(self::$dataModMapping['*'])) {
            return self::$dataModMapping['*'] . $entity;
        }

        // If we found a custom datamod mapping use that.
        if (isset(self::$dataModMapping[$entity])) {
            return self::$dataModMapping[$entity];
        }

        // Try to load the mapping anyway.
        return $entity;
    }
}
