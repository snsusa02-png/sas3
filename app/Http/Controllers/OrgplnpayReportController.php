<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\equiprqst_item;
use App\eritm_offer;
use App\eritm_supply;
use App\Exports\InvoicesExport;
use App\Exports\PayPlanExport;
use App\orgplnpay;
use App\orgplnpay_item;
use App\pay_category;
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

class OrgplnpayReportController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;  //reports
        //$this->objcode = 'reports';
        $this->objcode = 'equiprqsts';
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


    public function rep13(Request $request)
    {//Заявки на оплату, ожидающие оплаты
        $report_id = 13;

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep13";
        $s_ownorgid = "";
        $s_orgid = "";
        $s_buildobjid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");
            $s_buildobjid = $request->get("s_buildobjid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
                's_buildobjid' => $s_buildobjid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (1 == 1 and !empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //$d = new DateTime('last day of previous month');
                    $d = new DateTime('previous day');
                    $s_enddate = $d->format('Y-m-d');
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
            }
        }

        $search_params = [
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            "s_ownorgid" => $s_ownorgid,
            "s_orgid" => $s_orgid,
            "s_buildobjid" => $s_buildobjid,
        ];

        //Если параметры поиска не заданы, то уйдем на index
        $sc = null;
        $needSearch = false;
        foreach ($search_params as $p) {
            if ($p <> "") {
                $needSearch = true;
                break;
            }
        }
        $needSearch = true; //так как нет обязательных параметров

        if ($needSearch) {
            $sc = " 1=1 ";
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and b.orgid=" . $s_ownorgid;
            }
            if (strlen($s_orgid) > 0) {
                $sc .= " and er.orgid=" . $s_orgid;
            }
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = orgplnpay_item::from('orgplnpay_items as pi')
                ->join('orgplnpays as p', function ($j) {
                    $j->on('p.id', 'pi.docid')
                        ->whereraw('p.docdate=curdate()');
                })
//                ->join('orgs as ro', function ($j) {
//                    $j->on('ro.id', 'er.orgid');
//                })
                ->join('orgs as oo', 'oo.id', 'p.ownorgid')
                ->join('orgs as o', 'o.id', 'pi.orgid')
                ->whereNull("pi.fctpay_at")
                ->where("pi.orgid", '<>', 131)//ИФНС
                ->whereraw("datediff(now(),pi.created_at)>1");
            //->whereRaw($sc);


            $recs = $recs->select(
                'pi.*'
                , 'p.ownorgid', 'oo.name as ownorgname'
                , 'o.name as orgname'
                , db::raw('TIMESTAMPDIFF(second, pi.created_at, now()) as wait_seconds')
            )
                ->orderby('ownorgname')
                ->orderby('p.ownorgid')
                ->orderby('wait_seconds', 'desc')
                ->get();
            //dd($recs);


            //обновим счетчик использования отчета
            report::where('id', $report_id)->update([
                'use_cnt' => DB::raw('use_cnt + 1'),
                'lastuse_dt' => now(),
                'lastuse_userid' => \Auth::user()->id,
                'lastuse_username' => \Auth::user()->name,
            ]);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();
        $data->ownorgs = org::from('orgs as o')
            ->whereRaw(" o.id in (
        SELECT  b.orgid
        FROM equiprqst_items as ri
		    join budget_itmsums as bis on bis.id=ri.bdgtitmsumid
		    join budget_items as bi on bi.id=bis.itmid
		    join budgets as b on b.id=bi.budgetid
	)")
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();

        $data->orgs = org::from('orgs as o')
            ->whereRaw('exists (select 1 from equiprqsts as er where er.orgid=o.id)')
            ->select('id', 'name')
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();

        $data->buildobjs = buildobj::from('buildobjs as bo')
            ->whereRaw('exists (select 1 from equiprqsts as er where er.buildobjid=bo.id)')
            ->select('id', 'name')
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();


        return view('orgplnpays.rep13', compact('recs', 'search_params', 'data'));
    }

    public function rep14(Request $request)
    {//Оплаченные счета
        $report_id = 14;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы со счетами для этой организации!']);

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep14";
        $s_ownorgid = "";
        $s_orgid = "";
        $s_buildobjid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");
            $s_buildobjid = $request->get("s_buildobjid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
                's_buildobjid' => $s_buildobjid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (1 == 1 and !empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                } else {
                    // Значения по-умолчанию --------------------------
                    //$d = new DateTime('first day of previous month');
                    $d = new DateTime('previous day');
                    $s_begdate = $d->format('Y-m-d');
                    //$d = new DateTime('last day of previous month');
//                    $d = new DateTime('previous day');
//                    $s_enddate = $d->format('Y-m-d');
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
            }
        }

        $search_params = [
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            "s_ownorgid" => $s_ownorgid,
            "s_orgid" => $s_orgid,
            "s_buildobjid" => $s_buildobjid,
        ];

        //Если параметры поиска не заданы, то уйдем на index
        $sc = null;
        $needSearch = false;
        foreach ($search_params as $p) {
            if ($p <> "") {
                $needSearch = true;
                break;
            }
        }
        $needSearch = true; //так как нет обязательных параметров

        if ($needSearch) {
            $sc = " 1=1 ";
            if (strlen($s_begdate) > 0) {
                $sc .= " and pi.fctpay_at >= '" . $s_begdate . "'";
            }
            if (strlen($s_enddate) > 0) {
                $sc .= " and pi.fctpay_at <='" . $s_enddate . " 23:59:59'";
            }
            if (strlen($s_orgid) > 0) {
                $sc .= " and pp.orgid=" . $s_orgid;
            }
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = orgplnpay_item::
            from('orgplnpay_items as pi')
                ->join('orgplnpays as pp', 'pp.id', 'pi.docid')
                ->join('orgs as oo', 'oo.id', 'pp.ownorgid')
                ->join('orgs as o', 'o.id', 'pi.orgid')
                ->join('users as u', 'u.id', 'pi.fctpay_by')
//                ->join('orgs as ro', function ($j) {
//                    $j->on('ro.id', 'er.orgid');
//                })
                ->whereNotNull("pi.fctpaysum")
                ->where("pi.categoryid", 7)//Материалы
                ->whereRaw($sc);

            $recs = $recs->select(
                'o.name', 'pi.fctpaysum', 'pi.fctpay_at', 'u.name'
                , 'pi.src_sysobjid', 'pi.src_objid', 'pi.reason', 'pi.fctpay_by'
                , 'pp.ownorgid'
                , 'oo.name as ownorgname'
                , 'pi.orgid'
                , 'o.name as orgname'
                , 'u.name as payusername'
            )
                ->orderby('ownorgname', 'asc')
                ->orderby('ownorgid', 'asc')
                ->orderby('fctpay_at', 'desc')
                ->get();
            //dd($recs);


            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();
        $data->ownorgs = org::from('orgs as o')
            ->whereRaw(" o.id in (
        SELECT  b.orgid
        FROM equiprqst_items as ri
		    join budget_itmsums as bis on bis.id=ri.bdgtitmsumid
		    join budget_items as bi on bi.id=bis.itmid
		    join budgets as b on b.id=bi.budgetid
	)")
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();

        $data->orgs = org::from('orgs as o')
            ->whereRaw('exists (select 1 from equiprqsts as er where er.orgid=o.id)')
            ->select('id', 'name')
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();

        $data->buildobjs = buildobj::from('buildobjs as bo')
            ->whereRaw('exists (select 1 from equiprqsts as er where er.buildobjid=bo.id)')
            ->select('id', 'name')
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();


        return view('orgplnpays.rep14', compact('recs', 'search_params', 'data'));
    }

    public function rep16(Request $request)
    {//Оплаченные счета на материалы
        $report_id = 16;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, 'invoices.allorgs_for_er'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы со счетами для этой организации!']);

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep" . $report_id;
        $s_ownorgid = "";
        $s_orgid = "";
        $s_buildobjid = "";
        $s_begdate = "";
        $s_enddate = "";
        $s_view_days = 7;
        $s_paystatus = "";

        if ($request->isMethod('post')) {

            $s_view_days = $request->get("s_view_days");
            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");
            $s_buildobjid = $request->get("s_buildobjid");
            $s_paystatus = $request->get("s_paystatus");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_view_days' => $s_view_days,
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
                's_buildobjid' => $s_buildobjid,
                's_paystatus' => $s_paystatus,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (1 == 1 and !empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_view_days = $params['s_view_days'];
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                    $s_paystatus = $params['s_paystatus'] ?? null;
                } else {
                    // Значения по-умолчанию --------------------------
                    $s_view_days = 7;
                    //$d = new DateTime('first day of previous month');
                    $d = new DateTime('previous day');
                    $s_begdate = $d->format('Y-m-d');
                    //$d = new DateTime('last day of previous month');
//                    $d = new DateTime('previous day');
//                    $s_enddate = $d->format('Y-m-d');
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
            }
        }

        $search_params = [
            "s_view_days" => $s_view_days,
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            "s_ownorgid" => $s_ownorgid,
            "s_orgid" => $s_orgid,
            "s_buildobjid" => $s_buildobjid,
            "s_paystatus" => $s_paystatus,
        ];

        //Если параметры поиска не заданы, то уйдем на index
        $sc = null;
        $needSearch = false;
        foreach ($search_params as $p) {
            if ($p <> "") {
                $needSearch = true;
                break;
            }
        }
        //$needSearch = true; //так как нет обязательных параметров

        $sc = " 1=1 ";
        $s_view_days = $s_view_days ?? 7;
        $sc .= " and pp.docdate >= date_sub(curdate(), interval {$s_view_days} day)";

        if ($needSearch) {

            //            if (strlen($s_begdate) > 0) {
//                $sc .= " and pi.fctpay_at >= '".$s_begdate."'";
//            }
//            if (strlen($s_enddate) > 0) {
//                $sc .= " and pi.fctpay_at <='".$s_enddate." 23:59:59'";
//            }
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and pp.ownorgid=" . $s_ownorgid;
            }
            if (strlen($s_orgid) > 0) {
                $sc .= " and pi.orgid=" . $s_orgid;
            }
            if (strlen($s_paystatus) > 0) {
                if ($s_paystatus == 0)
                    $sc .= " and ifnull(pi.fctpaysum,0)=0";
                elseif ($s_paystatus == 1)
                    $sc .= " and pi.fctpaysum>0 and pi.fctpaysum<pi.plnpaysum";
                elseif ($s_paystatus == 2)
                    $sc .= " and ifnull(pi.fctpaysum,0)=pi.plnpaysum";
            }
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = orgplnpay_item::
            from('orgplnpay_items as pi')
                ->join('orgplnpays as pp', 'pp.id', 'pi.docid')
                ->join('orgs as oo', 'oo.id', 'pp.ownorgid')
                ->join('orgs as o', 'o.id', 'pi.orgid')
                ->leftjoin('invoices as inv', function ($j) {
                    $j->on('inv.id', 'pi.src_objid')
                        ->where('pi.src_sysobjid', 915);
                })
                ->leftjoin('users as u1', 'u1.id', 'pi.agr1_by')
                ->leftjoin('users as u2', 'u2.id', 'pi.agr2_by')
                ->where("pi.categoryid", 7)//Материалы
                //->whereRaw("pi.fctpaysum is not null or pp.docdate=curdate()")
                ->whereRaw($sc);

            $recs = $recs->select(
                'pi.*'
                , 'pp.docdate'
                , 'pp.ownorgid', 'oo.name as ownorgname'
                , 'o.name as orgname'
                , 'inv.docnum as invoice_docnum'
                , DB::raw("concat(u1.lname,' ',left(u1.fname,1),'.',left(u1.mname,1),'.') as agr1_by_name")
                , DB::raw("concat(u2.lname,' ',left(u2.fname,1),'.',left(u2.mname,1),'.') as agr2_by_name")
            )
                ->orderby('pp.docdate', 'desc')
                ->orderby('ownorgname', 'asc')
                ->orderby('ownorgid', 'asc')
                //->orderby('fctpay_at', 'desc')
                ->get();
            //dd($recs);


            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();
        $data->ownorgs = org::from('orgs as o')
            ->whereRaw(" o.id in (SELECT pp.ownorgid FROM orgplnpays as pp join orgplnpay_items as pi
                on pi.docid=pp.id and pi.categoryid=7
                where date_sub(curdate(), interval {$s_view_days} day) <= pp.docdate)")
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();

        $data->orgs = org::from('orgs as o')
            ->whereRaw("exists (select 1 from orgplnpay_items as pi
                join orgplnpays as pp on pp.id=pi.docid and date_sub(curdate(), interval {$s_view_days} day) <= pp.docdate
                where pi.orgid=o.id and pi.categoryid=7)")
            ->select('id', 'name')
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();

        $data->paystatuses = [
            0 => 'не оплачен',
            1 => 'оплачен частично',
            2 => 'оплачен полностью',
        ];

//        $data->buildobjs = buildobj::from('buildobjs as bo')
//            ->whereRaw('exists (select 1 from equiprqsts as er where er.buildobjid=bo.id)')
//            ->select('id', 'name')
//            ->orderby('name')
//            ->get()
//            ->pluck('name', 'id')->toArray();


        return view('orgplnpays.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public function rep17(Request $request)
    {//Анализ срока ожидания оплаты счетов
        $report_id = 17;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, 'invoices.allorgs_for_er')
            and !usrsysright::isUserHasRightByCode_cached($userid, 'invoices.allorgs'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данным отчетом!']);

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep" . $report_id;
        $s_ownorgid = "";
        $s_orgid = "";
        $s_buildobjid = "";
        $s_begdate = "";
        $s_enddate = "";
        $s_view_days = 7;
        $s_paystatus = "";

        if ($request->isMethod('post')) {

            $s_view_days = $request->get("s_view_days");
            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");
            $s_buildobjid = $request->get("s_buildobjid");
            $s_paystatus = $request->get("s_paystatus");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_view_days' => $s_view_days,
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
                's_buildobjid' => $s_buildobjid,
                's_paystatus' => $s_paystatus,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (1 == 1 and !empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_view_days = $params['s_view_days'];
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                    $s_paystatus = $params['s_paystatus'] ?? null;
                } else {
                    // Значения по-умолчанию --------------------------
                    $s_view_days = 7;
                    //$d = new DateTime('first day of previous month');
                    $d = new DateTime('previous day');
                    $s_begdate = $d->format('Y-m-d');
                    //$d = new DateTime('last day of previous month');
//                    $d = new DateTime('previous day');
//                    $s_enddate = $d->format('Y-m-d');
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
            }
        }

        $search_params = [
            "s_view_days" => $s_view_days,
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            "s_ownorgid" => $s_ownorgid,
            "s_orgid" => $s_orgid,
            "s_buildobjid" => $s_buildobjid,
            "s_paystatus" => $s_paystatus,
        ];

        //Если параметры поиска не заданы, то уйдем на index
        $sc = null;
        $needSearch = false;
        foreach ($search_params as $p) {
            if ($p <> "") {
                $needSearch = true;
                break;
            }
        }
        //$needSearch = true; //так как нет обязательных параметров

        $sc = " 1=1 ";
        $s_view_days = $s_view_days ?? 7;
        //$sc .= " and pp.docdate >= date_sub(curdate(), interval {$s_view_days} day)";

        if ($needSearch) {

            //            if (strlen($s_begdate) > 0) {
//                $sc .= " and pi.fctpay_at >= '".$s_begdate."'";
//            }
//            if (strlen($s_enddate) > 0) {
//                $sc .= " and pi.fctpay_at <='".$s_enddate." 23:59:59'";
//            }
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and pp.ownorgid=" . $s_ownorgid;
            }
            if (strlen($s_orgid) > 0) {
                $sc .= " and ppi.orgid=" . $s_orgid;
            }
        }
        // --------------------------------------------------------------------
        $needSearch = true;
        if ($needSearch) {

            $recs = orgplnpay_item::
            from('orgplnpay_items as ppi')
                ->join('orgplnpays as pp', 'pp.id', 'ppi.docid')
                ->join('orgs as oo', 'oo.id', 'pp.ownorgid')
                //->where("ppi.categoryid", 7) //Материалы
                //->whereRaw("ppi.fctpaysum is not null or pp.docdate=curdate()")
                ->whereNotNull('ppi.fctpay_at')
                ->whereRaw($sc);

            $recs = $recs->selectRaw("
            year(ppi.created_at) as year, month(ppi.created_at) as month, pp.ownorgid
            , max(oo.name) as ownorgname
            , min(time_to_sec(timediff(ppi.fctpay_at,ppi.created_at))) as min_sec
            , avg(time_to_sec(timediff(ppi.fctpay_at,ppi.created_at))) as avg_sec
            , max(time_to_sec(timediff(ppi.fctpay_at,ppi.created_at))) as max_sec
            ,count(*) as cnt")
                ->groupbyraw('1')
                ->groupbyraw('2')
                ->groupbyraw('3')
                ->orderbyraw('1 desc')
                ->orderbyraw('2 desc')
                ->orderby('ownorgname', 'asc')
                ->get();
            //dd($recs);


            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();
        $data->ownorgs = org::from('orgs as o')
            ->whereRaw(" o.id in (SELECT pp.ownorgid FROM orgplnpays as pp join orgplnpay_items as pi
                on pi.docid=pp.id and pi.categoryid=7
                where date_sub(curdate(), interval {$s_view_days} day) <= pp.docdate)")
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();

        $data->orgs = org::from('orgs as o')
            ->whereRaw("exists (select 1 from orgplnpay_items as pi
                join orgplnpays as pp on pp.id=pi.docid and date_sub(curdate(), interval {$s_view_days} day) <= pp.docdate
                where pi.orgid=o.id and pi.categoryid=7)")
            ->select('id', 'name')
            ->orderby('name')
            ->get()
            ->pluck('name', 'id')->toArray();

        $data->paystatuses = [
            0 => 'не оплачен',
            1 => 'оплачен частично',
            2 => 'оплачен полностью',
        ];

//        $data->buildobjs = buildobj::from('buildobjs as bo')
//            ->whereRaw('exists (select 1 from equiprqsts as er where er.buildobjid=bo.id)')
//            ->select('id', 'name')
//            ->orderby('name')
//            ->get()
//            ->pluck('name', 'id')->toArray();


        return view('orgplnpays.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public function rep31(Request $request)
    {//Оплаченные счета
        $report_id = 31;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы со счетами для этой организации!']);

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep" . $report_id;
        $s_ownorgid = "";
        $s_orgid = "";
        $s_categoryid = "";
        $s_begdate = "";
        $s_enddate = "";
        $s_paystatusid = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");
            $s_categoryid = $request->get("s_categoryid");
            $s_paystatusid = $request->get("s_paystatusid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
                's_categoryid' => $s_categoryid,
            ]]);
        } else {


            // Значения по-умолчанию --------------------------
            //$d = new DateTime('first day of previous month');
            $d = new DateTime('previous day');
            $s_begdate = $d->format('Y-m-d');
            //dd($s_begdate);
            //$d = new DateTime('last day of previous month');
//                    $d = new DateTime('previous day');
//                    $s_enddate = $d->format('Y-m-d');
            //------------------------------------------------

            if (session('search_setname') == $search_setname) {
                if (1 == 1 and !empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                    $s_categoryid = $params['s_categoryid'] ?? null;
                    $s_paystatusid = $params['s_paystatusid'] ?? null;
                } else {
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
            }
        }

        $search_params = [
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            "s_ownorgid" => $s_ownorgid,
            "s_orgid" => $s_orgid,
            "s_categoryid" => $s_categoryid,
            "s_paystatusid" => $s_paystatusid,
        ];

        //Если параметры поиска не заданы, то уйдем на index
        $sc = null;
        $needSearch = false;
        foreach ($search_params as $p) {
            if ($p <> "") {
                $needSearch = true;
                break;
            }
        }
        $needSearch = true; //так как нет обязательных параметров

        if ($needSearch) {
            $sc = " 1=1 ";

//            $sc .= " and exists (select 1 from userorgs as uo where uo.orgid=pp.ownorgid
//                                and uo.userid={$userid} and uo.active=1 and uo.acs_orgplnpays=1
//                                and now() between uo.begdt and ifnull(uo.enddt,now()) )";
            //todo: 2021-04-25 заменить это |^| ограничение на вариант с использованием usrsysrights.limsysobjid/limobjid
            $sc .= " and exists( select 1 FROM usrsysrights AS usr
                WHERE
                    usr.userid = {$userid} AND usr.sysfuncid = 410  AND usr.active = 1
                    AND now() BETWEEN usr.begdt AND IFNULL(usr.enddt, now())
                    AND IFNULL(usr.limsysobjid, 111) = 111
                    AND IFNULL(usr.limobjid, pp.ownorgid) = pp.ownorgid
                    )";


            if (strlen($s_begdate) > 0) {
                $sc .= " and pi.fctpay_at >= '" . $s_begdate . "'";
            }
            if (strlen($s_enddate) > 0) {
                $sc .= " and pi.fctpay_at <='" . $s_enddate . " 23:59:59'";
            }
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and pp.ownorgid=" . $s_ownorgid;
            }
            if (strlen($s_orgid) > 0) {
                $sc .= " and pi.orgid=" . $s_orgid;
            }
            if (strlen($s_categoryid) > 0) {
                $sc .= " and pi.categoryid=" . $s_categoryid;
            }
            if (strlen($s_paystatusid) > 0) {
                if ($s_paystatusid == 1) {
                    //неоплаченные
                    $sc .= " and pi.fctpaysum=0";
                } elseif ($s_paystatusid == 3) {
                    $sc .= " and pi.fctpaysum>0";
                }

            }
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = orgplnpay_item::
            from('orgplnpay_items as pi')
                ->join('orgplnpays as pp', 'pp.id', 'pi.docid')
                ->join('orgs as oo', 'oo.id', 'pp.ownorgid')
                ->join('orgs as o', 'o.id', 'pi.orgid')
                ->join('users as u', 'u.id', 'pi.fctpay_by')
                ->leftjoin('pay_categories as pc', 'pc.id', 'pi.categoryid')
//                ->join('orgs as ro', function ($j) {
//                    $j->on('ro.id', 'er.orgid');
//                })
                ->whereNotNull("pi.fctpaysum")
                ->whereRaw($sc);

            $recs = $recs->select(
                'o.name', 'pi.fctpaysum', 'pi.fctpay_at', 'u.name'
                , 'pi.src_sysobjid', 'pi.src_objid', 'pi.reason', 'pi.fctpay_by'
                , 'pp.ownorgid'
                , 'oo.name as ownorgname'
                , 'pi.orgid'
                , 'o.name as orgname'
                , 'u.name as payusername'
                , 'pc.name as category_name'
            )
                ->orderby('ownorgname', 'asc')
                ->orderby('ownorgid', 'asc')
                ->orderby('fctpay_at', 'desc')
                ->orderby('categoryid', 'asc')
                ->get();
            //dd($recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->ownorgs = org::lstFor([
            'in_orgplnpays' => 1,
            //'in_userorgs_with_acs_orgplnpays' => $userid,
            'user_has_right_for_org' => 410,
        ]);

        $data->orgs = org::lstFor([
            'in_orgplnpay_items' => 1,
            'orgplnpays_ownorgid_in' => implode(',', array_keys($data->ownorgs)),
        ]);

        $data->categories = pay_category::lstFor([
            'in_orgplnpay_items' => 1,
        ]);

//        $data->buildobjs = buildobj::from('buildobjs as bo')
//            ->whereRaw('exists (select 1 from equiprqsts as er where er.buildobjid=bo.id)')
//            ->select('id', 'name')
//            ->orderby('name')
//            ->get()
//            ->pluck('name', 'id')->toArray();


        return view('orgplnpays.rep31', compact('recs', 'search_params', 'data'));
    }


    public function rep43(Request $request)
    {
        //Патежный календарь

        $report_id = 43;

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

        $month = date("n");
        $yearQuarter = ceil($month / 3);

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => '' //Auth::user()->curorgid
            , 's_period_type' => 1
            , 's_begdate' => $fdomc
            , 's_enddate' => $ldomc
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

        //по не полностью оплаченным строкам
        //$sc .= " and pi.plnpaysum-ifnull(pi.fctpaysum,0)>0";

        //            $sc .= " and exists (select 1 from userorgs as uo where uo.orgid=pp.ownorgid
//                                and uo.userid={$userid} and uo.active=1 and uo.acs_orgplnpays=1
//                                and now() between uo.begdt and ifnull(uo.enddt,now()) )";
        //todo: 2021-04-25 заменить это |^| ограничение на вариант с использованием usrsysrights.limsysobjid/limobjid
        $sc .= " and exists( select 1 FROM usrsysrights AS usr
                WHERE
                    usr.userid = {$userid} AND usr.sysfuncid = 410  AND usr.active = 1
                    AND now() BETWEEN usr.begdt AND IFNULL(usr.enddt, now())
                    AND IFNULL(usr.limsysobjid, 111) = 111
                    AND IFNULL(usr.limobjid, pp.ownorgid) = pp.ownorgid
                    )";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;


                if ($item == 's_ownorgid') {
                    $sc = $sc . " and pp.ownorgid = '{$val}'";

                } elseif ($item == 's_begdate') {
                    $sc = $sc . " and pp.docdate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc = $sc . " and pp.docdate <= '{$val}'";

                } elseif ($item == 's_month') {
                    //$sc = $sc . " and month(pp.docdate) = '{$val}'";

                } elseif ($item == 's_quarter') {
                    //$sc = $sc . " and quarter(pp.docdate) = '{$val}'";

                } elseif ($item == 's_year') {
                    //$sc = $sc . " and year(pp.docdate) = '{$val}'";

                }
            }
        }


        if ($need_search) {

            $recs = orgplnpay_item::
            from('orgplnpay_items as pi')
                ->join('orgplnpays as pp', 'pp.id', 'pi.docid')
                ->join('orgs as oo', 'oo.id', 'pp.ownorgid')
                ->join('orgs as o', 'o.id', 'pi.orgid')
                ->leftjoin('pay_categories as pc', 'pc.id', 'pi.categoryid')
                ->whereRaw($sc);

            $recs = $recs->select(
                'pp.docdate', 'o.name'
                , 'pi.fctpaysum'
                , db::raw("pi.plnpaysum-ifnull(pi.fctpaysum,0) as plnpaysum")
                , 'pi.src_sysobjid', 'pi.src_objid', 'pi.reason'
                , 'pp.ownorgid'
                , 'oo.name as ownorgname'
                , 'pi.orgid'
                , 'o.name as orgname'
                , 'pc.name as category_name'
            )
                ->orderby('pp.docdate', 'asc')
                ->orderby('pi.plnpaysum', 'desc')
                ->get();
            //dd($recs);

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
                return orgplnpay::selectRaw("year(docdate) as year")->distinct()->orderby('year')
                    ->get()->pluck('year', 'year')->toArray();
            });


        $data->ownorgs = org::lstFor_cached([
            'in_orgplnpays' => 1,
            //'in_userorgs_with_acs_orgplnpays' => $userid,
            'user_has_right_for_org' => 410,
        ]);

        $data->categories = pay_category::lstFor_cached([
            'in_orgplnpay_items' => 1,
        ]);

        return view('orgplnpays.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }


    public function rep43_excel(Request $request)
    {
        //План платежей экспорт в Эксель
        // наследуем условияя поиска из ранее сделанного запроса в rep43

        $report_id = 43;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с платежами для этой организации!']);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => ''
            , 's_begdate' => ''
            , 's_enddate' => ''
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $need_search = false;
        $sc = "1=1";

        //по не полностью оплаченным строкам
        //$sc .= " and pi.plnpaysum-ifnull(pi.fctpaysum,0)>0";

        //            $sc .= " and exists (select 1 from userorgs as uo where uo.orgid=pp.ownorgid
//                                and uo.userid={$userid} and uo.active=1 and uo.acs_orgplnpays=1
//                                and now() between uo.begdt and ifnull(uo.enddt,now()) )";
        //todo: 2021-04-25 заменить это |^| ограничение на вариант с использованием usrsysrights.limsysobjid/limobjid
        $sc .= " and exists( select 1 FROM usrsysrights AS usr
                WHERE
                    usr.userid = {$userid} AND usr.sysfuncid = 410  AND usr.active = 1
                    AND now() BETWEEN usr.begdt AND IFNULL(usr.enddt, now())
                    AND IFNULL(usr.limsysobjid, 111) = 111
                    AND IFNULL(usr.limobjid, pp.ownorgid) = pp.ownorgid
                    )";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;


                if ($item == 's_ownorgid') {
                    $sc = $sc . " and pp.ownorgid = '{$val}'";

                } elseif ($item == 's_begdate') {
                    $sc = $sc . " and pp.docdate >= '{$val}'";

                } elseif ($item == 's_enddate') {
                    $sc = $sc . " and pp.docdate <= '{$val}'";
                }
            }
        }


        if ($need_search) {

            $recs = orgplnpay_item::
            from('orgplnpay_items as pi')
                ->join('orgplnpays as pp', 'pp.id', 'pi.docid')
                ->join('orgs as oo', 'oo.id', 'pp.ownorgid')
                ->join('orgs as o', 'o.id', 'pi.orgid')
                ->leftjoin('pay_categories as pc', 'pc.id', 'pi.categoryid')
                ->whereRaw($sc);

            $recs = $recs->select(
                'pp.docdate', 'o.name'
                , 'pi.fctpaysum'
                , db::raw("pi.plnpaysum-ifnull(pi.fctpaysum,0) as plnpaysum")
                , 'pi.src_sysobjid', 'pi.src_objid', 'pi.reason'
                , 'pp.ownorgid'
                , 'oo.name as ownorgname'
                , 'pi.orgid'
                , 'o.name as orgname'
                , 'pc.name as category_name'
            )
                ->orderby('pp.docdate', 'asc')
                ->orderby('pi.plnpaysum', 'desc')
                ->get();

            $d0 = date_create($search_params['s_begdate'])->format('Ymd');
            $d1 = date_create($search_params['s_enddate'])->format('Ymd');

            $response = Excel::download(new PayPlanExport($recs), "payCalendar_{$d0}-{$d1}.xlsx", \Maatwebsite\Excel\Excel::XLSX);

            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
            //HERE IS THE MAGIC FOLKS
            ob_end_clean();

            return $response;

            //dd($recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        }
        return;
    }


}
