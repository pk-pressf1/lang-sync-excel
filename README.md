# Удобное хранение переводов в Excel таблице 

## Установка

1. Установите пакет `pk-engine/lang-sync-excel` через Composer:

```bash
composer require pk-engine/lang-sync-excel
```

2. Добавьте провайдер в `bootstrap/providers.php`:

```php
return [
    //
    \PkEngine\LangSyncExcel\Providers\LangSyncExcelProvider::class
];
```

3. Добавьте запись в .env файл:
```bash
LANG_SYNC_EXCEL_PATH=/path/or/url/excel/file.xlsx
```

## Простая работа

### Получить файл переводов

Команда получит переводы из файлов локализации и создаст Excel файл, в storage для дальнейшей работы.

```bash
php artisan lang:set
```


### Записать файл переводов

Команда получит файл переводов по ссылке, распарсит его и запишет в соответствующие файлы

```bash
php artisan lang:get
```

## Google Docs Spreadsheet

Для вашего удобства, вы можете хранить файл в Google Docs Spreadsheet. 
Для этого сгенерируйте Excel файл и откройте в Google Docs Spreadsheet. 
Нажмите на <Файл> -> <Поделиться> -> <Опубликовать в интернете>
Нажмите <Опубликовать> и выберите параметры <Весь документ> и <Microsoft Excel (XLSX)>


_Приятного пользования!_

## License

The LangSyncExcel package is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).
