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
use App\report;
use App\org;
use App\machine;
use App\objlog;
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
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $begdate = today();
        //$search_params['s_begdate'] = $begdate->format('Y-m-d');
        //$search_params['s_enddate'] = $begdate->format('Y-m-t');

        $recs = null;

        $s_year = $search_params['s_year'];
        $s_month = $search_params['s_month'];

        $s_begdate = $search_params['s_begdate'];
        $s_enddate = $search_params['s_enddate'];

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
    left join stf_payrolltypes as spt on spt.staffid=a.staffid and '{$s_begdate}' between spt.begdate and ifnull( spt.enddate, '2024-09-01')
    left join payrolltypes pt on pt.id=spt.payrolltypeid
    order by staff_name, wt.name";

                //dd($s_begdate, $s_yr_mn, $sql);
                $recs = DB::select(DB::raw($sql));
                //dd($sql, $recs);

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

//        $data->ownorgs = org::lstFor_cached([
//            'in_driver_works_ownorgid' => 1,
//        ]);
        //dd($data->ownorgs);

        if ($export2xls == "1") {

            $data->period_title = '';

            if (isset($s_begdate) and $s_begdate <> '')
                $data->period_title .= ' с ' . date_format(date_create($s_begdate), 'd.m.Y');
            if (isset($s_enddate) and $s_enddate <> '')
                $data->period_title .= ' по ' . date_format(date_create($s_enddate), 'd.m.Y');

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

}
