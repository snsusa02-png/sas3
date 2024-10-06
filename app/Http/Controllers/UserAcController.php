<?php

namespace App\Http\Controllers;

use App\ac;
use App\user_ac;
use App\sysobj;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\objflag;
use App\objlog;
use App\orgpost;
use App\orgstaff;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserAcController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1502;
        $this->parsysobjid = 1501; //Acs
        $this->sysobjcode = 'user_acs';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        //по acl указанного объекта
//        $acl_sysobjcode = sysobj::where('code', $this->sysobjcode)
//                ->select(db::raw("ifnull(acl_sysobjcode, code) as acl_sysobjcode"))
//                ->first()
//                ->acl_sysobjcode ?? $this->sysobjcode;

        $usrrights = array();
        $usrrights['acs.admin'] = usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin');

        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
//        $usrrights['read'] = $usrrights['acs.admin'];
//        $usrrights['create'] = $usrrights['acs.admin'];
        $usrrights['update'] =  usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');;
        $usrrights['delete'] =  usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');;
        $usrrights['admindelete'] =  usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.admindelete');;

        $usrrights['save'] = $usrrights['update'];
        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
            $usrrights['admindelete'] = false;
        }

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
//        dd($usrrights);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $rec = new user_ac([
                    'id' => -1,
                    'userid' => $user_id,
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else {
                $rslt = ['error' => 'У вас нет права на это действие!'];
                if (isset($user_id))
                    return redirect(route('users.edit', $user_id))->with($rslt);
                else
                    return redirect(route('users.index'))->with($rslt);
            }
        } else {
            $rec = user_ac::find($id);
        }

        if (!isset($rec))
            return redirect(route('users.edit', $user_id));

        $rec->acs = ac::lstFor(
            ['new_for_user_or_current' => [$rec->userid, $rec->acsid],]
        );
        //dd($rec->userid, $rec->acsid,$rec->acs);

        $rec->retURL = $request->get('returl') ?? route('users.edit', $rec->userid);

        return view('user_acs.edit', compact('rec', "usrrights"));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\user_ac $rec
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, user_ac $rec)
    public function update(Request $request, $id)
    {
        //

        $messages = [
            'acsid.required' => 'Не указана категория информации',
            'userid.required' => 'Не указан пользователь',
        ];

        $rules = [
            "acsid" => "required",
            "userid" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {

            $usrid = $request->get('userid');
            $rec = new user_ac([
                "userid" => $usrid,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = user_ac::find($id);
            $mess = "Запись обновлена";
        }

        $rec->acsid = $request->get('acsid');

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        Cache::forget("user_{$rec->userid}_has_acs_{$rec->acsid}");

        $retURL = $request->get('retURL') ?? route('users.edit', $rec->userid) . '?#user_acs';

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
        $res = user_ac::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('user_acs.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['userid'];
            $usrid = $res->obj['userid'];
            $acsid = $res->obj['acsid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о доступе пользователя {$parobjid} к категории информации ({$acsid}', 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //забудем кэшированные данные про ...:
            Cache::forget("user_{$usrid}_has_acs_{$acsid}");


            $route = route('users.edit', $parobjid);
            $sd['success'] = 'Доступ отозван';
        }
        return redirect($route)->with($sd);
    }

    static public function list_for(Request $request)
    {
        //2021-06-09 SNS. Обертка для вызова user_ac::lstFor

        $result = "";
        try {

            $list = user_ac::lstFor([
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
