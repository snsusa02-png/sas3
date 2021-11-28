<?php
namespace App\Services;

use Storage;
use Config;
use Log;
use App\Traits\Result;
use App\Services\ParserLogger;
use App\Services\impFileLogger;
use App\Services\MainParserFile;
use App\Jobs\ParseFile;
use App\sysfiletype;
use App\importfile;
use App\mimetype;

class HandleLoadFile
{

    public function LoadFile($filetypeid, $file)
    {
        $res = new Result();
        $imptype = sysfiletype::find($filetypeid);
        $path = null;
        $impfile = new importfile();

        try {
            $path = Storage::disk($imptype->storage)
                ->putFile($imptype->catalog, $file);

            //Заполняем данные для записи
            $impfile->sysfiletype_id = $filetypeid;
            $impfile->mimetype_id = mimetype::getFileMime(
                $file->getClientOriginalExtension());
            if (!isset($impfile->mimetype_id)) {
                throw new \Exception("Неизвестный тип файла для данного типа загрузки, загрузка запрещена");
            }
            $impfile->clientfilename = $file->getClientOriginalName();
            $impfile->systemfilename = basename($path);
            $impfile->filesize = $file->getSize();
            $impfile->import_by = \Auth::user()->id;
            $impfile->import_at = now();
            $impfile->save();

            $log = new ParserLogger($impfile->id, 6);
            $log->SetLogger(new impFileLogger());
            $log->info('Файл успешно передан на сервер');
            $res->obj = $impfile->toArray();

            //Поместим задачу(job ParseFile) обработки файла в очередь
            $log->info('Файл поставлен в очередь на обработку');
            Log::info("инициатор загрузки: " . \Auth::user()->name . ', id:' . \Auth::user()->id);

            dispatch((new ParseFile($impfile->id, \Auth::user()))->onQueue('low'));


        } catch (\Exception $e) {
            if (isset($path)) {
                Storage::disk('local')->delete($path);
            }
            $res->err = 1;
            $res->msg = $e->getMessage()/*.$e->getTraceAsString()*/
            ;
        }
        return $res;
    }
}

?>
