<?php
/**
 * File containing the DB Parser Update Provider for XmlSvn
 */
class MyApp_Database_Updater_Parser_Provider_XmlSvn
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
            throw new MyApp_Database_Updater_Parser_Provider_Exception(
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
     * @param string $version Version to obtain.
     * @return MyApp_Database_Updater_Parser_Update_XmlSvn
     */
    public function getUpdate($version)
    {
        if (!$this->isUpdateAvailable($version)) {
            throw new MyApp_Database_Updater_Parser_Provider_Exception(
                __METHOD__ . ': No update available to fetch');
        }

        // finds the correct svn version of the update file by executing a svn command
        // retrieving the output as xml
        $updateFileXml =
            new SimpleXMLElement(
                MyApp_Database_Updater_Parser_Provider_XmlSvn::executeSvnCommand(
                    "-r PREV:HEAD log --xml " . $this->_resourceDir . "/$version.xml"));

        if (empty($updateFileXml->logentry)) {
           throw new MyApp_Database_Updater_Parser_Provider_Exception(
                __METHOD__ . ': Error while retriving svn version. (Not Found)');
        }

        // gets the svn revision and appends it to the update Container
        $updateSvnRevision = $updateFileXml->logentry[0]['revision'];
        $updateContainer =
            simplexml_load_file(
                $this->_resourceDir . "/$version.xml",
                'MyApp_Database_Updater_Parser_Update_Container_Xml',
                LIBXML_DTDLOAD + LIBXML_DTDVALID + LIBXML_NOCDATA);
        if ($updateContainer) {
            $updateContainer->addChild('svnRevision', $updateSvnRevision);
        } else {
            $libXmlError = libxml_get_last_error();
            if ($libXmlError) {
                $libXmlErrmsg = $libXmlError->message;
            }
            throw new MyApp_Database_Updater_Parser_Provider_Exception(
                __METHOD__ . ": Error while parsing $version.xml file [LibXml: " .
                "$libXmlErrmsg]");
        }

        return New MyApp_Database_Updater_Parser_Update_XmlSvn($updateContainer);

    }

    /**
     * Disconnects the service.
     *
     * @return void
     */
    public function disconnect()
    {
        if (!$this->_connected) {
            throw new MyApp_Database_Updater_Parser_Provider_Exception(
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
            throw new MyApp_Database_Updater_Parser_Provider_Exception(
                __METHOD__ . ': Resource disconnected');
        }

        return file_exists($this->_resourceDir . "/$version.xml");
    }

    /**
     * Helper static method which wraps the logic of a svn command execution
     * and error detection.
     *
     * @param string $svnCommand Svn command to be executed.
     * @return string Output of the command executed.
     * @throws MyApp_Database_Updater_Parser_Provider_Exception
     */
    public static function executeSvnCommand($svnCommand)
    {
    	$svnBinPath = '/usr/bin/svn';
        $returnCode = 0;
        $commandOutput = null;

        // check for the existance of svn and able to execute it
        if (!is_executable($svnBinPath)) {
            throw new MyApp_Database_Updater_Parser_Provider_Exception(
                __METHOD__ . "Error executing SVN, program not found.");
        }

        // exec a svn shell command and grab STDERR and STDOUT into php
        exec("svn $svnCommand 2>&1", $commandOutput, $returnCode);
        $commandOutput = implode("\n", $commandOutput);

        // if returnCode <> 0 we got an error from svn
        if ($returnCode !== 0) {
            throw new MyApp_Database_Updater_Parser_Provider_Exception(
                __METHOD__ . "[#$returnCode]: Error while executing a svn command" .
                "($svnCommand => $commandOutput)");
        }

        return $commandOutput;
    }
}
