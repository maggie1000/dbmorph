<?php
/**
 * Contains Dbmorph_Patch class.
 */
require_once 'Dbmorph/Exception.php';

/**
 * The purpose of the delta is to generate all the DDL and DML statements
 * that need to be run in the database as well as append the code for the
 * stored functions and procedures.  All of these changes are appended to
 * in one place and controlled via Dbmorph_Patch class.
 */
class Dbmorph_Patch
{
    /**
     * Name of the file that will contain all the contents of the patch.
     *
     * @var string
     */
    $_filename;

    /**
     * All deltas for this patch.
     *
     * @var array of Dbmorph_Delta
     */
    $_deltas = array();

    /**
     * Construct Dbmoroph_Patch object.  Requires Dbmorph_Config.
     *
     * @param Dbmorph_Config $config
     * @return Dbmorph_Patch
     */
    public function __construct(Dbmorph_Config $config)
    {
        $this->_filename = $config->patchFilename;
        return $this;
    }

    /**
     * Adds a delta to the patch.
     *
     * @param Dbmorph_Delta $delta
     * @return Dbmorph_Patch
     */
    public function append(Dbmorph_Delta $delta)
    {
        $this->_deltas[] = $delta;
        return $this;
    }

    /**
     * Builds all the deltas in a patch file.
     */
    public function build()
    {
        $contents = '';
        foreach($this->_deltas as $delta) {
            $contents .= $delta->getContents();
        }

        // @todo
        // open $_filename
        // write $contents to $_filename
        // close file handle
        // notify user that the patch is complete and ready to use
    }
}
?>
