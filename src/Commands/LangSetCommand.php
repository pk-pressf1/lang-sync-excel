<?php

namespace PkEngine\LangSyncExcel\Commands;

use Illuminate\Console\Command;
use PkEngine\LangSyncExcel\Services\LangSetService;

class LangSetCommand extends Command
{
    /**
     * @var string
     */
    protected $signature = 'lang:set';

    /**
     * @var string
     */
    protected $description = 'Генерация Ecxel файла с переводами';

    /**
     * @return int
     */
    public function handle(): int
    {
        try {
            $service = new LangSetService();
            $service->storeExcelToFile();
        }catch (\Exception $e){
            $this->error($e->getMessage());
        }

        return Command::SUCCESS;
    }


}
