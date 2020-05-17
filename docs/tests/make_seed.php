#!/usr/bin/php
<?php
/**
 * Test database seed generator
 *
 * User: Karel Wintersky
 * Date: 15.03.2018, time: 1:04
 */
/**/

/**
 * Class INI_Config, version 1.0.1
 */
class INI_Config
{
    const GLUE = '/';
    private $config = array();

    /**
     * @param $filepath
     * @param string $subpath
     */
    public function __construct($filepath, $subpath = '')
    {
        $this->init($filepath, $subpath);
    }

    /**
     * @param $filepath
     * @param string $subpath
     */
    public function init($filepath, $subpath = '')
    {
        if (file_exists($filepath)) {
            $new_config = parse_ini_file($filepath, true);

            if ($subpath == "" || $subpath == $this::GLUE) {
                foreach ($new_config as $key => $part) {
                    if (array_key_exists($key, $this->config)) {
                        $this->config[$key] = array_merge($this->config[$key], $part);
                    } else {
                        $this->config[$key] = $part;
                    }
                }

            } else {
                $this->config["{$subpath}"] = $new_config;
            }

            unset($new_config);
        } else {
            $message = "<strong>FATAL ERROR:</strong> Config file `{$filepath}` not found. " . PHP_EOL;

            if (function_exists('echo_status_cli')) {
                echo_status_cli($message);
                die(1);
            } else {
                if (php_sapi_name() === "cli") {
                    $message = strip_tags($message);
                }
                die($message);
            }
        }
    }

    /**
     * @param $filepath
     * @param string $subpath
     */
    public function append($filepath, $subpath = '')
    {
        $this->init($filepath, $subpath);
    }

    /**
     * @param $parents
     * @param null $default_value
     * @return array|null
     */
    public function get($parents, $default_value = NULL)
    {
        if ($parents === '') {
            return $default_value;
        }

        if (!is_array($parents)) {
            $parents = explode($this::GLUE, $parents);
        }

        $ref = &$this->config;

        foreach ((array) $parents as $parent) {
            if (is_array($ref) && array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            } else {
                return $default_value;
            }
        }
        return $ref;
    }

    /**
     * @param $parents
     * @param $value
     * @return bool
     */
    public function set($parents, $value)
    {
        if (!is_array($parents)) {
            $parents = explode($this::GLUE, (string) $parents);
        }

        if (empty($parents)) return false;

        $ref = &$this->config;

        foreach ($parents as $parent) {
            if (isset($ref) && !is_array($ref)) {
                $ref = array();
            }

            $ref = &$ref[$parent];
        }

        $ref = $value;
        return true;
    }

    /**
     * @param array $array
     * @param array|string $parents
     */
    private function array_unset_value(&$array, $parents)
    {
        if (!is_array($parents)) {
            $parents = explode($this::GLUE, $parents);
        }

        $key = array_shift($parents);

        if (empty($parents)) {
            unset($array[$key]);
        } else {
            $this->array_unset_value($array[$key], $parents);
        }
    }

    /**
     * @param $parents
     */
    public function delete($parents)
    {
        $this->array_unset_value($this->config, $parents);
    }

    /**
     * @return array
     */
    public function getAll()
    {
        return $this->config;
    }

}

/**/

/**
 * User: Arris
 *
 * Class DBConnection
 *
 * Date: 15.03.2018, time: 0:31
 */
class DBConnectionLite
{
    private $database_settings = array();
    private $pdo_connection;
    private $table_prefix = '';
    public  $is_connected = FALSE;
    public  $error_message = '';

    /**
     * DBConnection constructor.
     * @param $db_settings
     */
    public function __construct($db_settings)
    {
        $database_settings = $db_settings;

        $this->table_prefix = $db_settings['table_prefix'] ?? '';
        $this->database_settings = $database_settings;

        $dbhost = $database_settings['hostname'];
        $dbname = $database_settings['database'];
        $dbuser = $database_settings['username'];
        $dbpass = $database_settings['password'];
        $dbport = $database_settings['port'];



        $dsl = "mysql:host=$dbhost;port=$dbport;dbname=$dbname";

        try {
            $dbh = new \PDO($dsl, $dbuser, $dbpass);

            $dbh->exec("SET NAMES utf8 COLLATE utf8_unicode_ci");
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            $this->pdo_connection = $dbh;
        } catch (\PDOException $e) {
            $this->error_message = "Database connection error!: " . $e->getMessage() . "<br/>";
            $this->pdo_connection = null;
            return false;
        }

        $this->is_connected = true;
        return true;
    }

    /**
     * @return null|PDO
     */
    public function getconnection()
    {
        return $this->pdo_connection;
    }


}

/* end class.DBConnection.php *//**/

/**
 * User: Arris
 *
 * Class CLIConsole
 *
 * Date: 14.03.2018, time: 22:11
 */
class CLIConsole
{
    const VERSION = 1.4;

    const FOREGROUND_COLORS = [
        'black'         => '0;30',
        'dark gray'     => '1;30',
        'dgray'         => '1;30',
        'blue'          => '0;34',
        'light blue'    => '1;34',
        'lblue'         => '1;34',
        'green'         => '0;32',
        'light green'   => '1;32',
        'lgreen'        => '1;32',
        'cyan'          => '0;36',
        'light cyan'    => '1;36',
        'lcyan'         => '1;36',
        'red'           => '0;31',
        'light red'     => '1;31',
        'lred'          => '1;31',
        'purple'        => '0;35',
        'light purple'  => '1;35',
        'lpurple'       => '1;35',
        'brown'         => '0;33',
        'yellow'        => '1;33',
        'light gray'    => '0;37',
        'lgray'         => '0;37',
        'white'         => '1;37'
    ];

    const BACKGROUND_COLORS = [
        'black'     => '40',
        'red'       => '41',
        'green'     => '42',
        'yellow'    => '43',
        'blue'      => '44',
        'magenta'   => '45',
        'cyan'      => '46',
        'light gray'=> '47'
    ];

    private static $echo_status_cli_flags = [
        'strip_tags'        => false,
        'decode_entities'   => false
    ];

    /**
     * ConsoleReadline::readline('Введите число от 1 до 999: ', '/^\d{1,3}$/');
     * ConsoleReadline::readline('Введите число от 100 до 999: ', '/^\d{3}$/');
     *
     * @param $prompt -
     * @param $allowed_pattern
     * @param bool|FALSE $strict_mode
     * @return bool|string
     */
    public static function readline($prompt, $allowed_pattern = '/.*/', $strict_mode = FALSE)
    {
        if ($strict_mode) {
            if ((substr($allowed_pattern, 0, 1) !== '/') || (substr($allowed_pattern, -1, 1) !== '/')) {
                return FALSE;
            }
        } else {
            if (substr($allowed_pattern, 0, 1) !== '/')
                $allowed_pattern = '/' . $allowed_pattern;
            if (substr($allowed_pattern, -1, 1) !== '/')
                $allowed_pattern .= '/';
        }

        do {
            $result = readline($prompt);

        } while (preg_match($allowed_pattern, $result) !== 1);
        return $result;
    }

    /**
     * Печатает в консоли цветное сообщение.
     * Допустимые форматтеры:
     * <font color=""> задает цвет из списка: black, dark gray, blue, light blue, green, lightgreen, cyan, light cyan, red, light red, purple, light purple, brown, yellow, light gray, gray
     * <hr> - горизонтальная черта, 80 минусов (работает только в отдельной строчке)
     * <strong> - заменяет белым цветом
     * @param string $message
     * @param bool|TRUE $breakline
     */
    public static function echo_status_cli($message = "", $breakline = TRUE)
    {
        $fgcolors = self::FOREGROUND_COLORS;

        // replace <br>
        $pattern_br = '#(?<br>\<br\s?\/?\>)#U';
        $message = preg_replace_callback($pattern_br, function ($matches) {
            return PHP_EOL;
        }, $message);

        // replace <hr>
        $pattern_hr = '#(?<hr>\<hr\s?\/?\>)#U';
        $message = preg_replace_callback($pattern_hr, function ($matches) {
            return PHP_EOL . str_repeat('-', 80) . PHP_EOL;
        }, $message);

        // replace <font>
        $pattern_font = '#(?<Full>\<font[\s]+color=[\\\'\"](?<Color>[\D]+)[\\\'\"]\>(?<Content>.*)\<\/font\>)#U';
        $message = preg_replace_callback($pattern_font, function ($matches) use ($fgcolors) {
            $color = (PHP_VERSION_ID < 70000)
                ? isset($fgcolors[$matches['Color']]) ? $fgcolors[$matches['Color']] : $fgcolors['white']    // php below 7.0
                : $fgcolors[$matches['Color']] ?? $fgcolors['white '];                                           // php 7.0+
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // replace <strong>
        $pattern_strong = '#(?<Full>\<strong\>(?<Content>.*)\<\/strong\>)#U';
        $message = preg_replace_callback($pattern_strong, function ($matches) use ($fgcolors) {
            $color = $fgcolors['white'];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // вырезает все лишние таги (если установлен флаг)
        if (self::$echo_status_cli_flags['strip_tags'])
            $message = strip_tags($message);

        // преобразует html entity-сущности (если установлен флаг)
        if (self::$echo_status_cli_flags['decode_entities'])
            $message = htmlspecialchars_decode($message, ENT_QUOTES | ENT_HTML5);

        if ($breakline === TRUE) $message .= PHP_EOL;
        echo $message;
    }

    /**
     * Wrapper around echo/echo_status_cli
     * Выводит сообщение на экран. Если мы вызваны из командной строки - заменяет теги на управляющие последовательности.
     * @param $message
     * @param bool|TRUE $breakline
     */
    public static function echo_status($message = "", $breakline = TRUE)
    {
        if (php_sapi_name() === "cli") {
            self::echo_status_cli($message, $breakline);
        } else {
            if ($breakline === TRUE) $message .= PHP_EOL . "<br/>\r\n";
            echo $message;
        }
    }

    /**
     * Устанавливает флаги обработки разных тегов в функции echo_status()
     * @param bool|FALSE $will_strip - вырезать ли все лишние теги после обработки заменяемых?
     * @param bool|FALSE $will_decode - преобразовывать ли html entities в их html-представление?
     */
    public static function echo_status_setmode($will_strip = FALSE, $will_decode = FALSE)
    {
        self::$echo_status_cli_flags = array(
            'strip_tags'        => $will_strip,
            'decode_entities'   => $will_decode
        );
    }

}

/* end class.CLIConsole.php */
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

    $dbi = new DBConnectionLite($DB_SETTINGS);
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

    CLIConsole::echo_status("Generating {$count} rows for table <font color='yellow'>{$section_name}</font>");

    // fill table data
    for ($i=0; $i<$count; $i++) {
        $dbi->getconnection()->query( sprintf($TABLE_INSERT_REQUEST, $section_name) );

        if (($i != 0) && ($i % $part_limit == 0)) CLIConsole::echo_status("+ Generated " . str_pad($i, 7, ' ', STR_PAD_LEFT) . " db rows.");
    }
    CLIConsole::echo_status("+ Generated " . str_pad($i, 7, ' ', STR_PAD_LEFT) . " db rows. <br>");
}


