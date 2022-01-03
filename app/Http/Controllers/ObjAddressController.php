<?php

namespace App\Http\Controllers;

use App\obj_address;
use App\addresstype;
use App\objlog;
use App\sysobj;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class ObjAddressController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1922;
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

            //замена на родительскую модель/таблицу
            $sysobjcode = sysobj::acl_sysobjcode($sysobj->code);

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
     * @param \App\obj_address $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(obj_address $rec)
    public function edit(Request $request, $id, $sysobjid = null, $objid = null)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id, $sysobjid);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $rec = new obj_address([
                    'id' => -1,
                    'sysobjid' => $sysobjid,
                    'objid' => $objid,
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else
                return redirect(route('orgs.edit', $objid));
        } else {
            $rec = obj_address::find($id);
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

        $rec->addresstypes = addresstype::lstFor(
            ['active_or_current' => $rec->addresstypeid]
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
     * @param \App\obj_address $rec
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, obj_address $rec)
    public function update(Request $request, $id)
    {
        //
        $messages = [
            'sysobjid.required' => 'Не указан тип объекта ИС',
            'objid.required' => 'Не указан объект ИС',
            'street.required' => 'Укажите улицу',
            'addresstypeid.required' => 'Укажите тип адреса',
        ];

        $rules = [
            "sysobjid" => "required",
            "objid" => "required",
            //"street" => "required",
            "addresstypeid" => "required",
        ];

        $request->validate($rules, $messages);


        $userid = \Auth::user()->id;
        if ($id == -1) {

            $rec = new obj_address([
                "sysobjid" => $request->get('sysobjid'),
                "objid" => $request->get('objid'),
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = obj_address::find($id);
            $mess = "Запись обновлена";
        }

        $rec->addresstypeid = $request->get('addresstypeid');

        $rec->zip = mb_substr($request->get('zip'), 0, 6);
        $rec->country = mb_substr($request->get('country'), 0, 30);
        $rec->region = mb_substr($request->get('region'), 0, 30);
        $rec->city = mb_substr($request->get('city'), 0, 30);
        $rec->street_adr = mb_substr($request->get('street_adr'), 0, 60);
//        $rec->streettype = mb_substr($request->get('streettype'), 0, 16);
//        $rec->street = mb_substr($request->get('street'), 0, 60);
//        $rec->corpus = mb_substr($request->get('corpus'), 0, 16);
//        $rec->building = mb_substr($request->get('building'), 0, 16);
//        $rec->appartment = mb_substr($request->get('appartment'), 0, 16);

        $rec->notes = mb_substr($request->get('notes'), 0, 160);
        //$rec->private = 0;
        $rec->active = $request->get('active', 0);

        $rec->address=' ';

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        //dd($rec);
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        $retURL = $request->get('retURL') ?? route($rec->sysobj->code . '.edit', $rec->objid) . '?#obj_addresss';

        return redirect($retURL)->with('success', $mess);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\obj_address $rec
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = obj_address::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('obj_addresss.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['objid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о контакте (' . $parobjid . ')', 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //забудем кэшированные данные про ...:
            //Cache::forget('obj_address.lstUserActiveOrgs.' . $parobjid);

            $route = route('orgs.edit', $parobjid);
            $sd['success'] = 'Запись о названии организации удалена';
        }
        return redirect($route)->with($sd);
    }

    static public function list_for(Request $request)
    {
        //2021-06-09 SNS. Обертка для вызова obj_address::lstFor

        $result = "";
        try {

            $list = obj_address::lstFor([
                'orgid' => $request->orgid,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
            ]);


            $result = array('obj_addresss' => $list);

        } catch (\Exception $e) {
            Log::error('obj_addresss::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

    public function print_envelope(Request $request, $id)
    {

        $userid = \Auth::user()->id;


            $rec = obj_address::find($id);

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

        $rec->addresstypes = addresstype::lstFor(
            ['active_or_current' => $rec->addresstypeid]
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
                    $obj = $model::find($rec->objid);
                    $rec->_obj_info = $obj->Info;
                    $retroute = route($blades . ' . edit', $rec->objid);
                }
            }
        }
        $rec->obj = $obj;
        $rec->retURL = $retroute;
        //dd($rec);

        return view($this->sysobjcode . '.print_envelope', compact('rec', "usrrights"));
    }

}
