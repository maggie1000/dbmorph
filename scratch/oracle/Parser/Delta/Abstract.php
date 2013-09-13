<?php
/**
 * File containing the DB Updater Parser's Abstract Update Handler
 */

/**
 * Abstract class of DB Parser's Update Handler.
 */
abstract class MyApp_Database_Parser_Update_Abstract
{

    /**
     * Array containing the General Updates Attributes, e.g: version number
     *
     * @var array
     */
    protected $_attributes = array();

    /**
     * array Of Package elements.
     *
     * @var array Of MyApp_Database_Parser_Update_Element_Package_Interface
     */
    protected $_packages = array();

    /**
     * Job element
     *
     * @var MyApp_Database_Parser_Update_Element_Job_Interface
     */
    protected $_jobs = null;

    /**
     * Contains the Up Statement of the update.
     *
     * @var mixed
     */
    protected $_upStatement;

    /**
     * Contains the Down statement of the updated.
     *
     * @var mixed
     */
    protected $_downStatement;

    /**
     * Contains the name of the DB schema where the update is going to be run
     *
     * @var mixed
     */
    protected $_schema;

    /**
     * Contains the version number of the update (obtained from the update Container)
     *
     * @var interger
     */
    protected $_version;

    /**
     * Path to the sql definition directory (packages, jobs, creates sql files).
     *
     * By default, the root dir is set ('/');
     *
     * @var string
     */
    protected $_sqlDefinitionDir = '/';

    /**
     * Contains summary of the update.
     *
     * @var string
     */
    protected $_summary;

    /**
     * Returns the version number of the update.
     *
     * @return string
     */
    public function getVersion()
    {
        if (!$this->_version) {
            if (isset($this->_attributes['number'])) {
                $this->_version = $this->_attributes['number'];
            }
        }
        return (string) $this->_version;
    }

    /**
     * Returns summary of the update.
     *
     * @return string
     */
    public function getSummary()
    {
        return (string) $this->_summary;
    }

    /**
     * Returns the database schema to run the update into.
     *
     * @return string
     */
    public function getSchema()
    {
        return (string) $this->_schema;
    }

    /**
     * Returns the Up procedure of the update.
     *
     * @return string
     */
    public function getUpStatement()
    {
        return (string) $this->_upStatement;
    }

    /**
     * Returns the Down procedure of the update.
     *
     * @return string
     */
    public function getDownStatement()
    {
        return (string) $this->_downStatement;
    }

    /**
     * Checks if the current update has packages to be processed.
     *
     * @return boolean
     */
    public function hasPackages()
    {
        return !empty($this->_packages);
    }

    /**
     * Returns how many packages the update has.
     *
     * @return integer
     */
    public function getCountPackages()
    {
        return count($this->_packages);
    }

    /**
     * Resets the internal pointer of packages.
     * Returns the value of the first package element, or FALSE if the there
     * are no packages.
     *
     * @return mixed
     */
    public function resetPackages()
    {
        if ($this->hasPackages()) {
            return reset($this->_packages);
        }
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
        $this->_sqlDefinitionDir = $path;
    }
}
