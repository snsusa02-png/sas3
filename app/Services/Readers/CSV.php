<?php
namespace App\Services\Readers;

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

class CSV implements impFileReader{
    public function __construct($filename){

        $setFieldDelimiter=';';
        //По умолчанию поставим что это 1251
        $encoding='Windows-1251';
        $content=file_get_contents($filename,false,null,1,3200);
        if(isset($content)){
            //Получим первую строку
            $string=mb_split("\n",$content) [0];
            if(\mb_strlen($string)<10){
                $string=$content;
            }
            $cnt_semicolon=mb_substr_count($string,';');
            $cnt_comma=mb_substr_count($string,',');
            if($cnt_comma>$cnt_semicolon){
                //Число встретившихся запятых > ";" они скорее всего разделители
                $setFieldDelimiter=',';
            }
            //Проверим что это не UTF-8
            if($this->isUtf8($content)) $encoding='UTF-8';
        }
        $content=null;
        $this->reader=ReaderFactory::create(Type::CSV);
        $this->filename=$filename;
        $this->reader->setFieldDelimiter($setFieldDelimiter);
        $this->reader->setEncoding($encoding);
        $this->reader->open($this->filename);
    }
    public function open(){
    }


    public function close(){

    }

    public function getRowsIterator(){
        foreach ($this->reader->getSheetIterator() as $sheet) {
            foreach ($sheet->getRowIterator() as $row) {
                yield $row;
            }
            break;
        }

    }
    public function __destruct(){
        $this->reader->close();
    }

    private  function isUtf8($string)
    {
        return preg_match('%(?:'
          . '[\xC2-\xDF][\x80-\xBF]'                // non-overlong 2-byte
          . '|\xE0[\xA0-\xBF][\x80-\xBF]'           // excluding overlongs
          . '|[\xE1-\xEC\xEE\xEF][\x80-\xBF]{2}'    // straight 3-byte
          . '|\xED[\x80-\x9F][\x80-\xBF]'           // excluding surrogates
          . '|\xF0[\x90-\xBF][\x80-\xBF]{2}'        // planes 1-3
          . '|[\xF1-\xF3][\x80-\xBF]{3}'            // planes 4-15
          . '|\xF4[\x80-\x8F][\x80-\xBF]{2}'        // plane 16
          . ')+%xs', $string);
    }
}


?>
