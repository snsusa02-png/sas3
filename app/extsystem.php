<?php

namespace App;

use Cache;
use Illuminate\Database\Eloquent\Model;

use App\Traits\DeleteTrait;
use App\Traits\Result;
use Illuminate\Support\Facades\DB;

class extsystem extends Model
{
    use DeleteTrait;

    protected $table = 'extsystems';

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

    public static function listAll_cache($sysobjid = null)
    {
        $label = 'extsystems.list.' . ($sysobjid ?? '_');
        //Cache::forget($label);
        return Cache::remember($label, now()->addMinutes(10)
            , function () use ($sysobjid) {

                $rslt = extsystem::from('extsystems as es')
                    ->select('es.id', 'es.name');
                if (isset($sysobjid)) {
                    $rslt = $rslt->join('extsys_sysobjs as so', 'so.extsysid', 'es.id')
                        ->where('so.sysobjid', $sysobjid)
                        ->where('so.active', 1);
                }
                $rslt = $rslt->get()
                    ->pluck("name", "id")
                    ->toArray();
                return $rslt;
            });
    }

    static public function search_cond($params)
    {

        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, для того, чтобы работать с ней
        $userid = \Auth::user()->id;
//        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
//            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=m.acsid and uac.userid={$userid})";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'active') {
                        $sc .= " and es.active={$val}";

                    } elseif ($key == 's_name') {
                        $sc .= " and es.name like '%{$val}%'";

                    } elseif ($key == 'for_sysobjid') {
                        $sc .= " and exists(select 1 from extsys_sysobjs as so where so.extsysid=es.id and so.sysobjid={$val})";

                    } elseif ($key == 'in_sysobjid') {
                        $sc .= " and exists(select 1 from objextids as ei where ei.extsysid=es.id and ei.sysobjid={$val})";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;
    }


    static public function lstFor($params)
    {
        //2021-04-08 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            if (1 == 1) {
                $lst = self::from('extsystems as es')
                    ->whereRaw($sc)
                    ->select('id', 'name' )
                    ->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();
            }
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
            return \Illuminate\Support\Facades\Cache::remember('lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }


    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей machines
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['m.name', 'asc'], ['m.regnum', 'asc']];

            $recs = self::from('machines as m')
                ->Join('orgs as o', 'o.id', 'm.orgid')
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
