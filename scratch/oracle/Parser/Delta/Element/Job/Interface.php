<?php
/**
 * File containing the DB Parser's Element Job Interface
 * Defines a Parser Element Job interface ment to be implemented by any kind
 * of job element based on a kind of update container.
 */
interface MyApp_Database_Parser_Update_Element_Job_Interface
{
    /**
     * Returns the Up Statement of the job.
     *
     * @return string
     */
    public function getUpStatement();

    /**
     * Returns the Down Statement of the job.
     *
     * @return string
     */
    public function getDownStatement();
}
