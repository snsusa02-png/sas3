<?php

namespace App;

use DB;

use Illuminate\Database\Eloquent\Model;

class sysfunc extends Model
{

    static public $prefix = 'sysfuncs';

    //static public $sysobjid = 111;


    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid');
    }

    public static function funcLst()
    {
        $userid = \Auth::user()->id;
        return DB::table('sysfuncs as f')
            ->join("sysobjs as o", "o.id", "f.sysobjid")
            ->where("o.active", 1)
            ->where("f.active", 1)
            ->join('usrsysrights as ar', function ($join) use ($userid) {
                $join->on('ar.sysfuncid', '=', 'f.adminrightid')
                    ->where("ar.userid", $userid)
                    ->where("ar.active", 1)
                    ->whereRaw('now() between ar.begdt and ifnull(ar.enddt,now())');
            })
            ->select("f.id", "f.name as funcname", "ar.sysfuncid as adminrightid",
                "o.id as objid", "o.name as objname", "o.code as objcode")
//			->orderby("o.name")
            ->orderby("o.ordr")
            ->orderby("o.id")
            ->orderby("f.ordr")
            ->orderby("f.name")
            ->get();
    }


    static public function search_cond($params)
    {

        $userid = \Auth::user()->id;

        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];
        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 's_active') {
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " sf.active=1";

                    } elseif ($key == 's_sysobjid') {
                        $sc .= " and sf.sysobjid={$val}";

                    } elseif ($key == 's_name') {
                        $sc .= " and concat(sf.code,' ', sf.sysobjid) like '%{$val}%'";

                    } elseif ($key == 's_code') {
                        $sc .= " and sf.code like '%{$val}%'";

                    }

                }
            }
        }
        //Log::info($sc);

        return $sc;
    }


    static public function lstFor($params)
    {
        //2021-02-25 SNS. универсальный конструктор массива с id, name записей модели
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $userid = \Auth::user()->id;

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = self::from('sysfuncs as sf')
                ->whereRaw($sc)
                ->select('id', 'name'
                //, db::raw("concat(code,' - ', name) as name")
                )
                ->orderBy('sf.ordr', 'asc')
                ->orderBy('sf.name', 'desc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-07-10 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember('lstFor_' . self::$prefix . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-10-19 SNS. универсальный конструктор коллекции из записей sysfuncs
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['sf.code', 'asc'], ['sf.name', 'asc']];

            $recs = self::from('sysfuncs as sf')
                ->Join('sysobjs as so', 'so.id', 'sf.sysobjid')
                ->whereRaw($sc)
                ->select($fields);

            foreach ($sorts as $sort) {
                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
            }

            $recs = $recs->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

}
