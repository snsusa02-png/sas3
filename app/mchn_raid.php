<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use DB;
use DateTime;
use App\Traits\Result;

class mchn_raid extends Model
{
    static public $prefix = 'mchn_raids';
    static public $sysobjid = 1106;

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

    public function dispatcher()
    {
        //return $this->hasOne(User::class, 'id', 'disp_userid')->withDefault();
        return $this->hasOne(orgstaff::class, 'id', 'disp_staffid')->withDefault();
    }

    public function machine()
    {
        return $this->hasOne(machine::class, 'id', 'machineid')->withDefault();
    }

    public function suporg()
    {
        return $this->hasOne(org::class, 'id', 'suporgid')->withDefault();
    }

    public function driver_work()
    {
        return $this->hasOne(driver_work::class, 'id', 'dw_id')->withDefault();
    }

    public function mr_opers()
    {
        return $this->hasMany(mr_oper::class, 'mr_id', 'id');
    }

    public function opertype()
    {
        return $this->hasOne(opertype::class, 'id', 'opertypeid')->withDefault();
    }

    static public function paytypes()
    {
        return [1 => 'нал', 2 => 'б/н'];
    }

    public function mchn_opertype()
    {
        return $this->hasOne(mchn_opertype::class, 'id', 'mot_id')->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();
    }

    public function driver()
    {
        return $this->hasOne(orgstaff::class, 'id', 'driverid')->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function load_ownorg()
    {
        return $this->hasOne(org::class, 'id', 'load_ownorgid')->withDefault();
    }

    public function unload_ownorg()
    {
        return $this->hasOne(org::class, 'id', 'unload_ownorgid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function load_place()
    {
        return $this->hasOne(org_place::class, 'id', 'load_placeid')->withDefault();
    }

    public function load_refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'load_refitmid')->withDefault();
    }

    public function unload_refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'unload_refitmid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            //$rslt = $this->orgstaff->name . ' (' . $this->rolename . ') ' . $this->orgstaff->org->name;
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

    public static function years()
    {
        return Cache::remember('mchn_raids.years', now()->addMinutes(15)
            , function () {
                return self::selectraw('year(wrkdate) as yr')->distinct()
                    ->orderby('yr', 'desc')->get()
                    ->pluck('yr', 'yr')->toArray();
            });
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи

        //пересчитаем кол-во рейсов и ЗП водителя за рейсы --------------------------------------
        if (isset($rec->dw_id)) {
            $raid_info = mchn_raid::where('dw_id', $rec->dw_id)
                ->selectRaw("sum(raid_qty) as qty, sum(raid_qty*raid_salary) as sum")
                ->first();

            $driver_work = driver_work::find($rec->dw_id);
            $driver_work->salary_sum = $raid_info->sum + $driver_work->pdt_sum + $driver_work->repair_sum; //коррекция общей суммы ЗП
            $driver_work->raid_qty = $raid_info->qty;
            $driver_work->raid_sum = $raid_info->sum;
            $driver_work->save();
        }
        //---------------------------------------------------------------------------------------

        //Забудем связанный кэш -----------------
        self::cache_clear();

    }

    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи

        //удалим записи из obj_finopers, для которых уже нет соответствующих записей в mr_opers
        obj_finoper::from('obj_finopers as f')
            ->where('sysobjid', self::$sysobjid)
            ->whereRaw("not exists (select 1 from mr_opers as mro where mro.id=f.objid)")
            ->delete();

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
                        $sc .= " and mr.machineid={$val}";

                    } elseif ($key == 'driverid') {
                        $sc .= " and mr.driverid={$val}";

                    } elseif ($key == 'wrkdate') {
                        $sc .= " and mr.wrkdate='{$val}'";

                    } elseif ($key == 'active') {
                        $sc .= " and mr.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (mr.active=1 or mr.id={$val})";

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

            $lst = self::from('mchn_raids as mr')
                ->join('machines as m', 'm.id', 'mr.machineid')
                ->whereRaw($sc)
                ->select('mr.id', 'mr.name')
                ->orderBy('mr.name', 'asc')
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


    public static function informer_calendar_raid_qtys()
    {
        //Cache::forget('informer_calendar_raid_qtys');
        return Cache::remember('informer_calendar_raid_qtys', now()->addMinutes(6)
            , function () {

                $d = new DateTime('first day of this month');
                $start_ymd = $d->format('Y-m-d');
                $start_ym = $d->format('Y-m-');
                //$start_ym = '2022-01-';

                $results = DB::select(
                    DB::raw("select d.date, weekday(d.date)+1 as dow, day(d.date) as day, s.raid_qty
                    from(
select FROM_UNIXTIME(UNIX_TIMESTAMP(CONCAT(:start_ym,n)),'%Y-%m-%d') as Date
	from (
        select (((b4.0 << 1 | b3.0) << 1 | b2.0) << 1 | b1.0) << 1 | b0.0 as n
                from  (select 0 union all select 1) as b0,
                      (select 0 union all select 1) as b1,
                      (select 0 union all select 1) as b2,
                      (select 0 union all select 1) as b3,
                      (select 0 union all select 1) as b4 ) t
        where n > 0 and n <= day(last_day(:start_ymd))
        ) as d
      left join (select wrkdate,sum(raid_qty) raid_qty from mchn_raids as mr group by wrkdate) as s
                      on s.wrkdate=d.date
        order by date;"), array(
                    'start_ymd' => $start_ymd,
                    'start_ym' => $start_ym,
                ));
                //dd($results);
                return $results;
            }
        );
    }


    public static function clone($id)
    {
        // Клонируем указанную запись mchn_raid со всем содержимым

        $result = new Result;
        $userid = \Auth::user()->id;

        $mchn_raid = self::find($id);
        if (!isset($mchn_raid)) {
            $result->err = 1;
            $result->msg = 'Исходная запись не найдена!';
            return $result;
        }

        $new_mchn_raid = $mchn_raid->replicate();
        //дата записи не может быть ранее sysobj_lockdates.lock_before
        $new_mchn_raid->wrkdate = max($new_mchn_raid->wrkdate, sysobj_lockdate::mindate(self::$sysobjid));
        $new_mchn_raid->created_by = $userid;
        $new_mchn_raid->updated_by = $userid;
        $new_mchn_raid->save();
        mchn_raid::on_update($new_mchn_raid);

        //перенесем операции
        $opers = mr_oper::where('mr_id', $mchn_raid->id)->get();
        foreach ($opers as $oper) {
            $new_oper = $oper->replicate();
            $new_oper->mr_id = $new_mchn_raid->id;
            $new_oper->created_by = $userid;
            $new_oper->updated_by = $userid;
            $new_oper->save();
            mr_oper::on_update($new_oper);
        }

        $result->obj = $new_mchn_raid->toArray();

        return $result;
    }

    /*2023-06-11 Расчет ЗП сотрудника при почасовой ставке "День/Ночь"*/
    public static function calc_hr_salary(
        $wrkdate
        , $staffid
        , $day_wrkhrs
        , $night_wrkhrs
        , $aux_equipment)
    {
        $result = 0;
        if (isset($wrkdate) and isset($staffid)) {
            $sql = "select i.hr_day_rate, i.hr_night_rate, i.hr_aux_rate"
                . " from salary_rate_sets srs"
                . " join srs_hr_items i	on i.srs_id = srs.id"
                . " join orgstaff os on os.id={$staffid}"
                . " where ifnull(srs.ownorgid,os.orgid)=os.orgid"
                . " and srs.payrolltypeid=1"
                . " and '{$wrkdate}' between srs.begdate and ifnull(srs.enddate,'{$wrkdate}')"
                . " and TIMESTAMPDIFF(year, os.begdate, '{$wrkdate}' ) between i.min_wrkexp and i.max_wrkexp-0.001";
//            $rates = DB::select(DB::raw($sql));

            $rates = srs_hr_item::from('srs_hr_items as i')
                ->join('salary_rate_sets as srs', 'srs.id', 'i.srs_id')
                ->join('orgstaff as os', 'os.id', '=', DB::raw($staffid))
                ->where('srs.payrolltypeid', 1) //to-do - взять из карточки сотрдника
                ->whereRaw('ifnull(srs.ownorgid,os.orgid)=os.orgid')
                ->whereRaw("'{$wrkdate}' between srs.begdate and ifnull(srs.enddate,'{$wrkdate}')")
                ->whereRaw("TIMESTAMPDIFF(year, os.begdate, '{$wrkdate}' ) between i.min_wrkexp and i.max_wrkexp-0.001")
                ->select('i.hr_day_rate', 'i.hr_night_rate', 'i.hr_aux_rate')
                ->get();
            //dd($rates);
            if (isset($rates)) {
                foreach ($rates as $rate) {
                    $result = $day_wrkhrs * $rate->hr_day_rate
                        + $night_wrkhrs * $rate->hr_night_rate
                        + $aux_equipment * ($day_wrkhrs + $night_wrkhrs) * $rate->hr_aux_rate;
                    break;
                }
            }
        }
        return $result;
    }
}
