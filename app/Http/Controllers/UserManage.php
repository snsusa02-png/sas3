<?php

namespace App\Http\Controllers;

use App\mychat_user;
use App\objextid;
use App\objlog;
use App\order;
use App\ordstage;
use App\ordstatustype;
use App\org;
use App\sysobj;
use App\sysfunc;
use App\sysrole;
use App\Traits\SearchDataTrait;
use App\User;
use App\user_ac;
use App\user_acl_role;
use App\userorg;
use App\usrsysright;
use App\usrsysrole;
use Auth;
use DB;
use Cache;

//use Illuminate\Support\Facades\Request;
use Illuminate\Http\Request;


class UserManage extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 3;
        $this->sysobjcode = 'users';
        $this->objcode = $this->sysobjcode;
    }

    protected function setInterfaceRight($rec_id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode($userid, $this->objcode . '.read'),
            'save' => false,
            'delete' => false,
            'link2org' => false,
            'edtrights' => false,
            'reset.password' => false,

            'objextids.read' => false,
            'objextids.create' => false,

            'userorgs.read' => false,
            'userorgs.create' => false,

            'acs.admin' => usrsysright::isUserHasRightByCode($userid, 'acs.read'),
        );

        if ($rec_id == -1) {
            //новая запись
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.create');
            $usrrights['delete'] = false;
            $usrrights['link2org'] = usrsysright::isUserHasRightByCode($userid, 'user2staff');

        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.update');
            $usrrights['delete'] = (usrsysright::isUserHasRightByCode($userid, $this->objcode . '.delete')
                and $rec_id <> 12);

            //право создания сотрудников из зарегистрированных пользователей (если ы
//            $userData = user::select('StaffID')->find($rec_id);
//            if (is_null($userData->StaffID))
//                $usrrights['link2org'] = usrsysright::isUserHasRightByCode($userid, 'user2staff');

            $usrrights['edtrights'] = usrsysright::isUserHasRightByCode($userid, 'admin-global');

            $usrrights['reset.password'] = ($usrrights['delete'] and $rec_id <> 3); //пока повесим на право удаления

            if (sysobj::isActiveByCode_cache('objextids')) {
                $usrrights['objextids.read'] = usrsysright::isUserHasRightByCode($userid, 'objextids.read');
                $usrrights['objextids.create'] = usrsysright::isUserHasRightByCode($userid, 'objextids.create');
                //$usrrights['objextids.update'] = usrsysright::isUserHasRightByCode($userid, 'objextids.update');
            }
            if (sysobj::isActiveByCode_cache('userorgs')) {
                $usrrights['userorgs.read'] = usrsysright::isUserHasRightByCode($userid, 'userorgs.read');
                $usrrights['userorgs.create'] = usrsysright::isUserHasRightByCode($userid, 'userorgs.create');
            }

            $usrrights['acs.admin'] = usrsysright::isUserHasRightByCode($userid, 'acs.admin');
        }


//        if (usrsysright::isUserHasRight($userid, 63)) {
//            //право создания сотрудников из зарегистрированных пользователей
//            $usrrights['save'] = true;
//        }

        //dd($usrrights);
        return $usrrights;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userid = Auth::user()->id;

        session(['pageno' => $request->page ?? 1]);


        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.save'),
        );

        if (!$usrrights['read'])
            return redirect()->route('home');

        // - параметры поиска -------------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_name' => ''
            , 's_email' => ''
            , 's_orgname' => ''
            , 's_online' => 1   //по умолчанию - пользователи, которые сейчас онлайн
            , 's_sysfuncid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and u.name like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_email') {
                    $sc = $sc . " and u.email like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_orgname') {
                    $sc = $sc . " and exists(select 1 from userorgs as uo"
                        . " join orgs as o on o.id=uo.orgid  and o.name like '%{$val}%'"
                        . " where uo.userid=u.id and uo.active=1"
                        . " and now() between uo.begdt and ifnull(uo.enddt,now()) )";

                } elseif ($item == 's_online') {
                    $sc = $sc . " and " . (($val == 0) ? ' not' : '')
                        . " exists(select 1 from sessions as s"
                        //. " where s.user_id=u.id and (" . now()->getTimestamp() . "-s.last_activity)<300)";
                        . " where s.user_id=u.id and (UNIX_TIMESTAMP()-s.last_activity)<300)";

                } elseif ($item == 's_sysfuncid') {
                    $sc = $sc . " and exists(select 1 from usrsysrights ur
                        where ur.userid=u.id and ur.active=1
                        and now() between ur.begdt and ifnull(ur.enddt,now())
                        and ur.sysfuncid={$val})";
                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------


        $sort_params = session('sort_params_users.index');
        if (isset($sort_params)) {
            $sort_by = $sort_params['field'];
            $sort_dir = $sort_params['dir'];
        } else {
//            $sort_by = 'u.created_at';
//            $sort_dir = 'desc';
            $sort_by = 'u.name';
            $sort_dir = 'asc';
        }

        $recs = User::from('users as u')
            ->where('u.id', '<>', 1)//замаскируем системного пользователя
            ->whereRaw($sc)
            ->select('u.*', DB::raw('date(u.created_at) as regdate'))
            ->selectraw('(SELECT GROUP_CONCAT(o.name SEPARATOR "; ")
                FROM userorgs AS uo
                JOIN orgs AS o ON o.id = uo.orgid
                WHERE uo.active = 1 and uo.userid=u.id
                ORDER BY uo.begdt) as lstUserOrgs')
            ->orderBy($sort_by, $sort_dir)
            ->paginate($search_params['s_pageitmcnt'] ?? 20);
        //->toSql();
        //dd($sc,$recs);

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;


        objlog::log_info($this->sysobjid, 0, $this->sysobjcode . ".index", 5);

        return view($this->objcode . '.index'
            , compact('recs', 'usrrights', 'search_params', 'data'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create()
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //массив с правами на операции в интерфейсе
        $usrrights = $this->setInterfaceRight($id);

        if (!$usrrights['read']) {
            return view('home');
        }

        $rec = User::find($id);


        //        $orgs = org::whereActive(1)->select('id', 'name')->pluck("name", "id")->prepend("", "");

//        $rec->sysroles = sysrole::whereActive(1)->get();
//        $rec->usrroles = usrsysrole::whereActive(1)
//            ->where('userId', $id)
//            ->select('sysroleid')->get()->pluck('sysroleid', 'sysroleid')
//            ->toArray();

        $rec->userorgs = userorg::from('userorgs as uo')
            ->join('orgs as o', 'o.id', 'uo.orgid')
            ->where('uo.userid', $rec->id)
            ->select('uo.id', 'o.name as orgname', 'uo.active', 'uo.begdt', 'uo.enddt', 'uo.postname'
                , DB::raw('(select count(*) from org_curators as oc
                where oc.userid=uo.userid and oc.orgid=uo.orgid
                 and now() between oc.begdt and ifnull(oc.enddt,now()) ) as curator')
            )
            ->orderby('o.name')
            ->get();


        $rec->extids = objextid::from('objextids as ei')
            ->join('extsystems as s', 's.id', 'ei.extsysid')
            ->where('sysobjid', $this->sysobjid)
            ->where('objid', $rec->id)
            ->select('ei.id', 's.name as extsysname', 'extid')
            ->orderby('s.name')
            ->get();

        $rec->log = objlog::where('sysobjid', 3)
            ->where('objid', $rec->id)
            ->select('write_at', 'info', 'errlvl')
            ->selectRaw('case
                          when errlvl = 1 then "fatalerror"
                          when errlvl = 2 then "error"
                          when errlvl = 3 then "info"
                          when errlvl = 4 then "warning"
                          when errlvl = 5 then "debug"
                          when errlvl = 6 then "trace"
                          else "?"
                        end as errlvlname')
            ->orderBy('write_at', 'DESC')->limit(12)
            ->get()
            ->toArray();
//dd($rec->log);

        $rec->user_acs = user_ac::from('user_acs as uac')
            ->join('acs as ac', 'ac.id', 'uac.acsid')
            ->where('uac.userid', $rec->id)
            ->select('uac.*', 'ac.name as ac_name')
            ->orderby('ac.ordr')
            ->orderby('ac.name')
            ->get();
        //dd( $rec->user_acs);

        $rec->user_roles = user_acl_role::from('user_acl_roles as uar')
            ->join('acl_roles as ar', 'ar.id', 'uar.roleid')
            ->where('uar.userid', $rec->id)
            ->select('uar.id','uar.roleid', 'ar.name as role_name','uar.reason','uar.active')
            ->orderby('ar.name')
            ->get();
        //dd( $rec->user_roles);

        //массив с правами на операции в интерфейсе
//        $usrrights = $this->setInterfaceRight($rec->id);

        // Массив с правами редактируемого пользователя
        //$rec->usrsysrights = usrsysright::UserRightLst($id,111,1);
        $rec->usrsysrights = usrsysright::UserRightLst($id);

        objlog::log_info($this->sysobjid, $rec->id, $this->sysobjcode . ".edit", 5);

        return view('users.edit', compact('rec', 'usrrights'));
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
        $request->validate([
            "lname" => "required",
            "fname" => "required",
            'phone' => 'nullable|min:6|max:20',
        ]);

        $userid = \Auth::user()->id;
//        $maylink2org = usrsysright::isUserHasRightByCode($userid, 'user2staff');

        $mess = "";
        if ($id == -1) {
            $rec = new user();
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о пользователе";
        } else {
            $rec = user::find($id);
            $mess = "Изменена запись о пользователе";
        }


        //20190521 SNS. Устарело
        //        if ($maylink2org)
//            $orgid = $request->get('orgid');

        $rec->lname = $request->get('lname');
        $rec->fname = $request->get('fname');
        $rec->mname = $request->get('mname');
        $rec->name = $rec->lname . ' ' . $rec->fname . ' ' . $rec->mname;

        //Телефон, указанный клиентом при само-регистрации не редактируется пользователем
        //на форме может не быть такого поля. Раскоментирование приведет к затиранию телефона
        //20190509 SNS - все поменялось - даем редактировать телефон пользователя
        $rec->phone = $request->get('phone');
        $rec->active = $request->get('active') ?? 0;

        $rec->birthdate = $request->get('birthdate');
        $rec->sex = $request->get('sex');

        $rec->updated_at = now();
        $rec->updated_by = $userid;

        $clrRightsCache = $rec->isDirty('active'); //признак необходимости зачистки кэша прав

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        //Обновим связанные данные -----------------------------------------------------------
//        mychat_user::addOrUpdate(
//            [
//                'userid' => $rec->id,
//            ],
//            [
//                'uin' => $request->get('mychat_uin')??0,
//                'active' => 1,
//                'updated_by' => $userid,
//                'updated_at' => now(),
//            ]);


        //Зачистим кэш прав ------------------------------------------------------------------
        if ($clrRightsCache)
            usrsysright::clearUserRightsCache($id); //все права, так как могли деактивировать пользователя
        //------------------------------------------------------------------------------------

        Cache::forget('UserInfo' . $rec->id);

        return redirect(route('users.index'))->with('success', $mess);
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
        $res = User::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('users.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $sd['success'] = 'Запись о пользователе (' . $id . ': '
                . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);
            //$orgid = $res->obj['orgid'];
            $route = route('users.index');
        }
        return redirect($route)->with($sd);
    }

    public
    static function call_CreateUserStaffID($id, $orgid)
    {
//        dd($id, $orgid);
        DB::statement('call createuserstaffid(:p_UserID, :p_OrgID);', array($id, $orgid));
    }

    public
    function edtSysRights($userid, $limsysobjid, $limobjid)
    {
        $usrrights = $this->setInterfaceRight(1);
        //dd($usrrights);
        if (!$usrrights['save']) {
            return view('home');
        }

        $limsysobjid = ($limsysobjid == 0) ? null : $limsysobjid;
        $limobjid = ($limobjid == 0) ? null : $limobjid;

        $user = User::find($userid);
        $sysfunclst = sysfunc::funcLst();

        $usrsysrights = usrsysright::UserRightLst($userid, $limsysobjid, $limobjid);
        $usrsysrights = $usrsysrights->groupBy('funcid')->toArray();
        //dd($userid, $limsysobjid, $limobjid, $usrsysrights);

        $usrrights = $this->setInterfaceRight($userid);

        $data = new \stdClass();
        $data->userid = $userid;
        $data->limsysobjid = $limsysobjid;
        if (isset($limsysobjid)) {
            $sysobj = sysobj::find($limsysobjid);
            if (isset($sysobj)) {
                $data->limsysobj_name = $sysobj->name;

                if (isset($limobjid) and isset($sysobj->model_class)) {
                    $model = 'App\\' . $sysobj->model_class;
                    $data->limobj_name = $model::find($limobjid)->name ?? '-?-';
                }
            }
        }
        $data->limobjid = $limobjid;
        $data->sysfunclst = $sysfunclst;

        return view('users.sysrights', compact('user', 'userid', "usrsysrights", "sysfunclst", "usrrights", 'data'));
    }

    public
    static function updUsrSysRights(Request $request, $id, $limsysobjid, $limobjid)
    {

        $limsysobjid = ($limsysobjid == 0) ? null : $limsysobjid;
        $limobjid = ($limobjid == 0) ? null : $limobjid;

        if (isset($request->rightid)) {
            $rights = array_flip($request->rightid);
        } else {
            //если ничего не передано - то надо удалить все
            $rights = [];
        }

        //сначала удалим те права, которые не переданы
        $usrsysrights = usrsysright::UserRightLst($id, $limsysobjid, $limobjid);
        //20190506 SNS/ Возможно нужно перейти на AdminRightLst()
        //$usrsysrights = usrsysright::AdminRightLst();


        for ($x = 0; $x <= count($usrsysrights) - 1; $x++) {
            //if (!isset($rights[$usrsysrights[$x]->funcid])) usrsysright::delUsrSysRight($id, $usrsysrights[$x]->funcid);
            if (!isset($rights[$usrsysrights[$x]->funcid]))
                usrsysright::delUsrSysRight($id, $usrsysrights[$x]->funcid, $limsysobjid, $limobjid);
        }

        //теперь расставим переданные права
        if (isset($request->rightid)) {
            for ($x = 0; $x <= count($request->rightid) - 1; $x++) {
                usrsysright::setUsrSysRight($id, $request->rightid[$x], $limsysobjid, $limobjid);
            }
        }

        //Зачистим кэш прав ------------------------------------------------------------------
        usrsysright::clearUserRightsCache($id);
        //------------------------------------------------------------------------------------

        objlog::log_info(3, $id, "обновлены права пользователя {$id}/{$limsysobjid}/{$limobjid}", 5);

        $retURL = $request->get('retURL') ?? route('users.edit', $id);
        return redirect($retURL);

    }

    public
    static function setCurOrgID($orgid)
    {
        User::setCurOrgID(Auth::user()->id, $orgid);
//        return "";
    }

    public
    function setDefaultPasswordForUser($userid)
    {
        if (isset($userid)) {
            $user = User::find($userid);
            if (isset($user)) {
                $user->password = bcrypt('123456');
                $user->save();

                objlog::log_info(3, $user->id, 'Администратор установил пароль по - умолчанию', 4);
                return redirect()->back()->with("success", 'Пароль успешно изменен на "123456"!');
            }
        }
        return redirect()->back()->with("error", "Пароль не изменен!");
    }

    public function clone_rights(Request $request, $id)
    {

        //массив с правами на операции в интерфейсе
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read']) {
            return view('home');
        }

        $returl = $request->get('returl') ?? route('users.edit', $id);
        $userid = \Auth::user()->id;

        if ($request->isMethod('POST')){
            $src_id = $request->src_id;
            $tgt_id = $request->tgt_id;
        }else{
            $src_id = null;
            $tgt_id = $id;
        }
        $data = new \stdClass();
        $data->returl = $returl;
        $data->src_id = $src_id;
        $data->src_name = null;
        $data->tgt_id = $tgt_id;
        $data->userid = $tgt_id;
        $data->tgt_name = null;

        $usrsysrights = null;
        if ($tgt_id <> '') {
            $rec = User::find($tgt_id);
            if (isset($rec)) {
                $data->tgt_name = $rec->lname . ' ' . $rec->fname . ' ' . $rec->mname . ' ' . $rec->email;

                $limsysobjid = null;
                $limobjid = null;
            }
        }

        if ($src_id <> '') {
            $rec = User::find($src_id);
            if (isset($rec))
                $data->src_name = $rec->lname . ' ' . $rec->fname . ' ' . $rec->mname . ' ' . $rec->email;
        }

        if ($src_id <> '' and $tgt_id <> '') {
            //Права, которые есть у пользователя-источника, но отсутствуют у пользователя-получателя
            // Но также у пользователя, который занимается переносом прав должны быть административные права на передаваемые права
            $sql = "SELECT f.id, sysfuncid, f.sysobjid as objid, f.adminrightid, o.code as objcode, o.name as objname, f.name as funcname
                    FROM usrsysrights as s
                    join sysfuncs as f on f.id = s.sysfuncid
                    join sysobjs as o on o.id=f.sysobjid
                    where s.userid={$src_id} and enddt is null
                    -- У получателя не должно быть такого права
                    and not exists (select 1 from usrsysrights as t where t.userid={$tgt_id} and t.sysfuncid=s.sysfuncid and enddt is null)
                    -- Админ должен обладать правом администратора на передаваемые права
                    and exists (select 1 from usrsysrights as ur where ur.userid={$userid} and ur.sysfuncid=f.adminrightid and enddt is null)
                    order by o.ordr, o.name";

            $sysfunclst = DB::select(DB::raw($sql));
            //dd($sysfunclst);
        } else {
            $sysfunclst = null;
        }
        objlog::log_info($this->sysobjid, $tgt_id, $this->sysobjcode . ".clone_rights", 5);

        return view($this->sysobjcode . '.clone_rights', compact('data', 'sysfunclst', 'usrrights'));
    }

    public
    static function save_cloned_rights(Request $request, $id, $limsysobjid, $limobjid)
    {
        $limsysobjid = ($limsysobjid == 0) ? null : $limsysobjid;
        $limobjid = ($limobjid == 0) ? null : $limobjid;

        //теперь расставим переданные права
        if (isset($request->rightid)) {
            for ($x = 0; $x <= count($request->rightid) - 1; $x++) {
                usrsysright::setUsrSysRight($id, $request->rightid[$x], $limsysobjid, $limobjid);
            }
        }

        //Зачистим кэш прав ------------------------------------------------------------------
        usrsysright::clearUserRightsCache($id);
        //------------------------------------------------------------------------------------

        objlog::log_info(3, $id, "обновлены права пользователя {$id}/{$limsysobjid}/{$limobjid}", 5);

        //$retURL = $request->get('retURL') ?? route('users.edit', $id);
        $retURL = route('users.edit', $id);
        return redirect($retURL);

    }

}
