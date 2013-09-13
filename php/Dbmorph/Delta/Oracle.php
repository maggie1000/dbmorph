<?php
require_once 'Dbmorph/Delta.php';

class Dbmorph_Delta_Oracle
    extends Dbmorph_Delta
    implements Dbmorph_Delta_Interface
{

    /**
     * Code that recompiles stored functions.  Assumes use of 'CREATE OR REPLACE'
     * syntax when creating functions.
     *
     * @var array of string
     */
    private $_storedFunctionsFiles = array();

    /**
     * Code that recompiles stored procedures.  Assumes that stored procedures
     * are organized into packages with header and body each.  Assumes use of
     * 'CREATE OR REPLACE' syntax when creating packages.
     *
     * @var array of string
     */
    private $_storedProceduresFiles = array();

    /**
     * Constructor
     */
    public function __construct(DbmorphDeltaProvider $provider)
    {
        parent::__construct($provider);
    }

    /**
     * Retrieves all contents of the files referenced in the delta that contain
     * code for stored functions and procedures.
     */
    public function getContents()
    {
        $contents = '';
        if ($this->_direction == self::DIRECTION_UP) {
            $contents .= $this->getUp();
        } else {
            $contents .= $this->getDown();
        }

        foreach($this->_storedFunctionsFiles as $storedFunction => $filename) {
            $contents .= "DROP FUNCTION $storedFunction;\n";
            // svn cat -r[correct version of the file
            // if file exists in svn at that revision number:
                // append contents of file to $contents
        }

        return $contents;
    }
}
?>
