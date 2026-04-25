<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\driver_work;
use App\Exports\rep2xlsx_vdr_export;
use App\Exports\rep46Export;
use App\mr_oper;
use App\Exports\InvoicesExport;
use App\Exports\PayPlanExport;
use App\mchn_raid;
use App\mchntype;
use App\orgstaff;
use App\report;
use App\org;
use App\machine;
use App\objlog;
use App\stf_chrg_calc;
use App\stf_salary;
use App\Traits\SearchDataTrait;
use App\usrsysright;
use http\Env\Response;
use Illuminate\Http\Request;
use DB;
use DateTime;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use App\Events\notifyEvent;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Config;

class DriverWorkReportController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;  //reports
        //$this->objcode = 'reports';
        $this->objcode = 'driver_works';
    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['registrate'] = false;
        $usrrights['unregistrate'] = false;
        $usrrights['approve'] = false;
        $usrrights['setfact'] = false;


        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
            $usrrights['approve'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.approve');
            $usrrights['setfact'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.setfact');

        }


        return $usrrights;
    }


    public function rep58(Request $request)
    {
        //
        $report_id = 58;
        $rep = report::find($report_id);
        //dd($rep->name);
//        dd(str_replace( ' ', '_', $rep->name) );

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данной информацией!']);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $export2xls = $request->get('xls') ?? 0;
        $fdom = new DateTime('first day of this month');
        $fdomc = $fdom->format('Y-m-d');
        $year = $fdom->format('Y');
        $ldom = new DateTime('last day of this month');
        $ldomc = $ldom->format('Y-m-d');
        $curdate = new DateTime();
        $cd = $curdate->format('Y-m-d');

        $month = date("n");
        $yearQuarter = ceil($month / 3);

        $begdate = today();

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_month' => $month
            , 's_year' => $year
            , 's_begdate' => $begdate->format('Y-m-01')
            , 's_enddate' => $begdate->format('Y-m-t')
            , 's_depname' => ''
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);
        //dd($search_params, isset($search_params['s_ownorgid']) );

        $begdate = today();
        //$search_params['s_begdate'] = $begdate->format('Y-m-d');
        //$search_params['s_enddate'] = $begdate->format('Y-m-t');

        $recs = null;

        $s_year = $search_params['s_year'];
        $s_month = $search_params['s_month'];

        $s_begdate = $search_params['s_begdate'];
        $s_enddate = $search_params['s_enddate'];

        $s_ownorgid = $search_params['s_ownorgid'] ?? null;
        $s_depname = $search_params['s_depname'];

        if (($s_year <> '' and $s_month <> '') or ($s_begdate <> '' and $s_enddate <> '')) {

            if ($s_year <> '' and $s_month <> '') {

                $s_yr_mn = $s_year . '-' . str_pad($s_month, 2, '0', STR_PAD_LEFT);
                $s_begdate = $s_yr_mn . '-01';

                $sql = " select a.staffid, os.name as staff_name, os.lname as staff_lname, os.fname as staff_fname, os.mname as staff_mname
            , os.postname
            , wt.name as wrktype_name
             , spt.payrolltypeid, pt.name as payroltype_name
            , (select count(distinct v.selected_date) as cnt from
                (select adddate('{$s_begdate}', t1.i*10 + t0.i) selected_date from
                 (select 0 i union select 1 union select 2 union select 3 ) t1,
                 (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0
                 ) v
                 join driver_works dw1 on v.selected_date between date(dw1.wrkbegdt) and date(dw1.wrkenddt)
                 where 1=1
                    and dw1.staffid=a.staffid
                    and date_format(dw1.wrkdate,'%Y-%m') = '{$s_yr_mn}'
                    and date_format(v.selected_date, '%Y-%m')='{$s_yr_mn}'
                   ) as wrkdays
                   , a.* from (
    SELECT dw.staffid, DATE_FORMAT(dw.wrkdate,'%Y-%m') as ym, dw.wrktypeid
        /*, count( distinct dw.wrkdate) as wrkdate_cnt*/
        , max(dw.day_hr_rate) as day_hr_rate
        , min(dw.day_hr_rate) as day_hr_rate_min
        , max(dw.night_hr_rate) as night_hr_rate
        , min(dw.night_hr_rate) as night_hr_rate_min
        , sum(dw.day_wrkhrs) day_wrkhrs
        , sum(dw.day_wrkhrs*dw.day_hr_rate) day_hr_sum
        , sum(dw.night_wrkhrs) night_wrkhrs
        , sum(dw.night_wrkhrs*dw.night_hr_rate) night_hr_sum
        , sum(dw.day_brkhrs) day_brkhrs
        , sum(dw.night_brkhrs) night_brkhrs
        , sum(dw.breaks_sum) breaks_sum
        , sum(dw.day_brkhrs + dw.night_brkhrs) brkhrs
        , SUM( (select sum(day_hrs+night_hrs) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=11)) as brk_11_hrs
        , SUM( (select sum(day_hrs+night_hrs) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=21)) as brk_21_hrs
        , SUM( (select sum(day_hrs+night_hrs) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=22)) as brk_22_hrs
        , SUM( (select sum(brk_sum) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=11)) as brk_11_sum
        , SUM( (select sum(brk_sum) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=21)) as brk_21_sum
        , SUM( (select sum(brk_sum) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=22)) as brk_22_sum
        FROM `driver_works` as dw
        where 1=1
            and date_format(dw.wrkdate,'%Y-%m') = '{$s_yr_mn}'
        group by dw.staffid, ym, dw.wrktypeid
     ) as a
    join orgstaff as os on os.id=a.staffid
    join wrktypes as wt on wt.id=a.wrktypeid
    left join stf_payrolltypes as spt on spt.staffid=a.staffid and '{$s_begdate}' between spt.begdate and ifnull( spt.enddate, '2024-09-01')
    left join payrolltypes pt on pt.id=spt.payrolltypeid
    order by staff_name, wt.name";
                //dd($s_begdate, $s_yr_mn, $sql);
                $recs = DB::select(DB::raw($sql));
                // dd($sql, $recs);

            } else {

                // 2024-09-16 Нужно перепроверить результат запроса после модификации на произвольные даты периода!

                $s_yr_mn = date_format(date_create($s_begdate), 'Y-m');
                //dd($s_yr_mn);

                $sql = " select a.staffid, os.name as staff_name, os.lname as staff_lname, os.fname as staff_fname, os.mname as staff_mname
            , os.postname
            , wt.name as wrktype_name
             , spt.payrolltypeid, pt.name as payroltype_name
            , (select count(distinct v.selected_date) as cnt from
                (select adddate('{$s_begdate}', t1.i*10 + t0.i) selected_date from
                 (select 0 i union select 1 union select 2 union select 3 ) t1,
                 (select 0 i union select 1 union select 2 union select 3 union select 4 union select 5 union select 6 union select 7 union select 8 union select 9) t0
                 ) v
                 join driver_works dw1 on v.selected_date between date(dw1.wrkbegdt) and date(dw1.wrkenddt)
                 where 1=1
                    and dw1.staffid=a.staffid
                    /*and date_format(dw1.wrkdate,'%Y-%m') = '{$s_yr_mn}'*/
                    and dw1.wrkdate between '{$s_begdate}' and '{$s_enddate}'
                    /*and date_format(v.selected_date, '%Y-%m')='{$s_yr_mn}'*/
                    and v.selected_date between '{$s_begdate}' and '{$s_enddate}'
                   ) as wrkdays
                   , a.* from (
    SELECT dw.staffid, DATE_FORMAT(dw.wrkdate,'%Y-%m') as ym, dw.wrktypeid
        /*, count( distinct dw.wrkdate) as wrkdate_cnt*/
        , max(dw.day_hr_rate) as day_hr_rate
        , min(dw.day_hr_rate) as day_hr_rate_min
        , max(dw.night_hr_rate) as night_hr_rate
        , min(dw.night_hr_rate) as night_hr_rate_min
        , sum(dw.day_wrkhrs) day_wrkhrs
        , sum(dw.day_wrkhrs*dw.day_hr_rate) day_hr_sum
        , sum(dw.night_wrkhrs) night_wrkhrs
        , sum(dw.night_wrkhrs*dw.night_hr_rate) night_hr_sum
        , sum(dw.day_brkhrs) day_brkhrs
        , sum(dw.night_brkhrs) night_brkhrs
        , sum(dw.breaks_sum) breaks_sum
        , sum(dw.day_brkhrs + dw.night_brkhrs) brkhrs
        , SUM( (select sum(day_hrs+night_hrs) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=11)) as brk_11_hrs
        , SUM( (select sum(day_hrs+night_hrs) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=21)) as brk_21_hrs
        , SUM( (select sum(day_hrs+night_hrs) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=22)) as brk_22_hrs
        , SUM( (select sum(brk_sum) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=11)) as brk_11_sum
        , SUM( (select sum(brk_sum) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=21)) as brk_21_sum
        , SUM( (select sum(brk_sum) from dw_breaks b where b.dw_id=dw.id and b.wrktypeid=22)) as brk_22_sum
        FROM `driver_works` as dw
        where 1=1
            /*and date_format(dw.wrkdate,'%Y-%m') = '{$s_yr_mn}'*/
            and dw.wrkdate between '{$s_begdate}' and '{$s_enddate}'
        group by dw.staffid, ym, dw.wrktypeid
     ) as a
    join orgstaff as os on os.id=a.staffid
    join wrktypes as wt on wt.id=a.wrktypeid
    left join stf_payrolltypes as spt on spt.staffid=a.staffid and '{$s_begdate}' between spt.begdate and ifnull( spt.enddate, '{$s_enddate}')
    left join payrolltypes pt on pt.id=spt.payrolltypeid
    where 1=1";
                if ($s_ownorgid <> '') {
                    $sql .= " and os.orgid={$s_ownorgid}";
                }
                if ($s_depname <> '') {
                    $sql .= " and ucase(os.depname)='{$s_depname}'";
                }

                $sql .= " order by staff_name, wt.name";

                //dd($s_begdate, $s_yr_mn, $sql);
                $recs = DB::select(DB::raw($sql));
//                dd($sql, $recs);

            }

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $s_year . ' ' . $s_month);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->years = Cache::remember('driver_works_years', now()->addMinutes(55)
            , function () {
                return driver_work::selectRaw("year(wrkdate) as year")
                    ->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });
        //dd($data->years);

        $month_names = Config::get('constants.monthes');
        $data->monthes = Cache::remember('driver_works_monthes', now()->addMinutes(15)
            , function () {
                return driver_work::selectRaw("month(wrkdate) as month")
                    ->distinct()->orderby('month')
                    ->get()->pluck('month', 'month')->toArray();
            });
        foreach ($data->monthes as $key => $val) {
            //dd($key,$val);
            $data->monthes[$val] = $month_names[$key];
        }
        //dd($data->monthes);

//        if (isset($search_params['s_ownorgid'])) {
        $data->ownorgs = org::lstFor_cached([
            'in_driver_works_ownorgid' => 1,
        ]);
//            dd($data->ownorgs);
        //}

        $data->depnames = orgstaff::from('orgstaff as os')
            ->WhereNotNull('depname')
            ->where('depname', '<>', '')
            ->whereRaw("exists(select 1 from driver_works dw where dw.staffid=os.id and (dw.day_wrkhrs+dw.night_wrkhrs)>0)")
            ->select(db::raw('ucase(depname) as code'))
            ->groupBy(db::raw('ucase(depname)'))
            ->orderBy(db::raw('ucase(depname)'))
            ->get()->pluck('code', 'code')->toArray();
//                , db::raw("(select count(*) from usrsysrights as ur
        //dd($data->depnames);

        // Подзаголовок с выводом значенией параметров отбора
        $data->sub_title = '';

        if (isset($s_begdate) and $s_begdate <> '')
            $data->sub_title .= ' с ' . date_format(date_create($s_begdate), 'd.m.Y');
        if (isset($s_enddate) and $s_enddate <> '')
            $data->sub_title .= ' по ' . date_format(date_create($s_enddate), 'd.m.Y');

        if (isset($s_ownorgid) and $s_ownorgid <> '') {
            if ($data->sub_title <> '')
                $data->sub_title .= ',';
            $data->sub_title .= ' организация: ' . $data->ownorgs[$search_params['s_ownorgid']] ?? '-';
        }

        if (isset($s_depname) and $s_depname <> '') {
            if ($data->sub_title <> '')
                $data->sub_title .= ',';
            //$data->sub_title .= '<br>';
            $data->sub_title .= ' подразделение: ' . $s_depname;
        }

        if ($export2xls == "1") {

            $response = Excel::download(
                new rep2xlsx_vdr_export('exports.rep58xls', $recs, $data),
                str_replace(' ', '_', $rep->name) . "_{$s_begdate}_{$s_enddate}.xlsx",
                \Maatwebsite\Excel\Excel::XLSX);

            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }
        //dd($data);

        return view('driver_works.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public function rep59(Request $request)
    {
        //
        $report_id = 59;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данной информацией!']);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $fdom = new DateTime('first day of this month');
        $fdomc = $fdom->format('Y-m-d');
        $year = $fdom->format('Y');
        $ldom = new DateTime('last day of this month');
        $ldomc = $ldom->format('Y-m-d');
        $curdate = new DateTime();
        $cd = $curdate->format('Y-m-d');
        $month = date("n");
        $yearQuarter = ceil($month / 3);
        $year_month = $year . '-' . $month;

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
//            , 's_month' => $month
//            , 's_year' => $year
            , 's_year_month' => $year_month
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $begdate = today();
        $search_params['s_begdate'] = $begdate->format('Y-m-d');
        $search_params['s_enddate'] = $begdate->format('Y-m-t');

        $recs = null;

//        $s_year = $search_params['s_year'];
//        $s_month = $search_params['s_month'];
        //$tstdate = new DateTime($s_year . '-' . $s_month . '-01');

        $year_month = $search_params['s_year_month'];
        $tstdate = new DateTime($year_month . '-01');
        $begdate = $tstdate->format('Y-m-d');
        $enddate = $tstdate->format('Y-m-t');
        //dd( $tstdate, $begdate, $enddate, $tstdate->format('t'));

        $tmp_arr = explode("-", $year_month);
        $search_params['s_year'] = $tmp_arr[0] ?? '';
        $search_params['s_month'] = $tmp_arr[1] ?? '';
//        dd($search_params);

        if ($begdate <> '' and $enddate <> '') {


            $sql = "SELECT dw.machineid, concat(m.name, ' ', m.regnum) as machine_name
                , 1 as wrktypeid, 'упр-е авто' as wt_name
	            , sum(dw.day_wrkhrs) as day_hrs, sum(dw.night_wrkhrs) as night_hrs , count(1) as cnt
                FROM `driver_works` dw
                join machines m on m.id = dw.machineid
                where dw.wrkdate between '{$begdate}' and '{$enddate}'
                and dw.wrkenddt is not null
                group by dw.machineid
                union all
                SELECT dw.machineid, concat(m.name, ' ', m.regnum) as machine_name
                    , brk.wrktypeid, wt.name as wt_name
	                , sum(brk.day_hrs) as day_hrs, sum(brk.night_hrs) as night_hrs , count(1) as cnt
                    FROM `driver_works` dw
                    join dw_breaks as brk on brk.dw_id=dw.id
                    join wrktypes wt on wt.id=brk.wrktypeid
                    join machines m on m.id = dw.machineid
                    where wrkdate between '{$begdate}' and '{$enddate}'
                    group by dw.machineid,  brk.wrktypeid
                    order by machine_name, machineid, wrktypeid, wt_name";
            $recs = DB::select(DB::raw($sql));
            //dd($sql, $recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $year_month);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->years = Cache::remember('driver_works_years', now()->addMinutes(55)
            , function () {
                return driver_work::selectRaw("year(wrkdate) as year")
                    ->whereNotNull('wrkenddt')
                    ->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });
//        dd($data->years);

        $month_names = Config::get('constants.monthes');
        $data->monthes = Cache::remember('driver_works_monthes', now()->addMinutes(15)
            , function () {
                return driver_work::selectRaw("month(wrkdate) as month")
                    ->whereNotNull('wrkenddt')
                    ->distinct()->orderby('month')
                    ->get()->pluck('month', 'month')->toArray();
            });
        foreach ($data->monthes as $key => $val) {
            //dd($key,$val);
            $data->monthes[$val] = $month_names[$key];
        }
        //dd($data->monthes);

        $sql = "select year(wrkdate) as year, month(wrkdate) as month, count(DISTINCT day(wrkdate)) as days
                    from driver_works
                    where wrkenddt is not null
                    group by year(wrkdate), month(wrkdate)
                    order by 1 desc, 2 desc";
        $yms = DB::select(DB::raw($sql));
//        dd($yms);
        $data->yms = [];
        foreach ($yms as $ym) {
            $data->yms[$ym->year . '-' . $ym->month] = $ym->year . ', ' . $month_names[$ym->month];
        }
        //dd($data->yms);

        $data->begdate = $begdate;
        $data->enddate = $enddate;
        $data->days = $tstdate->format('t');;
//        $data->ownorgs = org::lstFor_cached([
//            'in_driver_works_ownorgid' => 1,
//        ]);
        //dd($data->ownorgs);

        return view('driver_works.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public function rep76(Request $request)
    {
        //
        $report_id = 76;
        $rep = report::find($report_id);
        //dd($rep->name);
//        dd(str_replace( ' ', '_', $rep->name) );

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данной информацией!']);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $export2xls = $request->get('xls') ?? 0;
        $fdom = new DateTime('first day of this month');
        $fdomc = $fdom->format('Y-m-d');
        $year = $fdom->format('Y');
        $ldom = new DateTime('last day of this month');
        $ldomc = $ldom->format('Y-m-d');
        $curdate = new DateTime();
        $cd = $curdate->format('Y-m-d');

        $month = date("n");
        $yearQuarter = ceil($month / 3);

        $begdate = today();

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_month' => $month
            , 's_year' => $year
            , 's_begdate' => $begdate->format('Y-m-01')
            , 's_enddate' => $begdate->format('Y-m-t')
            , 's_mchnname' => ''
            , 's_mchntypeid' => ''
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);
        //dd($search_params, isset($search_params['s_ownorgid']) );

        $begdate = today();
        //$search_params['s_begdate'] = $begdate->format('Y-m-d');
        //$search_params['s_enddate'] = $begdate->format('Y-m-t');

        $recs = null;

        $s_year = $search_params['s_year'];
        $s_month = $search_params['s_month'];

        $s_begdate = $search_params['s_begdate'];
        $s_enddate = $search_params['s_enddate'];

        $s_ownorgid = $search_params['s_ownorgid'] ?? null;
        $s_mchnname = $search_params['s_mchnname'];
        $s_mchntypeid = $search_params['s_mchntypeid'];

        if (($s_begdate <> '' and $s_enddate <> '')) {

            $s_yr_mn = date_format(date_create($s_begdate), 'Y-m');
            //dd($s_yr_mn);


            //2026-04-21 вариант с расчетом объема заправок
            $sql = "select a.*, b.fuel_qty
                    from (
                        SELECT dw.machineid, m.name as mchn_name
                                , mt.name as mchntype_name
                                , m.orgid, o.name as org_name
                                , sum(dw.meter_qty) as meter_qty
                                , sum(dw.fuel_spentqty ) as fuel_spentqty
                                , min(dw.wrkdate) as min_wrkdate
                                , max(dw.wrkdate) as max_wrkdate
                                , count(distinct dw.wrkdate) as wrkdays
                                , sum(if( dw.meter_qty is null, 0, 1)) as meter_wrkdays
                            FROM driver_works dw
                            join machines m on m.id=dw.machineid
                            join orgs o on o.id=m.orgid
                            join mchntypes as mt on mt.id=m.mchntypeid
                            where 1=1
                                    and dw.wrkdate between '{$s_begdate}' and '{$s_enddate}'
                                    -- and exists(select 1 from driver_works dd where dd.machineid=dw.machineid and dd.meter_qty is not null) and m.mchntypeid=7
                            ";
            if ($s_ownorgid <> '') {
                $sql .= " and m.orgid={$s_ownorgid}";
            }
            if ($s_mchntypeid <> '') {
                $sql .= " and m.mchntypeid={$s_mchntypeid}";
            }
            if ($s_mchnname <> '') {
                $sql .= " and ucase(m.name) like'%{$s_mchnname}%'";
            }

            $sql .= " group by dw.machineid
                        ) a
                       join (
                           select p.machineid, sum(fuel_qty) as fuel_qty
                                from fuelcard_pays p
                                where 1=1
                                    and p.paydate between '{$s_begdate}' and '{$s_enddate}'
                                group by p.machineid
                            ) b on b.machineid=a.machineid
                        order by a.mchn_name";

//            $sql = " SELECT dw.machineid, m.name as mchn_name
//                            , mt.name as mchntype_name
//	                        , m.orgid, o.name as org_name
//                            , sum(dw.meter_qty) as meter_qty
//                            , sum(dw.fuel_spentqty ) as fuel_spentqty
//                            , min(dw.wrkdate) as min_wrkdate
//                            , max(dw.wrkdate) as max_wrkdate
//                            , count(distinct dw.wrkdate) as wrkdays
//                            , sum(if( dw.meter_qty is null, 0, 1)) as meter_wrkdays
//                            FROM driver_works dw
//                            join machines m on m.id=dw.machineid
//                            join orgs o on o.id=m.orgid
//                            join mchntypes as mt on mt.id=m.mchntypeid
//                            where 1=1
//                            and dw.wrkdate between '{$s_begdate}' and '{$s_enddate}'
//                            and exists(select 1 from driver_works dd where dd.machineid=dw.machineid and dd.meter_qty is not null)";
//            // and meter_qty is not null
//            if ($s_ownorgid <> '') {
//                $sql .= " and m.orgid={$s_ownorgid}";
//            }
//            if ($s_mchntypeid <> '') {
//                $sql .= " and m.mchntypeid={$s_mchntypeid}";
//            }
//            if ($s_mchnname <> '') {
//                $sql .= " and ucase(m.name) like'%{$s_mchnname}%'";
//            }
//
//            $sql .= " group by dw.machineid";
//            $sql .= " order by m.name";

            //dd($s_begdate, $s_yr_mn, $sql);
            $recs = DB::select(DB::raw($sql));
//                dd($sql, $recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $s_year . ' ' . $s_month);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->ownorgs = org::lstFor_cached([
//            'in_driver_works_ownorgid' => 1,
            'orgs_in_machines_with_mileage' => 1,
        ]);
//           dd($data->ownorgs);

        $data->mchntypes = mchntype::lstFor_cached([
            'mchntype_in_machines_with_mileage' => 1,
        ]);
//        dd($data->mchntypes);
//        dd($data);

        // Подзаголовок с выводом значенией параметров отбора
        $data->sub_title = '';

        if (isset($s_begdate) and $s_begdate <> '')
            $data->sub_title .= ' с ' . date_format(date_create($s_begdate), 'd.m.Y');
        if (isset($s_enddate) and $s_enddate <> '')
            $data->sub_title .= ' по ' . date_format(date_create($s_enddate), 'd.m.Y');

        if (isset($s_ownorgid) and $s_ownorgid <> '') {
            if ($data->sub_title <> '')
                $data->sub_title .= ',';
            $data->sub_title .= ' организация: ' . $data->ownorgs[$search_params['s_ownorgid']] ?? '-';
        }

        if ($export2xls == "1") {

            $response = Excel::download(
                new rep2xlsx_vdr_export('exports.rep58xls', $recs, $data),
                str_replace(' ', '_', $rep->name) . "_{$s_begdate}_{$s_enddate}.xlsx",
                \Maatwebsite\Excel\Excel::XLSX);

            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }
        //dd($data);

        return view('driver_works.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    function rep77(Request $request)
    {
        //2026-03-05. Начисления и удержания за месяц. Версия 2.1
        // Попробуем изменить порядок формирования:
        // 1. Отберем работников, как основной "скелет" набора данных
        // 2. К каждому сотруднику привяжем поднаборы данных: - по рабочим часам и ставкам, - по начислениям/удержаниям

        $report_id = 77;

        $returl = $request->get('returl') ?? route('stf_chrg_calcs.index');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;


        $data = new \stdClass();
        $data->returl = $returl;

        $param_names = [
            's_ym' => null,
            's_period' => null,
            's_ownorgid' => null,
            's_depname' => null,
            's_stf_name' => null,
        ];
        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $s_ym = $search_params['s_ym'];
        $s_period = $search_params['s_period'];
        $s_ownorgid = $search_params['s_ownorgid'];
        $s_depname = $search_params['s_depname'];
        $s_stf_name = $search_params['s_stf_name'];

//        if ($s_ym <> '') {
        if ($s_period <> '') {
            $data->s_period = $s_period;
            $dates = explode("..", $s_period);
            $data->begdate = date_create($dates[0])->format('Y-m-d');
            $data->enddate = date_create($dates[1])->format('Y-m-d');
            $s_begdate = $data->begdate;
            $s_enddate = $data->enddate;
            " and forbegdate <= {$s_enddate}' and forEndDate >= '{$s_begdate}'";

            //dd($s_period, $dates, $data->begdate, $data->enddate);

            // основной запрос - сотрудники, соответствующие условиям запроса
            $sql =
                "select os.id, os.id as staffid, os.orgid, os.name, os.depname
                    , if(f.flagtypeid is null, 0, 1) as official_job
                    , (select count(distinct dw.wrkdate)
                        from driver_works as dw
                        where dw.staffid=os.id
                          and dw.wrkdate between '{$s_begdate}' and '{$s_enddate}') as wrkdays
	                FROM orgstaff os
	                left join objflags f on f.flagtypeid=192 and f.sysobjid=121 and f.objid=os.id
                    where exists(select 1 from stf_chrg_calcs scc where scc.staffid=os.id
                        and scc.forbegdate between '{$s_begdate}' and '{$s_enddate}'
                        and scc.forenddate between '{$s_begdate}' and '{$s_enddate}')";
            if (isset($s_ownorgid))
                $sql .= " and os.orgid={$s_ownorgid}";

            if (isset($s_depname))
                $sql .= " and ucase(os.depname)='{$s_depname}'";

            if (isset($s_stf_name))
                // $sql .= " and concat(' ', os.lname, ' ', os.fname, ' ', os.mname) like '% {$s_stf_name}%'";
                $sql .= " and concat(' ', os.name) like '% {$s_stf_name}%'";

            $sql .= " order by ucase(os.depname), os.lname, os.fname";

            $recs = \Illuminate\Support\Facades\DB::select(DB::raw($sql));
            //dd($sql, $recs);

            // 2. дополним основной набор поднабором данных о рабочих часах и ставках
            foreach ($recs as $stf) {

                $sql =
                    "select dw.wrktypeid, wt.name as wrktypename
                            , dw.day_hr_rate, sum(dw.day_wrkhrs) as day_wrkhrs
                            , dw.night_hr_rate, sum(dw.night_wrkhrs) as night_wrkhrs
                            , sum(dw.salary_sum) as salary_sum
                            , sum(b11.day_hrs) as b11_day_hrs, sum(b11.night_hrs) as b11_night_hrs, sum(b11.brk_sum) as repair_sum
                            , sum(b22.day_hrs) as b22_day_hrs, sum(b22.night_hrs) as b22_night_hrs, sum(b22.brk_sum) as wait_sum
                        from driver_works as dw
                        join wrktypes as wt on wt.id=dw.wrktypeid
                        left join dw_breaks b11 on b11.dw_id=dw.id and b11.wrktypeid=11 -- repair_sum
                        left join dw_breaks b22 on b22.dw_id=dw.id and b22.wrktypeid=22	-- wait_sum
                        where dw.staffid={$stf->id}
                            and dw.wrkdate between '{$s_begdate}' and '{$s_enddate}'
                            and (dw.day_hr_rate is not null or dw.night_hr_rate is not null)
                        group by dw.wrktypeid, dw.day_hr_rate, dw.night_hr_rate";

                $stf->wrkhrs = DB::select(DB::raw($sql));
                $stf->dw_cnt = count($stf->wrkhrs);
                //dd($stf, $stf->wrkhrs, $stf->dw_cnt);

                // начисления по сотруднику
                $sql =
                    "SELECT  ct.dir, oc.chargetypeid, ct.name as chargetype_name, sum(scc.charge_sum) charge_sum
                    FROM stf_chrg_calcs as scc
                    join org_charges as oc 	on oc.id=scc.orgchargeid
                    join chargetypes as ct on ct.id=oc.chargetypeid
                    where scc.staffid = {$stf->id}
                    and scc.charge_sum <> 0
                    and scc.forbegdate between '{$s_begdate}' and '{$s_enddate}'
                    and scc.forenddate between '{$s_begdate}' and '{$s_enddate}'
                    group by oc.chargetypeid";

                $stf->charges = DB::select(DB::raw($sql));
                //dd($stf, $stf->charges);

            }
            //dd($recs);


            // Какие виды начислений/Удержаний попали в рассматриваемый месяц
            $sql = "SELECT ct.id as id, ct.name, sum(scc.charge_sum) charge_sum, count(1) as cnt
                    FROM stf_chrg_calcs as scc
                    join orgstaff os on os.id=scc.staffid
                    join org_charges as oc 	on oc.id=scc.orgchargeid
                    join chargetypes as ct on ct.id=oc.chargetypeid
                    where scc.charge_sum <> 0";

//            if (isset($s_ym))
//                $sql .= " and forbegdate <= '" . date_create($data->enddate)->format('Y-m-d') . "'"
//                    . " and forEndDate >= '" . date_create($data->begdate)->format('Y-m-d') . "'";

            // 2025-08-10
            if (isset($s_period))
                //$sql .= " and concat(scc.forbegdate, '..', scc.forEndDate) = '{$s_period}'";
                $sql .= " and scc.forbegdate between '{$s_begdate}' and '{$s_enddate}'
                          and scc.forenddate between '{$s_begdate}' and '{$s_enddate}'";

            if (isset($s_ownorgid))
                $sql .= " and os.orgid={$s_ownorgid}";

            if (isset($s_depname))
                $sql .= " and ucase(os.depname)='{$s_depname}'";

            if (isset($s_stf_name))
                //$sql .= " and concat(' ', os.lname, ' ', os.fname, ' ', os.mname) like '% {$s_stf_name}%'";
                $sql .= " and concat(' ', os.name) like '% {$s_stf_name}%'";

            $sql .= " group by ct.id
                    order by ct.dir desc, ct.ordr, cnt desc";
            $data->cols = DB::select(DB::raw($sql));
            //dd($sql, $data->cols);

        } else {
            $recs = null;
        }

        // Заполним массив "Год.Месяц" уникальными значениями из первичных данных
        $month_names = Config::get('constants.monthes');
        Cache::forget('stf_chrg_calc_monthes');
        $data->yms = Cache::remember('stf_chrg_calc_monthes', now()->addMinutes(15)
            , function () {
                return stf_chrg_calc::selectRaw("date_format(forbegdate, '%Y-%m') as ym")->distinct()->orderby('ym', 'desc')
                    ->get()->pluck('ym', 'ym')->toArray();
            });
        //dd($data->monthes);
        foreach ($data->yms as $key => $val) {
            $y = substr($val, 0, 4);
            $m = 0 + substr($val, 5);

            $data->yms[$val] = $month_names[$m] . ' ' . $y;
            //dd($key,$val, $m, $y, $data->yms[$val]);
        }
        //dd($data->yms);
        //dd($data, $sql, $recs);

        $data->for_periods = stf_chrg_calc::
        selectRaw("concat(forbegdate,'..', forenddate) as period")
            ->distinct()
            ->orderby('period', 'desc')
            ->get()
            ->pluck('period', 'period')->toArray();
        //dd($data->for_periods);

        $data->ownorgs = org::lstFor_cached(['in_stf_chrg_calcs' => 1]);

//        $tarr = DB::select(DB::raw("SELECT distinct upper (os.depname) as dep_name
//                    FROM stf_chrg_calcs as scc
//                    join orgstaff os on os.id=scc.staffid
//                    join orgs o on o.id=os.orgid
//                    where os.depname is not null
//                    and scc.docdate>='2024-01-01'
//                    order by 1"));

        $tarr = DB::select("SELECT distinct upper (os.depname) as depname
                    FROM stf_chrg_calcs as scc
                    join orgstaff os on os.id=scc.staffid
                    join orgs o on o.id=os.orgid
                    where trim(os.depname) <> ''
                    -- and scc.docdate>='2024-01-01'
                    order by 1");

        //преобразуем индексированный массив в ассоциативный
        $data->depnames = array_column($tarr, 'depname', 'depname');
//        dd($tarr, $data->ownorgs, $data->depnames);

        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен отчет;');

        if ($export2xls == "1") {
            //dd($recs);
            //$response = Excel::download(new rep54Export($recs, $data), "Платежи за " . Str::slug($data->$date) . ".xlsx", \Maatwebsite\Excel\Excel::XLSX);
            $response = Excel::download(new rep2xlsx_vdr_export('exports.rep56xls', $recs, $data), "Начисления_и_удержания_{$s_period}.xlsx", \Maatwebsite\Excel\Excel::XLSX);

            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }

        return view('driver_works.rep' . $report_id, compact('search_params', 'data', 'recs'));
    }
}
