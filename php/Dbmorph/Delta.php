<?php
require_once 'Dbmorph/Exception.php';

abstract class Dbmorph_Delta
{
    // constants for direction of the delta
    const DIRECTION_UP = 1;
    const DIRECTION_DOWN = 0;

    /**
     * Provider for the delta.
     *
     * @var instance of Dbmorph_Delta_Provider
     */
    private $_provider;

    /**
     * Revision in svn when this delta was committed.
     *
     * @var int
     */
    private $_svnRevision;

    /**
     * Unique ID of the delta.
     *
     * @var int|string
     */
    private $_id;

    /**
     * Direction of the delta: up or down.  Are changes being applied to add
     * new stuff or is something being rolled back?  Use class constants
     * DIRECTION_UP or DIRECTION_DOWN for this property.
     */
    private $_direction;

    /**
     * Optional summary of the change contained in the delta.
     *
     * @var string
     */
    private $_summary;

    /**
     * Schema where the database delta will install changes.  One schema per
     * delta is allowed.  Changes to multiple schemas can be accomplished via
     * multiple deltas.  Not set by default to account for RDBMS that don't
     * explicitly support schemas.
     *
     * @var string
     */
    private $_schema;

    /**
     * All DDL (data definition language) statements and DML (data modification
     * language) statements that are needed to change the database schema going
     * forward.
     *
     * Additionally, it contains the output of files that contain stored procedures
     * and packages which were referenced in the file encapsulating the delta.
     *
     * @var string
     */
    private $_up;

    /**
     * All DDL (data definition language) statements and DML (data modification
     * language) statements that are needed to change the database schema going
     * backward.
     *
     * Additionally, it contains the output of files that contain stored procedures
     * and packages which were referenced in the file encapsulating the delta.
     *
     * @var string
     */
    private $_down;

    /**
     * Constructor: a delta requires a provider.
     */
    public function __construct(Dbmorph_Delta_Provider $provider)
    {
        $this->_provider = $provider;
    }

    /**
     * @return Dbmorph_Delta_Provider instance
     */
    public function getProvider()
    {
        return $this->_provider;
    }

    /**
     * @return int|str
     */
    public function getId()
    {
        return $this->_id;
    }

    /**
     * @return int
     */
    public function getDirection()
    {
        return $this->_direction;
    }

    /**
     * @return string
     */
    public function getSummary()
    {
        return $this->_summary;
    }
}
?>
