class SitemapSaver {
	
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
	private $sm_default_priority = 0.5;

	// частота изменения ссылки по умолчанию
	private $sm_default_changefreq = 'never';

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

	/**
	Конструктор класса. Устанавливает значения по умолчанию для данной секции.

	$domain - текущий домен (http://localhost/) с конечным слешем
	$name - имя файла карты, совпадает с именем секции
	$separator - сепаратор между именем карты и номером (-)
	$priority - приоритет по умолчанию
	$changefreq - частота обновления по умолчанию
	$use_gzip - использовать ли сжатие gzip
	*/
	public function __construct($domain, $name, $separator = '-', $priority, $changefreq, $use_gzip = true, $max_size = 50000000, $max_links = 50000)
	{
		$this->sm_name = $name;
		$this->sm_separator = $separator;
		$this->sm_default_priority = $priority;
		$this->sm_default_changefreq = $changefreq;
		$this->sm_use_gzip = $use_gzip;
	}
	
	// Запускает генерацию нового файла карты
	public function new()
	{
		// создаем инсанс XMLWriter
		$this->xmlw = new XMLWriter();

		// записываем стандартный заголовок
		$this->xmlw->openMemory();
		$this->xmlw->startDocument('1.0', 'UTF-8');
		$this->xmlw->setIndent(true);
		$this->xmlw->startElement('urlset');
		$this->xmlw->writeAttribute('xmlns', self::SCHEMA);

		// $this->buffer = $this->xmlw->flush(true); 
		
		// мы можем попробовать не хранить буфер отдельно, а во всех вызовах 
		// делать flush(false) для получения размера буфера

		// текущий размер буфера
		$this->buffer_size = $this->xmlw->flush(false);

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
			$this->new();
		}
		$this->xmlw->endElement();
		$this->xmlw->endDocument();

		$filename = $this->sm_name . $this->sm_separator . $this->sm_currentfile_number;

		// в зависимости от флага "используем упаковку" дополняем имя файла нужным
		// расширением и подготавливаем буфер

		if ($this->sm_use_gzip) {
			$filename .= '.xml.gz';
			$buffer = gzencode($this->xmlw->flush(true), 9);
		} else {
			$filename = .= '.xml';
			$buffer = $this->xmlw->flush(true);
		}

		// пишем в файл подготовленный буфер
		file_put_contents($filename, $this->xmlw->flush(true));

		// добавляем имя сгенерированного
		array_push( $this->sm_files_index, $filename);

		// удаляем текущий инстанс XMLWriter'а во избежание утечек памяти
		//@todo: тестирование, может быть просто = NULL?
		$this->xmlw = NULL;
		unset($this->xmlw);
 	}


 	// добавляет ссылку в сайтмап. Извне вызывается только эта функция!!!!
	public function push($location, $priority = NULL, $changefreq = NULL, $lastmod = NULL)
	{
		// проверяем, начат ли (открыт ли на запись) новый файл?
		if (! $this->xmlw instanceof XMLWriter) {
			// нет. Создаем новый файл
			$this->new();
		}
		
		// НА САМОМ ДЕЛЕ проверка сложнее - надо проверять размер буфера ИЛИ количество 
		// ссылок в текущем файле
		// проверяем, не превысило ли текущее количество ссылок в файле карты лимита?
		// если превысило - закрываем файл и открываем новый
		
		if (($this->xmlw->flush(false) >= $this->max_buffer_size)
			|| (($this->sm_currentfile_links_count % $this_max_links_count) === 0)) 
		{
			$this->stop();
			$this->new();
		}

		// добавляем в текущий файл элемент-ссылку на основе переданных параметров
		// увеличиваем на 1 количество ссылок в текущем файле (точнее буфере)
		$this->sm_currentfile_links_count++; 
		$this->xmlw->startElement('url');
		$this->xmlw->writeElement('loc', )





	}

	// возвращает список файлов сайтмапов для текущей секции
	public function getIndex()
	{
		return $this->sm_files_index;
	}

}