<?php

namespace App\Http\Controllers;

use App\buildobj;
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
use App\useddocnum;
use App\usrsysright;
use http\Env\Response;
use Illuminate\Http\Request;
use DB;
use DateTime;
use App\Events\notifyEvent;
use Illuminate\Support\Facades\Auth;

class MchnReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;  //reports
        //$this->objcode = 'reports';
        $this->objcode = 'mchnrqsts';
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

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {

        return view('mchnrqsts.index', compact(
            'recs', 'rec0'
            , 'objgroups', 'usedflags', 'statuses'
            , 'machinesinrqsts'
            , 'search_params', 'sort_params'
            , 'usrrights'));
    }

    public function printform1(Request $request, $id)
    {
        $rec = mchnrqst::from('mchnrqsts as mr')
            ->select('mr.*'
                , DB::raw('timediff(mr.plnenddt, mr.plnbegdt) as duration')
            )->where('id', $id)->first();

        if (isset($rec->rqstmachineid)) {
            $machine = $rec->rqstmachine;
            $rec->mcnhinfo = $machine->name;
            if (isset($machine->regnum))
                $rec->mcnhinfo .= ', гос.№ ' . $machine->regnum;
        } else {
            $rec->mcnhinfo = $rec->rqstmchntype->name;
            if (isset($rec->mchnrequirements))
                $rec->mcnhinfo .= ' (' . $rec->mchnrequirements . ')';
        }

        if (isset($rec->contractid))
            $rec->contractinfo = '№' . $rec->contract->docnum . ' от ' . date_format(date_create($rec->contract->docdate), "d.m.Y");
        else
            $rec->contractinfo = null;

        $rec->duration = mb_substr($rec->duration, 0, 5); //только часы и минуты

        $rec->respinfo = $rec->contactname . ', тел. ' . $rec->contactphone;

        return view('mchnrqsts.printform1', compact('rec'));

    }


    public function print_esm_7(Request $request, $id)
    {
        $rec = mchnrqst::from('mchnrqsts as mr')
            ->select('mr.*'
                , DB::raw('timediff(mr.plnenddt, mr.plnbegdt) as duration')
            )->where('id', $id)->first();

        $machine = $rec->asgnmachine;
        $rec->mcnhinfo = $machine->name;
        if (isset($machine->regnum))
            $rec->mcnhinfo .= ', гос.№ ' . $machine->regnum;

        if (isset($rec->contractid))
            $rec->contractinfo = '№' . $rec->contract->docnum . ' от ' . date_format(date_create($rec->contract->docdate), "d.m.Y");
        else
            $rec->contractinfo = null;

        $rec->duration = mb_substr($rec->duration, 0, 5); //только часы и минуты

        $rec->respinfo = $rec->contactname . ', тел. ' . $rec->contactphone;

        $ownorg = org::find($machine->orgid);
        $org = org::find($rec->orgid);

        return view('mchnrqsts.print_esm_7', compact('rec', 'ownorg', 'org'));

    }

    public function print_transp_nakl(Request $request, $id)
    {
        $rec = mchnrqst::from('mchnrqsts as mr')
            ->select('mr.*'
                , DB::raw('timediff(mr.plnenddt, mr.plnbegdt) as duration')
            )->where('id', $id)->first();

        if (!isset($rec))
            return redirect(route($this->objcode . '.index'));

        $machine = $rec->asgnmachine;
        $rec->mcnhinfo = $machine->name;
        if (isset($machine->regnum))
            $rec->mcnhinfo .= ', гос.№ ' . $machine->regnum;

        if (isset($rec->contractid))
            $rec->contractinfo = '№' . $rec->contract->docnum . ' от ' . date_format(date_create($rec->contract->docdate), "d.m.Y");
        else
            $rec->contractinfo = null;

        $rec->duration = mb_substr($rec->duration, 0, 5); //только часы и минуты

        $rec->respinfo = $rec->contactname . ', тел. ' . $rec->contactphone;

        $ownorg = org::find($machine->orgid);
        $org = org::find($rec->orgid);
        $rec->srcorg = $org; //грузоотправитель
        $rec->tgtorg = $org; //грузополучатель
        $rec->carrorg = $ownorg; //грузополучатель

        return view('mchnrqsts.print_transp_nakl', compact('rec', 'ownorg'));

    }


    public function rep01(Request $request)
    {//Отчетная форма по работе спец-техники за период


        $userid = \Auth::user()->id;

        $usrrights = [];
        $usrrights['set_paytype'] = usrsysright::isUserHasRightByCode_cached($userid, 'mchnrqsts.set_paytype');
//        $usrrights['set_paytype'] = false;
        //dd($usrrights['set_paytype']??false);

        // - параметры поиска -------------------------------------------------
        $search_setname = "mchnrqsts.rep01";
        $s_ownorgid = "";
        $s_orgid = "";          //Арендатор
        $s_buildobjid = "";
        $s_begdate = "";
        $s_enddate = "";
        $s_paytypeid = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");
            $s_buildobjid = $request->get("s_buildobjid");
            $s_paytypeid = $request->get("s_paytypeid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
                's_buildobjid' => $s_buildobjid,
                's_paytypeid' => $s_paytypeid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                    $s_buildobjid = $params['s_buildobjid'] ?? '';
                    $s_paytypeid = $params['s_paytypeid'] ?? '';
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //dd($s_begdate);
                    $d = new DateTime('last day of previous month');
                    $s_enddate = $d->format('Y-m-d');
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);

                $s_begdate = now()->format('d.m.Y');
            }
        }

        $search_params = [
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            "s_ownorgid" => $s_ownorgid,
            "s_orgid" => $s_orgid,
            "s_buildobjid" => $s_buildobjid,
            "s_paytypeid" => $s_paytypeid,
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

        $sc = " 1=1 ";
        //Пользователь без права указания типа оплаты видит только заявки на безнал (paytypeid=1)
        if (!$usrrights['set_paytype'] ?? false)
            $sc .= ' and mr.paytypeid=1';

        if ($needSearch) {
            if (strlen($s_paytypeid) > 0) {
                $sc .= " and mr.paytypeid=" . $s_paytypeid;
            }
            if (strlen($s_orgid) > 0) {
                $sc .= " and mr.orgid=" . $s_orgid;
            }
            if (strlen($s_buildobjid) > 0) {
                $sc .= " and mr.buildobjid=" . $s_buildobjid;
            }
//            if (strlen($s_ownorgid) > 0) {
//                $sc .= " and mr.ownorgid=" . $s_ownorgid;
//            }
//            if (strlen($s_begdate) > 0) {
//                $sc .= " and fctbegdt between '" . $s_begdate . "' and '" . $s_enddate . "'";
//            }

//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        //dd($s_paytypeid,$sc);

        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = mchnrqst::from('mchnrqsts as mr')
                ->join('machines as m', function ($j) use ($s_ownorgid) {
                    $j->on('m.id', 'mr.asgnmachineid')
                        ->where('m.orgid', $s_ownorgid);
                })
                ->join('orgs as oo', 'oo.id', 'm.orgid')//владелец техники
                ->join('orgs as o', 'o.id', 'mr.orgid')//арендатор
                ->where('mr.orgid', $s_orgid)
                ->where('mr.offbalance', 0)//только официальные документы(заявки)
                ->select('mr.*', 'm.name as mchnname', 'mr.fct_price as price', 'mr.fct_sum')
                ->wherebetween('mr.fctbegdt', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59'])
                ->whereraw($sc)
                ->orderby('mr.fctbegdt')
                ->orderby('mr.id')
                //->with('saleuser')
                //->toSql();
                ->get();

            // dd($recs);
            //обновим счетчик использования отчета
            report::updUseCnt(1);

            //
            objlog::log_info($this->sysobjid, 1, 'запрошен: ownorgid=' . $s_ownorgid . '; ' . $sc);
        } else {
            $recs = null;
        }

        //организации- владельцы техники
        $ownorgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from machines as m where m.orgid=o.id)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($ownorgs);

        //если только одна компаия - сразу выберем
        if (count($ownorgs) == 1)
            foreach ($ownorgs as $key => $value)
                $search_params["s_ownorgid"] = $key;


        //организации-пользователи техники
        $orgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from mchnrqsts as mr where mr.orgid=o.id and mr.decision=1)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($orgs);

        $buildobjs = buildobj::lstActive();

        $ownorgname = '';
        if (isset($s_ownorgid))
            $ownorgname = org::select('name')->find($s_ownorgid)->name ?? '';

        if (isset($s_orgid))
            $orgname = org::select('name')->find($s_orgid)->name ?? '';

        $paytypes = ['1' => 'Б/нал', '2' => 'Нал'];
        //dd($usrrights);

        return view('mchnrqsts.rep01', compact('recs', 'ownorgs', 'orgs', 'buildobjs'
            , 'paytypes'
            , 'search_params', 'ownorgname', 'orgname', 'usrrights'));
    }

    public function rep02_esm3(Request $request)
    {//Рапорт о работе строительной машины (механизма). Форма №ЭСМ-3


        // - параметры поиска -------------------------------------------------
        $search_setname = "mchnrqsts.rep02";
        $s_ownorgid = "";
        $s_orgid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //dd($s_begdate);
                    $d = new DateTime('last day of previous month');
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
        ];

        //Если параметры поиска не заданы, то уйдем на index

        $needSearch = false;
        foreach ($search_params as $p) {
            if ($p <> "") {
                $needSearch = true;
                break;
            }
        }

        //Отчет работает только с заявками за безнал (paytypeid=1)
        $sc = " mr.paytypeid=1";

        if ($needSearch) {
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and mr.ownorgid=" . $s_ownorgid;
            }
            if (strlen($s_orgid) > 0) {
                $sc .= " and mr.orgid=" . $s_orgid;
            }
            if (strlen($s_begdate) > 0) {
                $sc .= " and fctbegdt between '" . $s_begdate . "' and '" . $s_enddate . "'";
            }
//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        // --------------------------------------------------------------------


        if ($needSearch) {

            $recs = mchnrqst::from('mchnrqsts as mr')
                ->join('machines as m', function ($j) use ($s_ownorgid) {
                    $j->on('m.id', 'mr.asgnmachineid')
                        ->where('m.orgid', $s_ownorgid);
                })
                ->join('orgs as oo', 'oo.id', 'm.orgid')//владелец техники
                ->join('orgs as o', 'o.id', 'mr.orgid')//арендатор
                ->Join('orgs as co', function ($j) {
                    $j->on('co.id', 'mr.car_orgid');
                })
                ->Join('contract_prices as cp', function ($j) {
                    $j->on('cp.contractid', 'mr.contractid')
                        ->where('cp.sysobjid', 482)
                        ->whereColumn('cp.objid', 'mr.asgnmachineid');
                })
                ->where('mr.orgid', $s_orgid)
                ->where('mr.offbalance', 0)//только официальные документы(заявки)
                ->select('mr.*', 'm.name as mchnname', 'cp.price'
                    , 'o.name as orgname'
                    , 'co.name as car_orgname')
                ->wherebetween('mr.fctbegdt', [$s_begdate, $s_enddate])
                ->whereraw($sc)
                ->orderby('mr.fctbegdt')
                ->orderby('mr.id')
                //->with('saleuser')
                //->toSql();
                ->get();

            //dd($recs);
            //todo: обновить счетчик использования отчета
            //
            objlog::log_info(15, 82, 'запрошен: ownorgid=' . $s_ownorgid . '; ' . $sc);
        } else {
            $recs = null;
        }

        //организации- владельцы техники
        $ownorgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from machines as m where m.orgid=o.id)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($ownorgs);

        //если только одна компаия - сразу выберем
        if (count($ownorgs) == 1)
            foreach ($ownorgs as $key => $value)
                $search_params["s_ownorgid"] = $key;


        //организации-пользователи техники
        $orgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from mchnrqsts as mr where mr.orgid=o.id and mr.decision=1)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($orgs);

        $ownorgname = '';
        if (isset($s_ownorgid))
            $ownorgname = org::select('name')->find($s_ownorgid)->name ?? '';

        if (isset($s_orgid))
            $orgname = org::select('name')->find($s_orgid)->name ?? '';

        return view('mchnrqsts.rep02_esm3', compact('recs', 'ownorgs', 'orgs', 'search_params', 'ownorgname', 'orgname'));
    }

    public function rep03(Request $request)
    {//Сводный отчет по работе спец-техники за период


        $userid = \Auth::user()->id;

        $usrrights = [];
        $usrrights['set_paytype'] = usrsysright::isUserHasRightByCode_cached($userid, 'mchnrqsts.set_paytype');
        //dd($usrrights);

        // - параметры поиска -------------------------------------------------
        $search_setname = "mchnrqsts.rep03";
        $s_ownorgid = "";
        $s_orgid = "";
        $s_begdate = "";
        $s_enddate = "";
        $s_paytypeid = "";


        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");
            $s_paytypeid = $request->get("s_paytypeid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
                's_paytypeid' => $s_paytypeid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                    $s_paytypeid = $params['s_paytypeid'];
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //dd($s_begdate);
                    $d = new DateTime('last day of previous month');
                    $s_enddate = $d->format('Y-m-d');
                    $s_paytypeid = 1;
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
            "s_paytypeid" => $s_paytypeid,
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

        $sc = " 1=1 ";
        //Пользователь без права указания типа оплаты видит только заявки на безнал (paytypeid=1)
        if (!$usrrights['set_paytype'] ?? false)
            $sc .= ' and mr.paytypeid=1';

        if ($needSearch) {
            if (strlen($s_paytypeid) > 0) {
                $sc .= " and mr.paytypeid=" . $s_paytypeid;
            }

            if (strlen($s_ownorgid) > 0) {
                $sc .= " and mr.ownorgid=" . $s_ownorgid;
            }
//            if (strlen($s_begdate) > 0) {
//                $sc .= " and fctbegdt between '" . $s_begdate . "' and '" . $s_enddate . "'";
//            }

//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

//            SELECT DATE_FORMAT(fctbegdt, "%Y-%m-01") as period, mr.orgid, m.orgid
//            , sum(fct_sum) as fct_sum
//            , max(o2.name) as carorgname
//            , max(o1.name) as initorgname
//            FROM `mchnrqsts` as mr
//            join orgs as o1 on o1.id=mr.orgid
//            join machines as m on m.id=mr.asgnmachineid
//            join orgs as o2 on o2.id=m.orgid
//            WHERE fct_at is not null
//                and fctbegdt >='2020-04-01'
//            group by period,mr.orgid,m.orgid
//            order by period, initorgname

            $recs = mchnrqst::from('mchnrqsts as mr')
                ->join('machines as m', function ($j) use ($s_ownorgid) {
                    $j->on('m.id', 'mr.asgnmachineid');
                    if (isset($s_ownorgid))
                        $j = $j->where('m.orgid', $s_ownorgid);
                })
                ->join('orgs as oo', 'oo.id', 'm.orgid')//владелец техники
                ->join('orgs as o', 'o.id', 'mr.orgid')//арендатор

                ->wherebetween('mr.fctbegdt', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59'])
                ->where('mr.offbalance', 0)//только официальные документы(заявки)
                ->whereRaw($sc)
                ->select(
                    DB::raw('DATE_FORMAT(fctbegdt, "%Y-%m-01") as perbegdate')
                    , 'mr.orgid as initorgid', 'm.orgid as carorgid'
                    , DB::raw('count(mr.id) as rqst_cnt')
                    , DB::raw('sum(fct_qty) as qty_sum')
                    , DB::raw('avg(fct_qty) as qty_avg')
                    , DB::raw('sum(fct_sum) as fct_sum')
                    , DB::raw('sum(m.fuelper1hour*fct_qty) as fuel_qty')
                    , DB::raw('max(oo.name) as carorgname')
                    , DB::raw('max(o.name) as initorgname')
                )
                ->groupBy('perbegdate')
                ->groupby('mr.orgid')
                ->groupby('m.orgid')
                ->orderby('carorgname')
                ->orderby('perbegdate')
                ->orderby('initorgname')
                ->get();
//            dd($recs);

            //обновим счетчик использования отчета
            //report::where('id', 1)->increment('use_cnt', 1);
            report::where('id', 1)->update([

                'use_cnt' => DB::raw('use_cnt + 1'),
                'lastuse_dt' => now(),
                'lastuse_userid' => \Auth::user()->id,
                'lastuse_username' => \Auth::user()->name,
            ]);

            //занесем в журнал
            objlog::log_info(855, 3, 'запрошен: ownorgid=' . $s_ownorgid . '; ' . $sc);
        } else {
            $recs = null;
        }

        //организации- владельцы техники
        $ownorgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from machines as m where m.orgid=o.id)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($ownorgs);

        //если только одна компаия - сразу выберем
        if (count($ownorgs) == 1)
            foreach ($ownorgs as $key => $value)
                $search_params["s_ownorgid"] = $key;


        $ownorgname = '';
        if (isset($s_ownorgid))
            $ownorgname = org::select('name')->find($s_ownorgid)->name ?? '';

        $data = new \stdClass();
        $data->paytypes = ['1' => 'Б/нал', '2' => 'Нал'];

        return view('mchnrqsts.rep03', compact('recs', 'ownorgs', 'usrrights', 'data', 'search_params', 'ownorgname'));
    }

    public function rep06(Request $request)
    {//График занятости техники

        // - параметры поиска -------------------------------------------------
        $search_setname = "mchnrqsts.rep03";
        $s_ownorgid = "";
        $s_orgid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //dd($s_begdate);
                    $d = new DateTime('last day of previous month');
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

        if ($needSearch) {
            $sc = " 1=1 ";
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and mr.ownorgid=" . $s_ownorgid;
            }
            if (strlen($s_begdate) > 0) {
                $sc .= " and fctbegdt between '" . $s_begdate . "' and '" . $s_enddate . "'";
            }
//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = mchnrqst::from('mchnrqsts as mr')
                ->join('machines as m', function ($j) use ($s_ownorgid) {
                    $j->on('m.id', 'mr.asgnmachineid');
                    if (isset($s_ownorgid))
                        $j = $j->where('m.orgid', $s_ownorgid);
                })
                ->join('orgs as dro', 'dro.id', 'mr.driver_orgid')//ИП
                //->join('orgs as o', 'o.id', 'mr.orgid')//арендатор

                ->wherebetween('mr.fctbegdt', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59'])
                ->where('mr.offbalance', 0)//только официальные документы(заявки)
                ->select('mr.id', 'mr.asgnmachineid', 'fctbegdt', 'fctenddt'
                    , 'm.name as machinename', 'mr.driver_orgid', 'dro.name as driver_orgname', 'mr.drivername'
                )
                ->orderby('mr.id')
                ->orderby('fctbegdt')
                ->get();
            //dd($recs);

            //обновим счетчик использования отчета
            report::updUseCnt(6);

            //занесем в журнал
            objlog::log_info(855, 6, 'запрошен: ownorgid=' . $s_ownorgid . '; ' . $sc);
        } else {
            $recs = null;
        }

        //организации- владельцы техники
        $ownorgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from machines as m where m.orgid=o.id)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($ownorgs);

        //если только одна компаия - сразу выберем
        if (count($ownorgs) == 1)
            foreach ($ownorgs as $key => $value)
                $search_params["s_ownorgid"] = $key;


        $ownorgname = '';
        if (isset($s_ownorgid))
            $ownorgname = org::select('name')->find($s_ownorgid)->name ?? '';

        return view('mchnrqsts.rep06', compact('recs', 'ownorgs', 'search_params', 'ownorgname'));
    }

    public function rep07(Request $request)
    {//Сводный отчет по работе ИП за период


        // - параметры поиска -------------------------------------------------
        $search_setname = "mchnrqsts.rep01";
        $s_ownorgid = "";
        $s_orgid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //dd($s_begdate);
                    $d = new DateTime('last day of previous month');
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

        if ($needSearch) {
            $sc = " 1=1 ";
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and mr.ownorgid=" . $s_ownorgid;
            }
            if (strlen($s_orgid) > 0) {
                $sc .= " and mr.driver_orgid=" . $s_orgid;
            }
            if (strlen($s_begdate) > 0) {
                $sc .= " and fctbegdt between '" . $s_begdate . "' and '" . $s_enddate . "'";
            }
//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = mchnrqst::from('mchnrqsts as mr')
                ->join('machines as m', function ($j) use ($s_ownorgid) {
                    $j->on('m.id', 'mr.asgnmachineid')
                        ->where('m.orgid', $s_ownorgid);
                })
                ->join('orgs as oo', 'oo.id', 'm.orgid')//владелец техники
                ->join('orgs as od', 'od.id', 'mr.driver_orgid');//ИП
            if (strlen($s_orgid) > 0) {
                $recs = $recs->where("mr.driver_orgid", $s_orgid);
            }
            $recs = $recs->where('mr.offbalance', 0)//только официальные документы(заявки)
            ->select('mr.asgnmachineid', 'm.name as mchnname', 'mr.driver_orgid'
                , 'od.name as driver_orgname'
                , DB::raw("sum(mr.fct_qty) as fct_qty")
                , DB::raw("sum(mr.driver_sum) as driver_sum")
                , DB::raw("sum(m.fuelper1hour*mr.fct_qty) as fuel_qty")
            )
                ->wherebetween('mr.fctbegdt', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59'])
                ->groupby('mr.asgnmachineid')
                ->groupby('mr.driver_orgid')
                ->orderby('od.name')
                ->orderby('mr.driver_orgid')
                ->orderby('m.name')
                ->get();

            //dd($recs);
            //обновим счетчик использования отчета
            report::updUseCnt(7);

            //
            objlog::log_info($this->sysobjid, 7, 'запрошен: ownorgid=' . $s_ownorgid . '; ' . $sc);
        } else {
            $recs = null;
        }

        //организации- владельцы техники
        $ownorgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from machines as m where m.orgid=o.id)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($ownorgs);

        //если только одна компаия - сразу выберем
        if (count($ownorgs) == 1)
            foreach ($ownorgs as $key => $value)
                $search_params["s_ownorgid"] = $key;


        //организации-пользователи техники ИП
        $orgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from mchnrqsts as mr where mr.driver_orgid=o.id and mr.decision=1)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($orgs);

        $ownorgname = '';
        if (isset($s_ownorgid))
            $ownorgname = org::select('name')->find($s_ownorgid)->name ?? ' ';

        $orgname = '';
        if (isset($s_orgid))
            $orgname = org::select('name')->find($s_orgid)->name ?? '';

        return view('mchnrqsts.rep07', compact('recs', 'ownorgs', 'orgs', 'search_params', 'ownorgname', 'orgname'));
    }


    public function rep08(Request $request)
    {//

        $reportid = 8;

        // - параметры поиска -------------------------------------------------
        $search_setname = "mchnrqsts.rep_" . $reportid;
        $s_ownorgid = "";
        $s_orgid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //dd($s_begdate);
                    $d = new DateTime('last day of previous month');
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

        if ($needSearch) {
            $sc = " 1=1 ";
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and mr.ownorgid=" . $s_ownorgid;
            }
            if (strlen($s_begdate) > 0) {
                $sc .= " and fctbegdt between '" . $s_begdate . "' and '" . $s_enddate . "'";
            }
//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = mchnrqst::from('mchnrqsts as mr')
                ->join('machines as m', function ($j) use ($s_ownorgid) {
                    $j->on('m.id', 'mr.asgnmachineid');
                    if (isset($s_ownorgid))
                        $j = $j->where('m.orgid', $s_ownorgid);
                })
                ->join('orgs as dro', 'dro.id', 'mr.driver_orgid')//ИП
                //->join('orgs as o', 'o.id', 'mr.orgid')//арендатор

                ->wherebetween('mr.fctbegdt', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59'])
                ->where('mr.offbalance', 0)//только официальные документы(заявки)
                ->select('mr.id', 'mr.asgnmachineid', 'fctbegdt', 'fctenddt'
                    , 'm.name as machinename', 'mr.driver_orgid', 'dro.name as driver_orgname', 'mr.drivername'
                )
                ->orderby('dro.name')
                ->orderby('mr.driver_orgid')
                ->orderby('fctbegdt')
                ->get();
            //dd($recs);

            //обновим счетчик использования отчета
            report::updUseCnt($reportid);

            //занесем в журнал
            objlog::log_info(855, $reportid, 'запрошен: ownorgid=' . $s_ownorgid . '; ' . $sc);
        } else {
            $recs = null;
        }

        //организации- владельцы техники
        $ownorgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from machines as m where m.orgid=o.id)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($ownorgs);

        //если только одна компаия - сразу выберем
        if (count($ownorgs) == 1)
            foreach ($ownorgs as $key => $value)
                $search_params["s_ownorgid"] = $key;


        $ownorgname = '';
        if (isset($s_ownorgid))
            $ownorgname = org::select('name')->find($s_ownorgid)->name ?? '';

        return view('mchnrqsts.rep08', compact('recs', 'ownorgs', 'search_params', 'ownorgname'));
    }


    public function rep09(Request $request)
    {//Акты вып. работ по договорам с паеревозсиками грузов


        $userid = \Auth::user()->id;

        // - параметры поиска -------------------------------------------------
        $search_setname = "mchnrqsts.rep09";
        $s_ownorgid = "";
        $s_orgid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //dd($s_begdate);
                    $d = new DateTime('last day of previous month');
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

        if ($needSearch) {
            $sc = " 1=1 ";
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and mr.ownorgid=" . $s_ownorgid;
            }
            if (strlen($s_begdate) > 0) {
                $sc .= " and fctbegdt between '" . $s_begdate . "' and '" . $s_enddate . "'";
            }
//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

//            SELECT m.orgid, mr.driver_orgid
//            , sum(fct_sum) as fct_sum
//            , max(o1.name) as orgname
//            , max(o2.name) as driverorgname
//            FROM `mchnrqsts` as mr
//            join machines as m on m.id=mr.asgnmachineid
//            join orgs as o1 on o1.id=m.orgid
//            join orgs as o2 on o2.id=mr.driver_orgid
//            WHERE fctbegdt >='2020-03-01' and fctbegdt <='2020-03-31'
//            group by m.orgid, mr.driver_orgid
//            order by orgname, driverorgname

            $recs = mchnrqst::from('mchnrqsts as mr')
                ->join('machines as m', 'm.id', 'mr.asgnmachineid')
                ->join('orgs as oo', 'oo.id', 'm.orgid')//Заказчик
                ->join('orgs as o', 'o.id', 'mr.driver_orgid')//Исполнитель
                ->join('contracts as dc', 'dc.id', 'mr.driver_contractid')//Контракт с исполнителем (ИП)
                ->leftJoin('useddocnums as udn', function ($j) use ($s_begdate, $s_enddate) {
                    $j->on('udn.contractid', 'mr.driver_contractid')
                        ->where('udn.doctypeid', 21)
                        ->where('udn.begdate', $s_begdate)
                        ->where('udn.enddate', $s_enddate);
                })
                ->wherebetween('mr.fctbegdt', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59'])
                ->where('mr.offbalance', 0)//только официальные документы(заявки)
                ->select(
                    'm.orgid', 'mr.driver_contractid', 'mr.driver_orgid'
                    , DB::raw('max(dc.id) as contractid')
                    , DB::raw('max(dc.docnum) as contractnum')
                    , DB::raw('max(dc.docdate) as contractdate')
                    , DB::raw('max(udn.docnum) as docnum')
                    , DB::raw('sum(fct_qty) as fct_qty')
                    , DB::raw('sum(driver_sum) as fct_sum')
                    , DB::raw('max(oo.name) as orgname')
                    , DB::raw('max(oo.inn) as org_inn')
                    , DB::raw('max(oo.address) as org_address')
                    , DB::raw('max(oo.boss_name) as org_boss_name')
                    , DB::raw('max(oo.boss_postname) as org_boss_postname')
                    , DB::raw('max(o.name) as driverorgname')
                    , DB::raw('max(o.inn) as driverorg_inn')
                    , DB::raw('max(o.address) as driverorg_address')
                    , DB::raw('max(o.boss_name) as driverorg_boss_name')
                    , DB::raw('max(o.boss_postname) as driverorg_boss_postname')
                )
                ->groupby('mr.driver_contractid')
                ->groupby('m.orgid')
                ->groupby('mr.driver_orgid')
                ->orderby('orgname')
                ->orderby('driverorgname')
                ->get();
            //dd($recs);

            foreach ($recs->wherenull('docnum') as $rec) {

                //попробуем найти предыдущий документ этого типа по этому контракту
                $nxtnum = useddocnum::where('contractid', $rec->contractid)
                        ->where('doctypeid', 21)
                        ->where('enddate', '<', $s_begdate)
                        ->select('int_docnum')
                        ->orderby('enddate', 'desc')
                        ->first()->int_docnum ?? 0;
                $nxtnum += 1 + rand(1, 5);
                $ttt = new useddocnum([
                    'contractid' => $rec->contractid,
                    'doctypeid' => 21,
                    'begdate' => $s_begdate,
                    'enddate' => $s_enddate,
                    'docnum' => $nxtnum,
                    'int_docnum' => (int)$nxtnum,
                ]);
                $ttt->save();
                $rec->docnum = $nxtnum;
                //dd($ttt, $recs);
            }

            //обновим счетчик использования отчета
            report::updUseCnt(9, $userid);

            //занесем в журнал
            objlog::log_info(855, 9, 'запрошен: ownorgid=' . $s_ownorgid . '; ' . $sc);
        } else {
            $recs = null;
        }

        //организации- владельцы техники
        $ownorgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from machines as m where m.orgid=o.id)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($ownorgs);

        //если только одна компаия - сразу выберем
        if (count($ownorgs) == 1)
            foreach ($ownorgs as $key => $value)
                $search_params["s_ownorgid"] = $key;

        $ownorgname = '';
        if (isset($s_ownorgid))
            $ownorgname = org::select('name')->find($s_ownorgid)->name ?? '';


        $driverorgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from mchnrqsts as mr where mr.driver_orgid=o.id)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');

        return view('mchnrqsts.rep09_act', compact('recs', 'ownorgs', 'driverorgs'
            , 'search_params', 'ownorgname'));
    }


    public function rep26(Request $request)
    {//Отчетная форма по работе спец-техники по заявкам Арендатора за период


        $userid = \Auth::user()->id;

        $usrrights = [];
        $usrrights['set_paytype'] = usrsysright::isUserHasRightByCode_cached($userid, 'mchnrqsts.set_paytype');
//        $usrrights['set_paytype'] = false;
        //dd($usrrights['set_paytype']??false);

        // - параметры поиска -------------------------------------------------
        $search_setname = "mchnrqsts.rep26";
        $s_ownorgid = "";
        $s_orgid = "";          //Арендатор
        $s_buildobjid = "";
        $s_begdate = "";
        $s_enddate = "";
        $s_paytypeid = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            //$s_ownorgid = $request->get("s_ownorgid");
            $s_orgid = $request->get("s_orgid");
            $s_buildobjid = $request->get("s_buildobjid");
            //$s_paytypeid = $request->get("s_paytypeid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                //'s_ownorgid' => $s_ownorgid,
                's_orgid' => $s_orgid,
                's_buildobjid' => $s_buildobjid,
                //'s_paytypeid' => $s_paytypeid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    //$s_ownorgid = $params['s_ownorgid'];
                    $s_orgid = $params['s_orgid'];
                    $s_buildobjid = $params['s_buildobjid'] ?? '';
                    //$s_paytypeid = $params['s_paytypeid'] ?? '';
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //dd($s_begdate);
                    $d = new DateTime('last day of previous month');
                    $s_enddate = $d->format('Y-m-d');
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);

                $s_begdate = now()->format('d.m.Y');
            }
        }

        $search_params = [
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            //"s_ownorgid" => $s_ownorgid,
            "s_orgid" => $s_orgid,
            "s_buildobjid" => $s_buildobjid,
            //"s_paytypeid" => $s_paytypeid,
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

        $sc = " 1=1 ";
        //Пользователь без права указания типа оплаты видит только заявки на безнал (paytypeid=1)
        //if (!$usrrights['set_paytype'] ?? false)
            // $sc .= ' and mr.paytypeid=1';

            if ($needSearch) {
                if (strlen($s_paytypeid) > 0) {
                    $sc .= " and mr.paytypeid=" . $s_paytypeid;
                }
                if (strlen($s_orgid) > 0) {
                    $sc .= " and mr.orgid=" . $s_orgid;
                }
                if (strlen($s_buildobjid) > 0) {
                    $sc .= " and mr.buildobjid=" . $s_buildobjid;
                }
//            if (strlen($s_ownorgid) > 0) {
//                $sc .= " and mr.ownorgid=" . $s_ownorgid;
//            }
//            if (strlen($s_begdate) > 0) {
//                $sc .= " and fctbegdt between '" . $s_begdate . "' and '" . $s_enddate . "'";
//            }

//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
            }
        //dd($s_buildobjid,strlen($s_buildobjid),$sc);

        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = mchnrqst::from('mchnrqsts as mr')
                ->leftjoin('machines as m', function ($j) use ($s_ownorgid) {
                    $j->on('m.id', 'mr.asgnmachineid');
                        //->where('m.orgid', $s_ownorgid);
                })
                //->leftjoin('orgs as oo', 'oo.id', 'm.orgid')//владелец техники
                ->join('orgs as o', 'o.id', 'mr.orgid')//арендатор
                ->where('mr.orgid', $s_orgid)
                //->where('mr.offbalance', 0)//только официальные документы(заявки)
                ->select('mr.*', 'm.name as mchnname', 'mr.fct_price as price', 'mr.fct_sum')
                ->wherebetween('mr.fctbegdt', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59'])
                ->whereraw($sc)
                ->orderby('mr.fctbegdt')
                ->orderby('mr.id')
                //->with('saleuser')
                //->toSql();
                ->get();

            // dd($recs);
            //обновим счетчик использования отчета
            report::updUseCnt(26);

            //
            objlog::log_info($this->sysobjid, 1, 'запрошен: orgid=' . $s_orgid . '; ' . $sc);
        } else {
            $recs = null;
        }


        //организации-пользователи техники
        $orgs = org::from('orgs as o')->select('id', 'name')
            ->whereRaw('exists (select 1 from mchnrqsts as mr where mr.orgid=o.id and mr.decision=1)')
            ->orderby('o.name')
            ->get()->pluck('name', 'id');
        //dd($orgs);

        $buildobjs = buildobj::lstActive();

//        $ownorgname = '';
//        if (isset($s_ownorgid))
//            $ownorgname = org::select('name')->find($s_ownorgid)->name ?? '';

        if (isset($s_orgid))
            $orgname = org::select('name')->find($s_orgid)->name ?? '';

        $paytypes = ['1' => 'Б/нал', '2' => 'Нал'];
        //dd($usrrights);

        return view('mchnrqsts.rep26', compact('recs', 'orgs', 'buildobjs'
            //, 'paytypes'
            , 'search_params', 'orgname', 'usrrights'));
    }

}
