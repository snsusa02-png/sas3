<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\expensetype;
use App\obj_doc;
use App\objlog;
use App\doctype;
use App\sysobj;
use App\usrsysright;
use App\wrhdoc;
use Illuminate\Http\Request;

class ObjDocController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 923;
        $this->sysobjcode = 'obj_docs';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }


    protected function setInterfaceRight($id, $sysobjcode = null)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $sysobjcode = $sysobjcode ?? $this->objcode;

        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['edtrights'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.delete');
            $usrrights['edtrights'] = usrsysright::isUserHasRightByCode($userid, 'admin-global');
        }

        return $usrrights;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $sysobjid, $objid)
    {
        return $this->edit($request, -1, $sysobjid, $objid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $sysobjid = null, $objid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи
//            $ordr = obj_doc::where(['sysobjid' => $sysobjid, 'objid' => $objid])->max('ordr') ?? 0;
//            $ordr += 1;

            $rec = new obj_doc([
                'id' => -1,
                'sysobjid' => $sysobjid,
                'objid' => $objid,
//                'ordr' => $ordr,
                'active' => 1,
                'created_by' => \Auth::user()->id,
            ]);
        } else
            $rec = obj_doc::find($id);

        if (!isset($rec))
            return redirect(route('home'));

        $rec->retURL = $request->get('returl');

        //$data = new \stdClass();

//        $rec->userid = orgstaff::find($rec->staffid)->userid ?? null;
        $sysobj = sysobj::find($rec->sysobjid);
        $rec->_sysobj_name = $sysobj->name;
        //dd($rec->_sysobj_name);

        if (isset($sysobj->model_class)) {
            $model = "App\\{$sysobj->model_class}";
            $obj = $model::find($rec->objid);
            //dd($model, $rec->objid, $obj);
            if (isset($obj)){
                $rec->_obj_info = $obj->Info;
                if ($id == -1){
                    $rec->orgid = $obj->ownorgid;
                    $rec->operdate = $obj->operdate;
                }
            }
        }
//dd($sysobj, $rec->_obj_info);

        //Возьмем права от родительской системы:
        $usrrights = $this->setInterfaceRight($rec->id, $sysobj->code);

        if ($usrrights['save'] ?? false) {
            $rec->doctypes = doctype::lstFor(['active_or_current' => 1, 'for_sysobjid' => $rec->sysobjid]);
        } else {
            $rec->doctypes = null;
        }
//        dd($usrrights );
//        if ($id != -1 and $usrrights['edtrights'])
//            $rec->usrsysrights = usrsysright::UserRightLst($rec->userid, 466, $rec->buildobjid);

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        $request->validate([
            "sysobjid" => "required",
            "objid" => "required",
            "doctypeid" => "required",
            "docnum" => "required",
            "docdate" => "required",
            //"rolename.*" => "required",
        ]);

        $userid = \Auth::user()->id;

        $mess = "";
        $sysobjid = $request->get('sysobjid');
        $objid = $request->get('objid');

        if ($id == -1) {
            $rec = new obj_doc([
                "sysobjid" => $sysobjid,
                "objid" => $objid,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = obj_doc::find($id);
            $mess = "Запись обновлена";
        }

        $rec->doctypeid = $request->get('doctypeid');
        $rec->docdate = $request->get('docdate');
        $rec->begdate = $request->get('begdate');
        $rec->enddate = $request->get('enddate');
        $rec->docnum = $request->get('docnum');
        $rec->docseria = $request->get('docseria');
        $rec->notes = $request->get('notes');
        $rec->active = $request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();
//        dd($rec);
        $rec->save();
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        connectify('success', 'Доп.затраты: ' . $rec->reason . ' '.$rec->expense_sum , $mess);

        $retURL = $request->get('returl');
        if (!isset($retURL)) {
            $sysobjcode = sysobj::find($rec->sysobjid)->code;
            $retURL = (isset($sysobjcode))
                ? route($sysobjcode . '.edit', $rec->objid)
                : route('home');
        }
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
        $retURL = $request->get('returl') ?? route('obj_docs.edit', $id);
        $userid = \Auth::user()->id;


        //Возьмем права от родительской системы:
        $sysobjid = $request->get('sysobjid');
        $sysobj = sysobj::find($sysobjid);

        if (usrsysright::isUserHasRightByCode_cached($userid, $sysobj->code . '.delete')) {

            $res = obj_doc::delete_by_id($id);

            $sd = array();
            if ($res->err == 1) {
                $sd["error"] = $res->msg;
            } else {

                $retURL = $request->get('returl') ?? route('home');
                $sd['success'] = 'Запись о дополнительных затратах удалена';
            }
        } else {
            $sd['success'] = 'У вас нет прав на удаление записей!';
        }
        return redirect($retURL)->with($sd);
    }


    static public function listbuildobj_docs(Request $request)
    {
        $result = "";
        try {
            $buildobjid = $request->buildobjid;
            $buildobj = buildobj::find($buildobjid);
            $buildobj_docs = obj_doc::where('buildobjid', $buildobjid)
                ->select('id', 'name')
                ->get()->pluck('name', 'id')->toArray();
            //rqListRefitem4Auto($orgid, $request)

            $result = array('address' => $buildobj->address, 'doctypes' => $buildobj_docs);

        } catch (\Exception $e) {
        }
        return response()->json($result);

    }
}
