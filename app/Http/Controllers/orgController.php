<?php

namespace App\Http\Controllers;

use App;
use App\buildobj;
use App\contract;
use App\machine;
use App\objflag;
use App\objlog;
use App\org;
use App\org_curator;
use App\org_name;
use App\orgdep;
use App\orgpost;
use App\org_saldo;
use App\orgitmdiscount;
use App\sysobj;
use App\Traits\Result;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\userorg;
use App\orgstaff;
use App\orgtaxsys;
use App\objextid;
use App\User;
use App\usrsysright;
use App\grptype;
use App\group;
use App\grpitem;
use App\eritm_offer;
use App\ri_sup_price;
use App\paydoc;
use Auth;
use Cache;
use DB;
use Illuminate\Http\Request;
use App\Http\Middleware\IStock;
use App\project;
use Illuminate\Support\Facades\Log;
use App\invoice;
use Symfony\Component\Panther\Client;
use Symfony\Component\Panther\DomCrawler\Crawler;


//20190404 SNS

class orgController extends Controller
{

    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 111;
        $this->sysobjcode = 'orgs';
        $this->objcode = $this->sysobjcode;
    }

//    public function __construct(IStock $stock)
//    {
//        $this->middleware('auth');
//        $this->sysobjid = 111;
//        $this->objcode = 'orgs';
//
//        $this->stock = $stock;
//    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create');
        $usrrights['load'] = usrsysright::isUserHasRightByCode_cached($userid, 'admin-global');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        $usrrights['org_saldos.read'] = false;
        $usrrights['org_saldos.create'] = false;
        $usrrights['org_acnts.read'] = false;
        $usrrights['org_acnts.create'] = false;
        $usrrights['orgdeps.read'] = false;
        $usrrights['orgdeps.create'] = false;
        $usrrights['link_tasks'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
            $usrrights['org_saldos.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_saldos.read');
            $usrrights['org_saldos.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_saldos.create');
            $usrrights['org_saldos.update'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_saldos.update');
            $usrrights['org_acnts.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_acnts.read');
            $usrrights['org_acnts.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_acnts.create');
            $usrrights['orgdeps.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'orgdeps.read');
            $usrrights['orgdeps.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'orgdeps.create');
            $usrrights['link_tasks'] = usrsysright::isUserHasRightByCode_cached($userid, 'tasks.create');
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
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight(-1);
        //Проверка прав пользователя.
        if (!$usrrights['read']) {
            return view('home');
        }


        session([$this->objcode . '_pageno' => $request->page]);

        // - параметры поиска -------------------------------------------------
        $s_name = "";
        $s_boss_name = "";
        $s_inn = "";
        $s_addr = "";
        $s_statuscode = "";
        $s_orggrpid = "";
        $s_flagtypeid = "";
        $s_rating = "";
        $s_curatorid = "";

        if ($request->isMethod('post')) {
            $s_name = $request->get("s_name");
            $s_boss_name = $request->get("s_boss_name");
            $s_inn = $request->get("s_inn");
            $s_addr = $request->get("s_addr");
            $s_statuscode = $request->get("s_statuscode");
            $s_orggrpid = $request->get("s_orggrpid");
            $s_flagtypeid = $request->get("s_flagtypeid");
            $s_rating = $request->get("s_rating");
            $s_curatorid = $request->get("s_curatorid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $this->objcode]);
            session(['search_params' => [
                's_name' => $s_name,
                '$s_boss_name' => $s_boss_name,
                's_inn' => $s_inn,
                's_addr' => $s_addr,
                's_statuscode' => $s_statuscode,
                's_orggrpid' => $s_orggrpid,
                's_flagtypeid' => $s_flagtypeid,
                's_rating' => $s_rating,
                's_curatorid' => $s_curatorid,
            ]]);
        } else {
            if (session('search_setname') == $this->objcode) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_name = $params['s_name'] ?? null;
                    $s_boss_name = $params['s_boss_name'] ?? null;
                    $s_inn = $params['s_inn'] ?? null;
                    $s_addr = $params['s_addr'] ?? null;
                    $s_statuscode = $params['s_statuscode'] ?? null;
                    $s_orggrpid = $params['s_orggrpid'] ?? null;
                    $s_flagtypeid = $params['s_flagtypeid'] ?? null;
                    $s_rating = $params['s_rating'] ?? null;
                    $s_curatorid = $params['s_curatorid'] ?? null;
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_name" => $s_name,
            "s_boss_name" => $s_boss_name,
            "s_inn" => $s_inn,
            "s_addr" => $s_addr,
            "s_statuscode" => $s_statuscode,
            "s_orggrpid" => $s_orggrpid,
            "s_flagtypeid" => $s_flagtypeid,
            "s_rating" => $s_rating,
            "s_curatorid" => $s_curatorid,
        ];

        $needSearch = false;
        foreach ($search_params as $p) {
            if (isset($p)) {
                $needSearch = true;
                break;
            }
        }

        //var_dump($search_params);

        $sc = "1=1 ";
        if ($needSearch) {
//            if (strlen($s_name) > 0) {
//                $sc = $sc . " and o.name like '%" . mb_strtoupper($s_name) . "%'";
//            }
            if (strlen($s_name) > 0) {

                $find = explode(" ", $s_name);

                if (count($find) > 0) {
                    $sc .= ' and (1=1';
                    foreach ($find as $f) {
                        $sc .= " and concat(o.name
                            ,' ',ifnull(o.fullname,' ')
                            ,' ',ifnull(o.inn,' ')
                            ,' ',ifnull(o.kpp,' ')
                            ,' ',ifnull(o.okpo,' ')
                            ,' ',ifnull(o.ogrn,' ')
                            ,' ',ifnull(o.ogrnip,' ')
                            ) like '%" . $f . "%'";
                    }
                    $sc .= ')';
                }
//                $sc .= ')';
            }

            if (strlen($s_boss_name) > 0) {
                $sc = $sc . " and o.boss_name like '%" . mb_strtoupper($s_boss_name) . "%'";
            }
            if (strlen($s_rating) > 0) {
                $sc = $sc . " and o.rating=" . $s_rating;
            }
            if (strlen($s_inn) > 0) {
                $sc = $sc . " and concat(o.inn,'/',o.kpp) like '" . mb_strtoupper($s_inn) . "%'";
            }
            if (strlen($s_addr) > 0) {
                $sc = $sc . " and o.address like '%" . mb_strtoupper($s_addr) . "%'";
            }
            if (strlen($s_statuscode) > 0) {
                if ($s_statuscode == "0") {
                    //не считаем свою компанию
                    $sc = $sc . " and o.id <> 1 and not exists ("
                        . "select 1 from org_curators c where c.orgid=o.id and c.active=1"
                        . " and c.begdt<=now() and ifnull(c.enddt,now())>=now()"
                        . ")";
                }
                if ($s_statuscode == "1") {
                    $sc = $sc . " and exists ("
                        . "select 1 from org_curators c where c.orgid=o.id and c.active=1"
                        . " and c.begdt<=now() and ifnull(c.enddt,now())>=now()"
                        . ")";
                }
                if ($s_statuscode == "2") {
                    //Я - куратор
                    $staffid = \Auth::user()->StaffID;
                    $sc = $sc . " and exists ("
                        . "select 1 from org_curators c where c.orgid=o.id and c.active=1"
                        . " and c.begdt<=now() and ifnull(c.enddt,now())>=now()"
                        . " and c.StaffID=" . $staffid
                        . ")";
                }
            }

//            if (isset($s_groupid))
            if (strlen($s_orggrpid) > 0)
                $sc .= " and exists(select 1 from grpitems gl where gl.sysobjid=111 and gl.objid=o.id and gl.grpid=" . $s_orggrpid . ')';

            if (strlen($s_flagtypeid) > 0)
                $sc .= " and exists(select 1 from objflags f where f.sysobjid=111 and f.objid=o.id and f.flagtypeid=" . $s_flagtypeid . ')';

            if (strlen($s_curatorid) > 0)
                if ($s_curatorid == '-1')
                    $sc .= " and not exists(select 1 from org_curators c where c.orgid=o.id and c.active=1)";
                else
                    $sc .= " and exists(select 1 from org_curators c where c.orgid=o.id and c.active=1 and c.staffid={$s_curatorid})";
        }
        // --------------------------------------------------------------------


        $recs = org::from('orgs as o')
            ->whereraw($sc)
            ->select('o.id', 'o.name', 'o.active', 'o.inn', 'o.kpp', 'o.okpo', 'o.ogrn', 'o.ogrnip'
                , 'o.address', 'o.boss_postname', 'o.boss_name'
                , 'o.main_activity'
                , DB::raw("(select group_concat(ft.name SEPARATOR '; ') from objflags as ff join flagtypes as ft on ft.id=ff.flagtypeid where ff.sysobjid=111 and ff.objid=o.id) as lstflags"))
            ->selectraw('
                (SELECT GROUP_CONCAT(
                    CONCAT(u.lname, " "
                    , if(!isnull(u.fname), concat(SUBSTR(u.fname, 1, 1), "."), "")
                    , if(!isnull(u.mname), concat(SUBSTR(u.mname, 1, 1), "."), "")
                    )
                SEPARATOR "; ")
                FROM org_curators AS c
                JOIN users AS u ON u.id = c.userid
                WHERE c.orgid=o.id and c.active = 1 and now() between c.begdt and ifnull(c.enddt,now())
                ORDER BY c.begdt) as lstCurator')
            ->selectRaw("(SELECT GROUP_CONCAT(concat(gt.name,': <b>',g.name,'</b>') SEPARATOR '<br>')
                FROM grpitems AS gi
                JOIN `groups` AS g ON g.id = gi.grpid
                join grptypes as gt on gt.id=g.grptypeid
                WHERE gi.objid=o.id and gi.sysobjid = 111
                ORDER BY gt.id) as lstGroups")
            ->selectraw('(select count(*) as cnt from objflags as f
            where f.sysobjid=111 and f.objid=o.id and f.flagtypeid=12) as ownmark');


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_orgs.index');

        $recs = $recs->orderBy('ownmark', 'desc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('o.name', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate(10);

//номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $s_statuscodes = array('' => '-все-'
        , '0' => 'Клиенты без куратора'
        , '1' => 'Клиенты с куратором'
        , '2' => 'Мои клиенты');
//        $s_statuscode = "";

        $orggroups = group::lstOrgGroups_cache();

        $usedflags = objflag::lstUsedFlagsForSysObj_cache(111);

        $ratings = [
            -1 => 'негативный',
            0 => 'нейтральный',
            +1 => 'позитивный',
        ];

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        //номер первой записи на странице:
        $data->rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data->search_params = $search_params;

        $data->sysobj = sysobj::find($this->sysobjid);

        $data->curators = orgstaff::lstFor(['in_org_curators_now' => 2]);

        return view($this->sysobjcode . '.index', compact('recs', 'data'
            , 'rec0', 's_statuscodes', 'ratings'
            , 'orggroups', 'usedflags'
            , 'search_params', 'sort_params'
            , 'usrrights'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($owngrp)
    {
        return $this->edit(-1);
    }


//Поиск
    public function search(Request $request)
    {
        $searchval = "";
        $searchinn = "";
        $s_address = "";
        $s_statuscode = "";

        if ($request->isMethod('post')) {
            $searchval = $request->get("searchval");
            $searchinn = $request->get("searchinn");
            $s_address = $request->get("s_address");
            $s_statuscode = $request->get("s_statuscode");

            //сохраним параметры поиска в сессии
            session(['search_setname' => "orgs"]);
            session(['search_params' => [
                'searchval' => $searchval,
                'searchinn' => $searchinn,
                's_address' => $s_address,
                's_statuscode' => $s_statuscode,
            ]]);
        } else {
            if (session('search_setname') == "orgs") {
                //dd(session('search_params'));
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $search_params = session('search_params');
                    $searchval = $search_params['searchval'];
                    $searchinn = $search_params['searchinn'];
                    $s_address = $search_params['s_address'];
                    $s_statuscode = $search_params['s_statuscode'];
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        //в поиске не задано ничего, уйдем на index
        if (strlen($searchval . $searchinn . $s_address . $s_statuscode) == 0)
            return redirect()->route($this->sysobjcode . '.index');

        $sc = "1=1";
        if (strlen($searchval) > 0) {
            $sc = $sc . " and name like '%" . mb_strtoupper($searchval) . "%'";
        }
        if (strlen($searchinn) > 0) {
            $sc = $sc . " and concat(inn,'|',kpp) like '%" . mb_strtoupper($searchinn) . "%'";
        }
        if (strlen($s_address) > 0) {
            $sc = $sc . " and address like '%" . mb_strtoupper($s_address) . "%'";
        }
        if (strlen($s_statuscode) > 0) {
            if ($s_statuscode == "0") {
                //не считаем свою компанию
                $sc = $sc . " and o.id <> 1 and not exists ("
                    . "select 1 from org_curators c where c.orgid=o.id and c.active=1"
                    . " and c.begdt<=now() and ifnull(c.enddt,now())>=now()"
                    . ")";
            }
            if ($s_statuscode == "1") {
                $sc = $sc . " and exists ("
                    . "select 1 from org_curators c where c.orgid=o.id and c.active=1"
                    . " and c.begdt<=now() and ifnull(c.enddt,now())>=now()"
                    . ")";
            }
            if ($s_statuscode == "2") {
                //Я - куратор
                $staffid = \Auth::user()->StaffID;
                $sc = $sc . " and exists ("
                    . "select 1 from org_curators c where c.orgid=o.id and c.active=1"
                    . " and c.begdt<=now() and ifnull(c.enddt,now())>=now()"
                    . " and c.StaffID=" . $staffid
                    . ")";
            }
        }


        $recs = org::from('orgs as o')
            ->whereRaw($sc)
            ->orderBy('name', 'asc')
            /*->toSql();*/
            ->paginate(5);

        $usrrights = $this->setInterfaceRight(-1);
        //dd($recs);
        $s_statuscodes = array('' => '-все-'
        , '0' => 'Клиенты без куратора'
        , '1' => 'Клиенты с куратором'
        , '2' => 'Мои клиенты');
        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
        return view($this->sysobjcode . '.index', compact('recs', 'rec0'
            , 's_statuscodes', 'searchval', 'searchinn',
            's_address', 's_statuscode', 'usrrights'));
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $org = org::find($id);
        return view($this->sysobjcode . '.show', compact('org'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            $rec = new org([
                'id' => -1,
                'kindid' => 1,
                'active' => 1,
                'created_by' => $userid,
            ]);
        } else
            $rec = org::find($id);

        if (!isset($rec))
            return redirect(route($this->sysobjcode . '.index'));

        $usrrights = $this->setInterfaceRight($id);
        $usrrights['org_places.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_places.read');
        $usrrights['org_places.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_places.read');

//        $rec->taxsysid = orgtaxsys::TaxSysIDByOrgID($rec->id);
//        $rec->taxsysid = [];
        $rec->taxsystems = App\taxsystem::activeTaxSystems();

        $rec->isownorg = org::isOwnOrg($id);
//        $rec->ownorgid = org::OwnOrgIDByOrgID($id);
        $rec->ownorgid = 1;  //упрощаем
        $rec->ownorgs = org::lstOwnOrgs();

        //$rec->flags = objflag::FlagTypesForOrg($rec->id);
        $rec->flags = objflag::FlagTypesForObj(111, $rec->id);
        //dd($rec->flags);

        $rec->kinds = org::$kinds;

        if ($rec->id <> -1) {
            $rec->org_saldos = org_saldo::lstSaldos_cached($rec->id);
        }

        $auxinfo = org::AuxInfo($rec);
        for ($x = 0; $x <= count($auxinfo) - 1; $x++) {
            if ($auxinfo[$x]["reccount"] > 0 and $usrrights['delete'])
                $usrrights['delete'] = false;
        }

        $rec->ratings = [
            -1 => 'негативный',
            0 => 'нейтральный',
            +1 => 'позитивный',
        ];

        $data = new \stdClass();
        $data->sysobjid = $this->sysobjid;
        //$data->sysobj = sysobj::find($this->sysobjid);

        $ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);

        $rec->deps = orgdep::getFor(['orgid' => $rec->id], ['id', 'name', 'mngr_name', 'active']);

        $rec->orgposts = orgdep::from('orgdeps as od')
            ->leftjoin('orgposts as op', 'op.depid', 'od.id')
            ->where(['od.orgid' => $rec->id])
            ->select(['od.id as depid', 'od.code', 'od.name as depname', 'op.id', 'op.name', 'op.stdlimunits', 'op.stdusedunits', 'op.active'])
            ->orderBy('od.ordr')
            ->orderBy('od.name')
            ->orderBy('op.ordr')
            ->orderBy('op.name')
            ->get();

        $rec->org_names = org_name::getFor(['orgid' => $rec->id], ['n.id', 'n.name', 'n.begdate', 'n.enddate', 'n.active', db::raw("ont.name as type_name")]);


//        $rec->paydocs = paydoc::where(['orgid' => $rec->id])
//            ->orderby('paydate', 'desc')
//            ->get();
        //dd($rec->paydocs);

        return view($this->sysobjcode . '.edit', compact('rec', 'data', 'auxinfo', "ObjFlags", "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $userid = \Auth::user()->id;

        $kindid = $request->get('kindid');

        if ($kindid == 2) {
            // ИП
            $messages = [
                'name.required' => 'Укажите ФИО',
                'inn.required' => 'Укажите ИНН',
                'inn.size' => 'ИНН должен состоять из 12 цифр',
                'name.max' => 'ФИО может быть максимум 120 символов',
            ];

            $rules = [
                "name" => "required|max:120",
                "inn" => "nullable|size:12",
            ];

        } elseif ($kindid == 3) {
            // Ф/Л
            $messages = [
                'name.required' => 'Укажите ФИО',
                'name.max' => 'ФИО может быть максимум 120 символов',
                'inn.size' => 'ИНН должен состоять из 12 цифр',
            ];

            $rules = [
                "name" => "required|max:120",
                "inn" => "nullable|size:12",
            ];

        } else {
            // Ю/Л
            $messages = [
                'name.required' => 'Укажите название контрагента',
                'inn.required' => 'Укажите ИНН',
                'kpp.required' => 'Укажите КПП',
                'ogrn.min' => 'ОГРН должен состоять из 13 цифр',
                'ogrnip.min' => 'ОГРНИП должен состоять из 15 цифр',
                'name.max' => 'Название контрагента - максимум 120 символов',
                'fullname.max' => 'Полное название - максимум 300 символов',
            ];

            $rules = [
                "name" => "required|max:120",
                "inn" => "required|string|min:10|max:12",
//            "kpp" => "nullable|min:9|max:9",
//            "kpp" => "required|min:9|max:9",
                "fullname" => "nullable|max:300",
                "ogrn" => "nullable|min:13|max:13",
                "ogrnip" => "nullable|min:15|max:15",
            ];
        }

        $request->validate($rules, $messages);


        $inn = $request->get('inn');
        $kpp = $request->get('kpp');

        $mess = "";
        if ($id == -1) {
            $rec = new org([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = org::find($id);
            $mess = "Запись обновлена";
        }


        //2021-10-11 Если не знаем ИНН организации (например гос. учреждения), то допускаем указывать 10 нулей: 0000000000
        if ($kindid == 1 and $inn != "0000000000") {

            //проверка на валидность ИНН по контрольной сумме
            $rules = [
                "ttt" => [
                    function ($attribute, $value, $fail) use ($inn) {
                        //
                        if (!snsTrait::is_valid_inn($inn)) {
                            $fail("Неверный ИНН (ошибка в контрольной сумме)!");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);


            //проверка на дубль ИНН/КПП.

            $prm = new \stdClass();
            $prm->id = $rec->id;
            $prm->inn = $inn;
            $prm->kpp = $kpp;
            $prm->kindid = $kindid;

            $rules = [
                //В форме должно быть поле ttt
                "ttt" => [
                    function ($attribute, $value, $fail) use ($prm) {
                        //
                        $cnt = org::where([
                            'inn' => $prm->inn,
                            'kpp' => $prm->kpp,
                            'kindid' => $prm->kindid,
                        ])
                            ->where('id', '<>', ($prm->id ?? -1))
                            ->count();
                        //dd($cnt);
                        if ($cnt > 0) {
                            $fail("Организация с такими реквизитами  ИНН/КПП уже зарегистрирована!");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);
        }

        $rec->kindid = $kindid;
        $rec->name = mb_substr($request->get('name'), 0, 120);
        $rec->fullname = mb_substr($request->get('fullname'), 0, 300);
        $rec->inn = $inn;
        $rec->kpp = $kpp;
        if ($kindid == 1) {
            $rec->okpo = mb_substr($request->get('okpo'), 0, 8);
            $rec->ogrn = $request->get('ogrn');
        }
        if ($kindid == 2) {
            $rec->ogrnip = $request->get('ogrnip');
        }

        $rec->taxsysid = $request->get('taxsysid');
        $rec->address = mb_substr($request->get('address'), 0, 160);
        $rec->iddoc_info = mb_substr($request->get('iddoc_info'), 0, 160);
        $rec->phone = mb_substr($request->get('phone'), 0, 20);
        $rec->email = mb_substr($request->get('email'), 0, 36);
        $rec->boss_postname = mb_substr($request->get('boss_postname'), 0, 120);
        $rec->boss_name = mb_substr($request->get('boss_name'), 0, 60);
        $rec->boss_fullname = mb_substr($request->get('boss_fullname'), 0, 90);
        $rec->ca_postname = mb_substr($request->get('ca_postname'), 0, 120);
        $rec->ca_name = mb_substr($request->get('ca_name'), 0, 60);
        $rec->ca_fullname = mb_substr($request->get('ca_fullname'), 0, 90);
        //$rec->bank_account_info = mb_substr($request->get('bank_account_info'), 0, 300);
        $rec->main_activity = mb_substr($request->get('main_activity'), 0, 300);
        $rec->notes = mb_substr($request->get('notes'), 0, 300);
        $rec->begdate = $request->get('begdate');
        $rec->enddate = $request->get('enddate');
        $rec->active = $request->get('active', 0);
        $rec->rating = $request->get('rating');
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        if ($id == -1) {
            //для новых организаций часть данных возбмем с портала "За Честный Бизнес"
            //org::import_honest_business($rec->id);    //2021-10-11 Заблокировал, так как поменялся формат данных на странице - нужно менять парсер
        }

        $ownorgid = $request->get('ownorgid');
        org::SetOwnOrgIDforOrgID($rec->id, $ownorgid);

        $taxsysid = $request->get('taxsysid');
        if (1 == 0) orgtaxsys::SetTaxSysIDforOrgID($rec->id, $taxsysid);

//        if ($request->get('isownorg') == 1)
//            objflag::AddObjFlag($this->sysobjid, $rec->id, 12);

        // Сохранение флагов --------------------------------------------------------------------
        $setflags = $request->get('flagid');
        $allflags = $request->get('lstflags');
        $allflags = isset($allflags) ? substr($allflags, 1) : '';
        if (isset($allflags)) {
            $allflags = explode(',', $allflags);

            foreach ($allflags as $flagid) {
                if (isset($setflags[$flagid])) {
                    objflag::AddObjFlag($this->sysobjid, $rec->id, $flagid);
                } else {
                    objflag::DelObjFlag($this->sysobjid, $rec->id, $flagid);
                }
            }
            //dd(1);
        }
        // --------------------------------------------------------------------------------------

        //connectify('success', $rec->name, $mess);
        if ($id == -1) {
            return redirect(route($this->objcode . '.edit', $rec->id));
        } else {
            $pageno = session($this->objcode . '_pageno');
            return redirect(route($this->objcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        $res = org::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route($this->sysobjcode . '.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'], $res->msg);
        } else {

            //todo:: сделать удаление по ключу
            objflag::where('sysobjid', $this->sysobjid)->where('objid', $id)->delete();

            $sd['success'] = 'Запись о контрагенте (' . $id . ': '
                . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $pageno = session($this->objcode . '_pageno');
            $route = route($this->objcode . '.index') . '?page=' . $pageno;
            connectify('success', $res->obj['name'], 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    /**
     * Список скидок для заданного контрагента
     * 20190406 SNS
     */
    public
    function org_discounts($id)
    {
        //
        $rec = org::find($id);
        $discount = orgitmdiscount::getListDiscountInfo4Org($rec->id);
        return view($this->sysobjcode . '.org_discounts', compact('org', 'discount'));
    }

    /**
     * Список представителей заданного контрагента
     */
    public
    function org_users($id)
    {
        $org = org::find($id);
        $stafflist = userorg::from('userorgs as uo')
            ->join('users as u', 'u.id', 'uo.userid')
            ->where('uo.orgid', $org->id)
            ->select('uo.id', 'u.name', 'u.email', 'u.phone')
            ->get();
        return view($this->sysobjcode . '.org_users', compact('org', 'stafflist'));
    }

    /**
     * Список сотрудников заданного контрагента
     * 20190406 SNS
     */
    public
    function org_staff($id)
    {
        $org = org::find($id);
        $stafflist = orgstaff::getOrgStaffList($org->id);
        return view($this->sysobjcode . '.org_staff', compact('org', 'stafflist'));
    }

    /**
     * Список кураторов заданного контрагента
     * 20190406 SNS
     */
    public
    function org_curators($id)
    {
        $org = org::find($id);
        $curators = org_curator::OrgCuratorList($org->id);
        return view($this->sysobjcode . '.org_curators', compact('org', 'curators'));
    }

    public
    function org_supoffers($id)
    {
        $org = org::find($id);
        $list = eritm_offer::getOrgSupOfrList($org->id);
        return view($this->sysobjcode . '.org_supoffers', compact('org', 'list'));
    }

    public
    function org_extids($id)
    {
        $org = org::findOrFail($id);

        $recs = objextid::where('sysobjid', $this->sysobjid)
            ->where('objid', $id)
            ->with('extsys')->get();
        return view($this->sysobjcode . '.org_extids', compact('org', 'recs'));
    }

    /**
     * Список товаров, предлагаемых контрагентом
     * 20211108 SNS
     */
    public
    function org_ri_prices($orgid)
    {
        $userid = \Auth::user()->id;
        $org = org::find($orgid);
        $recs = ri_sup_price::getFor(['orgid' => $org->id
                //, 'on_date' => today()->format('Y-m-d')
            ]
            , ['rop.id', 'ri.name', 'rop.price', 'ri.unit', 'rop.active', 'rop.begdate', 'rop.enddate',
                'ri.itmtypeid', 'it.name as itmtypename',
                'op.name as place_name', 'rop.placeid'
            ]
            , [
                ['op.name', 'asc'],
                ['rop.placeid', 'asc'],
                ['it.ordr', 'asc'],
                ['it.name', 'asc'],
                ['ri.itmtypeid', 'asc'],
                ['ri.name', 'asc'],
                ['rop.refitmid', 'asc'],
                ['rop.begdate', 'asc']
            ]
        );

        //код системы по которой определяются права
        $acl_sysobjcode = sysobj::acl_sysobjcode('ri_sup_prices');

        $usrrights = [];
        $usrrights['ri_sup_prices.create'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.create');

        return view($this->sysobjcode . '.org_ri_prices', compact('org', 'recs', 'usrrights'));
    }


    public
    function list(Request $request)
    {
        //dd($request->isMethod('post'));
        return $this->search_low($request, $this->sysobjcode . '.list', false);
    }

    public function search_low(Request $request, $blade_name, $skip_null)
    {
//        $usrrights = $this->setInterfaceRight(-2);

        $search_name = "";
        $s_inn = "";
        //dd($request,$request->isMethod('put'));

        if ($request->isMethod('post')) {
            //снесем концевые пробелы
            $search_name = mb_ereg_replace("(^\s+)|(\s+$)/", "",
                $request->get("search_name"));
            $s_inn = mb_ereg_replace("(^\s+)|(\s+$)/", "",
                $request->get("s_inn"));

            //сохраним параметры поиска в сессии
            session(['search_setname' => "orgs"]);
            session(['search_params' => [
                'search_name' => $search_name,
                's_inn' => $s_inn,
            ]]);

        } else {
            if (session('search_setname') == "orgs") {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $search_params = session('search_params');
                    $search_name = $search_params['search_name'];
                    $s_inn = $search_params['s_inn'];
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        //Если параметры поиска не заданы, то уйдем на index
        if ($skip_null and strlen($search_name
                . $s_inn) == 0) return redirect()->route($this->sysobjcode . '.index');

        $sc = "1=1";
        if (strlen($search_name) > 0) {

            $find = explode(" ", $search_name);

            if (count($find) > 0) {
                $search = " ";
                foreach ($find as $f) {
                    $search .= " and name like '%" . mb_strtoupper($f) . "%'";
                }
                $sc .= $search;
            }
        }

        if (strlen($s_inn) > 0) {
            $sc = $sc . " and inn like '" . ($s_inn) . "%'";
        }

        $items = org::select('id', 'name', 'inn', 'kpp', 'active')
            ->whereRaw($sc)
            ->orderBy('name', 'asc')
            ->paginate(10);

        //номер первой записи на странице:
        $rec0 = $items->currentPage() * $items->perPage() - $items->perPage() + 1;
        return view($blade_name, compact('items', 'rec0'
            , 'search_name', 's_inn'));
    }


    //получение информации о контрагенте (только id, имя, инн, кпп)
    public function getshortinfo(Request $request)
    {
        $id = $request->get('id');
        $ref = org::select('id', 'name', 'inn', 'kpp')
            ->where('id', $id)
            ->first();

        $result = array('id' => null, 'name' => null);
        if (!is_null($ref)) {
            $result['id'] = $ref->id;
            $result['name'] = $ref->name;
            $result['inn'] = $ref->inn;
            $result['kpp'] = $ref->kpp;
        }
        return response()->json($result);
    }


    public function org_groups_edit($orgid)
    {
        $userid = \Auth::user()->id;
        $org = org::find($orgid);

        $usrrights = $this->setInterfaceRight($orgid);
        $usrrights['delete'] = false;

        $recs = Cache::remember('org_gt_groups_.' . $orgid, now()->addMinutes(15)
            , function () use ($orgid) {
                return grptype::from('grptypes as t')
                    ->SELECT('g.grptypeid', 't.name as typename', 'g.id as grpid', 'g.name as grpname', 'i.objid')
                    ->join('groups as g', 'g.grptypeid', '=', 't.id')
                    ->leftJoin('grpitems as i', function ($j) use ($orgid) {
                        $j->on('i.grpid', '=', 'g.id')
                            ->where('i.sysobjid', $this->sysobjid)
                            ->where('i.objid', $orgid);
                    })
                    ->where('t.forsysobjid', $this->sysobjid)
                    ->orderby('t.ordr')
                    ->orderby('t.name')
                    ->orderby('g.ordr')
                    ->orderBy('g.name')
                    ->get()->toArray();
            });

        $gt_groups = [];
        $curGrpTypeID = null;
        $curGrpTypeName = null;
        $groups = [];
        $value = null;
        foreach ($recs as $rec) {
            if ($rec['grptypeid'] <> $curGrpTypeID) {
                if (isset($curGrpTypeID)) {
                    $gt_groups[] = [
                        'id' => $curGrpTypeID,
                        'name' => $curGrpTypeName,
                        'groups' => $groups,
                        'value' => $value,
                    ];

                }
                $curGrpTypeID = $rec['grptypeid'];
                $curGrpTypeName = $rec['typename'];
                $groups = [];
                $value = null;
            }
//            $groups[] = [$rec['grpid'] => $rec['grpname']];   //Добавляет лишний уровень
            $groups += [$rec['grpid'] => $rec['grpname']];

            $value = (!isset($value) and $rec['objid']) ? $rec['grpid'] : $value;
        }
        if (isset($curGrpTypeID)) {
            $gt_groups[] = [
                'id' => $curGrpTypeID,
                'name' => $curGrpTypeName,
                'groups' => $groups,
                'value' => $value,
            ];

        }
        //dd($gt_groups);
        $org->gt_groups = $gt_groups;

        return view($this->sysobjcode . '.org_groups_edit', compact('org', "usrrights"));
    }

    public function org_groups_update(Request $request, $orgid)
    {
        //$userid = \Auth::user()->id;
        $mess = '';
        $orgname = org::select('name')->findOrFail($orgid)->name;

        $gt_vals = $request->gt;
        foreach ($gt_vals as $grptypeid => $grpid) {
            grpitem::addOrRmvItem($grptypeid, $grpid, $this->sysobjid, $orgid, $orgname);
        }

        return redirect(route($this->sysobjcode . '.index') . '?page=' . session($this->objcode . '_pageno'))
            ->with('success', $mess);
    }


    static public function info_params(Request $request)
    {
        $result = "";
        try {
            $orgid = $request->orgid;
            $org = org::find($orgid);

            //$result = array('address' => $org->address, 'opertypes' => $buildopertypes);
            $result = $org;

        } catch (\Exception $e) {
        }
        return response()->json($result);

    }


    static public function listprojects(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $orgid = $request->orgid;
            $list = project::from('projects as p')
                //->whereRaw("exists (select 1 from org_projs as op where op.projid=p.id and op.active and op.orgid=?)", [$orgid])
                ->where('active', 1)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()->pluck('name', 'id')->toArray();

            $result = array('projects' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);
    }

    static public function listorgs_m15tgt(Request $request)
    {
        $result = "";
        try {
            $srcorgid = $request->srcorgid;

            $list = org::from('orgs as o')
                ->whereRaw("o.id in (
                            SELECT distinct m15tgtorgid
                            from equiprqst_items as eri where m15srcorgid={$srcorgid})"
                )
                //->active()
                ->select('id', 'name')
                //->orderby('ordr')
                ->orderby('name')
                ->get()->pluck('name', 'id')->toArray();

            $result = array('orgs' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);
    }

    static public function listorgs_for00(Request $request)
    {
        //2021-02-17 SNS. Заготовка. Не доделано

        $result = "";
        try {

            $sc = '1=1';

            $upd_ownorgid = $request->upd_ownorgid;
            if (isset($upd_ownorgid))
                $sc .= " and exists (select 1 from invoices as inv where inv.doctypeid=2 and inv.ownorgid={$upd_ownorgid} )";


            $list = org::from('orgs as o')
                ->whereRaw($sc)
                ->select('id', 'name')
                //->orderby('ordr')
                ->orderby('name')
                ->get()->pluck('name', 'id')->toArray();

            $result = array('orgs' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);
    }

    static public function list_for(Request $request)
    {
        //2021-02-25 SNS. Обертка для вызова org::lstFor

        $result = "";
        try {

            $list = org::lstFor([
                'equiprsts_worker' => $request->equiprsts_worker,
                'buildobjid' => $request->buildobjid,
                'er_buildobjid' => $request->er_buildobjid,
                'buildobj_sup_not_dlvrd' => $request->buildobj_sup_not_dlvrd,
                'buildobj_ownorg_not_dlvrd' => $request->buildobj_ownorg_not_dlvrd,
                'org_ownorg_not_dlvrd' => $request->org_ownorg_not_dlvrd,
                'inv_ownorgid_by_orgid' => $request->inv_ownorgid_by_orgid,
                'upd_orgid_by_ownorgid' => $request->upd_orgid_by_ownorgid,
                'upd_orgid' => $request->upd_orgid,
                'in_budgets_for_buildobjid' => $request->in_budgets_for_buildobjid,
            ]);


            $result = array('orgs' => $list);

        } catch (\Exception $e) {
            Log::error('orgs::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }


    static public function updfrm_zachestnyibiznes($orgid)
    {
        $result = org::import_honest_business($orgid);

        return redirect(route('orgs.edit', $orgid))->with($result);
    }

    static public function tst_panther()
    {


        $url = "https://shopee.co.id/shop/127192295/search";
        $client = Client::createChromeClient(base_path("drivers/chromedriver"), null, ["port" => 9558]);    // create a chrome client

        $crawler = $client->request('GET', $url);
        dd($crawler);
        $client->waitFor('.shopee-page-controller');                                         // wait for the element with this css class until appear in DOM
        echo $crawler->filter('.shopee-page-controller')->text();
    }

    static public function tst_panther0()
    {


//        require __DIR__.'/vendor/autoload.php'; // Composer's autoloader

        $client = Client::createChromeClient();
        // Or, if you care about the open web and prefer to use Firefox
        //$client = Client::createFirefoxClient();

        $client->request('GET', 'https://api-platform.com'); // Yes, this website is 100% written in JavaScript
        $client->clickLink('Get started');

        // Wait for an element to be present in the DOM (even if hidden)
        $crawler = $client->waitFor('#installing-the-framework');
        // Alternatively, wait for an element to be visible
        //$crawler = $client->waitForVisibility('#installing-the-framework');

        echo $crawler->filter('#installing-the-framework')->text();
        $client->takeScreenshot('screen.png'); // Yeah, screenshot!
    }

    static public function list_for_ac(Request $request)
    {
        //2021-11-07 SNS. Для автокомплита

        $result = "";
        try {

            $list = org::getFor([
                'name' => $request->name,
                'name_inn' => $request->name_inn,
                'flagtypeid' => $request->flagtypeid,
                'active' => $request->active ?? 1,
                'in_ri_sup_prices' => $request->in_ri_sup_prices,
                'in_mr_opers_orgid' => $request->in_mr_opers_orgid,
                'in_mr_opers_with_suporgid' => $request->in_mr_opers_with_suporgid,
                'in_mr_opers_orgid_with_wrkdate_ge' => $request->in_mr_opers_orgid_with_wrkdate_ge,
                'in_mr_opers_orgid_with_wrkdate_le' => $request->in_mr_opers_orgid_with_wrkdate_le,
            ],
                ['o.id', 'o.name', 'o.inn', 'o.kpp']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('org::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }

    public function load()
    {
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight(-1);
        $rec = new \stdClass();
        $rec->title = 'Импорт записей о контрагентах';

        return view($this->sysobjcode . '.load', compact('rec', "usrrights"));
    }

    public function import(Request $request)
    {
        //Импорт без сохранения файла на диск. Только обработка

        $messages = [
            'doc.required' => 'Не указан файл с данными',
        ];

        $rules = [
            "doc" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        //$returl = $request->get('retroute');

        $usrrights = $this->setInterfaceRight(-1);
        $result = new Result();
        $rec = new \stdClass();

        $rec->extsysid = 9;   // ? М.б. использовать для связывания по кодам во внешней системе
        //dd($rec);

        if ($request->hasfile('doc')) {

            $file = $request->doc;

            $filesize = $file->getSize();
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            //dd($name, $extension, $filesize);

            if (1 == 1)
                $rec = org::import_001($file, $rec);
            else {
                $result->err = 1;
                $result->msg = 'Не определена процедура импорта!';
            }
            //--------------------------------------------------------------------------------
            //dd($result->msg);


        } else {
            $result->err = 1;
            $result->msg = 'Файл с данными не загружен!';
        }

        return view($this->sysobjcode . '.load', compact('rec', "usrrights"));
    }


}
