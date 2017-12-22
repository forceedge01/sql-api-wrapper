# SQL API Wrapper [ ![Codeship Status for forceedge01/sql-api-wrapper](https://app.codeship.com/projects/96302210-ad45-0135-72c6-56f00403434d/status?branch=master)](https://app.codeship.com/projects/257181)

This wrapper provides with powerful tools around the [behat-sql-extension](https://github.com/forceedge01/behat-sql-extension) API class. Methods provided:

- insertSeedDataIfExists() // Auto fires on construct.
- createFixture(array $data = [], string $uniqueColumn = null) // Recreates a record for fresh usage.
- getSingle(array $where) // Returns a single record defined by the mapping.
- getColumn(string $column, array $where) // Returns a single column value from the database.
- getValue(string $key) // Get key value based on mapping.
- truncate() // Truncates a table.
- subSelect(string $table, string $column, array $where) // Provides the ability to sub select a column for any query.
- saveSession(string $primaryKey) // Save the current session for later re-use.
- restoreSession() // Restore the session saved by saveSession.
- getRequiredData(array $data, string $key) // Extended: Extracts value from an array.
- getOptionalData(array $data, string $key, mixed $default = null) // Extended: Optional value from an array, provide default otherwise.
- getFieldMapping(string $key) // Extended: Get field mapping provided in the getDataMapping method.

Note: All methods provided by the wrapper are static, because they have a global state - we don't need to instantiate this wrapper.

## Example usage

Creating a DataMod to use in your context files.

To use this decorator effectively, you will have to make good use of polymorphism. Extend the BaseProvider in your project and implement the abstract method getAPI(). This method needs to return an object that implements Genesis\SQLExtension\Context\Interfaces\APIInterface.

```php
# BaseDataMod.php
<?php

use Genesis\SQLExtensionWrapper\BaseProvider;
use Genesis\SQLExtension\Context;

/**
 * Serves as a base class for your own project, makes refactoring easier if you decide to inject your own version of 
 * the API.
 */
abstract class BaseDataMod extends BaseProvider
{
    /**
     * @var array The connection details the API expects.
     */
    public static $connectionDetails;

    /**
     * @var Context\Interfaces\APIInterface
     */
    private static $sqlApi;

    /**
     * @return Context\Interfaces\APIInterface
     */
    public static function getAPI()
    {
        if (! self::$sqlApi) {
            self::$sqlApi = new Context\API(
                new Context\DBManager(self::$connectionDetails),
                new Context\SQLBuilder(),
                new Context\LocalKeyStore(),
                new Context\SQLHistory()
            );
        }

        return self::$sqlApi;
    }
}
```

Then further extend your class to use with your data component classes.

```php
# UserDataMod.php
<?php

class UserDataMod extends BaseDataMod
{
    /**
     * Returns the base table to interact with.
     *
     * @return string
     */
    public static function getBaseTable()
    {
        return 'User';
    }

    /**
     * Returns the data mapping for the base table.
     *
     * @return array
     */
    public static function getDataMapping()
    {
        return [
            'id' => 'user_id',
            'name' => 'full_name',
            'dateOfBirth' => 'dob',
            'gender' => 'gender',
            'status' => 'status'
        ];
    }

    /**
     * Method uses subSelect to intelligently select the Id of the status and updates the user record.
     * This is a common case where you want your feature files to be descriptive and won't just pass in id's, use
     * descriptive names instead and infer values in the lower layers.
     *
     * @param string $status The status name (enabled/disabled).
     * @param int $userId The user to update.
     *
     * @return void
     */
    public static function updateStatusById($status, $userId)
    {
        self::update(self::getBaseTable(), [
            'status' => self::subSelect('Status', 'id', ['name' => $status])
        ], [
            'id' => $userId
        ])
    }
}

```

Using your UserDataMod in your context file.

```php
# FeatureContext.php
<?php

use Exception;

/**
 * Ideally you would want to separate the data step definitions from interactive/assertive step definitions.
 * This is for demonstration only.
 */
class FeatureContext
{
    /**
     * @Given I have a User
     *
     * Use the API to create a fixture user.
     */
    public function createUser()
    {
        // This will create a fixture user.
        // The name will be set to 'Wahab Qureshi'. The rest of the fields if required by the database will be autofilled
        // with fixture data, if they are nullable, null will be stored.
        // If the record exists already, it will be deleted based on the 'name' key provided.
        UserDataMod::createFixture([
            'name' => 'Wahab Qureshi'
        ], 'name');
    }

    /**
     * @Given I have (number) User(s)
     *
     * Use the API to create random 10 users.
     */
    public function create10Users($count)
    {
        // Save this user's session.
        UserDataMod::saveSession('id');

        // Create 10 random users.
        for($i = 0; $i < 10; $i++) {
            // Store the ids created for these users maybe?
            $this->userIds[] = UserDataMod::createFixture();
        }

        // Restore session of the user we created above.
        UserDataMod::restoreSession();
    }

    /**
     * @Given I should see a User
     *
     * Use the API to retrieve the user created.
     */
    public function assertUserOnPage()
    {
        // Assumptions - we ran the following before running this command:
        // Given I have a User
        // And I have 10 Users

        // Retrieve data created, this will reference the user created by 'Given I have a User' as the session was preserved.
        $id = UserDataMod::getValue('id');
        $name = UserDataMod::getValue('name');
        $dateOfBirth = UserDataMod::getValue('dateOfBirth');
        $gender = UserDataMod::getValue('gender');

        // Assert that data is on the page.
        $this->assertSession()->assertTextOnPage($id);
        $this->assertSession()->assertTextOnPage($name);
        $this->assertSession()->assertTextOnPage($dateOfBirth);
        $this->assertSession()->assertTextOnPage($gender);
    }

    /**
     * @Given I should see (number) User(s) in the list
     *
     * Consumption of the users created above. For illustration purposes only.
     */
    public function assertUserOnPage($number)
    {
        $usersList = $this->getSession()->getPage()->find('css', '#usersListContainer li');
        $actualCount = count($usersList);

        if ($number !== $actualCount) {
            throw new Exception("Expected to have '$number' users, got '$actualCount'");
        }
    }
}
```

## Development

To get started with development of this project:

### Deployer https://github.com/forceedge01/deployer

When in the root of the project run
```
dep use
```

Then run
```
dep project:dev
```

The above will init and download the vagrant box as the submodule, get the box running, and perform a composer install within.

Running unit tests:

```
dep project:test
```

This will run the unit tests within the vagrant box.
