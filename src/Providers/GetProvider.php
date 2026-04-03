<?php

namespace PkEngine\LangSyncExcel\Providers;

interface GetProvider
{
    public function handler(string $url): string;
}