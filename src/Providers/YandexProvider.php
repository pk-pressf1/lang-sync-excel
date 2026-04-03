<?php

namespace PkEngine\LangSyncExcel\Providers;

use Illuminate\Support\Facades\Http;

class YandexProvider implements GetProvider
{

    public function handler(string $url): string
    {
        $json = Http::get("https://cloud-api.yandex.net/v1/disk/public/resources/download?public_key=$url")->json();
        return Http::get($json['href'])->body();
    }
}