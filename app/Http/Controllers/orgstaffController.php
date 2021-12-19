<?php

namespace App\Http\Controllers;

use App\bdgtacnttype;
use App\doctype;
use App\orgdep;
use App\staff_post;
use App\stforder;
use App\sysobj;
use App\Traits\Result;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use App\orgstaff;
use App\org;
use Cache;
use DB;
use App\orgpost;
use Illuminate\Support\Facades\Log;

class orgstaffController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 121;
        $this->sysobjcode = 'orgstaff';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    /*
     * Установка прав пользователя
     */
    protected function setInterfaceRight($recid)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['load'] = usrsysright::isUserHasRightByCode_cached($userid, 'admin-global');

        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = ($recid <> -1 and usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.admindelete'));
//        $usrrights['private_acs'] = ($recid <> -1 and usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.private_acs'));
        $usrrights['private_acs'] = (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.private_acs'));

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');


        $tmp_sysobjcode = $this->sysobjcode;
        $this->sysobjcode = 'orgplnpays';

        $usrrights['agr1'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.agr1');
        $usrrights['agr2'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.agr2');
        //$usrrights['regpay'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.regpay');

        $this->sysobjcode = $tmp_sysobjcode;

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
        if (!$usrrights['read']) {
            return view('home');
        }


        session([$this->sysobjcode . '_pageno' => $request->page ?? 1]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_active' => '1'
            , 's_orgflagid' => 12
            , 's_orgid' => ''
            , 's_name' => ''
            , 's_postname' => ''
            , 's_file_doctypeid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        //$sc .= " and exists(select 1 from objflags f where f.sysobjid=111 and f.objid=os.orgid and f.flagtypeid=12)";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and concat(os.lname,' ',os.fname,' ',os.mname) like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_orgflagid') {
                    $sc .= " and exists(select 1 from objflags f where f.sysobjid=111 and f.objid=os.orgid and f.flagtypeid={$val})";

                } elseif ($item == 's_orgid') {
                    $sc = $sc . " and os.orgid = '{$val}'";

                } elseif ($item == 's_active') {
                    $sc = $sc . " and ifnull(os.active,0) = '{$val}'";

                } elseif ($item == 's_postname') {
                    $sc = $sc . " and ( os.postname like '%{$val}%'
                    or exists (select 1 from orgposts as op where op.id=os.postid and op.name like '%{$val}%')
                    ) ";

                } elseif ($item == 's_file_doctypeid') {
                    $sc = $sc . " and exists (select 1 from objfiles as f where f.sysobjid={$this->sysobjid} and f.objid=os.id and f.doctypeid={$val})";

                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------


        $recs = orgstaff::from('orgstaff as os')
            ->join('orgs as o', 'o.id', 'os.orgid')
            ->leftJoin('orgposts as op', function ($j) {
                $j->on('op.id', 'os.postid');
            })
            ->leftJoin('orgdeps as od', function ($j) {
                $j->on('od.id', 'op.depid');
            })
            ->whereraw($sc)
            ->select('os.id', 'os.lname', 'os.fname', 'os.mname'
                , db::raw("ifnull(op.name, os.postname) as post_name")
                , db::raw("ifnull(od.ordr, 9999) as dep_ordr")
                , db::raw("ifnull(op.ordr, 9999) as post_ordr")
                , 'os.orgid', 'o.name as org_name'
                , 'os.active'
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        $recs = $recs->orderBy('org_name', 'asc');
        $recs = $recs->orderBy('os.orgid', 'asc');
        $recs = $recs->orderBy('dep_ordr', 'asc');
        $recs = $recs->orderBy('post_ordr', 'asc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('os.lname', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);
        //--------------------------------------------------------------


        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid);

        //номер первой записи на странице:
        $data->rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data->search_params = $search_params;

        $data->ownorgs = org::lstFor([
            'in_orgstaff' => 1,
            'flagtypeid' => $search_params['s_orgflagid'] ?? '',
        ]);

        $data->statuses = [1 => 'актив', 0 => 'архив'];

        $data->file_doctypes = doctype::lstUsedForSysObj($this->sysobjid);


        return view('orgstaff.index', compact(['recs', 'data', 'usrrights']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $orgid)
    {
        return $this->edit($request, -1, $orgid);
    }

    public function show($id)
    {
        //
        $rec = orgstaff::findOrFail($id);

        $userid = \Auth::user()->id;
        $usrrights = array();
        $usrrights['orgstaff.update'] = usrsysright::isUserHasRightByCode_cached($userid, 'orgstaff.update');
        return view('orgstaff.show', compact(['rec', 'usrrights']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $orgid = null)
    {
        //

        $userid = \Auth::user()->id;

        if ($id == -1) {
            $orgid = ($orgid == 0) ? null : $orgid;
            $rec = new orgstaff([
                'id' => -1,
                'orgid' => $orgid,
                'active' => 1,
                'created_by' => $userid,
            ]);
        } else
            $rec = orgstaff::find($id);

        if (!isset($rec))
            return redirect(route('orgs.index'))->with(['error' => 'Запись не найдена!']);

        $rec->retURL = $request->get('returl');

        //$data = new \stdClass();

        //$rec->isownorg = org::isOwnOrg($rec->orgid);

        $rec->orgs = org::lstFor([
            'flagtypeid_or_id' => [12, $rec->orgid],
        ]);

        //переключим признак указания подразделения/должности из справочника на не пустоту спр-ка подразделений
        $rec->strict_dep_posts = (orgdep::where('orgid', $rec->orgid)->count() > 0);
        //dd($rec->strict_dep_posts);
        $rec->isownorg = $rec->strict_dep_posts;

        if ($rec->strict_dep_posts and $id == -1)
            $rec->stdpostunit = 1;  //по-умолчанию для новой записи организации Холдинга

        if ($rec->strict_dep_posts) {
            $rec->orgdeps = orgdep::lstFor([
                'orgid' => $rec->orgid,
                //'with_post_vacancies' => 1,
                'with_post_vacancies_staff' => $rec->id,
                'active' => 1
            ]);

            $rec->orgposts = orgpost::from('orgposts as op')
                ->leftJoin('orgstaff as os', function ($j) use ($rec) {
                    $j->on('os.postid', 'op.id')
                        ->where('os.id', $rec->id)
                        ->where('os.active', 1);
                })
                ->where(['op.orgid' => $rec->orgid,
                    'op.depid' => $rec->depid
                ])
                ->whereRaw("op.stdlimunits-op.stdusedunits+ifnull(os.stdpostunit,0)>0")
                ->select('op.id', db::raw("concat(op.name,' (',op.stdlimunits-op.stdusedunits+ifnull(os.stdpostunit,0),')') as tname"))
                ->pluck('tname', 'id')->toarray();
        }

        //dd($rec->orgposts);

//        $users = orgstaff::UsersForStaffID($orgstaff->id);
        $rec->users = orgstaff::listUsersByFIO($rec->lname, $rec->fname, $rec->mname);
        $rec->userrights = [];
        //dd( $orgstaff->userrights);
        $usrrights = $this->setInterfaceRight($id);


        //проверим - является ли сотрудник руководителем предприятия или главным бухгалтером
        $rec->is_boss = ($rec->org->boss_staffid == $rec->id);
        $rec->is_ca = ($rec->org->ca_staffid == $rec->id);

        $rec->fot_acnttypes = bdgtacnttype::lstFor(['active' => 1,
            'dir' => -1,
        ]);

        $rec->sexes = ['M' => 'муж', 'F' => 'жен'];

        $rec->marriage_statuses = ($rec->sex == 'F')
            ? [0 => 'не замужем', 1 => 'замужем']
            : [0 => 'не женат', 1 => 'женат'];

        $rec->education_lvls = [
            1 => 'среднее общее',
            2 => 'начальное профессиональное',
            3 => 'среднее профессиональное',
            4 => 'высшее профессиональное'
        ];

        if ($id <> -1) {

            $acl_sysobjcode = sysobj::acl_sysobjcode('stforders');
            if (usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.read')) {
                $rec->stforders = stforder::from('stforders as so')
                    ->join('stfordtypes as sot', 'sot.id', 'so.ordtypeid')
                    ->where('so.staffid', $rec->id)
                    ->select('so.*', 'sot.name as ordtype_name', 'so.notes'
                        , db::raw("case when begdate > CURDATE() then 1
                    when begdate <= CURDATE() and IFNULL(enddate, CURDATE()) >= CURDATE() then 2
                    else 3
                    end as action_status")
                    )
                    ->orderby('so.orddate')
                    ->get();
            }

            //История занимаемых должностей
            $rec->staff_posts = staff_post::from('staff_posts as sp')
                ->leftJoin('orgposts as op', 'op.id', 'sp.postid')
                ->where(['staffid' => $rec->id])
                ->select('sp.*', db::raw("ifnull(op.name, sp.postname) as post_name"))
                ->orderBy('begdate', 'asc')
                ->get();
            //dd($rec->staff_posts);
        }

        return view('orgstaff.edit', compact(['rec', 'usrrights']));
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
        $messages = [
            'lname.required' => 'Укажите фамилию',
            'fname.required' => 'Укажите отчество',
            'orgid.required' => 'Укажите организацию',
            'depid.required' => 'Укажите подразделение',
            'postid.required' => 'Укажите должность',
            'stdpostunit.required' => 'Укажите величину ставки',
            'stdpostunit.gt' => 'Ставка не может быть равна нулю',
            //'postname.required' => 'Укажите должность',
            'inn.digits' => 'В ИНН должно быть 12 цифр',
            'snils.size' => 'Длина СНИЛС должна быть 14 символов',
            'phone.required' => 'Укажите номер телефона',
        ];

        $rules = [
            "lname" => "required",
            "fname" => "required",
            "orgid" => "required",
            //"postname" => "required",
            //'phone' => 'required|max:20',
            'inn' => 'nullable|digits:12',
            'snils' => 'nullable|size:14',
        ];

        $orgid = $request->get('orgid');

        //$isOwnOrg = org::isOwnOrg($orgid);
        $strictDepPost = (orgdep::where('orgid', $orgid)->count() > 0);

        if ($strictDepPost) {
            $rules['depid'] = 'required';
            $rules['postid'] = 'required';
            $rules['stdpostunit'] = 'required|gt:0';
        }
        //dd($rules);

        $request->validate($rules, $messages);
        //$request->validate($rules, $messages)->validateWithBag('post');

        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight($id);

        $mess = "";
        if ($id == -1) {
            $os = new orgstaff();
            $os->created_by = $userid;
            $os->created_at = now();
            $mess = "Создана запись о сотруднике";
        } else {
            $os = orgstaff::find($id);
            $mess = "Изменена запись о сотруднике";
        }
        $pre_postid = $os->postid;

        $os->lname = $request->get('lname');
        $os->fname = $request->get('fname');
        $os->mname = $request->get('mname');
        $os->name = $os->lname . ' ' . $os->fname . ' ' . $os->mname;

        $os->orgid = $orgid;

        if ($strictDepPost) {
            $os->depid = $request->get('depid');
            $os->postid = $request->get('postid');
            $os->postname = $os->post->name;
            $os->stdpostunit = $request->get('stdpostunit');

        } else {
            $os->depname = mb_substr($request->get('depname'), 0, 60);
            $os->postname = mb_substr($request->get('postname'), 0, 160);
        }

        $os->inn = $request->get('inn');
        $os->snils = $request->get('snils');

        $os->postbegdate = $request->get('postbegdate');
        $os->bossname = $request->get('bossname');

        $os->education_lvl = $request->get('education_lvl');

        $os->jobduties = mb_substr($request->get('jobduties'), 0, 300);
        $os->gendoctypes = mb_substr($request->get('gendoctypes'), 0, 300);
        $os->cnfrmdoctypes = mb_substr($request->get('cnfrmdoctypes'), 0, 300);
        $os->aprvdoctypes = mb_substr($request->get('aprvdoctypes'), 0, 300);

        $os->rqrd_software = mb_substr($request->get('rqrd_software'), 0, 300);
        $os->have_software = mb_substr($request->get('have_software'), 0, 300);

        if ($usrrights['private_acs'] ?? false) {
            $os->phone = $request->get('phone');
            $os->email = $request->get('email');

            $os->begdate = $request->get('begdate');
            $os->enddate = $request->get('enddate');
            $os->birthdate = $request->get('birthdate');
            $os->birthplace = $request->get('birthplace');
            $os->sex = $request->get('sex');
            $os->reg_address = mb_substr($request->get('reg_address'), 0, 160);

            $os->marriage = $request->get('marriage');

            $os->fot_acnttypeid = $request->get('fot_acnttypeid');
            $os->hour_salary = $request->get('hour_salary');
            $os->day_salary = $request->get('day_salary');

        }

        //$os->active = $request->get('active',1);
        $os->active = $request->get('active') ?? 0;
        $os->userid = $request->get('userid');
        $os->updated_by = $userid;
        $os->save();

        if ($strictDepPost) {
            //пересчитаем кол-ва использованных вакансий
            orgpost::refresh_units($pre_postid);
            orgpost::refresh_units($os->postid);
        }

        //сотрудник отмечен как руководитель предприятия
        if ($request->get('is_boss') == 1 or $request->get('is_ca') == 1) {
            $org = org::find($os->orgid);

            if (isset($org)) {

                if ($request->get('is_boss') == 1) {
                    $org->boss_staffid = $os->id;
                    $org->boss_fullname = mb_substr($os->lname . ' ' . $os->fname . ' ' . $os->mname, 0, 90);
                    $org->boss_name = mb_substr($os->lname . ' '
                        . mb_substr($os->fname, 0, 1) . '.'
                        . mb_substr($os->mname, 0, 1) . '.', 0, 60);
                }
                if ($request->get('is_ca') == 1) {
                    $org->ca_staffid = $os->id;
                    $org->ca_fullname = mb_substr($os->lname . ' ' . $os->fname . ' ' . $os->mname, 0, 90);
                    $org->ca_name = mb_substr($os->lname . ' '
                        . mb_substr($os->fname, 0, 1) . '.'
                        . mb_substr($os->mname, 0, 1) . '.', 0, 60);

                }
                $org->save();
                //dd($org);
            }

        }

        Cache::forget('org_aux_staff_.' . $os->orgid);

        $retURL = $request->get('returl') ?? route($this->sysobjcode . '.index')
            . '?page=' . session($this->sysobjcode . '_pageno') . '#' . $os->id;

        //return redirect(route('org_staff.index', $os->orgid))->with('success', $mess);
        return redirect($retURL)->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {

        $userid = \Auth::user()->id;

        $retURL = $request->get('returl') ?? route('orgstaff.edit', $id);

        if (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete')) {

            $res = orgstaff::delete_by_id($id);
            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $sd["error"] = $res->msg;
            } else {
                //пересчитаем кол-ва использованных вакансий
                orgpost::refresh_units($res->obj['postid']);

                $retURL = $request->get('returl') ?? route('org_staff.index', $res->obj['orgid']);
                $sd['success'] = 'Запись о сотруднике удалена';
            }
        } else {
            $sd['success'] = 'У вас нет прав на удаление записей!';
        }
        return redirect($retURL)->with($sd);
    }


    public
    function list(Request $request)
    {
        return $this->search_low($request, 'orgstaff.list', false);
    }

    public function search_low(Request $request, $blade_name, $skip_null)
    {

        $search_name = "";
        $s_postname = "";

        if ($request->isMethod('post')) {

            //снесем концевые пробелы
            $search_name = mb_ereg_replace("(^\s+)|(\s+$)/", "",
                $request->get("search_name"));
            $s_postname = mb_ereg_replace("(^\s+)|(\s+$)/", "",
                $request->get("s_postname"));

            //сохраним параметры поиска в сессии
            session(['search_setname' => "orgstaff"]);
            session(['search_params' => [
                'search_name' => $search_name,
                's_postname' => $s_postname,
            ]]);

        } else {
            if (session('search_setname') == "orgstaff") {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $search_params = session('search_params');
                    $search_name = $search_params['search_name'];
                    $s_postname = $search_params['s_postname'];
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        //Если параметры поиска не заданы, то уйдем на index
        if ($skip_null and strlen($search_name
                . $s_postname) == 0) return redirect()->route('orgs.index');

        $sc = "1=1";
        if (strlen($search_name) > 0) {
            $sc = $sc . " and lname like '%" . mb_strtoupper($search_name) . "%'";
        }
        if (strlen($s_postname) > 0) {
            $sc = $sc . " and postname like '" . ($s_postname) . "%'";
        }

        $items = orgstaff::from('orgstaff as os')
            ->join('orgs as o', 'o.id', 'os.orgid')
            ->select('os.id', DB::raw("concat(lname,' ',fname,' ',mname) as stfname")
                , 'os.active', 'os.postname', 'o.name as orgname')
            ->whereNotNull('os.lname')
            ->whereRaw($sc)
            ->orderBy('stfname', 'asc')
            ->paginate(20);

        //номер первой записи на странице:
        $rec0 = $items->currentPage() * $items->perPage() - $items->perPage() + 1;
        return view($blade_name, compact('items', 'rec0'
            , 'search_name', 's_postname'));
    }

    //получение информации о сотруднике (только id, ФИО, должность)
    public function getshortinfo(Request $request)
    {
        $id = $request->get('id');
        $ref = orgstaff::from('orgstaff as os')
            ->join('orgs as o', 'o.id', 'os.orgid')
            ->select('os.id', 'lname', 'fname', 'mname', 'postname', 'os.orgid', 'o.name as orgname')
            ->where('os.id', $id)
            ->first();

        $result = array('id' => null, 'name' => null, 'postname' => null);
        if (isset($ref)) {
            $result['id'] = $ref->id;
            $result['name'] = $ref->lname . ' ' . $ref->fname . ' ' . $ref->mname
                . ' ( ' . $ref->orgname . ', ' . $ref->postname . ')';
            $result['postname'] = $ref->postname;
            $result['orgid'] = $ref->orgid;
        }
        return response()->json($result);
    }

    static public function listorgstaff(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $orgid = $request->orgid;
            $list = orgstaff::where('orgid', $orgid)
                ->where('active', 1)
                //->select('id', DB::raw("concat(lname,' ', fname, ' ', mname, ', ', postname) as name"))
                ->select('id', DB::raw("concat(lname,' ', fname, ' ', mname, ', ', ifnull(postname,'-')) as name"))
                ->orderBy('lname')
                ->get()->pluck('name', 'id')->toArray();

            $result = array('staff' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);

    }

    static public function liststafffio(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $orgid = $request->orgid;
            $list = orgstaff::where('orgid', $orgid)
                ->where('active', 1)
                //->select('id', DB::raw("concat(lname,' ', fname, ' ', mname, ', ', postname) as name"))
                ->select(
                    DB::raw("concat(lname,' ', substr(fname,1,1), '.', substr(mname,1,1), '.') as tid")
                    , DB::raw("concat(lname,' ', fname, ' ', mname, ', ', ifnull(postname,'-')) as name"))
                ->orderBy('lname')
                ->get()->pluck('name', 'tid')->toArray();

            $result = array('staff' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);

    }


    static public function get_for(Request $request)
    {
        //2021-07-03 SNS. Обертка для вызова orgstaff::getFor

        $result = "";
//        try {

        $list = orgstaff::getFor([
            'active' => $request->active,
            'active_or_current' => $request->active_or_current,
            'in_documents' => $request->in_documents,
            'name' => $request->name ?? $request->q,
            'q' => $request->q,
            'orgid' => $request->orgid,
        ], [
            'os.id', db::raw("concat(os.name,', ', ifnull(op.name,os.postname), ' ', o.name) as name")
        ]);


        //$result = array('doctypes' => $list);
        $result = $list;

//        } catch (\Exception $e) {
//            Log::error('orgstaff::list_for:' . $e->getMessage());
//        }
        return response()->json($result);
    }


    public function load()
    {
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight(-1);
        $rec = new \stdClass();

        return view($this->sysobjcode.'.load', compact('rec', "usrrights"));
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
                $rec = orgstaff::import_001($file, $rec);
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
