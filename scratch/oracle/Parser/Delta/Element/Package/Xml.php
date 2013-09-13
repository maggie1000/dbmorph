<?php
/**
 * File containing the DB Parser's Element Package Xml
 */
class MyApp_Database_Parser_Update_Element_Package_Xml
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
     * parsed definition string after being queried.
     *
     * @var string
     */
    private $_parsedDefinition;

    /**
     * parsed body string after being queried to svn.
     *
     * @var string
     */
    private $_parsedBody;

    /**
     * defines where the sql definition dir of the database is at
     *
     * @var string
     */
    private $_sqlDefinitionDir;

    /**
     * path to the local or external resource where the definition of the package is
     * at.
     *
     * @var sting
     */
    private $_definitionResourcePath;

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
     * @param SimpleXMLElement $xmlContainer
     * @param string $version Version of the package, matches the update version
     * @param string $sqlDefinitionDir where the sql definition dir of the db
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
        if (is_null($this->_parsedDefinition)) {
            $this->_parsedDefinition =
                trim(
                    file_get_contents(
                        $this->_sqlDefinitionDir . '/' .
                        $this->_xmlContainer->pkg));
        }

        return $this->_parsedDefinition;
    }

    /**
     * Returns the package body.
     *
     * @return string
     */
    public function getBody()
    {
        if (is_null($this->_parsedBody)) {
             $this->_parsedBody =
                trim(
                   file_get_contents(
                        $this->_sqlDefinitionDir . '/' .
                        $this->_xmlContainer->pkg_body));
        }

        return $this->_parsedBody;
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
     * Returns the package body resource path.
     *
     * @return string
     */
    public function getBodyResourcePath()
    {
        return "{$this->_sqlDefinitionDir}/{$this->_xmlContainer->pkg_body}";
    }

    /**
     * Returns the package body revision number.
     *
     * @return integer
     */
    public function getBodyRevisionNumber()
    {
        $this->_version;
    }

    /**
     * Returns the package definition revision number.
     *
     * @return integer
     */
    public function getDefinitionRevisionNumber()
    {
        $this->_version;
    }
}
