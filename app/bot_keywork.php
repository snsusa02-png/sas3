<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class bot_keywork extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    //Работы для вида работ
    static public $prefix = 'bot_keyworks';
    static public $sysobjid = 1121;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid');
    }

    public function unittype()
    {
        return $this->hasOne(unittype::class, 'id', 'unittypeid')->withDefault();
    }

    static public function calc_work_enddt($begdt, $wrk_hrs)
    {
        //2021-04-29 SNS. Перенесено из cwp_works. Возможно требует ревизии

        // расчет даты/времени окончания выполнения работ с учетом рабочих интервалов

//        $begdt = date_create('2020-11-23 08:00:00');
//        $wrk_hrs = 64;

        if (isset($begdt) and isset($wrk_hrs)) {

            //рабочая смена с 08:00 до 17:00 с перерывом с 12:00 до 13:00
            $wrk_intervals = [
                ['beg' => 8, 'end' => 12, 'skip' => 1],
                ['beg' => 13, 'end' => 17, 'skip' => 15],
            ];
//dd($begdt, $wrk_hrs, $wrk_intervals);
            $dt0 = clone $begdt;
            $rest_hrs = $wrk_hrs;
            $all_hrs = 0;

            while ($rest_hrs > 0) {

                $hr0 = $dt0->format('H');
                $d = 0;

                foreach ($wrk_intervals as $wint) {
                    if ($hr0 >= $wint['beg'] and $hr0 <= $wint['end']) {
                        // возьмем минимальное из кол-ва часов до перерыва и остатка часов на работу
                        $d = min($wint['end'] - $hr0, $rest_hrs);
                        $rest_hrs -= $d;

                        //если не хватило, то придется работать после перерыва. => к общим затратам прибавим время перерыва
                        if ($rest_hrs > 0) {
                            $d += $wint['skip'];
                        }
                        $all_hrs += $d;
                    }
                }
                //сформируем новую дату/время
                $dt0 = date_add($dt0, date_interval_create_from_date_string($d . " hour"));
                //dd($wrk_hrs, $rest_hrs, $all_hrs, $dt0);
            }

            //dd($wrk_hrs, $rest_hrs, $all_hrs, $dt0);
            return $dt0;
        }
        return null;
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

                    if ($key == 'buildopertypeid') {
                        $sc .= " and kw.buildopertypeid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and kw.active={$val}";

                    } elseif ($key == 'not_in_cwp_workid') {
                        $sc .= " and not exists( select 1 from cwp_works as w where w.keyworkid=kw.id
                        and w.cwp_id={$val[0]} and w.id<>{$val[1]})";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name ключевых работ для вида работ строит. объекта
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('bot_keyworks as kw')
                ->join('unittypes as ut', 'ut.id', 'kw.unittypeid')
                ->whereRaw($sc)
                ->select('kw.id', db::raw("concat(kw.name,' (',ut.name,')') as tname"))
                ->orderBy('kw.ordr', 'asc')
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
        //2021-04-30 SNS. универсальный конструктор коллекции из записей bot_keyworks
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $recs = self::from('bot_keyworks as kw')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('kw.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }



}
