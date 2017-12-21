<?php

namespace Genesis\SQLExtensionWrapper\Tests;

use Genesis\SQLExtension\Context\Interfaces\APIInterface;
use Genesis\SQLExtension\Context\Interfaces\KeyStoreInterface;
use Genesis\SQLExtensionWrapper\BaseProvider;
use PHPUnit_Framework_TestCase;
use ReflectionClass;

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
        TestClass::$api = $this->getMock(APIInterface::class);

        $this->reflection = new ReflectionClass(TestClass::class);
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

        $keyStoreMock = $this->getMock(KeyStoreInterface::class);
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
     * testGetColumn Test that getColumn executes as expected.
     */
    public function testGetColumn()
    {
        // Prepare / Mock
        $column = 'id';
        $userId = 55;
        $where = ['name' => 'Abdul Wahab'];

        // Value of the id column will be resolved.
        $keyStoreMock = $this->getMock(KeyStoreInterface::class);
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
        $keyStoreMock = $this->getMock(KeyStoreInterface::class);
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
        $keyStoreMock = $this->getMock(KeyStoreInterface::class);
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
    public function testSelectWithTable()
    {
        $table = 'User';
        $where = ['dateOfBirth' => '10-05-1989'];

        // Prepare / Mock
        TestClass::$api->expects($this->once())
            ->method('select')
            ->with($table, ['dob' => '10-05-1989']);

        // Execute
        $this->invokeMethod('select', [$where, $table]);
    }

    /**
     * testSelect Test that select executes as expected.
     */
    public function testSelectWithoutTable()
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
    public function testInsertWithTable()
    {
        // Prepare / Mock
        $data = [
            'name' => 'Abdul Wahab Qureshi',
            'dateOfBirth' => '10-05-1989'
        ];
        $table = 'User';
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
        $result = $this->invokeMethod('insert', [$data, $table]);

        // Assert Result
        self::assertEquals($lastId, $result);
    }

    /**
     * testInsert Test that insert executes as expected.
     */
    public function testInsertWithoutTable()
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
    public function testUpdateWithTable()
    {
        // Prepare / Mock
        $values = [
            'name' => 'Abdul Wahab Qureshi',
            'dateOfBirth' => '10-05-1989'
        ];
        $where = [
            'name' => 'Qureshi'
        ];
        $table = 'User';

        TestClass::$api->expects($this->once())
            ->method('update')
            ->with($table, [
                'forename' => 'Abdul Wahab Qureshi',
                'dob' => '10-05-1989'
            ], [
                'forename' => 'Qureshi'
            ]);

        // Execute
        $this->invokeMethod('update', [$values, $where, $table]);
    }

    /**
     * testUpdate Test that update executes as expected.
     */
    public function testUpdateWithoutTable()
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
     * testCreateFixture Test that createFixture executes as expected.
     */
    public function testCreateFixture()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->createFixture();

        // Assert Result
        self::assert();
    }

    /**
     * testDelete Test that delete executes as expected.
     */
    public function testDeleteWithTable()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        $where = [];
        $table = '';

        // Execute
        $result = $this->invokeMethod('delete', [$where, $table]);

        // Assert Result
        self::assert();
    }

    /**
     * testDelete Test that delete executes as expected.
     */
    public function testDeleteWithoutTable()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        $where = [];

        // Execute
        $result = $this->invokeMethod('delete', [$where]);

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
     * testTruncate Test that truncate executes as expected.
     */
    public function testTruncate()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->truncate();

        // Assert Result
        self::assert();
    }

    /**
     * testSubSelect Test that subSelect executes as expected.
     */
    public function testSubSelect()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->subSelect();

        // Assert Result
        self::assert();
    }

    /**
     * testSaveSession Test that saveSession executes as expected.
     */
    public function testSaveSession()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->saveSession();

        // Assert Result
        self::assert();
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
     * testGetRequiredData Test that getRequiredData executes as expected.
     */
    public function testGetRequiredData()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->getRequiredData();

        // Assert Result
        self::assert();
    }

    /**
     * testGetOptionalData Test that getOptionalData executes as expected.
     */
    public function testGetOptionalData()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->getOptionalData();

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
}
