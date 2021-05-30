<?php

namespace core;
require 'parser.php';


class Main {

	public $parser;
	public $unique_status_codes;
	public $traffic;
	public $views; 
	public $status_codes;
	public $logfile;

    function __construct() {

    	// Вызывем функцию проверки аргументов
    	$this->checking_argc();

    	// Вводим начальные данные
    	$this->parser = new Parser();
		$this->unique_urls = array();
		$this->traffic = 0;
		$this->views = 0; 
		$this->unique_status_codes = array();
		$this->processing();
	}

	public function processing() {

		if (file_exists(ACCESS_LOG_PATH) and is_file(ACCESS_LOG_PATH)) {
			// Файл логов найден
			$this->processing_logfile();
			
			// Локальные переменные для временного хранения Ссылок, кодов ответа и количества неправильных строк в лог-файле
			$urls = array();
			$status_codes = array();
			$empty_rows_number = 0;

			echo "Обработка логов:\n";

			// Запускаем цикл, прекращающий работу когда достигнут конец файла
			for ($load = 1; $load <= $this->logfile_count; $load++) {
				
				// Статус бар 
				$percent = round($load * 100 / $this->logfile_count); // Процент выполнения обработки логов
				$progress = $load." / ".$this->logfile_count; // Сколько логов обработано из всех в файле

				$status = round($percent / 2); // Кол-во заполненых ячеек статус бара из 50 возможных
				$statusbar = str_repeat("█", $status); // Заполненые строки
				$remains = str_repeat(" ", 50 - $status); // Пустые строки

				echo "   Загрузка: ".$percent."% | ".$statusbar.$remains." | ".$progress." логов обработано ";
				echo ($load != $this->logfile_count) ? "\r" : "\n";
				sleep(1); // Для примера добавляем ожидание 1 сек. Слишком мало логов и не видно работу статус бар

				// Обрабатываем каждую строку файла как лог
				$log = fgets($this->logfile);
				
				if ($log == 0) {
					// Строка пустая, пропускаем
					$empty_rows_number++;
					continue;
				}

				// Находим IP-адрес клиента
				$client_ip = $this->parser->IPSearch($log);

				// Находим url
				$urls[] = $this->parser->UrlSearch($log);

				// Получаем информацию о запросе: тип запроса, его содержимое и код ответа
				$request_info = $this->parser->RequestParsing($log);

				// Разбираем массив на части запроса
				$request_method = $request_info[0]; // Метод запроса
				$status_code = $request_info[3]; // код состояния HTTP
				$received_bytes_number = $request_info[4]; // Количество отданных сервером байт;

				if (!array_key_exists($status_code, $status_codes)) {
					// В массиве кодов ответов запроса нет полученного в данном логе кода, объявляем его, чтобы избежать ошибки
					$status_codes[$status_code] = 0;
				}

				// Сохраняем данные кода ответа в массив
				$status_codes[$status_code]++;

				// Сохраняем данные трафика
				$this->traffic += $received_bytes_number;

				// Разбираем UserAgent и ищем поисковых роботов
				$this->parser->UserAgentParsing($log);

			}

			// Получаем униакльные коды статусов
			$this->unique_status_codes = array_unique($status_codes);

			// Получаем массив только уникальных ссылок
			$this->unique_urls = array_unique($urls);

			 // Получаем кол-во просмотров
			$this->views = $this->logfile_count - $empty_rows_number;

			$this->show_result();

			// Работа с лог-файлом закончена, закрываем файл
			$this->close_logfile();
		}

		else {
			// Файл не найден. Показываем ошибку и завершаем работу
			exit("ERROR: ".ACCESS_LOG_PATH." не существует или не является файлом. Повторите попытку!\n");
		}
	}

	public function processing_logfile() {
		// Функция обработки лог-файла: получает информацию о файле и открывает его
		echo "\nОбработка файла \"".ACCESS_LOG_PATH."\":\n";

		// Вычисляем размер полученного файла 
		$logfile_size = filesize(ACCESS_LOG_PATH);
		$logfile_size = round($logfile_size / 1024, 2); // Переводим для удобства в килобайты
		echo " - Размер файла: ".$logfile_size ."КБ \n";

		// Получаем дату обращение к файлу
		$logfile_last_date = date("F d Y.", fileatime(ACCESS_LOG_PATH));
		echo " - Дата обращения к логу: ".$logfile_last_date ."\n";

		// Открываем файл логов для чтения
		$this->logfile = fopen(ACCESS_LOG_PATH, 'r') or die("ERROR: Ошибка открытия файла ".ACCESS_LOG_PATH."\n");

		// Определяем число строк в лог-файле
		$this->logfile_count = count(file(ACCESS_LOG_PATH));
		echo " - Количество строк в лог-файле: ".$this->logfile_count ."\n\n";
	}

	public function close_logfile() {
		// Функция закрытия лог-файла
		fclose($this->logfile);
	}

	public function checking_argc() {
		// Функция проверки и обработки полученных из консоли аргументов
		global $argc, $argv;

		if ($argc > 2) {

			exit("ERROR: Слишком много параметров! Нужно передать только имя лог-файла. Например: access_log\n");
		}

		elseif ($argc == 1) {

			exit("ERROR: Необходимо передать имя лог-файла. Например: access_log\n");
		}

		// Создаём константу - путь к файлу логов
    	define("ACCESS_LOG_PATH", $argv[1]);
	}

    public function show_result() {
    	$logs_statistics = array(
			"views" => $this->views,
			"urls" => count($this->unique_urls),
			"traffic" => $this->traffic,
			"crawlers" => $this->parser->crawlers,
			"statusCodes" => $this->unique_status_codes
		);

		echo  "Результат в формате JSON:\n".json_encode($logs_statistics, JSON_PRETTY_PRINT)."\n";
    }
}

new Main();
