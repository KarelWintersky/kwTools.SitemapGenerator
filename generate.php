<?php
require_once 'core.sitemapgen.php';
require_once 'class.dbconnection.php';
require_once 'class.staticconfig.php';
require_once 'class.ini_config.php';
require_once 'class.sitemap_file_saver.php';

StaticConfig::init('db.ini');

$dbi = new DBConnection('main');

$sm_config = new INI_Config('_sitemap.ini');

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