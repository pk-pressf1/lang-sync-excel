<?php

namespace PkEngine\LangSyncExcel\Services;

use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Http;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PkEngine\LangSyncExcel\Builders\JsonFileBuilder;
use PkEngine\LangSyncExcel\Builders\PhpFileBuilder;

class LangGetService extends LangService
{
    protected string $url;

    protected Spreadsheet $spreadsheet;

    protected ?OutputStyle $output = null;

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
    public function parseFromUrlToPhp(): void
    {
        $this->parseFromUrl();
        $this->parseExcel(
            fn (
                array $dataList,
                string $locale,
                string $fileLabel
            ) => $this->saveLangPhp($dataList, $locale, $fileLabel)
        );

    }

    /**
     * @throws Exception
     */
    public function parseFromUrlToJson(): void
    {
        $this->parseFromUrl();
        $locales = [];
        $this->parseExcel(function (
                array $dataList,
                string $locale,
                string $fileLabel
            ) use (&$locales){

            if(!isset($locales[$locale]) || !is_array($locales[$locale])){
                $locales[$locale] = [];
            }
            if(!isset($locales[$locale][$fileLabel]) ||!is_array($locales[$locale][$fileLabel])){
                $locales[$locale][$fileLabel] = [];
            }
            $locales[$locale][$fileLabel] = array_merge($locales[$locale][$fileLabel], Arr::undot($dataList));
        }
        );
        foreach ($locales as $locale => $data) {
            $this->saveLangJson($data, $locale);
        }
    }

    private function parseExcel(\Closure $closure): void
    {
        foreach($this->spreadsheet->getAllSheets() as $sheet){
            $data = $this->getData($sheet);
            $header = array_shift($data);
            $fileLabel = $sheet->getTitle();
            if(!$header) throw new Exception('LangSyncExcel: не удалось получить заголовок из таблицы');
            foreach ($header as $i => $locale) {
                if($this->locales->contains($locale)){
                    $dataList = collect($data)->mapWithKeys(fn($item) => [$item[0] => $item[$i]])->toArray();
                    $closure($dataList, $locale, $fileLabel);
                }
            }
        }
    }

    /**
     * @throws Exception
     */
    private function parseFromUrl(): void
    {
        if(!$this->getExcel()){
            throw new Exception('LangSyncExcel: не удалось получить excel файл');
        }

        $this->spreadsheet = IOFactory::load($this->storage->path('/lang/lang_temp.xlsx'));
        $this->fileList = $this->getFileList();

    }

    /**
     * Получаем данные из таблицы
     * @param Worksheet $sheet
     * @return array
     */
    private function getData(Worksheet $sheet): array
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
    private function getExcel(): bool
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
     * Сохраняем данные в файл php
     * @param array $data
     * @param string $locale
     * @param string $fileLabel
     * @throws Exception
     */
    protected function saveLangPhp(array $data, string $locale, string $fileLabel): void
    {
        $builder = new PhpFileBuilder($data, $locale, $fileLabel);
        $builder->setOutput($this->output);
        $builder->build();
    }

    /**
     * Сохраняем данные в json
     * @throws Exception
     */
    protected function saveLangJson(array $data, string $locale): void
    {
        $builder = new JsonFileBuilder($data, $locale);
        $builder->setOutput($this->output);
        $builder->build();
    }

    public function setOutput(OutputStyle $output): void
    {
        $this->output = $output;
    }
}
