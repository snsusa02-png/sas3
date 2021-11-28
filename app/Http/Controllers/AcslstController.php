<?php

namespace App\Http\Controllers;

use App\org;
use App\sysfunc;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\User;
use App\usrsysright_org;
use App\usrsysright;
use App\sysobj;
use App\objlog;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class AcslstController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 961;  //Tasks
        $this->sysobjcode = 'acslst';
    }

    /*
     * Установка прав пользователя
     */
    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        //по acl указанного объекта
        $acl_sysobjcode = sysobj::where('code', $this->sysobjcode)
                ->select(db::raw("ifnull(acl_sysobjcode, code) as acl_sysobjcode"))
                ->first()->acl_sysobjcode ?? $this->sysobjcode;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, 'admin-global');
        $usrrights['create'] = $usrrights['read'];
        $usrrights['save'] = $usrrights['read'];
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = $usrrights['read'];

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
        } else {
//            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.update');
        }

        return $usrrights;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function index(Request $request, $sysobjid)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight(-1);

        if (!$usrrights['read']) {
            return view('home')->with(['error' => 'Нет доступа!']);
        }

        session([$this->sysobjcode . '_pageno' => $request->page ?? 1]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 20
            , 's_name' => ''
            , 's_sysfuncid' => ''
            , 's_active' => 1
        ];
        //var_dump($param_names);

        $search_params = $this->search_params($request, $param_names, 'acslst_index');
        //var_dump($search_params);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and u.name like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_sysfuncid') {
                    $sc = $sc . " and exists(select 1 from usrsysrights as usr where usr.userid=u.id and usr.sysfuncid = {$val}
                    and now() between usr.begdt and ifnull(usr.enddt,now()) )";

                } elseif ($item == 's_active') {
                    $sc = $sc . " and u.active = {$val}";

                }
            }
        }
        //var_dump($sc);
        //-------------------------------------------------------------------------------------------------------------


        $recs = User::from('users as u')
            ->whereRaw("exists(select 1 from usrsysrights as ur
            where ur.userid=u.id and ur.active=1
                and now() between ur.begdt and ifnull(ur.enddt,now())
                and exists (select 1 from sysfuncs as sf where sf.id=ur.sysfuncid and sf.sysobjid={$sysobjid})
                )")
            ->whereraw($sc)
            ->select('u.id', 'u.name', 'u.active'
                , db::raw("(select count(*) from usrsysrights as ur
            where ur.userid=u.id and ur.active=1
                and now() between ur.begdt and ifnull(ur.enddt,now())
                and exists (select 1 from sysfuncs as sf where sf.id=ur.sysfuncid and sf.sysobjid={$sysobjid})) as rights_cnt")
            );


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('u.name', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);
        //--------------------------------------------------------------


        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        //номер первой записи на странице:
        $data->rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data->search_params = $search_params;

        $data->sysobj = sysobj::find($sysobjid);

        $data->sysfuncs = sysfunc::lstFor(['active' => 1, 's_sysobjid' => $sysobjid]);

        $data->statuses = [1 => 'актив', 0 => 'архив'];


        return view('acslst.index', compact(['recs', 'data', 'usrrights']));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $sysobjid)
    {
        return $this->edit($request, $sysobjid, -1);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\usrsysright_org $rec
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $sysobjid, $usrid)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight(1);
        //$usrrights['edtrights'] = true;


        $sysobj = sysobj::find($sysobjid);

        if (!isset($sysobj))
            return redirect(route('home'));


        $rec = new \stdClass();
        $rec->id = -1;
        $rec->sysobjid = $sysobjid;
        $rec->sysobj = $sysobj;
        $rec->userid = $usrid;

        if ($usrid == -1) {
            $rec->users = User::where('active', 1)
                ->where('id', '<>', 1)
                ->orderBy('name')->get()->pluck('name', 'id')->toarray();
            $rec->rights = [];
        } else {
            $rec->users = [];
            $rec->user = User::find($usrid);
            $rec->rights = sysfunc::from('sysfuncs as sf')
                ->leftJoin('usrsysrights as usr', function ($j) use ($sysobjid, $usrid) {
                    $j->on('usr.sysfuncid', 'sf.id')
                        ->where(['usr.userid' => $usrid])
                        ->whereRaw("now() between usr.begdt and ifnull(usr.enddt,now())");
                })
                ->leftJoin('users as u', 'u.id', 'usr.updated_by')
                ->leftJoin('sysobjs as lso', 'lso.id', 'usr.limsysobjid')
                ->where('sf.sysobjid', $sysobjid)
                ->where('sf.active', 1)
                ->select('sf.id', 'sf.name', 'usr.active', 'usr.begdt', 'usr.created_by'
                    , 'u.name as created_by_name'
                    , 'usr.limsysobjid', 'usr.limobjid'
                    , 'lso.name as lso_name', 'lso.code as lso_code')
                ->orderBy('sf.ordr')
                ->get();
            //->pluck('name', 'id')->toarray();

        }

        return view('acslst.edit', compact('rec', 'usrrights'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\usrsysright_org $rec
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, $sysobjid, $usrid)
    public function update(Request $request)
    {
        //

        $userid = \Auth::user()->id;

        if ($request->id == -1) {

            $rules = [
                "userid" => "required",
            ];
            $messages = [
                "userid.required" => "Обязательно укажите пользователя",
            ];

            $request->validate($rules, $messages);

            //перейдем к редактированию прав
            return $this->edit($request, $request->sysobjid, $request->userid);
        }


        if (isset($request->rightid)) {
            $rights = array_flip($request->rightid);
        } else {
            //если ничего не передано - то нужно удалить все
            $rights = [];
        }

        $usrid = $request->userid;
        $sysobjid = $request->sysobjid;

        //сначала удалим те права, которые не переданы
        // список текущих прав пользователя на подсистему
        $usrsysrights = usrsysright::UserRightLst4sysobj($usrid, $sysobjid);

        for ($x = 0; $x <= count($usrsysrights) - 1; $x++) {
            if (!isset($rights[$usrsysrights[$x]->funcid]))
                //dd($usrsysrights[$x]->funcid);
                usrsysright::delUsrSysRight($usrid, $usrsysrights[$x]->funcid, null, null, $userid);
        }

        //теперь расставим переданные права
        if (isset($request->rightid)) {
            for ($x = 0; $x <= count($request->rightid) - 1; $x++) {
                usrsysright::setUsrSysRight($usrid, $request->rightid[$x], null, null, $userid);
            }
        }

        //Зачистим кэш прав ------------------------------------------------------------------
        usrsysright::clearUserRightsCache($usrid);
        //------------------------------------------------------------------------------------

        objlog::log_info(3, $usrid, "обновлены права пользователя {$usrid}", 5);

        $retURL = $request->get('retURL') ?? route('acslst.index', $sysobjid);
        return redirect($retURL);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\usrsysright_org $rec
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = usrsysright_org::delete_by_id($id, $this->sysobjid);
        //dd($res);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route($this->sysobjcode . '.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            //удалили

            $parobjid = $res->obj['usrsysrightid'];
            $usrsysright = usrsysright::find($parobjid);

            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //забудем кэшированные данные про организации пользователя:
            Cache::forget($this->sysobjcode . 'lstOrgsByUsersWithRight_' . $usrsysright->sysfunc->id);

            $route = route($this->sysobjcode . '.index', $parobjid);
            $sd['success'] = 'Запись о представляемой организации удалена';
        }
        return redirect($route)->with($sd);
    }

}
