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
     * @const Turn formatting on.
     */
    const FORMAT_ON = true;

    /**
     * @const Turn formatting off.
     */
    const FORMAT_OFF = false;

    /**
     * Convenience method to get a required index value out of array.
     *
     * @param array $data The data to check.
     * @param string $key The index to look for.
     * @param boolean $format Apply formatting on data.
     *
     * @return mixed Whatever the data index contains.
     */
    public static function getRequiredData(array $data, $key, $format = false)
    {
        if (! array_key_exists($key, $data)) {
            throw new Exception("Expect to find key '$key' in data: " . print_r($data, true));
        }

        if (! $format) {
            return $data[$key];
        }

        return self::getFormattedValue($data[$key], $key);
    }

    /**
     * Convenience method to get an optional index value out of array, if it does not exist will
     * return the default value.
     *
     * @param array $data The data to check.
     * @param string $key The index to look for.
     * @param string $default The value to return if the key index is not defined. Formatting not applied.
     * @param boolean $format Apply formatting on data.
     *
     * @return mixed Whatever the data index contains.
     */
    public static function getOptionalData(array $data, $key, $default = null, $format = false)
    {
        if (! array_key_exists($key, $data)) {
            return $default;
        }

        if (! $format) {
            return $data[$key];
        }

        return self::getFormattedValue($data[$key], $key);
    }

    /**
     * @param TableNode $tableNode
     * @param callable $func Will receive rowNumber, Row data
     *
     * @example Table multiple values for the same target like:
     * | field1 | field2 | field3 |
     * | abc    | xyz    | 123    |
     * | 123    | xyz    | abc    |
     * | xyz    | xyz    | xyz    |
     *
     * @return array
     */
    public static function loopMultiTable(TableNode $tableNode, callable $func)
    {
        return self::looper($tableNode, $func);
    }

    /**
     * @param TableNode $tableNode
     * @param callable $func Will receive rowNumber, Column, Value
     *
     * @example TableNode:
     * | Column      | Value      |
     * | Name        | Abdul      |
     * | DOB Date    | 10-05-1989 |
     * | Paid Amount | 500        |
     *
     * @return array
     */
    public static function loopSingleTable(TableNode $tableNode, callable $func)
    {
        $element = $tableNode->getRows();
        $result = [];
        foreach ($element as $index => $row) {
            $result[] = $func($index, $row[0], $row[1]);
        }

        return $result;
    }

    /**
     * @param TableNode $tableNode
     * @param callable $func Receives the row number and the complete value set in as args.
     *
     * @example TableNode:
     * | Field       | Value      |
     * | Name        | Abdul      |
     * | DOB Date    | 10-05-1989 |
     * | Paid Amount | 500        |
     *
     * @return array
     */
    public static function loopPageFieldsTable(TableNode $tableNode, callable $func)
    {
        return self::looper($tableNode->getHash(), $func);
    }

    /**
     * @param TableNode $where
     * | Column   | Value      |
     * | Name     | Abdul      |
     * | DOB Date | 10-05-1989 |
     *
     * @return array
     */
    public static function transformTableNodeToSingleDataSet(TableNode $where)
    {
        $array = [];
        foreach ($where->getRows() as $index => $row) {
            $array[$row[0]] = self::getFormattedValue($row[1], $row[0]);
        }

        return $array;
    }

    /**
     * @param TableNode $where
     * | Name    | DOB Date   |
     * | Abdul   | 10-05-1989 |
     * | Sabhat  | 01-04-1985 |
     *
     * @return array
     */
    public static function transformTableNodeToMultiDataSets(TableNode $where)
    {
        $array = [];
        foreach ($where as $index => $row) {
            foreach ($row as $column => $value) {
                $array[$index][$column] = self::getFormattedValue($value, $column);
            }
        }

        return $array;
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
     * @return string|int
     */
    public static function getFormattedValue($value, $field)
    {
        if (strpos($field, 'Date') !== false) {
            $date = new DateTime($value);

            return $date->format('Y-m-d H:i:s');
        } elseif (strpos($field, 'Amount') !== false && is_numeric($value)) {
            return $value * 100;
        }

        return $value;
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
}
