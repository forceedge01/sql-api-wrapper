<?php

namespace Genesis\SQLExtensionWrapper;

use Behat\Behat\Context\Context;

/**
 * DecoratedSQLContext class. This class gives you context step definitions out of the box that work with your
 * data modules mapping. To use set the appropriate mapping i.e dataMod => namespacedClass and give it a spin.
 */
class DataModSQLContext implements Context
{
    /**
     * @var array
     */
    private $dataModMapping;

    /**
     * @Given I have a :entity fixture with the following:
     *
     * @param string $entity
     * @param TableNode $where
     */
    public function givenICreateFixture($entity, TableNode $where)
    {
        $dataMod = $this->resolveEntity($entity);
        $dataMod::createFixture(
            DataRetriever::transformTableNodeToArray($where)
        );
    }

    /**
     * @param array $mapping
     *
     * @return this
     */
    public function setDataModMapping(array $mapping)
    {
        $this->dataModMapping = $mapping;

        return $this;
    }

    /**
     * @param string $entity
     *
     * @return string
     */
    private function resolveEntity($entity)
    {
        if (isset($this->dataModMapping[$entity])) {
            return $this->dataModMapping[$entity];
        }

        return $entity;
    }
}
