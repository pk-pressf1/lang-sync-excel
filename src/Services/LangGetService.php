<?php

namespace PkEngine\LangSyncExcel\Services;

use Exception;
use Illuminate\Console\OutputStyle;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;
use PkEngine\LangSyncExcel\Builders\ArbFileBuilder;
use PkEngine\LangSyncExcel\Builders\JsonFileBuilder;
use PkEngine\LangSyncExcel\Builders\PhpFileBuilder;
use PkEngine\LangSyncExcel\Providers\GoogleProvider;
use PkEngine\LangSyncExcel\Providers\YandexProvider;

class LangGetService extends LangService
{
    protected string $url;

    protected Spreadsheet $spreadsheet;

    protected ?OutputStyle $output = null;

    protected string $provider;

    /**
     * @throws Exception
     */
    public function __construct()
    {
        $this->url = config('lang-sync-excel.excel_url', '');
        $this->provider = config('lang-sync-excel.provider', 'google');
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
        $this->parseExcel(
            function (array $dataList, string $locale, string $fileLabel) {
                $builder = new PhpFileBuilder($dataList, $locale, $fileLabel);
                $builder->setOutput($this->output);
                $builder->build();
            }
        );

    }

    /**
     * @throws Exception
     */
    public function parseFromUrlToJson(): void
    {
        $locales = $this->prepareFlat();
        foreach ($locales as $locale => $data) {
            $builder = new JsonFileBuilder($data, $locale);
            $builder->setOutput($this->output);
            $builder->build();
        }
    }


    /**
     * @throws Exception
     */
    public function parseFromUrlToArb(): void
    {
        $locales = $this->prepareFlat();
        foreach ($locales as $locale => $data) {
            $builder = new ArbFileBuilder($data, $locale);
            $builder->setOutput($this->output);
            $builder->build();
        }
    }

    /**
     * @throws Exception
     */
    private function prepareFlat(): array
    {
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
        return $locales;
    }

    /**
     * @throws Exception
     */
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
    public function parseFromUrl(): void
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
     * @throws Exception
     */
    private function getExcel(): bool
    {

        $file = match ($this->provider){
            'google' => app(GoogleProvider::class)->handler($this->url),
            'yandex' => app(YandexProvider::class)->handler($this->url),
            default => null
        };
        if(!$file){
            throw new Exception('LangSyncExcel: не удалось получить файл Excel');
        }
        $this->output->info('Получение файла Excel ' . $this->url);
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

    public function setOutput(OutputStyle $output): void
    {
        $this->output = $output;
    }
}
