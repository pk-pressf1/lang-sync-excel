<?php

namespace PkEngine\LangSyncExcel\Services;

use Illuminate\Contracts\Filesystem\Filesystem;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

abstract class LangService
{
    protected Filesystem $storage;

    protected Collection $locales;

    protected Collection $fileList;

    public function __construct()
    {
        $this->storage = Storage::disk(config('lang-sync-excel.output_disk'));
    }
    /**
     * Получение списка локалей
     * @return \Illuminate\Support\Collection
     */
    protected function getLocales(): \Illuminate\Support\Collection
    {
        if(!File::exists(lang_path())){
            File::makeDirectory(lang_path());
        }
        return collect(File::directories(lang_path('/')))->map(function ($dir) {
            return basename($dir);
        });
    }

    abstract protected function getFileList(): Collection;
}
