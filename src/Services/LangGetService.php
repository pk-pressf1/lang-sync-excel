<?php

namespace PKEngine\LangSyncExcel\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use GuzzleHttp\Client;
use Symfony\Component\Finder\SplFileInfo;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
class LangGetService extends LangService
{
    protected string $url;

    protected Spreadsheet $spreadsheet;

    public function __construct()
    {
        $this->url = config('lang-sync-excel.excel_url', '');
        $this->locales = $this->getLocales();
        if(!$this->url){
            throw new \Exception('LangSyncExcel: excel_url не задан в config/lang-sync-excel.php OR в .env LANG_SYNC_EXCEL_URL');
        }

        parent::__construct();
    }

    public function parseFromUrl()
    {
        if(!$this->getExcel()){
            throw new \Exception('LangSyncExcel: не удалось получить excel файл');
        }

        $this->spreadsheet = IOFactory::load($this->storage->path('/lang/lang_temp.xlsx'));
        $this->fileList = $this->getFileList();

        foreach($this->spreadsheet->getAllSheets() as $sheet){
            $data = $this->getData($sheet);
            $header = array_shift($data);
            $fileLabel = $sheet->getTitle();
            if(!$header) throw new \Exception('LangSyncExcel: не удалось получить заголовок из таблицы');
            foreach ($header as $i => $locale) {
                if($this->locales->contains($locale)){
                    $dataList = collect($data)->mapWithKeys(fn($item) => [$item[0] => $item[$i]])->toArray();
                    $this->saveLang($dataList, $locale, $fileLabel);
                }
            }
        }
    }

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

    public function getExcel(): bool
    {
        $file = Http::get($this->url)->body();
        return $this->storage->put('lang/lang_temp.xlsx', $file);
    }

    protected function getFileList(): Collection
    {
        return collect($this->spreadsheet->getAllSheets())->map(fn ($sheet) => $sheet->getTitle());
    }


    protected function saveLang(array $data, string $locale, string $fileLabel)
    {
        $data = Arr::undot($data);
        $path = "$locale/$fileLabel.php";
        $lines = $this->recursivePhpExport($data);
        $lines = $this->closePhpExport($lines);
        $export = implode("\n", $lines);
        if(!File::exists(lang_path("$locale"))){
            File::makeDirectory(lang_path("$locale"));
        }
        File::put(lang_path($path), $export);
    }

    protected function recursivePhpExport(array $arr, $i = 1): array
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

    protected function closePhpExport(array $arr): array
    {
        $arr[] = '];';
        return $arr;
    }
}
