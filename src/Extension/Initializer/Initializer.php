<?php

namespace Genesis\SQLExtensionWrapper\Extension\Initializer;

use Behat\Behat\Context\Context;
use Behat\Behat\Context\Initializer\ContextInitializer;
use Genesis\SQLExtensionWrapper\BaseProvider;
use Genesis\SQLExtensionWrapper\DataModSQLContext;

/**
 * ContextInitialiser class.
 */
class Initializer implements ContextInitializer
{
    /**
     * @var array
     */
    private $connection = [];

    /**
     * @var array
     */
    private $dataModMapping = [];

    /**
     * @param array $connection
     * @param array $dataModMapping
     */
    public function __construct(
        array $connection = [],
        array $dataModMapping = []
    ) {
        $this->connection = $connection;
        $this->dataModMapping = $dataModMapping;
    }

    /**
     * @param Context $context
     */
    public function initializeContext(Context $context)
    {
        if ($context instanceof DataModSQLContext) {
            BaseProvider::setCredentials([
                'engine' => $this->getConfig('engine'),
                'name' => $this->getConfig('dbname'),
                'schema' => $this->getConfig('schema'),
                'prefix' => $this->getConfig('prefix'),
                'host' => $this->getConfig('host'),
                'port' => $this->getConfig('port'),
                'username' => $this->getConfig('username'),
                'password' => $this->getConfig('password')
            ]);

            $context::setDataModMapping($this->dataModMapping);
        }
    }

    private function getConfig($key)
    {
        if (isset($this->connection[$key])) {
            return $this->connection[$key];
        }
    }
}
