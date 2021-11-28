<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class cwp_work extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    //Работы для вида работ
    static public $prefix = 'cwp_works';
    static public $sysobjid = 973;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function workplan()
    {
        return $this->hasOne(contract_workplan::class, 'id', 'cwp_id');
    }

    public function keywork()
    {
        return $this->hasOne(bot_keywork::class, 'id', 'keyworkid');
    }

    public function unittype()
    {
        return $this->hasOne(unittype::class, 'id', 'unittypeid')->withDefault();
    }

    public function equips()
    {
        return $this->hasMany(cwp_work_equip::class, 'id', 'workid')
            ->with('ri_lim')
            ->with('refitem');
    }

    static public function calc_work_enddt($begdt, $wrk_hrs)
    {
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

    static public function calc_rel_works($src_workid)
    {
        if (isset($src_workid)) {

            //найдем зависимые работы ----------------------------------------------------------------------
            $linked_works = cwp_work_link::from('cwp_work_links as wl')
                ->join('cwp_works as src', 'src.id', 'wl.src_workid')
                ->where('src_workid', $src_workid)
                ->select('wl.tgt_workid'
                    , db::raw("ifnull(src.fctbegdt, ifnull(src.estbegdt, src.plnbegdt)) as src_begdt")
                    , db::raw("ifnull(src.fctenddt, ifnull(src.estenddt, src.plnenddt)) as src_enddt")
                    , 'wl.src_beg_shift'
                    , 'wl.src_end_shift'
                )
                ->get();
            //dd($linked_works);
            foreach ($linked_works as $lnk) {

                $wrk = cwp_work::find($lnk->tgt_workid);
                //dd($lnk->tgt_workid, $lnk, $wrk);
                if (isset($wrk)) {

                    $begdt = null;
                    if (isset($lnk->src_beg_shift) and isset($lnk->src_begdt)) {
                        //смещение от начала заданой работы
                        $begdt = cwp_work::calc_work_enddt(date_create($lnk->src_begdt)
                            , round($lnk->src_beg_shift, 0));
                        //dd($lnk->src_begdt, $lnk->src_beg_shift, $wrk->estbegdt);

                    } elseif (isset($lnk->src_end_shift) and isset($lnk->src_enddt)) {

                        $begdt = cwp_work::calc_work_enddt(date_create($lnk->src_enddt)
                            , round($lnk->src_end_shift, 0));
                    }

                    if (isset($begdt)) {
                        $enddt = cwp_work::calc_work_enddt($begdt, round($wrk->plnworkhrs, 0));
                        //dd($begdt, $wrk->plnworkhrs, $enddt);
                        $wrk->estbegdt = $begdt;
                        $wrk->estenddt = $enddt;
                        $wrk->save();
                        //dd($wrk->estbegdt, $wrk->plnworkhrs, $wrk->estenddt);

                        //рекурсивно вызовем для зависимых записей
                        self::calc_rel_works($wrk->id);
                    }

                }
                //dd($wrk);
            }
            //---------------------------------------------------------------------------------------------
        }
    }

    static public function calc_doneqty($cwp_id)
    {
        //Пересчет суммы факта по данным связанных записей
        self::where('id', $cwp_id)
            ->update(['doneqty' => db::raw("(select sum(qty) from cwp_facts where workid={$cwp_id})")]);
    }


    static public function informer_estimates()
    {
        //            SELECT
//            bo.name as bo_name,
//            bot.name as bot_name,
//            fe.avgdayqty,
//            w.plnqty,w.doneqty,w.plnqty-w.doneqty as restqty,
//            round((w.plnqty-w.doneqty)/fe.avgdayqty,1) as estwrkdays,
//            date_add(curdate(), interval round((w.plnqty-w.doneqty)/fe.avgdayqty,1) day) as estdonedate
//            FROM `cwp_works` as w
//            join (SELECT workid,sum(qty) as qty, sum(wrkdays) as wrkdays, sum(round(TIMESTAMPDIFF(second, begdt, enddt)/3600/24,1) ) as cwrkdays
//            ,round(sum(qty)/ sum(wrkdays),3) as avgdayqty
//             FROM cwp_facts
//             group by workid) as fe on fe.workid=w.id
//            join contract_workplans as cwp on cwp.id=w.cwp_id
//            join buildopertypes as bot on bot.id=cwp.buildopertypeid
//            join buildobjs as bo on bo.id=bot.buildobjid
//            where exists(select 1 from cwp_facts as f where f.workid=w.id
//                        and DATEDIFF(f.updated_at, now())<8
//                        )
//                        and exists(select 1 from buildobj_staffs as bos join orgstaff as os on os.id=bos.staffid where bos.buildobjid=bo.id and os.userid=12)
//            order by bo_name, bot.ordr,bot.name

        $userid = \Auth::user()->id;
        //Cache::forget('informer_cwp_estimates_' . $userid, now());
        return Cache::remember('informer_cwp_estimates_' . $userid, now()->addMinutes(55)
            , function () use ($userid) {

                $sc = "bo.active=1";//для активных объектов

                //отбираем только те работы по которым есть факт за последние 10 дней
                $sc .= " and exists(select 1 from cwp_facts as f where f.workid=w.id and DATEDIFF( now(), f.updated_at)<10)";

                //если у пользователя нет право просмотра справочника строительных объектов, то он должен быть в списке отв. сотрудников
                //todo:: дополнительно - сделать справочник informers и регулировать доступ через него
                if (!usrsysright::isUserHasRightByCode_cached($userid, 'buildobjs.read'))
                    $sc .= " and exists(select 1 from buildobj_staffs as bos join orgstaff as os on os.id=bos.staffid where bos.buildobjid=bo.id and os.userid={$userid})";


                $itms = cwp_work::from('cwp_works as w')
                    ->join('contract_workplans as cwp', 'cwp.id', 'w.cwp_id')
                    ->join('buildopertypes as bot', 'bot.id', 'cwp.buildopertypeid')
                    ->join('buildobjs as bo', 'bo.id', 'bot.buildobjid')
                    ->join('contracts as c', 'c.id', 'cwp.contractid')
                    ->join('orgs as co', 'co.id', 'c.orgid')
                    ->join(DB::raw('(SELECT workid,sum(qty) as qty, sum(wrkdays) as wrkdays
                        , sum(round(TIMESTAMPDIFF(second, begdt, enddt)/3600/24,1) ) as cwrkdays
                        , round(sum(qty)/ sum(round(TIMESTAMPDIFF(second, begdt, enddt)/3600/24,1)), 3) as avgdayqty
                        from cwp_facts  group by workid) as fe'),
                        function ($join) {
                            $join->on('fe.workid', '=', 'w.id');
                        })
                    ->leftjoin('unittypes as ut', 'ut.id', 'w.unittypeid')
                    ->whereRaw($sc)
                    ->select('bot.buildobjid', 'bo.name as buildobj_name'
                        , 'cwp.buildopertypeid', 'bot.name as bot_name'
                        , 'cwp.contractid', 'c.docnum as contract_num', 'c.docdate as contract_date', 'co.name as contract_orgname'
                        , 'w.id', 'w.cwp_id', 'w.name as wrk_name', 'w.plnqty', 'w.doneqty'
                        , 'fe.cwrkdays as wrkdays', 'fe.avgdayqty', 'ut.name as unit'
                        , db::raw("w . plnqty - w . doneqty as restqty")
                        , db::raw("round((w . plnqty - w . doneqty) / fe . avgdayqty, 1) as estwrkdays"),
                        db::raw("date_add(curdate(), interval round((w . plnqty - w . doneqty) / fe . avgdayqty, 1) day) as estdonedate")
                    )
                    ->orderby('bo.name')
                    ->orderby('c.docdate')
                    ->orderby('bot.ordr')
                    ->orderby('bot.name')
                    ->orderby('w.ordr')
                    ->get();
                //dd($itms);

                return $itms;
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

                    if ($key == 'cwp_id') {
                        $sc .= " and w.cwp_id={$val}";

                    } elseif ($key == 'keyworkid') {
                        $sc .= " and w.keyworkid={$val}";

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
        //2021-04-29 SNS. универсальный конструктор массива с id, name работ плана работ
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('cwp_works as w')
                ->join('contract_workplans as cwp', 'cwp.id', 'w.cwp_id')
                ->whereRaw($sc)
                ->select('w.id', 'w.name')
                ->orderBy('w.ordr', 'asc')
                ->orderBy('w.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null)
    {
        //2021-04-23 SNS. универсальный конструктор коллекции из записей cwp_works
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'u.*';
            //Log::info(json_encode($fields));

            $recs = self::from('cwp_works as w')
                ->join('contract_workplans as cwp', 'cwp.id', 'w.cwp_id')
                ->join('contracts as c', 'c.id', 'cwp.contractid')
                ->join('orgs as co', 'co.id', 'c.orgid')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('w.ordr', 'asc')
                ->orderBy('w.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


}
