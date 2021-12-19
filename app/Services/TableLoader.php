<?php

namespace App\Services;

use DB;
use Exception;
use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

use Illuminate\Database\Seeder;
use Illuminate\Database\Query\Builder;
use Box\Spout\Reader\ReaderFactory;
use Box\Spout\Common\Type;

//Универсальный сидер читающий $workDir."/".$fileConfig
//И по порядку из него загружает данные из $tablesPath
//Файл конфигурации содержит является сsv файлом который содердит в полях
//следующие данные
//1: имя файла (оно же имя таблицы если не задано 4-е поле)
//2: действие по обновлению U- обновление, O - перезапись
//3: ключевое поле таблицы по которому производится сравнение для обновление
//4: имя обновляемой таблицы (если он не совпадает с именем файла)

//В первой строке файла должны содержаться имена полей совпадающие с
//именами полей таблицы
//Порядок определенный в seedlist.csv является порядком загрузки данных

//csv-файл удобно получить экспортом из MySql. Сразу будет правильная кодировка и кавычки в нужных местах.

class TableObj
{
    public $tableName;
    public $action;
    public $key;
    public $fileName;

    public function __construct($fileName, $action, $key, $tableName)
    {
        $this->fileName = $fileName;
        $this->action = $action != "" ? strtoupper($action) : "U";
        $this->key = $key != "" ? $key : "id";
        $this->tableName = $tableName != "" ? $tableName : $fileName;
    }
}

class TableLoader
{
    protected $file4list;
    protected $dir4list;
    private $act_name = array("U" => "обновление", "O" => "перезапись");

    public function __construct($workDir, $fileConfig, $method = 'U')
    {
        $this->file4list = $fileConfig;
        $this->dir4list = $workDir;
        $this->act = $method;
//        $this->dir_xlsx = $tablesPath;
        $this->dir_xlsx = $workDir;
    }

    protected $table_action;

    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function load()
    {
        echo "Загрузка данных в таблицы:\n";
        $dir_xlsx = $this->dir_xlsx;
        $ctl_file_path = $this->dir4list . DIRECTORY_SEPARATOR . $this->file4list;

        $ctl_file = ReaderFactory::create(Type::CSV);
        $list_object = array();

        try {
            $ctl_file->open($ctl_file_path);
            foreach ($ctl_file->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    if (mb_strlen($row[0]) == 0) continue;
                    $filename = mb_ereg_replace("(^\s+)|(\s+$)/us", "", $row[0]);

                    $action = $this->act;
                    $kf = "id";
                    $rc = count($row);
                    if ($rc > 1 && $row[1] != "") {
                        $action = $row[1];
                    }
                    if ($rc > 2 && $row[2] != "") {
                        $kf = $row[2];
                    }
                    $tableName = "";
                    if ($rc > 3 && $row[3] != "") {
                        $tableName = $row[3];
                    }
                    if (!array_key_exists($action, $this->act_name)) {
                        throw new \Exception("Для таблицы " . $tblname
                            . " указан неизвестный метод загрузки" . $action
                        );
                    }
                    array_push($list_object,
                        new  TableObj($filename, $action, $kf, $tableName)
                    );
                }
            }
            $ctl_file->close();
            $nobj = $this->hasTable($list_object);
            if (count($nobj) > 0) {
                throw new Exception("В базе не существуют таблицы: " . join(",", $nobj));
            }
            $nobj = $this->fileExists($dir_xlsx, $list_object);
            if (count($nobj) > 0) {
                throw new Exception("Не существуют файлов для загрузки: " . join(",", $nobj));
            }

            $this->truncate_data($list_object);
            $ret = -1;
            foreach ($list_object as $obj) {
                $ret = $this->load_file($dir_xlsx, $obj);
                if ($ret == 0) break;
            }
            if ($ret == 0) {
                print "Произошла ошибка база в неконсистентном состоянии";
            }
        } catch (\Exception $e) {
            echo "Ошибка работы с контрольным файлом " . $ctl_file_path . ":" . $e->getMessage() . "\n";

        }
    }

    private function hasTable($list_object)
    {
        $tablename = array();
        foreach (array_reverse($list_object) as $obj) {
            if (!Schema::hasTable($obj->tableName)) {
                array_push($tablename, $obj->tableName);
            }
        }
        return $tablename;
    }

    private function fileExists($dir, $list_object)
    {
        $nfile = array();
        foreach ($list_object as $obj) {
            $file = $obj->fileName;
            $filepathx = $dir . DIRECTORY_SEPARATOR . $file . ".xlsx";
            $filepathc = $dir . DIRECTORY_SEPARATOR . $file . ".csv";
            if (file_exists($filepathx)) {

            } elseif ($filepathc) {

            } else {
                array_push($nfile, $file);
            }
        }
        return $nfile;
    }

    private function truncate_data($list_object)
    {
        //снесем данные в обратном порядке
        foreach (array_reverse($list_object) as $obj) {
            if ($obj->action == 'O' &&
                Schema::hasTable($obj->tableName)) {
                DB::table($obj->tableName)->delete(); //truncate не используется т.к. есть FK
            }
        }
    }

    private function load_file($dir, $object)
    {
        $ret = 0;
        $act = $object->action;
        $table_key = $object->key;
        print "Обрабатывается объект " . $object->tableName . "\n";
        print "Метод " . $act;
        if ($act == "U") {
            print ", ключевое поле: " . $table_key;
        }
        print "\n";
        $filepathx = $dir . DIRECTORY_SEPARATOR . $object->fileName . ".xlsx";
        $filepathc = $dir . DIRECTORY_SEPARATOR . $object->fileName . ".csv";
        $filepath = $dir . DIRECTORY_SEPARATOR . $object->fileName;
        $tp = "";
        if (file_exists($filepathx)) {
            $filepath = $filepathx;
            $tp = Type::XLSX;
        } elseif ($filepathc) {
            $filepath = $filepathc;
            $tp = Type::CSV;
        } else {
            print "Файлов с расширение xlsx или csv с именем " . $filepath . " не существует";
            return 0;
        }

        if (!Schema::hasTable($object->tableName)) {
            print "Таблица" . $object->tableName . " не существует";
            return 0;
        }
        try {
            $reader = ReaderFactory::create($tp);
            $reader->open($filepath);
            $cnt = -1;
            $fields = array();
            $table = array();
            foreach ($reader->getSheetIterator() as $sheet) {
                foreach ($sheet->getRowIterator() as $row) {
                    //Формируем массив для загрузки
                    $cnt++;
                    if ($cnt == 0) {
                        $fields = $this->getFields($row);
                        if ($act == "U") {
                            if (!array_key_exists($table_key, $fields)) {
                                throw new \Exception("В таблице " . $object->tableName . " не найдено поле "
                                    . $table_key . " определенное как ключевое"
                                );
                            }
                        }
                        continue;
                    }
                    $r = $fields;
                    foreach ($r as $key => $val) {
                        $v = mb_ereg_replace("[ \t\n\r]+", " ", mb_ereg_replace("(^\s+)|(\s+$)/us", "", $row[$val]));
                        $v = mb_ereg_replace('\\\"', '"', $v); //Уберем экранирование с "
                        if (mb_strlen($v) == 0) $v = null;
                        if (mb_strtolower($v) == 'null') $v = null; //Заменим строковое представление null на реальный null

                        $r[$key] = $v;
                    }
                    //var_dump($r);
                    array_push($table, $r);
                }
            }
            $reader->close();

            //Добавим служебные поля
            $table = $this->add_fields($object, $table);

            //Загружаем данные
            $ins = 0;
            $upd = 0;
            //var_dump($this->table_action);
            foreach ($table as $id => $val) {
                $need_insert = true;
                //var_dump($val);
                if ($act == "U") {
                    //print "\key=$table_key\n";
                    $t = $val[$table_key];
                    // print "\$t=$t\n";
                    $o = DB::table($object->tableName)->where($table_key, $t)->first();
                    if (isset($o)) {
                        $need_insert = false;
                        unset($val[$table_key]);
                        DB::table($object->tableName)->where($table_key, $t)->update($val);
                        $upd++;
                    }
                }
                if ($need_insert) {
                    DB::table($object->tableName)->insert($val);
                    $ins++;
                }
            }
            print "\tЗаписей: вставлено=>" . $ins . ", обновлено=>" . $upd . "\n";
            print "Окончена обработка объекта " . $object->tableName . "\n";
            //print_r($table);
            $ret = 1;

        } catch (\Exception $e) {
            echo "\tОшибка обработки объекта  " . $object->tableName . ":" . $e->getMessage() . "\n";
        }
        return $ret;
    }

    private function add_fields($object, $table)
    {
        //Загоняем данные created_at, updated_at, whocrt whoupd
        $who = "init";
        $whn = now()->toDateTimeString();
        $tableName = $object->tableName;
        if (Schema::hasColumn($tableName, 'created_at')) {
            foreach ($table as $id => $val) {
                $val['created_at'] = $whn;
                $table[$id] = $val;
            }
        }
        if (Schema::hasColumn($tableName, 'updated_at')) {
            foreach ($table as $id => $val) {
                $val['updated_at'] = $whn;
                $table[$id] = $val;
            }
        }
        if (Schema::hasColumn($tableName, 'whocrt')) {
            foreach ($table as $id => $val) {
                $val['whocrt'] = $who;
                $table[$id] = $val;
            }
        }
        if (Schema::hasColumn($tableName, 'whoupd')) {
            foreach ($table as $id => $val) {
                $val['whoupd'] = $who;
                $table[$id] = $val;
            }
        }
        return $table;
    }

    private function getFields($row)
    {
        $fa = array();
        foreach ($row as $k => $v) {
            $fa[$v] = mb_ereg_replace("[ \t\n\r]+", " ", mb_ereg_replace("(^\s+)|(\s+$)/us", "", $k));
        }
        return $fa;
    }


}
