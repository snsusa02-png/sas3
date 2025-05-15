<?php

namespace App\Http\Controllers;

use App\Exports\rep61Export;
use App\fuelcard;
use App\fuelcard_pay;
use App\mchn_raid;
use App\mchntype;
use App\opertype;
use App\orgstaff;
use App\report;
use App\org;
use App\objlog;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\User;
use App\usrsysright;
use Illuminate\Http\Request;
use DB;
use DateTime;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use App\Events\notifyEvent;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use function GuzzleHttp\Promise\task;

class FuelcardPayReportController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->sysobjid = 562;
        $this->sysobjcode = 'fuelcard_pays';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
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
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
            $usrrights['approve'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.approve');
            $usrrights['setfact'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.setfact');
        }

        return $usrrights;
    }


    public function rep64(Request $request)
    {
        //

        $report_id = 64;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с платежами для этой организации!']);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $export2xls = $request->get('xls') ?? 0;

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
            , 's_suporgid' => ''
            , 's_mchntypeid' => ''
            , 's_fuelcardid' => ''
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

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;

                if ($item == 's_ownorgid') {
                    $sc = $sc . " and m.orgid = '{$val}'";

                } elseif ($item == 's_suporgid') {
                    $sc = $sc . " and fc.suporgid = {$val}";

                } elseif ($item == 's_orgid') {
                    $sc = $sc . " and fc.orgid = {$val}";

                } elseif ($item == 's_mchntypeid') {
                    $sc = $sc . " and m.mchntypeid = {$val}";

                } elseif ($item == 's_fuelcardid') {
                    $sc = $sc . " and fp.cardid = {$val}";

                } elseif ($item == 's_begdate') {
                    $sc = $sc . " and fp.paydate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc = $sc . " and fp.paydate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(fp.paydate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(fp.paydate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(fp.paydate) = '{$val}'";
                }
            }
        }

        $recs = null;
        if ($need_search) {

            $recs = fuelcard_pay::from('fuelcard_pays as fp')
                ->join('machines as m', 'm.id', 'fp.machineid')
                ->join('mchntypes as mt', 'mt.id', 'm.mchntypeid')
                ->join('fuelcards as fc', 'fc.id', 'fp.cardid')
                ->leftjoin('orgs as so', 'so.id', 'fc.suporgid')
                ->select('fp.machineid', 'fp.cardid', 'fc.num as card_num'
                    , db::raw("concat(m.name, ', ', m.regnum) as machine_name")
                    , 'm.mchntypeid', 'mt.name as mchntype_name'
                    , db::raw("sum(fp.paysum) as paysum")
                    , db::raw("sum(fp.fuel_qty) as fuelqty")
                    , db::raw("count(DISTINCT paydate) as payqty")
                    , db::raw("min(paydate) as min_paydate")
                    , db::raw("max(paydate) as max_paydate")
                )
                ->whereRaw($sc)
                ->groupBy(['fp.machineid', 'fp.cardid'])
                ->orderby('mchntype_name', 'asc')
                ->orderby('machine_name', 'asc')
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

        $data->mchntypes = mchntype::lstFor_cached([
            'in_fuelcard_pays' => 1,
        ]);
        $data->fuelcards = fuelcard::lstFor_cached([
            'in_fuelcard_pays' => 1,
        ]);
        $data->fuelcards = collect($data->fuelcards)->sortBy('name')->reverse()->toArray();
        //dd($data->fuelcards);

        $data->suporgs = org::lstFor(['in_fuelcards_suporgid' => 1]);

        $data->orgs = org::lstFor(['in_fuelcards' => 1]);


        // расчет средней цены --------------------
        $paySum = $fuelQty = $data->avgPrice = 0;
        if (isset($recs)) {
            foreach ($recs as $rec) {
                $paySum += $rec->paysum;
                $fuelQty += $rec->fuelqty;
            }
            $data->avgPrice = ($fuelQty == 0) ? 0 : round($paySum / $fuelQty, 2);
        }


        $s_period_type = $search_params['s_period_type'] ?? '';
        $ownorgid = $search_params['s_ownorgid'] ?? '';
        $s_begdate = $search_params['s_begdate'] ?? '';
        $s_enddate = $search_params['s_enddate'] ?? '';

        //dd($search_params['s_year']);
        $data->sub_title = '';

        $period_title = '';

        if ($s_period_type == 1) {
            $period_title = 'за ' . date_format(date_create($s_begdate), 'd.m.Y');
        } elseif ($s_period_type == 2)
            $period_title = ($data->monthes[$search_params['s_month']] ?? '') . ' ' . ($search_params['s_year'] ?? '');
        elseif ($s_period_type == 3)
            $period_title = $search_params['s_quarter'] . ' квартал ' . ($search_params['s_year'] ?? '');
        elseif ($s_period_type == 4)
            $period_title = ($search_params['s_year'] ?? '') . ' год';
        else {
            if (isset($s_begdate) and $s_begdate <> '')
                $period_title .= ' с ' . date_format(date_create($s_begdate), 'd.m.Y');
            if (isset($s_enddate) and $s_enddate <> '')
                $period_title .= ' по ' . date_format(date_create($s_enddate), 'd.m.Y');
        }
        $data->sub_title .= 'Период: <b>' . $period_title .'</b>';

        if ( !is_null($search_params['s_suporgid']??null)){
            $data->sub_title .= '<br>Поставщик: <b>'. $data->suporgs[$search_params['s_suporgid']??''] .'</b>';
        }
        if (!is_null($search_params['s_orgid']??null)){
            $data->sub_title .= '<br>Владелец: <b>'. $data->orgs[$search_params['s_orgid']] .'</b>';
        }
        if (!is_null($search_params['s_fuelcardid']??null)){
            $data->sub_title .= '<br>Карта: <b>'. $data->fuelcards[$search_params['s_fuelcardid']] .'</b>';
        }
        if (!is_null($search_params['s_mchntypeid']??null)){
            $data->sub_title .= '<br>Техника: <b>'. $data->mchntypes[$search_params['s_mchntypeid']] .'</b>';
        }

        //dd($s_period_type,$s_begdate, $s_enddate, $data->period_title,  date_format(date_create($s_begdate), 'd.m.Y'));

        if ($export2xls == "1") {
            $response = Excel::download(new rep61Export($recs, $data), "rep_income_daily.xlsx", \Maatwebsite\Excel\Excel::XLSX);

            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }
//dd($data);
        return view('fuelcard_pays.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public function rep68(Request $request)
    {
        //
        $report_id = 68;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с платежами для этой организации!']);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $export2xls = $request->get('xls') ?? 0;

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
            , 's_mchntypeid' => ''
            , 's_fuelcardid' => ''
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

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;

                if ($item == 's_ownorgid') {
                    $sc = $sc . " and m.orgid = '{$val}'";

                } elseif ($item == 's_mchntypeid') {
                    $sc = $sc . " and m.mchntypeid = {$val}";

                } elseif ($item == 's_fuelcardid') {
                    $sc = $sc . " and fp.cardid = {$val}";

                } elseif ($item == 's_begdate') {
                    $sc = $sc . " and fp.paydate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc = $sc . " and fp.paydate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(fp.paydate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(fp.paydate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(fp.paydate) = '{$val}'";
                }
            }
        }

        $recs = null;
        if ($need_search) {

            $recs = fuelcard_pay::from('fuelcard_pays as fp')
                ->join('fuelcards as fc', 'fc.id', 'fp.cardid')
                //->join('machines as m', 'm.id', 'fp.machineid')
                //->join('mchntypes as mt', 'mt.id', 'm.mchntypeid')
                ->select(
                    'fp.cardid'
                    , 'fc.num as card_num'
                    //, db::raw("concat(m.name, ', ', m.regnum) as machine_name")
                    //, 'm.mchntypeid', 'mt.name as mchntype_name'
                    , db::raw("sum(fp.paysum) as paysum")
                    , db::raw("sum(fp.fuel_qty) as fuelqty")
                    , db::raw("count(DISTINCT paydate) as payqty")
                    , db::raw("min(paydate) as min_paydate")
                    , db::raw("max(paydate) as max_paydate")
                )
                ->whereRaw($sc)
                ->groupBy(['fp.cardid'])
                ->orderby('card_num', 'asc')
                ->get();
//            dd($sc, $recs);

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

//        $data->ownorgs = org::lstFor_cached([
//            'in_mchn_raids_ownorgid' => 1,
//        ]);
//        $data->orgs = org::lstFor_cached([
//            'in_mr_opers' => 1,
//            'not_flagtypeid' => 12,
//        ]);

        $data->mchntypes = mchntype::lstFor_cached([
            'in_fuelcard_pays' => 1,
        ]);
        $data->fuelcards = fuelcard::lstFor_cached([
            'in_fuelcard_pays' => 1,
        ]);
        $data->fuelcards = collect($data->fuelcards)->sortBy('name')->reverse()->toArray();
        //dd($data->fuelcards);

        // расчет средней цены --------------------
        $paySum = $fuelQty = $data->avgPrice = 0;
        if (isset($recs)) {
            foreach ($recs as $rec) {
                $paySum += $rec->paysum;
                $fuelQty += $rec->fuelqty;
            }
            $data->avgPrice = ($fuelQty == 0) ? 0 : round($paySum / $fuelQty, 2);
        }


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
        //dd($data, 'fuelcard_pays.rep' . $report_id);
        return view('fuelcard_pays.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

}
