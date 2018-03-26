<?php

namespace Genesis\SQLExtensionWrapper;

use Behat\Gherkin\Node\TableNode;
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
     * @param TableNode $tableNode
     * @param callable $func
     *
     * @return array
     */
    public static function loopMultiTable(TableNode $tableNode, callable $func)
    {
        return self::looper($tableNode, $func);
    }

    /**
     * @param TableNode $tableNode
     * @param callable $func
     *
     * @return array
     */
    public static function loopDataTable(TableNode $tableNode, callable $func)
    {
        return self::looper($tableNode, $func);
    }

    /**
     * @param TableNode $tableNode
     * @param callable $func
     *
     * @return array
     */
    public static function loopSingleTable(TableNode $tableNode, callable $func)
    {
        return self::looper($tableNode->getRows(), $func);
    }

    /**
     * @param TableNode $tableNode
     * @param callable $func
     *
     * @return array
     */
    public static function loopPageFieldsTable(TableNode $tableNode, callable $func)
    {
        return self::looper($tableNode->getHash(), $func);
    }

    /**
     * @param TableNode $tableNode
     *
     * @return array
     */
    public static function transformTableNodeToArray(TableNode $tableNode)
    {
        $array = [];

        foreach ($tableNode->getHash() as $index => $row) {
            foreach ($row as $field => $value) {
                $array[$index][$field] = self::getFormattedValue($value, $field);
            }
        }

        return $array;
    }

    /**
     * @param Traversable $element
     * @param callable $func
     *
     * @return array
     */
    private static function looper($element, callable $func)
    {
        $result = [];

        foreach ($element as $index => $row) {
            $result[] = $func($index, $row);
        }

        return $result;
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
            $date = new DateTime($value);

            return $date->format('Y-m-d H:i:s');
        }

        if (strpos($field, 'Amount') !== false) {
            return $value * 100;
        }

        return $value;
    }
}
