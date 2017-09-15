<?php
/**
 * User: Arris
 * Date: 13.09.2017, time: 20:02
 */

/*
 * Для работы перемещаем файл в корень
 * Этот файл тестирует извлечение данных из таблицы price и генерацию сайтмапа.
 * Используется вызов обёртки над XMLWriter'ом
 */


require_once 'core.sitemapgen.php';
require_once 'class.dbconnection.php';
require_once 'class.staticconfig.php';
require_once 'class.ini_config.php';
require_once 'class.sitemap_file_saver.php';

StaticConfig::init('db.ini');

$dbi = new DBConnection('main');

$qc = 'SELECT COUNT(id) AS cnt FROM price';
$qr = 'SELECT id, lastmod FROM price';
$url_limit = 50000;

$sth = $dbi->getconnection()->query( $qc );
$sth_result = $sth->fetch();
$url_count = $sth_result[ 'cnt' ];

$chunks_count = (int)ceil($url_count / $url_limit);
echo "Chunks count: {$chunks_count}", PHP_EOL;

// iterator per url_limit

$store = new SitemapFileSaver('/tmp/', 'http://localhost/', 'test', '-', 0.5, 'never', true, 50000000, $url_limit);

$offset = 0;
$t = microtime(true);
for ($i = 0; $i < $chunks_count; $i++) {
    echo "Chunk # {$i} started. ", PHP_EOL;

    $q_chunk = $qr . " LIMIT {$url_limit} OFFSET {$offset} ";

    echo "Chunk query = `{$q_chunk}` ", PHP_EOL;

    $sth = $dbi->getconnection()->query( $q_chunk );

    // iterate content
    $chunk_data = $sth->fetchAll();

    if ($chunk_data) echo "Fetch result successfull. ", PHP_EOL;

    $count = 0;
    foreach ($chunk_data as $record) {
        $count++;
        $loc = sprintf('price/%s.html', $record['id']);
        $store->push($loc, $record['lastmod']);
    }
    echo PHP_EOL, "Generated chunk from offset {$offset} and count {$count}", PHP_EOL;
    echo "Required time: ", round(microtime(true) - $t, 2), PHP_EOL;
    $t = microtime(true);

    $offset += $url_limit;

    // clear memory
    unset($sth);
}
$store->stop();

echo 'Затраты времени на проверку длины буфера: ', round( $store->debug_checkbuffer_time, 2), PHP_EOL;

unset($store);




