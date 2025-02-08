<?php

namespace App\Http\Controllers;

//use App\buildobj;
//use App\equiprqst_item;
//use App\eritm_offer;
//use App\eritm_supply;
//use App\Exports\InvoicesExport;
//use App\Exports\PayPlanExport;
//use App\Exports\rep53Export;
//use App\mchn_raid;
//use App\mr_oper;
//use App\obj_finoper;
//use App\org_saldo;
//use App\orgplnpay;
//use App\orgplnpay_item;
//use App\orgstaff;
//use App\pay_category;
//use App\paydoc;
//use App\task;
//use App\prodplan_fact;
use App\refitem;
use App\report;
use App\org;

//use App\group;
//use App\machine;
//use App\mchnrqsttype;
//use App\mchnrqst;
//use App\mchntype;
//use App\contract;
//use App\objflag;
use App\objlog;
use App\Traits\SearchDataTrait;

//use App\User;
//use App\user_template;
use App\usrsysright;
use App\wrhdoc;
use App\wrhdoclst;
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
use Illuminate\Support\Str;
use function GuzzleHttp\Promise\task;

class WrhDocReportController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;  //reports
        $this->objcode = 'wrhdocs';
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


    function rep55(Request $request, $date)
    {
        //Детализация производства и отгрузки продукции за дату

        $report_id = 55;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $data = new \stdClass();
        $data->date = $date;
        $data->returl = $returl;

//        $sql = "select a.refitmid, ri.name as refitm_name, ri.unit as refitm_unit
//	            , sum(a.pre_qty) as pre_qty, sum(a.inp_qty) as inp_qty
//	            , sum(a.out_qty) as out_qty, sum(a.sale_sum) as sale_sum
//                from (
//                    SELECT i.refitmid
//                        , SUM(IF(t.forStock=1, i.qty, 0) - IF(t.forStock=-1, i.qty, 0)) as pre_qty
//                        , null as inp_qty, null as out_qty, null as sale_sum
//                    FROM wrhdoclst as i
//                    INNER JOIN wrhdocs as d ON d.id = i.docid
//                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
//                    WHERE d.docsigned=1 and  d.docdate < '{$date}'
//                    GROUP BY refitmid
//                    union all
//                    SELECT     i.refitmid, null as pre_qty
//                        , SUM(IF(t.forStock= 1, i.qty, null )) as inp_qty
//                        , SUM(IF(t.forStock=-1, i.qty, null)) as out_qty
//                        , SUM(IF(t.forSale= 1, i.qty*i.price, null)) as sale_sum
//                    FROM wrhdoclst as i
//                    INNER JOIN wrhdocs as d ON d.id = i.docid
//                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
//                    WHERE d.docsigned=1 and d.docdate = '{$date}'
//                    GROUP BY refitmid
//                    ) as a
//                INNER JOIN  refitems as ri ON ri.id = a.refitmid
//                GROUP BY refitmid
//                order by refitm_name";

        $sql = "select a.* from("
            . "select a.ownorgid, oo.name as ownorg_name
                , a.refitmid, ri.name as refitm_name, ri.unit as refitm_unit
	            , sum(a.pre_qty) as pre_qty
	            , sum(a.pre_sum) as pre_sum
	            , sum(a.inp_qty) as inp_qty
	            , sum(a.inp_sum) as inp_sum
	            , sum(a.out_qty) as out_qty
	            , sum(a.out_sum) as out_sum
	            , sum(ifnull(a.pre_qty,0) + ifnull(a.inp_qty,0) - ifnull( a.out_qty,0)) as end_qty
                , sum((ifnull(a.pre_qty,0) + ifnull(a.inp_qty,0) - ifnull( a.out_qty,0))*ifnull(rp.price,0)) as end_sum
                from (
                    SELECT i.refitmid
                        , d.ownorgid
                        , SUM(t.forStock*i.qty) as pre_qty
                    	, SUM(t.forStock*i.qty*i.price) as pre_sum
                        , null as inp_qty
                    	, null as inp_sum
                        , null as out_qty
                        , null as out_sum
                    FROM wrhdoclst as i
                    INNER JOIN wrhdocs as d ON d.id = i.docid
                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                    WHERE d.docsigned=1 and  d.docdate < '{$date}'
                    GROUP BY refitmid, d.ownorgid
                    union all
                    SELECT i.refitmid
                        , d.ownorgid
                        , null as pre_qty
                    	, null as pre_sum
                        , SUM(IF(t.forStock=1, i.qty, 0)) as inp_qty
                    	, SUM(IF(t.forStock=1, i.qty, 0)*i.price) as inp_sum
                        , SUM(IF(t.forStock=-1, i.qty, 0)) as out_qty
                        , SUM(IF(t.forStock=-1, i.qty, 0)*i.price) as out_sum
                    FROM wrhdoclst as i
                    INNER JOIN wrhdocs as d ON d.id = i.docid
                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                    WHERE d.docsigned=1 and  d.docdate = '{$date}'
                    GROUP BY refitmid, d.ownorgid
                    ) as a
                INNER JOIN  refitems as ri ON ri.id = a.refitmid
                INNER JOIN  orgs as oo ON oo.id = a.ownorgid
                left join (SELECT refitmid, orgid, max(price) as price
	                        FROM `ri_sup_prices` sp
	                        WHERE '{$date}' between sp.begdate and if(sp.enddate is null,  '{$date}', sp.enddate)
                            group by refitmid, orgid) rp
		            on rp.orgid=a.ownorgid and rp.refitmid=a.refitmid
                GROUP BY a.refitmid, a.ownorgid ) a"
            // не берем записи со всеми нулями в количествах
            //. " where a.pre_qty>0 or ifnull(a.inp_qty,0)>0 or ifnull(a.out_Qty,0)>0"
            // берем записи только с приходом или расходом
            . " where ifnull(a.inp_qty,0)>0 or ifnull(a.out_Qty,0)>0"
            . " order by ownorg_name, ownorgid, refitm_name ";

        $recs = DB::select(DB::raw($sql));

        //dd($date,$sql,$recs);

        $recs2 = wrhdoc::from('wrhdoclst as dl')
            //->join('wrhdocs as d', 'd.id', 'dl.docid')
            ->join('wrhdocs as d', function ($join) {
                $join->on('d.id', '=', 'dl.docid')
                    ->where('d.docsigned', 1);
            })
            ->join('orgs as o', 'o.id', 'd.orgid')
            ->join('wrhdoctypes as t', function ($join) {
                $join->on('t.id', '=', 'd.doctypeid')
                    ->where('t.forsale', '<>', 0);
            })
            ->join('refitems as ri', 'ri.id', 'dl.refitmid')
            ->where('d.docdate', $date)
            ->select('d.orgid', 'o.name as org_name'
                , 'dl.refitmid', 'ri.name as refitm_name', 'ri.unit as refitm_unit'
                , db::raw("sum(t.forsale * dl.qty) as qty"), 'dl.price', db::raw("sum(t.forsale * dl.qty * dl.price) as itm_sum"))
            ->groupBy('d.orgid', 'dl.refitmid', 'dl.price')
            ->orderBy('org_name', 'asc')
            ->orderBy('d.orgid', 'asc')
            ->orderBy('refitm_name', 'asc')
            ->get();

        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен отчет; ' . $date);
        report::updUseCnt($report_id);

        //        if ($export2xls == "1") {
//            $response = Excel::download(new rep54Export($recs, $data), "Платежи за " . Str::slug($data->$date) . ".xlsx", \Maatwebsite\Excel\Excel::XLSX);
//
//            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
//            //HERE IS THE MAGIC FOLKS
//            ob_end_clean();
//            return $response;
//        }

        return view('wrhdocs.rep' . $report_id, compact('recs', 'recs2', 'data'));
    }

    function rep57(Request $request)
    {
        //Детализация производства и отгрузки продукции за период

        $report_id = 57;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $param_names = [
            's_begdate' => null,
            's_enddate' => strftime('%Y-%m-%d', strtotime(now())),
            's_itmtypeid' => null,
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $s_begdate = $search_params['s_begdate'];
        $s_enddate = $search_params['s_enddate'];
        $s_itmtypeid = $search_params['s_itmtypeid'];

//        dd($s_begdate, isset($s_begdate), is_null($s_begdate));
        $data = new \stdClass();
        $data->returl = $returl;

        //dd($search_params, $data);

        if ($s_begdate <> '') {
            $cnd1 = "";
            if (isset($s_itmtypeid)) $cnd1 = " and ri.itmtypeid = {$s_itmtypeid}";

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
                    join refitems ri on ri.id=i.refitmid
                    WHERE d.docsigned=1 and  d.docdate < '{$s_begdate}'
                    {$cnd1}
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
                     join refitems ri on ri.id=i.refitmid
                    WHERE d.docsigned=1
                    and d.docdate between '{$s_begdate}' and '{$s_enddate}'
                    {$cnd1}
                    GROUP BY refitmid
                    ) as a
                INNER JOIN  refitems as ri ON ri.id = a.refitmid
                GROUP BY refitmid
                order by refitm_name";

            $recs = DB::select(DB::raw($sql));

            //dd($date,$sql,$recs);

            if (1 == 0) {
                $recs2 = wrhdoc::from('wrhdoclst as dl')
                    //->join('wrhdocs as d', 'd.id', 'dl.docid')
                    ->join('wrhdocs as d', function ($join) {
                        $join->on('d.id', '=', 'dl.docid')
                            ->where('d.docsigned', 1);
                    })
                    ->join('orgs as o', 'o.id', 'd.orgid')
                    ->join('wrhdoctypes as t', function ($join) {
                        $join->on('t.id', '=', 'd.doctypeid')
                            ->where('t.forsale', '<>', 0);
                    })
                    ->join('refitems as ri', 'ri.id', 'dl.refitmid')
                    ->where('d.docdate', $s_begdate)
                    ->select('d.orgid', 'o.name as org_name'
                        , 'dl.refitmid', 'ri.name as refitm_name', 'ri.unit as refitm_unit'
                        , db::raw("sum(t.forsale * dl.qty) as qty"), 'dl.price', db::raw("sum(t.forsale * dl.qty * dl.price) as itm_sum"))
                    ->groupBy('d.orgid', 'dl.refitmid', 'dl.price')
                    ->orderBy('org_name', 'asc')
                    ->orderBy('d.orgid', 'asc')
                    ->orderBy('refitm_name', 'asc')
                    ->get();
            } else
                $recs2 = null;
            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $s_begdate);
            report::updUseCnt($report_id);

            //        if ($export2xls == "1") {
//            $response = Excel::download(new rep54Export($recs, $data), "Платежи за " . Str::slug($data->$date) . ".xlsx", \Maatwebsite\Excel\Excel::XLSX);
//
//            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
//            //HERE IS THE MAGIC FOLKS
//            ob_end_clean();
//            return $response;
//        }
        } else {
            $recs = null;
            $recs2 = null;
        }

        $data->itmtypes = wrhdoclst::from("wrhdoclst as di")
            ->join("wrhdocs as d", "d.id", "di.docid")
            ->join("refitems as ri", "ri.id", "di.refitmid")
            ->join("itmtypes as it", "it.id", "ri.itmtypeid")
            ->select('it.id', 'it.name')
            ->distinct()
            ->orderby('name', 'asc')
            ->get()
            ->pluck('name', 'id');
        // dd($data->itmtypes);


        return view('wrhdocs.rep' . $report_id, compact('search_params', 'recs', 'recs2', 'data'));
    }

    function rep57_i(Request $request)
    {
        //Детализация производства и отгрузки продукции за период

        $report_id = 57;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $param_names = [
            's_begdate' => null,
            's_enddate' => strftime('%Y-%m-%d', strtotime(now())),
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $s_begdate = $search_params['s_begdate'];
        $s_enddate = $search_params['s_enddate'];
        $s_refitmid = $request->ri_id;

        $data = new \stdClass();
        $data->returl = $returl;

        //dd($search_params, $data);

        if (isset($s_refitmid) and $s_begdate <> '') {
            $ri = refitem::where('id', $s_refitmid)->select('name', 'unit')->first();
            $data->refitm_name = $ri->name;
            $data->refitm_unit = $ri->unit;

            $sql = "SELECT d.id as docid
                , d.docdate
                , d.docnum
                , t.name as doctype_name
                , i.price
                , i.qty as qty
                , qty*i.price as sum
                , d.orgid
                , o.name as org_name
                FROM wrhdoclst as i
                INNER JOIN wrhdocs as d ON d.id = i.docid
                INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                left join orgs o on o.id=d.orgid
                WHERE d.docsigned=1
                and d.docdate between '{$s_begdate}' and '{$s_enddate}'
                and i.refitmid={$s_refitmid}
                and t.forStock= 1
                order by d.docdate, i.price";

            $recs = DB::select(DB::raw($sql));

//            dd($sql,$recs);

            //занесем в журнал
//            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $s_begdate);
//            report::updUseCnt($report_id);

        } else {
            $recs = null;
        }
        return view('wrhdocs.rep' . $report_id . '_i', compact('search_params', 'recs', 'data'));
    }

    function rep60(Request $request)
    {
        //Детализация отгрузки продукции по контрагенту

        $report_id = 60;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $param_names = [
            's_ownorgid' => null,
            's_orgid' => null,
            's_begdate' => null,
            's_enddate' => null,
            's_refitmid' => null,
            's_grp_docdate' => 1,
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $data = new \stdClass();
        $data->returl = $returl;
        $data->repInfo = '';

        $data->ownorgs = org::lstFor_cached(['in_wrhdocs_ownorg' => 1]);  //Владельцы из документов склада
        $data->orgs = org::lstFor_cached(['in_wrhdocs_org' => 1]);  //Контрагенты из документов склада
        $data->refitems = refitem::lstFor_cached(['in_wrhdocs' => 1]);  //Товары из документов склада

        if ($search_params['s_orgid'] <> '') {

            $s_orgid = $search_params['s_orgid'];
            $s_ownorgid = $search_params['s_ownorgid'];
            $s_begdate = $search_params['s_begdate'];
            $s_enddate = $search_params['s_enddate'];
            $s_refitmid = $search_params['s_refitmid'];

            $sc = "d.orgid = {$s_orgid}";
            $data->org_name = org::find($s_orgid)->name ?? '';
            $data->repInfo = "Контрагент: <b>{$data->org_name}</b>";

            if (isset($s_ownorgid)) {
                $sc .= " and d.ownorgid = {$s_ownorgid}";
                $data->ownorg_name = org::find($s_ownorgid)->name ?? '';
                $data->repInfo .= "<br>со склада: <b>{$data->ownorg_name}</b><br>";
            }

            $dates = '';
            if (isset($s_begdate) or isset($s_enddate)) {
                if (isset($s_begdate)) {
                    $sc .= " and d.docdate >= '{$s_begdate}'";
                    $dates .= "<b>" . date_create($s_begdate)->format('d.m.Y') . "</b>";
                } else
                    $dates .= "...";

                $dates .= ' - ';

                if (isset($s_enddate)) {
                    $sc .= " and d.docdate <= '{$s_enddate}'";
                    $dates .= "<b>" . date_create($s_enddate)->format('d.m.Y') . "</b>";
                } else
                    $dates .= "...";

                $data->repInfo .= "<br>за период: {$dates}";
            }

            if (isset($s_refitmid)) {
                $sc .= " and dl.refitmid = {$s_refitmid}";
                $data->ri_name = refitem::find($s_refitmid)->name ?? '';
                $data->repInfo .= "<br>товар: <b>{$data->ri_name}</b><br>";
            }

            $recs = wrhdoc::from('wrhdoclst as dl')
                //->join('wrhdocs as d', 'd.id', 'dl.docid')
                ->join('wrhdocs as d', function ($join) {
                    $join->on('d.id', '=', 'dl.docid')
                        ->where('d.docsigned', 1);
                })
                ->join('orgs as oo', 'oo.id', 'd.ownorgid')
                ->join('orgs as o', 'o.id', 'd.orgid')
                ->join('wrhdoctypes as t', function ($join) {
                    $join->on('t.id', '=', 'd.doctypeid')
                        ->where('t.forsale', '<>', 0);
                })
                ->join('refitems as ri', 'ri.id', 'dl.refitmid')
                ->whereRaw($sc)
                ->select('d.ownorgid', 'oo.name as ownorg_name'
                    , 'd.orgid', 'o.name as org_name'
                    , 'd.docdate'
                    , 'dl.refitmid', 'ri.name as refitm_name', 'ri.unit as refitm_unit'
                    , 'dl.price'
                    , db::raw("sum(t.forsale * dl.qty) as qty")
                    , db::raw("sum(t.forsale * dl.qty * dl.price) as sale_sum"))
                ->groupBy('d.ownorgid', 'd.orgid', 'd.docdate', 'dl.refitmid', 'dl.price')
                ->orderBy('ownorg_name', 'asc')
                ->orderBy('d.ownorgid', 'asc')
                ->orderBy('d.docdate', 'asc')
                ->orderBy('refitm_name', 'asc')
                ->get();

            //dd($date,$recs);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $s_orgid);
            //обновим счетчик использования отчета
            report::updUseCnt($report_id);

//        if ($export2xls == "1") {
//            $response = Excel::download(new rep54Export($recs, $data), "Платежи за " . Str::slug($data->$date) . ".xlsx", \Maatwebsite\Excel\Excel::XLSX);
//
//            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
//            //HERE IS THE MAGIC FOLKS
//            ob_end_clean();
//            return $response;
//        }

        } else {
            $recs = null;
        }
        return view('wrhdocs.rep' . $report_id, compact('search_params', 'recs', 'data'));
    }

    public function rep65(Request $request)
    {
        //
        $report_id = 65;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данной информацией!']);

        $returl = $request->get('returl') ?? url()->full() ?? route('home');

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

        $begdate = today();

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_month' => $month
            , 's_year' => $year
            , 's_begdate' => $begdate->format('Y-m-01')
            , 's_enddate' => $begdate->format('Y-m-t')
            , 's_itmtypeid' => null,
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $begdate = today();
        //$search_params['s_begdate'] = $begdate->format('Y-m-d');
        //$search_params['s_enddate'] = $begdate->format('Y-m-t');

        $recs = null;

//        $s_year = $search_params['s_year'];
//        $s_month = $search_params['s_month'];

        $s_begdate = $search_params['s_begdate'];
        $s_enddate = $search_params['s_enddate'];

        if ($s_begdate <> '' and $s_enddate <> '') {

            $s_yr_mn = date_format(date_create($s_begdate), 'Y-m');
            //dd($s_yr_mn);
//--            and dw1.wrkdate between '{$s_begdate}' and '{$s_enddate}'
            $s_itmtypeid = $search_params['s_itmtypeid'];

            $sql = "select a.operdate
                , sum(IF(a.dir>0, a.sum, null)) as inp_sum
                , sum(IF(a.dir<0, a.sum, null)) as out_sum
                , sum(a.dir*a.sum) as blns_sum
                , sum(IF(a.dir=0, a.sum, null)) as sale_sum
            from (
            select e.operdate, -1 dir, sum(e.expense_sum) AS SUM, t.name
                from obj_expenses e
                join expensetypes t on t.id=e.expensetypeid
                where sysobjid = 204
                and e.operdate BETWEEN '{$s_begdate}' and '{$s_enddate}'";

            if (isset($s_itmtypeid) && !empty($s_itmtypeid))
                $sql .= " and exists(select 1
                        from wrhdocs wd
                        join wrhdoclst as di on di.docid=wd.id
                        join refitems as ri on ri.id=di.refitmid
                        where wd.id=e.objid and ri.itmtypeid='{$s_itmtypeid}') ";

            $sql .= " GROUP by operdate, e.expensetypeid
            union
                SELECT d.docdate, +1 dir, sum(round(i.price * i.qty,2)) as sum, 'произведенная продукция' as name
                FROM `wrhdocs` d
                    JOIN wrhdoclst as i on i.docid=d.id";

            if (isset($s_itmtypeid) && !empty($s_itmtypeid))
                $sql .= " join refitems as ri on ri.id=i.refitmid
                        and ('{$s_itmtypeid}' is null or ri.itmtypeid='{$s_itmtypeid}')";

            $sql .= " WHERE doctypeid=10 and d.docdate BETWEEN '{$s_begdate}' and '{$s_enddate}'
                group by d.docdate
            union
                SELECT d.docdate as operdate, -1 dir, sum(round(i.price * i.qty,2)) as sum, 'материалы на производство' as name
                FROM `wrhdocs` d
                    JOIN wrhdoclst as i on i.docid=d.id
                WHERE doctypeid=5 and d.docdate BETWEEN '{$s_begdate}' and '{$s_enddate}'";

            if (isset($s_itmtypeid) && !empty($s_itmtypeid))
                $sql .= " and exists(select 1
                from wrhdocs pd
                join wrhdoclst as di on di.docid = pd.id
                join refitems as ri on ri.id=di.refitmid
                where pd.id=d.predocid and ri.itmtypeid='{$s_itmtypeid}') ";

            $sql .= " group by d.docdate";

            $sql .= " union
                SELECT d.docdate, 0 as dir, sum(d.docsum) as sum, 'реализация' as name
                FROM `wrhdocs` as d
                WHERE d.doctypeid in (select id from wrhdoctypes dt where dt.forsale=1)
                AND d.docdate BETWEEN  '{$s_begdate}' and '{$s_enddate}'";

            if (isset($s_itmtypeid) && !empty($s_itmtypeid))
                $sql .= " and exists(select 1
                from wrhdoclst as di
                join refitems as ri on ri.id=di.refitmid
                where di.docid=d.id and ri.itmtypeid='{$s_itmtypeid}') ";

            $sql .= " group by docdate
            ) a
            group by operdate
            order by operdate";

            //dd($s_begdate, $s_yr_mn, $sql);
            $recs = DB::select(DB::raw($sql));
            //dd($sql, $recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $s_begdate . ' - ' . $s_enddate);
        } else {
            $recs = null;
        }

        $data = new \stdClass();
        $data->returl = $returl;
//        dd($data->returl, url()->current(), url()->full());

        $data->itmtypes = wrhdoclst::from("wrhdoclst as di")
            ->join("wrhdocs as d", "d.id", "di.docid")
            ->join("refitems as ri", "ri.id", "di.refitmid")
            ->join("itmtypes as it", "it.id", "ri.itmtypeid")
            ->where("d.doctypeid", 10)  //Поступление от производства
            ->select('it.id', 'it.name')
            ->distinct()
            ->orderby('name', 'asc')
            ->get()
            ->pluck('name', 'id');
        //dd($data->itmtypes);

        return view('wrhdocs.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    function rep66(Request $request, $date)
    {
        //Детализация производства и отгрузки продукции за дату

        $report_id = 66;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $data = new \stdClass();
        $data->date = $date;
        $data->returl = $returl;

//        d.docdate = '{$date}'
        $sql = "select -1 dir, sum(e.expense_sum) AS sum, t.name
            from obj_expenses e
            join expensetypes t on t.id=e.expensetypeid
            where sysobjid=204
            and e.operdate = '{$date}'
            GROUP by operdate, e.expensetypeid
            union
            /*SELECT +1 dir, sum(round(i.price * i.qty,2)) as sum, 'произведенная продукция' as name
            FROM `wrhdocs` d
                JOIN wrhdoclst as i on i.docid=d.id
            WHERE doctypeid=10 and d.docdate = '{$date}'
            group by d.docdate*/
            SELECT +1 dir, sum(round(i.price * i.qty,2)) as sum, concat(ri.name, ' (', round(sum(i.qty),0), ri.unit,  ')') as name
            FROM `wrhdocs` d
                JOIN wrhdoclst as i on i.docid=d.id
                join refitems as ri on ri.id=i.refitmid
            WHERE doctypeid=10 and d.docdate = '{$date}'
            group by d.docdate, i.refitmid
            union
            SELECT -1 dir, sum(round(i.price * i.qty,2)) as sum, 'материалы на производство' as name
            FROM `wrhdocs` d
                JOIN wrhdoclst as i on i.docid=d.id
            WHERE doctypeid=5 and d.docdate = '{$date}'
            group by d.docdate
            order by dir, sum desc";

        $recs = DB::select(DB::raw($sql));

//        dd($date,$sql,$recs);

        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен отчет; ' . $date);
        report::updUseCnt($report_id);

        //        if ($export2xls == "1") {
//            $response = Excel::download(new rep54Export($recs, $data), "Платежи за " . Str::slug($data->$date) . ".xlsx", \Maatwebsite\Excel\Excel::XLSX);
//
//            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
//            //HERE IS THE MAGIC FOLKS
//            ob_end_clean();
//            return $response;
//        }

        return view('wrhdocs.rep' . $report_id, compact('recs', 'data'));
    }

    function rep67(Request $request, $date)
    {
        //Детализация реализации со склада за дату

        $report_id = 67;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $data = new \stdClass();
        $data->date = $date;
        $data->returl = $returl;

//        d.docdate = '{$date}'
        $sql = "select i.refitmid, ri.name, ri.unit, sum(i.qty) as qty, sum(i.price*i.qty) as sum
                FROM `wrhdocs` as d
                join wrhdoclst as i  on i.docid=d.id
                join refitems as ri on ri.id=i.refitmid
                WHERE d.doctypeid in (select id from wrhdoctypes dt where forsale=1)
                    and d.docdate='{$date}'
                group by i.refitmid
                order by sum desc";

        $recs = DB::select(DB::raw($sql));

//        dd($date,$sql,$recs);

        $recs2 = wrhdoc::from('wrhdoclst as dl')
            //->join('wrhdocs as d', 'd.id', 'dl.docid')
            ->join('wrhdocs as d', function ($join) {
                $join->on('d.id', '=', 'dl.docid')
                    ->where('d.docsigned', 1);
            })
            ->join('orgs as o', 'o.id', 'd.orgid')
            ->join('wrhdoctypes as t', function ($join) {
                $join->on('t.id', '=', 'd.doctypeid')
                    ->where('t.forsale', '<>', 0);
            })
            ->join('refitems as ri', 'ri.id', 'dl.refitmid')
            ->where('d.docdate', $date)
            ->select('d.orgid', 'o.name as org_name'
                , 'dl.refitmid', 'ri.name as refitm_name', 'ri.unit as refitm_unit'
                , db::raw("sum(t.forsale * dl.qty) as qty"), 'dl.price', db::raw("sum(t.forsale * dl.qty * dl.price) as itm_sum"))
            ->groupBy('d.orgid', 'dl.refitmid', 'dl.price')
            ->orderBy('org_name', 'asc')
            ->orderBy('d.orgid', 'asc')
            ->orderBy('refitm_name', 'asc')
            ->get();

        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен отчет; ' . $date);
        report::updUseCnt($report_id);

        //        if ($export2xls == "1") {
//            $response = Excel::download(new rep54Export($recs, $data), "Платежи за " . Str::slug($data->$date) . ".xlsx", \Maatwebsite\Excel\Excel::XLSX);
//
//            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
//            //HERE IS THE MAGIC FOLKS
//            ob_end_clean();
//            return $response;
//        }

        return view('wrhdocs.rep' . $report_id, compact('recs', 'recs2', 'data'));
    }


}
