<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Cache;

class report extends Model
{
    use DeleteTrait;

    static public $prefix = 'reports';
    static public $sysobjid = 855;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function ac()
    {
        return $this->hasOne(ac::class, 'id', 'acsid')->withDefault();
    }

    public function acs_right()
    {
        return $this->hasOne(sysfunc::class, 'id', 'acs_rightid')->withDefault();
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->name;
            return $rslt;
        } else
            return null;
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('firstread_at');
    }

    public function real_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at');
    }

    public function tags()
    {
        return $this->hasMany(objtag::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('tag');
    }

    static function getForUser($reportid, $userid)
    {

        $sc = "r.id={$reportid}";

        $sc .= " and r.active=1";   //Не черновик

        $sysobjid = self::$sysobjid;

        //Если отчет не публичный, то пользователь должен быть включен в список читателей (obj_readers) -----------
        $sc .= " and (r.public=1
                        or r.created_by={$userid}
                        or ( r.public=0 and exists(select 1 from obj_readers as rdr
                                where rdr.sysobjid={$sysobjid} and rdr.objid=r.id and rdr.userid={$userid})
                           )
                )";
        //Пользователь должен иметь доступ к категории информации, указанной в отчете
        $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=r.acsid and uac.userid={$userid})";

        // Учтем требование отчета к наличию у пользователя определенного права -----------------------------------
        $sc .= " and (r.acs_rightid is null
            or exists(select 1 from usrsysrights as usr where usr.userid={$userid} and usr.sysfuncid=r.acs_rightid
                and usr.active and now() between usr.begdt and ifnull(usr.enddt,now()) ) )";
        //---------------------------------------------------------------------------------------------------------

        return self::from('reports as r')->whereRaw($sc)->first();
    }


    //обновление статистики использования отчета
    static function updUseCnt($reportid, $userid = null, $username = null)
    {
        if (isset($reportid)) {
            $rep = report::find($reportid);

            if (!isset($rep))
                $rep = new report([
                    'id' => $reportid,
                    'name' => "отчет №{$reportid}",
                ]);

            $rep->use_cnt++;
            $rep->lastuse_dt = now();
            $rep->lastuse_userid = $userid ?? \Auth::user()->id;
            $rep->lastuse_username = $username ?? \Auth::user()->name;
            $rep->save();

//            report::where('id', $reportid)->update(['use_cnt' => DB::raw('use_cnt + 1'),
//                'lastuse_dt' => now(),
//                'lastuse_userid' => $userid ?? \Auth::user()->id,
//                'lastuse_username' => $username ?? \Auth::user()->name,]);

        }
    }

    static public function usedTags()
    {
        $cache_key = self::$prefix . '_usedTypes';
        Cache::forget($cache_key);
        $data = Cache::remember($cache_key, now()->addMinutes(8)
            , function () {
                $lst = objtag::from('objtags as t')
                    ->select('tag as tid', 'tag as tname')
                    ->where('sysobjid', self::$sysobjid)
                    ->orderBy('tag')
                    ->get()
                    ->pluck('tname', 'tid')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function popular($userid = null, $limit = 5)
    {
        //Cache::forget('popular_reports_' . ($userid ?? '*'));
        return Cache::remember('popular_reports_' . ($userid ?? '*'), now()->addMinutes(5)
            , function () use ($userid, $limit) {
                $sql = "SELECT ol.objid as id, r.name, count(*) as cnt, min(write_at) first_dt, max(write_at) as last_dt
        FROM objlogs as ol
        join reports as r on r.id = ol.objid
        where sysobjid = 855
        and DATEDIFF(now(), write_at) < 60";
                if (isset($userid))
                    $sql .= "   and write_by = {$userid}";

                $sql .= " group by objid
            having cnt > 2
            order by cnt desc, last_dt desc";

                if ($limit > 0)
                    $sql .= " limit 12";

                $lst = DB::select(DB::raw($sql));
                return ($lst);

            });
    }
}
