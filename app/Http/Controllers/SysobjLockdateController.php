<?php

namespace App\Http\Controllers;

use App\sysobj_lockdate;
use App\objlog;
use App\sysobj;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SysobjLockdateController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjcode = 'sysobj_lockdates';
    }

    protected function setInterfaceRight($sysobjid)
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

        $obj_sysobj = sysobj::find($sysobjid);
        if (isset($obj_sysobj)) {

            //замена на родительскую модель/таблицу
            $sysobjcode = $obj_sysobj->code;

            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.set_lockdate');
            $usrrights['create'] = $usrrights['delete'] = $usrrights['save'];
        }

        return $usrrights;
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\obj_address $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(obj_address $rec)
    public function edit(Request $request, $sysobjid)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($sysobjid);
        if (!($usrrights['save']))
            return redirect()->back()->with('error', 'У вас нет права на изменение этих данных!');

        $rec = sysobj_lockdate::find($sysobjid);
        if (!isset($rec)) {

            $rec = new sysobj_lockdate([
                'sysobjid' => $sysobjid,
            ]);
        }

        $sysobj = sysobj::find($rec->sysobjid);
        $rec->_sysobj_name = $sysobj->name;

//        if (isset($sysobj->model_class)) {
//            $model = "App\\{$sysobj->model_class}";
//            $obj = $model::find($rec->objid);
//            //dd($model, $rec->objid, $obj);
//            if (isset($obj))
//                $rec->_obj_info = $obj->Info;
//        }

        $retroute = $request->get('returl') ?? url()->previous();
        if (!isset($retroute)) {

            if (isset($sysobj->code)) {
                $retroute = route(strtolower($sysobj->code) . '.edit', $rec->sysobjid);
            }
        }
        $rec->retURL = $retroute;

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\obj_address $rec
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $sysobjid)
    {
        //
        $messages = [
            'lockdate.required' => 'Укажите дату блокировки',
        ];

        $rules = [
            //"sysobjid" => "required",
            "lockdate" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $rec = sysobj_lockdate::find($sysobjid);
        if (!isset($rec)) {
            $rec = new sysobj_lockdate([
                "sysobjid" => $sysobjid,
            ]);
            $mess = "Запись создана";
        } else {
            $mess = "Запись обновлена";
        }

        $rec->lockdate = $request->get('lockdate');
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        //dd($rec);
        $rec->save();


        objlog::log_info($rec->sysobjid, $rec->id, $mess, 5);

        //dd($request->get('retURL'),$rec->sysobj);
        $retURL = $request->get('retURL') ?? route($rec->sysobj->code . '.index') . '#lockdate';

        return redirect($retURL)->with('success', $mess);

    }
}
