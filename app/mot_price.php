<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class mot_price extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    //Работы для вида работ
    static public $prefix = 'mot_prices';
    static public $sysobjid = 487;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function mot()
    {
        return $this->hasOne(mchn_opertype::class, 'id', 'mot_id');
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

                    if ($key == 'mot_id') {
                        $sc .= " and motp.mot_id={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and motp.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (motp.active=1 or motp.id={$val})";

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

            $lst = self::from('mot_prices as motp')
                ->join('mchn_opertypes as mot', 'mot.id', 'motp.mot_id')
                ->whereRaw($sc)
                ->select('motp.id', db::raw("concat(motp.begdt,' - ', ifnull(motp.enddt,'...')) as name"))
                ->orderBy('motp.begdt', 'desc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;

    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей mchn_opertypes
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'motp.*';
            //Log::info(json_encode($fields));

            $recs = self::from('mot_prices as motp')
                ->whereRaw($sc)
                ->select($fields);

            $sorts = $sorts ?? [['motp.begdt', 'desc']];   //по умолчанию
            foreach ($sorts as $sort) {
                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
            }

            $recs = $recs->get();

            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

    public static function upd_enddts($mot_id)
    {
        //пересчет окончания периода для расценок на режим эксплуатации спецтехники
        $recs = self::where('mot_id', $mot_id)
            ->select('id', 'begdt', db::raw("DATE_ADD(begdt, INTERVAL -1 second) as enddt"))
            ->orderby('begdt', 'desc')->get();
        $enddt = null;
        try {
            DB::beginTransaction();
            foreach ($recs as $rec) {
                $nxtenddt = $rec->enddt;

                $rec->enddt = $enddt;
                $rec->save();

                $enddt = $nxtenddt;
            }

            //заполним текущие цены в mchn_opertype ---------------------
            $hour_work_cost = null;
            $hour_fuel_cost = null;
            $cur_price = mot_price::where('mot_id', $mot_id)->whereRaw("now() between begdt and ifnull(enddt,now())")->first();
            if (isset($cur_price)) {
                $hour_work_cost = $cur_price->hour_work_cost;
                $hour_fuel_cost = $cur_price->hour_fuel_cost;
            }
            mchn_opertype::where('id', $mot_id)->update([
                'hour_work_cost' => $hour_work_cost,
                'hour_fuel_cost' => $hour_fuel_cost,
            ]);
            //---------------------------------------------------------

            DB::commit();


        } catch (\Exception $e) {
            DB::rollback();
            //$this->log->fatalerror($e->getMessage());
            //var_dump($e->getTraceAsString());
            \Log::debug($e->getMessage());
            return $e->getMessage();
            //return null;
        } finally {
        }
    }

}
