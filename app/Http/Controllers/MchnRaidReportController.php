<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\driver_work;
use App\Exports\rep46Export;
use App\Exports\rep61Export;
use App\Exports\rep70Export;
use App\Exports\rep71Export;
use App\mr_oper;
use App\Exports\InvoicesExport;
use App\Exports\PayPlanExport;
use App\mchn_raid;
use App\opertype;
use App\orgstaff;
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

class MchnRaidReportController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;  //reports
        //$this->objcode = 'reports';
        $this->objcode = 'mchn_raids';
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


    public function rep46_0(Request $request)
    {
        //

        $report_id = 46;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с платежами для этой организации!']);

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

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_period_type' => 9
            , 's_begdate' => $cd //$fdomc
            , 's_enddate' => $cd //$ldomc
            , 's_month' => $month
            , 's_quarter' => $yearQuarter
            , 's_year' => $year
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);


        //зачистим ненужные параметры поиска
        switch ($search_params['s_period_type'] ?? 0) {
            case 1: //месяц/год
                $year = $search_params['s_year'];
                $month = $search_params['s_month'];
                $begdate = new DateTime($year . '-' . $month . '-1 00:00:00');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $begdate->format('Y-m-t');
                break;
            case 2: //квартал/год

                $year = $search_params['s_year'];
                $quarter = $search_params['s_quarter'];
                $begdate = new DateTime($year . '-' . (3 * $quarter - 2) . '-1 00:00:00');
                $enddate = new DateTime($year . '-' . (3 * $quarter) . '-' . ($quarter == 1 || $quarter == 4 ? 31 : 30) . ' 23:59:59');
                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;

            case 3://год
                $year = $search_params['s_year'];
                $begdate = new DateTime($year . '-1-1 00:00:00');
                $enddate = new DateTime($year . '-12-31 23:59:59');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;
            case 9://календарь
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
                break;
            default:
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
        }

        $need_search = false;
        $sc = "1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;


                if ($item == 's_ownorgid') {
                    $sc = $sc . " and mr.load_ownorgid = '{$val}'";

                } elseif ($item == 's_begdate') {
                    $sc = $sc . " and mr.wrkdate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc = $sc . " and mr.wrkdate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(mr.docdate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(mr.docdate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(mr.docdate) = '{$val}'";

                }
            }
        }

        $recs = null;
        $recs2 = null;

        if ($need_search) {

            //1-й набор - сырые данные по перевозкам за период
            $recs = mchn_raid::
            from('mchn_raids as mr')
                ->leftjoin('orgs as oo', 'oo.id', 'mr.unload_ownorgid')
                ->leftjoin('orgs as o', 'o.id', 'mr.orgid')
                ->leftjoin('refitems as ri', 'ri.id', 'mr.unload_refitmid')
                //->leftjoin('users as u_d', 'u_d.id', 'mr.disp_userid')
                ->leftjoin('orgstaff as u_d', 'u_d.id', 'mr.disp_staffid')
                ->whereRaw($sc);

            if (1 == 0) {
                $recs = $recs->select(
                    'mr.*', 'o.name'
                    , 'mr.unload_qty'
                    , db::raw("mr.unload_qty*mr.unload_price as unload_sum")
                    , 'oo.name as ownorgname'
                    , 'o.name as orgname'
                    , db::raw("concat(ifnull(u_d.fname,''),' ',u_d.lname) as dispuser_name")
                    , 'ri.name as refitm_name'
                    , db::raw("orgSaldo_onDate(mr.orgid, mr.unload_ownorgid, mr.wrkdate) as org_saldo")
                )
                    ->orderby('mr.wrkdate', 'asc')
                    ->orderby('mr.org_name', 'asc')
                    ->get();
            } else {
                $recs = $recs->select(
                    'mr.wrkdate'
                    , 'mr.unload_ownorgid', 'oo.name as ownorgname'
                    , 'mr.orgid', db::raw("max(org_name) as orgname")
                    , 'unload_placeid', db::raw("MAX(mr.unload_placename) as unload_placename")
                    , 'disp_staffid', db::raw("max(concat(ifnull(u_d.fname,''),' ',u_d.lname)) as dispuser_name")
                    , 'mr.unload_refitmid', 'ri.name as refitm_name'
                    , db::raw("sum(mr.raid_qty) as raid_qty")
                    , db::raw("sum(mr.unload_qty) as unload_qty")
                    , db::raw("sum(mr.unload_qty*mr.unload_price) as unload_sum")
                    , db::raw("orgSaldo_onDate(mr.orgid, mr.unload_ownorgid, mr.wrkdate) as org_saldo")
                )
                    ->groupBy(['mr.wrkdate', 'mr.unload_ownorgid', 'mr.orgid', 'disp_staffid', 'unload_placeid', 'mr.unload_refitmid'])
                    ->orderby('mr.wrkdate', 'asc')
                    ->orderby('orgname', 'asc')
                    ->get();
            }

            //2-й набор - группировка по местам погрузки
            $recs2 = mchn_raid::
            from('mchn_raids as mr')
                ->leftjoin('orgs as o', 'o.id', 'mr.suporgid')
                ->leftjoin('org_places as p', 'p.id', 'mr.load_placeid')
                ->leftjoin('refitems as ri', 'ri.id', 'mr.load_refitmid')
                ->whereRaw($sc);

            $recs2 = $recs2->select(
                'mr.suporgid', 'o.name as suporg_name'
                , 'mr.load_placeid', 'p.name as load_placename', 'p.address as load_place_address'
                , 'mr.load_price'
                , 'mr.load_refitmid'
                , db::raw("sum(mr.load_qty) as load_qty")
                , db::raw("sum(mr.load_sum) as load_sum")
                , 'ri.name as refitm_name'
            )
                ->groupBy('mr.suporgid')
                ->groupBy('mr.load_placeid')
                ->groupBy('mr.load_price')
                ->groupBy('mr.load_refitmid')
                ->orderby('p.name', 'asc')
                ->orderby('ri.name', 'asc')
                ->get();


            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->period_types = [1 => 'месяц', 2 => 'квартал', 3 => 'год', 9 => 'календарь'];

        $data->monthes = Config::get('constants.monthes');
        $data->quarters = [1 => 1, 2 => 2, 3 => 3, 4 => 4];

        $data->years = Cache::remember('orgplnpays_years', now()->addMinutes(55)
            , function () {
                return mchn_raid::selectRaw("year(wrkdate) as year")->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });


        $data->ownorgs = org::lstFor_cached([
            'in_mchn_raids_ownorgid' => 1,
        ]);

        return view('mchn_raids.rep' . $report_id, compact('recs', 'recs2', 'search_params', 'data'));
    }

    public function rep46(Request $request)
    {
        //
        $report_id = 46;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с платежами для этой организации!']);

        $export2xls = $request->get('xls') ?? 0;

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

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_period_type' => 1
            , 's_begdate' => $cd //$fdomc
            , 's_enddate' => $cd //$ldomc
            , 's_month' => $month
            , 's_quarter' => $yearQuarter
            , 's_year' => $year
            , 's_orgid' => ''
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        //зачистим ненужные параметры поиска
        switch ($search_params['s_period_type'] ?? 0) {
            case 1: //дата - 1 день
                $search_params['s_enddate'] = $search_params['s_begdate'];
                break;

            case 2: //месяц/год
                $year = $search_params['s_year'];
                $month = $search_params['s_month'];
                $begdate = new DateTime($year . '-' . $month . '-1 00:00:00');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $begdate->format('Y-m-t');
                break;

            case 3: //квартал/год

                $year = $search_params['s_year'];
                $quarter = $search_params['s_quarter'];
                $begdate = new DateTime($year . '-' . (3 * $quarter - 2) . '-1 00:00:00');
                $enddate = new DateTime($year . '-' . (3 * $quarter) . '-' . ($quarter == 1 || $quarter == 4 ? 31 : 30) . ' 23:59:59');
                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;

            case 4://год
                $year = $search_params['s_year'];
                $begdate = new DateTime($year . '-1-1 00:00:00');
                $enddate = new DateTime($year . '-12-31 23:59:59');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;

            case 9://календарь
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
                break;
            default:
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
        }

        $need_search = false;
        $sc = "1=1";
        $sc1 = " and 1=1";
        $sc2 = " and 1=1";
        $sc3 = " and 1=1";
        $sc4 = " and 1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;


                if ($item == 's_ownorgid') {
                    ////$sc = $sc . " and '{$val}' in (mro.suporgid, mro.orgid)";
                    //$sc = $sc . " and mr.load_ownorgid = '{$val}'";
                    $sc1 = $sc1 . " and mro.suporgid = {$val}";
                    $sc2 = $sc2 . " and mro.orgid = {$val}";
                    $sc3 = $sc3 . " and mr.load_ownorgid = {$val}";

                } elseif ($item == 's_orgid') {
                    //$sc = $sc . " and '{$val}' in (mro.suporgid, mro.orgid)";
                    $sc = $sc . " and exists (select 1 from mr_opers as mro1 where mro1.mr_id=mr.id and '{$val}' in (mro1.suporgid, mro1.orgid))";

                } elseif ($item == 's_begdate') {
                    $sc = $sc . " and mr.wrkdate >= '{$val}'";
                    $sc4 = $sc4 . " and dw.wrkdate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc = $sc . " and mr.wrkdate <= '{$val}'";
                    $sc4 = $sc4 . " and dw.wrkdate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(mr.docdate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(mr.docdate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(mr.docdate) = '{$val}'";

                }
            }
        }
//var_dump( $sc,$sc1, $sc2, $sc3, $sc4, $search_params['s_begdate'], $search_params['s_enddate']);

        $recs = $recs2 = $recs3 = $recs4 = null;

        if ($need_search) {

            //1-й набор - продажи ГК за период
            $recs = mr_oper::from('mr_opers as mro')
                ->join('mchn_raids as mr', 'mr.id', 'mro.mr_id')
                ->join('refitems as ri', 'ri.id', 'mro.refitmid')
                ->leftjoin('orgs as oo', 'oo.id', 'mro.suporgid')
                ->leftjoin('orgs as o', 'o.id', 'mro.orgid')
                ->leftjoin('orgstaff as u_d', 'u_d.id', 'mro.disp_staffid')
                ->whereRaw($sc . $sc1)
                ->where('mro.sale_dir', +1);

            $recs = $recs->select(
                'mro.suporgid', 'oo.name as ownorgname'
                , 'mro.orgid', db::raw("max(o.name) as orgname")
                , 'mro.org_placeid as unload_placeid', db::raw("MAX(mro.org_placename) as unload_placename")
                , 'mro.disp_staffid', db::raw("max(concat(ifnull(u_d.fname,''),' ',u_d.lname)) as dispuser_name")
                , 'mro.refitmid as unload_refitmid', 'ri.name as refitm_name'
                , 'mro.itm_price'
                //, 'mr.wrkdate'

                , db::raw("max(ri.unit) as unit")

                , db::raw("sum(mro.raid_qty) as raid_qty")
                , db::raw("sum(mro.itm_qty) as unload_qty")
                , db::raw("sum(mro.itm_qty*mro.itm_price) as unload_sum")
                , db::raw("orgSaldo_onDate(mro.orgid, mro.suporgid, max(mr.wrkdate)) as org_saldo")
                , db::raw("min(mr.wrkdate) as min_wrkdate")
                , db::raw("max(mr.wrkdate) as max_wrkdate")
            )
                //->groupBy(['mr.wrkdate', 'mro.suporgid', 'mro.orgid', 'mro.disp_staffid', 'mro.org_placeid', 'mro.refitmid'])
                ->groupBy(['mro.suporgid', 'mro.orgid', 'mro.disp_staffid'
                    , 'mro.org_placeid', db::raw("lcase(mro.org_placename)")
                    , 'mro.refitmid', 'mro.itm_price'])
                //->orderby('mr.wrkdate', 'asc')
                ->orderby('ownorgname', 'asc')
                ->orderby('suporgid', 'asc')
                ->orderby('orgname', 'asc')
                ->get();
            //dd($recs);

            //2-й набор - группировка по местам погрузки (наши покупки у поставщиков)
            $recs2 = mr_oper::from('mr_opers as mro')
                ->join('mchn_raids as mr', 'mr.id', 'mro.mr_id')
                ->leftjoin('orgs as oo', 'oo.id', 'mro.orgid')
                ->leftjoin('orgs as o', 'o.id', 'mro.suporgid')
                ->leftjoin('org_places as p', 'p.id', 'mro.sup_placeid')
                ->leftjoin('refitems as ri', 'ri.id', 'mro.refitmid')
                ->whereRaw($sc . $sc2)
                ->where('mro.sale_dir', -1)
                ->select(
                    'mro.orgid as ownorgid', 'oo.name as ownorg_name'
                    , 'mro.suporgid', 'o.name as suporg_name'
                    , 'mro.sup_placeid as load_placeid', 'p.name as load_placename', 'p.address as load_place_address'
                    , 'mro.itm_price as load_price'
                    , 'mro.refitmid'
                    , db::raw("max(ri.unit) as unit")
                    , db::raw("sum(mro.itm_qty) as load_qty")
                    , db::raw("sum(mro.itm_qty*mro.itm_price) as load_sum")
                    , 'ri.name as refitm_name'
                )
                ->groupBy('mro.orgid')
                ->groupBy('mro.suporgid')
                ->groupBy('mro.sup_placeid')
                ->groupBy('mro.itm_price')
                ->groupBy('mro.refitmid')
                ->orderby('oo.name', 'asc')
                ->orderby('mro.orgid', 'asc')
                ->orderby('p.name', 'asc')
                ->orderby('ri.name', 'asc')
                ->get();


            //3-й набор - итоги по машинам/водителям
            /*$recs3 = mchn_raid::from('mchn_raids as mr')
                //->join('mr_opers as mro', 'mro.mr_id', 'mr.id')
                ->join('machines as m', 'm.id', 'mr.machineid')
                ->join('orgstaff as os', 'os.id', 'mr.driverid')
                ->leftjoin('driver_works as dw', 'dw.id', 'mr.dw_id')
                ->whereRaw($sc . $sc3)
                ->select(
                    'mr.machineid', db::raw("concat(m.name,' ',m.regnum) as machine_name")
                    , 'mr.driverid', 'os.name as driver_name'
                    , db::raw("sum(mr.raid_qty) as raid_qty")
                    , db::raw("sum(mr.raid_qty*mr.raid_salary) as salary")
                    , db::raw("max(dw.repair_hrs) as repair_hrs")
                    , db::raw("max(dw.repair_sum) as repair_sum")
                    , db::raw("max(dw.pdt_hrs) as pdt_hrs")
                    , db::raw("max(dw.pdt_sum) as pdt_sum")
                )
                ->groupBy('mr.machineid')
                ->groupBy('mr.driverid')
                ->orderBy('driver_name')
                ->get();
            */

            $recs3 = driver_work::from('driver_works as dw')
                ->join('machines as m', 'm.id', 'dw.machineid')
                ->join('orgstaff as os', 'os.id', 'dw.staffid')
//                ->whereRaw($sc . $sc3)
                ->whereRaw('1=1 ' . $sc4)
                ->select(
                    'dw.machineid', db::raw("concat(m.name,' ',m.regnum) as machine_name")
                    , 'dw.staffid as driverid', 'os.name as driver_name'
                    , db::raw("sum( (select sum(mr.raid_qty) from mchn_raids as mr where mr.dw_id = dw.id)) as raid_qty")
                    //, db::raw("sum(dw.day_wrkhrs) day_wrkhrs")
                    //, db::raw("sum(dw.night_wrkhrs) night_wrkhrs")
                    , db::raw("sum(dw.day_wrkhrs + dw.night_wrkhrs) wrkhrs")
                    , db::raw("sum(dw.day_wrkhrs*dw.day_hr_rate + dw.night_wrkhrs*dw.night_hr_rate) hr_sum")
                    , db::raw("sum(dw.day_brkhrs + dw.night_brkhrs) brkhrs")
                    , db::raw("sum(dw.breaks_sum) breaks_sum")
                )
                ->groupBy('dw.machineid')
                ->groupBy('dw.staffid')
                ->orderBy('driver_name')
                ->get();
//            dd($sc, $recs3);

            //2024-02-11 Поступления и реализация на склад
            $s_begdate = $search_params['s_begdate'];
            $s_enddate = $search_params['s_enddate'];
            if (1 == 0) {
                $sql = "select a.refitmid, ri.name as refitm_name, ri.unit as refitm_unit
	            , sum(a.pre_qty) as pre_qty
	            , sum(a.inp_qty) as inp_qty
	            , sum(a.out_qty) as out_qty
	            , sum(a.inp_sum) as inp_sum
	            , sum(a.sale_sum) as sale_sum
                from (
                    SELECT i.refitmid
                        , SUM(IF(t.forStock=1, i.qty, 0) - IF(t.forStock=-1, i.qty, 0)) as pre_qty
                        , null as inp_qty, null as out_qty,null as inp_sum, null as sale_sum
                    FROM wrhdoclst as i
                    INNER JOIN wrhdocs as d ON d.id = i.docid
                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                    WHERE d.docsigned=1 and  d.docdate < '{$s_begdate}'
                    GROUP BY refitmid
                    union all
                    SELECT     i.refitmid, null as pre_qty
                        , SUM(IF(t.forStock= 1, i.qty, null )) as inp_qty
                        , SUM(IF(t.forStock=-1, i.qty, null)) as out_qty
                        , SUM(IF(t.forStock=+1, i.qty*i.price, null)) as inp_sum
                        , SUM(IF(t.forSale= 1, i.qty*i.price, null)) as sale_sum
                    FROM wrhdoclst as i
                    INNER JOIN wrhdocs as d ON d.id = i.docid
                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                    WHERE d.docsigned=1
                    and d.docdate between '{$s_begdate}' and '{$s_enddate}'
                    GROUP BY refitmid
                    ) as a
                INNER JOIN  refitems as ri ON ri.id = a.refitmid
                GROUP BY refitmid
                order by refitm_name";

                $recs4 = DB::select(DB::raw($sql));
            }

            //2024-02-18 Реализация со склада:
            $sql = "SELECT ifnull(wd.saleorgid, wd.ownorgid) as ownorgid, oo.name as ownorg_name
                    , wd.orgid, o.name as org_name
                    , dt.forstock, dt.forsale, wd.docdate
                    , ri.name as refitm_name
                    , ri.unit as refitm_unit
                    , dl.qty, dl.price, dl.qty * dl.price as sum
                    , orgSaldo_onDate(wd.orgid, ifnull(wd.saleorgid, wd.ownorgid), wd.docdate) as org_saldo
                    FROM `wrhdocs` as wd
                    join wrhdoctypes as dt on dt.id=wd.doctypeid
                    join wrhdoclst dl on dl.docid=wd.id
                    join refitems as ri on ri.id=dl.refitmid
                    left join orgs as oo on oo.id = ifnull(wd.saleorgid, wd.ownorgid)
                    left join orgs as o on o.id = wd.orgid
                    WHERE 1
                        and wd.docdate between '{$s_begdate}' and '{$s_enddate}'
                        and dt.forstock<>0
                    order by wd.docdate asc, dt.forstock desc
	                    , ownorg_name, wd.ownorgid
	                    , org_name, wd.orgid, refitm_name";
            $recs4 = DB::select(DB::raw($sql));

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->period_types = [1 => 'день', 2 => 'месяц', 3 => 'квартал', 4 => 'год', 9 => 'календарь'];

        $data->monthes = Config::get('constants.monthes');
        $data->quarters = [1 => 1, 2 => 2, 3 => 3, 4 => 4];

        $data->years = Cache::remember('orgplnpays_years', now()->addMinutes(55)
            , function () {
                return mchn_raid::selectRaw("year(wrkdate) as year")->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });


        $data->ownorgs = org::lstFor_cached([
            'in_mchn_raids_ownorgid' => 1,
            'flagtypeid' => 12,
        ]);
//        $data->orgs = org::lstFor_cached([
//            'in_mr_opers' => 1,
//            'not_flagtypeid' => 12,
//        ]);
        $data->orgs = org::lstFor_cached([
            'in_mr_opers_orgid' => 1,
        ]);

        $s_period_type = $search_params['s_period_type'] ?? '';
        $ownorgid = $search_params['s_ownorgid'] ?? '';
        $s_begdate = $search_params['s_begdate'] ?? '';
        $s_enddate = $search_params['s_enddate'] ?? '';

        //dd($search_params['s_year']);
        $data->period_title = '';
        $data->period_subtitle = '';

        if ($s_period_type == 1) {
            $data->period_title = 'за ' . date_format(date_create($s_begdate), 'd.m.Y');
        } elseif ($s_period_type == 2) {
            $data->period_title = ($data->monthes[$search_params['s_month']] ?? '') . ' ' . ($search_params['s_year'] ?? '');
            $data->period_subtitle = date_format(date_create($s_begdate), 'd.m.Y') . ' - ' . date_format(date_create($s_enddate), 'd.m.Y');
        } elseif ($s_period_type == 3) {
            $data->period_title = $search_params['s_quarter'] . ' квартал ' . ($search_params['s_year'] ?? '');
            $data->period_subtitle = date_format(date_create($s_begdate), 'd.m.Y') . ' - ' . date_format(date_create($s_enddate), 'd.m.Y');
        } elseif ($s_period_type == 4) {
            $data->period_title = ($search_params['s_year'] ?? '') . ' год';
            $data->period_subtitle = date_format(date_create($s_begdate), 'd.m.Y') . ' - ' . date_format(date_create($s_enddate), 'd.m.Y');
        } else {
            if (isset($s_begdate) and $s_begdate <> '')
                $data->period_title .= ' с ' . date_format(date_create($s_begdate), 'd.m.Y');
            if (isset($s_enddate) and $s_enddate <> '')
                $data->period_title .= ' по ' . date_format(date_create($s_enddate), 'd.m.Y');
        }
//dd($s_period_type,$s_begdate, $s_enddate, $data->period_title,  date_format(date_create($s_begdate), 'd.m.Y'));

        if ($export2xls == "1") {
            $response = Excel::download(new rep46Export($data, $recs, $recs2, $recs3), "rep_daily.xlsx", \Maatwebsite\Excel\Excel::XLSX);

            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }

        return view('mchn_raids.rep' . $report_id, compact('recs', 'recs2', 'recs3', 'recs4', 'search_params', 'data'));
    }


    public
    function rep51(Request $request)
    {
        //
        $report_id = 51;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с платежами для этой организации!']);


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

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_period_type' => 9
            , 's_begdate' => $cd //$fdomc
            , 's_enddate' => $cd //$ldomc
            , 's_month' => $month
            , 's_quarter' => $yearQuarter
            , 's_year' => $year
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);
        //dd($year, $month, $param_names, $search_params);


        //месяц/год
        $year = $search_params['s_year'];
        $month = $search_params['s_month'];
        if (isset($year) and isset($month)) {
            $begdate = new DateTime($year . '-' . $month . '-1 00:00:00');

            $search_params['s_begdate'] = $begdate->format('Y-m-d');
            $search_params['s_enddate'] = $begdate->format('Y-m-t');

            // временная корректировка расхождений -------------------------------
            $bdate = $search_params['s_begdate'];
            $edate = $search_params['s_enddate'];
            $cmd = "update driver_works as dw"
                . " set salary_sum = (if(hrs_salary is null, 0, hrs_salary) + if(breaks_sum is null, 0, breaks_sum))"
                . " where 1=1 and dw.wrkdate >= '{$bdate}' and dw.wrkdate <= '{$edate}'"
                . " and if(salary_sum is null, 0, salary_sum)<>( if(hrs_salary is null, 0, hrs_salary) + if(breaks_sum is null, 0, breaks_sum) )";
             DB::statement($cmd);
            //--------------------------------------------------------------------
        }

        $need_search = false;
        $sc = "1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;


                if ($item == 's_ownorgid') {
                    $sc = $sc . " and os.orgid = '{$val}'";

                } elseif ($item == 's_begdate') {
                    $sc = $sc . " and dw.wrkdate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc = $sc . " and dw.wrkdate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(dw.wrkdate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(dw.wrkdate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(dw.wrkdate) = '{$val}'";

                }
            }
        }

        $recs = null;
        $recs2 = null;

        if ($need_search) {

            $recs = driver_work::from('driver_works as dw')
                ->join('orgstaff as os', 'os.id', 'dw.staffid')
                ->whereRaw($sc)
                ->select('dw.wrkdate', 'dw.staffid'
                    , 'os.lname as staff_lname'
                    , 'os.fname as staff_fname'
                    , 'os.mname as staff_mname'
                    , db::raw("sum(dw.salary_sum) as salary_sum")
                    , db::raw("sum(dw.hrs_salary) as raid_sum")
                    , db::raw("sum(dw.breaks_sum) as pdt_sum")
                //, db::raw("sum(dw.repair_sum) as repair_sum")
                )
                ->groupBy(['dw.wrkdate', 'dw.staffid'])
                ->orderBy('os.lname')
                ->orderBy('os.fname')
                ->orderBy('dw.staffid')
                ->orderBy('dw.wrkdate')
                ->get();
            //dd($sc, $recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->years = Cache::remember('driver_works_years', now()->addMinutes(55)
            , function () {
                return driver_work::selectRaw("year(wrkdate) as year")->distinct()->orderby('year', 'desc')
                    ->get()->pluck('year', 'year')->toArray();
            });
        //dd($data->years);

        $month_names = Config::get('constants.monthes');
        $data->monthes = Cache::remember('driver_works_monthes', now()->addMinutes(15)
            , function () {
                return driver_work::selectRaw("month(wrkdate) as month")->distinct()->orderby('month')
                    ->get()->pluck('month', 'month')->toArray();
            });
        foreach ($data->monthes as $key => $val) {
            //dd($key,$val);
            $data->monthes[$val] = $month_names[$key];
        }
        //dd($data->monthes);

        $data->ownorgs = org::lstFor_cached([
            'in_driver_works_ownorgid' => 1,
        ]);
        //dd($data->ownorgs);

        return view('driver_works.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public
    function rep52(Request $request)
    {
        //
        $report_id = 52;

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

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_month' => $month
            , 's_year' => $year
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $begdate = today();
        $search_params['s_begdate'] = $begdate->format('Y-m-d');
        $search_params['s_enddate'] = $begdate->format('Y-m-t');

        $recs = null;

        $s_year = $search_params['s_year'];
        $s_month = $search_params['s_month'];

        if ($s_year <> '' and $s_month <> '') {

            $sql = " select staffid, os.name as staff_name, os.lname as staff_lname, os.fname as staff_fname, os.mname as staff_mname
            , os.postname, sum(driver_sum) as driver_sum, sum(salary_sum) as salary_sum from (
    SELECT mr.driverid as staffid, DATE_FORMAT(mr.wrkdate,'%Y-%m') as ym , sum(mro.driver_sum) as driver_sum, null as salary_sum
        FROM `mr_opers` as mro
        join mchn_raids as mr on mr.id=mro.mr_id
        where mro.driver_sum >0
            and year(mr.wrkdate)={$s_year}
            and month(mr.wrkdate)={$s_month}
            and mr.opertypeid in(3,4,9)
        group by mr.driverid,ym
    union all
    select staffid, DATE_FORMAT(ss.wrkbegdate,'%Y-%m') as ym , null as driver_sum, sum(ss.salary_sum) as salary_sum
        from stf_salaries as ss
        where year(ss.wrkbegdate)={$s_year}
            and month(ss.wrkbegdate)={$s_month}
        group by ss.staffid,ym
     ) as a
    join orgstaff as os on os.id=a.staffid
    group by staffid";

            $recs = DB::select(DB::raw($sql));
            //dd($sql, $recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $s_year . ' ' . $s_month);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->years = Cache::remember('stf_salary_years', now()->addMinutes(55)
            , function () {
                return stf_salary::selectRaw("year(wrkbegdate) as year")->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });
        //dd($data->years);

        $month_names = Config::get('constants.monthes');
        $data->monthes = Cache::remember('stf_salary_monthes', now()->addMinutes(15)
            , function () {
                return stf_salary::selectRaw("month(wrkbegdate) as month")->distinct()->orderby('month')
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

        return view('driver_works.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }


    public
    function prnt_table(Request $request, $docid)
    {
        //dd($docid);
        $prodplan = prodplan::find($docid);
        if (!isset($prodplan))
            return redirect(route('prodplans.index'))->with(['error' => 'ППР не найден']);

        $items = prodplan_item::from('prodplan_items as i')
            ->leftJoin('prodplan_items as i1', 'i1.id', 'i.parid')
            ->leftJoin(DB::raw('(SELECT p.ppiid, sum(edi.itmsum/edi.qty*p.plnqty) as getsum
                    FROM ppi_edi_parts as p
                    join estdoc_items as edi on edi.id=p.ediid
                    group by p.ppiid) as s'),
                function ($join) {
                    $join->on('i.id', '=', 's.ppiid');
                })
            ->where('i.docid', $docid)
            ->where('i.lvltypeid', '>', 1)
            ->whereNotNull('i.drctbegdt')
            ->whereNotNull('i.drctenddt')
            ->select('i1.name as par_name', 'i.parid', 'i.id', 'i.name', 'i.plnqty', 'i.unit'
                , db::raw("date(i.drctbegdt) as begdate")
                , db::raw("date(i.drctenddt) as enddate")
                //, 'i.plncost'
                , 's.getsum as plncost')
            ->orderBy('i1.ordr')
            ->orderBy('i1.id')
            ->orderBy('i.drctbegdt')
            ->orderBy('i.drctenddt')
            ->orderBy('i.id')
            ->get();

        $prodplan->mindate = $items->min('begdate');
        $prodplan->maxdate = $items->max('enddate');
        $prodplan->days = (strtotime($prodplan->maxdate) - strtotime($prodplan->mindate)) / 3600 / 24 + 1;
//        dd($prodplan->mindate,$prodplan->maxdate,$prodplan->days);
        $prodplan->monthes = Config::get('constants.monthes');

        return view('prodplans.prnt_table', compact('prodplan', 'items'));
    }

    public
    function rep61(Request $request)
    {
        //
        $report_id = 61;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данной информацией!']);

        $export2xls = $request->get('xls') ?? 0;

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $fdom = new DateTime('first day of this month');
        $fdomc = $fdom->format('Y-m-d');
        $year = $fdom->format('Y');
        $ldom = new DateTime('last day of this month');
        $ldomc = $ldom->format('Y-m-d');
        $curdate = new DateTime();
        $yesterday = new DateTime('yesterday');
        $pd = $yesterday->format('Y-m-d');
        $cd = $curdate->format('Y-m-d');

        $month = date("n");
        $yearQuarter = ceil($month / 3);

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_period_type' => 1
            , 's_begdate' => $pd //$fdomc
            , 's_enddate' => $pd //$ldomc
            , 's_month' => $month
            , 's_quarter' => $yearQuarter
            , 's_year' => $year
            , 's_orgid' => ''
            , 's_opertypeid' => ''
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        //зачистим ненужные параметры поиска
        switch ($search_params['s_period_type'] ?? 0) {
            case 1: //дата - 1 день
                $search_params['s_enddate'] = $search_params['s_begdate'];
                break;

            case 2: //месяц/год
                $year = $search_params['s_year'];
                $month = $search_params['s_month'];
                $begdate = new DateTime($year . '-' . $month . '-1 00:00:00');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $begdate->format('Y-m-t');
                break;

            case 3: //квартал/год

                $year = $search_params['s_year'];
                $quarter = $search_params['s_quarter'];
                $begdate = new DateTime($year . '-' . (3 * $quarter - 2) . '-1 00:00:00');
                $enddate = new DateTime($year . '-' . (3 * $quarter) . '-' . ($quarter == 1 || $quarter == 4 ? 31 : 30) . ' 23:59:59');
                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;

            case 4://год
                $year = $search_params['s_year'];
                $begdate = new DateTime($year . '-1-1 00:00:00');
                $enddate = new DateTime($year . '-12-31 23:59:59');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;

            case 9://календарь
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
                break;
            default:
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
        }

        $need_search = false;
        $sc1 = "1=1";
        $sc2 = "1=1";
        $sc3 = "1=1";
        $sc4 = "1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;


                if ($item == 's_ownorgid') {
                    $sc1 = $sc1 . " and mr.load_ownorgid = '{$val}'";

                } elseif ($item == 's_opertypeid') {
                    $sc1 = $sc1 . " and mr.opertypeid = {$val}";

                } elseif ($item == 's_begdate') {
                    $sc1 = $sc1 . " and mr.wrkdate >= '{$val}'";
                    $sc2 = $sc2 . " and dw.wrkdate >= '{$val}'";
                    $sc3 = $sc3 . " and fcp.paydate >= '{$val}'";
                    $sc4 = $sc4 . " and msu.operdate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc1 = $sc1 . " and mr.wrkdate <= '{$val}'";
                    $sc2 = $sc2 . " and dw.wrkdate <= '{$val}'";
                    $sc3 = $sc3 . " and fcp.paydate <= '{$val}'";
                    $sc4 = $sc4 . " and msu.operdate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(mr.docdate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(mr.docdate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(mr.docdate) = '{$val}'";
                }
            }
        }

        if ($need_search) {

            $sql = "select machineid, m.regnum, m.name as machine_name
	, sum(sale_sum) as sale_sum
    , sum(buy_sum) as buy_sum
    , sum(salary_sum) as salary_sum
    , sum(fuel_sum) as fuel_sum
    , sum(spare_sum) as spare_sum
    , sum(sale_sum) - sum(buy_sum) - sum(salary_sum) - sum(fuel_sum) - sum(spare_sum) as income_sum
    from (
SELECT mr.machineid
	, sum(if(mro.sale_dir=1,1,0)*mro.itm_sum) as sale_sum
    , sum(if(mro.sale_dir=-1,1,0)*mro.itm_sum) as buy_sum
    , sum(mro.driver_sum) as salary_sum
    , 0 as fuel_sum
    , 0 as spare_sum
	FROM mr_opers as mro
	join mchn_raids as mr on mr.id=mro.mr_id
    where " . $sc1
//                . " and mr.opertypeid=1"
                . " group by mr.machineid"
                . " union
SELECT dw.machineid
	, 0, 0, sum(dw.salary_sum) as  salary_sum, 0, 0
FROM driver_works dw
 where " . $sc2
                . " group by machineid
 union
SELECT fcp.machineid
	, 0, 0, 0, sum(fcp.paysum) as  fuel_sum, 0
FROM fuelcard_pays fcp
 where " . $sc3
                . " group by machineid
 union
    SELECT machineid, 0, 0, 0, 0, sum(msu.spare_sum) as  spare_sum
    FROM mchn_spare_usages msu
    where " . $sc4 . " group by machineid
    ) a
     join machines as m on m.id=a.machineid
	group by machineid
order by income_sum desc";

            $recs = DB::select(DB::raw($sql));
            //dd($sql, $recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ');
        } else {
            $recs = null;
        }

        $data = new \stdClass();
        $data->period_types = [1 => 'день', 2 => 'месяц', 3 => 'квартал', 4 => 'год', 9 => 'календарь'];

        $data->monthes = Config::get('constants.monthes');
        $data->quarters = [1 => 1, 2 => 2, 3 => 3, 4 => 4];

        $data->years = Cache::remember('orgplnpays_years', now()->addMinutes(55)
            , function () {
                return mchn_raid::selectRaw("year(wrkdate) as year")->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });

        $data->ownorgs = org::lstFor_cached([
            'in_mchn_raids_ownorgid' => 1,
        ]);
        $data->orgs = org::lstFor_cached([
            'in_mr_opers' => 1,
            'not_flagtypeid' => 12,
        ]);
        $data->opertypes = opertype::lstFor_cached([
            'in_mchn_raids' => 1,
        ]);

        $s_period_type = $search_params['s_period_type'] ?? '';
        $ownorgid = $search_params['s_ownorgid'] ?? '';
        $s_begdate = $search_params['s_begdate'] ?? '';
        $s_enddate = $search_params['s_enddate'] ?? '';

        //dd($search_params['s_year']);
        $data->period_title = '';

        if ($s_period_type == 1) {
            $data->period_title = 'за ' . date_format(date_create($s_begdate), 'd.m.Y');
        } elseif ($s_period_type == 2)
            $data->period_title = ($data->monthes[$search_params['s_month']] ?? '') . ' ' . ($search_params['s_year'] ?? '');
        elseif ($s_period_type == 3)
            $data->period_title = $search_params['s_quarter'] . ' квартал ' . ($search_params['s_year'] ?? '');
        elseif ($s_period_type == 4)
            $data->period_title = ($search_params['s_year'] ?? '') . ' год';
        else {
            if (isset($s_begdate) and $s_begdate <> '')
                $data->period_title .= ' с ' . date_format(date_create($s_begdate), 'd.m.Y');
            if (isset($s_enddate) and $s_enddate <> '')
                $data->period_title .= ' по ' . date_format(date_create($s_enddate), 'd.m.Y');
        }
        //dd($s_period_type,$s_begdate, $s_enddate, $data->period_title,  date_format(date_create($s_begdate), 'd.m.Y'));

        if ($export2xls == "1") {
            $response = Excel::download(new rep61Export($recs, $data), "rep_income_daily.xlsx", \Maatwebsite\Excel\Excel::XLSX);

            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }

        return view('mchn_raids.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public
    function rep70(Request $request)
    {
        //Данные по покупкам/продажам по перевозкам
        $report_id = 70;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данной информацией!']);

        $export2xls = $request->get('xls') ?? 0;

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $fdom = new DateTime('first day of this month');
        $fdomc = $fdom->format('Y-m-d');
        $year = $fdom->format('Y');
        $ldom = new DateTime('last day of this month');
        $ldomc = $ldom->format('Y-m-d');
        $curdate = new DateTime();
        $yesterday = new DateTime('yesterday');
        $pd = $yesterday->format('Y-m-d');
        $cd = $curdate->format('Y-m-d');

        $month = date("n");
        $yearQuarter = ceil($month / 3);

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_period_type' => 1
            , 's_begdate' => $pd //$fdomc
            , 's_enddate' => $pd //$ldomc
            , 's_month' => $month
            , 's_quarter' => $yearQuarter
            , 's_year' => $year
            , 's_ownorgid' => ''
            , 's_orgid' => ''
            , 's_orgname' => ''
            , 's_opertypeid' => ''
            , 's_driverid' => ''
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        //зачистим ненужные параметры поиска
        switch ($search_params['s_period_type'] ?? 0) {
            case 1: //дата - 1 день
                $search_params['s_enddate'] = $search_params['s_begdate'];
                break;

            case 2: //месяц/год
                $year = $search_params['s_year'];
                $month = $search_params['s_month'];
                $begdate = new DateTime($year . '-' . $month . '-1 00:00:00');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $begdate->format('Y-m-t');
                break;

            case 3: //квартал/год

                $year = $search_params['s_year'];
                $quarter = $search_params['s_quarter'];
                $begdate = new DateTime($year . '-' . (3 * $quarter - 2) . '-1 00:00:00');
                $enddate = new DateTime($year . '-' . (3 * $quarter) . '-' . ($quarter == 1 || $quarter == 4 ? 31 : 30) . ' 23:59:59');
                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;

            case 4://год
                $year = $search_params['s_year'];
                $begdate = new DateTime($year . '-1-1 00:00:00');
                $enddate = new DateTime($year . '-12-31 23:59:59');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;

            case 9://календарь
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
                break;
            default:
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
        }

        $need_search = false;
        $sc1 = "1=1";
        $sc2 = "1=1";
        $sc3 = "1=1";
        $sc4 = "1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;


                if ($item == 's_ownorgid') {
                    //$sc1 = $sc1 . " and mr.load_ownorgid = '{$val}'";
                    $sc1 = $sc1 . " and mro.suporgid = '{$val}'";

                } elseif ($item == 's_orgid') {
                    $sc1 = $sc1 . " and mro.orgid = '{$val}'";

                } elseif ($item == 's_opertypeid') {
                    $sc1 = $sc1 . " and mr.opertypeid = {$val}";

                } elseif ($item == 's_driverid') {
                    $sc1 = $sc1 . " and mr.driverid = {$val}";

                } elseif ($item == 's_begdate') {
                    $sc1 = $sc1 . " and mr.wrkdate >= '{$val}'";
                    $sc2 = $sc2 . " and dw.wrkdate >= '{$val}'";
                    $sc3 = $sc3 . " and fcp.paydate >= '{$val}'";
                    $sc4 = $sc4 . " and msu.operdate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc1 = $sc1 . " and mr.wrkdate <= '{$val}'";
                    $sc2 = $sc2 . " and dw.wrkdate <= '{$val}'";
                    $sc3 = $sc3 . " and fcp.paydate <= '{$val}'";
                    $sc4 = $sc4 . " and msu.operdate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(mr.docdate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(mr.docdate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(mr.docdate) = '{$val}'";
                }
            }
        }

        if ($need_search) {

            //------------------------------
            $sql = "select mro.mr_id, ot.name, mr.wrkdate
, dw.staffid, os.lname, os.fname, os.mname
, dw.machineid, m.regnum, m.name as machine_name
, GROUP_CONCAT(sup.name SEPARATOR ', ') as sup_name
, GROUP_CONCAT(mro.sup_placename SEPARATOR ', ') as sup_placename
, GROUP_CONCAT( if(mro.sale_dir=-1, ri.name, null) SEPARATOR ', ') as buy_itmname
, sum(if(mro.sale_dir=-1, mro.itm_sum, 0)) as buy_sum
--
, GROUP_CONCAT(org.name SEPARATOR ', ') as org_name
, GROUP_CONCAT( if(mro.sale_dir=1, ri.name, null) SEPARATOR ', ') as sale_itmname
, GROUP_CONCAT(mro.org_placename SEPARATOR ', ') as org_placename
, sum(if(mro.sale_dir=1, mro.itm_sum, 0)) as sale_sum
, sum(mro.raid_qty) as raid_qty
	from mr_opers mro
	join mchn_raids as mr on mr.id=mro.mr_id
    join driver_works dw on dw.id=mr.dw_id
    join opertypes ot on ot.id=ifNull(dw.opertypeid, 1)
    join orgstaff os on os.id=dw.staffid
    join machines m on m.id=dw.machineid
    join refitems as ri on ri.id=mro.refitmid
    join orgs as sup on sup.id=mro.suporgid
    join orgs as org on org.id=mro.orgid
    where {$sc1}
    group by mro.mr_id
    order by mr.wrkdate, os.lname, dw.staffid";

            $recs = DB::select(DB::raw($sql));
            //dd($sql, $recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ');
        } else {
            $recs = null;
        }

        $data = new \stdClass();
        $data->period_types = [1 => 'день', 2 => 'месяц', 3 => 'квартал', 4 => 'год', 9 => 'календарь'];

        $data->monthes = Config::get('constants.monthes');
        $data->quarters = [1 => 1, 2 => 2, 3 => 3, 4 => 4];

        $data->years = Cache::remember('orgplnpays_years', now()->addMinutes(55)
            , function () {
                return mchn_raid::selectRaw("year(wrkdate) as year")->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });

        $s_period_type = $search_params['s_period_type'] ?? '';
        $ownorgid = $search_params['s_ownorgid'] ?? '';
        $orgid = $search_params['s_orgid'] ?? '';
        $s_begdate = $search_params['s_begdate'] ?? '';
        $s_enddate = $search_params['s_enddate'] ?? '';

        $data->ownorgs = org::lstFor_cached([
            'in_mchn_raids_ownorgid' => 1,
        ]);
        /*$data->orgs = org::lstFor_cached([
            'in_mr_opers' => 1,
            'in_mr_opers_with_suporgid' => $search_params['s_ownorgid'] ?? '',
            //'in_mr_opers_with_wrkdate_ge' => $search_params['s_begdate'] ?? '',
            //'in_mr_opers_with_wrkdate_le' => $search_params['s_enddate'] ?? '',
            //'not_flagtypeid' => 12,
        ]);*/
        //dd($data);
        $data->opertypes = opertype::lstFor_cached([
            'in_mchn_raids' => 1,
        ]);
        $data->drivers = orgstaff::lstFor_cached([
            'driver_in_mchn_raids' => 1,
        ]);

        //dd($search_params['s_year']);
        $data->period_title = '';

        if ($s_period_type == 1) {
            $data->period_title = 'за ' . date_format(date_create($s_begdate), 'd.m.Y');
        } elseif ($s_period_type == 2)
            $data->period_title = ($data->monthes[$search_params['s_month']] ?? '') . ' ' . ($search_params['s_year'] ?? '');
        elseif ($s_period_type == 3)
            $data->period_title = $search_params['s_quarter'] . ' квартал ' . ($search_params['s_year'] ?? '');
        elseif ($s_period_type == 4)
            $data->period_title = ($search_params['s_year'] ?? '') . ' год';
        else {
            if (isset($s_begdate) and $s_begdate <> '')
                $data->period_title .= ' с ' . date_format(date_create($s_begdate), 'd.m.Y');
            if (isset($s_enddate) and $s_enddate <> '')
                $data->period_title .= ' по ' . date_format(date_create($s_enddate), 'd.m.Y');
        }
        //dd($s_period_type,$s_begdate, $s_enddate, $data->period_title,  date_format(date_create($s_begdate), 'd.m.Y'));


        if ($export2xls == "1") {
            $xls_fileName = "mchn_operations_" .$s_begdate;
            if ($s_enddate <> $s_begdate)
                $xls_fileName .= '_' . $s_enddate;
            $xls_fileName .= ".xlsx";

            $response = Excel::download(new rep70Export($recs, $data), $xls_fileName, \Maatwebsite\Excel\Excel::XLSX);

            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }

        return view('mchn_raids.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public
    function rep71(Request $request)
    {
        //Баллы по перевозкам
        $report_id = 71;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данной информацией!']);

        $export2xls = $request->get('xls') ?? 0;

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $fdom = new DateTime('first day of this month');
        $fdomc = $fdom->format('Y-m-d');
        $year = $fdom->format('Y');
        $ldom = new DateTime('last day of this month');
        $ldomc = $ldom->format('Y-m-d');
        $curdate = new DateTime();
        $yesterday = new DateTime('yesterday');
        $pd = $yesterday->format('Y-m-d');
        $cd = $curdate->format('Y-m-d');

        $month = date("n");
        $yearQuarter = ceil($month / 3);

        $param_names = [
            's_pageitmcnt' => 20
            , 's_drivername' => ''
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_period_type' => 1
            , 's_begdate' => $pd //$fdomc
            , 's_enddate' => $pd //$ldomc
            , 's_month' => $month
            , 's_quarter' => $yearQuarter
            , 's_year' => $year
            , 's_orgid' => ''
            , 's_opertypeid' => ''
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        //зачистим ненужные параметры поиска
        switch ($search_params['s_period_type'] ?? 0) {
            case 1: //дата - 1 день
                $search_params['s_enddate'] = $search_params['s_begdate'];
                break;

            case 2: //месяц/год
                $year = $search_params['s_year'];
                $month = $search_params['s_month'];
                $begdate = new DateTime($year . '-' . $month . '-1 00:00:00');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $begdate->format('Y-m-t');
                break;

            case 3: //квартал/год

                $year = $search_params['s_year'];
                $quarter = $search_params['s_quarter'];
                $begdate = new DateTime($year . '-' . (3 * $quarter - 2) . '-1 00:00:00');
                $enddate = new DateTime($year . '-' . (3 * $quarter) . '-' . ($quarter == 1 || $quarter == 4 ? 31 : 30) . ' 23:59:59');
                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;

            case 4://год
                $year = $search_params['s_year'];
                $begdate = new DateTime($year . '-1-1 00:00:00');
                $enddate = new DateTime($year . '-12-31 23:59:59');

                $search_params['s_begdate'] = $begdate->format('Y-m-d');
                $search_params['s_enddate'] = $enddate->format('Y-m-d');
                break;

            case 9://календарь
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
                break;
            default:
                $search_params['s_quarter'] = '';
                $search_params['s_month'] = '';
                $search_params['s_year'] = '';
        }

        $need_search = false;
        $sc1 = "route_points is not null";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;

                if ($item == 's_drivername') {
                    $sc1 = $sc1 . " and mr.drivername like '%{$val}%'";

                } elseif ($item == 's_opertypeid') {
                    $sc1 = $sc1 . " and mr.opertypeid = {$val}";

                } elseif ($item == 's_begdate') {
                    $sc1 = $sc1 . " and mr.wrkdate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc1 = $sc1 . " and mr.wrkdate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(mr.docdate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(mr.docdate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(mr.docdate) = '{$val}'";
                }
            }
        }

        if ($need_search) {

            //------------------------------
            $sql = "SELECT mr.wrkdate, mr.driverid, max(mr.drivername) as drivername, sum(mro.raid_qty*route_points) as route_points
                FROM `mr_opers` mro
                join mchn_raids as mr on mr.id=mro.mr_id
                where {$sc1}
                group by mr.wrkdate, mr.driverid
                order by route_points desc, drivername, mr.wrkdate";

            $recs = DB::select(DB::raw($sql));
            //dd($sql, $recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ');

        } else {
            $recs = null;
        }

        $data = new \stdClass();
        $data->period_types = [1 => 'день', 2 => 'месяц', 3 => 'квартал', 4 => 'год', 9 => 'календарь'];

        $data->monthes = Config::get('constants.monthes');
        $data->quarters = [1 => 1, 2 => 2, 3 => 3, 4 => 4];

        $data->years = Cache::remember('orgplnpays_years', now()->addMinutes(55)
            , function () {
                return mchn_raid::selectRaw("year(wrkdate) as year")->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });

//        $data->ownorgs = org::lstFor_cached([
//            'in_mchn_raids_ownorgid' => 1,
//        ]);
//        $data->orgs = org::lstFor_cached([
//            'in_mr_opers' => 1,
//            'not_flagtypeid' => 12,
//        ]);
        $data->opertypes = opertype::lstFor_cached([
            'in_mchn_raids' => 1,
        ]);

        $s_period_type = $search_params['s_period_type'] ?? '';
        $ownorgid = $search_params['s_ownorgid'] ?? '';
        $s_begdate = $search_params['s_begdate'] ?? '';
        $s_enddate = $search_params['s_enddate'] ?? '';

        //dd($search_params['s_year']);
        $data->period_title = '';

        if ($s_period_type == 1) {
            $data->period_title = 'за ' . date_format(date_create($s_begdate), 'd.m.Y');
        } elseif ($s_period_type == 2)
            $data->period_title = ($data->monthes[$search_params['s_month']] ?? '') . ' ' . ($search_params['s_year'] ?? '');
        elseif ($s_period_type == 3)
            $data->period_title = $search_params['s_quarter'] . ' квартал ' . ($search_params['s_year'] ?? '');
        elseif ($s_period_type == 4)
            $data->period_title = ($search_params['s_year'] ?? '') . ' год';
        else {
            if (isset($s_begdate) and $s_begdate <> '')
                $data->period_title .= ' с ' . date_format(date_create($s_begdate), 'd.m.Y');
            if (isset($s_enddate) and $s_enddate <> '')
                $data->period_title .= ' по ' . date_format(date_create($s_enddate), 'd.m.Y');
        }
        //dd($s_period_type,$s_begdate, $s_enddate, $data->period_title,  date_format(date_create($s_begdate), 'd.m.Y'));


        if (1==1 and $export2xls == "1") {
            $xls_fileName = "route_points_" .$s_begdate;
            if ($s_enddate <> $s_begdate)
                $xls_fileName .= '_' . $s_enddate;
            $xls_fileName .= ".xlsx";

            $response = Excel::download(new rep71Export($recs, $data), $xls_fileName, \Maatwebsite\Excel\Excel::XLSX);

            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }

        return view('mchn_raids.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

}
