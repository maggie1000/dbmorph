<?php
/**
 * File containing the DB Updater Parser's Service Provider Interface Class
 */
interface MyApp_Database_Parser_Provider_Interface
{

    /**
     * Connects the service to the corresponding URI.
     *
     * @param string $uri URI where the resources are located (update files)
     * @param array $options Array of options for the service (Credentials, etc)
     * @return boolean True if successfully connected to the resource.
     */
    public function connect($uri, $options = null);

    /**
     * Obtains a Update container from the resource.
     *
     * @param string $version Version to obtain.
     * @return MyApp_Database_Parser_Update_Interface
     */
    public function getUpdate($version);

    /**
     * Checks wheter or not an update is available.
     *
     * @param string $version
     * @return boolean True if the update is available.
     */
    public function isUpdateAvailable($version);

    /**
     * Disconnects the service.
     *
     * @return void
     */
    public function disconnect();

    /**
     * Gets the current connection Uri (last registered).
     *
     * @return string
     */
    public function getConnectionUri();

    /**
     * Gets the current connection options (last registered).
     *
     * @return mixed
     */
    public function getConnectionOptions();
}
