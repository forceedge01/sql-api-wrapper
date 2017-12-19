<?php

namespace Genesis\SQLExtensionWrapper\Tests;

use Genesis\SQLExtension\Context\Interfaces\APIInterface;
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
            'name' => 'forname',
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
        $this->testObject = $this->reflection->newInstanceArgs($this->dependencies);
    }

    /**
     * testSelect Test that select executes as expected.
     */
    public function testSelect()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->select();

        // Assert Result
        self::assert();
    }

    /**
     * testGetSingle Test that getSingle executes as expected.
     */
    public function testGetSingle()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->getSingle();

        // Assert Result
        self::assert();
    }

    /**
     * testGetColumn Test that getColumn executes as expected.
     */
    public function testGetColumn()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->getColumn();

        // Assert Result
        self::assert();
    }

    /**
     * testInsert Test that insert executes as expected.
     */
    public function testInsert()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->insert();

        // Assert Result
        self::assert();
    }

    /**
     * testUpdate Test that update executes as expected.
     */
    public function testUpdate()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->update();

        // Assert Result
        self::assert();
    }

    /**
     * testDelete Test that delete executes as expected.
     */
    public function testDelete()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->delete();

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
     * testGetValue Test that getValue executes as expected.
     */
    public function testGetValue()
    {
        $this->markTestIncomplete('This test has not been implemented yet.');
        // Prepare / Mock
        //nmock

        // Execute
        $result = $this->testObject->getValue();

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
}
