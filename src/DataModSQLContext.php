<?php

namespace Genesis\SQLExtensionWrapper;

/**
 * DecoratedSQLContext class. This class gives you context step definitions out of the box that work with your
 * data modules mapping. To use set the appropriate mapping i.e dataMod => namespacedClass and give it a spin.
 */
class DecoratedSQLContext
{
    /**
     * @var array
     */
    private $dataModMapping;

    /**
     * @param string $entity
     * @param TableNode $where
     *
     * @return string
     */
    public function givenICreateFixture($entity, TableNode $where)
    {
        $dataMod = $this->resolveEntity($entity);
        $dataMod::createFixture(
            $where
        );
    }

    public function setDataModMapping(array $mapping)
    {
        $this->dataModMapping = $mapping;

        return $this;
    }

    private function resolveEntity($entity)
    {
        if (isset($this->dataModMapping['*'])) {
            return $this->dataModMapping['*'];
        }

        if (isset($this->dataModMapping[$entity])) {
            return $this->dataModMapping[$entity];
        }

        return $entity;
    }
}
