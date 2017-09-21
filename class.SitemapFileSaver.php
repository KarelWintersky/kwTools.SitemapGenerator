<?php

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
		$date_format_type = 'YMD'
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