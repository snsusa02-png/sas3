<?php
namespace App\Services;

use Storage;
use Log;
use App\Traits\Result;
use App\sysfiletype;
use App\importfile;
use App\mimetype;
use App\importfilelog;
use App\Services\Reader;
use App\objpref;


class MainParserFile
{

    public function __construct($fileid, $loglevel, $user)
    {
        //info('MainParserFile: fileid: ' . $fileid);

        $this->impfile = importfile::find($fileid);
        if (!isset($this->impfile)) {
            throw new \Exception("Файл c id=$fileid не найден");
        }
        info('MainParserFile: impfile->systemfilename: ' . $this->impfile->systemfilename);

        $this->filetype = sysfiletype::find($this->impfile->sysfiletype_id);
        $this->user = $user; //объект модель

        $this->log = new ParserLogger($fileid, $loglevel);
        $this->log->SetLogger(new impFileLogger());
        ini_set('max_execution_time', 1800); //30 minutes
    }

    public function parseFile()
    {
        $res = new Result();
        $notes = "";
        do {
            try {
                $this->log->info("Начата обработка файла");
                //Загружаем файл
                $res = $this->OpenFile();
                if ($res->err > 0) {
                    $this->log->error($res->msg);
                    break;
                }
                //$this->log->info("файл открыт");

                $reader = $res->obj;
                $reader->fileid = $this->impfile->id;
//                dd($reader);

                //Загружаем обработчик
                $res = $this->LoadPaser();
                if ($res->err > 0) {
                    $this->log->error($res->msg);
                    break;
                }
                $parser = $res->obj;

                //доп. параметры(преференции) для задачи импорта ---------------------------------------
                $prefvalue = objpref::getListPrefValue(21, $this->filetype->id)->toArray();

                $usr = Array("preftypeid" => "user", "prefvalue" => $this->user->id);
                array_push($prefvalue, $usr);
                //--------------------------------------------------------------------------------------

                $res->obj->setting($prefvalue, $this->log); //заготовка под параметр

                $this->log->info("запускаем обработчик...");
                $res = $parser->doit($reader);

                if ($res->err == 0) {
                    $notes = $res->msg;
                }
                $reader->close();

            } catch (\Exception $e) {
                $res = new Result();
                $res->err = 1;
                $res->msg = $e->getMessage();
                $this->log->fatalerror($res->msg);
                Log::info(" Загружаемый файл " . $this->impfile->id);
                Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
            }

        } while (false);

        if ($res->err > 0) {
            $this->log->fatalerror($res->msg);
            $notes = $res->msg;
        }
        $this->writeImportNote($notes);

        $this->log->info("Завершена обработка файла");

        return $res;
    }

    protected function writeImportNote($notes)
    {
        try {
            $this->impfile->importnotes = $notes;
            $this->impfile->save();
        } catch (\Exception $e) {
            Log::error($e->getMessage());
        }
    }

    protected function LoadPaser()
    {
        $res = new Result();
        if (!isset($this->filetype->handler)) {
            $res->err = 2;
            $res->msg = "Не установлен обработчик данного типа файла";
        } else {
            try {
                //$this->log->info("задан обработчик: " . $this->filetype->handler);
                $res->obj = app($this->filetype->handler);
            } catch (\Exception $e) {
                $res->err = 1;
                $res->msg = $e->getMessage();
            }
        }
        return $res;
    }

    protected function OpenFile()
    {
        $res = new Result();
        $mtype = mimetype::find($this->impfile->mimetype_id);
        if (!isset($mtype)) {
            $res->err = 2;
            $res->msg = "Не найден тип для обработки файла";
            return $res;
        }
        if (!isset($mtype->readclass)) {
            $res->err = 2;
            $res->msg = "Не задан читатель файла";
            return $res;
        }
        try {
            //Формируем имя для файла
            $filename = $this->filetype->catalog
                . DIRECTORY_SEPARATOR
                . $this->impfile->systemfilename;

            $filename = Storage::disk($this->filetype->storage)
                ->getAdapter()
                ->applyPathPrefix($filename);

            if (!file_exists($filename)) {
                throw new \Exception("Файл " . $this->impfile->systemfilename . " не найден");
            }
            $res->obj = app($mtype->readclass, ['filename' => $filename]);
            $res->obj->open();

        } catch (\Exception $e) {
            $res->err = 1;
            $res->msg = $e->getMessage();
        }
        return $res;
    }
}

?>
