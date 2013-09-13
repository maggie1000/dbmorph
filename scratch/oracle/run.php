<?php
/**
 * Note: for more documentation on how this script works, see
 *  MyApp/Database/Manager.php
 *
 * Usage:
 * database_manager.php
 *      -c <config_file> (e.g. your-config-file.ini)
 *      -s <section> (section in the config file)
 *      -v <version number> (if not specified, the script will update to max available version)
 *      -f <force current version number> (this is the 'from' version of the database -
 *                                         if not specified, the script will do a database query
 *                                         to check the current version - this is preferred)
 *      -p <update provider> (The update provider is the system or method the update parser will
 *                            use in order to retrieve the update. Options available are:
 *                            - XmlSvn
 *                            - XmlLocal: Warning!: XmlLocal should be used only in case of testing
 *                                        an update about to be released.
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

require_once 'Zend/Loader.php';
Zend_Loader::registerAutoload();

$manager = new MyApp_Database_Manager();

// Get command line args
$manager->parseOptions();

// Run the script to generate the .sql file with all necessary database changes.
$manager->run();
?>
