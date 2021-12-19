<?php

namespace App\Http\Controllers;

use App\contacttype;
use App\obj_contact;
use App\objlog;
use App\sysobj;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ObjContactController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1902;
        $this->sysobj = sysobj::find($this->sysobjid);
        $this->sysobjcode = $this->sysobj->code;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
        $this->parsysobjid = null;
    }

    protected function setInterfaceRight($id, $sysobjid)
    {
        /*
         * Формирует массив прав пользователя на текущий объект ($id)
         * на основании прав пользователя на родительский объект ($sysobjid)
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = false;
        $usrrights['create'] = false;
        $usrrights['save'] = false;
        $usrrights['delete'] = false;

        $sysobj = sysobj::find($sysobjid);
        if (isset($sysobj)) {

            $sysobjcode = $sysobj->code;

            //замена на родительскую модель/таблицу
            //Заляпуха - todo: ввести в sysobjs поле src_acl
            if ($sysobjcode == 'jts_items')
                $sysobjcode = 'jobtimesheets';
            if ($sysobjcode == 'jts_machines')
                $sysobjcode = 'jobtimesheets';
            if ($sysobjcode == 'jts_violations')
                $sysobjcode = 'jobtimesheets';
            elseif ($sysobjcode == 'jts_mchn_visits')
                $sysobjcode = 'jobtimesheets';
            elseif ($sysobjcode == 'orgdeps')
                $sysobjcode = 'org_acnts';
            elseif ($sysobjcode == 'orgposts')
                $sysobjcode = 'org_acnts';

            $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.read');
            $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.create');

            if ($id == -1) {
                $usrrights['save'] = $usrrights['create'];
                $usrrights['delete'] = false;
            } else {
                $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.update');
                $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.delete');
            }
        }

        return $usrrights;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request, $sysobjid, $objid)
    {
        return $this->edit($request, -1, $sysobjid, $objid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\obj_contact $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(obj_contact $rec)
    public function edit(Request $request, $id, $sysobjid = null, $objid = null)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id, $sysobjid);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $rec = new obj_contact([
                    'id' => -1,
                    'sysobjid' => $sysobjid,
                    'objid' => $objid,
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else
                return redirect(route('orgs.edit', $objid));
        } else {
            $rec = obj_contact::find($id);
        }

        if (!isset($rec))
            return redirect(route('orgs.edit', $objid));

        $usrrights = $this->setInterfaceRight($id, $rec->sysobjid);

        $sysobj = sysobj::find($rec->sysobjid);
        $rec->_sysobj_name = $sysobj->name;

        if (isset($sysobj->model_class)) {
            $model = "App\\{$sysobj->model_class}";
            $obj = $model::find($rec->objid);
            //dd($model, $rec->objid, $obj);
            if (isset($obj))
                $rec->_obj_info = $obj->Info;
        }

        $rec->contacttypes = contacttype::lstFor(
            ['active_or_current' => $rec->contacttypeid]
        );
        //dd(($rec));


        $retroute = $request->get('returl');
        if (!isset($retroute)) {

            if (isset($sysobj->code)) {
                $retroute = route(strtolower($sysobj->code) . '.edit', $rec->objid);

            } else {

                if ($rec->sysobjid == 951) {
                    $model = 'App\event';
                    $blades = 'events';
                    $obj = $model::find($objid);
                    $rec->_obj_info = $obj->Info;
                    $retroute = route($blades . ' . edit', $rec->objid);
                }
            }
        }
        $rec->retURL = $retroute;
        //dd($rec);

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\obj_contact $rec
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, obj_contact $rec)
    public function update(Request $request, $id)
    {
        //
        $messages = [
            'sysobjid.required' => 'Не указан тип объекта ИС',
            'objid.required' => 'Не указан объект ИС',
            'contact.required' => 'Укажите контактные данные',
            'contacttypeid.required' => 'Укажите тип контакта',
        ];

        $rules = [
            "sysobjid" => "required",
            "objid" => "required",
            "contact" => "required",
            "contacttypeid" => "required",
        ];

        $request->validate($rules, $messages);


        $userid = \Auth::user()->id;
        if ($id == -1) {

            $rec = new obj_contact([
                "sysobjid" => $request->get('sysobjid'),
                "objid" => $request->get('objid'),
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = obj_contact::find($id);
            $mess = "Запись обновлена";
        }

        $rec->contacttypeid = $request->get('contacttypeid');
        $rec->contact = mb_substr($request->get('contact'), 0, 60);
        $rec->notes = mb_substr($request->get('notes'), 0, 160);
        $rec->private = 0;
        $rec->active = $request->get('active', 0);

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        $retURL = $request->get('retURL') ?? route('orgs.edit', $rec->objid) . '?#obj_contacts';

        return redirect($retURL)->with('success', $mess);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\obj_contact $rec
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = obj_contact::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('obj_contacts.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['orgid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о названии организации (' . $parobjid . ')', 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //забудем кэшированные данные про ...:
            //Cache::forget('obj_contact.lstUserActiveOrgs.' . $parobjid);

            $route = route('orgs.edit', $parobjid);
            $sd['success'] = 'Запись о названии организации удалена';
        }
        return redirect($route)->with($sd);
    }

    static public function list_for(Request $request)
    {
        //2021-06-09 SNS. Обертка для вызова obj_contact::lstFor

        $result = "";
        try {

            $list = obj_contact::lstFor([
                'orgid' => $request->orgid,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
            ]);


            $result = array('obj_contacts' => $list);

        } catch (\Exception $e) {
            Log::error('obj_contacts::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
