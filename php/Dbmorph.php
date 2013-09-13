<?php
require_once 'Dbmorph_Config.php';
require_once 'Dbmorph_Patch.php';
require_once 'Dbmorph_Delta.php';

class Dbmorph
{
    public function run()
    {
        $config = new Dbmorph_Config();
        $patch = new Dbmorph_Patch($config);
        $patch->build();
    }
}
?>
