<?php
/**
 * Database Manager class.
*
 * This script is meant to be run after any 'svn update'.  It should run cleanly
 * regardless of when it is run.  If you see any errors, please notify
 * omg_db_broke@[whatever] immediately.
 *
 * How it works:
 *
 * This script generates a .sql file which contains all DDL and DML statements,
 * stored procedures code and code to install or uninstall jobs which are required
 * to change the database version from X to Y.
 *
 * The database version is stored in a table specific to each schema called:
 * SCHEMA_VERSION.  If this table does not exist, the script will assume the
 * database version is 0.
 *
 * Database changes are stored in .xml files.  This script depends on svn to
 * accurately keep track of when the .xml files were committed.  In practice,
 * this means that the .xml files should be committed AFTER the stored procedures
 * are as the svn version of the stored procedures that this script will use
 * is dependent on the svn version of the .xml files.
 *
 * Pseudocode:
 *
 * <code>
 * foreach (current_revision to target_revision as revision_number) {
 *     Append to output file {
 *         Retrieve the up/down statement from the [revision_number].xml file.
 *         Obtain the svn revision of the [revision_number].xml file.
 *         foreach (stored procedure file referenced in [revision_number].xml) {
 *             Obtain the most recent revision of the referenced file
 *             that is <= the svn revision of [revision_number].xml file.
 *         }
 *         Retrieve the up/down jobs statement from the [revision_number].xml file.
 *     }
 * }
 * </code>
 *
 * Save the output file and give instructions to user how to run it.
 *
 * The script obtains the code contained in the svn revisions of files which
 * correspond to the .svn revision of the .xml file.  It stores all this code
 * in the order it needs to be run in a file that is generated as this script
 * runs.  When the script is finished running, the user is given instructions
 * how to run the script on their dev environment.
 *
 * Usage: database_manager.php (or run.php)
 * + -c <config_file> (e.g. myapp.ini)
 * + -s <section> (section in the config file)
 * + -v <version number> (if not specified, the script will update to max
 *                       available version)
 * + -f <force current version number> (this is the 'from' version of the
 *                                     database. If not specified, the script
 *                                     will do a database query to check the
 *                                     current version - this is preferred)
 * + -b <branch number> (specifies the branch number or 'trunk'. Default is 'trunk')
 * + -p <update provider> (The update provider is the method the parser will
 *                        use to retrieve the update. Options available are: <br />
 *                        XmlSvn: Gets the packages from subversion <br />
 *                        XmlLocal: Gets the packages from the local file
 *                                  system. WARNING: should only be used only
 *                                  when an update about to be released.
 *
 * Quick note about output buffering: if output buffering is set on (which is
 * what it is on our dev environment), all the script messages will be printed
 * out at the end of the script execution.  This is not what we want since the
 * script requires user interaction to continue with individual updates.  To turn
 * it off, you may have to run the php command with the following options:
 *
 * -d output_buffering=off -d output_handler=
 *
 * (Yes, there is a blank space after outputhandler=, it is not a typo.)
 *
 * Sample usage:
 * $PHP $PHP_ARGS -d output_buffering=off -d output_handler= util/database_manager.php -c $MYAPP_BASE/conf/myapp.ini -s username
 */
class MyApp_Database_Manager
{
    /**
     * Used for a basic process file locking mechanism, being this
     * file the resource that each process will try to acquire.
     *
     * @var string
     */
    const PID_FILE_NAME = 'database_manager.lock';

    /**
     * Time in seconds to consider the PID file stale. If current time plus
     * PID_FILE_MAX_TIME is reached, the lock will be removed.
     * @var int
     */
    const PID_FILE_MAX_TIME = 300;

    /**
     * Defines the path where the PID_FILE will be created.
     *
     * @var string
     */
    private $_lockPath;

    /**
     * Used for storing the options passed to the script via the command line.
     *
     * @var array
     */
    private $_options = array();

    /**
     * Deltas for the branch.
     *
     * @var array
     */
    private $_deltas = array();

    /**
     * Already installed deltas.
     *
     * @var array
     */
    private $_installedDeltas = array();

    /**
     * Deltas to install/uninstall.
     *
     * var array
     */
    private $_deltasToInstall = array();

    /**
     * Keeps track whether we're going 'up' or 'down' while applying deltas.
     *
     * var string
     */
    private $_direction;

    /**
     * URI of the svn repository.
     *
     * @var string
     */
    private $_svnRepoUri;

    /**
     * Path to the directory relative to the branch/trunk where all stored
     * procedures are kept.
     *
     * @var string
     */
    private $_sqlDirectory;

    /**
     * Contains the database handle for the script.
     */
    private $_dbh;

    /**
     * Contains the instance of the parser for the database updates.
     */
    private $_parser;

    /**
     * File handle for writing the entire database change.
     *
     * @var stream
     */
    private $_fileHandle;

    /**
     * Contains the path and the name of the file that will contain the entire
     * database change.
     *
     * @var string
     */
    private $_databaseChangeFilename;

    /**
     * Contains instructions to print out to the user when the script is done
     * running.
     *
     * @var string
     */
    private $_instructions;

    /**
     * Object constructor
     *
     */
    public function __construct()
    {
        // nothing!
    }

    /**
     * Main method that runs the entire database change.
     */
    public function run()
    {
        try {
            // sets the path to the lock dir.
            $this->_lockPath = $this->getConfig()->databaseManager->tmpDir;

            // acquire the exclusive lock
            $this->_acquireLock();

            $this->setDeltasToInstall(
                $this->getInstalledDeltas(),
                $this->getAvailableDeltas());

            $this->processDeltas();

            echo $this->getInstructions();

        // catch any parser exceptions
        } catch (MyApp_Database_Parser_Exception $e) {
            echo "$e\n";
        } catch (MyApp_Database_Parser_Update_Exception $e) {
            echo "$e\n";
        } catch (MyApp_Database_Parser_Provider_Exception $e) {
            echo "$c\n";
        }

        // release the current exclusive lock
        $this->_releaseLock();
    }

    /**
     * Parse command line options.
     */
    public function parseOptions()
    {
        echo "Parsing command line options...";

        $options = getopt("c:s:v:f:x:p:b:");

        // Check whether arguments were passed as required correctly

        // The config file
        if (is_null($options['c'])) {
            $this->getUsage("You must specify a config file.");
        }

        // Section in the config file
        if (is_null($options['s'])) {
            $this->getUsage("You must specify a section in the config file.");
        }

        // Target version specified (if specified)
        if (array_key_exists('v', $options)) {
            if (!is_null($options['v']) && !is_numeric($options['v'])) {
                $this->getUsage("Version should be numeric or null.");
            } else if ($options['v'] < 0) {
                $this->getUsage("Target version is less than 0.  Preventing space-time continuum collapse:  exiting...");
            }
        } else {
            // if option v was not specified, set it to its default of null
            $options['v'] = null;
        }

        // Which branch are the deltas coming from?
        if (!array_key_exists('b', $options)) {
            $this->setBranch('trunk');
        } else {
            $this->setBranch($options['b']);
        }

        $this->setRequestedVersion($options['v']);

        // if no parser update provider is given, sets it to null in order for
        // the setter method to set the default one
        if (!array_key_exists('p', $options)) {
            $options['p'] = null;
        }

        $this->setUpdateParserProvider($options['p']);

        // Current version (if specified)
        if (array_key_exists('f', $options)) {
            if (!is_null($options['f']) && !is_numeric($options['f'])) {
                $this->getUsage("Current version should be numeric or null.");
            } else if ($options['f'] < 0) {
                $this->getUsage("Current version specified is less than 0.  Preventing space-time continuum collapse:  exiting...");
            }
        } else {
            // if option f was not specified, set it to null
            $options['f'] = null;
        }

        $this->setForceCurrentVersion($options['f']);

        // Check whether the specified config file exists
        if (!file_exists($options['c'])) {
            $this->getUsage("Specified config file does not exist.");
        }

        try {
            $config = new Zend_Config_Xml($options['c'], $options['s']);
        } catch (Zend_Config_Exception $e) {
            $this->getUsage($e);
        }

        $this->setConfig($config);

        // Get dbManager config which tells us the association of deltas
        // to branches.
        $this->parseDbManagerConfig();

        echo "done.\n";
    }

    /**
     * Parses the XML config for the dbManager which tells us the association
     * of deltas 2 branches.
     */
    public function parseDbManagerConfig()
    {
        $dbManagerConfig =
            simplexml_load_file(
                $this->getConfig()->databaseManager->config,
                'SimpleXMLElement',
                LIBXML_DTDLOAD + LIBXML_DTDVALID + LIBXML_NOCDATA);

        if ($libXmlError = libxml_get_last_error()) {
            $libXmlError->message;
        } else {
            $this->setSvnRepoUri($dbManagerConfig->svn_repo_uri);
            $this->setSqlDirectory($dbManagerConfig->sql_directory);

            $deltas = array();

            // get the cut-off point - when should we look in trunk for previous deltas
            foreach ($dbManagerConfig->branches->branch as $branch) {
                if (trim((string) $branch->name) == $this->getBranch()) {
                    $lastUpdateFromTrunk = $branch->last_update_from_trunk;
                }
            }

            // gather all possible deltas for this branch

            $deltaOrderCounter = 0;

            // first, process trunk
            foreach ($dbManagerConfig->branches->branch as $branch) {
                if (trim((string) $branch->name) == 'trunk') {
                    foreach($branch->deltas->delta as $delta) {
                        if ((int) $delta <= $lastUpdateFromTrunk) {
                            $deltaAttributes = $delta->attributes();

                            $deltaInfo =
                                array('deltaNum' => (int) $delta,
                                      'branch' => trim((string) $branch->name),
                                      'src' => trim((string) $deltaAttributes['src']),
                                      'originalBranch' => null);

                            $originalBranch = trim((string) $deltaAttributes['original_branch']);

                            if (!is_null($originalBranch) && !empty($originalBranch)) {
                                $deltaInfo['originalBranch'] = $originalBranch;
                            }

                            $deltas[$deltaOrderCounter] = $deltaInfo;
                            $deltaOrderCounter++;
                        }
                    }
                }
            }

            // process specified branch, if it's not trunk
            foreach ($dbManagerConfig->branches->branch as $branch) {
                if ($branch->name == $this->getBranch()) {
                    foreach($branch->deltas->delta as $delta) {
                        if ((int) $delta > $lastUpdateFromTrunk) {
                            $deltaAttributes = $delta->attributes();

                            $deltaInfo =
                                array('deltaNum' => (int) $delta,
                                      'branch' => trim((string) $branch->name),
                                      'src' => trim((string) $deltaAttributes['src']),
                                      'originalBranch' => null);

                            $originalBranch = trim((string) $deltaAttributes['original_branch']);

                            if (!is_null($originalBranch) && !empty($originalBranch)) {
                                $deltaInfo['originalBranch'] = $originalBranch;
                            }

                            $deltas[$deltaOrderCounter] = $deltaInfo;
                            $deltaOrderCounter++;
                        }
                    }
                }
            }

            $this->setDeltas($deltas);
        }
    }

    /**
     * Print usage message and exit.
     */
    public function getUsage($message = null)
    {
        $usage =  "Error: \n\t$message\n" .
            "Usage: \n\tdatabase_manager.php\n" .
            "\t-c <config_file> (e.g. myapp.ini)\n" .
            "\t-s <section> (section in the config file)\n" .
            "\t-v <version number> (if not specified, the script will update to max available version)\n" .
            "\t-f <from version number> (if not specified, the script will update from the current version of the database)\n" .
            "\t-b <branch number> (specifies the branch number or 'trunk'. Default is 'trunk')\n" .
            "\t-p <update provider> ('XmlSvn' or 'XmlLocal'. Default is 'XmlSvn'.)\n";

        $this->leave($usage);
    }


    /**
     * Check the current version of the database and return it.
     * If the database is brand new and doesn't contain a SCHEMA_VERSION table,
     * return 0.
     */
    private function getInstalledDeltasFromDb()
    {
        echo "Getting the current version of the database: ";

        $dbh = $this->getDbh();

        // check if the table that keeps track of the schema version even exists
        $query = "/* " . __METHOD__ . " */
            SELECT count(*) table_exists
              FROM user_tables
             WHERE table_name = 'SCHEMA_VERSION'";

        $sth = $dbh->prepare($query);
        $sth->execute();
        $row = $sth->fetchAssoc();

        if (!$row['TABLE_EXISTS']) {
            echo " 0.\n";
            return 0;
        }

        // We added field 'is_installed' later in the process.  This is
        // a check to make sure we don't query against this field unless
        // it exists.a
        $query = "/* " . __METHOD__ . " */
            select count(*) field_exists
              from user_tab_columns
             where table_name = 'SCHEMA_VERSION'
               and column_name = 'IS_INSTALLED'";

        $sth = $dbh->prepare($query);
        $sth->execute();
        $row = $sth->fetchAssoc();

        $isInstalledClause = '';
        if ($row['FIELD_EXISTS'] == 1) {
            $isInstalledClause = 'where is_installed = 1';
        }

        // if schema version table exists, return the current db version
        $query = "/* " . __METHOD__ . " */
            select to_number(last_update_ran) delta_num
              from schema_version
             $isInstalledClause
            order by to_number(last_update_ran)";

        $sth = $dbh->prepare($query);
        $sth->execute();
        return $sth->fetchAll();
    }

    /**
     * See what updates are available.
     *
     * @todo deprecated
     */
    private function getMaxAvailableVersion()
    {
        echo "Getting the max available version: ";

        $maxAvailableVersion = 0;

        foreach ($this->getDeltas() as $deltaNum => $delta) {
            if ($maxAvailableVersion < $deltaNum) {
                $maxAvailableVersion = $deltaNum;
            }
        }

        echo "$maxAvailableVersion.\n";

        return $maxAvailableVersion;
    }

    /**
     * Returns an array of all available versions in the order they
     * are listed in the config file.
     */
    private function getAvailableDeltas()
    {
        return $this->getDeltas();
    }

    /**
     * Creates an array of deltas that need to be processed.  The elements of the array
     * are ordered in the order they need to run.
     *
     * Sets the direction of the deltas.
     */
    private function setDeltasToInstall(array $installedDeltas, array $availableDeltas)
    {
        $deltasToInstall = array();

        if (is_null($this->getRequestedVersion())) {
            // scenario 1:  updating database with all available deltas - up
            echo "\nNo target version was specified.  Will process all available deltas.\n";

            $this->setDirection('up');

            foreach($availableDeltas as $deltaOrderCounter => $delta) {
                if (!in_array($delta['deltaNum'], $installedDeltas)) {
                    $deltasToInstall[] = $delta;
                }
            }
        } else {
            // user has specified a target version
            echo "\nTarget version: " . $this->getRequestedVersion() . ", processing deltas to get to this version.\n";

            if (!in_array($this->getRequestedVersion(), $installedDeltas)) {
                echo "General Direction: up\n";
                // scenario 2:  updating database up up to a certain delta

                $this->setDirection('up');

                // This version has not yet been installed, going up
                foreach($availableDeltas as $deltaOrderCounter => $delta) {
                    if (!in_array($delta['deltaNum'], $installedDeltas))  {
                        $deltasToInstall[] = $delta;
                    }

                    if ($delta['deltaNum'] == $this->getRequestedVersion()) {
                        // reached the target version, stop adding versions to install
                        break;
                    }
                }
            } else {
                echo "General Direction: down\n";
                // This version has already been installed, going down

                // scenario 3:  downgrading database to specified delta

                $this->setDirection('down');

                // reverse the array because we're going in the down direction
                $reversedAvailableDeltas = array_reverse($availableDeltas);

                foreach ($reversedAvailableDeltas as $deltaOrderCounter => $delta) {
                    if ($delta['deltaNum'] == $this->getRequestedVersion()) {
                        // do not uninstall the target version, stop adding versions to uninstall
                        $deltasToInstall[] = $delta;
                        break;
                    }

                    // Uninstall the delta if it's already been installed
                    if (in_array($delta['deltaNum'], $installedDeltas)) {
                        $deltasToInstall[] = $delta;
                    }
                }
            }
        }

        $this->_deltasToInstall = $deltasToInstall;
    }

    /**
     * Returns deltas to install/uninstall in the order they should be processed.
     */
    public function getDeltasToInstall()
    {
        return $this->_deltasToInstall;
    }

    /**
     * Process all updates.
     */
    private function processDeltas()
    {
        $deltasToInstall = $this->getDeltasToInstall();

        $i = 0;
        echo "\nProcessing deltas: \n";

        $numDeltas = count($deltasToInstall);
        if ($numDeltas == 0) {
            $this->leave('There are no deltas to install at this time.');
        }

        if (is_null($this->getRequestedVersion())) {
            $targetVersion = $deltasToInstall[$numDeltas - 1]['deltaNum'];
            echo "Target version was not specified.  Will process all available deltas for this branch.\n";
        } else {
            $targetVersion = $this->getRequestedVersion();
        }

        $isTargetVersionInDeltasToInstall = false;
        foreach ($deltasToInstall as $delta) {
            if ($i == 0) {
                $fromVersion = $delta['deltaNum'];
            }
            $i++;
            echo "\tdelta: " . $delta['deltaNum'] . "\n";

            if ($delta['deltaNum'] == $targetVersion) {
                $isTargetVersionInDeltasToInstall = true;
            }
        }

        if (!$isTargetVersionInDeltasToInstall) {
            $this->leave("The requested version is not available for this branch.  Exiting...\n");
        } else {

            // set the location of the file that will contain all the changes
            $this->setDatabaseChangeFilename($fromVersion, $targetVersion);

            // open the database change file for writing
            $this->getFileHandle();

            foreach($deltasToInstall as $delta) {
                $this->getParser($delta);
                $this->processUpdate($delta, $this->getDirection(), $delta['originalBranch']);
            }

            // close the database change file
            $this->closeFileHandle();

            $this->appendInstructions(
                "The entire database change is now contained in " .
                $this->getDatabaseChangeFilename() . ".  In order to apply " .
                "this change, run:\n\n\tsqlplus " .
                $this->getConfig()->database->main->username . '/' .
                $this->getConfig()->database->main->password . '@' .
                $this->getConfig()->database->main->name . ' < ' .
                $this->getDatabaseChangeFilename() . "\n\n");

            echo "Done processing all updates.\n";
        }
    }

    /**
     * Process a specific update.
     *
     * @param str $direction 'up' or 'down'
     */
    private function processUpdate($delta, $direction, $deltaOriginalBranch = null)
    {
        $deltaNum = $delta['deltaNum'];
        // $update->getSvnRevision() will give the svn revision of the xml file that contains this update
        echo "- processing update " . $deltaNum . ", direction: $direction.\n";

            $parser = $this->getParser($delta);

            $branch = 'trunk';
            if ($this->getBranch() != 'trunk') {
                $branch = 'branches/' . $this->getBranch();
            }

            if (!is_null($deltaOriginalBranch)) {
                $branch = 'branches/' . $deltaOriginalBranch;
            }

            // If the update provider is not specified, get data from local disk,
            // otherwise, retrieve it from version control (svn).
            if ($this->getUpdateParserProvider() == 'XmlLocal') {
                $sqlDirectory = $this->getSqlDirectory();
            } else {
                $sqlDirectory =
                    $this->getSvnRepoUri() .
                    $branch . '/' . $this->getSqlDirectory();
            }

            // the parser needs to know the root directory for packages (definition files) (3rd param)
            $update = $parser->fetchUpdate(
                $deltaNum,
                'Oracle',
                $sqlDirectory);

            // @todo If we ever use more than the 'main' schema, we can modify
            // this method to create separate files for changes to be ran on
            // separate schemas.
            //$this->appendInstructions(
            //    "Installing update #$updateNumber to be installed on schema " . $update->getSchema() . "\n");

            $this->appendInstructions(
                "Summary for update #$deltaNum: " . $update->getSummary() . "\n\n");

            if ($direction == 'up') {
                $install = $update->getUpStatement();
                $jobs = $update->getJob()->getUpStatement();
                $schemaVersionChange =
                    $this->getSchemaVersionUpStatement($deltaNum,
                                                       $update->getSummary());
            } else if ($direction == 'down') {
                $install = $update->getDownStatement();
                $jobs = $update->getJob()->getDownStatement();
                $schemaVersionChange =
                    $this->getSchemaVersionDownStatement($deltaNum);
            }

            // append the statements to execute
            $this->appendDatabaseChange($install . "\n\n");

            if ($update->hasPackages()) {
                while (($package = $update->getPackage()))
                {
                    // append package definitions and bodies to the database change
                    echo "Retrieving svn revision for: " . $package->getDefinitionResourcePath() . "\n";
                    $this->appendDatabaseChange($package->getDefinition() . "\n");
                    echo "Retrieving svn revision for: " . $package->getBodyResourcePath() . "\n";
                    $this->appendDatabaseChange($package->getBody() . "\n");
                }
            }

            // append the jobs creation or removal
            $this->appendDatabaseChange("\n\n" . $jobs . "\n");

            // append the update of the SCHEMA_VERSION table
            $this->appendDatabaseChange("\n\n" . $schemaVersionChange . "\n");
    }

    /**
     * Set the config member based on input from command line.
     */
    public function setConfig(Zend_Config_Xml $config)
    {
        $this->_options['config'] = $config;
    }

    /**
     * Get config object.
     */
    public function getConfig()
    {
        return $this->_options['config'];
    }

    /**
     * Set the association of deltas to releases.
     */
    public function setDeltaToRelease(array $deltaToRelease)
    {
        $this->_deltaToRelease = $deltaToRelease;
    }

    /**
     * Retrieves all available deltas on a given branch.
     *
     * @return array
     */
    public function getDeltas()
    {
        return $this->_deltas;
    }

    /**
     * Sets available deltas on a given branch.
     */
    public function setDeltas(array $deltas)
    {
        $this->_deltas = $deltas;
    }

    /**
     * Retrieves the URI of svn repository
     */
    public function getSvnRepoUri()
    {
        return $this->_svnRepoUri;
    }

    /**
     * Sets the URI of svn repository
     */
    public function setSvnRepoUri($svnRepoUri)
    {
        $this->_svnRepoUri = $svnRepoUri;
    }

    /**
     * Retrieves the directory in a branch/trunk where we keep all stored procedures.
     */
    public function getSqlDirectory()
    {
        return $this->_sqlDirectory;
    }

    /**
     * Sets the directory where stored procs are kept.
     */
    public function setSqlDirectory($sqlDirectory)
    {
        $this->_sqlDirectory = $sqlDirectory;
    }

    /**
     * Set version requested by user on the command line.  If not specified
     * by user, it is set to null.
     */
    public function setRequestedVersion($version)
    {
        $this->_options['requested_version'] = $version;
    }

    /**
     * Get target version specified by user.
     */
    public function getRequestedVersion()
    {
        return $this->_options['requested_version'];
    }

    /**
     * Sets the current version of the database.  This can be derived from the db
     * OR as an override parameter provided on the command line.
     */
    public function setForceCurrentVersion($version)
    {
        $this->_options['current_version'] = $version;
    }

    /**
     * Get the current version.  If provided on the command line, use it,
     * otherwise, get it from the db.
     */
    public function getInstalledDeltas()
    {
        /**
        @todo Address the case when the user passes the from (or "current") version
        if ($this->_options['current_version']) {
            return $this->_options['current_version'];
        } else {
            $currentVersion = $this->getInstalledDeltasFromDb();
            $this->setForceCurrentVersion($currentVersion);
        }

        return $this->_options['current_version'];
        **/
        if (count($this->_installedDeltas) == 0) {
            $installedDeltasFromDb = $this->getInstalledDeltasFromDb();

            if ($installedDeltasFromDb === 0) {
                $installedDeltasFromDb = array();
            }

            $installedDeltas = array();
            foreach($installedDeltasFromDb as $row) {
                $installedDeltas[] = $row[0];
            }

            $this->_installedDeltas = $installedDeltas;
        }

        return $this->_installedDeltas;
    }

    /**
     * Sets the branch for which the database manager is generating updates.
     */
    public function setBranch($branch)
    {
        $this->_options['branch'] = $branch;
    }

    /**
     * Retrieves the numer of the branch for which the database manager is
     * generating updates.
     */
    public function getBranch()
    {
        return $this->_options['branch'];
    }

    /**
     * Set the parser object for the manager.
     */
    public function setParser($parser)
    {
        $this->_parser = $parser;
    }

    /**
     * Get the parser object.  No longer a singleton so we can reset the
     * connect URI depending on the source of deltas.
     */
    public function getParser($delta)
    {
        // get the unique singleton parser instance
        $parser = MyApp_Database_Parser::getInstance();

        // who's going to give us the update?
        $parser->setUpdateProvider($this->getUpdateParserProvider());

        $deltas = $this->getDeltas();
        $connectUri = $delta['src'];

        $parser->providerConnect($connectUri);

        $this->setParser($parser);

        return $this->_parser;
    }

    /**
     * Set the database handler.
     */
    public function setDbh(MyApp_Database_Oci8 $dbh)
    {
        $this->_dbh = $dbh;
    }

    /**
     * Get the database handler object.  If it doesn't exist, create it (singleton).
     */
    public function getDbh($schema = 'main')
    {
        if (is_null($this->_dbh)) {
            $dbh = new MyApp_Database_Oci8(
                $this->getConfig()->database->$schema->dsn,
                $this->getConfig()->database->$schema->username,
                $this->getConfig()->database->$schema->password);

            $this->setDbh($dbh);
        }

        return $this->_dbh;
    }

    /**
     * Set the file handle for writing the entire database change.
     */
    public function setFileHandle($fileHandle)
    {
        $this->_fileHandle = $fileHandle;
    }

    /**
     * Get the file handle for writing the entire database change.  If the file
     * does not exist, using option 'w' will attempt to create it.  This will
     * overwrite any file with the existing filename!
     */
    public function getFileHandle()
    {
        if (is_null($this->_fileHandle)) {
            if (!$fileHandle = fopen($this->getDatabaseChangeFilename(), 'w')) {
                $this->leave('Could not open file ' .
                             $this->getDatabaseChangeFilename() .
                             ' for writing.');
            }

            $this->setFileHandle($fileHandle);
        }

        return $this->_fileHandle;
    }

    /**
     * Append to the file with the entire database change.
     */
    public function appendDatabaseChange($string)
    {
        if (fwrite($this->getFileHandle(), $string) === false) {
            $this->leave("Cannot write to file " . $this->getDatabaseChangeFilename());
        }
    }

    /**
     * Close the file pointed to by this file handle.
     */
    public function closeFileHandle()
    {
        if (!is_null($this->_fileHandle)) {
            fclose($this->_fileHandle);
        }

        return true;
    }

    /**
     * Sets the name of the file that will contain all of the code that needs to
     * run to move the database from the version $from to the version $to.
     *
     * @param string $from Revision that the database is already at.
     * @param string $to Revision that the database will be at after the
     *                   changes are ran.
     */
    public function setDatabaseChangeFilename($from, $to)
    {

        $this->_databaseChangeFilename =
            $this->getConfig()->databaseManager->tmpDir . '/' .
            'databaseChangeFrom' . $from . 'to' . $to . $this->getDirection() . '.sql';
    }

    /**
     * Return the name of the file with all the database changes as generated
     * this script.
     */
    public function getDatabaseChangeFilename()
    {
        return $this->_databaseChangeFilename;
    }

    /**
     * Append to the instructions which are printed out when the script is done.
     */
    public function appendInstructions($string)
    {
        $this->_instructions = $this->getInstructions() . $string;
    }

    /**
     * Get the final instructions for the user.
     */
    public function getInstructions()
    {
        return $this->_instructions;
    }

    /**
     * Create the PL/SQL statement to run to update SCHEMA_VERSION table
     * for a specific update.
     *
     * @param number $updateNumber Number of the update which corresponds to the
     *                             XML file that stores it.
     * @param string $updateSummary Summary that describes changes in this update.
     * @param string $direction Specifies whether we're going "up" or "down",
     *                          which will dictate whether we should add the update
     *                          or remove it.
     */
    public function getSchemaVersionUpStatement($updateNumber, $updateSummary)
    {
        // the vars are not bound, so escape apostrophes
        $updateSummary = str_replace("'", "''", $updateSummary);

        return
            "begin\n\tschema_version_pkg.add_update(p_update => '$updateNumber', p_summary => '$updateSummary');\nend;\n/\nshow errors\n\ncommit;";
    }

    public function getSchemaVersionDownStatement($updateNumber)
    {
        return
            "begin\n\tschema_version_pkg.remove_update(p_update => '$updateNumber');\nend;\n/\nshow errors\n\ncommit;";
    }

    /**
     * Force the manager to quit.
     */
    private function leave($message = null, $releaseLock = true)
    {
        if ($releaseLock) {
            $this->_releaseLock();
        }

        echo "$message\n";
        exit();
    }

    /**
     * Acquires a unique resource in order to not allow simultaneous execution of this
     * script.
     *
     * It is based on a mechanism that enforces access to a resource
     * (@see self::PID_FILE_NAME) each time the script runs. If the resource was acquired
     * by another process, it will check if the resource is stale
     * (@see self::PID_FILE_MAX_TIME), otherwise it will fail with a fatal error.
     *
     * @return boolean True if the resource was successfully acquired.
     */
    private function _acquireLock()
    {
        // set the correct path to the lock resource based on dir and file name.
        $lockResource = $this->_lockPath . "/" . self::PID_FILE_NAME;
        echo "Acquiring exclusive lock $lockResource\n";

        // Check to see if lock file exists.
        if (file_exists($lockResource)) {
            // If the lock file exists and is not stale, the process is running,
            // leave the process and don't release current lock.
            if (!((filectime($lockResource) + self::PID_FILE_MAX_TIME) <= time())) {
                $this->leave(
                   "Error: database manager is either already running or it did " .
                       "not exit cleanly during the previous run.\nPID: $lockResource\n",
                   false);
            }
        }

        // Touches the pid file in order to create it if it does not exist.
        return touch($lockResource);
    }

    /**
     * Releases current acquired resource. (@see self::PID_FILE_NAME)
     *
     * @return boolean True if the resource was successfully released, otherwise it
     *                 will return false.
     */
    private function _releaseLock()
    {
        // set the correct path to the lock resource based on dir and file name.
        $lockResource = $this->_lockPath . "/" . self::PID_FILE_NAME;
        echo "Releasing exclusive lock $lockResource\n";

        if (file_exists($lockResource)) {
            return unlink($lockResource);
        }

        return true;
    }

    /**
     * Set the Provider which the parser will use in order to retrieve the update.
     * Valid Options are:
     * - XmlSvn
     * - XmlLocal
     *
     * If null or empty is passed, the default Provider will be set to XmlSvn.
     *
     * @param string $provider
     */
    public function setUpdateParserProvider($provider)
    {
        if (empty($provider)) {
            $provider = 'XmlSvn';
        }

        $this->_options['update_parser_provider'] = $provider;
    }

    /**
     * Returns the Update Provider nam to be user by the parser.
     *
     * @return string
     */
    public function getUpdateParserProvider()
    {
        return $this->_options['update_parser_provider'];
    }

    /**
     * Set the direction.
     */
    public function setDirection($direction)
    {
        $this->_direction = $direction;
    }

    /**
     * Get direction.
     */
    public function getDirection()
    {
        if (!$this->isDirectionValid()) {
            // @todo error
        }
        return $this->_direction;
    }

    /**
     * True/false if the direction is valid.  Valid directions are:
     *  'up'
     *  'down'
     */
    public function isDirectionValid()
    {
        if ($this->_direction == 'up' || $this->_direction == 'down') {
            return true;
        }
        return false;
    }
}
?>
