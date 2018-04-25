<?php

namespace Genesis\SQLExtensionWrapper\Exception;

use Exception;

/**
 * DataModNotFoundException class.
 */
class DataModNotFoundException extends Exception
{
    /**
     * @param string $dataModRef
     */
    public function __construct($dataModRef, array $paths = [])
    {
        parent::__construct(
            'Unable to find dataMod "'.$dataModRef.'", please make ' .
            'sure path is registered correctly and it exists. Registered dataMod paths: ' .
            print_r($paths, true)
        );
    }
}
