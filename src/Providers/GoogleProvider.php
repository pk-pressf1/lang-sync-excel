<?php

namespace PkEngine\LangSyncExcel\Providers;

use Illuminate\Support\Facades\Http;

class GoogleProvider implements GetProvider
{

    public function handler(string $url): string
    {
        return Http::get($url)->body();
    }
}