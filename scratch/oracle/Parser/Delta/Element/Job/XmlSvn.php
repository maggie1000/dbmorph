<?php
/**
 * File containing the DB Parser's Element Job Xml and Svn
 * Class implementation of a Element Job supporting Xml and Svn as structure.
 *
 */
class MyApp_Database_Parser_Update_Element_Job_XmlSvn
{
    /**
     * SimpleXMLElement object of the Job
     *
     * @var SimpleXMLElement
     */
    private $_xmlContainer;

    /**
     * Object Constructor
     *
     * @param SimpleXMLElement $xmlContainer
     */
    public function __construct(SimpleXMLElement $xmlContainer)
    {
        $this->_xmlContainer = $xmlContainer;
    }

    /**
     * Returns the Up Procedure for a job.
     *
     * @return string
     */
    public function getUpStatement()
    {
        return trim((string) $this->_xmlContainer->up);
    }

    /**
     * Returns the Down Procedure for a job.
     *
     * @return string
     */
    public function getDownStatement()
    {
        return trim((string) $this->_xmlContainer->down);
    }
}
