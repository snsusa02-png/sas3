<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class wrhdoctype extends Model
{

    public static function lstActive()
    {
        return static::from('wrhdoctypes as t')
            ->select('t.id', 't.name')
            ->where('t.active', 1)
            ->whereNull('t.parent_id')//Для документов отбираем только базовые типы. Дочерние типы доступны только для wrhdoclst.subtypeid
            ->orderby('t.ordr')
            ->orderby('t.name')
            ->get();
    }

    static public function listUsed()
    {
        $recs = wrhdoctype::from('wrhdoctypes as t')
            ->select('name', 'id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('wrhdocs as wd')
                    ->whereRaw('wd.doctypeid = t.id');
            })
            ->orderBy('name')
            ->get()->pluck("name", "id")->prepend("-тип: любой-", "");
        return $recs;
    }

    static public function search_cond($params)
    {

        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            $val = $val ?? -1;
            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'active') {
                        $sc .= " and wdt.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (wdt.active=1 or wdt.id={$val})";

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
        //2021-04-29 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('wrhdoctypes as wdt')
                ->whereRaw($sc)
                ->select('wdt.ordr', 'wdt.id', 'wdt.name')
                ->orderBy('wdt.ordr', 'asc')
                ->orderBy('wdt.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc, $lst);
            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей справочника
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $recs = self::from('wrhs as w')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('w.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


}
