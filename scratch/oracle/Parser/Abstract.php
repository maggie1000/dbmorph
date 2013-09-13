<?php
/**
 * Abstract Parser class ment to be extended by the parser engines.
 */
abstract class MyApp_Database_Parser_Abstract
{
    /**
     * Container of the update
     *
     * @var MyApp_Database_Parser_Update_Abstract
     */
    protected $_updateContainer;

    /**
     * Object Constructor.
     *
     *
     */
    final public function __construct(MyApp_Database_Parser_Update_Interface $update)
    {
        if (empty($update))
        {
            throw new MyApp_Database_Parser_Exception(
                __METHOD__ . ': Invalid update received (empty)');
        }

        $this->_updateContainer = $update;

    }

    /**
     * Returns an string with all the data needed to uninstall the update.
     *
     * @return string
     */
    public function parseUninstallUpdate()
    {
        if (!$this->validate()) {
            throw new MyApp_Database_Parser_Exception(
                __METHOD__ . ': Update Container Validation Failed');
        }
    }

    /**
     * Returns an string with all the data needed to install the update.
     *
     * @return string
     */
    public function parseInstallUpdate()
    {
        if (!$this->validate()) {
            throw new MyApp_Database_Parser_Exception(
                __METHOD__ . ': Update Container Validation Failed');
        }
    }

    /**
     * Retrieves the update container
     *
     * @return MyApp_Database_Parser_Update_Abstract
     */
    public function getUpdateContainer()
    {
        if (!$this->validate()) {
            throw new MyApp_Database_Parser_Exception(
                __METHOD__ . ': Update Container Validation Failed');
        }

        return $this->_updateContainer;
    }

    /**
     * Object destructor.
     *
     */
    public function __destruct()
    {
        $this->updateContainer = null;
    }

    /**
     * Sets the path to the Sql Definition base directory, where the Packages,
     * Jobs, etc sql files exists. No trailing slashes.
     *
     * @param string $path
     * @return void
     */
    public function setSqlDefinitionDir($path)
    {
        $this->_updateContainer->setSqlDefinitionDir($path);
    }
}
