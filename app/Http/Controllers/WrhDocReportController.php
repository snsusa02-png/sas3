<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\equiprqst_item;
use App\eritm_offer;
use App\eritm_supply;
use App\Exports\InvoicesExport;
use App\Exports\PayPlanExport;
use App\Exports\rep53Export;
use App\mchn_raid;
use App\mr_oper;
use App\obj_finoper;
use App\org_saldo;
use App\orgplnpay;
use App\orgplnpay_item;
use App\orgstaff;
use App\pay_category;
use App\paydoc;
use App\task;
use App\prodplan_fact;
use App\report;
use App\org;
use App\group;
use App\machine;
use App\mchnrqsttype;
use App\mchnrqst;
use App\mchntype;
use App\contract;
use App\objflag;
use App\objlog;
use App\Traits\SearchDataTrait;
use App\User;
use App\user_template;
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

        $sql = "select a.ownorgid, oo.name as ownorg_name
                , a.refitmid, ri.name as refitm_name, ri.unit as refitm_unit
	            , sum(a.pre_qty) as pre_qty
	            , sum(a.pre_sum) as pre_sum
	            , sum(a.inp_qty) as inp_qty
	            , sum(a.inp_sum) as inp_sum
	            , sum(a.out_qty) as out_qty
	            , sum(a.out_sum) as out_sum
	            , sum((a.pre_qty + a.inp_qty - a.out_qty)*rp.price) as end_sum
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
                GROUP BY a.refitmid, a.ownorgid
                order by ownorg_name, ownorgid, refitm_name ";

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
            's_enddate' => null,
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $s_begdate = $search_params['s_begdate'];
        $s_enddate = $search_params['s_enddate'];

//        dd($s_begdate, isset($s_begdate), is_null($s_begdate));
        $data = new \stdClass();
        $data->returl = $returl;

        //dd($search_params, $data);

        if ($s_begdate <> '') {
            $sql = "select a.refitmid, ri.name as refitm_name, ri.unit as refitm_unit
	            , sum(a.pre_qty) as pre_qty, sum(a.inp_qty) as inp_qty
	            , sum(a.out_qty) as out_qty, sum(a.sale_sum) as sale_sum
                from (
                    SELECT i.refitmid
                        , SUM(IF(t.forStock=1, i.qty, 0) - IF(t.forStock=-1, i.qty, 0)) as pre_qty
                        , null as inp_qty, null as out_qty, null as sale_sum
                    FROM wrhdoclst as i
                    INNER JOIN wrhdocs as d ON d.id = i.docid
                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                    WHERE d.docsigned=1 and  d.docdate < '{$s_begdate}'
                    GROUP BY refitmid
                    union all
                    SELECT     i.refitmid, null as pre_qty
                        , SUM(IF(t.forStock= 1, i.qty, null )) as inp_qty
                        , SUM(IF(t.forStock=-1, i.qty, null)) as out_qty
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
        return view('wrhdocs.rep' . $report_id, compact('search_params', 'recs', 'recs2', 'data'));
    }

}
