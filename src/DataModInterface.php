<?php

namespace Genesis\SQLExtensionWrapper;

/**
 * Genesis Data Mod interface.
 */
interface DataModInterface
{
    /**
     * @return string
     */
    public static function getBaseTable();

    /**
     * @return array
     */
    public static function getDataMapping();
}
