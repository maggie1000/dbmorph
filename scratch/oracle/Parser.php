<?php
/**
 * Singleton Class able to retrieve a valid update instance to be parsed via
 * Factory Method.
 *
 * Uses a factory method @see MyApp_Database_Parser::fetchUpdate()
 * in order to obtain a update from a  update provider and a engine
 * specific parser (e.g: Oracle)
 */
class MyApp_Database_Parser
{
    /**
     * Singleton instance
     *
     * @var MyApp_Database_Parser
     */
    private static $_instance = null;

    /**
     * Update provier service interface.
     *
     * @var MyApp_Database_Parser_Provider_Interface
     */
    private $_service;

    /**
     * Constructor
     *
     * Instantiate using {@see MyApp_Database_Parser::getInstance()};
     * Update Parser is a singleton object.
     */
    private function __construct() {}

    /**
     * Gets an instance of the singleton object.
     *
     * @return MyApp_Database_Parser
     */
    public static function getInstance()
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    /**
     * Sets the update provider for the parser. The update provider
     * is used to retrieve the update from a storage using the provider
     * protocol (e.g: SimpleXML: xml file, HTTP, WebService).
     *
     * @param string $provider Valid and supported provider name.
     * @return void
     */
    public function setUpdateProvider($provider = 'SimpleXML')
    {
        // validate and create an instance of the provider.
        $className = "MyApp_Database_Parser_Provider_$provider";
        if (!class_exists($className, true)) {
            throw new MyApp_Database_Parser_Exception(
                '[Parser] Unsupported update provider: ' . $provider);
        }

        $this->_service = new $className;
    }

    /**
     * Connects to the service provider.
     *
     *
     * @param string $uri Base URI where the updates resides, e.g: The SimpleXML
     * service uses local xml files, the URI in this case will be the dir where
     * the xml files are stored.
     *
     * @param string $options Optional, service options.
     * @return boolean True if successfully connected.
     */
    public function providerConnect($uri, $options = null)
    {
        if (!$this->_service) {
            throw new MyApp_Database_Parser_Exception(
                '[Parser] A service has not been established');
        }

        return $this->_service->connect($uri);
    }

    /**
     * Disconnects from the service provider.
     *
     * @return boolean True if successfully disconnected.
     *
     */
    public function providerDisconnect()
    {
        if (!$this->_service) {
            throw new MyApp_Database_Parser_Exception(
                '[Parser] A service has not been established');
        }

        return $this->_service->disconnect();
    }

    /**
     * Retrieves a parsed update from the update provider using
     * a specific parser engine, e.g: Oracle.
     *
     * see Readme.txt for a list of supported providers as engines.
     *
     * @param string $version Version of the update
     * @param string $engine Parser Engine.
     * @param string $sqlDefinitionDir Some Services requires local sql resources
     *                                 to be present, this param will be passed along
     *                                 with the engine.
     * @return MyApp_Database_Parser_Abstract
     */
    public function fetchUpdate($version, $engine = 'Oracle', $sqlDefinitionDir = null)
    {
        if (!$this->_service) {
            throw new MyApp_Database_Parser_Exception(
                '[Parser] A service has not been established');
        }

        // validates and creatse an instance of the engine.
        $engineName = "MyApp_Database_Parser_" . ucwords($engine);
        if (!class_exists($engineName, true)) {
            throw new MyApp_Database_Parser_Exception(
                "[Parser] Unsupported parser engine ($engine)");
        }

        // build the engine and return the update container
        $engineClass = new $engineName($this->_service->getUpdate($version));
        $engineClass->setSqlDefinitionDir($sqlDefinitionDir);
        return $engineClass->getUpdateContainer();
    }

    /**
     * Verifies if an update is available.
     *
     * @param string $version Version of the update
     * @return boolean True if the update is available.
     */
    public function isUpdateAvailable($version)
    {
        if (!$this->_service) {
            throw new MyApp_Database_Parser_Exception(
                '[Parser] A service has not been established');
        }

        return $this->_service->isUpdateAvailable($version);
    }

    /**
     * Gets the current connection Uri (last registered).
     *
     * @return string
     */
    public function getConnectionUri()
    {
        if (!$this->_service) {
            throw new MyApp_Database_Parser_Exception(
                '[Parser] A service has not been established');
        }

        return $this->_service->getConnectionUri();
    }

    /**
     * Gets the current connection options (last registered).
     *
     * @return array
     */
    public function getConnectionOptions()
    {
        if (!$this->_service) {
            throw new MyApp_Database_Parser_Exception(
                '[Parser] A service has not been established');
        }

        return $this->_service->getConnectionOptions();
    }
}
