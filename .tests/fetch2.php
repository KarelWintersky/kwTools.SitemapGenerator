<?php
/**
 * User: Arris
 * Date: 13.09.2017, time: 20:02
 */

/*
 * Для работы перемещаем файл в корень
 * Этот файл тестирует извлечение данных из таблицы price и генерацию сайтмапа.
 * Используется прямая работа с XMLWrite без обёртки (и, соответственно, без проверки на максимальный размер файла)
*/


require_once 'core.sitemapgen.php';
require_once 'class.dbconnection.php';
require_once 'class.staticconfig.php';
require_once 'class.ini_config.php';
require_once 'class.sitemap_file_saver.php';

StaticConfig::init('config.db.ini');

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


// $store = new SitemapFileSaver('/tmp/', 'http://localhost/', 'test', '-', 0.5, 'never', true, 50000000, $url_limit);

$offset = 0;
$t = microtime(true);

// each chunk
for ($i = 0; $i < $chunks_count; $i++) {
    echo "Chunk # {$i} started. ", PHP_EOL;

    $xmlw = new \XMLWriter();
    $xmlw->openMemory();
    $xmlw->startDocument('1.0', 'UTF-8');
    $xmlw->setIndent(true);
    $xmlw->startElement('urlset');
    $xmlw->writeAttribute('xmlns', 'http://www.sitemaps.org/schemas/sitemap/0.9');

    $q_chunk = $qr . " LIMIT {$url_limit} OFFSET {$offset} ";
    echo "Chunk query = `{$q_chunk}` ", PHP_EOL;

    $sth = $dbi->getconnection()->query( $q_chunk );
    $chunk_data = $sth->fetchAll();
    if ($chunk_data) echo "Fetch result successfull. ", PHP_EOL;

    $count = 0;
    foreach ($chunk_data as $record) {
        $count++;
        $location = sprintf('price/%s.html', $record['id']);

        $xmlw->startElement('url');

        // location с учетом домена
        $xmlw->writeElement('loc', 'http://localhost/' . $location);
        $xmlw->writeElement('lastmod', SitemapFileSaver::format_date($record['lastmod']));
        $xmlw->endElement();
    }
    $xmlw->endElement();
    $xmlw->endDocument();
    $filename = 'test-' . $i . '.xml.gz';
    $buffer = gzencode($xmlw->flush(true), 9);
    file_put_contents('/tmp/' . $filename, $buffer);
    unset($xmlw);

    echo PHP_EOL, "Generated chunk from offset {$offset} and count {$count}, required time: ", round(microtime(true) - $t, 2), ' sec.' ,PHP_EOL;
    $t = microtime(true);

    $offset += $url_limit;

    // clear memory
    unset($sth);
}




