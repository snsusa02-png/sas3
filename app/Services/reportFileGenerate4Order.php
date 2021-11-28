<?php
namespace App\Services;

use App\mimetype;
use App\objfile;
use App\Services\reportGenerator4Order\Make1COrderXml;
use App\sysfiletype;
use App\sysobj;
use App\Traits\Result;
use Illuminate\Support\Str;
use Log;
use Storage;

class reportFileGenerate4Order
{

    protected $sysobjid = null;

    public function __construct($userid, $generatorid)
    {
        $this->userid = $userid;
        $this->generatorid = $generatorid;
        $this->sysobjid = sysobj::getIdByCode('Orders');
        if (is_null($this->sysobjid)) {
            throw new \Exception("Не найден код Orders", 1);
        }
    }

    public function doIt($ordid, $needContent = true, $genname)
    {
        $res = new Result();

        try {
            do {
                //TODO: получение генератора
                $gen = new Make1COrderXml($ordid);
                $res = $gen->generate();
                if ($res->err > 0) {
                    break;
                }
                $mimetypeid = $gen->mimetypeid();
                $genname = is_null($genname) ? $gen->getGeneratorName() : $genname;
                $filetypeid = $gen->getFileTypeId();
                $content = $res->obj;
                $res = $this->save2file($needContent, $ordid, $mimetypeid, $filetypeid, $genname, $content);
            } while (false);
        } catch (\Exception $e) {
            $res = new Result();
            $res->err = 1;
            $res->msg = "Ошибка генерации";
            Log::info("Генерация из заказа " . $ordid);
            Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
            print ($e->getMessage() . "\n" . $e->getTraceAsString());
        }
        return $res;
    }

    private function save2file($needContent, $ordid, $mimetypeid, $filetypeid, $genname, $content)
    {
        $res = new Result();
        try {
            $mime = mimetype::find($mimetypeid);
            $filetype = sysfiletype::find($filetypeid);
            $uuid = Str::uuid();

            $filename = $uuid . "." . $mime->extension;
            $fullname = $filetype->catalog . DIRECTORY_SEPARATOR . $filename;
            //Сохраняем данные на диск
            $path = Storage::disk($filetype->storage)
                ->put($fullname, $content);
            $objfile = new objfile();
            $objfile->sysobjid = $this->sysobjid;
            $objfile->objid = $ordid;
            $objfile->sysfiletype_id = $filetypeid;
            $objfile->mimetypeid = $mimetypeid;
            $ext = pathinfo($genname, PATHINFO_EXTENSION);
            if(mb_strtolower($ext) == mb_strtolower($mime->extension)) {
                $objfile->publicfilename = $genname;  
            } else {
                $objfile->publicfilename = $genname . '.' . $mime->extension;
            }
            $objfile->systemfilename = $filename;
            $objfile->filesize = mb_strlen($content, '8bit');
            $objfile->created_by = $this->userid;
            $objfile->updated_by = $this->userid;
            $objfile->save();
            $obj = [];
            $obj['id'] = $objfile->id;
            $obj['clientfilename'] = $objfile->publicfilename;
            if ($needContent) {
                $obj['content'] = $content;
            }
            $res->obj = $obj;

        } catch (\Exception $e) {
            $res = new Result();
            $res->err = 1;
            $res->msg = "Ошибка генерации";
            Log::info("Генерация из заказа " . $ordid);
            Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
            print ($e->getMessage() . "\n" . $e->getTraceAsString());
        }
        return $res;
    }

}

?>
