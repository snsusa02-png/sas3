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

class OrgStaffReportController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;  //reports
        $this->objcode = 'paydocs';
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


    public function rep47(Request $request)
    {
        //

        $report_id = 47;

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
            , 's_ownorgid' => Auth::user()->curorgid
            , 's_showmode' => 1
            , 's_curatorid' => ''
            , 's_org_kind' => ''
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);


        $ownorgid = $search_params['s_ownorgid'];

        $need_search = false;
        $sc = "1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;

                if ($item == 's_ownorgid') {
                    $sc = $sc . " and o.id <> '{$val}'";

                } elseif ($item == 's_showmode') {

                    if ($val == 1) {
                        //только должники
                        $sc = $sc . " and orgSaldo_onDate(o.id, {$ownorgid}, null)<0";
                    } elseif ($val == 2) {
                        //только должники и клиенты с переплатой
                        $sc = $sc . " and orgSaldo_onDate(o.id, {$ownorgid}, null)<>0";
                    } elseif ($val == 3) {
                        //только переплата
                        $sc = $sc . " and orgSaldo_onDate(o.id, {$ownorgid}, null)>0";
                    } elseif ($val == 4) {
                        //с любым балансом
                        $sc = $sc . "";
                    }
                } elseif ($item == 's_curatorid') {
                    $sc = $sc . " and exists (select 1 from org_curators as oc
                        where oc.orgid=o.id and oc.staffid={$val}
                        and oc.active=1 and now() between oc.begdt and ifnull(oc.enddt,now()) )";

                } elseif ($item == 's_org_kind') {
                    if ($val == 1) {
                        //только поставщики
                        $sc = $sc . " and exists (select 1 from mr_opers as mro where mro.suporgid=o.id)";
                    } elseif ($val == 2) {
                        //только не поставщики
                        $sc = $sc . " and not exists (select 1 from mr_opers as mro where mro.suporgid=o.id)";
                    } elseif ($val == 9) {
                        //все
                        $sc = $sc . "";
                    }
                }
            }
        }

        $recs = null;

        if ($need_search) {

            $recs = org::from('orgs as o')
                ->whereRaw($sc);


            $sc_task = "";
            //Если у пользователя нет права в Задачах, то показывать только публичные задачи и задачи в которых он участвует
            if (!usrsysright::isUserHasRightByCode_cached($userid, 'tasks.read'))
                $sc_task = " and ( tsk.public_lvl=2 or tsk.inituserid={$userid}
                                    or exists (select 1 from task_users r where r.taskid=tsk.id and userid={$userid}) )";

            $recs = $recs->select(
                'o.id as orgid', 'o.name as orgname'
                , db::raw("orgSaldo_onDate(o.id, {$ownorgid}, null) as org_saldo")
                , db::raw("(select group_concat( trim(concat(ifnull(os.fname,''),' ', os.lname)) SEPARATOR ',')
                            from orgstaff as os
                            join org_curators as oc
                            on oc.staffid=os.id and oc.active=1 and now() between oc.begdt and ifnull(oc.enddt,now())
                            where oc.orgid=o.id
                            ) as org_curators")
                , db::raw("(select group_concat( concat(tsk.name,'|',tsk.id)  SEPARATOR ';')
                            from tasks as tsk
                            where tsk.srcsysobjid=111 and tsk.srcobjid=o.id
                            and tsk.statusid is null
                            {$sc_task}
                            ) as tasks")
            )
                ->orderby('org_saldo', 'asc')
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

        $data->ownorgs = org::lstFor_cached([
            //'in_paydocs_ownorgid' => 1,
            'flagtypeid' => 12,
        ]);

        //$data->showmodes = [1 => 'Должники', 2 => 'должники и с переплатой', 4 => 'все'];
        $data->showmodes = [1 => 'Должники', 3 => 'Переплата', 2 => 'Должники и Переплата', 4 => 'Все'];
        $data->org_kinds = [1 => 'Поставщики', 3 => 'Не поставщики', 9 => 'Все'];

//        $data->curators = User::lstFor_cached([
//            'in_org_curators_now' => 1,
//        ]);
        $data->curators = orgstaff::lstFor_cached([
            'in_org_curators_now' => 1,
        ]);

        $usrrights = [];
        $usrrights['link_tasks'] = usrsysright::isUserHasRightByCode_cached($userid, 'tasks.create');

        return view('paydocs.rep' . $report_id, compact('recs', 'search_params', 'data', 'usrrights'));
    }

    public
    function rep48(Request $request, $ownorgid, $orgid)
    {
        //Детализация баланса контрагента

        $report_id = 48;

        $returl = $request->get('returl') ?? route('home');
        $export2xls = $request->get('xls') ?? 0;

        $userid = Auth::user()->id;
        if (!(isset($ownorgid) and isset($orgid)))
            return redirect($returl)
                ->with(['error' => 'Запрос ожидал две компании!']);

        $org_saldo = org_saldo::from('org_saldos as s')
            ->where(['ownorgid' => $ownorgid, 'orgid' => $orgid])
            ->first();

        $mindate = $org_saldo->ondate ?? null;

        //соберем все операции и платежи начиная с $mindate
        $sc1 = $sc2 = "1=1";
        if (isset($mindate)) {
            $sc1 .= " and mr.wrkdate>='{$mindate}'";
            $sc2 .= " and pd.paydate>='{$mindate}'";
        }

        if (1 == 0) {
            $wrks = mchn_raid::from('mchn_raids as mr')
                ->join('refitems as l_ri', 'l_ri.id', 'mr.load_refitmid')
                ->where(['mr.load_ownorgid' => $ownorgid, 'mr.orgid' => $orgid, 'mr.active' => 1])
                ->whereRaw($sc1)
                ->select('mr.wrkdate as operdate'
                    , db::raw("1106 as sysobjid")
                    , 'mr.id as objid'
                    , db::raw("concat(l_ri.name,', ',l_ri.unit) as itmname")
                    , 'mr.unload_price as itmprice'
                    , 'mr.unload_qty as itmqty'
                    , db::raw("-unload_sum as itmsum")
                );
            $recs = paydoc::from('paydocs as pd')
                ->where(['pd.ownorgid' => $ownorgid, 'pd.orgid' => $orgid, 'pd.active' => 1])
                ->whereRaw($sc2)
                ->select('pd.paydate as operdate'
                    , db::raw("520 as sysobjid")
                    , 'pd.id as objid'
                    , db::raw("concat('оплата (',ifnull(pd.reason,''),')') as itmname")
                    , db::raw("null as itmprice")
                    , db::raw("null as itmqty")
                    , db::raw("pd.paydir*pd.paysum as itmsum")
                )
                ->union($wrks)
                ->orderBy('operdate')
                ->get();
        }


        $sc = "{$ownorgid} in (fo.srcorgid, fo.tgtorgid) and {$orgid} in (fo.srcorgid, fo.tgtorgid)";
        if (isset($mindate))
            $sc .= " and fo.operdate>='{$mindate}'";

        $recs = obj_finoper::from('obj_finopers as fo')
            ->leftJoin('mr_opers as mro', function ($j) {
                $j->on('mro.id', 'fo.objid')
                    ->where('fo.sysobjid', 1107);
            })
            // 2023-01-31 Добавим название авто
            ->leftJoin('mchn_raids as mr', function ($j) {
                $j->on('mr.id', 'mro.mr_id');
            })
            ->leftJoin('machines as m', function ($j) {
                $j->on('m.id', 'mr.machineid');
            })
            ->whereRaw($sc)
            ->orderBy('operdate')
//            ->select('fo.*'
//                , db::raw("if(srcorgid = {$ownorgid}, - 1, + 1) * opersum as opersum")
            ->select('sysobjid', 'fo.objid', 'sumtypeid', 'operdate', 'fo.price', 'fo.descript', 'mro.org_placename'
                , db::raw("sum(fo.qty) as qty")
                , db::raw("sum( if(srcorgid = {$ownorgid}, - 1, + 1) * opersum) as opersum")
                , db::raw("trim(group_concat(mro.name separator ' ')) as notes")
                , db::raw("trim(group_concat(m.regnum separator ' ')) as mchn_regnums")
                , db::raw("sum(mro.raid_qty) as raid_qty")
            )
            ->groupBy(['sysobjid', 'fo.objid', 'sumtypeid', 'operdate', 'price', 'descript', 'mro.org_placename'])
            ->get();

        $data = new \stdClass();
        $data->returl = $returl;
        $data->ownorg = org::find($ownorgid);
        $data->org = org::find($orgid);
        $data->org_saldo = $org_saldo;
//        dd($data);

        //обновим счетчик использования отчета
        report::updUseCnt($report_id, $userid, \Auth::user()->name);

        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен отчет; ' . $ownorgid . '/' . $orgid);

        if ($export2xls == "1") {
            $response = Excel::download(new PayPlanExport($recs, $data), "saldo_details.xlsx", \Maatwebsite\Excel\Excel::XLSX);

            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }

        return view('paydocs.rep' . $report_id, compact('recs', 'data'));
    }


    public
    function informer49(Request $request)
    {
        //Сводка контрагентов с ненулевым балансом по всем организациям ГК

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с платежами для этой организации!']);

        $ownorgs = org::getFor(['flagtypeid' => 12], ['id', 'name'], [['name', 'asc']]);
        //dd($ownorgs);
        foreach ($ownorgs as $ownorg) {
            $ownorgid = $ownorg->id;

            $sc = "1=1";
            $sc .= " and o.id <> '$ownorgid'";

            //только должники и клиенты с переплатой
            $sc = $sc . " and orgSaldo_onDate(o.id, {$ownorgid}, null)<>0";


            $recs = org::from('orgs as o')
                ->whereRaw($sc);

            $recs = $recs->select(
                'o.id as orgid', 'o.name as orgname'
                , db::raw("orgSaldo_onDate(o.id, {$ownorgid}, null) as org_saldo")
                , db::raw("(select group_concat( trim(concat(ifnull(u.fname,''),' ', u.lname)) SEPARATOR ',')
                            from orgstaff as u join org_curators as oc
                            on oc.staffid=u.id and oc.active=1
                                and now() between oc.begdt and ifnull(oc.enddt,now())
                            where oc.orgid=o.id
                            ) as org_curators")

            )
                ->orderby('org_saldo', 'asc')
                ->get();

            $ownorg->recs = $recs;
        }

        return view('paydocs.informer49', compact('ownorgs'));
    }

    function rep53(Request $request, $ownorgid, $orgid)
    {
        //Детализация баланса контрагента по данным mr_opers и paydocs

        $report_id = 53;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        if (!(isset($ownorgid) and isset($orgid)))
            return redirect($returl)
                ->with(['error' => 'Запрос ожидал две компании!']);

        $org_saldo = org_saldo::from('org_saldos as s')
            ->where(['ownorgid' => $ownorgid, 'orgid' => $orgid])
            ->first();

        $mindate = $org_saldo->ondate ?? null;

        //соберем все операции и платежи начиная с $mindate
        $sc1 = $sc2 = $sc3 = "1=1";
        if (isset($mindate)) {
            $sc1 .= " and mr.wrkdate>='{$mindate}'";
            $sc2 .= " and pd.paydate>='{$mindate}'";
            $sc3 .= " and d.docdate>='{$mindate}'";
        }

        if (1 == 1) {
            $sells = mr_oper::from('mr_opers as mro')
                ->join('mchn_raids as mr', 'mr.id', 'mro.mr_id')
                ->join('refitems as ri', 'ri.id', 'mro.refitmid')
                ->where(['mro.suporgid' => $ownorgid, 'mro.orgid' => $orgid])
                ->whereRaw($sc1)
                ->select('mr.wrkdate as operdate', db::raw('2 as sumtypeid')
                    , db::raw("1106 as sysobjid")
                    , db::raw('max(mro.org_placename) as org_placename')
                    , db::raw("concat(ri.name,', ',ri.unit) as descript")
                    , 'mro.itm_price'
                    , db::raw("sum(mro.itm_qty) as qty")
                    , db::raw("sum(-mro.itm_sum) as opersum")
                    , db::raw("trim(group_concat( mro.name SEPARATOR ' ')) as notes")
                    , db::raw("sum(mro.raid_qty) as raid_qty")
                )
                ->groupBy('operdate', 'sysobjid', 'org_placename', 'mro.refitmid', 'mro.itm_price');

            $buys = mr_oper::from('mr_opers as mro')
                ->join('mchn_raids as mr', 'mr.id', 'mro.mr_id')
                ->join('refitems as ri', 'ri.id', 'mro.refitmid')
                ->where(['mro.suporgid' => $orgid, 'mro.orgid' => $ownorgid])
                ->whereRaw($sc1)
                ->select('mr.wrkdate as operdate', db::raw('2 as sumtypeid')
                    , db::raw("1106 as sysobjid")
                    , db::raw('max(mro.sup_placename) as org_placename')
                    , db::raw("concat(ri.name,', ',ri.unit) as descript")
                    , 'mro.itm_price'
                    , db::raw("sum(mro.itm_qty) as qty")
                    , db::raw("sum(+mro.itm_sum) as opersum")
                    , db::raw("trim(group_concat( mro.name SEPARATOR ' ')) as notes")
                    , db::raw("sum(mro.raid_qty) as raid_qty")
                )
                ->groupBy('operdate', 'sysobjid', 'org_placename', 'mro.refitmid', 'mro.itm_price');

            $wrh_sells = wrhdoc::from('wrhdocs as d')
                ->join('wrhdoctypes as dt', 'dt.id', 'd.doctypeid')
                ->join('wrhs as w', 'w.id', 'd.wrhid')
                ->join('wrhdoclst as i', 'i.docid', 'd.id')
                ->join('refitems as ri', 'ri.id', 'i.refitmid')
                ->where('dt.forsale', '<>', 0)
                ->whereRaw("ifnull(d.saleorgid, d.ownorgid) = {$ownorgid}")
                ->where(['d.orgid' => $orgid])
                ->whereRaw($sc3)
                ->select('d.docdate as operdate', db::raw('2 as sumtypeid')
                    , db::raw("204 as sysobjid")
                    , db::raw('w.name as org_placename')
                    , db::raw("concat('отгрузка ', ri.name, ', ', ri.unit) as descript")
                    , 'i.price as itm_price'
                    , db::raw("sum(i.qty) as qty")
                    , db::raw("sum(-dt.forsale*i.qty*i.price ) as opersum")
                    , db::raw("null as notes")
                    , db::raw("null as raid_qty")
                )
                ->groupBy('operdate', 'sysobjid', 'org_placename', 'i.refitmid', 'i.price');

            $recs = paydoc::from('paydocs as pd')
                ->where(['pd.ownorgid' => $ownorgid, 'pd.orgid' => $orgid, 'pd.active' => 1])
                ->whereRaw($sc2)
                ->select('pd.paydate as operdate', db::raw('1 as sumtypeid')
                    , db::raw("520 as sysobjid")
                    , db::raw("null as org_placename")
                    , db::raw("concat('оплата (',ifnull(pd.reason,''),')') as descript")
                    , db::raw("null as itm_price")
                    , db::raw("null as qty")
                    , db::raw("pd.paydir*pd.paysum as opersum")
                    , db::raw("null as notes")
                    , db::raw("null as raid_qty")
                )
                ->unionall($sells)
                ->unionall($buys)
                ->unionall($wrh_sells)
                ->orderBy('operdate')
                ->get();
            //dd($recs);
        }

        $data = new \stdClass();
        $data->returl = $returl;
        $data->ownorg = org::find($ownorgid);
        $data->org = org::find($orgid);
        $data->org_saldo = $org_saldo;

        $sc_task = "1=1";
        //Если у пользователя нет права в Задачах, то показывать только публичные задачи и задачи в которых он участвует
        if (!usrsysright::isUserHasRightByCode_cached($userid, 'tasks.read'))
            $sc_task = "( tsk.public_lvl=2 or tsk.inituserid={$userid}
                                    or exists (select 1 from task_users r where r.taskid=tsk.id and userid={$userid}) )";

        $data->tasks = task::from('tasks as tsk')
            ->where(['tsk.srcsysobjid' => 111, 'tsk.srcobjid' => $orgid])
            ->whereNull('tsk.statusid')
            ->whereRaw($sc_task)
            ->select('tsk.name', 'tsk.id')
            ->get();


        //обновим счетчик использования отчета
        report::updUseCnt($report_id, $userid, \Auth::user()->name);

        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен отчет; ' . $ownorgid . '/' . $orgid);
        if ($export2xls == "1") {
            $response = Excel::download(new rep53Export($recs, $data), "Детализация_" . Str::slug($data->org->name) . ".xlsx", \Maatwebsite\Excel\Excel::XLSX);

            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }


        return view('paydocs.rep' . $report_id, compact('recs', 'data'));
    }

    function rep54(Request $request, $date)
    {
        //Детализация платежей за дату

        $report_id = 54;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $data = new \stdClass();
        $data->date = $date;
        $data->returl = $returl;

        $recs = paydoc::from('paydocs as pd')
            ->join('orgs as oo', 'oo.id', 'pd.ownorgid')
            ->join('orgs as o', 'o.id', 'pd.orgid')
            ->where('pd.paydate', $date)
            ->select('pd.id as objid', 'pd.paydir', 'pd.ownorgid'
                , 'oo.name as ownorg_name'
                , 'o.name as org_name'
                , db::raw("ifnull(pd.reason,' ') as descript")
                , db::raw("pd.paydir*pd.paysum as paysum")
                , db::raw("case when (pd.paydir=1)  then pd.paysum else null end as inp_sum")
                , db::raw("case when (pd.paydir=-1) then pd.paysum else null end as out_sum")
            )
            ->get();
        //dd($date,$recs);

        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен отчет; ' . $date);
        if ($export2xls == "1") {
            $response = Excel::download(new rep54Export($recs, $data), "Платежи за " . Str::slug($data->$date) . ".xlsx", \Maatwebsite\Excel\Excel::XLSX);

            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
            //HERE IS THE MAGIC FOLKS
            ob_end_clean();
            return $response;
        }

        return view('paydocs.rep' . $report_id, compact('recs', 'data'));
    }

}
