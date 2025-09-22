<?php

namespace PkEngine\LangSyncExcel\Commands;


use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;
use PkEngine\LangSyncExcel\Services\LangGetService;

class LangGetCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'lang:get';

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
     * @throws \Exception
     */
    public function handle(): int
    {
        $service = new LangGetService();
        $service->parseFromUrl();

        return Command::SUCCESS;
    }
}
