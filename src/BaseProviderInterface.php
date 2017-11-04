<?php

namespace Genesis\SQLExtensionWrapper;

/**
* This class serves as a Decorator for the Genesis API.
* To use this class effectively, create separate classes for each of your tables and extend off this class.
*/
interface BaseProviderInterface
{
    abstract public function getBaseTable();

    abstract public function getDataMapping();

    public function insertSeedDataIfExists();

    public function getKeyword($key);

    public function truncate();

    public function createFixture($data = []);
}
