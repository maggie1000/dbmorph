<?php
/**
 * File containing the DB Parser's Element Package Interface
 * Defines a Parser Element Package interface ment to be implemented by any kind
 * of package element based on a kind of update container.
 */
interface MyApp_Database_Parser_Update_Element_Package_Interface
{
    /**
     * Returns the package definition.
     *
     * @return string
     */
    public function getDefinition();

    /**
     * Returns the package body.
     *
     * @return string
     */
    public function getBody();
}
