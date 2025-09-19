<?php

namespace PKEngine\LangSyncExcel\Providers;

use PKEngine\LangSyncExcel\Commands\{LangGetCommand, LangSetCommand};
use Illuminate\Support\ServiceProvider;

class LangSyncExcelProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register(): void
    {
        // Регистрация конфигурации
        $this->mergeConfigFrom(
            __DIR__.'/../../config/lang-sync-excel.php', 'lang-sync-excel'
        );
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                LangGetCommand::class,
                LangSetCommand::class,
            ]);
        }

        // Публикация конфигурационного файла
        $this->publishes([
            __DIR__.'/../../config/lang-sync-excel.php' => config_path('lang-sync-excel.php'),
        ], 'config');
    }
}
