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
     * Couple with getValue() to get the resulting values out.
     *
     * @param string $table The table to select from.
     * @param array $where The selection criteria.
     *
     * @return $this
     */
    public static function select($table, array $where)
    {
        static::getAPI()->select($table, self::resolveDataFieldMappings($where));

        return $this;
    }

    /**
     * @param string $table
     * @param array $where
     *
     * @return array
     */
    public static function getSingle($table, array $where)
    {
        self::select($table, $where);

        $data = [];
        foreach (static::getDataMapping() as $name => $dbColumnName) {
            $data[$name] = self::getValue($name);
        }

        return $data;
    }

    /**
     * @param string $column
     * @param string $table
     * @param array $where
     *
     * @return string
     */
    public static function getColumn($column, $table, array $where)
    {
        self::select($table, $where);

        return static::getValue($column);
    }

    /**
     * @param string $table The table to insert into.
     * @param array $data The data set to insert.
     *
     * @return int The insert Id.
     */
    public static function insert($table, array $data)
    {
        static::getAPI()->insert($table, self::resolveDataFieldMappings($data));

        return static::getAPI()->getLastId();
    }

    /**
     * @param string $table The table to select from.
     * @param array $valus The values data set to update with.
     * @param array $where The selection criteria.
     *
     * @return void
     */
    public static function update($table, array $values, array $where)
    {
        static::getAPI()->update(
            $table,
            self::resolveDataFieldMappings($values),
            self::resolveDataFieldMappings($where)
        );
    }

    /**
     * @param string $table The table to delete from.
     * @param array $where The selection criteria.
     *
     * @return void
     */
    public static function delete($table, array $where)
    {
        static::getAPI()->delete($table, static::resolveDataFieldMappings($where));
    }

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
     * Get the value of a column out of the keystore.
     * Depends on getBaseTable.
     *
     * @param string $column The column name.
     * @param mixed $key
     *
     * @return string
     */
    public static function getValue($key)
    {
        self::ensureBaseTable();

        return static::getAPI()->get('keyStore')
            ->getKeyword(
                static::getBaseTable() .
                '.' .
                self::getFieldMapping($key)
            );
    }

    /**
     * Truncates a table based on the value provided by getBaseTable and assumes that the table has the column id.
     * Depends on getBaseTable.
     *
     * @return void
     */
    public static function truncate()
    {
        self::ensureBaseTable();

        static::getAPI()->delete(static::getBaseTable(), [
            'id' => '!NULL'
        ]);
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
    public static function subSelect($table, $column, array $where)
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
        if (! isset(self::getDataMapping()[$key])) {
            throw new Exception("No data mapping provided for key $key");
        }

        return self::getDataMapping()[$key];
    }

    /**
     * Make sure the baseTable value is defined.
     */
    protected static function ensureBaseTable()
    {
        if (! self::getBaseTable()) {
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
}
