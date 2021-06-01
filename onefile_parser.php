<?php

if ($argc > 2) {
	exit("ERROR: Слишком много параметров! Нужно передать только имя лог-файла. Например: access_log\n");
} else if ($argc == 1) {
	exit("ERROR: Необходимо передать имя лог-файла. Например: access_log\n");
}

// Создаём константу - путь к файлу логов
define("ACCESS_LOG_PATH", $argv[1]);

$traffic = 0;
$urls = array();
$status_codes = array();
$crawlers = array();

// Количество найденных пустых строк в файле лога
$empty_rows_number = 0;

// Поисковые роботы различных поисковых систем
$spiderbots = array(
	"Google" => array(
		"Googlebot",
		"Googlebot-Image",
	),
	"Bing" => array(
		"Bingbot",
		"AdIdxBot",
		"BingPreview",
	),
	"Baidu" => array(
		"Baiduspider",
		"Baiduspider-image",
		"Baiduspider-image",
		"Baiduspider-video",
		"Baiduspider-news",
		"Baiduspider-favo",
		"Baiduspider-cpro",
		"Baiduspider-ads",
	),
	"Yandex" => array(
		"Yandex",
		"YandexBot",
	),
	"Yahoo!" => array(
		"Slurp",
		"Yahoo! Slurp",
	),
);

// Проверяем указанный пользователем файл
if (file_exists(ACCESS_LOG_PATH) and is_file(ACCESS_LOG_PATH)) {
	// Файл логов найден
	echo "\nОбработка файла \"".ACCESS_LOG_PATH."\":\n";

	// Вычисляем размер полученного файла 
	$logfile_size = filesize(ACCESS_LOG_PATH);
	$logfile_size = round($logfile_size / 1024, 2); // Переводим для удобства в килобайты
	echo " - Размер файла: ".$logfile_size ."КБ \n";

	// Получаем дату обращение к файлу
	$logfile_last_date = date("F d Y.", fileatime(ACCESS_LOG_PATH));
	echo " - Дата обращения к логу: ".$logfile_last_date ."\n";

	// Открываем файл логов для чтения
	$logfile = fopen(ACCESS_LOG_PATH, 'r') or die("ERROR: Ошибка открытия файла ".ACCESS_LOG_PATH."\n");

	// Определяем число строк в лог-файле
	$logfile_count = count(file(ACCESS_LOG_PATH));
	echo " - Количество строк в лог-файле: ".$logfile_count ."\n\n";
	echo "Обработка логов:\n";

	// $load = 1; // Объявляем первую итерацию для работы статусбара
	// Запускаем цикл, прекращающий работу когда достигнут конец файла, таким образом можем обрабоатть большой объем данных
	for ($load = 1; $load <= $logfile_count; $load++) {

		// Статус бар 
		$percent = round($load * 100 / $logfile_count); // Процент выполнения обработки логов
		$progress = $load." / ".$logfile_count; // Сколько логов обработано из всех в файле

		$status = round($percent / 2); // Кол-во заполненых ячеек статус бара из 50 возможных
		$statusbar = str_repeat("█", $status); // Заполненые строки
		$remains = str_repeat(" ", 50 - $status); // Пустые строки

		echo "   Загрузка: ".$percent."% | ".$statusbar.$remains." | ".$progress." логов обработано ";
		echo ($load != $logfile_count) ? "\r" : "\n";
		sleep(1); // Для примера добавляем ожидание 1 сек. Слишком мало логов и не видно работу статус бар

		// Обрабатываем каждую строку файла как лог
		$log = fgets($logfile);
		
		if ($log == 0) {
			// Строка пустая, пропускаем
			$empty_rows_number++;
			continue;
		}

		// Находим IP-адрес клиента в логе с помошью регулярного выражения
		$IP_regex = "/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/";
		preg_match($IP_regex, $log, $found_IPs);
		$client_ip = $found_IPs[0];

		// Находим url с помощью регулярного выражения
		$url_regex = '/(http|https|ftp):\/\/[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,5}\/[^"]*/';
		preg_match($url_regex, $log, $client_url);
		
		// Получаем информацию о запросе: тип запроса, его содержимое и код ответа
		$request_info_regex = '/"(GET|POST).+(HTTP\/1.1|HTTP\/1.0)" \w{3} \w+/'; // 
		preg_match($request_info_regex, $log, $found_request_info);

		// Удаляем из полученной строки кавычки и делим по пробелам, получаем все нужные данные о запросе в виде массива
		$request_info = explode(" ", str_replace('"', '', $found_request_info[0]));

		// Разбираем массив на части запроса
		$request_method = $request_info[0]; // Метод запроса
		$request_url = $request_info[1]; // URL запроса
		$protocol_HTTP = $request_info[2]; // протокол HTTP запроса
		$status_code = $request_info[3]; // код состояния HTTP
		$received_bytes_number = $request_info[4]; // Количество отданных сервером байт;

		if (!array_key_exists($status_code, $status_codes)) {
			// В массиве кодов ответов запроса нет полученного в данном логе кода, объявляем его, чтобы избежать ошибки
			$status_codes[$status_code] = 0;
		}

		// Сохраняем данные URL-запроса
		$urls[] = $request_url;

		// Сохраняем данные кода ответа в массив
		$status_codes[$status_code]++;

		// Сохраняем данные трафика
		if ($status_code === "200") {
			// Сохраняем данные трафика, Если статус запроса: 200 OK — успешный запрос
			$traffic += $received_bytes_number;
		}

		// Получаем User-Agent
		$user_agent = str_replace('"', '', explode('" "', $log)[1]);
		
		// Находим в useragent'е поискового роботов
		$is_crawler_found = false;
		foreach($spiderbots as $search_engine => $crawler_names){
			if (!array_key_exists($search_engine, $crawlers)) {
				// В массиве найденных поисковых роботов нет полученного в данном useraget'е, объявляем его, чтобы избежать ошибки
				$crawlers[$search_engine] = 0;
			}

			// Проверяем каждого поискового робота, каждой поисковой системы, пока не найдем, использованного в запросе
			foreach($crawler_names as $crawler_name){
				if (stripos($user_agent, $crawler_name)) {
					// Поисковый робот найден, записываем его и завершаем цикл
					$is_crawler_found = true;
					$crawlers[$search_engine]++;
					break;
				}
			}

			if ($is_crawler_found) {
				// Для данного UserAgent'а поисковый робот уже найден, завершаем цикл, чтобы сэкономить производительность
				break;
			}
		}

		// if ($load == 7) {
		// 	exit();
		// }
	}

	// Закрываем файл логов
	fclose($logfile);

} else {
	// Файл не найден. Показываем ошибку и завершаем работу
	exit("ERROR: ".ACCESS_LOG_PATH." не существует или не является файлом. Повторите попытку!\n");
}

$unique_status_codes = array_unique($status_codes);

// Получаем массив только уникальных ссылок
$unique_urls = array_unique($urls);

 // Получаем кол-во просмотров
$views = $logfile_count - $empty_rows_number;

$logs_statistics = array(
	"views" => $views,
	"urls" => count($unique_urls),
	"traffic" => $traffic,
	"crawlers" => $crawlers,
	"statusCodes" => $status_codes
);

echo  "\nРезультат в формате JSON:\n".json_encode($logs_statistics, JSON_PRETTY_PRINT)."\n";