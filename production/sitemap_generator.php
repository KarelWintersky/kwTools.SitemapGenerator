#!/usr/bin/php
<?php
/**
 * User: Karel Wintersky
 * Date: 14.03.2018, time: 22:40
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
class DBConnection extends \PDO
{
    private $database_settings = array();
    private $pdo_connection;
    private $table_prefix = '';
    public  $is_connected = FALSE;
    public  $error_message = '';

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

/**/

/**
 * Class SitemapFileSaver
 */
class SitemapFileSaver {
	// Sitemap XML schema
	const SCHEMA = 'http://www.sitemaps.org/schemas/sitemap/0.9';
	
	// инстанс XMLWriter'а
	private $xmlw;

	// внутренние переменные 
	// домен с конечным слешем
	private $sm_domain = ''; 

	// корень имени файла карты (имя секции)
	private $sm_name = '';

	// разделитель между корнем имени карты и номером
	private $sm_separator = '-';
	
	// приоритет ссылки по умолчанию
	private $sm_default_priority = NULL;

	// частота изменения ссылки по умолчанию
	private $sm_default_changefreq = NULL;

	// путь к каталогу файлов сайтмапа
	private $sm_storage_path = '';

	// использовать ли сжатие gzip
	private $sm_use_gzip = false;

	// номер текущего файла, содержащего ссылки. На старте - 0
	private $sm_currentfile_number = 0;

	// количество ссылок в текущем файле
	private $sm_currentfile_links_count = 0;

	// формат даты
	private $specify_date_format;

	// внутренний буфер, содержащий текст текущего файла сайтмапа
	private $buffer = '';

	// размер внутреннего буфера с текущей (генерируемой вотпрямщас) сайтмап-картой
	private $buffer_size = 0;

	// лимитирующие значения
	// размер файла в байтах по умолчанию
	private $max_buffer_size = 50 * 1000 * 1000;

	// максимальное количество ссылок в файле
	private $max_links_count = 50000;

	// массив промежуточных файлов сайтмапа данной секции
	// возвращаем его для построения индекса 
	private $sm_files_index = array();

	// debug
	public $debug_checkbuffer_time = 0;



	/**
	 * Конструктор класса. Устанавливает значения по умолчанию для данной секции.
	 *
	 * @param string $storage_path	-- путь к каталогу файлов (от корня сервера или текущего скрипта), заканчивается слешем! (проверка?)
	 * @param $domain				-- текущий домен (http://localhost/) с конечным слешем
	 * @param $name					-- имя файла карты, обычно совпадает с именем секции
	 * @param string $separator		-- разделитель между именем карты и номером (-)
	 * @param float $priority		-- приоритет по умолчанию (если NULL - атрибут не используется)
	 * @param string $changefreq	-- частота обновления по умолчанию (если NULL - атрибут не используется)
	 * @param bool|true $use_gzip	-- использовать ли сжатие gzip
	 * @param int $max_size			-- максимальный размер в байтах
	 * @param int $max_links		-- максимальное количество ссылок в сайтмэпе
	 * @param string $date_format_type -- тип формата даты (iso8601 или YMD)
	 */
	public function __construct(
		$storage_path = '',
		$domain,
		$name,
		$separator = '-',
		$priority = 0.5,
		$changefreq = 'never',
		$use_gzip = true,
		$max_size = 50000000,
		$max_links = 50000,
		$date_format_type = 'iso8601'
	)
	{
		$this->sm_storage_path = $storage_path;
		$this->sm_domain = $domain;
		$this->sm_name = $name;
		$this->sm_separator = $separator;

		if ($priority) {
			$this->sm_default_priority = $priority;
		}

		if ($changefreq) {
			$this->sm_default_changefreq = $changefreq;
		}

		$this->sm_use_gzip = $use_gzip;

		$this->max_buffer_size = $max_size;
		$this->max_links_count = $max_links;

		if ($date_format_type === 'iso8601') {
			$this->specify_date_format = 'c';
		} else {
			$this->specify_date_format = 'Y-m-d';
		}
	}
	
	/**
	 * Запускает генерацию нового файла карты
	 */
	public function start()
	{
		// создаем инсанс XMLWriter
		$this->xmlw = new \XMLWriter();

		// записываем стандартный заголовок
		$this->xmlw->openMemory();
		$this->xmlw->startDocument('1.0', 'UTF-8');
		$this->xmlw->setIndent(true);
		$this->xmlw->startElement('urlset');
		$this->xmlw->writeAttribute('xmlns', self::SCHEMA);

		// Переносим сгенерированный контент в буфер (смотри https://github.com/KarelWintersky/kwSiteMapGen/issues/1 )
		$this->buffer = $this->xmlw->flush(true);
		$this->buffer_size = count($this->buffer);

		// увеличиваем на 1 номер текущего файла сайтмапа со ссылками
		$this->sm_currentfile_number++;

		// сбрасываем количество ссылок в текущем файле
		$this->sm_currentfile_links_count = 0;
	}

	/**
	 * Останавливает генерацию файла карты, записывает данные на диск и обновляет переменные
	 */
	public function stop()
	{
		// проверяем, проинициализирован ли инстанс XMLWriter'а
		if (! $this->xmlw instanceof XMLWriter ) {
			$this->start();
		}
		$this->xmlw->fullEndElement();
		$this->xmlw->endDocument();
		$this->buffer .= $this->xmlw->flush(true);
		$this->buffer_size += count($this->buffer);

		$filename = $this->sm_name . $this->sm_separator . $this->sm_currentfile_number;

		// в зависимости от флага "use_gzip" дополняем имя файла нужным и упаковываем контент

		if ($this->sm_use_gzip) {
			$filename .= '.xml.gz';
			$buffer = gzencode($this->buffer, 9);
		} else {
			$filename .= '.xml';
			$buffer = $this->buffer;
		}

		// пишем в файл подготовленный буфер
		file_put_contents($this->sm_storage_path . $filename, $buffer);

		// добавляем имя сгенерированного файла сайтмапа в индекс сайтмапов
		array_push( $this->sm_files_index, $filename);

		$this->sm_currentfile_links_count = 0;

		$this->xmlw = NULL;
		unset($this->xmlw);
 	}


	/**
	 * Добавляет ссылку в сайтмап. Извне вызывается только эта функция!!!!
	 *
	 * УДАЛЕНЫ: опциональные значения priority и changefreq. Их изменение для конкретной
	 * ссылки мы можем реализовать в будущем (соответственно изменится и конфиг)
	 *
	 * @param $location
	 * @param null $lastmod
	 */
	public function push($location, $lastmod = NULL)
	{
		$DEBUG = FALSE;

		// проверяем, начат ли (открыт ли на запись) новый файл? Если нет - создаём новый файл.
		if (! $this->xmlw instanceof XMLWriter) {
			if ($DEBUG) var_dump("Instance not found, creating new: START()");
			$this->start();
		}

		// проверяем, не превысило ли текущее количество ссылок в файле карты лимита?
		// если превысило - закрываем файл и открываем новый
		if (
			($this->buffer_size >= $this->max_buffer_size)
			||
			($this->sm_currentfile_links_count >= $this->max_links_count)
		)
		{
			if ($DEBUG) var_dump("Started new iteration, STOP() + START()");
			$this->stop();
			$this->start();
		}

		// добавляем в текущий файл элемент-ссылку на основе переданных параметров
		// увеличиваем на 1 количество ссылок в текущем файле (точнее буфере)
		$this->sm_currentfile_links_count++; 

		// начинаем элемент
		$this->xmlw->startElement('url');

		// location с учетом домена
		$this->xmlw->writeElement('loc', $this->sm_domain . $location);
		
		// lastmod
		if ($lastmod) {
			$this->xmlw->writeElement('lastmod', $this::format_date($lastmod, $this->specify_date_format));
		} else {
			//@todo: необходимость этой строчки (установить lastmod в текущий таймштамп ЕСЛИ он не указан в аргументах функции) под сомнением
			$this->xmlw->writeElement('lastmod', $this::format_date( time() , $this->specify_date_format));
		}

		//@todo: добавить аргументы  в функцию. Если они оба NULL - пытаемся использовать значения, единые для всей секции
		// если и они не установлены (в конфиге) - не пишем атрибуты changefreq и priority
		// это может быть нужно для CSV-источника данных и индивидуальных параметров статических страниц
		if ($this->sm_default_changefreq) {
			$this->xmlw->writeElement('changefreq', $this->sm_default_changefreq);
		}

		if ($this->sm_default_priority) {
			$this->xmlw->writeElement('priority', $this->sm_default_priority);	
		}

		$this->xmlw->endElement();

		$this->buffer .= $this->xmlw->flush(true);
		$this->buffer_size += count($this->buffer);
	}


	/**
	 * возвращает список файлов сайтмапов для текущей секции
	 * @return array
	 */
	public function getIndex()
	{
		return $this->sm_files_index;
	}

	/* ==================================== STATIC METHODS ==================================== */

	/**
	 * форматирует переданную дату в W3C-представление (https://www.w3.org/TR/NOTE-datetime)
	 * из 'Unix timestamp or any English textual datetime description' в формат (по умолчанию Y-m-d)
	 * @param $date
	 * @param string $format
	 * @return bool|string
	 */
	public static function format_date($date, $format = 'Y-m-d')
	{
		if (ctype_digit($date)) {
			return date($format, $date);
		} else {
			return date($format, strtotime($date));
		}
	}

	/**
	 *
	 * @param $www_location			- URL к каталогу с файлами сайтмапов включая домен и финальный слэш
	 * @param $fs_index_location	- путь к файлу индекса от корня сервера или текущего скрипта
	 * @param $files				- массив с именами файлов сайтмапов (полный список собирается через array_merge)
	 * @param string $lastmod		- указатель на момент модификации файлов sitemap
 	 * @param string $date_format_type -- тип формата даты (iso8601 или YMD)
	 */
	public static function createSitemapIndex($www_location, $fs_index_location, $files, $lastmod = 'Today', $date_format_type = 'YMD')
	{
		$specify_date_format = ($date_format_type === 'iso8601') ? 'c' : 'Y-m-d';

		// $SCHEMA = 'http://www.sitemaps.org/schemas/sitemap/0.9';

		$iw = new XMLWriter();

		$iw->openURI($fs_index_location);
		$iw->startDocument('1.0', 'UTF-8');
		$iw->setIndent(true);
		$iw->startElement('sitemapindex');
		$iw->writeAttribute('xmlns', self::SCHEMA);

		foreach ($files as $filename) {
			$iw->startElement('sitemap');
			$iw->writeElement('loc', $www_location . $filename);
			$iw->writeElement('lastmod', self::format_date($lastmod, $specify_date_format));
			$iw->endElement();
		}

		$iw->fullEndElement();
		$iw->endDocument();

		unset($iw);
	} // end createSitemapIndex()

} // end class

/* end class.SitemapFileSaver.php */
// check SAPI status: script can be launched only from console
if (php_sapi_name() !== "cli") {
    die("KW's Sitemap Generator can't be launched in browser.");
}

$this_filename = basename($argv[0]); // get file basename

$welcome_message = <<<SMG_WELCOME

<font color="white">{$this_filename}</font> is a <strong>Karel Wintersky's Configurable Sitemap Generator</strong> with .ini-files as configs
© Karel Wintersky, 2018, <font color="dgray">https://github.com/KarelWintersky/kwTools.SitemapGenerator</font>

SMG_WELCOME;

$hint_message = <<<SMG_USAGE

{$this_filename}: <font color="red">missing config file</font><br>
<font color="white">Use: </font> {$this_filename} <font color="yellow">/path/to/sitemap-config.ini</font>
or
<font color="white">Use: </font> {$this_filename} --help

SMG_USAGE;

function at($array, $key, $default_value = NULL) { return (array_key_exists($key, $array)) ? $array[$key] : $default_value; }

CLIConsole::echo_status($welcome_message);

// аргумент --help выводит справку
if (($argc == 1) || (strtolower($argv[1]) === '--help')) {
    CLIConsole::echo_status($hint_message);
    die;
}

// в противном случае ожидается, что в 1 аргументе передан инишник, а в нем ожидаются секции:
$argv_config_filepath = $argv[1];
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

$limit_urls  = at($GLOBAL_SETTINGS, 'limit_urls', 50000);
$limit_bytes = at($GLOBAL_SETTINGS, 'limit_bytes', 50000000);
$IS_LOGGING  = at($GLOBAL_SETTINGS, 'logging', TRUE);

$sm_config->delete('___GLOBAL_SETTINGS___');
$sm_config->delete('___GLOBAL_SETTINGS:DATABASE___');
$sm_config->delete("___GLOBAL_SETTINGS:{$db_section_suffix}___");

$all_sections = $sm_config->getAll();

$index_of_sitemap_files = array();

if ($IS_LOGGING) CLIConsole::echo_status("<strong>Generating sitemap</strong> based on <font color='yellow'>{$argv_config_filepath}</font>" . PHP_EOL);

// iterate all sections
$stat_total_time = microtime(true);
foreach ($all_sections as $section_name => $section_config) {
    if (array_key_exists('enabled', $section_config) && $section_config['enabled'] == 0) continue;

    if ($IS_LOGGING) CLIConsole::echo_status("<font color='yellow'>[{$section_name}]</font>");

    // init values based on section config
    $url_priority   = at($section_config, 'url_priority', 0.5);
    $url_changefreq = at($section_config, 'url_changefreq', 'never');

    $store = new SitemapFileSaver(
        $GLOBAL_SETTINGS['sitemaps_storage'],
        $GLOBAL_SETTINGS['sitehref'],
        at($section_config, 'radical', $section_name),
        at($GLOBAL_SETTINGS, 'sitemaps_filename_separator', '-'),
        $url_priority,
        $url_changefreq,
        at($section_config, 'use_gzip', true) && at($GLOBAL_SETTINGS, 'use_gzip', true),
        $limit_bytes,
        $limit_urls,
        at($GLOBAL_SETTINGS, 'date_format_type', '') );

    // analyze source type in config section
    switch ($section_config['source']) {
        case 'sql': {
            if ($dbi === NULL) {
                CLIConsole::echo_status("Нельзя использовать секцию с источником данных SQL - отсутствует подключение к БД");
                unset($store);
                continue; // next section
            }

            $sth = $dbi->getconnection()->query( $section_config['sql_count_request'] );
            $sth_result = $sth->fetch();
            $url_count = $sth_result[ $section_config['sql_count_value'] ];

            $chunks_count = (int)ceil($url_count / $limit_urls);

            $offset = 0;
            $t = microtime(true);

            // iterate all chunks
            for ($i = 0; $i < $chunks_count; $i++) {
                $q_chunk = $section_config['sql_data_request'] . " LIMIT {$limit_urls} OFFSET {$offset} ";
                $sth = $dbi->getconnection()->query( $q_chunk );
                $chunk_data = $sth->fetchAll();

                $count = 0;

                /**
                 * Callback function
                 * @param $value
                 */
                $sql_pusher = function($value) use ($section_config, $store, &$count) {
                    $id         = $value[ $section_config['sql_data_id'] ];
                    // $lastmod    = $value[ $section_config['sql_data_lastmod']];

                    $lastmod = ($section_config['sql_data_lastmod'] === 'NOW()') ? NULL : $value[ $section_config['sql_data_lastmod']];
                    $location   = sprintf( $section_config['url_location'], $id);
                    $count++;
                    $store->push( $location, $lastmod );
                };
                array_walk($chunk_data, $sql_pusher);

                if ($IS_LOGGING) CLIConsole::echo_status("+ Generated sitemap URLs from offset " . str_pad($offset, 7, ' ', STR_PAD_LEFT) . " and count " . str_pad($count, 7, ' ', STR_PAD_LEFT) . ". Consumed time: " . round(microtime(true) - $t, 2) .  " sec.");
                $t = microtime(true);

                $offset += $limit_urls;
                unset($sth); // clear memory
            } // for each chunk
            $store->stop();

            // save sitemap files list to index array
            $index_of_sitemap_files = array_merge($index_of_sitemap_files, $store->getIndex());

            break;
        } // end of 'sql' case

        case 'file': {
            $path_to_file = $section_config['filename'];

            if (strpos($path_to_file, '$') !== FALSE) {
                $path_to_file = str_replace('$', $argv_config_path, $path_to_file);
            }

            if (!file_exists($path_to_file)) {
                CLIConsole::echo_status("<font color='lred'>[ERROR]<font> File {$path_to_file} declared in section {$section_name}, option [filename] : not found!");
                CLIConsole::echo_status("<font color='lred'>This section will be ignored!</font>");
                unset($store);
                continue;
            }

            $contentfile = file( $path_to_file );
            $url_count = count($contentfile);
            $section_lastmod = ( $section_config['lastmod'] === 'NOW()') ? time() : NULL;

            $count = 0;
            $t = microtime(true);

            /**
             * Callback function
             * @param $value
             * @param $index
             * @param $section_lastmod
             */
            $file_pusher = function($value, $index, $section_lastmod) use ($section_config, $store, &$count) {
                $location = sprintf( $section_config['url_location'], trim($value));
                $store->push( $location, $section_lastmod );
                $count++;
            };
            array_walk($contentfile, $file_pusher, $section_lastmod);

            $store->stop();

            if ($IS_LOGGING) CLIConsole::echo_status("+ Generated " . str_pad($count, 7, ' ', STR_PAD_LEFT) . "  sitemap URLs. Consumed time: " . round(microtime(true) - $t, 2) . " sec.");
            $t = microtime(true);

            // save sitemap files list to index array
            $index_of_sitemap_files = array_merge($index_of_sitemap_files, $store->getIndex());

            break;
        } // end of 'file' case

        case 'csv': {
            // not implemented
            // загружаем CSV-файл в массив
            // построчно отдаем в Store->push() с соотв. локейшеном, апдейт-дейт и прочими значениями

            break;
        } // end of 'csv' case

        default: {
            if ($IS_LOGGING) CLIConsole::echo_status("<font color='lred'>[ERROR]<font> Unknown source type for section {$section_name}");
            break;
        } // end of DEFAULT case

    } // end of switch

    // destruct SAVER instance
    $store = null;
    unset($store);

    echo PHP_EOL;
} // end of foreach section

if ($IS_LOGGING) CLIConsole::echo_status("<font color='yellow'>[sitemap.xml]</font>") ;

SitemapFileSaver::createSitemapIndex(
    $GLOBAL_SETTINGS['sitemaps_href'],
    $GLOBAL_SETTINGS['sitemaps_mainindex'],
    $index_of_sitemap_files,
    'Today'
);
if ($IS_LOGGING) CLIConsole::echo_status("+ Generated sitemap index." . PHP_EOL);

$dbi = null;

if ($IS_LOGGING) CLIConsole::echo_status("<strong>Finished.</strong>" . PHP_EOL);
if ($IS_LOGGING) CLIConsole::echo_status('Total spent time:  <strong>' . round( microtime(true) - $stat_total_time, 2) . '</strong> seconds. ');
if ($IS_LOGGING) CLIConsole::echo_status('Peak memory usage: <strong>' . (memory_get_peak_usage(true) >> 10) . '</strong> Kbytes. ' . PHP_EOL);


/* EOF */