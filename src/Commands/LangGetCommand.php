<?php

namespace PkEngine\LangSyncExcel\Commands;


use Illuminate\Console\Command;
use PkEngine\LangSyncExcel\Services\LangGetService;

class LangGetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:get  {--json}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Получение Excel файла с переводами';

    /**
     * Execute the console command.
     *
     * @return int
     * @throws \Exception
     */
    public function handle(): int
    {
        $json = $this->option('json');
//        try {
            $service = new LangGetService();
            if($json){
                $service->parseFromUrlToJson();
            }else{
                $service->parseFromUrlToPhp();
            }

//        }catch (\Exception $e){
//            $this->error($e->getMessage());
//        }



        return Command::SUCCESS;
    }
}
