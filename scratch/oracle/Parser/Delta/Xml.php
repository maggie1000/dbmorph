<?php
/**
 * File containing the DB Updater Parser's XmlSvn Update Handler
 */
class MyApp_Database_Parser_Update_Xml
    extends MyApp_Database_Parser_Update_Abstract
{

    /**
     * Object constructor.
     *
     * @param unknown_type $updateData
     * @return void
     */
    public function __construct($updateData)
    {
        // loads all then properties into the object so the getters methods
        // can access them.
        $this->_attributes = $updateData->attributes();
        $this->_packages = $updateData->xpath('/update/packages/package');
        $this->_jobs = $updateData->jobs;
        $this->_version = trim((string) $updateData->version);
        $this->_schema = trim((string) $updateData->schema);
        $this->_summary = trim((string) $updateData->summary);
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
                    new MyApp_Database_Parser_Update_Element_Package_Xml(
                        current($this->_packages), $this->getVersion(), $this->_sqlDefinitionDir);
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
        new MyApp_Database_Parser_Update_Element_Job_Xml(
            $this->_jobs,
            $this->getVersion());
    }
}
