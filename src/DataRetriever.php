<?php

namespace Genesis\SQLExtensionWrapper;

use DateTime;
use Exception;
use Traversable;

/**
 * DataRetriever class. Holds convenience methods for interacting with data coming from feature files.
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

        return self::getFormattedValue($data[$key], $key);
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

        return self::getFormattedValue($data[$key], $key);
    }

    /**
     * @param iterable $iterable
     * @param callable $func
     *
     * @return string
     */
    public static function loopDataSet(Traversable $iterable, callable $func)
    {
        foreach ($iterable as $key => $value) {
            $func($key, $value);
        }
    }

    /**
     * Rules:
     * - A field ending with Date will be returned as DateTime
     * - A field ending with Amount will be returned in pence
     * - The value otherwise as is.
     *
     * @param string $value
     * @param string $field
     *
     * @return string|DateTimeInterface
     */
    private static function getFormattedValue($value, $field)
    {
        if (strpos($field, 'Date') !== false) {
            return new DateTime($value);
        }

        if (strpos($field, 'Amount') !== false) {
            return $value * 100;
        }

        return $value;
    }
}
