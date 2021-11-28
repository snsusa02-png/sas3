<?php
namespace App\Services\Parsers;
use App\Services\Readers;
use App\Services;

interface impFileParser{
    public function setting($param, \App\Services\ParserLogger $log);
    public function doit( \App\Services\Readers\impFileReader $reader);
}

?>
