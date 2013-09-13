<?php
interface Dbmorph_Provider_Interface
{
    /**
     * Set the provider for the delta (xml, text, other db)
     *
     * @param Delta_Provider $deltaProvider
     */
    public function setDeltaProvider($deltaProvider);

    /**
     * Retrieve the RDBMS info for this delta.
     *
     * @return string
     */
    public function getRdbms();

    /**
     * Retrieve the unique ID of the delta.
     *
     * @return int|string
     */
    public function getId();

    /**
     * Retrieve the svn revision of the delta.  If delta has not yet been
     * committed, retrieve local copy of delta.
     */
    public function getSvnRevision();

    /**
     * Retrieve the optional summary of the delta.  This can be used to tell
     * the user how the delta will change the database schema.
     *
     * @return string
     */
    public function getSummary();

    /**
     * Retrieve the name of the schema on which to install the delta.  This can
     * be optional based on the RDBMS.
     *
     * @return string
     */
    public function getSchemaName();

    /**
     * Retrieve the DDL + DML that make changes to the database schema going
     * forward.
     *
     * @return string
     */
    public function getUp();

    /**
     * Retrieve the DDL + DML that make changes to the database schema going
     * backward.
     *
     * @return string
     */
    public function getDown();

    /**
     * Retrieve the list of stored procedures referenced in the delta.  This can
     * be implemented differently depending on the stored procedures support in
     * a given RDBMS, e.g. MySQL 5 will provide just names of stored procedures
     * while Oracle 10g allows you to package stored procedures into packages
     * which are defined in a header and body file.
     *
     * @return array of string
     */
    public function getStoredProcedureReferences();

    /**
     * Retrieve the actual contents of the referenced stored procedures.  This
     * can be implemented differently depending on the requirements of the
     * RDBMS for rebuilding a stored procedure, e.g. MySQL 5 requires that you drop
     * a stored procedure and then recreate it while Oracle 10g allows the
     * 'create or replace' syntax.
     *
     * @return string
     */
    public function getStoredProcedureContents($deltaSvnRevision);

    /**
     * How/where is the delta stored?  XML?  text file?  SQLLite?
     *
     * @return instance of Dbmorph_Delta_Provider
     */
    public function getEngine();

    /**
     * Retrieves a delta
     *
     * @return instance of Dbmorph_Delta
     */
    public function getDelta($deltaNumber);
}
?>
