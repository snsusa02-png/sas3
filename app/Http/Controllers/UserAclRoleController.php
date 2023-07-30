<?php

namespace App\Http\Controllers;

use App\user_acl_role;
use App\acl_role;
use App\objlog;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class UserAclRoleController extends Controller
{
    use SearchDataTrait;
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1555;
        $this->parsysobjid = 3; //users
        $this->sysobjcode = 'user_acl_roles';
        $this->model = 'App\user_acl_role';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    protected function setInterfaceRight($recid)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');

        if ($recid > 0) {
            //для существующих записей проверим открытость периода
            //if ($this->model::isLocked($recid)) {

//                $usrrights['save'] = false;
//                $usrrights['delete'] = false;
//                $usrrights['admindelete'] = false;
            //}
        } else {
            $usrrights['delete'] = false;
            $usrrights['admindelete'] = false;
        }

        $usrrights['edit'] = $usrrights['save'];

        return $usrrights;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request, $user_id = null)
    {
        return $this->edit($request, -1, $user_id);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\user_ac $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(user_ac $rec)
    public function edit(Request $request, $id, $user_id = null)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $rec = new $this->model([
                    'id' => -1,
                    'userid' => $user_id,
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else {
                $rslt = ['error' => 'У вас нет права на это действие!'];
                if (isset($staffid))
                    return redirect(route('orgstaff.edit', $staffid))->with($rslt);
                else
                    return redirect(route('orgstaff.index'))->with($rslt);
            }
        } else {
            $rec = $this->model::find($id);
        }

        $rec->_obj_info = $rec->user->Info;

        $rec->roles = acl_role::lstFor([
            'userid' => $rec->userid,
            'active_or_current' => $rec->roleid ?? -1,
        ]);

        if (!isset($rec))
            return redirect(route('users.edit', $user_id));

        $rec->retURL = $request->get('returl') ?? route('users.edit', $rec->userid);

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\user_ac $rec
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $messages = [
            'userid.required' => 'Укажите пользователя',
            'roleid.required' => 'Укажите роль',
            'id' => 'Укажите другую роль',
        ];

        $rules = [
            "userid" => "required",
            "roleid" => "required",
        ];

        $request->validate($rules, $messages);

        //Проверка, что указанная роль ранее не была присвоена пользователю -----------
        if (1 == 1) {

            $user_id = $request->get('userid');
            $roleid = $request->get('roleid');
            $cnt = user_acl_role::where(['userid' => $user_id, 'roleid' => $roleid])
                ->where('id', '<>', $id)
                ->count();
            // Здесь "id" - это имя существкющего на форме поля ввода
            $rules = [
                "id" => [
                    function ($attribute, $value, $fail) use ($id, $user_id, $roleid) {
                        //
                        $cnt = user_acl_role::where(['userid' => $user_id, 'roleid' => $roleid])
                            ->where('id', '<>', $id)
                            ->count();
                        if ($cnt > 0) {
                            $fail("Эта же роль уже есть у этого пользователя! Укажите другую роль.");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);
        }
        //----------------------------------------------------------------------

        $userid = \Auth::user()->id;
        $mess = "";
        $user_id = $request->get('userid');
        if ($id == -1) {

            $rec = new $this->model([
                "userid" => $user_id,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = $this->model::find($id);
            $mess = "Запись обновлена";
        }

        //$rec->userid = $user_id;
        $rec->roleid = $request->get('roleid');
        $rec->reason = $request->get('reason');

        $rec->active = $request->get('active') ?? 1;

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        //Cache::forget("user_{$usrid}_has_acs_{$rec->acsid}");

        // Назначим права пользователя в соответствии с выбранной ролью ----------------

        // Если ранее была указана другая роль, то отзовем права той роли
        $pre_roleid = $request->get('pre_roleid');
        if (isset($pre_roleid) and $pre_roleid <> $rec->roleid) {
            //dd($user_id, $pre_roleid, $rec->roleid);
            user_acl_role::delRoleRights($user_id, $pre_roleid);
        }
        //Назначим права от новой роли
        user_acl_role::addRoleRights($user_id, $rec->roleid);
        //------------------------------------------------------------------------------

        $retURL = $request->get('retURL') ?? route('users.edit', $rec->userid) . '?#acl_roles';
        return redirect($retURL)->with('success', $mess);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\user_ac $rec
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = $this->model::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route($this->sysobjcode.'.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['userid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о начислении ЗП', 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //отзовем права пользователя, соответствующие правам отзываемой роли доступа
            user_acl_role::delRoleRights($res->obj['userid'], $res->obj['roleid']);

            //забудем кэшированные данные про ...:
            //Cache::forget("user_{$usrid}_has_acs_{$acsid}");

            $route = route('users.edit', $parobjid);
            $sd['success'] = 'Запись удалена';
        }
        return redirect($route)->with($sd);
    }

    static public function list_for(Request $request)
    {
        //2021-06-09 SNS. Обертка для вызова user_ac::lstFor

        $result = "";
        try {

            $list = $this->model::lstFor([
                'orgid' => $request->orgid,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
                'with_posts' => $request->with_posts,
                'with_post_vacancies' => $request->with_post_vacancies,
                'with_post_vacancies_staff' => $request->with_post_vacancies_staff,
            ]);


            $result = array('user_acs' => $list);

        } catch (\Exception $e) {
            Log::error('user_acs::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
