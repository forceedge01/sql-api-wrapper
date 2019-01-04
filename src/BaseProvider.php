<?php

namespace Genesis\SQLExtensionWrapper;

use Exception;
use Genesis\SQLExtensionWrapper\Exception\RequiredDataException;
use Genesis\SQLExtension\Context;

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
     * @var array Bridge to other types models.
     */
    private static $bridge;

    /**
     * @var Context\Api
     */
    private static $sqlApi;

    /**
     * @return array
     */
    public static function setCredentials(array $credentials)
    {
        self::setApi($credentials);
    }

    /**
     * @param array $credentials
     */
    private static function setApi(array $credentials)
    {
        if (! self::$sqlApi) {
            self::$sqlApi = new Context\API(
                new Context\DBManager(new Context\DatabaseProviders\Factory(), $credentials),
                new Context\SQLBuilder(),
                new Context\LocalKeyStore()
            );
        }
    }

    /**
     * Call the setCredentails method in your feature context constructor file to get this up and running.
     *
     * Override if you want to use a different version of the API.
     *
     * @return Context\Api
     */
    public static function getApi()
    {
        return self::$sqlApi;
    }

    /**
     * Returns the base table to operate on.
     *
     * @return string
     */
    public static function getBaseTableForCaller()
    {
        if (($bridge = self::$bridge) &&
            self::implementsInterface(get_called_class(), BridgedDataModInterface::class)
        ) {
            return $bridge->getBaseTable(static::getBridgedClass());
        }

        return static::getBaseTable();
    }

    /**
     * The data mapping to use when reading/writing data to the table.
     *
     * @return array [
     *     '<mappingName>' => '<mappedToName>',
     *     ...
     * ]
     */
    public static function getDataMappingForCaller()
    {
        if (($bridge = self::$bridge) &&
            self::implementsInterface(get_called_class(), BridgedDataModInterface::class)
        ) {
            return $bridge->getDataMapping(static::getBridgedClass());
        }

        return static::getDataMapping();
    }

    /**
     * @param array $data The data being passed in.
     *
     * @return array
     */
    public static function getDefaultsForCaller(array $data)
    {
        if (method_exists(get_called_class(), 'getDefaults')) {
            return static::getDefaults($data);
        }

        return [];
    }

    /**
     * Their can only be one bridge registered at any given time.
     *
     * @param string $bridge
     *
     * @return void
     */
    public static function registerBridge($bridge)
    {
        if (! ($bridge instanceof BridgeInterface)) {
            throw new Exception('Bridge must implement ' . BridgeInterface::class);
        }

        self::$bridge = $bridge;
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
     * Create fresh fixture data set everytime this method is run, deleting the old value and recreating it.
     * Depends on getBaseTable. The data provided is validated against the mapping you have set in your data mod
     * so you don't pass in values that are not intended to be passed in.
     *
     * @param array $data The data set to create the fixture from, note if no data is provided, it will be auto-filled.
     * @param string $uniqueColumn The column that uniquely represents the data set and any
     * old data set would match.
     *
     * @return int The last insert Id of the fixture data.
     */
    public static function createFixture(array $data = [], $uniqueColumn = null)
    {
        self::ensureBaseTable();

        if ($uniqueColumn) {
            if (! isset($data[$uniqueColumn])) {
                throw new Exception('Unique column provided in createFixture does not exist on data.');
            }

            static::getAPI()->delete(self::getBaseTableForCaller(), self::resolveDataFieldMappings(
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
        foreach (self::getDataMappingForCaller() as $name => $dbColumnName) {
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
        $table = self::getBaseTableForCaller();
        self::select($where);

        return self::getValue($column);
    }

    /**
     * Get the value of a column out of the keystore. Throws exception if not found.
     * Depends on getBaseTable.
     *
     * @param string $key The column name.
     * @param string $message The message to display when not found.
     *
     * @return string
     */
    public static function getRequiredValue($key, $message = null)
    {
        $mapping = self::getFieldMapping($key);

        try {
            return static::getAPI()->get('keyStore')
                ->getKeyword(
                    self::getBaseTableForCaller() .
                    '.' .
                    $mapping
                );
        } catch (Exception $e) {
            throw new Exception($message . ' - ' . $e->getMessage());
        }
    }

    /**
     * Get the value of a column out of the keystore.
     * Depends on getBaseTable.
     *
     * @param string $key The column name.
     * @param string|null $defaultValue The default value to return if not found.
     *
     * @return string|null
     */
    public static function getValue($key, $defaultValue = null)
    {
        $mapping = self::getFieldMapping($key);

        try {
            return static::getAPI()->get('keyStore')
                ->getKeyword(
                    self::getBaseTableForCaller() .
                    '.' .
                    $mapping
                );
        } catch (Exception $e) {
            return $defaultValue;
        }
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
    public static function rawSubSelect($table, $column, array $where)
    {
        return static::getAPI()->subSelect($table, $column, $where);
    }

    /**
     * Construct an external reference clause for the query.
     * Note: This will only work with the first result returned.
     *
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
    public static function subSelect($column, array $where)
    {
        $table = self::getBaseTableForCaller();

        return static::getAPI()->subSelect(
            $table,
            self::getFieldMapping($column),
            self::resolveDataFieldMappings($where)
        );
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

        static::getAPI()->select(self::getBaseTableForCaller(), [
            self::getFieldMapping(self::$savedSession[$callingClass]['key']) =>
            self::$savedSession[$callingClass]['value']
        ]);
    }

    /**
     * This method is useful for placing keyword references for data mods
     * in strings to later replace with their values.
     *
     * @param string $key
     *
     * @return string
     */
    public static function getKeyword($key)
    {
        return '{' . self::getBaseTableForCaller() . '.' . self::getFieldMapping($key) . '}';
    }

    /**
     * @param array $indexes
     * @param array $data
     *
     * @return void
     */
    protected static function requiredData(array $indexes, array $data)
    {
        foreach ($indexes as $index) {
            if (! array_key_exists($index, $data)) {
                throw new RequiredDataException($index, $indexes, $data);
            }
        }
    }

    /**
     * Couple with getValue() to get the resulting values out. This method is protected and should be implemented
     * by one your data modules, this is so you can provide more context around the action your taking.
     *
     * To get a value for a column or access an entire row, please look at other public methods available.
     *
     * @param array $where The selection criteria.
     *
     * @return void
     */
    public static function select(array $where)
    {
        self::ensureBaseTable();
        static::getAPI()->select(self::getBaseTableForCaller(), self::resolveDataFieldMappings($where));
    }

    /**
     * @param array $data The data set to insert. This method is protected and should be implemented
     * by one your data modules, this is so you can provide more context around the action your taking.
     *
     * @return int The insert Id.
     */
    public static function insert(array $data)
    {
        self::ensureBaseTable();

        $data = array_merge(self::getDefaultsForCaller($data), $data);
        static::getAPI()->insert(self::getBaseTableForCaller(), self::resolveDataFieldMappings($data));

        return static::getAPI()->getLastId();
    }

    /**
     * Since an update can cause chaos and affect other rows without immediately
     * implications, this method should be used from within a method exposed
     * by the data module with more context around what it intends to do.
     *
     * This method is protected and should be implemented
     * by one your data modules, this is so you can provide more context around the action your taking.
     *
     * @param array $values The values data set to update with.
     * @param array $where The selection criteria.
     *
     * @return void
     */
    protected static function update(array $values, array $where)
    {
        self::ensureBaseTable();

        static::getAPI()->update(
            self::getBaseTableForCaller(),
            self::resolveDataFieldMappings($values),
            self::resolveDataFieldMappings($where)
        );
    }

    /**
     * This method is protected and should be implemented
     * by one your data modules, this is so you can provide more context around the action you're taking.
     *
     * @param array $where The selection criteria.
     *
     * @return void
     */
    public static function delete(array $where)
    {
        self::ensureBaseTable();

        static::getAPI()->delete(self::getBaseTableForCaller(), self::resolveDataFieldMappings($where));
    }

    /**
     * @param array $where
     *
     * @return string
     */
    public static function assertExists(array $where)
    {
        self::ensureBaseTable();
        static::getApi()->assertExists(self::getBaseTableForCaller(), self::resolveDataFieldMappings($where));
    }

    /**
     * @param array $where
     *
     * @return string
     */
    public static function assertNotExists(array $where)
    {
        self::ensureBaseTable();
        static::getApi()->assertNotExists(self::getBaseTableForCaller(), self::resolveDataFieldMappings($where));
    }

    /**
     * @param array $data
     *
     * @return Representations\Query
     */
    public static function getSampleInsertQuery(array $data = [])
    {
        self::ensureBaseTable();
        return BaseProvider::getApi()->getSampleInsertQuery(
            self::getBaseTableForCaller(),
            self::resolveDataFieldMappings($data)
        );
    }

    /**
     * Truncates a table based on the value provided by getBaseTable and assumes that the table has the column id.
     * Depends on getBaseTable. This method is protected and should be implemented
     * by one your data modules, this is so you can provide more context around the action your taking.
     *
     * @param null|mixed $table
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
        $mapping = self::getDataMappingForCaller();
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
        if (! self::getBaseTableForCaller()) {
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
                $table = self::getBaseTableForCaller();
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
            return self::getBaseTableForCaller();
        }

        return $table;
    }

    /**
     * @param string $class
     * @param string $interface
     *
     * @return bool
     */
    private static function implementsInterface($class, $interface)
    {
        return (in_array($interface, class_implements($class)));
    }
}
