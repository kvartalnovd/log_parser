<?php

namespace core;


class Parser {

	function __construct() {
		// Вытягиваем конфиг с поисковыми роботами различных поисковых систем
		$this->spiderbots = require 'spiderbots_config.php';
		$this->crawlers = array();
	}

	public function IPSearch($log) {
		// Функция поиска IP-адрес клиента в логе с помошью регулярного выражения
		$IP_regex = "/^\d{1,3}\.\d{1,3}\.\d{1,3}\.\d{1,3}/";
		preg_match($IP_regex, $log, $found_IPs);

		return $found_IPs[0];
	}

	public function UrlSearch($log) {
		// Функция поиска url с помощью регулярного выражения
		$url_regex = '/(http|https|ftp):\/\/[a-z0-9_-]+(\.[a-z0-9_-]+)*\.[a-z]{2,5}\/[^"]*/';
		preg_match($url_regex, $log, $client_url);

		return $client_url[0];
	}

	public function RequestParsing($log) {
		// Функция поиска и разбора запроса из лога

		// Получаем информацию о запросе: тип запроса, его содержимое и код ответа
		$request_info_regex = '/"(GET|POST).+(HTTP\/1.1|HTTP\/1.0)" \w{3} \w+/'; // 
		preg_match($request_info_regex, $log, $found_request_info);

		// Удаляем из полученной строки кавычки и делим по пробелам, получаем все нужные данные о запросе в виде массива
		$request_info = explode(" ", str_replace('"', '', $found_request_info[0]));

		return $request_info;
	}

	public function UserAgentParsing($log) {
		// Функция поиска и разбора UserAgent'a из лога

		// Получаем User-Agent
		$user_agent = str_replace('"', '', explode('" "', $log)[1]);
		
		// Находим в useragent'е поискового роботов
		foreach($this->spiderbots as $search_engine => $crawler_names){
			if (!array_key_exists($search_engine, $this->crawlers)) {
				// В массиве найденных поисковых роботов нет полученного в данном useraget'е, объявляем его, чтобы избежать ошибки
				$this->crawlers[$search_engine] = 0;
			}

			// Проверяем каждого поискового робота, каждой поисковой системы, пока не найдем, использованного в запросе
			foreach($crawler_names as $crawler_name){
				if (stripos($user_agent, $crawler_name)) {
					// Поисковый робот найден, записываем его и завершаем цикл
					$this->crawlers[$search_engine]++;
					return;
				}
			}
		}
	}
}