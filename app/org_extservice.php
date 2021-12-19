<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use \Illuminate\Support\Facades\DB;


class org_extservice extends Model
{
    use DeleteTrait;

    static public $prefix = 'org_extservices';
    static public $sysobjid = 981;

    protected $guarded = [];

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid');
    }

    public function srvcorg()
    {
        return $this->hasOne(org::class, 'id', 'srvcorgid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('mustread', 'desc')
            ->orderby('firstread_at');
    }

    static public function orgextsrvcsums_for_user($userid)
    {
        //Текущие остатки на субсчетах сервисов, доступных для заданного пользователя
        //Cache::forget('orgextsrvcsums_for_user_' . $userid);
        return Cache::remember('orgextsrvcsums_for_user_' . $userid, now()->addMinutes(5)
            , function () use ($userid) {

                if (usrsysright::isUserHasRightByCode_cached($userid, 'org_extservices.read')) {
                    //доступны все записи
                    return self::from('org_extservices as oes')
                        ->join('orgs as so', 'so.id', 'oes.srvcorgid')
                        ->join('orgs as o', 'o.id', 'oes.orgid')
                        ->leftjoin('contracts as c', function ($join) {
                            $join->on('c.id', 'oes.contractid');
                        })
                        ->where('oes.active', 1)
                        ->select('oes.id', 'oes.name', 'oes.orgid', 'so.name as srvcorgname', 'o.name as orgname'
                            , db::raw("concat('№',c.docnum,' от ',c.docdate) as contract_info")
                            , 'rest_sum', 'rest_dt', 'lock_limsum', 'oes.inform_limdays', 'avg_daysum'
                            , db::raw("if(avg_daysum<0,(rest_sum-lock_limsum)/-avg_daysum,null) as est_days2lock"))
                        ->whereRaw("if(avg_daysum<0,(rest_sum-lock_limsum)/-avg_daysum,null) < 60")
                        ->orderby('est_days2lock')
                        ->orderby('o.name')
                        ->orderby('oes.orgid')
                        //->orderby('oes.rest_dt')
                        ->get();

                } else {
                    //доступны только записи в которых пользователь указан в obj_readers
                    return self::from('org_extservices as oes')
                        ->join('orgs as so', 'so.id', 'oes.srvcorgid')
                        ->join('orgs as o', 'o.id', 'oes.orgid')
                        ->leftjoin('contracts as c', function ($join) {
                            $join->on('c.id', 'oes.contractid');
                        })
                        ->where('oes.active', 1)
                        ->whereraw("exists (select 1 from obj_readers as r
                    where r.sysobjid=981 and r.objid=oes.id and r.userid={$userid})")
                        ->select('oes.id', 'oes.name', 'oes.orgid', 'so.name as srvcorgname', 'o.name as orgname'
                            , 'rest_sum', 'rest_dt', 'lock_limsum', 'avg_daysum'
                            , db::raw("concat('№',c.docnum,' от ',c.docdate) as contract_info")
                            , db::raw("if(avg_daysum<0,(rest_sum-lock_limsum)/-avg_daysum,null) as est_days2lock")
                        )
                        ->whereRaw("if(avg_daysum<0,(rest_sum-lock_limsum)/-avg_daysum,null) < 60")
                        ->orderby('est_days2lock')
                        ->orderby('o.name')
                        ->orderby('oes.orgid')
                        //->orderby('oes.rest_dt')
                        ->get();
                }

            }
        );
    }


    static public function search_cond($params)
    {

        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'srvcorgid') {
                        $sc .= " and oes.srvcorgid={$val}";

                    } elseif ($key == 'orgid') {
                        $sc .= " and oes.orgid={$val}";

                    } elseif ($key == 'contractid') {
                        $sc .= " and oes.contractid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and oes.active={$val}";

//                    } elseif ($key == 'not_in_cwp_workid') {
//                        $sc .= " and not exists( select 1 from cwp_works as w where w.keyworkid=kw.id
//                        and w.cwp_id={$val[0]} and w.id<>{$val[1]})";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name сервисов поставщиков/провайдеров
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('org_extservices as oes')
                ->join('orgs as so', 'so.id', 'oes.srvcorgid')
                ->whereRaw($sc)
                ->select('oes.id', DB::raw("concat(oes.name,' (',so.name,')') as tname"))
                ->orderBy('tname', 'asc')
                ->get()->pluck('tname', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null)
    {
        //2021-04-23 SNS. универсальный конструктор коллекции из записей org_extservice
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'u.*';
            //Log::info(json_encode($fields));

            $recs = self::from('org_extservices as oes')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('oes.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


}
