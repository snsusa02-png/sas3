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
                    WHERE d.docsigned=1 and  d.docdate < '{$date}'
                    GROUP BY refitmid
                    union all
                    SELECT     i.refitmid, null as pre_qty
                        , SUM(IF(t.forStock= 1, i.qty, null )) as inp_qty
                        , SUM(IF(t.forStock=-1, i.qty, null)) as out_qty
                        , SUM(IF(t.forSale= 1, i.qty*i.price, null)) as sale_sum
                    FROM wrhdoclst as i
                    INNER JOIN wrhdocs as d ON d.id = i.docid
                    INNER JOIN wrhdoctypes as t ON t.id = d.doctypeid AND t.forstock <> 0
                    WHERE d.docsigned=1 and d.docdate = '{$date}'
                    GROUP BY refitmid
                    ) as a
                INNER JOIN  refitems as ri ON ri.id = a.refitmid
                GROUP BY refitmid
                order by refitm_name";

        /*$recs = wrhdoclst::from('wrhdoclst as i')
            ->join('wrhdocs as d', 'd.id', 'i.docid')
            ->join('wrhdoctypes as t', function ($join) {
                $join->on('t.id', '=', 'd.doctypeid')
                    ->where('t.forstock', '<>', 0);
            })
            ->join('refitems as ri', 'ri.id', 'i.refitmid')
            ->where('d.docdate', $date)
            ->where('d.docsigned', 1)
            ->select('i.refitmid', 'ri.name as refitm_name', 'ri.unit as refitm_unit'
                , db::raw("sum( if(t.forStock= 1, i.qty, null)) as inp_qty")
                , db::raw("sum( if(t.forStock=-1, i.qty, null)) as out_qty")
                , db::raw("sum( if(t.forStock=-1, i.qty*i.price, null)) as out_sum")
            )
            ->groupBy('refitmid')
            ->orderBy('refitm_name')
            ->get();
        */
        $recs = DB::select(DB::raw($sql));

        //dd($date,$sql,$recs);

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

        return view('wrhdocs.rep' . $report_id, compact('recs', 'data'));
    }

}
