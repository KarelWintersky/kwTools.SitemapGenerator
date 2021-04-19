<?php

function at($array, $key, $default_value = NULL)
{
    return (array_key_exists($key, $array)) ? $array[$key] : $default_value;
}

/**
 *
 * Class SitemapSystem
 */
class SitemapSystem {
    /** Маркер склейки путей конфига */
    const CONFIG_GLUE = '/';

    /* Мессаги */
    const MESSAGES = [
        'only_cli'  =>  "Sitemap Generator can't be launched in browser.",
        'welcome'   =>    '
<font color="white">KWCSG</font> is a <strong>Karel Wintersky\'s Configurable Sitemap Generator</strong><br>
It uses .ini files for configuration <br>
See: https://github.com/KarelWintersky/kwTools.SitemapGenerator/blob/master/README.md
or https://github.com/KarelWintersky/kwTools.SitemapGenerator/blob/master/README-EN.md

© Karel Wintersky, 2020, <font color="dgray">https://github.com/KarelWintersky/kwTools.SitemapGenerator</font><hr>',

       'missing_config' =>  '<font color="red">missing config file</font>
<font color="white">Use: </font> %1$s <font color="yellow">--config /path/to/sitemap-config.ini</font>
or
<font color="white">Use: </font> %1$s --help<hr>
',

        'missing_dbsuffix'  =>  '<font color="yellow">[WARNING]</font> Key <font color="cyan">___GLOBAL_SETTINGS___/db_section_suffix </font> is <strong>EMPTY</strong> in file <font color="yellow">%1$s</font>
Database connection can\'t be established.
Any sections with <strong>source=\'sql\'</strong> will be skipped.
',

        'missing_dbsection' =>  '<font color=\'lred\'>[ERROR]</font> : Config section <font color=\'cyan\'>[___GLOBAL_SETTINGS:%1$s___]</font> not found in file <font color=\'yellow\'>%2$s</font>
See <font color=\'green\'>https://github.com/KarelWintersky/kwTools.SitemapGenerator</font> for assistance.
',
        'unknown_db_driver' =>  '<font color="red">[ERROR]</font> Unknown database driver: %1$s!'

    ];

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

    /**
     * Конфиг
     * @var array
     */
    private $config = [];

    /**
     * Инстанс PDO для коннекта к БД
     *
     * @var \PDO
     */
    public $pdo_connection;

    /**
     * @var bool|null
     */
    public  $is_db_connected = FALSE;

    /**
     * @var string
     */
    public  $error_message = '';

    /**
     * SitemapSystem constructor.
     *
     * NOWDOC/HEREDOC в определении значений массива у констант не работает, поэтому сообщения создаются динамически
     *
     * @param $config_file
     */
    public function __construct($config_file)
    {
        // загружаем конфиг
        $this->config_load($config_file);

        $GLOBAL_SETTINGS = $this->config_get_key('___GLOBAL_SETTINGS___');

        $db_section_suffix = $this->config_get_key('___GLOBAL_SETTINGS___/db_section_suffix', '');

        if (empty($db_section_suffix)) {
            $this->is_db_connected = NULL;
            self::say('missing_dbsuffix', $config_file);
            return;
        }

        $DB_SETTINGS = $this->config_get_key("___GLOBAL_SETTINGS:{$db_section_suffix}___");

        if ($DB_SETTINGS === NULL) {
            self::say('missing_dbsection', $db_section_suffix, $config_file);
            die(2);
        }

        $this->initDBConnection($DB_SETTINGS);
    }

    /**
     * Загружает конфиг из файла
     *
     * @param $config_file
     * @param string $subpath
     */
    public function config_load($config_file, $subpath = '')
    {
        if (!file_exists($config_file)) {
            self::say_cli("<strong>FATAL ERROR:</strong> Config file `{$config_file}` not found. ");
            die(1);
        }

        if (!is_readable($config_file)) {
            self::say_cli("<strong>FATAL ERROR:</strong> Config file `{$config_file}` not readable. ");
            die(2);
        }

        $new_config = parse_ini_file($config_file, true, INI_SCANNER_TYPED);

        if (empty(trim($subpath, $this::CONFIG_GLUE))) {
            foreach ($new_config as $key => $part) {
                if (array_key_exists($key, $this->config)) {
                    $this->config[$key] = array_merge($this->config[$key], $part);
                } else {
                    $this->config[$key] = $part;
                }
            }
        } else {
            $this->config[ "{$subpath}" ] = $new_config;
        }

        unset($new_config);
    }

    /**
     * Возвращает значение конфига по ключу
     *
     * @param $parents
     * @param null $default_value
     * @return array|mixed|null
     */
    public function config_get_key($parents, $default_value = NULL)
    {
        if ($parents === '') {
            return $default_value;
        }

        if (!is_array($parents)) {
            $parents = explode($this::CONFIG_GLUE, $parents);
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
     * Возвращает все значения из конфига
     * @return array
     */
    public function config_get_all()
    {
        return $this->config;
    }

    /**
     * Удаляет ключ (с потомками) из конфига
     * @param $parents
     */
    public function config_remove_key($parents)
    {
        $this->array_unset_value($this->config, $parents);
    }

    public function config_remove_global_settings()
    {
        $db_section_suffix = $this->config_get_key('___GLOBAL_SETTINGS___/db_section_suffix', '');

        $this->config_remove_key('___GLOBAL_SETTINGS___');
        $this->config_remove_key('___GLOBAL_SETTINGS:DATABASE___');
        $this->config_remove_key("___GLOBAL_SETTINGS:{$db_section_suffix}___");
    }
    
    public function validateGlobalSection()
    {
    
    }

    /**
     * служебная функция удаления
     * @param $array
     * @param $parents
     */
    private function array_unset_value(&$array, $parents)
    {
        if (!is_array($parents)) {
            $parents = explode($this::CONFIG_GLUE, $parents);
        }

        $key = array_shift($parents);

        if (empty($parents)) {
            unset($array[$key]);
        } else {
            $this->array_unset_value($array[$key], $parents);
        }
    }

    /**
     * Печатает сообщение с анализом аргументов
     *
     * @param string $message_id
     * @param mixed ...$args
     */
    public static function say($message_id = "", ...$args)
    {
        $string = array_key_exists($message_id, self::MESSAGES) ? self::MESSAGES[$message_id] : $message_id;

        $string =
            (func_num_args() > 1)
                ? vsprintf($string, $args)
                : $string;

        self::say_cli( $string );
    }

    public function initDBConnection($database_settings)
    {
        try {
            switch ($database_settings['driver']) {
                case 'mysql':
                case 'mysqli':
                case 'pdo': {
                    $dsl = sprintf("mysql:host=%s;port=%s;dbname=%s",
                        $database_settings['hostname'],
                        $database_settings['port'],
                        $database_settings['database']);
                    $dbh = new \PDO($dsl, $database_settings['username'], $database_settings['password']);
                    break;
                }
                case 'pgsql': {
                    $dsl = sprintf("pgsql:host=%s;port=%d;dbname=%s;user=%s;password=%s",
                        $database_settings['hostname'],
                        $database_settings['port'],
                        $database_settings['database'],
                        $database_settings['username'],
                        $database_settings['password']);

                    $dbh = new \PDO($dsl);
                    break;
                }
                case 'sqlite': {
                    $dsl = sprintf("sqlite:%s", realpath($database_settings['hostname']));
                    $dbh = new \PDO($dsl);
                    break;
                }
                default: {
                    self::say('unknown_db_driver', $database_settings['driver']);
                    die(2);
                    break;
                }
            } // switch

            // default charset and collate is "SET NAMES utf8 COLLATE utf8_unicode_ci"
            $database_settings['charset'] = at($database_settings, 'charset', 'utf8');
            $database_settings['charset_collate'] = at($database_settings, 'charset_collate', 'utf8_unicode_ci');

            if ($database_settings['charset']) {
                $dbh->exec("SET NAMES {$database_settings['charset']}");
            }

            if (isset($database_settings['charset_collate'])) {
                $dbh->exec("SET COLLATE {$database_settings['charset_collate']}");
            }
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            $this->pdo_connection = $dbh;

            $this->is_db_connected = true;

        } catch (Exception $e) {
            die($e->getMessage());
        }

        return true;
    }

    /**
     * Equal to KarelWintersky\Arris\CLIConsole::say()
     *
     * Печатает в консоли цветное сообщение.
     * Допустимые форматтеры:
     * <font color=""> задает цвет из списка: black, dark gray, blue, light blue, green, lightgreen, cyan, light cyan, red, light red, purple, light purple, brown, yellow, light gray, gray
     * <hr> - горизонтальная черта, 80 минусов (работает только в отдельной строчке)
     * <strong> - заменяет белым цветом
     *
     * @param string $message
     * @param bool $breakline
     */
    public static function say_cli($message = "", $breakline = TRUE)
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
            $color = $fgcolors[$matches['Color']] ?? $fgcolors['white '];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        // replace <strong>
        $pattern_strong = '#(?<Full>\<strong\>(?<Content>.*)\<\/strong\>)#U';
        $message = preg_replace_callback($pattern_strong, function ($matches) use ($fgcolors) {
            $color = $fgcolors['white'];
            return "\033[{$color}m{$matches['Content']}\033[0m";
        }, $message);

        if ($breakline === TRUE) $message .= PHP_EOL;
        echo $message;
    }

}

# -eof-
