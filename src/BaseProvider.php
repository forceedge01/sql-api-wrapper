<?php

namespace Genesis\SQLExtensionWrapper;

use Exception;

/**
* This class serves as a Decorator for the Genesis API class.
* To use this class effectively, create separate classes for each of your tables and extend off this class.
*/
abstract class BaseProvider implements APIDecoratorInterface
{
    /**
     * @var array The saved session storage.
     */
    private static $savedSession;

    /**
     * Inserts seed data if method 'setupSeedData' exists on calling class.
     *
     * @return void
     */
    public static function insertSeedDataIfExists()
    {
        if (method_exists(get_called_class(), 'setupSeedData')) {
            self::insertSeedData(static::setupSeedData());
        }
    }

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
    public static function createFixture(array $data = [], $uniqueColumn = null)
    {
        self::ensureBaseTable();

        if ($uniqueColumn) {
            static::getAPI()->delete(static::getBaseTable(), self::resolveDataFieldMappings(
                [$uniqueColumn => $data[$uniqueColumn]]
            ));
        }

        static::getAPI()->insert(static::getBaseTable(), self::resolveDataFieldMappings($data));

        return static::getAPI()->getLastId();
    }

    /**
     * @param array $where
     *
     * @return array
     */
    public static function getSingle(array $where)
    {
        self::ensureBaseTable();
        self::select($where, static::getBaseTable());

        $data = [];
        foreach (static::getDataMapping() as $name => $dbColumnName) {
            $data[$name] = self::getValue($name);
        }

        return $data;
    }

    /**
     * @param string $column
     * @param array $where
     *
     * @return string
     */
    public static function getColumn($column, array $where)
    {
        self::ensureBaseTable();
        $table = static::getBaseTable();
        self::select($where, $table);

        return self::getValue($column);
    }

    /**
     * Get the value of a column out of the keystore.
     * Depends on getBaseTable.
     *
     * @param string $key The column name.
     * @param string|null $table The table name.
     *
     * @return string
     */
    public static function getValue($key)
    {
        $mapping = self::getFieldMapping($key);

        return static::getAPI()->get('keyStore')
            ->getKeyword(
                static::getBaseTable() .
                '.' .
                $mapping
            );
    }

    /**
     * Couple with getValue() to get the resulting values out.
     *
     * @param array $where The selection criteria.
     * @param string|null $table The table to select from.
     *
     * @return void
     */
    protected static function select(array $where, $table = null)
    {
        $table = self::getTable($table);
        static::getAPI()->select($table, self::resolveDataFieldMappings($where));
    }

    /**
     * @param array $data The data set to insert.
     * @param string|null $table The table to insert into.
     *
     * @return int The insert Id.
     */
    protected static function insert(array $data, $table = null)
    {
        $table = self::getTable($table);
        static::getAPI()->insert($table, self::resolveDataFieldMappings($data));

        return static::getAPI()->getLastId();
    }

    /**
     * @param array $values The values data set to update with.
     * @param array $where The selection criteria.
     * @param string|null $table The table to select from.
     *
     * @return void
     */
    protected static function update(array $values, array $where, $table = null)
    {
        $table = self::getTable($table);
        static::getAPI()->update(
            $table,
            self::resolveDataFieldMappings($values),
            self::resolveDataFieldMappings($where)
        );
    }

    /**
     * @param array $where The selection criteria.
     * @param string|null $table The table to delete from.
     *
     * @return void
     */
    protected static function delete(array $where, $table = null)
    {
        $table = self::getTable($table);
        static::getAPI()->delete($table, static::resolveDataFieldMappings($where));
    }

    /**
     * Truncates a table based on the value provided by getBaseTable and assumes that the table has the column id.
     * Depends on getBaseTable.
     *
     * @return void
     */
    protected static function truncate($table = null)
    {
        $table = self::getTable($table);
        static::getAPI()->delete($table, [
            'id' => '!NULL'
        ]);
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
     * class::update('Address', [
     *     'postCodeId' => class::subSelect('PostCode', 'id', ['code'=> 'B237QQ'])
     * ], [
     *     'id' => $addressId
     * ]);
     *
     * @return string The subSelect external ref query.
     */
    protected static function subSelect($table, $column, array $where)
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
     * @return void
     */
    public static function saveSession($primaryKey)
    {
        self::$savedSession[get_called_class()] = [
            'key' => $primaryKey,
            'value' => self::getValue($primaryKey),
        ];
    }

    /**
     * Automatically restores the session based on the primary key stored used by saveSession call.
     *
     * @return void
     */
    public static function restoreSession()
    {
        $callingClass = get_called_class();

        static::getAPI()->select(static::getBaseTable(), [
            self::getFieldMapping(self::$savedSession[$callingClass]['key']),
            self::$savedSession[$callingClass]['value'],
        ]);
    }

    /**
     * Method that resolves data mapping for an entire data set.
     *
     * @param array $data The data set to resolve.
     *
     * @return array Resolved data set.
     */
    protected static function resolveDataFieldMappings(array $data)
    {
        $resolvedData = [];
        foreach ($data as $key => $value) {
            $resolvedData[self::getFieldMapping($key)] = $value;
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
    protected static function getFieldMapping($key)
    {
        $mapping = static::getDataMapping();
        if (! isset($mapping[$key])) {
            throw new Exception(
                "No data mapping provided for key '$key', mapping provided: " . print_r($mapping, true)
            );
        }

        return $mapping[$key];
    }

    /**
     * Make sure the baseTable value is defined.
     */
    protected static function ensureBaseTable()
    {
        if (! static::getBaseTable()) {
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
                $table = static::getBaseTable();
            }

            if (! is_array($individualSeedData)) {
                throw new Exception("Provided data '$individualSeedData' invalid, must be an array.");
            }

            static::getAPI()->insert($table, $individualSeedData);
        }
    }

    /**
     * Get the table to interact with, if the table value is provided then use that. Otherwise
     * get the table value defined by the calling class.
     *
     * @param string $table
     *
     * @return string
     */
    private static function getTable($table)
    {
        if (! $table) {
            return static::getBaseTable();
        }

        return $table;
    }
}
