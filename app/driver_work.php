<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class driver_work extends Model
{
    static public $prefix = 'dirver_works';
    static public $sysobjid = 1141;

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

    public function machine()
    {
        return $this->hasOne(machine::class, 'id', 'machineid')->withDefault();
    }

//    public function mchn_opertype()
//    {
//        return $this->hasOne(mchn_opertype::class, 'id', 'mot_id')->withDefault();
//    }

    public function wrktype()
    {
        return $this->hasOne(wrktype::class, 'id', 'wrktypeid')->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();
    }

    public function raids()
    {
        return $this->hasMany(mchn_raid::class, 'dw_id', 'id')
            ->orderBy('wrkbegdt')
            ->with('opertype');
    }

    public function breaks()
    {
        return $this->hasMany(dw_break::class, 'dw_id', 'id')
            ->orderBy('begdt');
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

    public static function min_wrkdate()
    {
        //определим минимально-допустимую дату для поля wrkdate
        return sysobj_lockdate::where('sysobjid', self::$sysobjid)->first()->lock_before ?? null;
    }

    static public function find_or_create($params)
    {
        // ищет запись по параметрам. если не находит - создает с предзаполненными параметрами + подходящие значения дл новой записи

        if (!(isset($params) and is_array($params) and count($params) > 0))
            return null;

        $driver_work = driver_work::where($params)->first();
        if (!isset($driver_work)) {
            //создадим запись в driver_works

            //если параметры не содержат времени начала работ, то создадим его от даты, или от сегодня
            if (!isset($params['wrkbegdt']))
                $params['wrkbegdt'] = $params['wrkdate'] ?? today()->format('Y-m-d');


            $driver_work = new driver_work($params);
//            dd($params, $driver_work);

            //добавим значения, подходящие для новой записи
            $driver_work->statusid = 0;    //черновик
            $driver_work->active = 1;

            //и вычислим некоторые значения по mchn_raids
            //$data = mchn_raid::

            $driver_work->save();
        }
        return $driver_work;
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

                    if ($key == 'active') {
                        $sc .= " and dw.active={$val}";

                    } elseif ($key == 's_name' or $key == 'name') {
                        //$sc .= " and concat(ifnull(dw.code,' '),' ',dw.name) like '%{$val}%'";

                        $search_flds = "dw.name";

                        $words = explode(" ", $val);
                        if (count($words) > 0) {
                            $sc .= ' and (';

                            //ищем "как ввел"
                            $sc .= ' (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            //попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
                            $words = explode(" ", StringUtil::switcher_ru($val));
                            $sc .= ' or (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            $sc .= ')';
                        }


                    } elseif ($key == '-in_') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') .
                            " exists (select 1 from mchn_raids as mr where mr.refitmid=dw.id)";

                    } elseif ($key == 's_staffid') {
                        $sc .= " and dw.staffid={$val}";

                    } elseif ($key == 's_machineid') {
                        $sc .= " and dw.machineid={$val}";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-11-19 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('estdocs as dw')
                ->whereRaw($sc)
                ->select('dw.id', 'dw.name as tname')
                ->orderBy('tname', 'asc')
                ->get()->pluck('tname', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-11-19 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember(self::$prefix . '_lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-11-19 SNS. универсальный конструктор коллекции из записей estdocs
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'dw.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['dw.name', 'asc']];

            $recs = self::from('driver_works as dw');

            $recs = $recs->whereRaw($sc)
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

    /*2023-06-11 Расчет ЗП сотрудника при почасовой ставке "День/Ночь"*/
    public static function calc_hr_salary(
        $wrkdate
        , $staffid
        , $day_wrkhrs
        , $night_wrkhrs
        , $wrktypeid)
    {
        $result = 0.00;
        if (isset($wrkdate) and isset($staffid)) {

            // не используется ----
            $sql = "select i.hr_day_rate, i.hr_night_rate"
                . " from salary_rate_sets srs"
                . " join srs_hr_items i	on i.srs_id = srs.id"
                . " join orgstaff os on os.id={$staffid}"
                . " join stf_payrolltypes spt"
                . "   on spt.staffid=os.id"
                . "  and p_wrkdate between spt.begdate and ifnull(spt.enddate, '{$wrkdate}')"
                . " where ifnull(srs.ownorgid,os.orgid)=os.orgid"
                . " and srs.payrolltypeid = spt.payrolltypeid"
                . " and i.wrktypeid={$wrktypeid}"
                . " and '{$wrkdate}' between srs.begdate and ifnull(srs.enddate,'{$wrkdate}')"
                . " and TIMESTAMPDIFF(month, ifnull(os.begdate,'{$wrkdate}'), '{$wrkdate}' )/12 between i.min_wrkexp and i.max_wrkexp-0.001";
//            $rates = DB::select(DB::raw($sql));

            // сначала определим схему начисления ЗП, действующую на дату работы
            $payrolltypeid = stf_payrolltype::from('stf_payrolltypes as spt')
                    ->where('staffid', $staffid)
                    ->whereRaw("'{$wrkdate}' between spt.begdate and ifnull(spt.enddate,'{$wrkdate}')")
                    ->first()
                    ->payrolltypeid ?? 1;
//dd($payrolltypeid);

            // теперь готовы определить ставки, действующие на дату работы
            $rates = srs_hr_item::from('srs_hr_items as i')
                ->join('salary_rate_sets as srs', 'srs.id', 'i.srs_id')
                ->join('orgstaff as os', 'os.id', '=', DB::raw($staffid))
                ->where('i.wrktypeid', $wrktypeid)
                ->where('srs.payrolltypeid', $payrolltypeid)
                ->whereRaw('ifnull(srs.ownorgid,os.orgid)=os.orgid')
                ->whereRaw("'{$wrkdate}' between srs.begdate and ifnull(srs.enddate,'{$wrkdate}')")
                ->whereRaw("TIMESTAMPDIFF(month, ifnull(os.begdate,'{$wrkdate}'), '{$wrkdate}' )/12 between i.min_wrkexp and i.max_wrkexp-0.001")
                ->select('i.hr_day_rate', 'i.hr_night_rate')
                ->get();
            //dd($rates);
            if (isset($rates)) {
                foreach ($rates as $rate) {
                    //dd($day_wrkhrs, $rate->hr_day_rate, $day_wrkhrs * $rate->hr_day_rate);
                    //dd($night_wrkhrs, $rate->hr_night_rate, $night_wrkhrs * $rate->hr_night_rate);
                    $result = $day_wrkhrs * $rate->hr_day_rate
                        + $night_wrkhrs * $rate->hr_night_rate// + $aux_equipment * ($day_wrkhrs + $night_wrkhrs) * $rate->hr_aux_rate
                    ;

                    break;
                }
            }
        }
        return $result;
    }

    public static function refr_stf_month_chrg_calc($p_staffid, $p_wrkdate, $p_userid)
    {
        $orgstaff = orgstaff::find($p_staffid);
        //dd($p_staffid, isset($orgstaff), $orgstaff);

        if (!isset($orgstaff))
            return;

        // -- 11 --------------------------------------------------------------------------------------------
        // Регистрация расчета ЗП
        $chargetypeid = 11;

        // Определим - существует ли необходимость привязки начисления этой организации к общей ведомости
        $orgcharge = org_charge::where(['orgid' => $orgstaff->orgid, 'chargetypeid' => $chargetypeid])->first();

        if (isset($orgcharge)) {

            //Подсчитаем общую сумму ЗП сотрудника за ВЕСЬ месяц от указанной даты $p_wrkdate
            $spp = stf_prl_period::from('stf_prl_periods as spp')
                ->where('spp.staffid', $p_staffid)
                ->whereRaw('? between spp.begdate and spp.enddate', [$p_wrkdate])
                ->first();
            //dd($spp);

            if (isset($spp)) {
                $int_begdate=$spp->begdate;
                $int_enddate=$spp->enddate;
            } else {
                $int_begdate = date_create($p_wrkdate)->format('Y-m-01');
                $int_enddate = date_create($p_wrkdate)->format('Y-m-t');
            }
            //dd($int_begdate, $int_enddate);

            // сумма начисленной ЗП
            $charge_sum = driver_work::where('staffid', $p_staffid)
                ->wherebetween('wrkdate', [$int_begdate, $int_enddate])
                ->sum('salary_sum');
            //dd($charge_sum);

            // Сформируем детали расчета - для сохранения в поле примечания (stf_chrg_calc.notes)
            $sql = "select group_concat(notes separator '; ') notes from (SELECT concat(
        		SUM(day_wrkhrs), ' ч * ', day_hr_rate, ' руб (день)'
                , ' + ', SUM(night_wrkhrs), ' ч * ', night_hr_rate, ' руб (ночь)'
                , ' + ', SUM(breaks_sum), ' руб (простой)'
		        ) as notes
                FROM driver_works as dw
                where staffid={$p_staffid}
                  and wrkdate between '{$int_begdate}' and '{$int_enddate}'
                  and salary_sum>0
                GROUP BY day_hr_rate, night_hr_rate) a";
            $rslt = DB::select(DB::raw($sql));
            $notes = $rslt[0]->notes ?? '';
            //dd($sql, $notes);

            // Так как привязываем совокупную запись, то берем "общий" идентификатор - "0"
            $stfchrgcalc = stf_chrg_calc::where([
                'staffid' => $p_staffid
                , 'ref_sysobjid' => self::$sysobjid
                , 'ref_objid' => 0
                , 'docdate' => $int_begdate
                , 'orgchargeid' => $orgcharge->id
            ])->first();
            if (!isset($stfchrgcalc)) {

                $stfchrgcalc = new stf_chrg_calc([
                    "staffid" => $p_staffid,
                    "orgchargeid" => $orgcharge->id,
                    "charge_dir" => $orgcharge->chargetype->dir,
                    "docdate" => $int_begdate,
                    "forbegdate" => $int_begdate,
                    "forenddate" => $int_enddate,
                    "created_by" => $p_userid,
                    "created_at" => now(),
                    "ref_sysobjid" => self::$sysobjid,
                    "ref_objid" => 0,
                ]);
            }
            $stfchrgcalc->staffid = $p_staffid;
            $stfchrgcalc->charge_sum = $charge_sum;
            $stfchrgcalc->notes = $notes;

            $stfchrgcalc->updated_by = $p_userid;
            $stfchrgcalc->updated_at = now();
            //dd($stfchrgcalc);
            $stfchrgcalc->save();
        }
        //-- end of 11 -------------------------------------------------------------------------------------

        // -- 54 - Регистрация расчета Премии за работу свыше 340 часов ------------------------------------
        $chargetypeid = 54;

        // Определим - существует ли необходимость привязки начисления этой организации к общей ведомости
        $orgcharge = org_charge::where(['orgid' => $orgstaff->orgid, 'chargetypeid' => $chargetypeid])->first();

        if (isset($orgcharge)) {

            //Подсчитаем общую сумму рабочих часов сотрудника за ВЕСЬ месяц от указанной даты $p_wrkdate
            $int_begdate = date_create($p_wrkdate)->format('Y-m-01');
            $int_enddate = date_create($p_wrkdate)->format('Y-m-t');
            $sql = "select i.hr_rate
                    , wh.wrkhrs
                    , if(i.hr_rate>0, wh.wrkhrs - i.min_wrkhrs, 0) as prize_hrs
                    , (wh.wrkhrs - i.min_wrkhrs)*i.hr_rate as prize_sum
                    , wh.min_wrkdate, wh.max_wrkdate, wh.cnt
                    from prs_hr_items as i
                        join (
                            select staffid, EXTRACT( YEAR_MONTH FROM `wrkdate` ) as ym,  count(1) as cnt
                                , sum(day_wrkhrs + night_wrkhrs) as wrkhrs
                                , min(wrkdate) as min_wrkdate, max(wrkdate) as max_wrkdate
                                from driver_works dw where 1=1
                                and wrkdate between CAST(DATE_FORMAT('{$int_begdate}' ,'%Y-%m-01') as DATE)  and '{$int_enddate}'
                                 /*and wrkdate between CAST(DATE_FORMAT('2023-08-09' ,'%Y-%m-01') as DATE)  and last_day('2023-08-09')*/
                                and dw.active=1
                                and dw.staffid = {$p_staffid}
                                group by staffid, ym) wh
                            on wh.wrkhrs is not null
                                and wh.wrkhrs between i.min_wrkhrs and i.max_wrkhrs-0.001
                        join orgstaff os on os.id=wh.staffid
                        join orgs as o on o.id=os.orgid
                        join prize_rate_sets as prs
                            on prs.id = i.prs_id
                            and prs.prizetypeid = 1 /*-- p_prizetypeid*/
                            and ifnull(prs.ownorgid, os.orgid)=os.orgid
                            and prs.active=1
                            /*-- период действия набора ставок*/
                            --  and '{$int_begdate}' between prs.begdate and ifnull(prs.enddate, '{$int_begdate}')
                        /*join stf_prizetypes spt
                            on spt.prizetypeid=prs.prizetypeid
                            and spt.staffid=os.id*/
                        where 1=1 /*and hr_rate > 0*/";
            $rslt = DB::select(DB::raw($sql));

            //dd($rslt, $rslt[0], $rslt[0]->prize_sum, $orgcharge);
            if (isset($rslt)) {

                $charge_sum = $rslt[0]->prize_sum + 0;
                //Сформируем детали расчета - для сохранения в поле примечания (stf_chrg_calc.notes)
                $notes = 'Всего отработано часов: ' . $rslt[0]->wrkhrs
                    . ', из них свыше 340: ' . $rslt[0]->prize_hrs
                    . ' по ставке ' . $rslt[0]->hr_rate . ' р/час';

            } else {
                $charge_sum = 0;
                $notes = '';
            }
            //dd($notes, $charge_sum);

            // Так как привязываем совокупную запись, то берем "общий" идентификатор - "0"
            $stfchrgcalc = stf_chrg_calc::where([
                'staffid' => $p_staffid
                , 'ref_sysobjid' => self::$sysobjid
                , 'ref_objid' => 0  // "общий" идентификатор
                , 'docdate' => $int_begdate
                , 'orgchargeid' => $orgcharge->id
            ])->first();

            if (!isset($stfchrgcalc)) {

                $stfchrgcalc = new stf_chrg_calc([
                    "staffid" => $p_staffid,
                    "orgchargeid" => $orgcharge->id,
                    "charge_dir" => $orgcharge->chargetype->dir,
                    "docdate" => $int_begdate,
                    "forbegdate" => $int_begdate,
                    "forenddate" => $int_enddate,
                    "created_by" => $p_userid,
                    "created_at" => now(),
                    "ref_sysobjid" => self::$sysobjid,
                    "ref_objid" => 0,
                ]);
            }
            $stfchrgcalc->staffid = $p_staffid;
            $stfchrgcalc->charge_sum = $charge_sum;
            $stfchrgcalc->notes = $notes;

            $stfchrgcalc->updated_by = $p_userid;
            $stfchrgcalc->updated_at = now();
            //dd($stfchrgcalc);
            $stfchrgcalc->save();
        }
        //-- end of 54 -------------------------------------------------------------------------------------

    }

}
