<?php

namespace App\Http\Controllers;

use App\ac;
use App\budget;
use App\buildobj;
use App\buildopertype;
use App\contract;
use App\contract_category;
use App\contract_exe;
use App\contract_price;
use App\contract_regnum;
use App\contract_review;
use App\contract_workplan;
use App\contractrole;
use App\doctype;
use App\document;
use App\eventtype;
use App\invoice;
use App\obj_finoper;
use App\obj_link;
use App\obj_org;
use App\obj_reader;
use App\obj_staff;
use App\objflag;
use App\objlog;
use App\objtag;
use App\org;
use App\contracttype;
use App\contract_org;
use App\org_extservice;
use App\orgplnpay_item;
use App\paydoc;
use App\regnum_src;
use App\report;
use App\sysobj;
use App\User;
use App\user_notice;
use App\user_template;
use App\userorg;
use App\usrsysright;

use App\Jobs\SendNotify;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class ContractController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 151;
        $this->objcode = 'contracts';
        $this->sysobjcode = 'contracts';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        if (\Auth::user()->active == 0)
            return view('home');

        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['updregnum'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['contract_workplans.create'] = false;
        $usrrights['obj_staffs.create'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
            //2021-08-10
            $usrrights['updregnum'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.updregnum');
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
            $usrrights['updregnum'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.updregnum');

            $usrrights['contract_workplans.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'contract_workplans.create');

            $usrrights['contract_exes.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'contract_exes.read');
            $usrrights['contract_exes.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'contract_exes.create');

            $usrrights['contract_reviews.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'contracts.read');
            $usrrights['contract_reviews.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'contracts.create');
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
        if (\Auth::user()->active == 0)
            return view('home');

        $userid = \Auth::user()->id;

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.save'),
        );


        //2020-09-28 Меняем концепцию - если у пользователя нет прав на чтение (ВСЕХ записей), то здесь не блокируем,
        //а смотрим дальше по месту - есть ли он в списке читателей для каждого договора

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 15
            , 's_ownorgid' => ''
            , 's_orgname' => ''
            , 's_name' => ''
            , 's_tag' => ''
            , 's_tag_type' => ''
            , 's_tag_val' => ''
            , 's_categoryid' => ''
            , 's_typeid' => ''
            , 's_docnum' => ''
            , 's_docdate' => ''
            , 's_begdocdate' => ''
            , 's_enddocdate' => ''
            , 's_regnum' => ''
            , 's_regnumstatus' => ''
            , 's_end_at' => ''
            , 's_statusid' => ''
            , 's_orggrpid' => ''
            , 's_flagtypeid' => ''
            , 's_buildobjid' => ''
            , 's_file_doctypeid' => ''
            , 's_new4me' => ''
            , 's_inituserid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);
//dd($search_params);

        //сформируем условие запроса в БД -----
        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, указанной в записи о типе документа
        $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=c.acsid and uac.userid={$userid})";


        //Если пользователь не имеет базового права на чтение, то доступ будет предоставлен если пользователь включен в список читателей
        //$usrrights['read'] = false;
        if (!$usrrights['read']) {
            $sc .= ' and exists (select 1 from obj_readers r where r.sysobjid=' . $this->sysobjid . ' and objid=c.id and userid=' . $userid . ')';
        }

        $need_search = false;

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;

                if ($item == 's_ownorgid') {
                    $sc = $sc . " and c.ownorgid = '{$val}'";

                } elseif ($item == 's_orgname') {
                    // $sc = $sc . " and o.name like '%" . mb_strtoupper($s_orgname) . "%'";
                    $sc = $sc . " and exists (select 1 from contract_orgs as co
                            join orgs as coo on coo.id=co.orgid  where co.contractid=c.id ";

                    //" and coo.name like '%" . mb_strtoupper($s_orgname) . "%')";

                    $find = explode(" ", $val);

                    if (count($find) > 0) {
                        $sc .= ' and (1=1';
                        foreach ($find as $f) {
                            $sc .= " and coo.name like '%" . $f . "%'";
                        }
                        $sc .= ')';
                    }
                    $sc .= ')';

                } elseif ($item == 's_name') {
                    $sc = $sc . " and concat(ifnull(c.name,' '),' ', ifnull(c.descript,' '),' ', ifnull(c.outline,' '),' ', ifnull(c.notes,' '))  like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_tag') {
                    $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid=151 and objid=c.id and ot.tag='" . mb_strtoupper($val) . "')";

                } elseif ($item == 's_tag_type') {
                    $tval = mb_strtoupper($val);
                    $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid={$this->sysobjid}
                     and objid=c.id and ( ot.type='{$tval}' or (ot.type is null and ot.tag='{$tval}')))";

                } elseif ($item == 's_tag_val') {
                    $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid={$this->sysobjid}
                     and objid=c.id and ot.val like '%" . mb_strtoupper($val) . "%')";

                } elseif ($item == 's_buildobjid') {
                    $sc = $sc . " and exists (select 1 from obj_links as ol where ol.sysobjid=151 and objid=c.id and ol.lnksysobjid=466 and ol.lnkobjid={$val})";

                } elseif ($item == 's_file_doctypeid') {
                    $sc = $sc . " and exists (select 1 from objfiles as f where f.sysobjid=151 and f.objid=c.id and f.doctypeid={$val})";

                } elseif ($item == 's_docnum') {
                    $find = explode(" ", $val);
                    if (count($find) > 0) {
                        $sc .= ' and (1=1';
                        foreach ($find as $f) {
                            $sc .= " and c.docnum like '%" . $f . "%'";
                        }
                        $sc .= ')';
                    }

                } elseif ($item == 's_regnumstatus') {
                    if ($val == 0)
                        $sc = $sc . " and c.regnum is null";
                    else
                        $sc = $sc . " and c.regnum is not null";

                } elseif ($item == 's_regnum') {
                    if ($val == '*')
                        $sc = $sc . " and c.regnum is not null";
                    else
                        $sc = $sc . " and cast(substr(c.regnum,2) as decimal) = '{$val}'";

                } elseif ($item == 's_docdate') {
                    $sc = $sc . " and c.docdate = '{$val}'";

                } elseif ($item == 's_begdocdate') {
                    $sc = $sc . " and c.docdate >= '{$val}'";

                } elseif ($item == 's_enddocdate') {
                    $sc = $sc . " and c.docdate <= '{$val}'";

                } elseif ($item == 's_statusid') {
                    $sc = $sc . " and c.statusid = '{$val}'";

                } elseif ($item == 's_categoryid') {
                    $sc = $sc . " and c.categoryid = {$val}";

                } elseif ($item == 's_typeid') {
                    $sc = $sc . " and c.contracttypeid = '{$val}'";

                } elseif ($item == 's_end_at') {
                    $sc = $sc . " and date_add(curdate(), interval {$val} day)>=ifnull(c.enddate,'3333-01-01')";

                } elseif ($item == 's_orggrpid') {
                    $sc .= " and exists(select 1 from grpitems gl where gl.sysobjid=" . $this->sysobjid
                        . " and gl.objid=c.id and gl.grpid={$val})";

                } elseif ($item == 's_flagtypeid') {
                    $sc .= " and exists(select 1 from objflags f where f.sysobjid=" . $this->sysobjid
                        . " and f.objid=c.id and f.flagtypeid={$val})";

                } elseif ($item == 's_new4me') {
                    if ($val == 0)
                        //уже открывал
//                        $sc .= " and not exists(select 1 from obj_readers r where r.sysobjid={$this->sysobjid}
//                             and r.objid=c.id and r.userid={$userid} and r.firstread_at is null )";
                        $sc .= " and exists(select 1 from obj_readers r where r.sysobjid={$this->sysobjid}
                             and r.objid=c.id and r.userid={$userid} and r.firstread_at is not null )";
                    elseif ($val == 1) {
                        //пользователь есть в списке доступа, но еще не открывал
//                        $sc .= " and exists(select 1 from obj_readers r where r.sysobjid={$this->sysobjid}
//                             and r.objid=c.id and r.userid={$userid} and r.firstread_at is null )";

                        //присутствие в списке доступа - не важно, главное что пользователь еще не открывал этот договор
                        $sc .= " and not exists(select 1 from obj_readers r where r.sysobjid={$this->sysobjid}
                             and r.objid=c.id and r.userid={$userid} and r.firstread_at is not null )";
                    }

                } elseif ($item == 's_inituserid') {
                    $sc .= " and exists(select 1 from obj_readers r where r.sysobjid={$this->sysobjid} and r.objid=c.id
                        and r.roletypeid=1 and r.userid={$val} )";

                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------

        //по-старому ---------------
        //для совместимости со старым методом формированя условия отбора - инициализируем переменные поиска
        if (1 == 0) {
            foreach ($search_params as $item => $val) {
                $$item = $val;
            }
        }

        // --------------------------------------------------------------------

        if ($need_search) {
            $recs = contract::from('contracts as c')
                ->leftJoin('orgs as oo', function ($j) {
                    $j->on('oo.id', 'c.ownorgid');
                })
                ->leftJoin('orgs as o', function ($j) {
                    $j->on('o.id', 'c.orgid');
                })
                ->leftJoin('contracttypes as t', function ($j) {
                    $j->on('t.id', 'c.contracttypeid');
                })
                ->leftJoin('contract_categories as cc', function ($j) {
                    $j->on('cc.id', 'c.categoryid');
                })
                ->whereraw($sc)
                ->select('c.id', 'c.name', 'c.descript', 'c.active', 'c.regnum', 'c.regnum_num'
                    , 'c.docnum', 'c.docdate', 'c.docsum'
                    , 'c.begdate', 'c.enddate'
                    , 'c.categoryid', 'cc.name as categoryname'
                    , 't.name as contracttypename'
                    , 'c.statusid', 'c.status_notes'
                    , 'c.ownorgid', 'oo.name as ownorgname'
                    , 'o.name as orgname'
                    , db::raw("(select group_concat(concat(cr.name,': ', coo.name ) SEPARATOR '; ')
                    from contract_orgs as co
                    join orgs as coo on coo.id=co.orgid
                    join contractroles as cr on cr.id=co.roleid
                    where co.contractid=c.id and co.orgid<>c.ownorgid) as controrg_lst")

                    , db::raw("(SELECT GROUP_CONCAT(dt.name SEPARATOR '; ')
                    FROM objfiles AS f
                    JOIN doctypes AS dt ON dt.id = f.doctypeid
                    WHERE f.sysobjid=151 and f.objid=c.id
                    ORDER BY f.id) as lstImageDocs")
                )
                ->with('tags');

//        ->selectraw('
//                (SELECT GROUP_CONCAT(concat(co.rolename,\': \', ifnull( o2.name,\' - любая компания -\'))
//                 SEPARATOR "; ")
//                FROM contract_orgs AS co
//                left JOIN orgs AS o2 ON o2.id = co.orgid
//                WHERE co.contractid=c.id
//                ORDER BY co.id) as lstContrOrgs');


//Базовая сортировка ---------------------------------------------
            $recs = $recs->orderBy('oo.name', 'asc')->orderby('c.ownorgid');
//----------------------------------------------------------------

//Сортировка пользователя ----------------------------------------
//session()->put('sort_params_' . $this->sysobjcode . '.index', []);

            $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

            if (isset($sort_params)) {
                foreach ($sort_params as $prm)
                    //$recs = $recs->orderBy($prm['field'], $prm['dir']);
                    $recs = $recs->orderByRaw($prm['field'] . " " . $prm['dir']);
            } else {
                //$recs = $recs->orderByRaw("convert(substr(c.regnum,2), signed integer) asc");
                //$recs = $recs->orderByRaw("cast(substr(c.regnum,2) as unsigned) asc");
                //$recs = $recs->orderBy('c.regnum_num', 'asc');
                $recs = $recs->orderBy('c.id', 'asc');
            }
//----------------------------------------------------------------

            $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);

            //номер первой записи на странице:
            $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        } else {
            $recs = null;
            $rec0 = null;
            $sort_params = null;
        }

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid);


        $objgroups = []; //group::lstOrgGroups_cache();

        $data->regnumstatuses = [0 => 'не присвоен', 1 => 'присвоен'];

        $data->file_doctypes = doctype::lstUsedForSysObj(151);

        $usedflags = objflag::lstUsedFlagsForSysObj_cache($this->sysobjid);

        $usedtypes = contracttype::usedTypes();

        //Cache::forget("tagtypes_{$this->sysobjid}");
        $data->tagtypes =
            Cache::remember("tagtypes_{$this->sysobjid}", now()->addMinutes(5)
                , function () {
                    return objtag::from('objtags as tg')
                        ->where('sysobjid', $this->sysobjid)
                        //->whereNotNull('type')
                        ->selectRaw("ifnull(tg.type,tg.tag) as type")
                        ->distinct()
                        ->orderByRaw("ifnull(tg.type,tg.tag) asc")
                        ->get()
                        ->pluck('type', 'type')
                        ->toArray();
                });

        $usedtags = contract::usedTags();
        //$usedbuildobjs = contract::usedBuildObjs();
        $usedbuildobjs = [];

        $data->user_all_contract_cnt = Cache::remember('user_all_contract_cnt_' . $userid, now()->addMinutes(15)
            , function () use ($usrrights, $userid) {
                if ($usrrights['read'])
                    return 1;
                else
                    return obj_reader::where([
                        'sysobjid' => $this->sysobjid,
                        'userid' => $userid
                    ])
                        ->count();
            });

        $data->new4me = [0 => 'видел', 1 => 'не видел'];

        //список инициаторов - для поиска
        $data->initusers = user::lstFor([
            //'in_contract_initiators' => 1,
            'obj_readers_roletypeid' => [151, 1],
        ]);

        //Выясним - есть ли у пользователя шаблон для этого типа объектов ИС
        $data->template_id = user_template::where(['sysobjid' => $this->sysobjid, 'userid' => $userid])->first()->id ?? null;


        $usedownorgs = Cache::remember('contract_usedownorgs', now()->addMinutes(5)
            , function () {
                return org::from('orgs as o')
                    ->whereraw(" exists(select 1 from contracts as c where c.ownorgid=o.id)")
                    ->get()->pluck('name', 'id')->toArray();
            });

        $usedcategories = contract_category::lstActive();

        $end_variants = [
            0 => 'истек',
            30 => 'истечет в ближайшие 30 дней',
            60 => 'истечет в ближайшие 60 дней',
            90 => 'истечет в ближайшие 90 дней',
            120 => 'истечет в ближайшие 120 дней',
        ];
        $statuses = contract::statuses();

        return view('contracts.index', compact(
            'recs', 'rec0'
            , 'objgroups', 'usedflags', 'usedtypes', 'usedownorgs', 'usedcategories', 'statuses', 'end_variants'
            , 'usedtags', 'usedbuildobjs'
            , 'data'
            , 'search_params', 'sort_params'
            , 'usrrights'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create()
    {
        if (\Auth::user()->active == 0)
            return view('home');

        return $this->edit(-1);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit($id)
    {
        if (\Auth::user()->active == 0)
            return view('home');

        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $newData = [];

            $tmplt = user_template::getTemplate($userid, $this->sysobjid);
            if (isset($tmplt->contract)) {
                $newData = (array)$tmplt->contract; //конвертируем в массив
            }

            //Добавим свои значения
            $newData['id'] = -1;
            $newData['active'] = 1;
            $newData['created_by'] = \Auth::user()->id;


            $rec = new contract($newData);

            $rec->tags_lst = $newData['tags'] ?? '';

        } else
            $rec = contract::from('contracts as c')
                ->where('id', $id)
                ->whereRaw("exists (select 1 from user_acs as uac where uac.acsid=c.acsid and uac.userid={$userid})")
                ->first();


        if (!isset($rec))
            return redirect(route($this->sysobjcode . '.index'));

        $rec->sysobjid = $this->sysobjid;

        $usrrights = $this->setInterfaceRight($rec->id);

        //если пользователь не имеет базового права на чтение договоров, то проверим - есть ли у него доступ конкретно к этому договру
        //$usrrights['read'] = false;
        if (!$usrrights['read']) {
            $cnt = obj_reader::where('sysobjid', $this->sysobjid)
                    ->where('objid', $rec->id)
                    ->where('userid', $userid)->count() ?? 0;
            if ($cnt == 0)
                return redirect(route($this->sysobjcode . '.index'));
        }

        //право на добавление персонала по договору
        $usrrights['obj_staffs.create'] = (isset($rec->sysobjid) and $usrrights['save']);


        //преобразуем для нормальной работы <INPUT TYPE="DATE"...
        //$contract->begdate = strftime('%Y-%m-%dT%H:%M:%S', strtotime($contract->begdate));
        if (isset($rec->begdate))
            $rec->begdate = strftime('%Y-%m-%d', strtotime($rec->begdate));
        if (isset($rec->enddate))
            $rec->enddate = strftime('%Y-%m-%d', strtotime($rec->enddate));


        $rec->buildobjid = obj_link::getFirstLnkId($this->sysobjid, $id, 466);
        $rec->buildobjname = '-не задан-';
        if (isset($rec->buildobjid)) {
            $rec->buildobjname = buildobj::find($rec->buildobjid)
                    ->name ?? '-не задан-';
        }

        $rec->roletypes = contractrole::list_for_contracttypeid($rec->contracttypeid);
        $rec->ownorgroletypeid = contract_org::where(['contractid' => $rec->id, 'orgid' => $rec->ownorgid])
                ->select('roleid')->first()->roleid ?? null;
        $rec->orgroletypeid = contract_org::where(['contractid' => $rec->id, 'orgid' => $rec->orgid])
                ->select('roleid')->first()->roleid ?? null;

        $rec->flag_184 = objflag::IsSetObjFlag($this->sysobjid, $rec->id, 184);

        //$usrrights['updregnum'] = (isset($rec->regnum)) ? false : $usrrights['updregnum'];

        $rec->contracttypes = contracttype::lstTypes();

        $rec->ownorgs = org::lstOwnOrgs();

        //todo: заменить на выбор контрагентов из группы в зависимости от выбранного типа договора
        $rec->orgs = org::lstBuyers();

        $rec->categories = contract_category::lstActive();

        $rec->acs = ac::lstFor(['active_or_current' => $rec->acsid]);

        $rec->statuses = contract::statuses();

        $rec->contract_orgs = contract_org::contract_orgs($id);

        $rec->contract_prices = contract_price::listPricesForContract($id);

        $rec->bills = invoice::where([
            'contractid' => $rec->id,
            'doctypeid' => 1,
        ])
            ->select('id', 'docdate', 'docsum', db::raw("concat('№',docnum,' от ', docdate) as name"))
            ->orderBy('docdate', 'desc')
            ->get();

        if (1 == 0) {
            $rec->pays = orgplnpay_item::where([
                'contractid' => $rec->id,
            ])
                ->select('id', 'reason', 'plnpaysum', 'fctpaysum', 'fctpay_at')
                ->orderBy('fctpay_at', 'desc')
                ->get();
        }

        if (1 == 1) {
            $rec->paydocs = paydoc::where([
                'contractid' => $rec->id,
            ])
                ->select('id', 'paydate', 'paydir', 'paysum', 'docnum', 'doсdate', 'reason')
                ->orderBy('paydate', 'desc')
                ->get();
        }

        if (1 == 0) {
            $rec->contract_reviews = contract_review::where('contractid', $rec->id)
                ->orderby('id', 'desc')
                ->get();
        }

        //$usrrights = $this->setInterfaceRight($id);
        //право изменения категории доступа
        $usrrights['acs.edit'] = User::user_has_acs_cached($userid, $rec->acsid);

        //dd($contract->files());

        $ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);

        if ($id <> -1)
            //для создаваемой записи функция затирает значение из шаблона
            $rec->tags_lst = objtag::lstTags($this->sysobjid, $id);


        if (1 == 0) {
            $rec->doc_templates = [
                0 => 'не типовой',
                1 => 'типовой',
            ];

            $rec->buildobjs = buildobj::lstActive();

            $rec->budgets = budget::from('budgets as b')
                ->where('par_contractid', $id)
                ->select('b.*'
                    , db::raw("(select sum(estdocsum) from budget_items as bi where bi.budgetid=b.id) as estdocsum")
                )
                ->get();

            //Планы работ по контракту
            $rec->contract_workplans = contract_workplan::from('contract_workplans as wp')
                ->join('buildopertypes as bot', 'bot.id', 'wp.buildopertypeid')
                ->where([
                    'wp.contractid' => $rec->id,
                ])
                ->select('wp.*', 'bot.name as buildopertypename'
                    , db::raw("(select min(ifnull(fctbegdt,ifnull(estbegdt,plnbegdt))) from cwp_works as w where w.cwp_id=wp.id) as min_begdt")
                    , db::raw("(select max(ifnull(fctenddt,ifnull(estenddt,plnenddt))) from cwp_works as w where w.cwp_id=wp.id) as max_enddt")
                )
                ->orderby('buildopertypename')
                ->orderby('wp.id')
                ->get();

            //Исполнение по контракту
            $rec->contract_exes = contract_exe::from('contract_exes as ce')
                ->join('buildopertypes as bot', 'bot.id', 'ce.buildopertypeid')
                ->where([
                    'ce.contractid' => $rec->id,
                ])
                ->select('ce.*', 'bot.name as buildopertypename')
                ->orderby('buildopertypename')
                ->orderby('ce.docdate')
                ->get();

            //Сервисы по договору org_extservices ---------------
            $rec->org_extservices = org_extservice::getFor([
                'contractid' => $rec->id,
            ], [
                'id', 'name', 'rest_sum'
            ]);
            //---------------------------------------------------

        }

        //кандидаты для опозитного договора
        $rec->opposite_docs = [];
        if ($rec->id <> -1 and org::isOwnOrg($rec->orgid)) {

            $rec->opposite_docs = contract::where([
                'docnum' => $rec->docnum,
                'docdate' => $rec->docdate,
                'ownorgid' => $rec->orgid,
                'orgid' => $rec->ownorgid,
            ])
                ->select('id', db::raw("concat(name,' №',docnum,' от ', docdate) as tname"))
                ->get()
                ->pluck('tname', 'id')
                ->toArray();
            //dd($rec->opposite_contracts);
        }

        //Источнки № регистрации ----------------------------
        $rec->regnum_srcs = regnum_src::lstFor([
            'ownorgid' => $rec->ownorgid,
            'categoryid' => $rec->categoryid,
        ]);
        //dd($rec->ownorgid, $rec->categoryid, $rec->regnum_srcs);
        //---------------------------------------------------

        //обновим статистику открытий для данного пользователя
        obj_reader::addOrUpdateStat($this->sysobjid, $rec->id, $userid);

        if ($rec->id <> -1) {
            //Действия НЕ для новой записи --------------------------------------------------------

            //уберем уведомления о необходимости ознакомления
            //dd($this->sysobjid * 1000000 + $rec->id, route($this->sysobjcode . '.edit', $rec->id));
            user_notice::Remove($this->sysobjid * 1000000 + $rec->id
                , route($this->sysobjcode . '.edit', $rec->id)
                , $userid);


            //Выясним - есть ли шаблон для этого типа объектов ИС
            $rec->template_id = user_template::where(['sysobjid' => $this->sysobjid, 'userid' => $userid])->first()->id ?? null;


            if ($userid == 121 or 1==1)
                $rec->finopers = obj_finoper::from('obj_finopers as fo')
                    ->join('orgs as s_o', 's_o.id', 'fo.srcorgid')
                    ->join('orgs as t_o', 't_o.id', 'fo.tgtorgid')
                    ->leftjoin('opertypes as ot', 'ot.id', 'fo.opertypeid')
                    ->where('fo.contractid', $rec->id)
                    ->select('fo.*'
                        , 's_o.name as srcorg_name'
                        , 't_o.name as tgtorg_name'
                        , 'ot.name as opertype_name'
                    )
                    ->orderBy('fo.operdate')
                    ->get();

            //-------------------------------------------------------------------------------------
        }

        $data = new \stdClass();
        $data->sysobjid = $this->sysobjid;

        //Зачистим связанные кэшированные данные ----------------------------------------
        Cache::forget('informer_new_contracts_' . $userid);
        //-------------------------------------------------------------------------------

        return view('contracts.edit', compact('rec', 'data', "ObjFlags", "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        //
        if (\Auth::user()->active == 0)
            return view('home');


        $messages = [
            'categoryid.required' => 'Укажите категорию договора',
            'contracttypeid.required' => 'Укажите тип договора',
            'docnum.required' => 'Укажите номер договора',
            'docdate.required' => 'Укажите дату договора',
            'descript.required' => 'Опишите предмет договора',
            'ownorgid.required' => 'Укажите компанию со стороны холдинга',
            'orgid.required' => 'Укажите контрагента',
            'statusid.required' => 'Укажите текущий статус договора/документа',
            'acs.required' => 'Укажите категорию информации (для доступа)',
            'regnum_srcid.required_with' => 'Когда указан номер регистрации, то необходимо указать источник номеров',
        ];

        $rules = [
            "categoryid" => "required",
            "contracttypeid" => "required",
            "docnum" => "required",
            "docdate" => "required",
            "descript" => "required",
            "ownorgid" => "required",
            "orgid" => "required",
            "statusid" => "required",
            'regnum_srcid' => 'required_with:regnum_num'
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $mess = "";
        if ($id == -1) {
            $rec = new contract([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = contract::find($id);
            $mess = "Запись обновлена";
        }

        //защита от действий пользователя без прав
        $usrrights = $this->setInterfaceRight($rec->id);

        if (!$usrrights['save'] ?? false)
            return redirect(route($this->sysobjcode . '.index'));


        if (1 == 1) {

            //проверка на дубль ownorgid/orgid/docnum.
            $ownorgid = $request->get('ownorgid');
            $orgid = $request->get('orgid');
            $docnum = $request->get('docnum');
            $docdate = $request->get('docdate');
            $categoryid = $request->get('categoryid');

            $prm = new \stdClass();
            $prm->id = $rec->id;
            $prm->ownorgid = $ownorgid;
            $prm->orgid = $orgid;
            $prm->docnum = $docnum;
            $prm->docdate = $docdate;
            $prm->categoryid = $categoryid;
            $prm->regnum = $request->get('regnum');

            $rules = [
                //В форме должно быть поле ttt
                "ttt" => [
                    function ($attribute, $value, $fail) use ($prm) {
                        //
                        $cnt = contract::where([
                            'ownorgid' => $prm->ownorgid,
                            'orgid' => $prm->orgid,
                            'docnum' => $prm->docnum,
                            'docdate' => $prm->docdate,
                            'categoryid' => $prm->categoryid,
                        ])
                            ->where('id', '<>', ($prm->id ?? -1))
                            ->count();
                        //dd($cnt);
                        if ($cnt > 0) {
                            $fail("Договор с такими реквизитами 'Сторона 1/Сторона 2/Дата/Номер/Категория' уже существует!");
                        }
                    },
                ],
//                "tt2" => [
//                    function ($attribute, $value, $fail) use ($prm) {
//                        //
//                        if (isset($prm->regnum)) {
//
//                            $cnt = contract::where([
//                                'ownorgid' => $prm->ownorgid,
//                                'regnum' => $prm->regnum,
//                            ])
//                                ->where('id', '<>', ($prm->id ?? -1))
//                                ->count();
//                            //dd($cnt);
//                            if ($cnt > 0) {
//                                $fail("Договор с таким регистрационным номером (" . $prm->regnum . ") уже зарегистрирован!");
//                            }
//                        }
//                    },
//                ],

            ];

            $request->validate($rules, $messages);
        }

        if (1 == 1) {

            //проверка на дубль regnum_srcid/regnum_num
            $regnum_srcid = $request->get('regnum_srcid');
            $regnum_num = $request->get('regnum_num');

            if (isset($regnum_num)) {
                $prm = new \stdClass();
                $prm->id = $rec->id;
                $prm->regnum_srcid = $regnum_srcid;
                $prm->regnum_num = $regnum_num;

                $rules = [
                    //В форме должно быть поле ttt
                    "ttt" => [
                        function ($attribute, $value, $fail) use ($prm) {
                            //
                            $cnt = contract::where([
                                'regnum_srcid' => $prm->regnum_srcid,
                                'regnum_num' => $prm->regnum_num,
                            ])
                                ->where('id', '<>', ($prm->id ?? -1))
                                ->count();
                            //dd($cnt);
                            if ($cnt > 0) {
                                $fail("Договор с таким же номером регистрации {$prm->regnum_num} уже зарегистрирован!");
                            }
                        },
                    ],

                ];

                $request->validate($rules, $messages);
            }
        }

        $rec->categoryid = $request->get('categoryid');
        $rec->contracttypeid = $request->get('contracttypeid');

        if ($usrrights['updregnum'] ?? false)
            $rec->regnum = $request->get('regnum');

        $rec->name = mb_substr($request->get('name'), 0, 210);
        $rec->docnum = $request->get('docnum');
        $rec->docdate = $request->get('docdate');
        $rec->docsum = $request->get('docsum');
        $rec->advance_pcnt = $request->get('advance_pcnt');
        $rec->advance_sum = $request->get('advance_sum');
        $rec->begdate = $request->get('begdate') ?? $rec->docdate;
        $rec->enddate = $request->get('enddate');
        $rec->descript = mb_substr($request->get('descript'), 0, 300);
        $rec->notes = mb_substr($request->get('notes'), 0, 65535);
        $rec->outline = $request->get('outline');
        $rec->ownorgid = $request->get('ownorgid');
        $rec->orgid = $request->get('orgid');
        $rec->statusid = $request->get('statusid');
        $rec->status_notes = mb_substr($request->get('status_notes'), 0, 160);
        $rec->opposite_docid = $request->get('opposite_docid');
        $rec->doc_templateid = $request->get('doc_templateid');
        //$rec->active = $request->get('active', 0);
        $rec->active = ($rec->statusid == 0) ? 0 : 1;
        $rec->acsid = 1; // упрощение (временное) //$request->get('acsid', 1) ?? 1;    //1-public

        $rec->regnum_srcid = $request->get('regnum_srcid'); //источник номеров регистрации
        //$rec->regnum_code = ($rec->category->code ?? '') . '-' . ($rec->regnum_src->code ?? '');
        $rec->regnum_num = $request->get('regnum_num'); //чистый номер регистрации

        $tnum = (int)$rec->regnum_num;
        $tnum = ($tnum < 1000) ? str_pad($tnum, 3, '0', STR_PAD_LEFT) : $tnum;

        //$rec->regnum = $rec->regnum_src->num_prefix . $rec->regnum_num . $rec->regnum_src->num_suffix;
        $rec->regnum = $rec->regnum_src->num_prefix . $tnum . $rec->regnum_src->num_suffix;

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        //$rec->name = mb_substr('№' . $rec->docnum . ' от ' . $rec->docdate . ' ' . $rec->descript, 0, 120);
        $rec->save();
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        connectify('success', $rec->name, $mess);

        //add or update ownorg to contract_orgs on party/side = 1
        contract_org::addOrg2Party($rec->id, $rec->ownorgid, 1);
        //add or update org to contract_orgs on party/side = 2
        contract_org::addOrg2Party($rec->id, $rec->orgid, 2);


        // Сохранение ролей сторон --------------------------------------------------------------
        //2021-02-09 SNS. Промежуточный вариант - сохранять сразу и иметь возможность указания произвольного названия роли
        $ownorgroletypeid = $request->get('ownorgroletypeid');
        if (isset($ownorgroletypeid)) {
            contract_org::where(['contractid' => $rec->id, 'orgid' => $rec->ownorgid])
                ->update(['roleid' => $ownorgroletypeid]);
        }
        $orgroletypeid = $request->get('orgroletypeid');
        if (isset($orgroletypeid))
            contract_org::where(['contractid' => $rec->id, 'orgid' => $rec->orgid])
                ->update(['roleid' => $orgroletypeid]);
        //---------------------------------------------------------------------------------------

        // Присвоение регистрационного номера контракта -----------------------------------------
        if ($request->get('set_regnum') == 1 and !isset($rec->regnum)) {

            $rec->regnum = contract_regnum::get_regnum($rec->ownorgid, $rec->categoryid);
            if (isset($rec->regnum))
                $rec->save();
        }
        // --------------------------------------------------------------------------------------

        //Сохранение признаков ------------------------------------------------------------------
        $flag_184 = $request->get('flag_184', 0);
        if ($flag_184 == 1)
            objflag::UpdObjFlag($this->sysobjid, $rec->id, 184, $userid);
        else
            objflag::DelObjFlag($this->sysobjid, $rec->id, 184);
        //dd($flag_184);
        // --------------------------------------------------------------------------------------

        if ($id == -1) {
            obj_reader::addOrUpdate($this->sysobjid, $rec->id, $userid, 0, 5);  //5-регистратор
        }

        // добавить в обязательные читатели руководителей и главных бухгалтеров предприятий - сторон договора -------
        //dd($rec->ownorg->boss->userid);
        obj_reader::addOrUpdate($this->sysobjid, $rec->id, $rec->ownorg->boss->userid, 1);
        obj_reader::addOrUpdate($this->sysobjid, $rec->id, $rec->ownorg->ca->userid, 1);
        obj_reader::addOrUpdate($this->sysobjid, $rec->id, $rec->org->boss->userid, 1);
        obj_reader::addOrUpdate($this->sysobjid, $rec->id, $rec->org->ca->userid, 1);

        //2021-01-15 в userorgs добавили поле acs_contracts. если = 1, то также добавлять доступ к договорам этим пользователям-представителям
        $users = userorg::where(['active' => 1, 'acs_contracts' => 1])
            ->whereRaw("begdt <= now() and ifnull(enddt,now())>=now() and orgid in (?,?)", [$rec->ownorgid, $rec->orgid])
            ->select('userid')
            ->get();
        //dd($users);
        foreach ($users as $user) {
            obj_reader::addOrUpdate($this->sysobjid, $rec->id, $user->userid, 1);
        }
        // ----------------------------------------------------------------------------------------------------------

        // Сохранение тэгов -----------------------------------------------------------------
        objtag::attach($this->sysobjid, $rec->id, $request->get('tags'));
        //-----------------------------------------------------------------------------------

        // Привязка к объекту ---------------------------------------------------------------
        $buildobjid = $request->get('buildobjid');
        obj_link::addOrUpdateSingle($this->sysobjid, $rec->id, 466, $buildobjid);
        //-----------------------------------------------------------------------------------

        //если заполнен id оппозитного документа, то обновим обратную связь -----------------
        if (isset($rec->opposite_docid)) {
            contract::where('id', $rec->opposite_docid)->update(['opposite_docid' => $rec->id]);

            obj_link::addOrUpdateSingleWithParams($this->sysobjid, $rec->id, $this->sysobjid, $rec->opposite_docid
                , ['name' => $rec->info, 'show_url' => route('contracts.edit', $rec->opposite_docid)]);
            obj_link::addOrUpdateSingleWithParams($this->sysobjid, $rec->opposite_docid, $this->sysobjid, $rec->id
                , ['name' => contract::find($rec->opposite_docid)->info, 'show_url' => route('contracts.edit', $rec->id)]);
        }
        //-----------------------------------------------------------------------------------


        //для новой записи возьмем значения из шаблона ---------------------------------------------------
        if ($id == -1) {

            $tmplt = user_template::getTemplate($userid, $this->sysobjid);
            if (isset($tmplt->contract)) {

                //извлечем данные о сторонах(участниках) документа
                $orgs = $tmplt->contract_orgs;
                foreach ($orgs as $org) {
                    contract_org::addOrUpdate(['contractid' => $rec->id, 'orgid' => $org->orgid], (array)$org);
                }

                //извлечем данные о подписантах
                foreach ($tmplt->obj_staffs as $staff) {
                    //obj_staff::addWithRole($this->sysobjid, $rec->id, $staff->staffid, $staff->roletypeid);
                    obj_staff::add($this->sysobjid, $rec->id, $staff);
                }
            }
        }//----------------------------------------------------------------------------------------------


//        if ($id == -1) {
        return redirect(route($this->sysobjcode . '.edit', $rec->id));
//        } else {
//            $pageno = session($this->sysobjcode . '_pageno');
//            return redirect(route($this->sysobjcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
//        }
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
        if (\Auth::user()->active == 0)
            return view('home');

        $res = contract::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('contracts.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'], $res->msg);
        } else {
            $sd['success'] = 'Запись о договоре (' . $id . ': '
                . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $pageno = session($this->sysobjcode . '_pageno');
            $route = route($this->sysobjcode . '.index') . '?page=' . $pageno;
            connectify('success', $res->obj['name'], 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    static public function listcontracts(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $ownorgid = $request->ownorgid;
            $orgid = $request->orgid;
            $list = contract::from("contracts as c")
                ->join("orgs as oo", 'oo.id', "c.ownorgid")
                ->where('c.ownorgid', $ownorgid);
            if (isset($orgid))
                $list = $list->where('c.orgid', $orgid);

            $list = $list->where('c.active', 1)
                ->select('c.id', db::raw("concat(c.docnum,' ', c.docdate, ' ', c.name) as tname"))
                ->orderBy('tname')
                ->get()->pluck('tname', 'id')->toArray();

            $result = array('contracts' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);

    }

    static public function fill_regnum(Request $request)
    {
        //для AJAX-запросов заполнения номера для заданного контракта

        $result = "";
        try {
            $contractid = $request->contractid;
            if (isset($contractid)) {
                $contract = contract::select('ownorgid', 'categoryid')->find($contractid);

                if (isset($contract) and isset($contract->ownorgid) and isset($contract->categoryid)) {
                    $itm = contract_regnum::from('contract_regnums as rn')
                        ->where('rn.orgid', $contract->ownorgid)
                        ->where('rn.categoryid', $contract->categoryid)
                        ->lockForUpdate()
                        ->first();
                    //dd($itm, isset($itm));

                    if (!isset($itm)) {
                        //dd($contract,isset($contract));
                        $itm = contract_regnum::create([
                            'orgid' => $contract->ownorgid,
                            'categoryid' => $contract->categoryid,
                            'regnum' => 1,
                        ]);
                        //dd(122, $itm);
                    }
                    if (isset($itm)) {

                        $result = array('regnum' => contract_category::find($contract->categoryid)->code
                            . $itm->regnum);
                        $itm->regnum++;
                        $itm->save();
                    }
                    //dd(response()->json($result));
                }
            }

        } catch (\Exception $e) {
            throw new \Exception ($e->getMessage());
        }

        return response()->json($result);

    }


    static public function fill_regnums($ownorgid)
    { //Первичная заливка рег. номеров
        $userid = \Auth::user()->id;

        if (isset($ownorgid) and $userid == 12) {
            $startnums = [1 => 51, 2 => 51, 3 => 51, 4 => 51];


            $categories = contract_category::where('active', 1)
                ->select('id', 'code')->get();
            //dd($categories);

            foreach ($categories as $category) {

                if (isset($category->id)) {

                    $lst = contract::where('ownorgid', $ownorgid)
                        ->where('categoryid', $category->id)
                        ->orderby('docdate')
                        ->orderby('docnum')
                        ->get();
                    //dd($ownorgid,$categoryid);
                    if (isset($lst) and count($lst) > 0) {

                        $nxtnum = $startnums[$category->id] ?? 1001;
                        foreach ($lst as $itm) {
                            //$itm->regnum = $category->code . str_pad($nxtnum, 3, '0', STR_PAD_LEFT );
                            $itm->regnum = $category->code . $nxtnum;
                            $itm->save();

                            $nxtnum++;
                        }
                    }
                }

            }
        }

        return redirect(route('contracts.index'));
    }


    public
    function notify_mustreaders(Request $request, $id)
    {

        $userid = \Auth::user()->id;

        $ret_url = route($this->sysobjcode . '.edit', $id);    //для возврата
        $ref_url = route($this->sysobjcode . '.edit', $id);  // для уведомления

        //objlog::log_info($this->sysobjid, $id, 'Запрос отправки уведомления' . $ret_url, 5);

        $eventtypecode = $this->sysobjcode . '.mustread';
        $event = eventtype::where('code', $eventtypecode)->first();

        if (isset($event) and $event->active) {

            $eventtypeid = $event->id;  //1895;
            $eventtypeid = $this->sysobjid * 1000000 + $id; //2020-11-11 SNS. По такому принципу формируется eventid в ObjReaderrController@update
            //когда неизвестны подродбности родительской записи

            $rcpts = obj_reader::from('obj_readers as r')
                ->join('users as u', 'u.id', 'r.userid')
                ->where('u.active', 1)//только незаблокированным пользователям
                ->where('sysobjid', $this->sysobjid)
                ->where('objid', $id)
                ->where('mustread', 1)//обязательное прочтение
                ->whereNull('firstread_at')// тем, кто еще не заходил/ не открывал
                ->select('r.userid', 'u.lname', 'u.fname', 'u.mname', 'u.email')
                ->get();
//        dd($rcpts);

            if (count($rcpts) > 0) {

                //сформируем сообщение
                $obj = contract::find($id);

                $subj = "Уведомление о документе (" . $obj->name . ': ' . $obj->docnum . ' / ' . $obj->docdate . ")";


                $lstrcpts = ''; //список персон которым отправлено уведомление
                foreach ($rcpts as $rcpt) {
                    if (isset($rcpt)) {
                        $email = $rcpt->email;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                            //для журнала сформируем список получателей
                            $lstrcpts .= ' ' . $rcpt->lname
                                . ' ' . mb_substr($rcpt->fname, 0, 1) . '.'
                                . mb_substr($rcpt->mname, 0, 1) . '. (' . $email . ');';

                            //$email = 'shevchenko.s@basko.su';
                            //$email = 'snsusa02@gmail.com';

                            $msg = "Здравствуйте, " . $rcpt->fname . " " . $rcpt->mname . "!"
                                . "<br>"
                                . "<br>Вам необходимо ознакомиться с документом: <b>" . $obj->info . "</b>"
                                . "<br><hr>"
                                . " <a href='" . $ref_url . "'>Перейти к документу</a>";
                            //dd($subj, $msg);
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        }

                        if (isset($rcpt->userid)) {

                            $subj = "Новый документ";
                            $msg = $obj->info;
                            user_notice::addOrUpdate($eventtypeid, $ref_url, $rcpt->userid, $subj, $msg, now(), null, $userid);
                        }
                    }
                }

                objlog::log_info($this->sysobjid, $id, 'Уведомление о необходимости ознакомления отправлено: ' . $lstrcpts, 5);
//            dd($meeting, $rcpts, $lstrcpts);
            } else {
                objlog::log_info($this->sysobjid, $id, 'Нет кандидатов для отправки уведомления - либо уже есть отметка об ознакомлении, либо у участника не указана ЭП', 3);
            }


            //убрать из списка уведомлений те, что относятся к данному документу и где пользователь уже ознакомился
            $rcpts = obj_reader::from('obj_readers as r')
                ->where('sysobjid', $eventtypeid)
                ->where('objid', $id)
                ->where('mustread', 1)//обязательное прочтение
                ->whereNotNull('firstread_at')// тем, кто еще не заходил/ не открывал
                ->select('r.userid')
                ->get();

            if (count($rcpts) > 0) {
                foreach ($rcpts as $rcpt) {
                    user_notice::Remove($eventtypeid, $ref_url, $rcpt->userid);
                }
            }

        }

        return redirect($ret_url);
    }

    static public function list_for_buildopertypeid(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $buildopertypeid = $request->buildopertypeid;
            if (isset($buildopertypeid)) {

                $list = contract::list_orgcontracts_for_buildopertypeid($buildopertypeid);

                $result = array('orgcontracts' => $list);
            } else $result = null;


        } catch (\Exception $e) {
        }
        return response()->json($result);

    }


    public function rep23_(Request $request)
    {//Реестр договоров, отобранных по условиям

        $report_id = 23;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данной информацией!']);

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep" . $report_id;
        $s_contractid = "";
        $s_buildopertypeid = "";
        $s_orgid = "";
        $s_bdgtorgid = "";
        $s_budgetid = "";
        $s_begdate = "";
        $s_enddate = "";
        $s_itmname = "";

        if ($request->isMethod('post')) {

            $s_contractid = $request->get("s_contractid");
            $s_buildopertypeid = $request->get("s_buildopertypeid");
//            $s_begdate = $request->get("s_begdate");
//            $s_enddate = $request->get("s_enddate");
//            $s_orgid = $request->get("s_orgid");
//            $s_bdgtorgid = $request->get("s_bdgtorgid");
//            $s_budgetid = $request->get("s_budgetid");
//            $s_itmname = $request->get("s_itmname");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_contractid' => $s_contractid,
                's_buildopertypeid' => $s_buildopertypeid,
//                's_begdate' => $s_begdate,
//                's_enddate' => $s_enddate,
//                's_orgid' => $s_orgid,
//                's_bdgtorgid' => $s_bdgtorgid,
//                's_budgetid' => $s_budgetid,
//                's_itmname' => $s_itmname,
            ]]);
        } else {
            $s_contractid = $request->input("contractid");

            if (session('search_setname') == $search_setname) {
                if (1 == 0 and !empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');

                    $s_contractid = $params['s_contractid'];
                    $s_buildopertypeid = $params['s_buildopertypeid'] ?? null;
                    //dd($s_contractid);
//                    $s_begdate = $params['s_begdate'];
//                    $s_enddate = $params['s_enddate'];
//                    $s_orgid = $params['s_orgid'];
//                    $s_bdgtorgid = $params['s_bdgtorgid'];
//                    $s_budgetid = $params['s_budgetid'];
//                    $s_itmname = $params['s_itmname'];
                } else {
                    // Значения по-умолчанию --------------------------
                    $s_contractid = $request->input("contractid");
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
            }
        }

        $search_params = [
            "s_contractid" => $s_contractid,
            "s_buildopertypeid" => $s_buildopertypeid,
//            "s_begdate" => $s_begdate,
//            "s_enddate" => $s_enddate,
//            "s_orgid" => $s_orgid,
//            "s_bdgtorgid" => $s_bdgtorgid,
//            "s_budgetid" => $s_budgetid,
//            "s_itmname" => $s_itmname,
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

        //$needSearch = true;

        if ($needSearch) {
            $sc = " 1=1 ";
            $sc1 = " ";
            $sc2 = " ";

            if (strlen($s_contractid) > 0)
                $sc .= " and ce.contractid={$s_contractid}";

            if (strlen($s_buildopertypeid) > 0) {
                $sc .= " and ce.buildopertypeid=" . $s_buildopertypeid;
            }

            if (strlen($s_begdate) > 0) {
                $sc .= " and upd.docdate>='{$s_begdate}'";
                $sc1 .= " and ifnull(inv.docdate, ifnull(sup.fctgetdate, sup.created_at)) >= '{$s_begdate}'";
                $sc2 .= " and ifnull(inv.docdate, exp.created_at) >='{$s_begdate}'";
            }
            if (strlen($s_enddate) > 0) {
                $sc .= " and upd.docdate<='{$s_enddate}'";
                $sc1 .= " and ifnull(inv.docdate, ifnull(sup.fctgetdate, sup.created_at)) <= '{$s_enddate}'";
                $sc2 .= " and ifnull(inv.docdate, exp.created_at) <='{$s_enddate}'";
            }

        }
        //dd($sc);
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = DB::select(DB::raw(
                "SELECT date_format(docdate,'%Y-%m') as reg_ym
, sum(if(doctypeid=1,docsum,0)) as d1_sum
, sum(if(doctypeid=2,docsum,0)) as d2_sum
, sum(if(doctypeid=3,docsum,0)) as d3_sum
, sum(docsum) as docsum
FROM contract_exes as ce
join buildopertypes as bot on bot.id=ce.buildopertypeid
 where " . $sc . "
group by  reg_ym
order by  reg_ym
"
            ));
            //dd($sc);
//            dd($recs);


            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->contract = contract::find($s_contractid);

        //Cache::forget('conrtacts_with_exe');
        $data->contracts = Cache::remember('conrtacts_with_exe', now()->addMinutes(15)
            , function () {
                $list = contract::from('contracts as c')
                    ->join('orgs as oo', 'oo.id', 'c.ownorgid')
                    ->join('orgs as o', 'o.id', 'c.orgid')
                    ->whereRaw("c.id in (select distinct contractid FROM contract_exes) ")
                    ->select('c.id', db::raw("concat(oo.name, ' - ', o.name, ' №',c.docnum,' от ', c.docdate,' ', c.descript) as tname"))
                    ->get()->pluck('tname', 'id')->toArray();

                return $list;
            });
//dd($data->contracts );

        if (isset($s_contractid))
            $data->buildopertypes = buildopertype::from('buildopertypes as bot')
                ->whereRaw("bot.id in (select distinct buildopertypeid from contract_exes as ce where ce.contractid={$s_contractid})")
                ->orderby('bot.ordr')
                ->orderby('bot.name')
                ->get()->pluck('name', 'id')->toArray();
        else
            $data->buildopertypes = [];
//        dd( $data->buildopertypes );

        if (isset($s_buildopertypeid))
            $data->buildopertype = buildopertype::find($s_buildopertypeid);

        return view('contract_exes.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }

    public function rep23(Request $request)
    {
        $report_id = 23;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с данной информацией!']);

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep" . $report_id;

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.read'),
        );

        //2020-09-28 Меняем концепцию - если у пользователя нет прав на чтение (ВСЕХ записей), то здесь не блокируем,
        //а смотрим дальше по месту - есть ли он в списке читателей для каждого договора

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска -------------------------------------------------
        $s_ownorgid = "";
        $s_orgname = "";
        $s_name = "";
        $s_tag = "";
        $s_categoryid = "";
        $s_typeid = "";
        $s_docnum = "";
        $s_docdate = "";
        $s_regnum = "";
        $s_min_regnum = "";
        $s_max_regnum = "";
        $s_regnumstatus = "";
        $s_end_at = "";
        $s_statusid = "";
        $s_orggrpid = "";
        $s_flagtypeid = "";
        $s_buildobjid = "";
        $s_file_doctypeid = "";


        //dd($request->get("s_tag"));
        //(null !== $request->getQueryString())
        //Тэг может задаваться через QueryString
        $s_tag = $request->get("s_tag");
        //dd($s_tag,isset($s_tag));

        if ($request->isMethod('post') or isset($s_tag)) {
            $s_ownorgid = $request->get("s_ownorgid");
            $s_orgname = $request->get("s_orgname");
            $s_name = $request->get("s_name");
            $s_tag = $request->get("s_tag");
            $s_docnum = $request->get("s_docnum");
            $s_docdate = $request->get("s_docdate");
            $s_regnum = $request->get("s_regnum");
            $s_min_regnum = $request->get("s_min_regnum");
            $s_max_regnum = $request->get("s_max_regnum");
            $s_regnumstatus = $request->get("s_regnumstatus");
            $s_statusid = $request->get("s_statusid");
            $s_categoryid = $request->get("s_categoryid");
            $s_typeid = $request->get("s_typeid");
            $s_end_at = $request->get("s_end_at");
            $s_orggrpid = $request->get("s_orggrpid");
            $s_flagtypeid = $request->get("s_flagtypeid");
            $s_buildobjid = $request->get("s_buildobjid");
            $s_file_doctypeid = $request->get("s_file_doctypeid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $this->sysobjcode]);
            session(['search_params' => [
                's_ownorgid' => $s_ownorgid,
                's_orgname' => $s_orgname,
                's_name' => $s_name,
                's_tag' => $s_tag,
                's_docnum' => $s_docnum,
                's_docdate' => $s_docdate,
                's_regnum' => $s_regnum,
                's_min_regnum' => $s_min_regnum,
                's_max_regnum' => $s_max_regnum,
                's_regnumstatus' => $s_regnumstatus,
                's_statusid' => $s_statusid,
                's_categoryid' => $s_categoryid,
                's_typeid' => $s_typeid,
                's_end_at' => $s_end_at,
                's_orggrpid' => $s_orggrpid,
                's_flagtypeid' => $s_flagtypeid,
                's_buildobjid' => $s_buildobjid,
                's_file_doctypeid' => $s_file_doctypeid,
            ]]);
        } else {

            if (session('search_setname') == $this->sysobjcode) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_name = $params['s_name'] ?? null;
                    $s_tag = $params['s_tag'] ?? null;
                    $s_ownorgid = $params['s_ownorgid'] ?? null;
                    $s_orgname = $params['s_orgname'] ?? null;
                    $s_docnum = $params['s_docnum'] ?? null;
                    $s_docdate = $params['s_docdate'] ?? null;
                    $s_regnum = $params['s_regnum'] ?? null;
                    $s_min_regnum = $params['s_min_regnum'] ?? null;
                    $s_max_regnum = $params['s_max_regnum'] ?? null;
                    $s_regnumstatus = $params['s_regnumstatus'] ?? null;
                    $s_categoryid = $params['s_categoryid'] ?? null;
                    $s_typeid = $params['s_typeid'] ?? null;
                    $s_statusid = $params['s_statusid'] ?? null;
                    $s_end_at = $params['s_end_at'] ?? null;
                    $s_orggrpid = $params['s_orggrpid'] ?? null;
                    $s_flagtypeid = $params['s_flagtypeid'] ?? null;
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                    $s_file_doctypeid = $params['s_file_doctypeid'] ?? null;
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_ownorgid" => $s_ownorgid,
            "s_orgname" => $s_orgname,
            "s_name" => $s_name,
            "s_tag" => $s_tag,
            "s_categoryid" => $s_categoryid,
            "s_typeid" => $s_typeid,
            "s_docnum" => $s_docnum,
            "s_docdate" => $s_docdate,
            "s_regnum" => $s_regnum,
            "s_min_regnum" => $s_min_regnum,
            "s_max_regnum" => $s_max_regnum,
            "s_regnumstatus" => $s_regnumstatus,
            "s_statusid" => $s_statusid,
            "s_end_at" => $s_end_at,
            "s_orggrpid" => $s_orggrpid,
            "s_flagtypeid" => $s_flagtypeid,
            "s_buildobjid" => $s_buildobjid,
            "s_file_doctypeid" => $s_file_doctypeid,
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
        //$usrrights['read'] = false;
        if (!$usrrights['read']) {
            $sc .= ' and exists (select 1 from obj_readers r where r.sysobjid=' . $this->sysobjid . ' and objid=c.id and userid=' . $userid . ')';
        }

        if ($needSearch) {
            if (strlen($s_ownorgid) > 0) {
                $sc = $sc . " and c.ownorgid = '" . $s_ownorgid . "'";
            }
            if (strlen($s_orgname) > 0) {
                // $sc = $sc . " and o.name like '%" . mb_strtoupper($s_orgname) . "%'";
                $sc = $sc . " and exists (select 1 from contract_orgs as co
                join orgs as coo on coo.id=co.orgid  where co.contractid=c.id ";

                //" and coo.name like '%" . mb_strtoupper($s_orgname) . "%')";

                $find = explode(" ", $s_orgname);

                if (count($find) > 0) {
                    $sc .= ' and (1=1';
                    foreach ($find as $f) {
                        $sc .= " and coo.name like '%" . $f . "%'";
                    }
                    $sc .= ')';
                }
                $sc .= ')';
            }

            if (strlen($s_name) > 0) {
                $sc = $sc . " and concat(ifnull(c.name,' '),' ', ifnull(c.descript,' '),' ', ifnull(c.notes,' '))  like '%" . mb_strtoupper($s_name) . "%'";
            }
            if (strlen($s_tag) > 0) {
                $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid=151 and objid=c.id and ot.tag='" . mb_strtoupper($s_tag) . "')";
            }
            if (strlen($s_buildobjid) > 0) {
                $sc = $sc . " and exists (select 1 from obj_links as ol where ol.sysobjid=151 and objid=c.id and ol.lnksysobjid=466 and ol.lnkobjid=" . $s_buildobjid . ")";
            }
            if (strlen($s_file_doctypeid) > 0) {
                $sc = $sc . " and exists (select 1 from objfiles as f where f.sysobjid=151 and f.objid=c.id and f.doctypeid=" . $s_file_doctypeid . ")";
            }
            if (strlen($s_docnum) > 0) {
                $sc = $sc . " and c.docnum like '%" . mb_strtoupper($s_docnum) . "%'";
            }
            if (strlen($s_regnumstatus) > 0) {
                if ($s_regnumstatus == 0)
                    $sc = $sc . " and c.regnum is null";
                else
                    $sc = $sc . " and c.regnum is not null";
            }
            if (strlen($s_regnum) > 0) {
                if ($s_regnum == '*')
                    $sc = $sc . " and c.regnum is not null";
                else
                    $sc = $sc . " and c.regnum = '" . mb_strtoupper($s_regnum) . "'";
            }
            if (strlen($s_min_regnum) > 0) {
                $sc = $sc . " and substr(c.regnum,2) >= " . $s_min_regnum;
            }
            if (strlen($s_max_regnum) > 0) {
                $sc = $sc . " and substr(c.regnum,2) <= " . $s_max_regnum;
            }
            if (strlen($s_docdate) > 0) {
                $sc = $sc . " and c.docdate = '" . $s_docdate . "'";
            }


            if (strlen($s_statusid) > 0) {
                $sc = $sc . " and c.statusid = '" . $s_statusid . "'";
            }

            if (strlen($s_categoryid) > 0) {
                $sc = $sc . " and c.categoryid = '" . $s_categoryid . "'";
            }
            if (strlen($s_typeid) > 0) {
                $sc = $sc . " and c.contracttypeid = '" . $s_typeid . "'";
            }
            if (strlen($s_end_at) > 0) {
                $sc = $sc . " and date_add(curdate(), interval $s_end_at day)>=ifnull(c.enddate,'3333-01-01')";
            }

            if (strlen($s_orggrpid) > 0)
                $sc .= " and exists(select 1 from grpitems gl where gl.sysobjid=" . $this->sysobjid
                    . " and gl.objid=c.id and gl.grpid=" . $s_orggrpid . ')';

            if (strlen($s_flagtypeid) > 0)
                $sc .= " and exists(select 1 from objflags f where f.sysobjid=" . $this->sysobjid
                    . " and f.objid=c.id and f.flagtypeid=" . $s_flagtypeid . ')';

        }
        // --------------------------------------------------------------------


        $recs = contract::from('contracts as c')
            ->leftJoin('orgs as oo', function ($j) {
                $j->on('oo.id', 'c.ownorgid');
            })
            ->leftJoin('orgs as o', function ($j) {
                $j->on('o.id', 'c.orgid');
            })
            ->leftJoin('contracttypes as t', function ($j) {
                $j->on('t.id', 'c.contracttypeid');
            })
            ->leftJoin('contract_categories as cc', function ($j) {
                $j->on('cc.id', 'c.categoryid');
            })
            ->whereraw($sc)
            ->select('c.id', 'c.name', 'c.descript', 'c.active', 'c.regnum', 'c.docnum', 'c.docdate', 'c.docsum'
                , 'c.begdate', 'c.enddate'
                , 'c.categoryid', 'cc.name as categoryname'
                , 't.name as contracttypename'
                , 'c.statusid', 'c.status_notes'
                , 'c.ownorgid', 'oo.name as ownorgname'
                , 'o.name as orgname'
                , db::raw("(select group_concat(concat(co.rolename,': ', coo.name ) SEPARATOR '; ')
                    from contract_orgs as co join orgs as coo on coo.id=co.orgid
                    where co.contractid=c.id and co.orgid<>c.ownorgid) as controrg_lst")

                , db::raw("(SELECT GROUP_CONCAT(dt.name SEPARATOR '; ')
                    FROM objfiles AS f
                    JOIN doctypes AS dt ON dt.id = f.doctypeid
                    WHERE f.sysobjid=151 and f.objid=c.id
                    ORDER BY f.id) as lstImageDocs")
            )
            ->with('tags');

        //Базовая сортировка ---------------------------------------------
        $recs = $recs->orderBy('oo.name', 'asc')->orderby('c.ownorgid');
        //----------------------------------------------------------------

        //Сортировка пользователя ----------------------------------------
        //session()->put('sort_params_' . $this->sysobjcode . '.index', []);

        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        //$recs = $recs->orderBy('c.regnum', 'asc');
        $recs = $recs
            ->orderByRaw("substr(c.regnum,1,1), cast(substr(c.regnum,2) as decimal)");
//        }
        //----------------------------------------------------------------

//        $recs = $recs->paginate(50);
        $recs = $recs->get();
        //dd($recs);

        $data = new \stdClass();

        $objgroups = []; //group::lstOrgGroups_cache();

        $data->regnumstatuses = [0 => 'не присвоен', 1 => 'присвоен'];

        $data->file_doctypes = doctype::lstUsedForSysObj(151);
        //dd($data->file_doctypes);

        $usedflags = objflag::lstUsedFlagsForSysObj_cache($this->sysobjid);

        $usedtypes = contracttype::usedTypes();

        $usedtags = contract::usedTags();
        $usedbuildobjs = contract::usedBuildObjs();
        //dd($usedbuildobjs);


        $usedownorgs = Cache::remember('contract_usedownorgs', now()->addMinutes(5)
            , function () {
                return org::from('orgs as o')
                    ->whereraw(" exists(select 1 from contracts as c where c.ownorgid=o.id)")
                    ->get()->pluck('name', 'id')->toArray();
            });

        $usedcategories = contract_category::lstActive();


        $end_variants = [
            0 => 'истек',
            30 => 'истечет в ближайшие 30 дней',
            60 => 'истечет в ближайшие 60 дней',
            90 => 'истечет в ближайшие 90 дней',
            120 => 'истечет в ближайшие 120 дней',
        ];
        $statuses = contract::statuses();

//dd($usrrights);

        return view('contracts.rep23', compact(
            'recs'
            , 'objgroups', 'usedflags', 'usedtypes', 'usedownorgs', 'usedcategories', 'statuses', 'end_variants'
            , 'usedtags', 'usedbuildobjs'
            , 'data'
            , 'search_params', 'sort_params'
            , 'usrrights'));
    }


    static public function list_for(Request $request)
    {
        //2021-02-20 SNS.

        $result = "";
        try {

            $list = contract::lstFor([
                'in_equiprsts' => $request->in_equiprsts,
                'ownorgid' => $request->ownorgid,
                'orgid' => $request->orgid,
                'between_orgs' => $request->between_orgs,
                'actual' => $request->actual,
                'categoryid' => $request->categoryid,
                //'for_userid' => ($request->for_userid ?? Auth::user()->id),
                'for_userid' => ($request->for_userid),
                'buildobjid' => $request->buildobjid,
                'budget_buildobjid' => $request->budget_buildobjid,
                'budget_orgid' => $request->budget_orgid,
                'buildopertypeid' => $request->buildopertypeid,
            ]);


            $result = array('contracts' => $list);

        } catch (\Exception $e) {
            Log::error('contracts::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }


    public function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = contract::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at', 'regnum_num', 'regnum'])->toArray());

        $buildobjid = obj_link::getFirstLnkId($this->sysobjid, $rec->id, 466);
        if (isset($buildobjid))
            $document['buildobjid'] = $buildobjid;

        $buildopertypeid = obj_link::getFirstLnkId($this->sysobjid, $rec->id, 467);
        if (isset($buildopertypeid))
            $document['buildopertypeid'] = $buildopertypeid;

        $document['tags'] = objtag::lstTags($this->sysobjid, $id);

        //dd($document);
        $orgs = contract_org::where(['contractid' => $rec->id])->get()
            ->makeHidden(['id', 'contractid', 'created_at', 'updated_at', 'created_by', 'updated_by'])->toArray();
        //уберем пустые элементы массива
        foreach ($orgs as $elm) {
            $elm = array_filter($elm);
        }

        $obj_staffs = obj_staff::where(['sysobjid' => $this->sysobjid, 'objid' => $rec->id])->get()
            ->makeHidden(['id', 'created_at', 'updated_at', 'created_by', 'updated_by'])->toArray();
        //уберем пустые элементы в каждой записи массива
        foreach ($obj_staffs as $elm) {
            $elm = array_filter($elm);
        }

        $template_js = [
            'contract' => $document,
            'contract_orgs' => $orgs,
            'obj_staffs' => $obj_staffs,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

}
