<?php

namespace App\Http\Controllers;

use App\objlog;
use App\org_name;
use App\orgnametype;
use App\sysobj;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrgNameController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1622;
        $this->sysobj = sysobj::find($this->sysobjid);
        $this->sysobjcode = $this->sysobj->code; //'staff_posts';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
        $this->parsysobjid = 111; //orgs
    }

    protected function setInterfaceRight($id)
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

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
        }

        return $usrrights;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request, $orgid = null)
    {
        return $this->edit($request, -1, $orgid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\org_name $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(org_name $rec)
    public function edit(Request $request, $id, $orgid = null)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {


                $rec = new org_name([
                    'id' => -1,
                    'orgid' => $orgid,
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else
                return redirect(route('orgs.edit', $orgid));
        } else {
            $rec = org_name::find($id);
        }

        if (!isset($rec))
            return redirect(route('orgs.edit', $orgid));


        $rec->nametypes = orgnametype::lstFor(
            ['active_or_current' => $rec->id]
        );
        //dd(($rec));


        $rec->retURL = $request->get('returl');

        return view($this->sysobjcode.'.edit', compact('rec', "usrrights"));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\org_name $rec
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, org_name $rec)
    public function update(Request $request, $id)
    {
        //
        $messages = [
            'orgid.required' => 'Не указана организация',
            'name.required' => 'Укажите название',
            'begdate.required' => 'Укажите дату начала действия названия',
        ];

        $rules = [
            "orgid" => "required",
            "begdate" => "required",
        ];

        $request->validate($rules, $messages);


        $userid = \Auth::user()->id;
        if ($id == -1) {

            $rec = new org_name([
                "orgid" => $request->get('orgid'),
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = org_name::find($id);
            $mess = "Запись обновлена";
        }

        $rec->nametypeid = $request->get('nametypeid');
        $rec->name = mb_substr($request->get('name'), 0, 300);
        $rec->begdate = $request->get('begdate');
        $rec->enddate = $request->get('enddate');
        $rec->lang = $request->get('lang', 'ru');
        $rec->active = $request->get('active', 0);

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        $retURL = $request->get('retURL') ?? route('orgs.edit', $rec->orgid) . '?#org_names';


//        if ($id == -1)
//            //return redirect(route('staff_posts.edit', $rec->id))->with('success', $mess);
//            return redirect(route('orgs.edit', $rec->orgid))->with('success', $mess);
//        else
//            return redirect(route('orgs.edit', $rec->orgid))->with('success', $mess);

        return redirect($retURL)->with('success', $mess);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\org_name $rec
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = org_name::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('org_names.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['orgid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о названии организации (' . $parobjid . ')', 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //забудем кэшированные данные про ...:
            //Cache::forget('org_name.lstUserActiveOrgs.' . $parobjid);

            $route = route('orgs.edit', $parobjid);
            $sd['success'] = 'Запись о названии организации удалена';
        }
        return redirect($route)->with($sd);
    }

    static public function list_for(Request $request)
    {
        //2021-06-09 SNS. Обертка для вызова org_name::lstFor

        $result = "";
        try {

            $list = org_name::lstFor([
                'orgid' => $request->orgid,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
            ]);


            $result = array('org_names' => $list);

        } catch (\Exception $e) {
            Log::error('org_names::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
