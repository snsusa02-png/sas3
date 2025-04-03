<?php

namespace App\Http\Controllers;

use App\sysobj;
use App\Exports\rep61Export;
use App\refitem;
use App\report;
use App\org;

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

        $this->sysobjcode = 'wrhdocs';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

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
	            , sum(a.pre_sum) as pre_sum
	            , sum(a.inp_qty) as inp_qty
	            , sum(a.inp_sum) as inp_sum
	            , sum(a.out_qty) as out_qty
	            , sum(a.out_sum) as out_sum
	            , sum(a.sale_qty) as sale_qty
	            , sum(a.sale_sum) as sale_sum
	            , sum(a.cur_qty) as cur_qty
	            , sum(a.cur_sum) as cur_sum
                from (
                    SELECT i.refitmid
                        , SUM(t.forStock*i.qty) as pre_qty
                        /*, SUM(IF(t.forStock=1, i.qty*i.price, 0) - IF(t.forStock=-1, i.qty*i.price, 0)) as pre_sum*/
                        , SUM(t.forStock*i.qty*ri.price) as pre_sum /*по текущей цене из номенклатуры*/
                        , null as inp_qty, null as inp_sum
                        , null as out_qty, null as out_sum
                        , null as sale_qty, null as sale_sum
                        , null as cur_qty, null as cur_sum
                    FROM wrhdoclst as i
                    INNER JOIN wrhdocs as d ON d.id = i.docid
                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                    join refitems ri on ri.id=i.refitmid
                    WHERE d.docsigned=1 and  d.docdate < '{$s_begdate}'
                    {$cnd1}
                    GROUP BY refitmid
                    union all
                    SELECT     i.refitmid, null as pre_qty, null as pre_sum
                        , SUM(IF(t.forStock= 1, i.qty, null )) as inp_qty
                        , SUM(IF(t.forStock=+1, i.qty*i.price, null)) as inp_sum
                        , SUM(IF(t.forStock=-1, i.qty, 0)) as out_qty
                        , SUM(IF(t.forStock=-1, i.qty*ri.price, 0)) as out_sum
                        , SUM(IF(t.forSale=1, i.qty, 0)) as sale_qty
                        , SUM(IF(t.forSale=1, i.qty*i.price, 0)) as sale_sum
                        , null as cur_qty
                        , null as cur_sum
                    FROM wrhdoclst as i
                    INNER JOIN wrhdocs as d ON d.id = i.docid
                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                     join refitems ri on ri.id=i.refitmid
                    WHERE d.docsigned=1
                    and d.docdate between '{$s_begdate}' and '{$s_enddate}'
                    {$cnd1}
                    GROUP BY refitmid
                    union all
                    SELECT     i.refitmid
                        , null as pre_qty, null as pre_sum
                        , null as inp_qty, null as inp_sum
                        , null as out_qty, null as out_sum
                        , null as sale_qty, null as sale_sum
                        , sum(t.forstock*i.qty) as cur_qty
                        , sum(t.forstock*i.qty*ri.price) as cur_sum
                        FROM wrhdoclst as i
                        INNER JOIN wrhdocs as d ON d.id = i.docid
                        INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                        join refitems ri on ri.id=i.refitmid
                        WHERE d.docsigned=1
                        and d.docdate <= '{$s_enddate}'
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

    function rep57_o(Request $request)
    {
        //Детализация расхода склада за период

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
                and t.forStock= -1
                order by d.docdate, i.price";

            $recs = DB::select(DB::raw($sql));

//            dd($sql,$recs);

            //занесем в журнал
//            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $s_begdate);
//            report::updUseCnt($report_id);

        } else {
            $recs = null;
        }
        return view('wrhdocs.rep' . $report_id . '_o', compact('search_params', 'recs', 'data'));
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

            if (isset($s_itmtypeid) && !empty($s_itmtypeid)) {
                $sql .= " and ( exists( select 1 from wrhdocs wd
                        join wrhdoclst as di on di.docid=wd.id
                        join refitems as ri on ri.id=di.refitmid
                        where wd.id=e.objid and wd.docsigned=1 and ri.itmtypeid='{$s_itmtypeid}') ";
                // или прочие затраты связаны с актом списания на производство, который привязан к накладной от производства
                // по которой пришли товары заданной категории
                $sql .= " or exists(select 1 from wrhdocs wd
                        join wrhdocs as pd on pd.id=wd.predocid and pd.doctypeid=10 and pd.docsigned=1
                        join wrhdoclst pdi on pdi.docid = pd.id
                        join refitems as ri on ri.id=pdi.refitmid and ri.itmtypeid='{$s_itmtypeid}'
                        where wd.id=e.objid and wd.doctypeid=5) ";
                $sql .= " ) ";
            }

            $sql .= " GROUP by operdate, e.expensetypeid
            union
                SELECT d.docdate as operdate, +1 dir, sum(round(i.price * i.qty,2)) as sum, 'произведенная продукция' as name
                FROM `wrhdocs` d
                    JOIN wrhdoclst as i on i.docid=d.id";

            if (isset($s_itmtypeid) && !empty($s_itmtypeid))
                $sql .= " join refitems as ri on ri.id=i.refitmid
                        and ('{$s_itmtypeid}' is null or ri.itmtypeid='{$s_itmtypeid}')";

            $sql .= " WHERE doctypeid=10 and d.docsigned=1 and d.docdate BETWEEN '{$s_begdate}' and '{$s_enddate}'
                group by d.docdate
            union
                SELECT d.docdate as operdate, -1 dir, sum(round(i.price * i.qty,2)) as sum, 'материалы на производство' as name
                FROM `wrhdocs` d
                JOIN wrhdoclst as i on i.docid=d.id
                WHERE doctypeid=5 and d.docsigned=1 and d.docdate BETWEEN '{$s_begdate}' and '{$s_enddate}'";

            // материалы на произаодство скорее всего не соответствуют заданной категории производимого товара
            // главное, чтобы они были связаны с документом на производство таких товаров
            if (isset($s_itmtypeid) && !empty($s_itmtypeid))
                $sql .= " and exists(select 1
                from wrhdocs pd
                join wrhdoclst as di on di.docid = pd.id
                join refitems as ri on ri.id=di.refitmid
                where pd.id=d.predocid
                    and pd.doctypeid=10
                    and pd.docsigned=1
                    and ri.itmtypeid='{$s_itmtypeid}') ";

            $sql .= " group by d.docdate";

            $sql .= " union
                SELECT d.docdate, 0 as dir, sum(di.price*di.qty) as sum, 'реализация' as name
                FROM `wrhdocs` as d
                join wrhdoclst as di on di.docid=d.id
                join refitems as ri on ri.id=di.refitmid
                WHERE d.docsigned=1 and d.doctypeid in (select id from wrhdoctypes dt where dt.forsale=1)
                AND d.docdate BETWEEN  '{$s_begdate}' and '{$s_enddate}'";

            if (isset($s_itmtypeid) && !empty($s_itmtypeid))
                $sql .= " and ri.itmtypeid='{$s_itmtypeid}'";

            $sql .= " group by docdate";

            // - доп затраты, прикрепленные к актам списания на производство, которые, в свою очередь,
            // являются дочерними к накладной на производство товаров заданной категории
//            $sql .= " union select d.docdate as operdate, -1 dir, sum(expense_sum) as sum, 'доп затраты' as name
//                    FROM `wrhdocs` d
//                    join obj_expenses oe on oe.sysobjid=204 and oe.objid=d.id
//                    WHERE doctypeid=5 and d.docdate BETWEEN '{$s_begdate}' and '{$s_enddate}'";
//
//            if (isset($s_itmtypeid) && !empty($s_itmtypeid))
//                $sql .= " and exists(select 1 from wrhdocs pd
//                            join wrhdoclst as di on di.docid = pd.id
//                            join refitems as ri on ri.id=di.refitmid
//                            where pd.id=d.predocid and ri.itmtypeid='{$s_itmtypeid}')";
//
//            $sql .= " group by d.docdate";

            $sql .= ") a
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

    function rep66(Request $request, $date, $s_itmtypeid)
    {
        //Детализация производства и отгрузки продукции за дату

        $report_id = 66;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $data = new \stdClass();
        $data->date = $date;
        $data->returl = $returl;

        $s_itmtypeid = ($s_itmtypeid == '*') ? '' : $s_itmtypeid;

//        d.docdate = '{$date}'
        $sql = "select -1 dir, sum(e.expense_sum) AS sum, t.name
            from obj_expenses e
            join expensetypes t on t.id=e.expensetypeid
            where sysobjid=204
            and e.operdate = '{$date}'";

        if (isset($s_itmtypeid) && !empty($s_itmtypeid)) {
            $sql .= " and ( exists( select 1 from wrhdocs wd
                        join wrhdoclst as di on di.docid=wd.id
                        join refitems as ri on ri.id=di.refitmid
                        where wd.id=e.objid and wd.docsigned=1 and ri.itmtypeid='{$s_itmtypeid}') ";
            // или прочие затраты связаны с актом списания на производство, который привязан к накладной от производства
            // по которой пришли товары заданной категории
            $sql .= " or exists(select 1 from wrhdocs wd
                        join wrhdocs as pd on pd.id=wd.predocid and pd.doctypeid=10 and pd.docsigned=1
                        join wrhdoclst pdi on pdi.docid = pd.id
                        join refitems as ri on ri.id=pdi.refitmid and ri.itmtypeid='{$s_itmtypeid}'
                        where wd.id=e.objid and wd.doctypeid=5) ";
            $sql .= " ) ";
        }

        $sql .= " GROUP by operdate, e.expensetypeid
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
            WHERE doctypeid=10 and d.docdate = '{$date}'";

        if (isset($s_itmtypeid) && !empty($s_itmtypeid))
            $sql .= " and ('{$s_itmtypeid}' is null or ri.itmtypeid='{$s_itmtypeid}')";

        $sql .= " group by d.docdate, i.refitmid
            union
            SELECT -1 dir, sum(round(i.price * i.qty,2)) as sum, 'материалы на производство' as name
            FROM `wrhdocs` d
                JOIN wrhdoclst as i on i.docid=d.id";

            // материалы на произаодство скорее всего не соответствуют заданной категории производимого товара
            // главное, чтобы они были связаны с документом на производство таких товаров
            if (isset($s_itmtypeid) && !empty($s_itmtypeid))
                $sql .= " and exists(select 1
                from wrhdocs pd
                join wrhdoclst as di on di.docid = pd.id
                join refitems as ri on ri.id=di.refitmid
                where pd.id=d.predocid
                    and pd.doctypeid=10
                    and pd.docsigned=1 and ri.itmtypeid='{$s_itmtypeid}') ";

        $sql .= " WHERE doctypeid=5 and d.docdate = '{$date}'";
        $sql .= " group by d.docdate
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

    function rep67(Request $request, $date, $s_itmtypeid)
    {
        //Детализация реализации со склада за дату

        $report_id = 67;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $data = new \stdClass();
        $data->date = $date;
        $data->returl = $returl;

        $s_itmtypeid = ($s_itmtypeid == '*') ? '' : $s_itmtypeid;

//        d.docdate = '{$date}'
        $sql = "select i.refitmid, ri.name, ri.unit, sum(i.qty) as qty, sum(i.price*i.qty) as sum
                FROM `wrhdocs` as d
                join wrhdoclst as i  on i.docid=d.id
                join refitems as ri on ri.id=i.refitmid
                WHERE d.doctypeid in (select id from wrhdoctypes dt where forsale=1)
                    and d.docdate='{$date}'";

        if (isset($s_itmtypeid) && !empty($s_itmtypeid)) {
            $sql .= " and ri.itmtypeid='{$s_itmtypeid}'";
        }

        $sql .= " group by i.refitmid
                order by sum desc";

        $recs = DB::select(DB::raw($sql));

//        dd($date,$sql,$recs);
        $sc = "1=1";
        if (isset($s_itmtypeid) && !empty($s_itmtypeid)) {
            $sc .= " and ri.itmtypeid='{$s_itmtypeid}'";
        }

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
            ->whereraw($sc)
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

    public function rep69(Request $request)
    {
        //
        $report_id = 69;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данными для этой организации!']);

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
            , 's_itmtypeid' => ''
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
        $sc0 = $sc = "1=1";
        $s_begdate = $search_params['s_begdate'];

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;

                if ($item == 's_ownorgid') {
                    $sc = $sc . " and m.orgid = '{$val}'";

                } elseif ($item == 's_itmtypeid') {
                    $sc = $sc . " and ri.itmtypeid = {$val}";
                    $sc0 .= " and ri0.itmtypeid = {$val}";

                } elseif ($item == 's_refitmid') {
                    $sc = $sc . " and i.refitmid = {$val}";

                } elseif ($item == 's_begdate') {
                    $sc = $sc . " and d.docdate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc = $sc . " and d.docdate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(d.docdate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(fp.paydate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(fp.paydate) = '{$val}'";
                }
            }
        }

        $recs = null;
        if ($need_search) {

            /*SELECT i.refitmid, max(ri.name) as name, max(ri.unit) as unittypes
	, max(au.k2ref_unit) as k2ref_unit
            -- , max(1/au.k2ref_unit) as qty_per_m3
	, sum(i.qty) as qty
	, sum(round(i.qty/au.k2ref_unit, 3)) qty_m3
	FROM `wrhdocs` as wd
	join wrhdoclst i on i.docid=wd.id
    join refitems as ri on ri.id=i.refitmid
    left join ri_units au on au.refitmid=ri.id and au.unittypeid = 8
    WHERE wd.doctypeid=10
            and ri.itmtypeid =123
            -- and i.refitmid=1762
            and wd.docsigned = 1
    group by i.refitmid, au.k2ref_unit
    order by ri.name
            */

//            $recs = wrhdoc::from('wrhdocs as d')
//                ->join('wrhdoclst as i', 'i.docid', 'd.id')
//                ->join('refitems as ri', 'ri.id', 'i.refitmid')
//                ->leftjoin('ri_units as au', function ($join) {
//                    $join->on('au.refitmid', '=', 'i.refitmid')
//                        ->where('au.unittypeid', 8);
//                })
//                ->select(
//                    'i.refitmid'
//                    , db::raw("max(ri.name) as name")
//                    , db::raw("max(ri.unit) as unittype")
//                    //, db::raw("concat(m.name, ', ', m.regnum) as machine_name")
//                    , db::raw("sum(i.qty) as qty")
//                    , db::raw("sum(round(i.qty/au.k2ref_unit, 3)) qty_au")
//                )
//                ->where('d.docsigned', 1)
//                ->whereRaw($sc)
//                ->groupBy(['i.refitmid'])
//                ->orderby('ri.name', 'asc')
//                ->get();

            // 2025-03-15
            //dd($sc);
            $sql = "SELECT t.refitmid
                , ri.name as itmname
                , ri.unit as unit
                , sum(beg_qty) as beg_qty
                , round(sum(beg_qty_m3), 3) as beg_qty_m3
                , sum(prod_qty) as prod_qty
                , round(sum(prod_qty_m3), 3) as prod_qty_m3
                , sum(sale_qty) as sale_qty
                , round(sum(sale_qty_m3),3) as sale_qty_m3
                from(
            SELECT di.refitmid
				, dt.forstock*di.qty as beg_qty
                , dt.forstock*di.qty/au.k2ref_unit as beg_qty_m3
                , null as prod_qty
                , null prod_qty_m3
                , null as sale_qty
                , null as sale_qty_m3
                FROM `wrhdocs` as d
                join wrhdoctypes as dt on dt.id=d.doctypeid and dt.forstock<>0
                join wrhdoclst di on di.docid=d.id
                join refitems as ri on ri.id=di.refitmid
                left join ri_units au on au.refitmid=ri.id and au.unittypeid = 8
                WHERE d.docsigned = 1
                    and d.docdate < '{$s_begdate}'
                    /*номенклатура - из производства*/
                    and exists(select 1 from  wrhdocs d0
						join wrhdoclst di0 on di0.docid=d0.id
						join refitems ri0 on ri0.id=di0.refitmid
                        where d0.doctypeid=10
                        and d0.docdate < '{$s_begdate}'
                        and {$sc0}
                        and di0.refitmid=di.refitmid)
            union ALL
            SELECT di.refitmid
                , null as beg_qty
                , null as beg_qty_m3
                , di.qty as prod_qty
                , di.qty/au.k2ref_unit prod_qty_m3
                , null as sale_qty
                , null as sale_qty_m3
                FROM `wrhdocs` as d
                join wrhdoclst di on di.docid=d.id
                join refitems as ri on ri.id=di.refitmid
                left join ri_units au on au.refitmid=ri.id and au.unittypeid = 8
                WHERE d.doctypeid=10 /* производство*/
                    and d.docsigned = 1
                    and {$sc}
            union ALL
            SELECT di.refitmid
                , null as beg_qty
                , null as beg_qty_m3
                , null as prod_qty
                , null as prod_qty_m3
                , di.qty as sale_qty
                , di.qty/au.k2ref_unit as sale_qty_m3
                FROM `wrhdoctypes` dt
                join wrhdocs as d on d.doctypeid=dt.id
                join wrhdoclst di on di.docid=d.id
                join refitems as ri on ri.id=di.refitmid
                left join ri_units au on au.refitmid=ri.id and au.unittypeid = 8
                WHERE dt.forsale=1
                and d.docsigned = 1
                and {$sc}
            ) as t
            join refitems ri on ri.id=t.refitmid
            group by refitmid
            order by ri.name";
            $recs = DB::select(DB::raw($sql));
            //dd($sc, $s_begdate, $sql, $recs);

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
                return wrhdoc::selectRaw("year(docdate) as year")
                    ->where('doctypeid', 10)->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });

//        $data->ownorgs = org::lstFor_cached([
//            'in_mchn_raids_ownorgid' => 1,
//        ]);
//        $data->orgs = org::lstFor_cached([
//            'in_mr_opers' => 1,
//            'not_flagtypeid' => 12,
//        ]);

//        $data->mchntypes = mchntype::lstFor_cached([
//            'in_fuelcard_pays' => 1,
//        ]);
//        $data->fuelcards = fuelcard::lstFor_cached([
//            'in_fuelcard_pays' => 1,
//        ]);
//        $data->fuelcards = collect($data->fuelcards)->sortBy('name')->reverse()->toArray();
//        //dd($data->fuelcards);

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
        return view('wrhdocs.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }


}
