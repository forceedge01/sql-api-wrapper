<?php

namespace Genesis\SQLExtensionWrapper;

/**
 * Genesis Bridge interface.
 */
interface BridgeInterface
{
    /**
     * @param string $dataModelClass
     *
     * @return string
     */
    public static function getBaseTable($dataModelClass);

    /**
     * @param string $dataModelClass
     *
     * @return array
     */
    public static function getDataMapping($dataModelClass);
}
