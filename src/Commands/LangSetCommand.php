<?php

namespace PkEngine\LangSyncExcel\Commands;

use Illuminate\Console\Command;
use PkEngine\LangSyncExcel\Services\LangSetService;

class LangSetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:set';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        $service = new LangSetService();
        $service->storeExcelToFile();

        return Command::SUCCESS;
    }


}
