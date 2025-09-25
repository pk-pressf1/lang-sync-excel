<?php

namespace PkEngine\LangSyncExcel\Builders;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\File;
use Exception;

class PhpFileBuilder implements FileBuilderInterface
{

    public function __construct(
        protected array $data,
        protected string $locale,
        protected string $fileLabel
    ){}

    /**
     * @throws Exception
     */
    public function build(): void
    {
        $data = Arr::undot($this->data);
        $path = "$this->locale/$this->fileLabel.php";
        $lines = $this->recursivePhpExport($data);
        $lines = $this->closePhpExport($lines);
        $export = implode("\n", $lines);
        if(!File::exists(lang_path("$this->locale"))){
            if(!File::makeDirectory(lang_path("$this->locale"))){
                throw new Exception('LangSyncExcel: не удалось создать папку: ' . lang_path("$this->locale"));
            };
        }
        if(!File::put(lang_path($path), $export)){
            throw new Exception('LangSyncExcel: не удалось сохранить файл: ' . lang_path($path));
        };
    }

    /**
     * Рекурсивно создаем массив для php файла
     * @param array $arr
     * @param int $i
     * @return array
     */
    protected function recursivePhpExport(array $arr, int $i = 1): array
    {
        $tab = '';
        for($n = 1; $n <= $i; $n++){
            $tab .= '    ';
        }
        if($i === 1){
            $lines = ['<?php', 'return ['];
        }else{
            $lines = [];
        }
        foreach($arr as $key => $item){
            if(!$key){
                continue;
            }
            if(is_array($item)){
                $lines[] = $tab.'"'.$key.'" => [';
                $subLines = $this->recursivePhpExport($item, $i + 1);
                $lines = array_merge($lines, $subLines);
                $lines[] = $tab.'],';
            }else{
                $item = str_replace('"', '\"', $item);
                $lines[] = $tab.'"'.$key.'" => "'.$item.'",';

            }

        }
        return $lines;
    }

    /**
     * @param array $arr
     * @return array
     */
    protected function closePhpExport(array $arr): array
    {
        $arr[] = '];';
        return $arr;
    }
}
