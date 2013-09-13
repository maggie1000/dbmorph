<?php
/**
 * Quick script to help developers figure out what database deltas need to be installed.
 * Works for MySQL only.
 * Does not support stored procedures, unless they're contained within the delta.
 *
 * Usage:
 *   -e APPLICATION_ENV
 *   -p /path/to/app
 *   -m targetDelta for the 'main' database (optional)
 */

$options = getopt("e:p:m:g:v::");

if (array_key_exists('v', $options)) {
    define('VERBOSE', true);
} else {
    define('VERBOSE', false);
}

if (!array_key_exists('e', $options)) {
    getUsageAndLeave('You must specify the environment, e.g. staging, dev.');
}

if (!array_key_exists('p', $options)) {
    getUsageAndLeave('You must specify path/to/application.');
}

if (array_key_exists('m', $options)) {
    if (is_null($options['m']) || !is_numeric($options['m'])) {
        getUsageAndLeave('Main target delta should be numeric or null.');
    } else if ($options['m'] < 0) {
        getUsageAndLeave('Main target version is less than 0.  Preventing space-time continuum collapse: exiting...');
    }
} else {
    verbose("Main target delta was not specified.  Will process all available deltas.\n");
    $options['m'] = null;
}

// putenv("APPLICATION_ENV=username");
putenv('APPLICATION_ENV=' . $options['e']);

// $appPath = '/home/username/code-checkout';
$appPath = $options['p'];

// $mainTargetDelta = null;
$mainTargetDelta = $options['m'];

// set up the script
$libPath = $appPath . '/deploy/lib';
set_include_path($libPath);

$dbDeltaPath = $appPath . '/db/deltas';

require 'MyApp/Environment.php';
require 'MyApp/Environment/Script.php';
APPLICATION_ENV_Script::init($appPath);

// Figure out changes for the 'main' database
verbose("\n-----------------------------------\n");
verbose("database: 'main' \n\n");
$dbMain = APPLICATION_ENV_Script::getMainDb();
$deltasInDb = checkDeltasInDb($dbMain);
$deltaPathMain = $dbDeltaPath . '/main';
processDeltas('main', $deltaPathMain, $deltasInDb, $mainTargetDelta);

verbose("\nAnd now you're done!\n");

/**
 * Checks which and if any deltas were run in the database.
 * @return int Last installed (up) delta ran in the database.
 */
function checkDeltasInDb($dbh)
{
    $lastDelta = 0;

    // first, check if schema_version table exists
    $query = "show tables like 'schema_version'";
    $sth = $dbh->prepare($query);
    $sth->execute();
    $rows = $sth->fetchAll();

    if (count($rows) > 0) {
        $query = "/* " . __METHOD__ . " */" .
            "select delta_id, direction, unix_timestamp(ran_on) ran_on
               from schema_version
              order by id asc";
        $sth = $dbh->prepare($query);
        $sth->execute();
        $rows = $sth->fetchAllAssoc();

        $deltas = array();
        verbose("Deltas installed: \n");

        foreach($rows as $row) {
            verbose("delta: {$row['delta_id']}, ");
            if ($row['direction'] == 0) {
                verbose("un");
                $deltas[$row['delta_id']] = 0;
            } else {
                verbose("  ");
                $deltas[$row['delta_id']] = 1;
            }
            verbose("installed on " . date('M d, Y - h:i:s', $row['ran_on']) . "\n");
        }
    }

    return $deltas;
}

function findLastDelta(array $deltas)
{
    $lastDelta = 0;
    foreach($deltas as $deltaId => $wasInstalled) {
        if ($wasInstalled == 1) {
            $lastDelta = $deltaId;
        }
    }

    if ($lastDelta == 0) {
        verbose("\nlast installed delta: none\n");
    } else {
        verbose("\nlast installed delta: $lastDelta \n");
    }

    return $lastDelta;
}

/**
 * Reads all deltas in the deltas directory for a given database.
 * @return string String containing all the deltas to run in the database.
 */
function processDeltas($dbName, $deltaPath, $deltas, $targetDelta = null)
{
    $lastDelta = findLastDelta($deltas);

    // figure out which direction we're going
    $upFiles = explode("\n", trim(shell_exec('ls ' . $deltaPath . ' | grep "^up_"')));
    $downFiles = explode("\n", trim(shell_exec('ls ' . $deltaPath . ' | grep "^down_"')));

    $maxUpFile = 0;
    $matches = array();
    foreach($upFiles as $file) {
        preg_match('/\d+/', $file, $matches);
        if ($matches[0] > $maxUpFile) {
            $maxUpFile = $matches[0];
        }
    }
    verbose("\nmaxUpFile = $maxUpFile\n");

    if ($targetDelta > $maxUpFile) {
        $targetDelta = $maxUpFile;
        verbose("\nthere are only $maxUpFile deltas available.\n");
    }

    if (is_null($targetDelta)) {
        $direction = 1;
    } else {
        if ($targetDelta > $lastDelta) {
            verbose("\ngoing up!\n");
            $direction = 1;
        } else if ($targetDelta < $lastDelta) {
            verbose("\ngoing down!\n");
            $direction = 0;
        } else {
            verbose("\nyou're at target! - but let's check if you haven't missed any deltas in the middle!\n");
            return true;
        }
    }

    verbose("\n");
    $deltaIds = array();
    $installedDeltaIds = array();
    foreach($deltas as $deltaId => $deltaDirection) {
        $deltaIds[] = $deltaId;
        if ($deltaDirection == 0) {
            verbose("delta $deltaId was uninstalled and not re-installed.\n");
            if (in_array("up_$deltaId.sql", $upFiles)) {
                verbose("\tup_$deltaId.sql is available for install!\n");
            } else {
                verbose("\tup_$deltaId.sql is NOT available for install!\n");
            }
        } else {
            $installedDeltaIds[] = $deltaId;
        }
    }

    // depending on direction and whether the targetDelta has been set, figure
    // out which deltas need to be processed
    $cats = '';
    if ($direction == 1) {
        if (is_null($targetDelta)) {
            $targetDelta = $maxUpFile;
        }

        $deltaFilename = "{$dbName}_up";

        $from = 1;
        $to = $targetDelta;

        foreach(range($from, $to) as $num) {
            if (in_array("up_$num.sql", $upFiles) && !in_array($num, $installedDeltaIds)) {
                verbose("adding up_$num.sql\n");
                $deltaFilename .= "_$num";
                $cats .= "$deltaPath/up_$num.sql ";
            }
        }

        $deltaFilename .= ".sql";

    } else if ($direction == 0) {
        if (is_null($targetDelta)) {
            $targetDelta = 1;
        }
        if ($targetDelta < 1) {
            $targetDelta = $lastDelta;
        }

        $deltaFilename = "{$dbName}_down";

        $from = $lastDelta;
        $to = 1;

        foreach(range($from, $to) as $num) {
            if (in_array("down_$num.sql", $downFiles) && in_array($num, $deltaIds)) {
                verbose("adding down_$num.sql\n");
                $deltaFilename .= "_$num";
                $cats .= "$deltaPath/down_$num.sql ";
            }
        }

        $deltaFilename .= ".sql";
    }

    if (!empty($cats)) {
        $catsDir = APPLICATION_ENV::getConfig()->database->deltas->dir;

        echo "\nTo install required database changes on this database, run the following commands:\n\n";
        echo "\tcat $cats > $catsDir/$deltaFilename";
        echo "\n\tmysql -u " . getenv('APPLICATION_ENV') . "_$dbName -D " . getenv('APPLICATION_ENV') . "_$dbName -p < $catsDir/$deltaFilename\n";
    } else {
        verbose("\n");
        echo "Your '$dbName' database is exactly how you want it.  Yay!\n";
    }
}

/**
 * Will print the message to screen if user specified verbose mode.
 */
function verbose($message)
{
    if (VERBOSE) {
        echo $message;
    }
    return;
}

/**
 * Shows proper usage of script and exits.
 */
function getUsageAndLeave($message = null)
{
    echo $message;
    echo "\n\nUsage:\n";
    echo "\t-e APPLICATION_ENV\n";
    echo "\t-p /path/to/app\n";
    echo "\t-m targetDelta for the 'main' database (optional)\n";
    echo "\t-v verbose - prints a lot of junk\n";
    echo "\n";
    exit();
}
