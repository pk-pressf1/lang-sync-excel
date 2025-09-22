<?php

namespace PkEngine\LangSyncExcel\Services;

use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Finder\SplFileInfo;
use Illuminate\Contracts\Filesystem\Filesystem;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Cell\Coordinate;

class LangSetService extends LangService
{
    protected array $phrases;

    public function __construct()
    {
        $this->locales = $this->getLocales();
        $this->fileList = $this->getFileList();
        parent::__construct();
    }

    public function storeExcelToFile(): void
    {

        $this->setPhrases();
        $spreadsheet = $this->makeExcel();
        $this->storeExcel($spreadsheet);

    }

    /**
     * Получение фраз для записи в таблицу
     * @return void
     */
    private function setPhrases(): void
    {
        $this->fileList->each(function (string $fileLabel){
            $this->phrases[$fileLabel] = [];
            $blank = $this->getBlankPhrases($fileLabel);
            $this->locales->each(function (string $locale) use ($fileLabel, $blank){
                $data = array_merge($blank, $this->getFileData($locale, $fileLabel));
                $this->phrases[$fileLabel][$locale] = $data;
            });

        });
    }

    /**
     * Запись файла в storage
     * @param Spreadsheet $spreadsheet
     * @return void
     */
    private function storeExcel(Spreadsheet $spreadsheet): void
    {
        // Сохраняем во временный файл
        $writer = IOFactory::createWriter($spreadsheet, 'Xlsx');
        $tempFile = tempnam(sys_get_temp_dir(), 'lang_sync_');
        $writer->save($tempFile);

        // Загружаем в целевую файловую систему
        $this->storage->putFileAs('lang', new \Illuminate\Http\File($tempFile), 'lang.xlsx');

        // Удаляем временный файл
        unlink($tempFile);
    }

    /**
     * Формирование таблицы
     * @return Spreadsheet
     */
    private function makeExcel(): Spreadsheet
    {
        $spreadsheet = new Spreadsheet();
        $spreadsheet->removeSheetByIndex(0);
        foreach ($this->phrases as $fileLabel => $localesData) {

            // Проверяем, существует ли лист с именем локали
            if (!$spreadsheet->sheetNameExists($fileLabel)) {
                $sheet = $spreadsheet->createSheet();
                $sheet->setTitle(substr($fileLabel, 0, 31));
            } else {
                $sheet = $spreadsheet->setActiveSheetIndexByName($fileLabel);
            }

            $keys = $this->getKeys($fileLabel);

            // Заполняем заголовки
            $sheet->setCellValue('A1', 'Key');
            $this->locales->each(function (string $locale, $index) use (&$sheet, $keys){
                $column = Coordinate::stringFromColumnIndex($index + 2);
                $sheet->setCellValue($column . '1', $locale);
                $sheet->getColumnDimension($column)->setWidth(30);
            });

            // Устанавливаем ширину первого столбца (A)
            $sheet->getColumnDimension('A')->setWidth(50);


            // Заполняем данные
            foreach ($keys as $index => $key) {
                $row = $index + 2;

                $sheet->setCellValue('A' . $row, $key);

                $this->locales->each(function (string $locale, $index) use (&$sheet, $key, $row, $localesData){
                    $column = Coordinate::stringFromColumnIndex($index + 2);

                    $value = $localesData[$locale][$key] ?? '';
                    $sheet->setCellValue($column . $row, $value);
                });
            }
        }

        return $spreadsheet;
    }

    protected function getKeys(string $fileLabel): array
    {
        $keys = [];
        $this->locales->each(function (string $locale) use (&$keys, $fileLabel){
            $fileData = $this->getFileData($locale, $fileLabel);
            $keys = array_merge($keys, array_keys($fileData));
        });
        return array_unique($keys); ;

    }

    protected function getBlankPhrases(string $fileLabel): array
    {
        $keys = $this->getKeys($fileLabel);
        return array_map(fn () => '', array_flip(array_unique($keys)));
    }

    protected function getFileData(string $locale, string $fileLabel): array
    {
        $filePath = lang_path("/$locale/$fileLabel.php");
        if(!File::exists($filePath)) return [];
        $file = require $filePath;
        return Arr::dot($file);
    }

    protected function getFileList(): Collection
    {
        return $this->locales->map(
            fn($locale) => collect(File::files(lang_path("/$locale")))
                ->filter(fn(SplFileInfo $file) => $file->getExtension() === 'php')
                ->map(function (SplFileInfo $file) {
                    return substr(basename($file), 0, -4);
                })
        )->flatten()->unique()->sort()->values();
    }
}
