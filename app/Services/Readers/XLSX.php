<?php
namespace App\Services\Readers;

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

class XLSX implements impFileReader{
    public function __construct($filename){
        $this->reader=ReaderFactory::create(Type::XLSX);
        $this->filename=$filename;
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

}


?>
