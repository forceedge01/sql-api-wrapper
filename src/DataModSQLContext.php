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
     * @Given I have a :entity fixture with the following:
     *
     * @param string $entity
     * @param TableNode $where
     */
    public function givenICreateFixture($entity, TableNode $where)
    {
        $dataMod = $this->resolveEntity($entity);
        $dataSets = DataRetriever::transformTableNodeToArray($where);

        foreach ($dataSets as $dataSet) {
            $dataMod::createFixture(
                $dataSet
            );
        }
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
        if (isset(self::$dataModMapping[$entity])) {
            return self::$dataModMapping[$entity];
        }

        return $entity;
    }
}
