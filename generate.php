<?php
require_once 'core.sitemapgen.php';
require_once 'class.DBConnectionLite.php';
require_once 'class.INI_config.php';
require_once 'class.sitemap_file_saver.php';

$db_config = new INI_Config('config.db.ini');
$dbi = new DBConnectionLite('sitemap', $db_config);

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

    echo_status_cli("<font color='red'>[{$section_name}]</font>");

    // инициализируем значения на основе конфига
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
        $limit_urls);

    // анализируем тип источника данных в конфиге секции
	switch ($section_config['source']) {
		case 'sql': {
			// get count
            $sth = $dbi->getconnection()->query( $section_config['sql_count_request'] );
			$sth_result = $sth->fetch();
			$url_count = $sth_result[ $section_config['sql_count_value'] ];

			// всего цепочек по $limit_urls в цепочке
			$chunks_count = (int)ceil($url_count / $limit_urls);

			// смещение в выборке
			$offset = 0;
			$t = microtime(true);

            // итерация по всем цепочкам
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
                    $lastmod    = $value[ $section_config['sql_data_lastmod']];
                    $location   = sprintf( $section_config['url_location'], $id);
                    $count++;
                    $store->push( $location, $lastmod );
                };
                array_walk($chunk_data, $sql_pusher);

                if ($IS_LOGGING) echo "> Generated sitemap URLs from offset ", str_pad($offset, 7, ' ', STR_PAD_LEFT), " and count ", str_pad($count, 7, ' ', STR_PAD_LEFT), ". Consumed time: ", round(microtime(true) - $t, 2), " sec.", PHP_EOL;
				$t = microtime(true);

				$offset += $limit_urls;

				// clear memory
				unset($sth);
			} // for each chunk
			$store->stop();

			// сохраняем список файлов сайтмапа в индексный массив
			$index_of_sitemap_files = array_merge($index_of_sitemap_files, $store->getIndex());
			
			break;
		} // end of 'sql' case

		case 'file': {
            $contentfile = file( $section_config['filename'] );
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

            if ($IS_LOGGING) echo "> Generated ", str_pad($count, 7, ' ', STR_PAD_LEFT), "  sitemap URLs. Consumed time: ", round(microtime(true) - $t, 2), " sec.", PHP_EOL;
            $t = microtime(true);

            // сохраняем список файлов сайтмапа в индексный массив
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
            if ($IS_LOGGING) echo "Unknown source for section {$section_name}", PHP_EOL;
			break;
		} // end of DEFAULT case
			
	} // end of switch

    // деструктим инстанс сейвера
    $store = null;
    unset($store);

    echo PHP_EOL;
} // end of foreach section

if ($IS_LOGGING) echo 'Generating sitemap index. ', PHP_EOL;

SitemapFileSaver::createSitemapIndex(
	$GLOBAL_SETTINGS['sitemaps_href'],
	$GLOBAL_SETTINGS['sitemaps_storage'] . $GLOBAL_SETTINGS['sitemaps_mainindex'],
	$index_of_sitemap_files,
	'Today'
	);
$dbi = null;

if ($IS_LOGGING) echo "Total spent time: ", round( microtime(true) - $stat_total_time, 2), " seconds. ", PHP_EOL;
if ($IS_LOGGING) echo 'Peak memory usage:', memory_get_peak_usage(true), PHP_EOL;