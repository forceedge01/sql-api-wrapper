<?php

namespace Genesis\SQLExtensionWrapper;

use Genesis\SQLExtension\Context;

/**
* This class serves as a Decorator for the Genesis API.
* To use this class effectively, create separate classes for each of your tables and extend off this class.
*/
interface APIDecoratorInterface
{
    /**
     * @return Context\Interfaces\APIInterface
     */
    public static function getAPI();

    /**
     * Returns the base table to interact with.
     *
     * @return string
     */
    public static function getBaseTable();

    /**
     * Returns the data mapping for the base table.
     *
     * @return array
     */
    public static function getDataMapping();

    /**
     * Inserts seed data if method 'setupSeedData' exists on calling class.
     *
     * @return void
     */
    public static function insertSeedDataIfExists();

    /**
     * Get the value of a column out of the keystore.
     * Depends on getBaseTable.
     *
     * @param string $column The column name.
     *
     * @return string
     */
    public static function getValue($column);

    /**
     * Get a single record out mapped to your defined mapping. For single columns use
     * getColumn which is better in performance.
     *
     * @param array $where
     *
     * @return array
     */
    public static function getSingle(array $where);

    /**
     * @param string $column
     * @param array $where
     *
     * @return string
     */
    public static function getColumn($column, array $where);

    /**
     * Create fresh fixture data set everytime this method is run, deleting the old value and recreating it.
     * Depends on getBaseTable.
     *
     * @param array $data The data set to create the fixture from, note if no data is provided, it will be auto-filled.
     * @param string|null $uniqueColumn The column that uniquely represents the data set and any
     * old data set would match.
     *
     * @return int The last insert Id of the fixture data.
     */
    public static function createFixture(array $data = [], $uniqueColumn = null);

    /**
     * @param string $primaryKey The unique primary key that will reference the current session.
     *
     * @return $this
     */
    public static function saveSession($primaryKey);

    /**
     * Automatically restores the session based on the primary key stored used by saveSession call.
     *
     * @return $this
     */
    public static function restoreSession();

    /**
     * TODO: take the reference of a dataMod as the table, and resolve datamapping from it.
     *
     * Construct an external reference clause for the query.
     * Note: This will only work with the first result returned.
     *
     * @param string $table The table to select from.
     * @param string $column The column to select within the table.
     * @param array $where The array to filter the values from.
     *
     * @example Example usage: Update postcode where address Id is provided.
     *
     * class::update('Address', [
     *     'postCodeId' => class::subSelect('PostCode', 'id', ['code'=> 'B237QQ'])
     * ], [
     *     'id' => $addressId
     * ]);
     *
     * @return string The subSelect external ref query.
     */
    public static function subSelect($table, $column, array $where);

    /**
     * Their can only be one bridge registered at any given time.
     *
     * @param string $bridgeInterface
     * @param string $bridgeHandler
     *
     * @return void
     */
    public static function registerBridge($bridgeHandler);
}
