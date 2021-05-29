## Парсер access.log файлов
# Обрабатывает записи обращений к сайту


> Это [тестовое задание](https://docs.google.com/document/d/1M0ao6kx9Pb0oFGKyLlQXh6RdUKK-FdNpwLr_E5y1x-c/edit) на вакансию [PHP Developer](https://hh.ru/vacancy/41712942):
> Имеется обычный http access_log файл.
> Требуется написать PHP скрипт, обрабатывающий этот лог и выдающий информацию о нём в json виде.
>
> Требуемые данные:
>  - количество хитов/просмотров;
>  - количество уникальных url;
>  - объем трафика;
>  - количество строк всего;
>  - количество запросов от поисковиков;
>  - коды ответов;

# Запуск
> Для запуска вам потребуется PHP!
Клонируйте репозиторий при помощи git и перейдите в папку:
    ```bash
    git clone https://github.com/kvartalnovd/log_parser.git
    cd log_parser
    ```
Вы можете запустить программу написанную одним скриптом:
    ```bash
    php onefile_parser.php access_log
    ```
Или написанную с использованием ООП:
    ```bash
    php main.php access_log
    ```

> Они одинаковы, но написаны в разных стилях

<!-- # Пример выполнения скрипта
![Результат обработки файла access_log](http://webdesign.ru.net/images/Heydon_min.jpg) -->