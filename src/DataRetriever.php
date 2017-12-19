<?php

namespace Cruise\Testing\Behaviour\ContextHelper;

use Exception;

/**
 * DataRetriever class. Holds convenience methods.
 */
class DataRetriever
{
    /**
     * Convenience method to get a required index value out of array.
     *
     * @param array $data The data to check.
     * @param string $key The index to look for.
     *
     * @return mixed Whatever the data index contains.
     */
    public static function getRequiredData(array $data, $key)
    {
        if (! array_key_exists($key, $data)) {
            throw new Exception("Expect to find key '$key' in data: " . print_r($data, true));
        }

        return $data[$key];
    }

    /**
     * Convenience method to get an optional index value out of array, if it does not exist will
     * return the default value.
     *
     * @param array $data The data to check.
     * @param string $key The index to look for.
     * @param string $default The value to return if the key index is not defined.
     *
     * @return mixed Whatever the data index contains.
     */
    public static function getOptionalData(array $data, $key, $default = null)
    {
        if (! array_key_exists($key, $data)) {
            return $default;
        }

        return $data[$key];
    }
}
