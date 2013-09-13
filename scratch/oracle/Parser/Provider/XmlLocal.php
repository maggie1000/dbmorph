<?php
/**
 * File containing the DB Updater Parser Update Provider for Xml Local
 */
class MyApp_Database_Parser_Provider_XmlLocal
{
    /**
     * Resource directory path (where the update files are).
     *
     * @var string
     */
    private $_resourceDir;

    /**
     * Flag to know when the service is connected or not.
     *
     * @var boolean
     */
    private $_connected = false;

    /**
     * Service options.
     *
     * @var array Of Options
     */
    private $_options;

    /**
     * Connects to the local XML Svn resource directory.
     *
     * @param string $uri URI where the resources are located (update files)
     * @param array $options Array of options for the service (Credentials, etc)
     * @return boolean True if successfully connected to the resource.
     */
    public function connect($resourceDir, $options = null)
    {
        if(!file_exists($resourceDir)) {
            throw new MyApp_Database_Parser_Provider_Exception(
                __METHOD__ . ' could not locate resource dir: '. $resourceDir);
        }

        $this->_resourceDir = $resourceDir;
        $this->_connected = true;
        $this->_options = $options;

        return true;
    }

    /**
     * Gets the current connection Uri (last registered).
     *
     * @return string
     */
    public function getConnectionUri()
    {
        return $this->_resourceDir;
    }

    /**
     * Gets the current connection options (last registered).
     *
     * @return array
     */
    public function getConnectionOptions()
    {
        return $this->_options;
    }

    /**
     * Obtains the Update Object container.
     *
     * @todo SvnVersion should not be a global property for the update container.
     * @param string $version Version to obtain.
     * @return MyApp_Database_Parser_Update_Xml
     */
    public function getUpdate($version)
    {
        if (!$this->isUpdateAvailable($version)) {
            throw new MyApp_Database_Parser_Provider_Exception(
                __METHOD__ . ': No update available to fetch');
        }

        $updateContainer =
            simplexml_load_file(
                $this->_resourceDir . "/$version.xml",
                'MyApp_Database_Parser_Update_Container_Xml',
                LIBXML_DTDLOAD + LIBXML_DTDVALID + LIBXML_NOCDATA);
        if (!$updateContainer) {
            $libXmlError = libxml_get_last_error();
            if ($libXmlError) {
                $libXmlErrmsg = $libXmlError->message;
            }
            throw new MyApp_Database_Parser_Provider_Exception(
                __METHOD__ . ": Error while parsing $version.xml file [LibXml: " .
                "$libXmlErrmsg]");
        }

        return New MyApp_Database_Parser_Update_Xml($updateContainer);

    }

    /**
     * Disconnects the service.
     *
     * @return void
     */
    public function disconnect()
    {
        if (!$this->_connected) {
            throw new MyApp_Database_Parser_Provider_Exception(
                __METHOD__ . ': Resource already disconnected');
        }

        $this->_connected = false;
    }

    /**
     * Checks wheter or not an update is available.
     *
     * @param string $version
     * @return boolean True if the update is available.
     */
    public function isUpdateAvailable($version)
    {
        if (!$this->_connected) {
            throw new MyApp_Database_Parser_Provider_Exception(
                __METHOD__ . ': Resource disconnected');
        }

        return file_exists($this->_resourceDir . "/$version.xml");
    }
}
