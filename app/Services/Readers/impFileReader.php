<?php
namespace App\Services\Readers;

interface impFileReader {
    public function open();
    public function close();
    public function getRowsIterator();
}
?>
