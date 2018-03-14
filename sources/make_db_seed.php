<?php
/**
 * Test database seed generator
 *
 * User: Karel Wintersky
 * Date: 15.03.2018, time: 1:04
 */
require_once 'class.INI_Config.php';
require_once 'class.DBConnection.php';
require_once 'class.CLIConsole.php';

function at($array, $key, $default_value = NULL) { return (array_key_exists($key, $array)) ? $array[$key] : $default_value; }

// check SAPI status: script can be launched only from console
if (php_sapi_name() !== "cli") {
    die("KW's Sitemap Generator can't be launched in browser.");
}

$argv_config_filepath = 'make_db_seed.ini';
$argv_config_path = dirname($argv_config_filepath);

$sm_config = new INI_Config( $argv_config_filepath );
$GLOBAL_SETTINGS = $sm_config->get('___GLOBAL_SETTINGS___');

// получим суффикс секции с данными подключения к БД
$db_section_suffix = $sm_config->get('___GLOBAL_SETTINGS___/db_section_suffix', '');

if ($db_section_suffix === '') {
    // опция - пустая строчка
    $dbi = NULL;

    $error_message = <<<MSG_DBSUFFIX_EMPTY

<font color='yellow'>[WARNING]</font> Key <font color='cyan'>___GLOBAL_SETTINGS___/db_section_suffix </font> is <strong>EMPTY</strong> in file <font color='yellow'>{$argv_config_filepath}</font><br>
Database connection can't be established.<br>
Any sections with <strong>source='sql'</strong> will be skipped.<br>
<br>

MSG_DBSUFFIX_EMPTY;

    CLIConsole::echo_status($error_message);
} else {
    $DB_SETTINGS = $sm_config->get("___GLOBAL_SETTINGS:{$db_section_suffix}___");

    if ($DB_SETTINGS === NULL) {
        $error_message = <<<MSG_DBSECTION_NOTFOUND

<font color='lred'>[ERROR]</font> : Config section <font color='cyan'>[___GLOBAL_SETTINGS:{$db_section_suffix}___]</font> not found in file <font color='yellow'>{$argv_config_filepath}</font><br>
See <font color="green">https://github.com/KarelWintersky/kwTools.SitemapGenerator</font> for assistance. <br>
MSG_DBSECTION_NOTFOUND;

        CLIConsole::echo_status($error_message);
        die(2);
    }

    $dbi = new DBConnection($DB_SETTINGS);
    if (!$dbi->is_connected) die($dbi->error_message);
}

$sm_config->delete('___GLOBAL_SETTINGS___');
$sm_config->delete('___GLOBAL_SETTINGS:DATABASE___');
$sm_config->delete("___GLOBAL_SETTINGS:{$db_section_suffix}___");

$all_sections = $sm_config->getAll();

CLIConsole::echo_status("<br><strong>Generating test data</strong> based on <font color='yellow'>{$argv_config_filepath}</font><br>");

$TABLE_SCHEME = <<<TABLE_SCHEME
CREATE TABLE `%s` (
  `id` INT(11) NOT NULL AUTO_INCREMENT,
  `subject` VARCHAR(80) DEFAULT NULL,
  `lastmod` DATETIME DEFAULT NULL,
  PRIMARY KEY (`id`)
) ENGINE=MYISAM AUTO_INCREMENT=1 DEFAULT CHARSET=utf8;
TABLE_SCHEME;

$TABLE_INSERT_REQUEST = <<<TABLE_INSERT_REQUEST
    INSERT INTO `%s` (`lastmod`,`subject`) VALUES (
      FROM_UNIXTIME(UNIX_TIMESTAMP('2014-01-01 01:00:00')+FLOOR(RAND()*31536000)),
      ROUND(RAND()*100,2)
    );
TABLE_INSERT_REQUEST;

$part_limit = at($GLOBAL_SETTINGS, 'partial_count', 5000);

foreach ($all_sections as $section_name => $section_config) {
    CLIConsole::echo_status("<font color='yellow'>[{$section_name}]</font>");

    $count = $section_config['count'];

    $dbi->getconnection()->query("DROP TABLE IF EXISTS `{$section_name}`");
    $dbi->getconnection()->query( sprintf($TABLE_SCHEME, $section_name) );

    CLIConsole::echo_status("Generating {$count} rows for table {$section_name}");

    // fill table data
    for ($i=0; $i<$count; $i++) {
        $dbi->getconnection()->query( sprintf($TABLE_INSERT_REQUEST, $section_name) );

        if (($i != 0) && ($i % $part_limit == 0)) CLIConsole::echo_status("+ Generated " . str_pad($i, 7, ' ', STR_PAD_LEFT) . " db rows.");
    }
    CLIConsole::echo_status("+ Generated " . str_pad($i, 7, ' ', STR_PAD_LEFT) . " db rows. <br>");
}


