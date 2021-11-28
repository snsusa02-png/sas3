<?php
namespace App\Services\Readers;

use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

class XML implements impFileReader{
    public function __construct($filename){

        //todo - убрать заляпуху
        $this->reader=ReaderFactory::create(Type::CSV);
        $this->filename=$filename;

    }
    public function open(){
    }


    public function close(){
    }

    public function getRowsIterator(){
    }

    public function __destruct(){
        $this->reader->close();
    }

}
?>
