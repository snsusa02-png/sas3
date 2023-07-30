<?php

namespace App\Http\Controllers;

use App\acl_role;
use App\acl_role_right;
use App\objflag;
use App\objlog;
use App\org;
use App\sysfunc;
use App\sysobj;
use App\Traits\Result;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\User;
use App\user_acl_role;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class AclRoleController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1551;
        $this->sysobjcode = 'acl_roles';
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
        //$usrrights['load'] = usrsysright::isUserHasRightByCode_cached($userid, 'admin-global');

        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = ($recid <> -1 and usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.admindelete'));
//        $usrrights['private_acs'] = ($recid <> -1 and usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.private_acs'));
//        $usrrights['private_acs'] = (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.private_acs'));

        if ($recid == -1) {
            // для новой записи
            $usrrights['save'] = $usrrights['create'];

        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
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
        if (!$usrrights['read']) {
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');
        }


        session([$this->sysobjcode . '_pageno' => $request->page ?? 1]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_active' => '1'
            , 's_name' => ''
        ];

        $search_params = $this->search_params($request, $param_names);
        //сформируем условие запроса в БД -----------------------
        $sc = acl_role::search_cond($search_params);
        //dd($sc);
        //-------------------------------------------------------

        $recs = acl_role::from('acl_roles as ar')
            ->whereraw($sc)
            ->select('ar.id', 'ar.name', 'ar.descript', 'ar.active'
                , db::raw('(select count(1) from acl_role_rights as r where r.roleid=ar.id) as rights_cnt')
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        $recs = $recs->orderBy('ar.name', 'asc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('ar.name', 'asc');
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

        objlog::log_info($this->sysobjid, 0, $this->sysobjcode . ".index", 5);

        return view($this->sysobjcode . '.index', compact(['recs', 'data', 'usrrights']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        $usrrights = $this->setInterfaceRight(-1);
        if (!$usrrights['create'])
            return redirect()->back()->with('error', 'У вас нет права на создание записей!');

        return $this->edit($request, -1);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        //
        $usrrights = $this->setInterfaceRight($id);
//        dd($usrrights);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');

        $userid = \Auth::user()->id;

        if ($id == -1) {
            $rec = new acl_role([
                'id' => -1,
                'active' => 1,
                'created_by' => $userid,
            ]);
//            dd($rec);
        } else
            $rec = acl_role::find($id);

        if (!isset($rec))
            return redirect(route($this->sysobjcode . '.index'))->with(['error' => 'Запись не найдена!']);

        $rec->retURL = $request->get('returl');

        //$data = new \stdClass();

        if ($id <> -1) {

            //Список прав, соответствующих роли
            $rec->role_rights = acl_role_right::RoleRightLst($rec->id);

            // Список сотрудников, использующих данную роль
            $rec->role_users = user_acl_role::from('user_acl_roles as uar')
                ->join('users as u', 'u.id', 'uar.userid')
                ->where('uar.roleid', $rec->id)
                ->select('u.id', 'u.name', 'u.email')
                ->orderby('u.name')
                ->get();
        }else{
            $rec->role_rights=[];
            $rec->role_users=[];
        }

        objlog::log_info($this->sysobjid, $rec->id, $this->sysobjcode . ".edit", 5);

        return view($this->sysobjcode . '.edit', compact(['rec', 'usrrights']));
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
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');
        if ($id == -1 and !$usrrights['create'])
            return redirect()->back()->with('error', 'У вас нет права на создание записей!');
        if ($id <> -1 and !$usrrights['save'])
            return redirect()->back()->with('error', 'У вас нет права на изменение записей!');

        $messages = [
            'name.required' => 'Введите название роли',
        ];

        $rules = [
            "name" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $mess = "";
        if ($id == -1) {
            $rec = new acl_role();
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о применяемом начислении для сотрудника";
        } else {
            $rec = acl_role::find($id);
            $mess = "Изменена запись о применяемом начислении для сотрудника";
        }
        $rec->name = $request->get('name');
        $rec->descript = mb_substr($request->get('descript'), 0, 360);

        $rec->active = $request->get('active') ?? 1;
        $rec->updated_by = $userid;
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $this->sysobjcode . ".update", 5);

        //Cache::forget('org_aux_staff_.' . $rec->orgid);

        $retURL = $request->get('returl') ?? route($this->sysobjcode . '.index')
            . '?page=' . session($this->sysobjcode . '_pageno') . '#' . $rec->id;

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
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['delete'])
            return redirect()->back()->with('error', 'У вас нет права на удаление записей!');

        $userid = \Auth::user()->id;

        $retURL = $request->get('returl') ?? route($this->sysobjcode . '.edit', $id);

        if (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete')) {

            $res = acl_role::delete_by_id($id);
            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $sd["error"] = $res->msg;
            } else {

                $retURL = $request->get('returl') ?? route($this->sysobjcode . '.index');
                $sd['success'] = 'Запись о сотруднике удалена';
            }
        } else {
            $sd['success'] = 'У вас нет прав на удаление записей!';
        }
        return redirect($retURL)->with($sd);
    }


    static public function listacl_role(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $orgid = $request->orgid;
            $list = acl_role::where('orgid', $orgid)
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


    static public function get_for(Request $request)
    {
        //2021-07-03 SNS. Обертка для вызова acl_role::getFor

        $result = "";
//        try {

        $list = acl_role::getFor([
            'active' => $request->active,
            'active_or_current' => $request->active_or_current,
            'in_documents' => $request->in_documents,
            'name' => $request->name ?? $request->q,
            'q' => $request->q,
            'orgid' => $request->orgid,
        ], [
            'oc.id', db::raw("concat(oc.name,', ', ifnull(op.name,oc.postname), ' ', o.name) as name")
        ]);


        //$result = array('doctypes' => $list);
        $result = $list;

//        } catch (\Exception $e) {
//            Log::error('acl_role::list_for:' . $e->getMessage());
//        }
        return response()->json($result);
    }


    static public function list_for_ac(Request $request)
    {
        //2023-03-19 SNS. Для автокомплита

        $result = "";
        try {

            $list = acl_role::getFor([
                'name' => $request->name,
                'orgid' => $request->orgid,
            ],
                ['oc.id', 'ct.name', 'ct.dir', 'oc.charge_sum']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('acl_role::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }

    public
    function edtRoleRights($roleid)
    {
        $usrrights = $this->setInterfaceRight(1);
        if (!$usrrights['save']) {
            return view('home');
        }

        $role = acl_role::find($roleid);
        $sysfunclst = sysfunc::funcLst();

        //
        $subj_rights = acl_role_right::RoleRightLst($roleid);
        $subj_rights = $subj_rights->groupBy('funcid')->toArray();

        $usrrights = $this->setInterfaceRight($roleid);

        $data = new \stdClass();
        $data->roleid = $roleid;
        $data->sysfunclst = $sysfunclst;

        return view('acl_roles.role_rights_edit', compact('role', "subj_rights", "sysfunclst", "usrrights", 'data'));
    }

    public
    static function updRoleRights(Request $request, $id)
    {

        if (isset($request->rightid)) {
            $rights = array_flip($request->rightid);
        } else {
            //если ничего не передано - то надо удалить все
            $rights = [];
        }
        //dd($id, $rights);

        //сначала удалим те права, которые не переданы ------------------------------
        // получим список текущих прав роли
        $subj_rights = acl_role_right::RoleRightLst($id);

        for ($x = 0; $x <= count($subj_rights) - 1; $x++) {
            //if (!isset($rights[$usrsysrights[$x]->funcid])) usrsysright::delUsrSysRight($id, $usrsysrights[$x]->funcid);
            if (!isset($rights[$subj_rights[$x]->funcid]))
//                usrsysright::delUsrSysRight($id, $subj_rights[$x]->funcid, $limsysobjid, $limobjid);
                acl_role_right::delRoleRight($id, $subj_rights[$x]->funcid);
        }
        //---------------------------------------------------------------------------

        //теперь расставим переданные права
        if (isset($request->rightid)) {
            for ($x = 0; $x <= count($request->rightid) - 1; $x++) {
                acl_role_right::setRoleRight($id, $request->rightid[$x]);
            }
        }

        //Зачистим кэш прав ------------------------------------------------------------------
        acl_role_right::clearRoleRightsCache($id);
        //------------------------------------------------------------------------------------

        objlog::log_info(3, $id, "обновлены права роли доступа {$id}", 5);

        $retURL = $request->get('retURL') ?? route('acl_roles.edit', $id);
        return redirect($retURL);

    }

}
