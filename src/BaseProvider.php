<?php

namespace Genesis\SQLExtensionWrapper;

use Exception;
use Genesis\SQLExtension\Context\API;

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
     * @return API
     */
    abstract public static function getApi();

    /**
     * Returns the base table to operate on.
     *
     * @return string
     */
    abstract public static function getBaseTable();

    /**
     * The data mapping to use when reading/writing data to the table.
     *
     * @return array [
     *     '<mappingName>' => '<mappedToName>',
     *     ...
     * ]
     */
    abstract public static function getDataMapping();

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
     * @param array $uniqueColumn The columns that uniquely represents the data set and any
     * old data set would match.
     *
     * @return int The last insert Id of the fixture data.
     */
    public static function createFixture(array $data = [], $uniqueColumn = [])
    {
        self::ensureBaseTable();

        if ($uniqueColumn) {
            if (! isset($data[$uniqueColumn])) {
                throw new Exception('Unique column provided in createFixture does not exist on data.');
            }

            static::getAPI()->delete(static::getBaseTable(), self::resolveDataFieldMappings(
                [$uniqueColumn => $data[$uniqueColumn]]
            ));
        }

        return self::insert($data);
    }

    /**
     * Get a row from the database.
     *
     * @param array $where
     *
     * @return array
     */
    public static function getSingle(array $where)
    {
        self::ensureBaseTable();
        self::select($where);

        $data = [];
        foreach (static::getDataMapping() as $name => $dbColumnName) {
            $data[$name] = self::getValue($name);
        }

        return $data;
    }

    /**
     * Get value of a column from the database.
     *
     * @param string $column
     * @param array $where
     *
     * @return string
     */
    public static function getColumn($column, array $where)
    {
        self::ensureBaseTable();
        $table = static::getBaseTable();
        self::select($where);

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
     *
     * @return void
     */
    public static function select(array $where)
    {
        self::ensureBaseTable();
        static::getAPI()->select(static::getBaseTable(), self::resolveDataFieldMappings($where));
    }

    /**
     * @param array $data The data set to insert.
     *
     * @return int The insert Id.
     */
    public static function insert(array $data)
    {
        self::ensureBaseTable();

        if (method_exists(get_called_class(), 'getDefaults')) {
            $data = array_merge(static::getDefaults($data), $data);
        }

        static::getAPI()->insert(static::getBaseTable(), self::resolveDataFieldMappings($data));

        return static::getAPI()->getLastId();
    }

    /**
     * @param array $values The values data set to update with.
     * @param array $where The selection criteria.
     *
     * @return void
     */
    public static function update(array $values, array $where)
    {
        self::ensureBaseTable();

        static::getAPI()->update(
            static::getBaseTable(),
            self::resolveDataFieldMappings($values),
            self::resolveDataFieldMappings($where)
        );
    }

    /**
     * @param array $where The selection criteria.
     *
     * @return void
     */
    public static function delete(array $where)
    {
        self::ensureBaseTable();
        static::getAPI()->delete(static::getBaseTable(), self::resolveDataFieldMappings($where));
    }

    /**
     * Truncates a table based on the value provided by getBaseTable and assumes that the table has the column id.
     * Depends on getBaseTable.
     *
     * @param null|mixed $table
     *
     * @return void
     */
    public static function truncate($table = null)
    {
        $table = self::getTable($table);
        static::getAPI()->delete($table, [
            'id' => '!NULL'
        ]);
    }

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
    public static function subSelect($table, $column, array $where)
    {
        return static::getAPI()->subSelect($table, $column, $where);
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
            self::getFieldMapping(self::$savedSession[$callingClass]['key']) =>
            self::$savedSession[$callingClass]['value']
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
    private static function insertSeedData(array $seedData)
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

            static::getAPI()->insert($table, self::resolveDataFieldMappings($individualSeedData));
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
