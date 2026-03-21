<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class fuelcard_pay extends Model
{
    static public $prefix = 'fuelcard_pays';
    static public $sysobjid = 562;

    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function driver()
    {
        return $this->hasOne(orgstaff::class, 'id', 'driverid')->withDefault();
    }

    public function machine()
    {
        return $this->hasOne(machine::class, 'id', 'machineid')->withDefault();
    }

    public function card()
    {
        return $this->hasOne(fuelcard::class, 'id', 'cardid')->withDefault();
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')->withDefault();
    }

    public function ri_sup_price()
    {
        return $this->hasOne(ri_sup_price::class, 'id', 'ri_sup_priceid')->withDefault();
    }

    static public function paydirs()
    {
        return [+1 => 'пополнение', -1 => 'расход'];
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->wrkdate . ' ' . $this->drivername;
            return $rslt;
        } else
            return null;
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lock_before = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lock_before)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->wrkdate < $lock_before);
            }
        }
        return false;
    }


    public function admindelete()
    {
        $result = new Result;
        //Удаляем себя вместе с дочками
        try {
            DB::transaction(function () {
                $this->mr_opers()->delete();
                $this->files()->delete();  //TODO: ? ->deleteOne() ? Так как не удаляется файл с диска

                //удалим записи из obj_finopers, для которых уже нет соответствующих записей в mr_opers
                obj_finoper::from('obj_finopers as f')
                    ->where('sysobjid', 1107)
                    ->whereRaw("not exists (select 1 from mr_opers as mro where mro.id=f.objid)")
                    ->delete();

                return parent::delete();
            });
        } catch (\Exception $e) {
            $result->err = 1;
            $result->msg = 'Ошибка удаления записи: ' . $e->getMessage();
        }
        return $result;
    }

    public static function min_wrkdate()
    {
        //определим минимально-допустимую дату для поля wrkdate
        $min_date = sysobj_lockdate::where('sysobjid', self::$sysobjid)->first()->lock_before ?? null;
        if (isset($min_date)) {
            return $min_date;
        }
        return null;
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи


        //Забудем связанный кэш -----------------
        self::cache_clear();
    }

    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function cache_clear($rec = null)
    {
        //для вызова при изменении / удалении записей
        if (isset($rec)) {
        }
        Cache::forget('mchn_raids.years');
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

                    if ($key == 'machineid') {
                        $sc .= " and fcp.machineid={$val}";

                    } elseif ($key == 'driverid') {
                        $sc .= " and fcp.driverid={$val}";

                    } elseif ($key == 'operdate') {
                        $sc .= " and fcp.operdate='{$val}'";

                    } elseif ($key == 'active') {
                        $sc .= " and fcp.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (fcp.active=1 or fcp.id={$val})";

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

            $lst = self::from('fuelcard_pays as fcp')
                ->join('fuelcards as fc', 'fc.id', 'fcp.cardid')
                ->whereRaw($sc)
                ->select('fcp.id', 'fc.num as name')
                ->orderBy('fc.num', 'asc')
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
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'mot.*';
            //Log::info(json_encode($fields));

            $recs = self::from('mchn_raids as mr')
                ->whereRaw($sc)
                ->select($fields);

            $sorts = $sorts ?? [['mr.wrkdate', 'asc']];   //по умолчанию
            foreach ($sorts as $sort) {
                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
            }

            $recs = $recs->get();

            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

    static public function rfr_finopers($id)
    {

        foreach (mr_oper::where('mr_id', $id)->get() as $oper) {
            mr_oper::rfr_finopers($oper);
        }
    }

    static public function addOrUpdate($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $rec = self::where($search_params)->first();

            if (!isset($rec)) {
                $rec = new self($search_params);
            }
            $rec->fill($set_params);
            $rec->save();

            return $rec;
        }
        return null;
    }


    public static function informer_calendar_sums()
    {
        //Cache::forget('informer_calendar_sums');
        return Cache::remember('informer_calendar_sums', now()->addMinutes(6)
            , function () {

                //$d = new DateTime('first day of this month');
                $d = today();
                $d->modify('-90 day');

                $start_ymd = $d->format('Y-m-d');

                $results = DB::select(
                    DB::raw("select date, weekday(date) as dow, day(date) as day, sum(raid_qty) as raid_qty
                                , sum(inp_paysum) as inp_paysum, sum(out_paysum) as out_paysum
                                , sum(prod_salesum) as prod_salesum
                            from (
                                SELECT mr.wrkdate as date, sum(mro.raid_qty) as raid_Qty, null inp_paysum, null out_paysum, null as prod_salesum
                                    FROM `mchn_raids` as mr
                                    join mr_opers as mro
                                    on mro.mr_id=mr.id
                                    WHERE mr.wrkdate>='{$start_ymd}'
                                    group by mr.wrkdate
                                UNION ALL
                                SELECT pd.paydate as date, null as raid_Qty, sum(pd.paysum) as inp_paysum, null out_paysum, null as prod_salesum
                                    FROM `paydocs` as pd
                                    WHERE pd.paydate>='{$start_ymd}'
                                    and pd.paydir=1
                                    group by pd.paydate
                                UNION ALL
                                SELECT pd.paydate as date, null as raid_Qty, null as inp_paysum, sum(pd.paysum) as out_paysum, null as prod_salesum
                                    FROM `paydocs` as pd
                                    WHERE pd.paydate>='{$start_ymd}'
                                    and pd.paydir=-1
                                    group by pd.paydate
                                UNION ALL
                                SELECT wd.docdate as date, null as raid_Qty, null as inp_paysum, null as out_paysum, sum(wd.docsum) as prod_salesum
                                    FROM `wrhdocs` as wd
                                    join wrhdoctypes as wdt on wdt.id=wd.doctypeid and wdt.forsale=1
                                    WHERE wd.docsigned=1 and wd.docdate>='{$start_ymd}'
                                    group by wd.docdate
                            ) as a
                            group by date
                            order by date desc")
                );
                //dd($results);
                return $results;
            }
        );
    }

    public static function clone($id)
    {
        // Клонируем указанную запись fuelcard_pays со всем содержимым

        $result = new Result;
        $userid = \Auth::user()->id;

        $src_fco = self::find($id);
        if (!isset($src_fco)) {
            $result->err = 1;
            $result->msg = 'Исходная запись не найдена!';
            return $result;
        }

        $new_fco = $src_fco->replicate();
        //дата записи не может быть ранее sysobj_lockdates.lock_before
        $new_fco->paydate = max($new_fco->paydate, sysobj_lockdate::mindate(self::$sysobjid));
        $new_fco->created_by = $userid;
        $new_fco->updated_by = $userid;
        $new_fco->save();
        fuelcard_pay::on_update($new_fco);

        $result->obj = $new_fco->toArray();

        return $result;
    }

}
