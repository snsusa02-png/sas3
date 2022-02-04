<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\equiprqst_item;
use App\eritm_offer;
use App\eritm_supply;
use App\Exports\InvoicesExport;
use App\Exports\PayPlanExport;
use App\mchn_raid;
use App\obj_finoper;
use App\org_saldo;
use App\orgplnpay;
use App\orgplnpay_item;
use App\orgstaff;
use App\pay_category;
use App\paydoc;
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

class PayDocReportController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;  //reports
        //$this->objcode = 'reports';
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
                }
            }
        }

        $recs = null;

        if ($need_search) {

            $recs = org::from('orgs as o')
                ->whereRaw($sc);

            $recs = $recs->select(
                'o.id as orgid', 'o.name as orgname'
                , db::raw("orgSaldo_onDate(o.id, {$ownorgid}, null) as org_saldo")
                , db::raw("(select group_concat( trim(concat(ifnull(os.fname,''),' ', os.lname)) SEPARATOR ',')
                            from orgstaff as os
                            join org_curators as oc
                            on oc.staffid=os.id and oc.active=1 and now() between oc.begdt and ifnull(oc.enddt,now())
                            where oc.orgid=o.id
                            ) as org_curators")

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

//        $data->curators = User::lstFor_cached([
//            'in_org_curators_now' => 1,
//        ]);
        $data->curators = orgstaff::lstFor_cached([
            'in_org_curators_now' => 1,
        ]);

        return view('paydocs.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public
    function rep48(Request $request, $ownorgid, $orgid)
    {
        //Детализация баланса контрагента

        $report_id = 48;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;

//        $ownorgid = $request->get('ownorgid');
//        $orgid = $request->get('orgid');

        if (!(isset($ownorgid) and isset($orgid)))
            return redirect($returl)
                ->with(['error' => 'Запрос ожидал две компании!']);

        $org_saldo = org_saldo::from('org_saldos as s')
            ->where(['ownorgid' => $ownorgid, 'orgid' => $orgid])
            ->first();
        //dd($org_saldo);

        $mindate = $org_saldo->ondate ?? null;
        //dd($mindate);

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
            ->whereRaw($sc)
            ->orderBy('operdate')
//            ->select('fo.*'
//                , db::raw("if(srcorgid = {$ownorgid}, - 1, + 1) * opersum as opersum")
            ->select('sysobjid', 'sumtypeid', 'operdate', 'price', 'descript'
                , db::raw("sum(qty) as qty")
                , db::raw("sum( if(srcorgid = {$ownorgid}, - 1, + 1) * opersum) as opersum")
            )
            ->groupBy(['sysobjid', 'sumtypeid', 'operdate', 'price', 'descript'])
            ->get();
        //dd($recs);

        $data = new \stdClass();
        $data->returl = $returl;
        $data->ownorg = org::find($ownorgid);
        $data->org = org::find($orgid);
        $data->org_saldo = $org_saldo;
        //dd($data);

        //обновим счетчик использования отчета
        report::updUseCnt($report_id, $userid, \Auth::user()->name);

        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен отчет; ' . $ownorgid . '/' . $orgid);

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
        //dd($ownorgs);

        return view('paydocs.informer49', compact('ownorgs'));
    }

}
