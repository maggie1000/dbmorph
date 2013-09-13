<?php
interface Delta_Interface
{
    /**
     * Retrieves all appropriate contents of the delta.  Depending on things
     * like direction or RDBMS, the contents may differ (e.g. 'CREATE OR REPLACE'
     * in Oracle vs. dropping a function and rebuilding it in MySQL).
     */
    public function getContents();
}
