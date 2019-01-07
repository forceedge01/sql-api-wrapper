<?php

namespace Genesis\SQLExtensionWrapper;

/**
 * Genesis Data Mod interface.
 */
interface DataModInterface
{
    /**
     * Declare the table to interact with.
     *
     * @return string
     */
    public static function getBaseTable();

    /**
     * Mapping to the table. Any columns mapped to '*' will be excluded from the query but the data will be
     * passed around.
     *
     * @return array
     */
    public static function getDataMapping();
}
