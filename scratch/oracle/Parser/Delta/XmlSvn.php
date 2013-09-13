<?php
/**
 * File containing the DB Parser's XmlSvn Update Handler
 */
class MyApp_Database_Parser_Update_XmlSvn
    extends MyApp_Database_Parser_Update_Abstract
{
    /**
     * Svn Version of the main XML file.
     *
     * @var string
     */
    private $_svnRevision;

    /**
     * Object constructor.
     *
     * @param mixed $updateData
     * @return void
     */
    public function __construct($updateData)
    {
        // loads all then properties into the object so the getters methods
        // can access them.
        $this->_attributes = $updateData->attributes();
        $this->_svnRevision = (int) $updateData->svnRevision;
        $this->_packages = $updateData->xpath('/update/packages/package');
        $this->_jobs = $updateData->jobs;
        $this->_version = trim((string) $updateData->version);
        $this->_summary = trim((string) $updateData->summary);
        $this->_schema = trim((string) $updateData->schema);
        $this->_upStatement = trim((string) $updateData->up);
        $this->_downStatement = trim((string) $updateData->down);
    }

    /**
     * Returns a data base update package.
     *
     * Uses an internal pointer to walk thru the update pacakages, whenever
     * this method is call, current package will return and the internal pointer
     * will be moved to the next package.
     * {@link resetPackages()}; resets the internal pointer to the first package.
     * If no more packages were found, this method returns null.
     *
     * @return Db_Update_Parser_Package Db_Update_Parser_Package or null if no
     *                                  no more packages were found.
     */
    public function getPackage()
    {
        $current = null;
        // if internal pointer points to a valid package, return it and advance
        // the pointer
        if ($this->hasPackages()) {
            if (current($this->_packages)) {
                $current =
                    new MyApp_Database_Parser_Update_Element_Package_XmlSvn(
                        current($this->_packages), $this->getSvnRevision(), $this->_sqlDefinitionDir);
                next($this->_packages);
            }
        }

        return $current;
    }

    /**
     * Returns the job for the update.
     *
     * @return MyApp_Database_Parser_Update_Element_Job_Interface.
     */
    public function getJob()
    {
       return
        new MyApp_Database_Parser_Update_Element_Job_XmlSvn(
            $this->_jobs,
            $this->getSvnRevision());
    }

    /**
     * Returns the main SVN Revision number of the update package.
     *
     * Generally, the main SVN Revision number is the rev of the XML update
     * file.
     *
     * @return int
     */
    public function getSvnRevision()
    {
        return $this->_svnRevision;
    }
}
