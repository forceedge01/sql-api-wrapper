<?php

namespace Genesis\SQLExtensionWrapper\Exception;

use Exception;

/**
 * RequiredDataException class.
 */
class RequiredDataException extends Exception
{
    /**
     * @param string $missingIndex
     * @param array $indexes
     * @param array $data
     */
    public function __construct($missingIndex, array $indexes, array $data)
    {
        parent::__construct(
            "Expected to have data index '$missingIndex' in Data " . print_r($data, true) .
            ', declared required data: ' . print_r($indexes, true)
        );
    }
}
