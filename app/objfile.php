<?php

namespace App;

use App\Traits\DeleteFileTrait;
use DB;
use Illuminate\Support\Facades\Cache;
use Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Result;


class objfile extends Model
{
    use \App\Traits\TransactionDeleteTrait;
    use DeleteFileTrait;

    //protected $guarded = [];
    protected $fillable = ['id', 'sysobjid', 'objid', 'created_by'];

    public function sysfiletype()
    {
        return $this->hasOne(sysfiletype::class, 'id', 'sysfiletype_id');
    }

    public function mimetype()
    {
        return $this->hasOne(mimetype::class, 'id', 'mimetypeid');
    }

    public function doctype()
    {
        return $this->hasOne(doctype::class, 'id', 'doctypeid')->withDefault();
    }

    public function docsubtype()
    {
        return $this->hasOne(doctype::class, 'id', 'docsubtypeid')->withDefault();
    }

    public function delete()
    {//SNS. расширение метода класса для удаления файла с диска при удалении записи из таблицы

        $folder = substr($this->systemfilename, 0, strrpos($this->systemfilename, "/") + 1);
        $filename = substr(strrchr($this->systemfilename, "/"), 1);
        //dd($folder, $filename);

        //Удалим файл с диска
        $this->deleteOne($folder, 'public', $filename);
        //и удалим запись
        return parent::delete();
    }

    public static function destroy($id)
    {
        $result = new Result;

        DB::beginTransaction();
        try {
            do {
                $rec = static::select("objid", 'disk', 'systemfilename', "publicfilename"
                    , 'ft.storage', 'ft.catalog', 'mt.extension', 'mt.mimetype')
                    ->from('objfiles as fi')
                    ->join('sysfiletypes as ft', 'ft.id', '=', 'fi.sysfiletype_id')
                    ->join('mimetypes as mt', 'mt.id', '=', 'fi.mimetypeid')
                    ->where('fi.id', $id)->first();
                if (!isset($rec)) throw new \Exception ('Запись c ID=' . $id . ' не найдена');

                //2021-04-21 SNS sysfiletypes практически не используется в basco.
                //$filename = $rec->catalog . "/" . $rec->systemfilename;
                $filename = $rec->systemfilename;

                $result = objfile::t_delete_by_id($id);
                $result->obj = array("objid" => $rec->objid);
                if ($result->err == 1) {
                    break;
                }
                //Storage::delete($filename);
                //Storage::disk($rec->storage)->delete($filename);
                //$fileuri = Storage::disk($rec->storage)->getAdapter()->applyPathPrefix($filename);
                $fileuri = Storage::disk($rec->disk)->getAdapter()->applyPathPrefix($filename);

                //Storage::disk($rec->storage)->delete($filename);
                Storage::disk($rec->disk)->delete($rec->systemfilename);
                //if (file_exists($fileuri)) {
                if (Storage::disk($rec->disk)->exists($fileuri)) {
                    throw new \Exception ('Файл ' . $rec->systemfilename . ' не был удален с диска');
                } else {
                    Log::info("Файл " . $rec->publicfilename . "(" .
                        $rec->systemfilename . ") удален пользователем id=" . \Auth::user()->id);

                }
            } while (false);
        } catch (\Exception $e) {
            
            DB::rollback();
            Log::error("Ошибка удаления файла экспорта:" . $e->getMessage());
            Log::error($e->getTraceAsString());
            $result->err = 1;
            $result->msg = "Ошибка удаления файла";
        }
        if ($result->err != 1) {
            DB::commit();
        }
        return $result;
    }


}
