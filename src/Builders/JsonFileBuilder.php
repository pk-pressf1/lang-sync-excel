<?php

namespace PkEngine\LangSyncExcel\Builders;

use Illuminate\Support\Facades\File;
use Exception;

class JsonFileBuilder implements FileBuilderInterface
{

    public function __construct(
        protected array $data,
        protected string $locale
    ){}

    /**
     * @throws Exception
     */
    public function build(): void
    {
        $path = "$this->locale.json";
        $lines = $this->recursiveJsonExport($this->data);
        $lines = $this->closeJsonExport($lines);
        $export = implode("\n", $lines);
        if(!File::put(lang_path($path), $export)){
            throw new Exception('LangSyncExcel: не удалось сохранить файл: ' . lang_path($path));
        };
    }

    protected function recursiveJsonExport(array $arr, int $i = 1): array
    {
        $tab = '';
        for($n = 1; $n <= $i; $n++){
            $tab .= '  ';
        }
        if($i === 1){
            $lines = ['{'];
        }else{
            $lines = [];
        }

        foreach($arr as $key => $data){

            if(!$key || !$data){
                continue;
            }
            $key = $this->escaping($key);

            if(is_array($data)){
                $lines[] = $tab.'"'.$key.'": {';
                $subLines = $this->recursiveJsonExport($data, $i + 1);
                $lines = array_merge($lines, $subLines);
                $lines[] = $tab.'},';

            }elseif(is_string($data)){
                $data = $this->escaping($data);
                $lines[] = $tab.'"'.$key.'": "'.$data.'",';
            }
        }
        $this->clearComma($lines);
        return $lines;
    }

    protected function closeJsonExport(array $arr): array
    {
        $arr[] = '}';
        return $arr;
    }

    protected function escaping(string $str): array|string
    {
        $str = str_replace("\n", " ", $str);
        $str = str_replace('"', '\"', $str);
        return $str;
    }

    protected function clearComma(array &$lines): void
    {
        if($c = count($lines)){
            $i = $c - 1;
            $lastString = $lines[$i];
            if($lastString[-1] === ','){
                $lines[$i] = substr($lastString, 0, -1);
            }
        }
    }
}
