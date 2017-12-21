<?php

namespace Genesis\SQLExtensionWrapper\Tests;

use Genesis\SQLExtension\Context\Interfaces\APIInterface;
use Genesis\SQLExtension\Context\Interfaces\KeyStoreInterface;
use Genesis\SQLExtensionWrapper\BaseProvider;
use PHPUnit_Framework_TestCase;
use ReflectionClass;
use ReflectionProperty;

// This violates PSR, but since this is not production code and gives us a lot more in return, we shall keep it!
class TestClass extends BaseProvider
{
    public static $api;

    public static function getAPI()
    {
        return self::$api;
    }

    public static function getBaseTable()
    {
        return 'test.table';
    }

    public static function getDataMapping()
    {
        return [
            'id' => 'id',
            'name' => 'forename',
            'dateOfBirth' => 'dob'
        ];
    }
}

class BaseProviderTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var BaseProviderInterface The object to be tested.
     */
    private $testObject;

    /**
     * @var ReflectionClass The reflection class.
     */
    private $reflection;

    /**
     * @var array The test object dependencies.
     */
    private $dependencies = [];

    /**
     * Set up the testing object.
     */
    public function setUp()
    {
        TestClass::$api = $this->createMock(APIInterface::class);

        $this->reflection = new ReflectionClass(TestClass::class);
        // $this->testObject = $this->reflection->newInstance();
    }

    /**
     * testGetSingle Test that getSingle executes as expected.
     */
    public function testGetSingle()
    {
        // Prepare / Mock
        $where = ['name' => 20];
        $userId = 5;
        $forename = 'Abdul Wahab';
        $dateOfBirth = '10-05-1989';

        $keyStoreMock = $this->createMock(KeyStoreInterface::class);
        $keyStoreMock->expects($this->at(0))
            ->method('getKeyword')
            ->with('test.table.id')
            ->willReturn($userId);
        $keyStoreMock->expects($this->at(1))
            ->method('getKeyword')
            ->with('test.table.forename')
            ->willReturn($forename);
        $keyStoreMock->expects($this->at(2))
            ->method('getKeyword')
            ->with('test.table.dob')
            ->willReturn($dateOfBirth);

        TestClass::$api->expects($this->exactly(3))
            ->method('get')
            ->with('keyStore')
            ->willReturn($keyStoreMock);

        // Execute
        $result = TestClass::getSingle($where);

        // Assert Result
        self::assertCount(3, $result);
        self::assertEquals($userId, $result['id']);
        self::assertEquals($forename, $result['name']);
        self::assertEquals($dateOfBirth, $result['dateOfBirth']);
    }

    /**
     * @expectedException Exception
     */
    public function testGetSingleWrongMappingProducesException()
    {
        // Prepare / Mock
        $where = ['random' => 20];

        // Execute
        TestClass::getSingle($where);
    }

    /**
     * testCreateFixture Test that createFixture executes as expected.
     */
    public function testCreateFixtureWithoutUniqueKey()
    {
        // Prepare / Mock
        $data = [
            'name' => 'Abdul'
        ];
        $lastId = 5;

        TestClass::$api->expects($this->never())
            ->method('delete');
        TestClass::$api->expects($this->once())
            ->method('insert')
            ->with('test.table', ['forename' => 'Abdul']);
        TestClass::$api->expects($this->once())
            ->method('getLastId')
            ->willReturn($lastId);

        // Execute
        $result = TestClass::createFixture($data);

        // Assert Result
        self::assertEquals($lastId, $result);
    }

    /**
     * testCreateFixture Test that createFixture executes as expected.
     */
    public function testCreateFixtureWithUniqueKey()
    {
        // Prepare / Mock
        $uniqueKey = 'name';
        $data = [
            'id' => 20,
            'name' => 'Abdul'
        ];
        $lastId = 5;

        TestClass::$api->expects($this->once())
            ->method('delete')
            ->with('test.table', ['forename' => 'Abdul']);
        TestClass::$api->expects($this->once())
            ->method('insert')
            ->with('test.table', ['id' => 20, 'forename' => 'Abdul']);
        TestClass::$api->expects($this->once())
            ->method('getLastId')
            ->willReturn($lastId);

        // Execute
        $result = TestClass::createFixture($data, $uniqueKey);

        // Assert Result
        self::assertEquals($lastId, $result);
    }

    /**
     * testGetColumn Test that getColumn executes as expected.
     */
    public function testGetColumn()
    {
        // Prepare / Mock
        $column = 'id';
        $userId = 55;
        $where = ['name' => 'Abdul Wahab'];

        // Value of the id column will be resolved.
        $keyStoreMock = $this->createMock(KeyStoreInterface::class);
        $keyStoreMock->expects($this->at(0))
            ->method('getKeyword')
            ->with('test.table.id')
            ->willReturn($userId);
        TestClass::$api->expects($this->once())
            ->method('get')
            ->with('keyStore')
            ->willReturn($keyStoreMock);

        // Internal select method will be called.
        TestClass::$api->expects($this->once())
            ->method('select')
            ->with('test.table', ['forename' => 'Abdul Wahab']);

        // Execute
        $result = TestClass::getColumn($column, $where);

        // Assert Result
        self::assertEquals($userId, $result);
    }

    /**
     * testGetValue Test that getValue executes as expected.
     */
    public function testGetValue()
    {
        // Prepare / Mock
        $key = 'name';
        $expectedResult = 'resulting value';

        // Value of the id column will be resolved.
        // When the table is not provided, the mapping is enforced.
        $keyStoreMock = $this->createMock(KeyStoreInterface::class);
        $keyStoreMock->expects($this->at(0))
            ->method('getKeyword')
            ->with('test.table.forename')
            ->willReturn($expectedResult);
        TestClass::$api->expects($this->once())
            ->method('get')
            ->with('keyStore')
            ->willReturn($keyStoreMock);

        // Execute
        $result = TestClass::getValue($key);

        // Assert Result
        self::assertEquals($expectedResult, $result);
    }

    /**
     * testGetValue Test that getValue executes as expected.
     *
     * @expectedException Exception
     */
    public function testGetValueInternalMappingEnforced()
    {
        // Prepare / Mock
        $key = 'abc';

        // Value of the id column will be resolved.
        $keyStoreMock = $this->createMock(KeyStoreInterface::class);
        $keyStoreMock->expects($this->never())
            ->method('getKeyword');
        TestClass::$api->expects($this->never())
            ->method('get');

        // Execute
        TestClass::getValue($key);
    }

    /**
     * testSelect Test that select executes as expected.
     */
    public function testSelect()
    {
        $where = ['id' => 5];

        // Prepare / Mock
        TestClass::$api->expects($this->once())
            ->method('select')
            ->with('test.table', $where);

        // Execute
        $this->invokeMethod('select', [$where]);
    }

    /**
     * testInsert Test that insert executes as expected.
     */
    public function testInsert()
    {
        // Prepare / Mock
        $data = [
            'name' => 'Abdul Wahab Qureshi',
            'dateOfBirth' => '10-05-1989'
        ];
        $table = 'test.table';
        $lastId = 3434;

        // Prepare / Mock
        TestClass::$api->expects($this->once())
            ->method('insert')
            ->with($table, [
                'forename' => 'Abdul Wahab Qureshi',
                'dob' => '10-05-1989'
            ]);
        TestClass::$api->expects($this->once())
            ->method('getLastId')
            ->willReturn($lastId);

        // Execute
        $result = $this->invokeMethod('insert', [$data]);

        // Assert Result
        self::assertEquals($lastId, $result);
    }

    /**
     * testUpdate Test that update executes as expected.
     */
    public function testUpdate()
    {
        // Prepare / Mock
        $values = [
            'name' => 'Abdul Wahab Qureshi',
            'dateOfBirth' => '10-05-1989'
        ];
        $where = [
            'name' => 'Qureshi'
        ];
        $table = 'test.table';

        TestClass::$api->expects($this->once())
            ->method('update')
            ->with($table, [
                'forename' => 'Abdul Wahab Qureshi',
                'dob' => '10-05-1989'
            ], [
                'forename' => 'Qureshi'
            ]);

        // Execute
        $this->invokeMethod('update', [$values, $where]);
    }

    /**
     * testDelete Test that delete executes as expected.
     */
    public function testDelete()
    {
        // Prepare / Mock
        $where = ['name' => 'Jackie', 'id' => 20];

        TestClass::$api->expects($this->once())
            ->method('delete')
            ->with('test.table', [
                'forename' => 'Jackie',
                'id' => 20
            ]);

        // Execute
        $this->invokeMethod('delete', [$where]);
    }

    /**
     * testTruncate Test that truncate executes as expected.
     */
    public function testTruncateWithTable()
    {
        // Prepare / Mock
        $table = 'User';

        TestClass::$api->expects($this->once())
            ->method('delete')
            ->with('User', [
                'id' => '!NULL'
            ]);

        // Execute
        $this->invokeMethod('truncate', [$table]);
    }

    /**
     * testSubSelect Test that subSelect executes as expected.
     */
    public function testSubSelect()
    {
        // Prepare / Mock
        $table = 'User';
        $column = 'email';
        $where = ['name' => 'Abdul', 'dob' => '10-05-1989'];

        // Execute
        $result = $this->invokeMethod('subSelect', [$table, $column, $where]);

        // Assert Result
        self::assertEquals('[User.email|name:Abdul,dob:10-05-1989]', $result);
    }

    /**
     * testSaveSession Test that saveSession executes as expected.
     */
    public function testSaveSession()
    {
        // Prepare / Mock
        $primaryKey = 'id';

        // Value of the id column will be resolved.
        // When the table is not provided, the mapping is enforced.
        $keyStoreMock = $this->createMock(KeyStoreInterface::class);
        $keyStoreMock->expects($this->at(0))
            ->method('getKeyword')
            ->with('test.table.id')
            ->willReturn(55);
        TestClass::$api->expects($this->once())
            ->method('get')
            ->with('keyStore')
            ->willReturn($keyStoreMock);

        // Execute
        TestClass::saveSession($primaryKey);

        // Access the session value saved.
        $result = $this->getPrivatePropertyValue('savedSession');

        // Assert Result
        self::assertEquals([TestClass::class => ['key' => 'id', 'value' => 55]], $result);
    }

    /**
     * testRestoreSession Test that restoreSession executes as expected.
     */
    public function testRestoreSession()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->restoreSession();

        // Assert Result
        self::assert();
    }

    /**
     * testInsertSeedDataIfExists Test that insertSeedDataIfExists executes as expected.
     */
    public function testInsertSeedDataIfExists()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->insertSeedDataIfExists();

        // Assert Result
        self::assert();
    }

    /**
     * @param string $method The method to invoke.
     * @param array $args The arguments to pass to the method.
     *
     * @return string
     */
    private function invokeMethod($method, array $args)
    {
        $reflectionMethod = $this->reflection->getMethod($method);
        $reflectionMethod->setAccessible(true);

        return $reflectionMethod->invokeArgs($this->testObject, $args);
    }

    /**
     * @param string $property
     *
     * @return mixed
     */
    private function getPrivatePropertyValue($property)
    {
        $reflectionProperty = new ReflectionProperty(BaseProvider::class, 'savedSession');
        $reflectionProperty->setAccessible(true);

        return $reflectionProperty->getValue();
    }
}
