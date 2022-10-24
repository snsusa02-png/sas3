<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\contract;
use App\group;
use App\grptype;

//use function App\Http\Controllers\getStartAndEndDate;
//use function App\Http\Controllers\isTblInGrps;
use App\mchn_raid;
use App\mr_oper;
use App\order;
use App\org;
use App\org_curator;
use App\org_place;
use App\orgstaff;
use App\refitem;
use App\report;
use App\saleplan;
use App\objlog;
use App\User;
use App\wrkrep;
use Cache;
use Config;
use DateTime;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\mFinDocs\Entities\findoc;
use Modules\Stock\Entities\wrh;
use Modules\Stock\Entities\wrh_stock;
use Illuminate\Support\Facades\DB;

class AnaliticsController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 600;
    }


    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('reports.index');
    }

    public static function rep32setparams(Request $request)
    {
        // - установим параметры поиска -------------------------------------------------
        $search_setname = "reports.rep32";

        if ($request->isMethod('post')) {

            $search_params = [];

            $search_params['s_ownorgid'] = $request->get("s_ownorgid");
            $search_params['s_buildobjid'] = $request->get("s_buildobjid");
            $search_params['s_contractid'] = $request->get("s_contractid");

            $search_params['vTimeSelType'] = $request->get("vTimeSelType");

            $search_params['vYr1'] = $request->get("vYr1");
            $search_params['vMn1'] = $request->get("vMn1");
            $search_params['vDc1'] = $request->get("vDc1");
            $search_params['vWk1'] = $request->get("vWk1");
            $search_params['vBegDate1'] = $request->get("vBegDate1");
            $search_params['vEndDate1'] = $request->get("vEndDate1");

            $search_params['vYr2'] = $request->get("vYr2");
            $search_params['vMn2'] = $request->get("vMn2");
            $search_params['vDc2'] = $request->get("vDc2");
            $search_params['vWk2'] = $request->get("vWk2");
            $search_params['vBegDate2'] = $request->get("vBegDate2");
            $search_params['vEndDate2'] = $request->get("vEndDate2");

            $search_params['s_orggrpid'] = $request->get("s_orggrpid");
            $search_params['s_orgid'] = $request->get("s_orgid");
            $search_params['s_orgname'] = $request->get("orgname");
            $search_params['s_mngrid'] = $request->get("s_mngrid");
            $search_params['s_minitmsum'] = $request->get("s_minitmsum");
            $search_params['s_mindocsum'] = $request->get("s_mindocsum");

            $search_params['showGrpSum'] = $request->get("showGrpSum");
            $search_params['ordbyItmSumDesc'] = $request->get("ordbyItmSumDesc");
            $search_params['ordbyDocQtyDesc'] = $request->get("ordbyDocQtyDesc");

            $GrpLst = $request->input("GrpLst");
            $search_params['GrpLst'] = $GrpLst;
            $search_params['list2'] = array_filter(explode(',', $GrpLst));

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => $search_params]);
        }
        //$method = $request->method();
        //dd($method,  session('search_params'), session('search_setname'));

        return redirect(route('reports.rep32'));
    }


    public static function rep32(Request $request)
    {//Анализ продаж по данным заказов (не фин. документов)

        $report_id = 32;
        $userid = \Auth::user()->id;

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep" . $report_id;
        $s_buildobjid = null;
        $s_ownorgid = null;
        $s_contractid = null;
        $vTimeSelType = 1;
        $vYr1 = null;
        $vYr1 = today()->format('Y');
        $vMn1 = null;
        $vDc1 = null;
        $vWk1 = null;
        $vBegDate1 = null;
        $vEndDate1 = null;
        $vYr2 = null;
        $vMn2 = null;
        $vDc2 = null;
        $vWk2 = null;
        $vBegDate2 = null;
        $vEndDate2 = null;

        $s_orggrpid = null;
        $s_orgid = null;
        $s_orgname = null;
        $s_mngrid = null;
        $s_minitmsum = null;
        $s_mindocsum = null;
        $list2 = null;
        $GrpLst = "";
        $showGrpSum = null;
        $ordbyItmSumDesc = null;
        $ordbyDocQtyDesc = null;

        if ($request->isMethod('post')) {

        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {

                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_ownorgid = $params['s_ownorgid'] ?? null;
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                    $s_contractid = $params['s_contractid'] ?? null;
//                    dd($params);
                    $vTimeSelType = $params['vTimeSelType'] ?? 1;
                    $vYr1 = $params['vYr1'] ?? null;
                    $vMn1 = $params['vMn1'] ?? null;
                    $vDc1 = $params['vDc1'] ?? null;
                    $vWk1 = $params['vWk1'] ?? null;
                    $vBegDate1 = $params['vBegDate1'] ?? null;;
                    $vEndDate1 = $params['vEndDate1'] ?? null;;

                    $vYr2 = $params['vYr2'] ?? null;
                    $vMn2 = $params['vMn2'] ?? null;
                    $vDc2 = $params['vDc2'] ?? null;
                    $vWk2 = $params['vWk2'] ?? null;
                    $vBegDate2 = $params['vBegDate2'] ?? null;;
                    $vEndDate2 = $params['vEndDate2'] ?? null;;

                    $s_orggrpid = $params['s_orggrpid'] ?? null;
                    $s_orgid = $params['s_orgid'] ?? null;
                    $s_orgname = $params['s_orgname'] ?? null;
                    $s_mngrid = $params['s_mngrid'] ?? null;
                    $s_minitmsum = $params['s_minitmsum'] ?? null;
                    $s_mindocsum = $params['s_mindocsum'] ?? null;
                    $list2 = $params['list2'] ?? null;
                    $GrpLst = $params['GrpLst'] ?? null;
                    $showGrpSum = $params['showGrpSum'] ?? 0;
                    $ordbyItmSumDesc = $params['ordbyItmSumDesc'] ?? 0;
                    $ordbyDocQtyDesc = $params['ordbyDocQtyDesc'] ?? 0;
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_ownorgid" => $s_ownorgid,
            "s_buildobjid" => $s_buildobjid,
            "s_contractid" => $s_contractid,
            "vTimeSelType" => $vTimeSelType,
            "vYr1" => $vYr1,
            "vMn1" => $vMn1,
            "vDc1" => $vDc1,
            "vWk1" => $vWk1,
            "vBegDate1" => $vBegDate1,
            "vEndDate1" => $vEndDate1,
            "vYr2" => $vYr2,
            "vMn2" => $vMn2,
            "vDc2" => $vDc2,
            "vWk2" => $vWk2,
            "vBegDate2" => $vBegDate2,
            "vEndDate2" => $vEndDate2,
            "s_orggrpid" => $s_orggrpid,
            "s_orgid" => $s_orgid,
            "s_orgname" => $s_orgname,
            "s_mngrid" => $s_mngrid,
            "s_minitmsum" => $s_minitmsum,
            "s_mindocsum" => $s_mindocsum,
            "list2" => $list2,
            "GrpLst" => $GrpLst,
            "showGrpSum" => $showGrpSum,
            "ordbyItmSumDesc" => $ordbyItmSumDesc,
            "ordbyDocQtyDesc" => $ordbyDocQtyDesc,
        ];

        function getStartAndEndDate($week, $year)
        {
            $dto = new DateTime();
            $dto->setISODate($year, $week);
            $ret['week_start'] = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $ret['week_end'] = $dto->format('Y-m-d');
            return $ret;
        }


        if ($vTimeSelType == 1) {

            $begdate1 = null;
            $enddate1 = null;
            if (isset($vYr1))

                if (isset($vWk1)) {
                    $week_array = getStartAndEndDate($vWk1, $vYr1);
                    //print_r($week_array);
                    $begdate1 = $week_array['week_start'];
                    $enddate1 = $week_array['week_end'];
                } elseif (isset($vMn1)) {
                    $begdate1 = $vYr1 . '-' . $vMn1 . '-01';
                    $enddate1 = date('Y-m-d', strtotime($begdate1 . ' + 1 month - 1 day'));

                } else {
                    $begdate1 = $vYr1 . '-01-01';
                    $enddate1 = $vYr1 . '-12-31';
                }


            $begdate2 = null;
            $enddate2 = null;
            if (isset($vYr2)) {
                $vMn2 = $vMn2 ?? $vMn1;

                if (isset($vWk2)) {
                    $week_array = getStartAndEndDate($vWk2, $vYr2);
                    $begdate2 = $week_array['week_start'];
                    $enddate2 = $week_array['week_end'];
                } elseif (isset($vMn2)) {
                    $begdate2 = $vYr2 . '-' . $vMn2 . '-01';
                    $enddate2 = date('Y-m-d', strtotime($begdate2 . ' + 1 month - 1 day'));

                } else {
                    $begdate2 = $vYr2 . '-01-01';
                    $enddate2 = $vYr2 . '-12-31';
                }
            } else
                $vMn2 = null;

            $search_params['vMn2'] = $vMn2;

        } else {
            $begdate1 = $vBegDate1;
            $enddate1 = $vEndDate1;
            $begdate2 = $vBegDate2;
            $enddate2 = $vEndDate2;
        }

        if (isset($begdate1) and isset($begdate2)) {
            if ($begdate2 < $begdate1) {
                $d = $begdate1;
                $begdate1 = $begdate2;
                $begdate2 = $d;

                $d = $enddate1;
                $enddate1 = $enddate2;
                $enddate2 = $d;
            }
//            dd($begdate1, $enddate1, $begdate2, $enddate2);
        }


        if (isset($begdate1) and isset($enddate1)) {
            //Если параметры поиска не заданы, то ...
            $needSearch = true;
        } else $needSearch = false;
//dd($needSearch);


        //$ownorgs = org::lstOwnOrgs();

        $data = new \stdClass();
        $data->retURL = $request->get('returl');
        $data->ownorgs = org::lstFor(['in_wrkrep_machines' => 1]);  //заказчики
        $data->buildobjs = buildobj::lstFor(['in_wrkreps' => 1, 'in_buildobj_staff' => $userid]);
        $data->contracts = contract::lstFor(['in_wrkrep_machines' => 1]);
        $data->orggroups = group::lstOrgGroups_cache();
        $data->years = wrkrep::years();
        $data->monthes = Config::get('constants.monthes');
//dd($data);
        $year = date_create_from_format('Y-m-d', date('Y-m-d'))->format("Y");

        //Менеджеры/кураторы
        $managers = org_curator::AllCurators_cache();
        //$managers = order::AllWorkers_cache();
        $managers = [];
        $managers[0] = '-без менеджера-';
        //dd($managers);


        $conditions = '';
        $sc = null;
        if ($needSearch) {

            $conditions .= 'Период 1: "<b>' . $begdate1 . ' - ' . $enddate1 . '</b>"; ';
            if (isset($begdate1) and isset($begdate2))
                $conditions .= 'Период 2: "<b>' . $begdate2 . ' - ' . $enddate2 . '</b>"; ';

            $sc = " 1=1 ";
            if (1 == 1 and isset($s_ownorgid)) {
                $sc .= " and fd.ownorgid=" . $s_ownorgid;
                $conditions .= 'Заказчик = "<b>' . $data->ownorgs[$s_ownorgid] . '</b>"; ';
            }

            if (1 == 1 and isset($s_contractid)) {
                $sc .= " and di.contractid=" . $s_contractid;
                $conditions .= 'Договор = "<b>' . $data->contracts[$s_contractid] . '</b>"; ';
            }

            if (isset($s_orggrpid)) {
                $sc .= " and exists(select 1 from grpitems gl where gl.sysobjid=111 and gl.objid=m.orgid and gl.grpid=" . $s_orggrpid . ')';
                $groups = group::lstAllGroups_cache();
//                dd($groups);
                $conditions .= 'Группа = <b>' . $groups[$s_orggrpid] . '</b>; ';
            }

            if (isset($s_buildobjid)) {
                $sc .= " and fd.buildobjid=" . $s_buildobjid;
                $conditions .= 'Объект = &quot;<b>' . ($data->buildobjs[$s_buildobjid] ?? $s_buildobjid) . '</b>&quot;; ';
            }
            if (isset($s_orgid)) {
                $sc .= " and m.orgid=" . $s_orgid;
                $conditions .= 'Поставщик = "<b>' . $s_orgname . '</b>"; ';
            }

            if (isset($s_mngrid))
                if ($s_mngrid == 0) {
                    $sc .= " and fd.inituserid is null";
                    $conditions .= 'Без исполнителя';
                } else {
                    $sc .= " and fd.inituserid=" . $s_mngrid;
                    $conditions .= 'Табельщик: <b>' . $managers[$s_mngrid] . '</b>; ';
                }
        }
        // --------------------------------------------------------------------
        //var_dump($sc);
//        var_dump($GrpLst);
        //dd($sc);


        $allgrps = [
//            ['title' => 'год отгрузки', 'fld' => 'year(fd.docdate)', 'lbl' => 'yr'],
            ['title' => 'квартал', 'jointbl' => '', 'fld' => 'QUARTER(fd.docdate)', 'lbl' => 'quart', 'timescale' => 1],
            ['title' => 'месяц', 'jointbl' => '', 'fld' => 'month(fd.docdate)', 'lbl' => 'mn', 'timescale' => 1],
            ['title' => 'неделя', 'jointbl' => '', 'fld' => 'week(fd.docdate)', 'lbl' => 'wk', 'timescale' => 1],
            ['title' => 'объект', 'jointbl' => 'bo', 'fld' => 'fd.buildobjid', 'lbl' => 'buildobjid', 'show_val' => 'bo.name'],
            ['title' => 'дата работы', 'jointbl' => '', 'fld' => 'fd.docdate', 'lbl' => 'docdate', 'timescale' => 1],
            ['title' => 'вид работ', 'jointbl' => 'bot', 'fld' => 'fd.buildopertypeid', 'lbl' => 'buildopertypeid', 'show_val' => 'bot.name'],
//            ['title' => 'менеджер', 'jointbl' => 'su', 'fld' => 'fd.saleuserid', 'lbl' => 'saleuserid', 'show_val' => 'ifnull(su.name,"-нет-")'],
            ['title' => 'поставщик', 'jointbl' => '', 'fld' => 'm.orgid', 'lbl' => 'orgid', 'show_val' => 'o.name'],
            ['title' => 'категория спецтехники', 'jointbl' => 'mt', 'fld' => 'm.mchntypeid', 'lbl' => 'mchntypeid', 'show_val' => 'ifnull(mt.name,"-нет-")'],
            ['title' => 'техника', 'jointbl' => 'ь', 'fld' => 'di.machineid', 'lbl' => 'machineid', 'show_val' => "ifnull(concat(m.regnum,', ',m.name),'-не известно-')"],
            //['title' => 'заказчик', 'jointbl' => 'oo', 'fld' => 'di.orgid', 'lbl' => 'ownorgid', 'show_val' => 'oo.name'],
            ['title' => 'договор подряда', 'jointbl' => 'c', 'fld' => 'di.contractid', 'lbl' => 'contractid', 'show_val' => "concat(c.docnum,' от ', c.docdate)"],
//            ['title' => 'брэнд', 'jointbl' => 'ri', 'fld' => 'ri.brandid', 'lbl' => 'brandid', 'show_val' => 'ifnull(b.name,"-нет-")'],
            ['title' => 'табельщик', 'jointbl' => 'su', 'fld' => 'fd.inituserid', 'lbl' => 'inituserid', 'show_val' => 'ifnull(su.name,"-нет-")'],
        ];


        //Добавим динамические группировки - от групп для организаций
        $grptypes = grptype::where('active', 1)
            ->where('forsysobjid', 111)
            ->select('id', 'name')->get();
        foreach ($grptypes as $gt) {
            $n = $gt->id;
            $allgrps[] = ['title' => 'группы: ' . $gt->name, 'jointbl' => 'grp' . $n, 'fld' => 'grp' . $n . '.name', 'lbl' => 'grp' . $n . 'name', 'show_val' => 'ifnull(grp' . $n . '.name,"-нет-")'];
        }
        //dd($allgrps);

        $aCnds = [];
        foreach ($allgrps as $grp) {
            $aCnds += [$grp['fld'] => $grp['title']];
        }

        $grps = [];
        $aGrps = [];   //Массив с выбором

        //dd($sc);

        if (isset($sc)) {

            if (isset($list2)) {
                foreach ($list2 as $itm) {
                    foreach ($allgrps as $grp) {
                        if ($grp['fld'] == $itm) {
                            $grps[] = $grp;
                            $aGrps += [$grp['fld'] => $grp['title']];
                        }
                    }
                }
                //var_dump($grps, $aGrps);
            }
//            dd($aCnds, $aGrps, array_diff($aCnds, $aGrps), array_diff($aGrps, $aCnds));
            $aCnds = array_diff($aCnds, $aGrps);

            function isTblInGrps($tbl, $grps)
            {
                $add = false;
                foreach ($grps as $grp) {
                    if ($grp['jointbl'] == $tbl) {
                        $add = true;
                        break;
                    }
                }
                return $add;
            }

            $dataset = [];
            $dataset[1] = [$begdate1, $enddate1];

            $datasetCnt = 1;
            if (isset($begdate2) and isset($enddate2)) {
                $datasetCnt++;
                $dataset[2] = [$begdate2, $enddate2];

                $recs2 = wrkrep::from('wrkreps as fd')
                    ->join('wrkrep_machines as di', 'di.wrkrep_id', 'fd.id')
                    ->join('machines as m', 'm.id', 'di.machineid')
                    ->join('orgs as o', 'o.id', 'm.orgid')

                    //->where('ft.forsale', '<>', 0)
                    //->where('fd.ownorgid', $s_ownorgid)
                    ->wherebetween('fd.docdate', [$begdate2, $enddate2])
                    ->whereIn('fd.statusid', [1])
                    ->whereraw($sc);


                if (isTblInGrps('oo', $grps))
                    $recs2 = $recs2->leftjoin('orgs as oo', 'oo.id', 'fd.ownorgid');

                if (isTblInGrps('bo', $grps))
                    $recs2 = $recs2->leftjoin('buildobjs as bo', 'bo.id', 'fd.buildobjid');

                if (isTblInGrps('bot', $grps))
                    $recs2 = $recs2->leftjoin('buildopertypes as bot', 'bot.id', 'di.buildopertypeid');

                if (isTblInGrps('mt', $grps))
                    $recs2 = $recs2->leftjoin('mchntypes as mt', 'mt.id', 'm.mchntypeid');

                if (isTblInGrps('c', $grps))
                    $recs2 = $recs2->leftjoin('contracts as c', 'c.id', 'di.contractid');

                if (isTblInGrps('su', $grps))
                    $recs2 = $recs2->leftjoin('users as su', 'su.id', 'fd.inituserid');

                //* Динамически подкючим группы - если нужно
                foreach ($grptypes as $gt) {
                    $tbl = 'grp' . $gt->id;
                    if (isTblInGrps($tbl, $grps))
                        $recs2 = $recs2->leftJoin(DB::raw('(select gi.objid, g.name as name
                        from grpitems as gi
                        join groups as g on g.id=gi.grpid and g.grptypeid=' . $gt->id
                            . ' where gi.sysobjid=111) ' . $tbl),
                            function ($join) use ($tbl) {
                                $join->on($tbl . '.objid', '=', 'm.orgid');
                            });
                }

                $recs2 = $recs2->selectRaw('2 as dataset, sum(di.wrkhrs*1) as itmqty
                , sum(di.wrkhrs*(di.hour_work_cost+di.hour_fuel_cost)) itmsum
                , count(distinct fd.id) as docqty');


                foreach ($grps as $grp) {
                    if (isset($grp['show_val']))
                        $recs2 = $recs2->addSelect(DB::raw($grp['show_val'] . ' as ' . $grp['lbl']));
                    else
                        $recs2 = $recs2->addSelect(DB::raw($grp['fld'] . ' as ' . $grp['lbl']));

                    $recs2 = $recs2->groupby($grp['lbl']);
                }
            }


            $recs = wrkrep::from('wrkreps as fd')
                ->join('wrkrep_machines as di', 'di.wrkrep_id', 'fd.id')
                ->join('machines as m', 'm.id', 'di.machineid')
                ->join('orgs as o', 'o.id', 'm.orgid')
                ->wherebetween('fd.docdate', [$begdate1, $enddate1])
                ->whereIn('fd.statusid', [1])
                ->whereraw($sc);

            if (isTblInGrps('oo', $grps))
                $recs = $recs->leftjoin('orgs as oo', 'oo.id', 'fd.ownorgid');

            if (isTblInGrps('bo', $grps))
                $recs = $recs->leftjoin('buildobjs as bo', 'bo.id', 'fd.buildobjid');

            if (isTblInGrps('bot', $grps))
                $recs = $recs->leftjoin('buildopertypes as bot', 'bot.id', 'di.buildopertypeid');

            if (isTblInGrps('su', $grps))
                $recs = $recs->leftjoin('users as su', 'su.id', 'fd.inituserid');

            if (isTblInGrps('c', $grps))
                $recs = $recs->leftjoin('contracts as c', 'c.id', 'di.contractid');

            if (isTblInGrps('mt', $grps))
                $recs = $recs->leftjoin('mchntypes as mt', 'mt.id', 'm.mchntypeid');


            //Динамически подкючим группы - если нужно
            foreach ($grptypes as $gt) {
                $tbl = 'grp' . $gt->id;
                if (isTblInGrps($tbl, $grps))
                    $recs = $recs->leftJoin(DB::raw('(select gi.objid, g.name as name
                        from grpitems as gi
                        join groups as g on g.id=gi.grpid and g.grptypeid=' . $gt->id
                        . ' where gi.sysobjid=111) ' . $tbl),
                        function ($join) use ($tbl) {
                            $join->on($tbl . '.objid', '=', 'm.orgid');
                        });
            }

//            $recs = $recs->selectRaw('1 as dataset, sum(di.qty*ft.forsale) as itmqty, sum(di.qty*di.price*ft.forsale) itmsum
//                , count(distinct fd.id) as docqty');
            $recs = $recs->selectRaw('1 as dataset, sum(di.wrkhrs*1) as itmqty
                , sum(di.wrkhrs*(di.hour_work_cost+di.hour_fuel_cost)) itmsum
                , count(distinct fd.id) as docqty');

            foreach ($grps as $grp) {
                if (isset($grp['show_val']))
                    $recs = $recs->addSelect(DB::raw($grp['show_val'] . ' as ' . $grp['lbl']));
                else
                    $recs = $recs->addSelect(DB::raw($grp['fld'] . ' as ' . $grp['lbl']));

                $recs = $recs->groupby($grp['lbl']);
            }

            if (isset($begdate2) and isset($enddate2)) {
                $recs = $recs->union($recs2);
            }

            if ($datasetCnt == 1 and count($grps) == 1 and $ordbyItmSumDesc == 1)
                $recs = $recs->orderby('itmsum', 'desc');

            if ($datasetCnt == 1 and count($grps) == 1 and $ordbyDocQtyDesc == 1)
                $recs = $recs->orderby('docqty', 'desc');

            foreach ($grps as $grp) {
                $recs = $recs->orderby($grp['lbl']);
            }
            $recs = $recs->orderby('dataset');

            $recs = $recs->get();


            if (isset($s_minitmsum)) {
                //filter by minimal ItmSum
                $recs = $recs->filter(function ($item) use ($s_minitmsum) {
                    return $item->itmsum >= $s_minitmsum;
                })->values();
            }
            if (isset($s_mindocsum)) {
                //filter by minimal ItmSum
                $recs = $recs->filter(function ($item) use ($s_mindocsum) {
                    return $item->itmsum / $item->docqty >= $s_mindocsum;
                })->values();
            }
            if (1 == 0) {
                //filter by minimal Order's count
                $recs = $recs->filter(function ($item) {
                    return $item->docqty > 5;
                })->values();
            }

//            Event::dispatch(new docs1CLoadedEvent('code'
//                , 'user: ' . \Auth::user()->name
//                . ', period1: ' . $begdate1 . '-' . $enddate1
//                . ', period2: ' . $begdate2 . '-' . $enddate2));

//            event(new docs1CLoadedEvent('code'
//                , '2 user: ' . \Auth::user()->name
//                . ', period1: ' . $begdate1 . '-' . $enddate1
//                . ', period2: ' . $begdate2 . '-' . $enddate2));

        } else {
            //return (redirect()->back());
            $dataset = null;
            $datasetCnt = 0;
            $grps = null;
            $recs = null;
        }

        //todo: обновить счетчик использования отчета
        //
        report::updUseCnt($report_id, $userid);
        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен: ' . $conditions);


        return view('analitics.rep32', compact(['dataset', 'datasetCnt', 'recs', 'grps', 'data'
            , 'year'
            , 'managers', 'search_params'
            , 'aCnds', 'aGrps', 'GrpLst', 'conditions']));
    }

    public static function rep45setparams(Request $request)
    {
        // - установим параметры поиска -------------------------------------------------
        $search_setname = "reports.rep45";

        if ($request->isMethod('post')) {

            $search_params = [];

            $search_params['s_sale_dir'] = $request->get("s_sale_dir");
            $search_params['s_suporgid'] = $request->get("s_suporgid");
            $search_params['s_buildobjid'] = $request->get("s_buildobjid");
            $search_params['s_contractid'] = $request->get("s_contractid");
            $search_params['s_contractid'] = $request->get("s_contractid");
            $search_params['s_sup_placeid'] = $request->get("s_sup_placeid");
            $search_params['s_org_placeid'] = $request->get("s_org_placeid");
            $search_params['s_refitmid'] = $request->get("s_refitmid");

            $search_params['vTimeSelType'] = $request->get("vTimeSelType");

            $search_params['vYr1'] = $request->get("vYr1");
            $search_params['vMn1'] = $request->get("vMn1");
            $search_params['vDc1'] = $request->get("vDc1");
            $search_params['vWk1'] = $request->get("vWk1");
            $search_params['vBegDate1'] = $request->get("vBegDate1");
            $search_params['vEndDate1'] = $request->get("vEndDate1");

            $search_params['vYr2'] = $request->get("vYr2");
            $search_params['vMn2'] = $request->get("vMn2");
            $search_params['vDc2'] = $request->get("vDc2");
            $search_params['vWk2'] = $request->get("vWk2");
            $search_params['vBegDate2'] = $request->get("vBegDate2");
            $search_params['vEndDate2'] = $request->get("vEndDate2");

            $search_params['s_orggrpid'] = $request->get("s_orggrpid");
            $search_params['s_orgid'] = $request->get("s_orgid");
            $search_params['s_orgname'] = $request->get("orgname");
            $search_params['s_mngrid'] = $request->get("s_mngrid");
            $search_params['s_minitmsum'] = $request->get("s_minitmsum");
            $search_params['s_mindocsum'] = $request->get("s_mindocsum");

            $search_params['showGrpSum'] = $request->get("showGrpSum");
            $search_params['ordbyItmSumDesc'] = $request->get("ordbyItmSumDesc");
            $search_params['ordbyDocQtyDesc'] = $request->get("ordbyDocQtyDesc");

            $GrpLst = $request->input("GrpLst");
            $search_params['GrpLst'] = $GrpLst;
            $search_params['list2'] = array_filter(explode(',', $GrpLst));

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => $search_params]);
        }
        //$method = $request->method();
        //dd($method,  session('search_params'), session('search_setname'));

        return redirect(route('reports.rep45'));
    }


    public static function rep45(Request $request)
    {//Анализ данных по рейсам автомобилей/спецтехники

        $report_id = 45;
        $userid = \Auth::user()->id;

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep" . $report_id;
        $s_sale_dir = 1;
        $s_buildobjid = null;
        $s_suporgid = null;
        $s_contractid = null;
        $s_sup_placeid = null;
        $s_org_placeid = null;
        $s_refitmid = null;
        $vTimeSelType = 1;
        $vYr1 = null;
        $vYr1 = today()->format('Y');
        $vMn1 = null;
        $vDc1 = null;
        $vWk1 = null;
        $vBegDate1 = null;
        $vEndDate1 = null;
        $vYr2 = null;
        $vMn2 = null;
        $vDc2 = null;
        $vWk2 = null;
        $vBegDate2 = null;
        $vEndDate2 = null;

        $s_orggrpid = null;
        $s_orgid = null;
        $s_orgname = null;
        $s_mngrid = null;
        $s_minitmsum = null;
        $s_mindocsum = null;
        $list2 = null;
        $GrpLst = "";
        $showGrpSum = null;
        $ordbyItmSumDesc = null;
        $ordbyDocQtyDesc = null;

        if ($request->isMethod('post')) {

        } else {
            if (session('search_setname') == $search_setname) {
                if (!empty(session('search_params'))) {

                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_sale_dir = $params['s_sale_dir'] ?? 1;
                    $s_suporgid = $params['s_suporgid'] ?? null;
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                    $s_contractid = $params['s_contractid'] ?? null;
                    $s_sup_placeid = $params['s_sup_placeid'] ?? null;
                    $s_org_placeid = $params['s_org_placeid'] ?? null;
                    $s_refitmid = $params['s_refitmid'] ?? null;
//                    dd($params);
                    $vTimeSelType = $params['vTimeSelType'] ?? 1;
                    $vYr1 = $params['vYr1'] ?? null;
                    $vMn1 = $params['vMn1'] ?? null;
                    $vDc1 = $params['vDc1'] ?? null;
                    $vWk1 = $params['vWk1'] ?? null;
                    $vBegDate1 = $params['vBegDate1'] ?? null;;
                    $vEndDate1 = $params['vEndDate1'] ?? null;;

                    $vYr2 = $params['vYr2'] ?? null;
                    $vMn2 = $params['vMn2'] ?? null;
                    $vDc2 = $params['vDc2'] ?? null;
                    $vWk2 = $params['vWk2'] ?? null;
                    $vBegDate2 = $params['vBegDate2'] ?? null;;
                    $vEndDate2 = $params['vEndDate2'] ?? null;;

                    $s_orggrpid = $params['s_orggrpid'] ?? null;
                    $s_orgid = $params['s_orgid'] ?? null;
                    $s_orgname = $params['s_orgname'] ?? null;
                    $s_mngrid = $params['s_mngrid'] ?? null;
                    $s_minitmsum = $params['s_minitmsum'] ?? null;
                    $s_mindocsum = $params['s_mindocsum'] ?? null;
                    $list2 = $params['list2'] ?? null;
                    $GrpLst = $params['GrpLst'] ?? null;
                    $showGrpSum = $params['showGrpSum'] ?? 0;
                    $ordbyItmSumDesc = $params['ordbyItmSumDesc'] ?? 0;
                    $ordbyDocQtyDesc = $params['ordbyDocQtyDesc'] ?? 0;
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_sale_dir" => $s_sale_dir,
            "s_suporgid" => $s_suporgid,
            "s_buildobjid" => $s_buildobjid,
            "s_contractid" => $s_contractid,
            "s_sup_placeid" => $s_sup_placeid,
            "s_org_placeid" => $s_org_placeid,
            "s_refitmid" => $s_refitmid,
            "vTimeSelType" => $vTimeSelType,
            "vYr1" => $vYr1,
            "vMn1" => $vMn1,
            "vDc1" => $vDc1,
            "vWk1" => $vWk1,
            "vBegDate1" => $vBegDate1,
            "vEndDate1" => $vEndDate1,
            "vYr2" => $vYr2,
            "vMn2" => $vMn2,
            "vDc2" => $vDc2,
            "vWk2" => $vWk2,
            "vBegDate2" => $vBegDate2,
            "vEndDate2" => $vEndDate2,
            "s_orggrpid" => $s_orggrpid,
            "s_orgid" => $s_orgid,
            "s_orgname" => $s_orgname,
            "s_mngrid" => $s_mngrid,
            "s_minitmsum" => $s_minitmsum,
            "s_mindocsum" => $s_mindocsum,
            "list2" => $list2,
            "GrpLst" => $GrpLst,
            "showGrpSum" => $showGrpSum,
            "ordbyItmSumDesc" => $ordbyItmSumDesc,
            "ordbyDocQtyDesc" => $ordbyDocQtyDesc,
        ];
        //dd($search_params );

        function getStartAndEndDate($week, $year)
        {
            $dto = new DateTime();
            $dto->setISODate($year, $week);
            $ret['week_start'] = $dto->format('Y-m-d');
            $dto->modify('+6 days');
            $ret['week_end'] = $dto->format('Y-m-d');
            return $ret;
        }


        if ($vTimeSelType == 1) {

            $begdate1 = null;
            $enddate1 = null;
            if (isset($vYr1))

                if (isset($vWk1)) {
                    $week_array = getStartAndEndDate($vWk1, $vYr1);
                    //print_r($week_array);
                    $begdate1 = $week_array['week_start'];
                    $enddate1 = $week_array['week_end'];
                } elseif (isset($vMn1)) {
                    $begdate1 = $vYr1 . '-' . $vMn1 . '-01';
                    $enddate1 = date('Y-m-d', strtotime($begdate1 . ' + 1 month - 1 day'));

                } else {
                    $begdate1 = $vYr1 . '-01-01';
                    $enddate1 = $vYr1 . '-12-31';
                }


            $begdate2 = null;
            $enddate2 = null;
            if (isset($vYr2)) {
                $vMn2 = $vMn2 ?? $vMn1;

                if (isset($vWk2)) {
                    $week_array = getStartAndEndDate($vWk2, $vYr2);
                    $begdate2 = $week_array['week_start'];
                    $enddate2 = $week_array['week_end'];
                } elseif (isset($vMn2)) {
                    $begdate2 = $vYr2 . '-' . $vMn2 . '-01';
                    $enddate2 = date('Y-m-d', strtotime($begdate2 . ' + 1 month - 1 day'));

                } else {
                    $begdate2 = $vYr2 . '-01-01';
                    $enddate2 = $vYr2 . '-12-31';
                }
            } else
                $vMn2 = null;

            $search_params['vMn2'] = $vMn2;

        } else {
            $begdate1 = $vBegDate1;
            $enddate1 = $vEndDate1;
            $begdate2 = $vBegDate2;
            $enddate2 = $vEndDate2;
        }

        if (isset($begdate1) and isset($begdate2)) {
            if ($begdate2 < $begdate1) {
                $d = $begdate1;
                $begdate1 = $begdate2;
                $begdate2 = $d;

                $d = $enddate1;
                $enddate1 = $enddate2;
                $enddate2 = $d;
            }
//            dd($begdate1, $enddate1, $begdate2, $enddate2);
        }


        if (isset($begdate1) and isset($enddate1)) {
            //Если параметры поиска не заданы, то ...
            $needSearch = true;
        } else $needSearch = false;
//dd($needSearch);


        //$ownorgs = org::lstOwnOrgs();

        $data = new \stdClass();

        $data->report = report::find($report_id);
        $data->retURL = $request->get('returl');
        $data->sale_dirs = [-1 => 'Закупки', +1 => 'Продажи', 0 => 'Внутренние'];

        $data->suporgs = org::lstFor_cached(['in_mr_opers_suporgid' => 1]);  //Продавцы
        $data->orgs = org::lstFor_cached(['in_mr_opers_orgid' => 1]);  //Покупатели

        $data->sup_places = org_place::lstFor_cached(['in_mr_opers_sup_placeid' => 1]);  //Места поставщика
        $data->org_places = org_place::lstFor_cached(['in_mr_opers_org_placeid' => 1]);  //Места клиента

        $data->refitems = refitem::lstFor_cached(['in_mr_opers' => 1], null
            , ['ri.id', DB::raw("concat(ri.name,', ', ri.unit) as tname")]);  //Груз/Услуга in_mchn_raids

        $data->orggroups = group::lstOrgGroups_cache();
        $data->years = mchn_raid::years();
        $data->monthes = Config::get('constants.monthes');

        $year = date_create_from_format('Y-m-d', date('Y-m-d'))->format("Y");

        //Диспетчеры
        $data->dispatchers = orgstaff::lstFor_cached(['dispatcher_in_mchn_raids' => 1]);

        $conditions = '';
        $sc = null;
        if ($needSearch) {

            $conditions .= 'Период 1: "<b>' . $begdate1 . ' - ' . $enddate1 . '</b>"; ';
            if (isset($begdate1) and isset($begdate2))
                $conditions .= 'Период 2: "<b>' . $begdate2 . ' - ' . $enddate2 . '</b>"; ';

            $sc = " 1=1 ";

            if (1 == 1 and isset($s_sale_dir)) {
                $sc .= " and mro.sale_dir=" . $s_sale_dir;
                $conditions .= 'Тип операций = "<b>' . $data->sale_dirs[$s_sale_dir] . '</b>"; ';
            }
            if (1 == 1 and isset($s_suporgid)) {
                $sc .= " and mro.suporgid=" . $s_suporgid;
                $conditions .= 'Исполнитель = "<b>' . $data->suporgs[$s_suporgid] . '</b>"; ';
            }
            if (1 == 1 and isset($s_sup_placeid)) {
                $sc .= " and mro.sup_placeid=" . $s_sup_placeid;
                $conditions .= 'Место поставщика = "<b>' . $data->sup_places[$s_sup_placeid] ?? '-' . '</b>"; ';
            }
            if (1 == 1 and isset($s_org_placeid)) {
                $sc .= " and mro.org_placeid=" . $s_org_placeid;
                $conditions .= 'Место клиента = "<b>' . $data->org_places[$s_org_placeid] ?? '-' . '</b>"; ';
            }
            if (1 == 1 and isset($s_refitmid)) {
                $sc .= " and mro.refitmid=" . $s_refitmid;
                $conditions .= 'Груз/Услуга = "<b>' . $data->refitems[$s_refitmid] ?? '-' . '</b>"; ';
            }

            if (1 == 1 and isset($s_contractid)) {
                $sc .= " and di.contractid=" . $s_contractid;
                $conditions .= 'Договор = "<b>' . $data->contracts[$s_contractid] . '</b>"; ';
            }

            if (isset($s_orggrpid)) {
                $sc .= " and exists(select 1 from grpitems gl where gl.sysobjid=111 and gl.objid=mro.orgid and gl.grpid=" . $s_orggrpid . ')';
                $groups = group::lstAllGroups_cache();
//                dd($groups);
                $conditions .= 'Группа = <b>' . $groups[$s_orggrpid] . '</b>; ';
            }

            if (isset($s_orgid)) {
                $sc .= " and mro.orgid=" . $s_orgid;
                $conditions .= 'Заказчик = "<b>' . $s_orgname . '</b>"; ';
            }

            if (isset($s_mngrid))
                if ($s_mngrid == 0) {
                    $sc .= " and mro.disp_staffid is null";
                    $conditions .= 'Без диспетчера';
                } else {
                    $sc .= " and mro.disp_staffid=" . $s_mngrid;
                    $conditions .= 'Диспетчер: <b>' . $data->dispatchers[$s_mngrid] . '</b>; ';
                }
        }
        // --------------------------------------------------------------------
        //var_dump($sc);
//        var_dump($GrpLst);
        //dd($sc);


        $allgrps = [
//            ['title' => 'год отгрузки', 'fld' => 'year(mr.wrkdate)', 'lbl' => 'yr'],
            ['title' => 'квартал', 'jointbl' => '', 'fld' => 'QUARTER(mr.wrkdate)', 'lbl' => 'quart', 'timescale' => 1],
            ['title' => 'месяц', 'jointbl' => '', 'fld' => 'month(mr.wrkdate)', 'lbl' => 'mn', 'timescale' => 1],
            ['title' => 'неделя', 'jointbl' => '', 'fld' => 'week(mr.wrkdate)', 'lbl' => 'wk', 'timescale' => 1],
            ['title' => 'дата работы', 'jointbl' => '', 'fld' => 'mr.wrkdate', 'lbl' => 'wrkdate', 'timescale' => 1],
            ['title' => 'категория спецтехники', 'jointbl' => 'mt', 'fld' => 'm.mchntypeid', 'lbl' => 'mchntypeid', 'show_val' => 'ifnull(mt.name,"-нет-")'],
            ['title' => 'техника', 'jointbl' => 'm', 'fld' => 'di.machineid', 'lbl' => 'machineid', 'show_val' => "ifnull(concat(m.regnum,', ',m.name),'-не известно-')"],
            ['title' => 'заказчик', 'jointbl' => 'o', 'fld' => 'mro.orgid', 'lbl' => 'orgid', 'show_val' => 'ifnull(o.name,"-не определен-")'],
            ['title' => 'диспетчер', 'jointbl' => 'ds', 'fld' => 'mro.disp_staffid', 'lbl' => 'disp_staffid', 'show_val' => 'ifnull(ds.name,"-нет-")'],
            ['title' => 'поставщик', 'jointbl' => 'so', 'fld' => 'mro.suporgid', 'lbl' => 'suporgid', 'show_val' => 'ifnull(so.name,"-не известен-")'],
            ['title' => 'груз/услуга', 'jointbl' => 'ri', 'fld' => 'mro.refitmid', 'lbl' => 'refitmid', 'show_val' => 'ifnull(ri.name,"-не известен-")'],
            ['title' => 'водитель', 'jointbl' => 'os', 'fld' => 'mr.driverid', 'lbl' => 'driverid', 'show_val' => 'ifnull(os.name,"-не известен-")'],
            ['title' => 'тип оплаты', 'jointbl' => 'pt', 'fld' => 'mro.paytypeid', 'lbl' => 'paytypeid', 'show_val' => 'ifnull(pt.name,"-не известен-")'],
            ['title' => 'место поставщика', 'jointbl' => 'p_l', 'fld' => 'mro.sup_placeid', 'lbl' => 'sup_placeid', 'show_val' => 'ifnull(p_l.name,"-не известно-")'],
            ['title' => 'место клиента', 'jointbl' => 'p_u', 'fld' => 'mro.org_placeid', 'lbl' => 'org_placeid', 'show_val' => 'ifnull(p_u.name,"-не известно-")'],
            ['title' => 'тип операции', 'jointbl' => 'ot', 'fld' => 'mr.opertypeid', 'lbl' => 'opertypeid', 'show_val' => 'ifnull(ot.name,"-не известно-")'],
            ['title' => 'договор', 'jointbl' => 'c', 'fld' => 'mro.contractid', 'lbl' => 'contractid', 'show_val' => 'ifnull(c.docnum,"-без договора-")'],
        ];


        //Добавим динамические группировки - от групп для организаций
        $grptypes = grptype::where('active', 1)
            ->where('forsysobjid', 111)
            ->select('id', 'name')->get();
        foreach ($grptypes as $gt) {
            $n = $gt->id;
            $allgrps[] = ['title' => 'группы: ' . $gt->name, 'jointbl' => 'grp' . $n, 'fld' => 'grp' . $n . '.name', 'lbl' => 'grp' . $n . 'name', 'show_val' => 'ifnull(grp' . $n . '.name,"-нет-")'];
        }
        //dd($allgrps);

        $aCnds = [];
        foreach ($allgrps as $grp) {
            $aCnds += [$grp['fld'] => $grp['title']];
        }

        $grps = [];
        $aGrps = [];   //Массив с выбором

        //dd($sc);

        if (isset($sc)) {

            if (isset($list2)) {
                foreach ($list2 as $itm) {
                    foreach ($allgrps as $grp) {
                        if ($grp['fld'] == $itm) {
                            $grps[] = $grp;
                            $aGrps += [$grp['fld'] => $grp['title']];
                        }
                    }
                }
                //var_dump($grps, $aGrps);
            }
//            dd($aCnds, $aGrps, array_diff($aCnds, $aGrps), array_diff($aGrps, $aCnds));
            $aCnds = array_diff($aCnds, $aGrps);

            function isTblInGrps($tbl, $grps)
            {
                $add = false;
                foreach ($grps as $grp) {
                    if ($grp['jointbl'] == $tbl) {
                        $add = true;
                        break;
                    }
                }
                return $add;
            }

            $dataset = [];
            $dataset[1] = [$begdate1, $enddate1];

            $datasetCnt = 1;
            if (isset($begdate2) and isset($enddate2)) {
                $datasetCnt++;
                $dataset[2] = [$begdate2, $enddate2];

                $recs2 = mr_oper::from('mr_opers as mro')
                    ->join('mchn_raids as mr', 'mr.id', 'mro.mr_id')
                    ->wherebetween('mr.wrkdate', [$begdate2, $enddate2])
                    ->whereIn('mr.active', [1])
                    ->whereraw($sc);


                if (isTblInGrps('m', $grps))
                    $recs2 = $recs2->leftjoin('machines as m', 'm.id', 'mr.machineid');

                if (isTblInGrps('o', $grps))
                    $recs2 = $recs2
                        ->leftjoin('orgs as o', 'o.id', 'mro.orgid');

                if (isTblInGrps('oo', $grps))
                    $recs2 = $recs2->leftjoin('orgs as oo', 'oo.id', 'mro.suporgid');

                if (isTblInGrps('so', $grps))
                    $recs2 = $recs2->leftjoin('orgs as so', 'so.id', 'mro.suporgid');

                if (isTblInGrps('p_l', $grps))
                    $recs2 = $recs2->leftjoin('org_places as p_l', 'p_l.id', 'mro.sup_placeid');

                if (isTblInGrps('p_u', $grps))
                    $recs2 = $recs2->leftjoin('org_places as p_u', 'p_u.id', 'mro.org_placeid');

                if (isTblInGrps('с', $grps))
                    $recs2 = $recs2
                        ->leftjoin('contracts as c', 'c.id', 'mro.contractid');

                if (isTblInGrps('mt', $grps)) {
                    // так как типы техники связаны через спр-к Техники, то подключим Технику
                    if (!isTblInGrps('m', $grps))
                        $recs2 = $recs2->leftjoin('machines as m', 'm.id', 'mr.machineid');

                    $recs2 = $recs2->leftjoin('mchntypes as mt', 'mt.id', 'm.mchntypeid');
                }

                if (isTblInGrps('c', $grps))
                    $recs2 = $recs2->leftjoin('contracts as c', 'c.id', 'mr.contractid');

                if (isTblInGrps('ds', $grps))
                    $recs2 = $recs2->leftjoin('orgstaff as ds', 'ds.id', 'mro.disp_staffid');

                if (isTblInGrps('ri', $grps))
                    $recs2 = $recs2->leftjoin('refitems as ri', 'ri.id', 'mro.refitmid');

                if (isTblInGrps('os', $grps))
                    $recs2 = $recs2->leftjoin('orgstaff as os', 'os.id', 'mr.driverid');

                if (isTblInGrps('pt', $grps))
                    $recs2 = $recs2->leftjoin('paytypes as pt', 'pt.id', 'mro.paytypeid');

                if (isTblInGrps('ot', $grps))
                    $recs2 = $recs2->leftjoin('opertypes as ot', 'ot.id', 'mr.opertypeid');

                //* Динамически подкючим группы - если нужно
                foreach ($grptypes as $gt) {
                    $tbl = 'grp' . $gt->id;
                    if (isTblInGrps($tbl, $grps))
                        $recs2 = $recs2->leftJoin(DB::raw('(select gi.objid, g.name as name
                        from grpitems as gi
                        join groups as g on g.id=gi.grpid and g.grptypeid=' . $gt->id
                            . ' where gi.sysobjid=111) ' . $tbl),
                            function ($join) use ($tbl) {
                                $join->on($tbl . '.objid', '=', 'mro.orgid');
                            });
                }

                $recs2 = $recs2->selectRaw('2 as dataset, sum(mr.mchnwrkhrs*1) as itmqty
                , sum(mro.itm_sum) itmsum
                , sum(mr.raid_qty) raid_qty
                , count(distinct mr.id) as docqty');


                foreach ($grps as $grp) {
                    if (isset($grp['show_val']))
                        $recs2 = $recs2->addSelect(DB::raw($grp['show_val'] . ' as ' . $grp['lbl']));
                    else
                        $recs2 = $recs2->addSelect(DB::raw($grp['fld'] . ' as ' . $grp['lbl']));

                    $recs2 = $recs2->groupby($grp['lbl']);
                }
            }


            $recs = mr_oper::from('mr_opers as mro')
                ->join('mchn_raids as mr', 'mr.id', 'mro.mr_id')
                ->wherebetween('mr.wrkdate', [$begdate1, $enddate1])
                ->whereIn('mro.active', [1])
                ->whereraw($sc);

            if (isTblInGrps('m', $grps))
                $recs = $recs->leftjoin('machines as m', 'm.id', 'mr.machineid');

            if (isTblInGrps('o', $grps))
                $recs = $recs->leftjoin('orgs as o', 'o.id', 'mro.orgid');

            if (isTblInGrps('oo', $grps))
                $recs = $recs->leftjoin('orgs as oo', 'oo.id', 'mro.suporgid');

            if (isTblInGrps('so', $grps))
                $recs = $recs->leftjoin('orgs as so', 'so.id', 'mro.suporgid');

            if (isTblInGrps('p_l', $grps))
                $recs = $recs->leftjoin('org_places as p_l', 'p_l.id', 'mro.sup_placeid');

            if (isTblInGrps('p_u', $grps))
                $recs = $recs->leftjoin('org_places as p_u', 'p_u.id', 'mro.org_placeid');

            if (isTblInGrps('ds', $grps))
                $recs = $recs->leftjoin('orgstaff as ds', 'ds.id', 'mro.disp_staffid');

            if (isTblInGrps('c', $grps))
                $recs = $recs->leftjoin('contracts as c', 'c.id', 'mro.contractid');

            if (isTblInGrps('mt', $grps)) {
                if (!isTblInGrps('m', $grps))
                    $recs = $recs->leftjoin('machines as m', 'm.id', 'mr.machineid');

                $recs = $recs->leftjoin('mchntypes as mt', 'mt.id', 'm.mchntypeid');
            }


            if (isTblInGrps('ri', $grps))
                $recs = $recs->leftjoin('refitems as ri', 'ri.id', 'mro.refitmid');

            if (isTblInGrps('os', $grps))
                $recs = $recs->leftjoin('orgstaff as os', 'os.id', 'mr.driverid');

            if (isTblInGrps('pt', $grps))
                $recs = $recs->leftjoin('paytypes as pt', 'pt.id', 'mro.paytypeid');

            if (isTblInGrps('ot', $grps))
                $recs = $recs->leftjoin('opertypes as ot', 'ot.id', 'mr.opertypeid');

            //Динамически подкючим группы - если нужно
            foreach ($grptypes as $gt) {
                $tbl = 'grp' . $gt->id;
                if (isTblInGrps($tbl, $grps))
                    $recs = $recs->leftJoin(DB::raw('(select gi.objid, g.name as name
                        from grpitems as gi
                        join groups as g on g.id=gi.grpid and g.grptypeid=' . $gt->id
                        . ' where gi.sysobjid=111) ' . $tbl),
                        function ($join) use ($tbl) {
                            $join->on($tbl . '.objid', '=', 'm.orgid');
                        });
            }

//            $recs = $recs->selectRaw('1 as dataset, sum(di.qty*ft.forsale) as itmqty, sum(di.qty*di.price*ft.forsale) itmsum
//                , count(distinct fd.id) as docqty');
            $recs = $recs->selectRaw('1 as dataset, sum(mr.mchnwrkhrs*1) as itmqty
                , sum(mro.itm_sum) itmsum
                , sum(mr.raid_qty) raid_qty
                , count(distinct mr.id) as docqty');

            foreach ($grps as $grp) {
                if (isset($grp['show_val']))
                    $recs = $recs->addSelect(DB::raw($grp['show_val'] . ' as ' . $grp['lbl']));
                else
                    $recs = $recs->addSelect(DB::raw($grp['fld'] . ' as ' . $grp['lbl']));

                $recs = $recs->groupby($grp['lbl']);
            }

            if (isset($begdate2) and isset($enddate2)) {
                $recs = $recs->union($recs2);
            }

            if ($datasetCnt == 1 and count($grps) == 1 and $ordbyItmSumDesc == 1)
                $recs = $recs->orderby('itmsum', 'desc');

            if ($datasetCnt == 1 and count($grps) == 1 and $ordbyDocQtyDesc == 1)
                $recs = $recs->orderby('raid_qty', 'desc');

            foreach ($grps as $grp) {
                $recs = $recs->orderby($grp['lbl']);
            }
            $recs = $recs->orderby('dataset');

            $recs = $recs->get();
//dd($recs);

            if (isset($s_minitmsum)) {
                //filter by minimal ItmSum
                $recs = $recs->filter(function ($item) use ($s_minitmsum) {
                    return $item->itmsum >= $s_minitmsum;
                })->values();
            }
            if (isset($s_mindocsum)) {
                //filter by minimal ItmSum
                $recs = $recs->filter(function ($item) use ($s_mindocsum) {
                    return $item->itmsum / $item->raid_qty >= $s_mindocsum;
                })->values();
            }
            if (1 == 0) {
                //filter by minimal Order's count
                $recs = $recs->filter(function ($item) {
                    return $item->raid_qty > 5;
                })->values();
            }

//            Event::dispatch(new docs1CLoadedEvent('code'
//                , 'user: ' . \Auth::user()->name
//                . ', period1: ' . $begdate1 . '-' . $enddate1
//                . ', period2: ' . $begdate2 . '-' . $enddate2));

//            event(new docs1CLoadedEvent('code'
//                , '2 user: ' . \Auth::user()->name
//                . ', period1: ' . $begdate1 . '-' . $enddate1
//                . ', period2: ' . $begdate2 . '-' . $enddate2));

        } else {
            //return (redirect()->back());
            $dataset = null;
            $datasetCnt = 0;
            $grps = null;
            $recs = null;
        }

        //todo: обновить счетчик использования отчета
        //
        report::updUseCnt($report_id, $userid);
        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен: ' . $conditions);


        return view('analitics.rep45', compact(['dataset', 'datasetCnt', 'recs', 'grps', 'data'
            , 'year'
            //, 'ownorgs', 'years', 'monthes', 'orggroups'
            , 'search_params'
            , 'aCnds', 'aGrps', 'GrpLst', 'conditions']));
    }

}
