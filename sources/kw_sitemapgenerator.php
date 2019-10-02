<?php
/**
 * User: Karel Wintersky
 * Date: 18.09.2019
 */
const KWSMG_VERSION = '2.0.8';

require_once 'class.SitemapSystem.php';
require_once 'class.SitemapFileSaver.php';

// check SAPI status: script can be launched only from console
if (php_sapi_name() !== "cli") {
    die("KW's Sitemap Generator can't be launched in browser.");
}

$self_filename = basename($argv[0]); // get file basename

$cli_options = getopt('v::h::', ['verbose::', 'config:', 'help::']);

if (empty($cli_options) || key_exists('help', $cli_options) || key_exists('h', $cli_options)) {
    SitemapSystem::say_message('welcome', $self_filename);
    SitemapSystem::say_message('missing_config', $self_filename);
    die;
}
if (!key_exists('config', $cli_options) or empty($cli_options['config'])) {
    SitemapSystem::say_message('missing_config', $self_filename);
    die;
}

$argv_config_file = $cli_options['config'];
$argv_config_path = dirname($argv_config_file);

$sm_engine = new SitemapSystem($argv_config_file);

$GLOBAL_SETTINGS = $sm_engine->config_get_key('___GLOBAL_SETTINGS___');

$limit_urls  = $sm_engine->config_get_key('___GLOBAL_SETTINGS___/limit_urls', 50000);
$limit_bytes = $sm_engine->config_get_key('___GLOBAL_SETTINGS___/limit_bytes', 50000000);
$is_verbose_mode  = $sm_engine->config_get_key('___GLOBAL_SETTINGS___/logging', true) ||  key_exists('verbose', $cli_options);
$global_include_root_page = $sm_engine->config_get_key('___GLOBAL_SETTINGS___/include_root_page', false);

$sm_engine->config_remove_global_settings();

$all_sections = $sm_engine->config_get_all();

$index_of_sitemap_files = [];

if ($is_verbose_mode) SitemapSystem::say_message("<hr><strong>Generating sitemap</strong> based on <font color='yellow'>{$argv_config_file}</font>" . PHP_EOL);

$stat_total_time = microtime(true);

if ( $global_include_root_page ) {
    if (!is_string($global_include_root_page)) $global_include_root_page = 'root';

    $all_sections[ "__{$global_include_root_page}__" ] = [
        'enabled'       =>  1,
        'source'        =>  'root',
        'radical'       =>  $global_include_root_page,
        'url_priority'  =>  1,
        'url_changefreq'=>  'always'
    ];
}

// iterate all sections
foreach ($all_sections as $section_name => $section_config) {
    if (array_key_exists('enabled', $section_config) && $section_config['enabled'] == 0) continue;

    if ($is_verbose_mode) SitemapSystem::say_message("<font color='yellow'>[{$section_name}]</font>");

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
            if ($sm_engine->is_db_connected === false) {
                SitemapSystem::say_message("Нельзя использовать секцию с источником данных SQL - отсутствует подключение к БД");
                unset($store);
                continue; // next section
            }

            $sth = $sm_engine->pdo_connection->query( $section_config['sql_count_request'] );
            $sth_result = $sth->fetch();
            $url_count = $sth_result[ $section_config['sql_count_value'] ];

            $chunks_count = (int)ceil($url_count / $limit_urls);

            $offset = 0;
            $t = microtime(true);

            // iterate all chunks
            for ($i = 0; $i < $chunks_count; $i++) {
                $q_chunk = $section_config['sql_data_request'] . " LIMIT {$limit_urls} OFFSET {$offset} ";
                $sth = $sm_engine->pdo_connection->query( $q_chunk );
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

                if ($is_verbose_mode) SitemapSystem::say_message("+ Generated sitemap URLs from offset " . str_pad($offset, 7, ' ', STR_PAD_LEFT) . " and count " . str_pad($count, 7, ' ', STR_PAD_LEFT) . ". Consumed time: " . round(microtime(true) - $t, 2) .  " sec.");
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
                SitemapSystem::say_message("<font color='lred'>[ERROR]</font> File {$path_to_file} declared in section {$section_name}, option [filename] : not found!");
                SitemapSystem::say_message("<font color='lred'>This section will be ignored!</font>");
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
                if (trim($value) === '/') $value = '';
                $location = sprintf( $section_config['url_location'], trim($value));
                $store->push( $location, $section_lastmod );
                $count++;
            };
            array_walk($contentfile, $file_pusher, $section_lastmod);

            $store->stop();

            if ($is_verbose_mode) SitemapSystem::say_message("+ Generated " . str_pad($count, 7, ' ', STR_PAD_LEFT) . "  sitemap URLs. Consumed time: " . round(microtime(true) - $t, 2) . " sec.");
            $t = microtime(true);

            // save sitemap files list to index array
            $index_of_sitemap_files = array_merge($index_of_sitemap_files, $store->getIndex());

            break;
        } // end of 'file' case

        case 'root': {
            $url_count = 1;

            $section_lastmod
                = !array_key_exists('lastmod', $section_config) || ($section_config['lastmod'] === 'NOW()')
                ? time()
                : null;

            $count = 0;
            $t = microtime(true);

            /**
             * Callback function
             * @param $value
             * @param $index
             * @param $section_lastmod
             */
            $store->push( '', $section_lastmod );
            $count++;

            $store->stop();

            if ($is_verbose_mode) SitemapSystem::say_message("+ Generated " . str_pad($count, 7, ' ', STR_PAD_LEFT) . "  sitemap URLs. Consumed time: " . round(microtime(true) - $t, 2) . " sec.");
            $t = microtime(true);

            // save sitemap files list to index array
            $index_of_sitemap_files = array_merge($index_of_sitemap_files, $store->getIndex());

            break;
        }

        case 'csv': {
            // not implemented
            // загружаем CSV-файл в массив
            // построчно отдаем в Store->push() с соотв. локейшеном, апдейт-дейт и прочими значениями

            break;
        } // end of 'csv' case

        default: {
            if ($is_verbose_mode) SitemapSystem::say_message("<font color='lred'>[ERROR]</font> Unknown source type for section {$section_name}");
            break;
        } // end of DEFAULT case

    } // end of switch

    // destruct SAVER instance
    $store = null;
    unset($store);

    if ($is_verbose_mode) SitemapSystem::say_message();
} // end of foreach section

/*
 * @todo: добавить обработку $ ко второму параметру. Это означает, что sitemap.xml надо записывать в `sitemaps_storage`
 * // smth like :
  ( strpos($GLOBAL_SETTINGS['sitemaps_mainindex'], '$') !== FALSE )
? str_replace('$', $GLOBAL_SETTINGS['sitemaps_storage'], $GLOBAL_SETTINGS['sitemaps_mainindex'])
: $GLOBAL_SETTINGS['sitemaps_mainindex']
*/

SitemapFileSaver::createSitemapIndex(
    $GLOBAL_SETTINGS['sitemaps_href'],
    $GLOBAL_SETTINGS['sitemaps_mainindex'],
    $index_of_sitemap_files,
    'Today'
);
if ($is_verbose_mode) SitemapSystem::say_message("+ Generated sitemap index." . PHP_EOL);

$sm_engine->pdo_connection = null;

if ($is_verbose_mode) SitemapSystem::say_message("<strong>Finished.</strong>" . PHP_EOL);
if ($is_verbose_mode) SitemapSystem::say_message('Total spent time:  <strong>' . round( microtime(true) - $stat_total_time, 2) . '</strong> seconds. ');
if ($is_verbose_mode) SitemapSystem::say_message('Peak memory usage: <strong>' . (memory_get_peak_usage(true) >> 10) . '</strong> Kbytes. ' . PHP_EOL);

/* EOF */

