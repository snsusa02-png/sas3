<?php

namespace App;

use App\Imports\invoiceImport;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class idcard extends Model
{
    //
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'idcards';
    static public $sysobjid = 1960;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }


    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = '/ №' . $this->num . ' - ' . $this->name . '.  ' . $this->org->name;
            return $rslt;
        } else
            return null;
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

                    if ($key == 'orgid') {
                        $sc .= " and ic.orgid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and ic.active={$val}";

                    } elseif ($key == 's_name') {
                        $sc .= " and concat(m.num,' ',m.name) like '%{$val}%'";

                    } elseif ($key == 'in_idcard_staffs') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from idcard_staffs as ics where ics.cardid=ic.id)";

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
                $lst = self::from('idcards as ic')
                    ->whereRaw($sc)
                    ->select('id', 'num as name')
                    ->orderby('num')
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
        //2021-12-04 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . self::$prefix . $hash);
            return Cache::remember('lstFor_' . self::$prefix . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей idcards
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['ic.name', 'asc'], ['ic.num', 'asc']];

            $recs = self::from('idcards as ic')
                ->Join('orgs as o', 'o.id', 'ic.orgid')
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
