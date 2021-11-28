<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\DeleteFileTrait;
use App\Traits\Result;
use DB;
use Illuminate\Support\Facades\Cache;
use Log;
use Storage;
use Illuminate\Database\Eloquent\Model;

class vp_photo extends Model
{
    static public $prefix = 'vp_photos';
    static public $sysobjid = 1112;

    use \App\Traits\TransactionDeleteTrait;
    use DeleteTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    public function viewpoint()
    {
        return $this->hasOne(viewpoint::class, 'id', 'viewpointid');
    }

    public function jts()
    {
        return $this->hasOne(jobtimesheet::class, 'id', 'jts_id')->withDefault();
    }


    public static function destroy($id)
    {
        $result = new Result;

        DB::beginTransaction();
        try {
            do {
                $rec = static::select("viewpointid as objid", 'systemfilename', "publicfilename")
                    ->where('id', $id)->first();
                //dd($rec);
                if (!isset($rec)) throw new \Exception ('Запись c ID=' . $id . ' не найдена');
                $filename = "public/" . $rec->systemfilename;
                //dd($filename, Storage::disk('local')->exists($filename));

                $result = vp_photo::t_delete_by_id($id);
                $result->obj = array("objid" => $rec->objid);
                if ($result->err == 1) {
                    break;
                }
                Storage::disk('local')->delete($filename);
                $fileuri = Storage::disk('local')->getAdapter()->applyPathPrefix($filename);
                if (file_exists($fileuri)) {
                    throw new \Exception ('Файл ' . $rec->systemfilename . ' не был удален с диска');
                } else {
                    Log::info("Файл " . $rec->publicfilename . "(" .
                        $rec->systemfilename . ") удален пользователем id=" . \Auth::user()->id);
                }
            } while (false);
        } catch (\Exception $e) {
            DB::rollback();
            Log::error("Ошибка удаления файла:" . $e->getMessage());
            Log::error($e->getTraceAsString());
            $result->err = 1;
            $result->msg = "Ошибка удаления файла";
        }
        if ($result->err != 1) {
            DB::commit();
        }
        return $result;
    }


    static public function last_photos($userid = null)
    {
        //Cache::forget('last_viewpoint_photos_' . ($userid ?? '*'));
        return Cache::remember('last_viewpoint_photos_' . ($userid ?? '*'), now()->addMinutes(15)
            , function () use ($userid) {

                $sql = "select p.infodt, p.systemfilename, p.notes
, vp.name as vp_name, vp.buildobjid, bo.name as bo_name
 from vp_photos as p
join (SELECT viewpointid, max(infodt) as infodt
 FROM vp_photos where active=1 group by viewpointid) as a
on a.viewpointid=p.viewpointid and a.infodt=p.infodt
join viewpoints as vp on vp.id=p.viewpointid and vp.active=1
join buildobjs as bo on bo.id=vp.buildobjid and bo.active=1
where timestampdiff(DAY, p.infodt, NOW())<5
order by vp.name";
                //if (isset($userid))
                //$sql .= "   and write_by = {$userid}";

                $lst = DB::select(DB::raw($sql));
                return ($lst);

            });
    }

}
