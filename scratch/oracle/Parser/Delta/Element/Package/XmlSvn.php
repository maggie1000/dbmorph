<?php
/**
 * File containing the DB Parser's Element Package Xml
 */
class MyApp_Database_Parser_Update_Element_Package_XmlSvn
{
    /**
     * SimpleXMLElement object of the Job
     *
     * @var SimpleXMLElement
     */
    private $_xmlContainer;

    /**
     * Version of the package, matches the version of the update.
     *
     * @var string
     */
    private $_version;

    /**
     * parsed definition string after being queried to svn.
     *
     * @var string
     */
    private $_svnParsedDefinition;

    /**
     * parsed body string after being queried to svn.
     *
     * @var string
     */
    private $_svnParsedBody;

    /**
     * defines where the sql definition dir of the database is at
     *
     * @var string
     */
    private $_sqlDefinitionDir;

    /**
     * Package Definition SVN revision number.
     *
     * @var integer
     */
    private $_definitionRevisionNumber;

    /**
     * path to the local or external resource where the definition of the package is
     * at.
     *
     * @var string
     */
    private $_definitionResourcePath;

    /**
     * Package Body SVN revision number.
     *
     * @var integer
     */
    private $_bodyRevisionNumber;

    /**
     * path to the local or external resource where the body of the package is
     * defined.
     *
     * @var string
     */
    private $_bodyResourcePath;

    /**
     * Object Constructor
     *
     * @param SimpleXMLElement $xmlContainer structure container of the package
     * @param string $version Version of the package, matches the update version
     * @param string $sqlDefinitionDir where the sql definition dir of the db
     *                                 is at.
     */
    public function __construct(SimpleXMLElement $xmlContainer, $version, $sqlDefinitionDir = null)
    {
        $this->_xmlContainer = $xmlContainer;
        $this->_version = $version;
        $this->_sqlDefinitionDir = $sqlDefinitionDir;
    }

    /**
     * Returns the package definition.
     *
     * @return string
     */
    public function getDefinition()
    {
        if (is_null($this->_svnParsedDefinition)) {
            $this->_svnParsedDefinition =
                trim(
                    MyApp_Database_Parser_Provider_XmlSvn::executeSvnCommand(
                        'cat -r'. $this->_version. ' ' .
                        $this->_sqlDefinitionDir . '/' .
                        $this->_xmlContainer->pkg));
        }

        return $this->_svnParsedDefinition;
    }

    /**
     * Returns the package body.
     *
     * @return string
     */
    public function getBody()
    {
         if (is_null($this->_svnParsedBody)) {
             $this->_svnParsedBody =
                trim(
                    MyApp_Database_Parser_Provider_XmlSvn::executeSvnCommand(
                        'cat -r'. $this->_version. ' ' .
                        $this->_sqlDefinitionDir . '/' .
                        $this->_xmlContainer->pkg_body));
        }

        return $this->_svnParsedBody;
    }

    /**
     * Returns the package definition revision number.
     *
     * @return integer
     */
    public function getDefinitionRevisionNumber()
    {
        if (empty($this->_definitionRevisionNumber)) {
            // finds the correct svn version
            $xmlLog =
                new SimpleXMLElement(
                    MyApp_Database_Parser_Provider_XmlSvn::executeSvnCommand(
                        "-r PREV:{$this->_version} log --xml {$this->getDefinitionResourcePath()}"));

            if (empty($xmlLog->logentry)) {
               throw new MyApp_Database_Parser_Update_Exception(
                    __METHOD__ . ': Error while retriving svn version for ' .
                    "{$this->getDefinitionResourcePath()} (Not Found)");
            }

            // gets the svn revision and appends it to the update Container
            $this->_definitionRevisionNumber = (int) $xmlLog->logentry[0]['revision'];
        }

        return $this->_definitionRevisionNumber;
    }

    /**
     * Returns the package definition resource path.
     *
     * @return string
     */
    public function getDefinitionResourcePath()
    {
        return "{$this->_sqlDefinitionDir}/{$this->_xmlContainer->pkg}";
    }

    /**
     * Returns the package body revision number.
     *
     * @return integer
     */
    public function getBodyRevisionNumber()
    {
        if (empty($this->_bodyRevisionNumber)) {
            // finds the correct svn version
            $xmlLog =
                new SimpleXMLElement(
                    MyApp_Database_Parser_Provider_XmlSvn::executeSvnCommand(
                        "-r PREV:{$this->_version} log --xml {$this->getBodyResourcePath()}"));

            if (empty($xmlLog->logentry)) {
               throw new MyApp_Database_Parser_Update_Exception(
                    __METHOD__ . ': Error while retriving svn version for ' .
                    "{$this->getBodyResourcePath()} (Not Found)");
            }

            // gets the svn revision and appends it to the update Container
            $this->_bodyRevisionNumber = (int) $xmlLog->logentry[0]['revision'];
        }

        return $this->_bodyRevisionNumber;
    }

    /**
     * Returns the package body resource path.
     *
     * @return string
     */
    public function getBodyResourcePath()
    {
        return "{$this->_sqlDefinitionDir}/{$this->_xmlContainer->pkg_body}";
    }
}
