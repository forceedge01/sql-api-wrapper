<?php

namespace Genesis\SQLExtensionWrapper;

/**
 * Genesis Bridge interface.
 */
interface BridgeInterface
{
    /**
     * @param string $bridgedClass
     *
     * @return string
     */
    public static function getBaseTable($bridgedClass);

    /**
     * @param string $bridgedClass
     *
     * @return array
     */
    public static function getDataMapping($bridgedClass);
}
