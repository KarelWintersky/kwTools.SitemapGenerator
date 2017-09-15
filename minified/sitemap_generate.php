<?php
/**
 * User: Arris
 * Date: 15.09.2017, time: 18:48
 */

/* @include 'core.sitemapgen.php' */
function at($array, $key, $default_value)
{
    if (array_key_exists($key, $array)) {
        return $array[$key];
    } else {
        return $default_value;
    }
}

/* @include 'class.dbconnection.php' */
class DBConnection extends \PDO
{
    public $connect_error = 0;
    public $database_settings;
    public $table_prefix = '';

    public $pdo_connection = NULL;

    public $is_connected = FALSE;

    /**
     *
     * @param string $key_connection
     */
    public function __construct($key_connection)
    {
        if ($key_connection === '') {
            echo("Connection to DB not defined \r\n");
            debug_print_backtrace();
            die("\r\n<br>");
        }
        $key_connection = trim($key_connection);

        $database_settings_section_name = StaticConfig::key('host/server') . ':' . StaticConfig::key( "connection/{$key_connection}" );
        $database_settings = StaticConfig::key( $database_settings_section_name );

        $this->database_settings = $database_settings;

        try {
            $dbhost = $database_settings['hostname'];
            $dbname = $database_settings['database'];
            $dbuser = $database_settings['username'];
            $dbpass = $database_settings['password'];
            $dbport = $database_settings['port'];

            $dsl = "mysql:host=$dbhost;port=$dbport;dbname=$dbname";

            $dbh = new \PDO($dsl, $dbuser, $dbpass);

            $dbh->exec("SET NAMES utf8 COLLATE utf8_unicode_ci");
            $dbh->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $dbh->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_ASSOC);

            $this->pdo_connection = $dbh;
        } catch (\PDOException $e) {
            echo "\r\n PDO CONNECTION ERROR: " . $e->getMessage() . "\r\n";

            $this->connect_error = "Database connection error!: " . $e->getMessage() . "<br/>";
            $this->pdo_connection = null;
            return false;
        }

        $this->table_prefix = StaticConfig::key(
            $database_settings_section_name . '/table_prefix'
        );

        StaticConfig::setPDO( $dbh );

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
} // class

/* @include 'class.staticconfig.php' */

class StaticConfig
{
    private static $config = array();
    private static $pdo_connection;

    /**
     * @param $filepath
     * @param string $subpath
     */
    public static function init($filepath, $subpath = '')
    {
        if (file_exists($filepath)) {
            $new_config = parse_ini_file($filepath, true);

            if ($subpath == "" || $subpath == '/') {
                foreach ($new_config as $key => $part) {
                    if (array_key_exists($key, self::$config)) {
                        self::$config[$key] = array_merge(self::$config[$key], $part);
                    } else {
                        self::$config[$key] = $part;
                    }
                }

            } else {
                self::$config["{$subpath}"] = $new_config;
            }

            unset($new_config);
        } else {
            die("<strong>FATAL ERROR:</strong> Config file `{$filepath}` not found. ");
        }
    }

    /**
     * @param $filepath
     * @param string $subpath
     */
    public static function append($filepath, $subpath = '')
    {
        self::init($filepath, $subpath);
    }

    /**
     * @param $key
     * @return array|null
     */
    public static function key($key)
    {
        $path = explode('/', $key);

        if ($path[0] == '') unset($path[0]);

        $r = self::$config;

        foreach ($path as $path_key => $path_value) {
            if (isset($r[$path_value])) {
                $r = $r[$path_value];
            } else return NULL;
        }
        unset($path);
        return $r;
    }

    /**
     * @return array
     */
    public static function getAll()
    {
        return self::$config;
    }

    public static function setPDO(\PDO $dbc)
    {
        if (get_class($dbc) === "DBConnection") {
            self::$pdo_connection = $dbc->getconnection();
        } elseif (get_class($dbc) == "PDO") {
            self::$pdo_connection = $dbc;
        } else {
            return false;
        }

        return true;
    }

    /**
     * @return mixed
     */
    public static function getPDO()
    {
        return self::$pdo_connection;
    }

    protected function __construct() {}

    private function __clone() {}

    private function __wakeup() {}
}


/* @include 'class.ini_config.php' */
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
            die("<strong>FATAL ERROR:</strong> Config file `{$filepath}` not found. ");
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
    //-------------------------------------------------------------------------------------------------------
    // https://stackoverflow.com/a/44189105/5127037

    /**
     * @param $parents
     * @param null $default
     * @return array|null
     */
    public function get($parents, $default = NULL)
    {
        if ($parents === '') {
            return $default;
        }

        if (!is_array($parents)) {
            $parents = explode($this::GLUE, $parents);
        }

        $ref = &$this->config;

        foreach ((array) $parents as $parent) {
            if (is_array($ref) && array_key_exists($parent, $ref)) {
                $ref = &$ref[$parent];
            } else {
                return null;
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

/* @include 'class.sitemap_file_saver.php' */
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

    // внутренний буфер, содержащий текст текущего файла сайтмапа
    private $buffer = '';

    // размер внутреннего буфера с текущей (генерируемой вотпрямщас) сайтмап-картой
    private $buffer_size = 0;

    // лимитирующие значения
    private $max_buffer_size = 50 * 1000 * 1000;
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
     * @param string $separator		-- сепаратор между именем карты и номером (-)
     * @param float $priority		-- приоритет по умолчанию (если NULL - атрибут не используется)
     * @param string $changefreq	-- частота обновления по умолчанию (если NULL - атрибут не используется)
     * @param bool|true $use_gzip	-- использовать ли сжатие gzip
     * @param int $max_size
     * @param int $max_links
     */
    public function __construct($storage_path = '', $domain, $name, $separator = '-', $priority = 0.5, $changefreq = 'never', $use_gzip = true, $max_size = 50000000, $max_links = 50000)
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
    }

    // Запускает генерацию нового файла карты
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

    // Останавливает генерацию файла карты, записывает данные на диск и обновляет переменные
    public function stop()
    {
        // проверяем, проинициализирован ли инстанс XMLWriter'а
        if (! $this->xmlw instanceof XMLWriter ) {
            $this->start();
        }
        $this->xmlw->fullEndElement();
        $this->xmlw->endDocument();
        $this->buffer .= $this->xmlw->flush(true);
        $this->buffer_size = count($this->buffer);

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

        // проверяем, начат ли (открыт ли на запись) новый файл?
        if (! $this->xmlw instanceof XMLWriter) {
            // нет. Создаем новый файл
            if ($DEBUG) var_dump("Instance not found, creating new: START()");
            $this->start();
        }

        // проверяем, не превысило ли текущее количество ссылок в файле карты лимита?
        // если превысило - закрываем файл и открываем новый

        // $t = microtime(true);																// <----- DEBUG
        if (
            (count($this->xmlw->outputMemory(false)) >= $this->max_buffer_size)
            ||
            ($this->sm_currentfile_links_count >= $this->max_links_count)
        )
        {
            // $this->debug_checkbuffer_time += (microtime(true) - $t);						// <----- DEBUG
            if ($DEBUG) var_dump("Started new iteration, STOP() + START()");
            $this->stop();
            $this->start();
        } else {
            // $this->debug_checkbuffer_time += (microtime(true) - $t);						// <----- DEBUG
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
            $this->xmlw->writeElement('lastmod', $this::format_date($lastmod));
        } else {
            //@todo: необходимость этой строчки (установить lastmod в текущий таймштамп ЕСЛИ он не указан в аргументах функции) под сомнением
            $this->xmlw->writeElement('lastmod', $this::format_date( time() ));
        }

        // значения changefreq и priority едины для всей секции

        if ($this->sm_default_changefreq) {
            $this->xmlw->writeElement('changefreq', $this->sm_default_changefreq);
        }

        if ($this->sm_default_priority) {
            $this->xmlw->writeElement('priority', $this->sm_default_priority);
        }

        $this->xmlw->endElement();

        $this->buffer .= $this->xmlw->flush(true);
        $this->buffer_size = count($this->buffer);
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
     */
    public static function createSitemapIndex($www_location, $fs_index_location, $files, $lastmod = 'Today')
    {
        $SCHEMA = 'http://www.sitemaps.org/schemas/sitemap/0.9';

        $iw = new XMLWriter();

        $iw->openURI($fs_index_location);
        $iw->startDocument('1.0', 'UTF-8');
        $iw->setIndent(true);
        $iw->startElement('sitemapindex');
        $iw->writeAttribute('xmlns', $SCHEMA);

        foreach ($files as $filename) {
            $iw->startElement('sitemap');
            $iw->writeElement('loc', $www_location . $filename);
            $iw->writeElement('lastmod', self::format_date($lastmod));
            $iw->endElement();
        }

        $iw->fullEndElement();
        $iw->endDocument();

        unset($iw);
    } // end createSitemapIndex()

} // end class

/* @include 'generate.php' */
StaticConfig::init('config.db.ini');

$dbi = new DBConnection('main');

$sm_config = new INI_Config('config.sitemap.ini');

$GLOBAL_SETTINGS = $sm_config->get('___GLOBAL_SETTINGS___');
$sm_config->delete('___GLOBAL_SETTINGS___');

$limit_urls  = at($GLOBAL_SETTINGS, 'limit_urls', 50000);
$limit_bytes = at($GLOBAL_SETTINGS, 'limit_bytes', 50000000);
$IS_LOGGING  = at($GLOBAL_SETTINGS, 'logging', TRUE);

$all_sections = $sm_config->getAll();

$index_of_sitemap_files = array();

// итерируем все секции
$stat_total_time = microtime(true);
foreach ($all_sections as $section_name => $section_config) {

    if (array_key_exists('enabled', $section_config) && $section_config['enabled'] == 0) continue;

    // инициализируем значения на основе конфига
    $url_priority = at($section_config, 'url_priority', 0.5);
    $url_changefreq = at($section_config, 'url_changefreq', 'never');

    // анализируем тип источника данных в конфиге секции
    switch ($section_config['source']) {
        case 'sql': {
            $sth = $dbi->getconnection()->query( $section_config['sql_count_request'] );
            $sth_result = $sth->fetch();
            $url_count = $sth_result[ $section_config['sql_count_value'] ];

            $store = new SitemapFileSaver(
                $GLOBAL_SETTINGS['sitemaps_storage'],
                $GLOBAL_SETTINGS['sitehref'],
                at($section_config, 'radical', $section_name),
                at($GLOBAL_SETTINGS, 'sitemaps_filename_separator', '-'),
                $url_priority,
                $url_changefreq,
                at($GLOBAL_SETTINGS, 'use_gzip', true),         // at($section_config, 'use_gzip', true) && at($GLOBAL_SETTINGS, 'use_gzip', true), // для возможности использовать use_gzip локально
                $limit_bytes,
                $limit_urls);

            // всего цепочек по $limit_urls в цепочке
            $chunks_count = (int)ceil($url_count / $limit_urls);

            // смещение в выборке
            $offset = 0;
            $t = microtime(true);
            for ($i = 0; $i < $chunks_count; $i++) {
                // if ($DEBUG) echo "Chunk # {$i} started. ", PHP_EOL;

                $q_chunk = $section_config['sql_data_request'] . " LIMIT {$limit_urls} OFFSET {$offset} ";

                // if ($DEBUG) echo "Chunk query = `{$q_chunk}` ", PHP_EOL;

                $sth = $dbi->getconnection()->query( $q_chunk );

                // iterate content
                $chunk_data = $sth->fetchAll();

                // if ($DEBUG && $chunk_data) echo "Fetch result successfull. ", PHP_EOL;

                $count = 0;
                // можно сделать через array_walk() с анонимной функцией, но это в полтора раза медленнее (если объявить неименованную функцию как аргумент)
                /*array_walk($chunk_data, function($index, $value) use ($section_config, $store) {
                    $id = $value[ $section_config['sql_data_id']];
                    $lastmod = $value[ $section_config['sql_data_lastmod']];
                    $location = sprintf( $section_config['url_location'], $id);
                    $store->push( $location, $lastmod );
                });*/

                foreach ($chunk_data as $record) {
                    $id = $record[ $section_config['sql_data_id'] ];
                    $lastmod = $record[ $section_config['sql_data_lastmod'] ];

                    $location = sprintf( $section_config['url_location'], $id);

                    $count++;
                    $store->push( $location, $lastmod );
                }

                if ($IS_LOGGING) echo "[{$section_name}] : Generated sitemap URLs from offset {$offset} and count {$count}. Consumed time: ", round(microtime(true) - $t, 2), " sec.", PHP_EOL;
                $t = microtime(true);

                $offset += $limit_urls;

                // clear memory
                unset($sth);
            } // for each chunk
            $store->stop();

            // сохраняем список файлов сайтмапа в индексный массив
            $index_of_sitemap_files = array_merge($index_of_sitemap_files, $store->getIndex());

            // деструктим инстанс сейвера
            $store = null;
            unset($store);

            break;
        } // end of 'sql' case

        case 'file': {
            $contentfile = file( $section_config['filename'] );
            $url_count = count($contentfile);

            if ( $section_config['lastmod'] === 'NOW()') {
                $section_lastmod = time();
            }

            $store = new SitemapFileSaver(
                $GLOBAL_SETTINGS['sitemaps_storage'],
                $GLOBAL_SETTINGS['sitehref'],
                at($section_config, 'radical', $section_name),
                at($GLOBAL_SETTINGS, 'sitemaps_filename_separator', '-'),
                $url_priority,
                $url_changefreq,
                at($GLOBAL_SETTINGS, 'use_gzip', true),         // at($section_config, 'use_gzip', true) && at($GLOBAL_SETTINGS, 'use_gzip', true), // для возможности использовать use_gzip локально
                $limit_bytes,
                $limit_urls);

            $t = microtime(true);

            $count = 0;

            foreach ($contentfile as $index => $string) {
                $lastmod = '';
                $location = sprintf( $section_config['url_location'], trim($string));

                $count++;
                $store->push( $location, $lastmod);
            }
            $store->stop();

            if ($IS_LOGGING) echo "[{$section_name}] : Generated {$count} sitemap URLs. Consumed time: ", round(microtime(true) - $t, 2), " sec.", PHP_EOL;
            $t = microtime(true);

            // сохраняем список файлов сайтмапа в индексный массив
            $index_of_sitemap_files = array_merge($index_of_sitemap_files, $store->getIndex());

            // деструктим инстанс сейвера
            unset($store);
            $store = null;

            break;
        } // end of 'file' case

        case 'csv': {
            // not implemented
            // загружаем CSV-файл в массив
            // построчно отдаем в Store->push() с соотв. локейшеном, апдейт-дейт и прочими значениями

            break;
        } // end of 'csv' case

        default: {
            echo "Unknown source for section {$section_name}", PHP_EOL;
            break;
        } // end of DEFAULT case

    } // end of switch
} // end of foreach section

if ($IS_LOGGING) echo PHP_EOL, 'Generating sitemap index. ', PHP_EOL;

SitemapFileSaver::createSitemapIndex(
    $GLOBAL_SETTINGS['sitemaps_href'],
    $GLOBAL_SETTINGS['sitemaps_storage'] . $GLOBAL_SETTINGS['sitemaps_mainindex'],
    $index_of_sitemap_files,
    'Today'
);
$dbi = null;

echo "Total spent time: ", round( microtime(true) - $stat_total_time, 2), " seconds. ", PHP_EOL;
