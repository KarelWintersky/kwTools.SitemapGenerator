<?php
/**
 * User: Arris
 * Date: 07.09.2017, time: 15:16
 */
require_once 'class.dbconnection.php';
require_once 'class.staticconfig.php';
require_once 'class.ini_config.php';

StaticConfig::init('db.ini');

/*$sm_config = new INI_Config('sitemap.ini');

echo '<pre>';

$sm_config->delete('___');

var_dump(  $sm_config->getAll() );*/

