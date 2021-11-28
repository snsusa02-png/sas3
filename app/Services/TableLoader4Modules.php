<?php
namespace App\Services;
use Module;
use App\Services\TableLoader;

//Загрузка данных с помощью TableLoader из модулей
//передаем:
//$moduleName - имя модуля
//$workSubPath - кусок пути после Database/Seeders модуля
//   (где хранится $fileConfig)
//$fileConfig - файл определяющий содержимое загрузки
//$tablesSubPath - кусок пути после $workSubPath где хранятся
//                            файлы с данными таблицы
//$method='U'     - метод загрузки

class TableLoader4Modules
{

    function __construct($moduleName, $workSubPath, $fileConfig, $tablesSubPath, $method='U' ){
        $module = Module::findOrFail($moduleName);
        $path = $module->getPath();
        $workDir =$path.DIRECTORY_SEPARATOR. "Database/Seeders";
        if($workSubPath != ""){
            $workDir .= DIRECTORY_SEPARATOR.$workSubPath;
        }
        $tablesPath = $workDir;
        if($tablesSubPath != ""){
            $tablesPath .=DIRECTORY_SEPARATOR.$tablesSubPath;
        }
        $this->TableLoader= new TableLoader($workDir,  $fileConfig, $tablesPath, $method);
    }

    function load(){
        $this->TableLoader->load();
    }


}
