<?php

require_once 'core.sitemapgen.php';
require_once 'class.dbconnection.php';
require_once 'class.staticconfig.php';
require_once 'class.ini_config.php';
require_once 'class.sitemap_file_saver.php';

StaticConfig::init('db.ini');

$dbi = new DBConnection('main');

$sm_config = new INI_Config('sitemap.ini');

$GLOBAL_SETTINGS = $sm_config->get('___');
$sm_config->delete('___');
$all_sections = $sm_config->getAll();

// итерируем все секции

$index_with_sitemap_files = array();

foreach ($all_sections as $section_name => $section_config) {
	// сначала сделаем блок для SQL, он сложнее

	// судя по всему, основные значения нужно грузить здесь, вне свитча

	switch ($section_config['source']) {
		case 'sql': {
			$sth = $dbi->getconnection()->query( $section_config['sql_count_request'] );
			$sth_result = $sth->fetch();
			$source_count = $sth_result[ $section_config['sql_count_value'] ];

			$limit_urls  = at($GLOBAL_SETTINGS, 'limit_urls', 50000);
			$limit_bytes = at($GLOBAL_SETTINGS, 'limit_bytes', 50000000);

			$url_priority = at($section_config, 'url_priority', 0.5);
			$url_changefreq = at($section_config, 'url_changefreq', 'never');

			$store = new SitemapFileSaver(
				$GLOBAL_SETTINGS['sitemaps_storage'],
				$GLOBAL_SETTINGS['sitehref'],
				at($section_config, 'radical', $section_name),
				at($GLOBAL_SETTINGS, 'sitemaps_filename_separator', '-'),
				$url_priority,
				$url_changefreq,
				at($GLOBAL_SETTINGS, 'use_gzip', true),
				$limit_bytes,
				$limit_urls);

			// теперь итератор по кускам в $GLOBAL_SETTINGS['limit_urls']

			// смещение в выборке
			$offset = 0; 
			// количество выборок (отладка!, см код RPR)
			$chunks_count = 1 + $source_count/$limit_urls;  

			// вот этот блок кода написан на скорую руку и 100% некорректно
			// его нужно переписать и отладить 
			
			for ($i=0; $i < $chunks_count; $i++) {
 				$q_data = $section_config['sql_data_request'] . ' LIMIT {$limit_urls} OFFSET {$n}';
 				$sth = $dbi->getconnection()->query($q_data);

 				$chunk = $sth->fetchAll();

 				// теперь итерируем цепочку
 				array_walk($chunk, function($index, $value) use ($section_config, $store){
 					$id = $value[ $section_config['sql_data_id']];
 					$lastmod = $value[ $section_config['sql_data_lastmod']];
 					$location = sprintf( $section_config['url_location'], $id);

 					// $location, $lastmod = NULL, $priority = NULL, $changefreq = NULL
 					$store->push( $location, $lastmod );
 				});
			}

			// сохраняем список файлов сайтмапа в индексный массив
			$index_with_sitemap_files = array_merge($index_with_sitemap_files, 
													$store->getIndex());
			
			// деструктим инстанс сейвера
			$store = null;
			unset($store); 

			break;
		} // end of 'sql' case

		case 'file': {

			// загружаем значения из конфига
			// Загружаем все строчки из файла
			// отдаем их в Store->push() построчно с соотв. локейшеном

			// обновляем $index_with_sitemap_files

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

createSitemapIndex($GLOBAL_SETTINGS['sitemaps_storage'],
	$GLOBAL_SETTINGS['sitemaps_mainindex'],
	$index_with_sitemap_files,
	'Today'
	);

$dbi = null;