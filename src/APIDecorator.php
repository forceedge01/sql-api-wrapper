<?php

namespace Genesis\SQLExtensionWrapper;

use Exception;

use Genesis\SQLExtension\Context\API;

/**
* This class serves as a Decorator for the Genesis API class.
* To use this class effectively, create separate classes for each of your tables and extend off this class.
*/
abstract class APIDecorator extends Context\API implements APIDecoratorInterface
{
    /**
     * @var array The saved session storage.
     */
    private static $savedSession;

    /**
     * Will attempt to insert seed data if setupSeedData method is defined.
     */
    public function __construct()
    {
        $this->insertSeedDataIfExists();
    }

    /**
     * Returns the base table to interact with.
     *
     * @return string
     */
    abstract public function getBaseTable();

    /**
     * Returns the data mapping for the base table.
     *
     * @return array
     */
    abstract public function getDataMapping();

    /**
     * Inserts seed data if method 'setupSeedData' exists on calling class.
     *
     * @return void
     */
    public function insertSeedDataIfExists()
    {
        if (method_exists($this, 'setupSeedData')) {
            // This will kick off seed data insertion from the constructor.
            $this->insertSeedData($this->setupSeedData());
        }
    }

    /**
     * Get the value of a column out of the keystore.
     * Depends on getBaseTable.
     *
     * @param string $column The column name.
     * @param mixed $key
     *
     * @return string
     */
    public function getValue($key)
    {
        $this->ensureBaseTable();

        $this->getAPI()->get('keyStore')->getKeyword($this->getBaseTable() . '.' . $this->getFieldMapping($key));
    }

    /**
     * Truncates a table based on the value provided by getBaseTable and assumes that the table has the column id.
     * Depends on getBaseTable.
     *
     * @return void
     */
    public function truncate()
    {
        $this->ensureBaseTable();

        $this->getAPI()->delete($this->getBaseTable(), [
            'id' => '!NULL'
        ]);
    }

    /**
     * Create fresh fixture data set everytime this method is run, deleting the old value and recreating it.
     * Depends on getBaseTable.
     *
     * @param array $data The data set to create the fixture from, note if no data is provided, it will be auto-filled.
     * @param string|null $uniqueColumn The column that uniquely represents the data set and any old data set would match.
     *
     * @return int The last insert Id of the fixture data.
     */
    public function createFixture(array $data = [], $uniqueColumn = null)
    {
        $this->ensureBaseTable();

        if ($uniqueColumn) {
            $this->getAPI()->delete($this->getBaseTable(), $this->resolveDataFieldMappings([$uniqueColumn => $data[$uniqueColumn]]));
        }

        $this->getAPI()->insert($this->getBaseTable(), $this->resolveDataFieldMappings($data));

        return $this->getAPI()->getLastId();
    }

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
    public function subSelect($table, $column, array $where)
    {
        $extRefWhereArray = [];
        foreach ($where as $column => $value) {
            $extRefWhereArray[] = sprintf('%s.%s', $column, $value);
        }

        $extRefWhere = implode(',', $extRefWhereArray);

        return sprint('[%s.%s|%s]', $table, $column, $extRefWhere);
    }

    /**
     * @param string $primaryKey The unique primary key that will reference the current session.
     *
     * @return $this
     */
    public function saveSession($primaryKey)
    {
        self::$savedSession[get_called_class()] = [
            'key' => $primaryKey,
            'value' => $this->getValue($primaryKey),
        ];

        return $this;
    }

    /**
     * Automatically restores the session based on the primary key stored used by saveSession call.
     *
     * @return $this
     */
    public function restoreSession()
    {
        $callingClass = get_called_class();

        $this->getAPI()->select($this->getBaseTable(), [
            $this->getFieldMapping(self::$savedSession[$callingClass]['key']),
            self::$savedSession[$callingClass]['value'],
        ]);

        return $this;
    }

    /**
     * Method that resolves data mapping for an entire data set.
     *
     * @param array $data The data set to resolve.
     *
     * @return array Resolved data set.
     */
    protected function resolveDataFieldMappings(array $data)
    {
        $resolvedData = [];
        foreach ($data as $key => $value) {
            $resolvedData[$this->getFieldMapping($key)] = $value;
        }

        return $resolvedData;
    }

    /**
     * Get the field mapping for a column.
     *
     * @param string $key The key to get the mapping for.
     *
     * @return string
     */
    protected function getFieldMapping($key)
    {
        if (! isset($this->getDataMapping()[$key])) {
            throw new Exception("No data mapping provided for key $key");
        }

        return $this->getDataMapping()[$key];
    }

    /**
     * Convenience method to get a required index value out of array.
     *
     * @param array $data The data to check.
     * @param string $key The index to look for.
     *
     * @return mixed Whatever the data index contains.
     */
    protected function getRequiredData(array $data, $key)
    {
        if (! array_key_exists($key, $data)) {
            throw new Exception("Expect to have key '$key' provided.");
        }

        return $data[$key];
    }

    /**
     * Convenience method to get an optional index value out of array, if it does not exist will return the default value.
     *
     * @param array $data The data to check.
     * @param string $key The index to look for.
     * @param string $default The value to return if the key index is not defined.
     *
     * @return mixed Whatever the data index contains.
     */
    protected function getOptionalData(array $data, $key, $default = null)
    {
        if (! array_key_exists($key, $data)) {
            return $default;
        }

        return $data[$key];
    }

    /**
     * Make sure the baseTable value is defined.
     */
    protected function ensureBaseTable()
    {
        if (! $this->getBaseTable()) {
            throw new Exception('This call requires the getBaseTable to return the table to operate on.');
        }
    }

    /**
     * Process seed data insertion.
     *
     * @param array $seedData
     *
     * @return void
     */
    private function insertSeedData(array $seedData)
    {
        if (! $seedData) {
            throw new Exception('Seed data method defined but no value provided.');
        }

        foreach ($seedData as $table => $individualSeedData) {
            if (! is_string($table)) {
                $table = $this->getBaseTable();
            }

            if (! is_array($individualSeedData)) {
                throw new Exception("Provided data '$individualSeedData' invalid, must be an array.");
            }

            $this->getAPI()->insert($table, $individualSeedData);
        }
    }
}
