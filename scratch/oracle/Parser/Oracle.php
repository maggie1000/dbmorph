<?php
/**
 * File containing the DB Update Parser Oracle Engine Class
 */
class MyApp_Database_Parser_Oracle
    extends MyApp_Database_Parser_Abstract
{
    /**
     * string documenter, the data base do not interpretates this string
     *
     * @var string
     */
    private $_docString = '--';

    /**
     * Update elements separator, the data base do not interpretates this string
     *
     * @var string
     */
    private $_endElementSeparator = "\n";

   /**
     * Returns an string with all the data needed to install the update in
     * a OCI database.
     *
     * @return string Update install data
     */
    public function parseInstallUpdate()
    {
        parent::parseInstallUpdate();
        $data = null;
        $data .=
            $this->_docString . 'Intalling Update Version: '
            . $this->_updateContainer->getVersion() . $this->_endElementSeparator;
        $data .=
            $this->_updateContainer->getUpStatement() . $this->_endElementSeparator;
        $data .=
            $this->_updateContainer->getJob()->getUpStatement()
            . $this->_endElementSeparator;
        $data .=
            $this->_docString . 'Packages to install: ' .
            $this->_updateContainer->getCountPackages() . $this->_endElementSeparator;

        $this->_updateContainer->resetPackages();

        $a = 0;
        while (($package = $this->_updateContainer->getPackage()))
        {
            $a++;
            $data .= $this->_docString . "Package #$a $this->_endElementSeparator";
            $data .= $package->getDefinition() . $this->_endElementSeparator;
            $data .= $package->getBody() . $this->_endElementSeparator;
        }

        return $data;
    }

    /**
     * Returns an string with all the data needed to uninstall the update.
     *
     * @return string Update uninstall data
     */
    public function parseUninstallUpdate()
    {
        parent::parseUninstallUpdate();

        $data = null;
        $data .=
            $this->_docString . 'Uninstalling Update Version: '
            . $this->_updateContainer->getVersion() . $this->_endElementSeparator;
        $data .=
            $this->_updateContainer->getDownStatement() . $this->_endElementSeparator;
        $data .=
            $this->_updateContainer->getJob()->getDownStatement() .
            $this->_endElementSeparator;

        return $data;
    }

    /**
     * Validates the update
     *
     * @todo add some awesome regexp
     * @return boolean True if the update structure is valid
     */
    public function validate($throwExceptions = true)
    {
        // checks if the version is given and is numeric!
        $version = $this->_updateContainer->getVersion();
        if (empty($version) ||
            !is_numeric($version))
        {
            if ($throwExceptions) {
                throw new MyApp_Database_Parser_Exception(
                    __METHOD__ . ': Invalid Revision Number');
            }
            return false;
        }

        //validates the packages
        $this->_updateContainer->resetPackages();
        if ($this->_updateContainer->hasPackages()) {
            while (($package = $this->_updateContainer->getPackage()))
            {
                if (!$package->getDefinition()) {
                    if ($throwExceptions) {
                        throw new MyApp_Database_Parser_Exception(
                            __METHOD__ . ': Invalid Package Definition');
                    }

                    return false;
                }

                if (!$package->getBody()) {
                    if ($throwExceptions) {
                        throw new MyApp_Database_Parser_Exception(
                            __METHOD__ . ': Invalid Package Body');
                    }
                    return false;
                }
            }

            // set the pointer at the beggining of the array
            $this->_updateContainer->resetPackages();
        }
        return true;
    }
}
