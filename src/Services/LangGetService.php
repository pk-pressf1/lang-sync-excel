<?php

namespace PkEngine\LangSyncExcel\Services;

use Exception;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class LangGetService extends LangService
{
    protected string $url;

    protected Spreadsheet $spreadsheet;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->url = config('lang-sync-excel.excel_url', '');
        $this->locales = $this->getLocales();
        if(!$this->url){
            throw new Exception('LangSyncExcel: excel_url не задан в config/lang-sync-excel.php OR в .env LANG_SYNC_EXCEL_URL');
        }

        parent::__construct();
    }

    /**
     * @throws Exception
     */
    public function parseFromUrl(): void
    {
        if(!$this->getExcel()){
            throw new Exception('LangSyncExcel: не удалось получить excel файл');
        }

        $this->spreadsheet = IOFactory::load($this->storage->path('/lang/lang_temp.xlsx'));
        $this->fileList = $this->getFileList();
        foreach($this->spreadsheet->getAllSheets() as $sheet){
            $data = $this->getData($sheet);
            $header = array_shift($data);
            $fileLabel = $sheet->getTitle();
            if(!$header) throw new Exception('LangSyncExcel: не удалось получить заголовок из таблицы');
            foreach ($header as $i => $locale) {
                if($this->locales->contains($locale)){
                    $dataList = collect($data)->mapWithKeys(fn($item) => [$item[0] => $item[$i]])->toArray();
                    $this->saveLang($dataList, $locale, $fileLabel);
                }
            }
        }
    }

    /**
     * Получаем данные из таблицы
     * @param Worksheet $sheet
     * @return array
     */
    public function getData(Worksheet $sheet): array
    {
        $data = []; // Создаем пустой массив для хранения данных
        foreach ($sheet->getRowIterator() as $row) {
            $rowData = [];
            $cellIterator = $row->getCellIterator();
            $cellIterator->setIterateOnlyExistingCells(FALSE); // Итерироваться по всем ячейкам
            foreach ($cellIterator as $cell) {
                $rowData[] = $cell->getValue(); // Получаем значение ячейки
            }
            $data[] = $rowData; // Добавляем строку в массив данных
        }
        return $data;
    }

    /**
     * Сохранение Excel в файл
     * @return bool
     */
    public function getExcel(): bool
    {
        $file = Http::get($this->url)->body();
        return $this->storage->put('lang/lang_temp.xlsx', $file);
    }

    /**
     * Получаем список файлов
     * @return Collection
     */
    protected function getFileList(): Collection
    {
        return collect($this->spreadsheet->getAllSheets())->map(fn ($sheet) => $sheet->getTitle());
    }


    /**
     * Сохраняем данные в файл
     * @param array $data
     * @param string $locale
     * @param string $fileLabel
     * @throws Exception
     */
    protected function saveLang(array $data, string $locale, string $fileLabel): void
    {
        $data = Arr::undot($data);
        $path = "$locale/$fileLabel.php";
        $lines = $this->recursivePhpExport($data);
        $lines = $this->closePhpExport($lines);
        $export = implode("\n", $lines);
        if(!File::exists(lang_path("$locale"))){
            if(!File::makeDirectory(lang_path("$locale"))){
                throw new Exception('LangSyncExcel: не удалось создать папку: ' . lang_path("$locale"));
            };
        }
        if(!File::put(lang_path($path), $export)){
            throw new Exception('LangSyncExcel: не удалось сохранить файл: ' . lang_path($path));
        };
    }

    /**
     * Рекурсивно создаем массив для php файла
     * @param array $arr
     * @param int $i
     * @return array
     */
    protected function recursivePhpExport(array $arr, int $i = 1): array
    {
        $tab = '';
        for($n = 1; $n <= $i; $n++){
            $tab .= '    ';
        }
        if($i === 1){
            $lines = ['<?php', 'return ['];
        }else{
            $lines = [];
        }
        foreach($arr as $key => $item){
            if(!$key){
                continue;
            }
            if(is_array($item)){
                $lines[] = $tab.'"'.$key.'" => [';
                $subLines = $this->recursivePhpExport($item, $i + 1);
                $lines = array_merge($lines, $subLines);
                $lines[] = $tab.'],';
            }else{
                $item = str_replace('"', '\"', $item);
                $lines[] = $tab.'"'.$key.'" => "'.$item.'",';

            }

        }
        return $lines;
    }

    /**
     * @param array $arr
     * @return array
     */
    protected function closePhpExport(array $arr): array
    {
        $arr[] = '];';
        return $arr;
    }
}
