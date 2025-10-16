<?php

namespace PkEngine\LangSyncExcel\Builders;

use Illuminate\Console\OutputStyle;

interface FileBuilderInterface
{
    public function build(): void;

    public function setOutput(OutputStyle $output): void;
}
