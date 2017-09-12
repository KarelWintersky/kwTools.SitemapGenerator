<?php
/**
 * User: Arris
 * Date: 07.09.2017, time: 20:08
 */


require_once 'class.dbconnection.php';
require_once 'class.staticconfig.php';
StaticConfig::init('db.ini');

$dbci = new DBConnection('main');

$mem1 = memory_get_usage();

$q = "SELECT id, subject FROM testrows LIMIT 200000";

$sth = $dbci->getconnection()->query($q);

$all = $sth->fetchAll();

$mem2 = memory_get_usage();

echo "Before ", $mem1, PHP_EOL, '<br/>';

echo "After: ", $mem2, PHP_EOL, '<br/>';

echo "Diff: ", $mem2-$mem1, PHP_EOL, '<br/>';