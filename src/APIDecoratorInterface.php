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
    public function getAPI();

    /**
     * Inserts seed data if method 'setupSeedData' exists on calling class.
     *
     * @return void
     */
    public function insertSeedDataIfExists();

    /**
     * Get the value of a column out of the keystore.
     * Depends on getBaseTable.
     *
     * @param string $column The column name.
     *
     * @return string
     */
    public function getValue($column);

    /**
     * Truncates a table based on the value provided by getBaseTable and assumes that the table has the column id.
     *
     * @return void
     */
    public function truncate();

    /**
     * Create fresh fixture data set everytime this method is run, deleting the old value and recreating it.
     * Depends on getBaseTable.
     *
     * @param array $data The data set to create the fixture from, note if no data is provided, it will be auto-filled.
     * @param string|null $uniqueColumn The column that uniquely represents the data set and any old data set would match.
     *
     * @return int The last insert Id of the fixture data.
     */
    public function createFixture(array $data = [], $uniqueColumn = null);

    /**
     * Construct an external reference clause for the query.
     * Note: This will only work with the first result returned.
     *
     * @param string $table The table to select from.
     * @param string $column The column to select within the table.
     * @param array $where The array to filter the values from.
     *
     * @example Example usage: Update postcode where address Id is provided.
     *
     * $this->update('Address', [
     *     'postCodeId' => $this->subSelect('PostCode.id|code: B237QQ')
     * ], [
     *     'id' => $addressId
     * ]);
     *
     * @return string The subSelect external ref query.
     */
    public function subSelect($table, $column, array $where);

    /**
     * @param string $primaryKey The unique primary key that will reference the current session.
     *
     * @return $this
     */
    public function saveSession($primaryKey);

    /**
     * Automatically restores the session based on the primary key stored used by saveSession call.
     *
     * @return $this
     */
    public function restoreSession();
}
