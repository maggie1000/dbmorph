<?php
interface Dbmorph_Delta_Provider_Interface {

    /**
     * How/where is the delta stored?  XML?  text file?  SQLLite?
     *
     * @return instance of DeltaProvider
     */
    public function getEngine();
}
?>
