<?php

namespace App\Http\Controllers;

use App\extsys_sysobj;
use App\extsystem;
use App\sysobj;
use Illuminate\Http\Request;
use App\usrsysright;
use Cache;

class ExtsysSysobjController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 33;
        $this->objcode = 'extsys_sysobjs';
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.read');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.create');
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.delete');
        }

        return $usrrights;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($extsysid = null)
    {
        return $this->edit(-1, $extsysid);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $extsysid = null)
    {
        //
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new extsys_sysobj();
            $rec->id = -1;
            $rec->extsysid = $extsysid;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = extsys_sysobj::findOrFail($id);
        }
        if (isset($rec)) {

            $rec->sysobjs = sysobj::lst_sysobjs4extsys_cache();

            $usrrights = $this->setInterfaceRight($id);

            return view($this->objcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\extsys_sysobj $extsys_sysobj
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, $extsysid = null)
    {

        $messages = [
            'extsysid.required' => 'Не указана внешняя система',
            'sysobjid.required' => 'Укажите справочник',
            'sysobjid.unique' => 'Такой справочник уже связан с этой внешней системой',
        ];

        $request->validate([
            "extsysid" => "required",
            'sysobjid' => 'required|unique:extsys_sysobjs,sysobjid,' . $id . ',,sysobjid,' . $request->input('sysobjid')
                . ',extsysid,' . $request->input('extsysid'),
//        'column_to_validate' => 'unique:table_name,column_to_validate,id_to_ignore,other_column,value,other_column_2,value_2,other_column_N,value_N',
        ], $messages);

        $userid = \Auth::user()->id;

        $mess = "";
        if ($id == -1) {

            $rec = new extsys_sysobj();
            $rec->extsysid = $extsysid;
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о группе";

        } else {

            $rec = extsys_sysobj::findOrFail($id);
            $mess = "Изменена запись о группе";
        }

        $rec->sysobjid = $request->get('sysobjid');
        $rec->updated_by = $userid;
        $rec->save();

        //cache forget
        Cache::forget('extsystems.list.' . ($rec->sysobjid ?? '_'));

        if ($id == -1) {
            //останемся в созданной записи
            return redirect(route($this->objcode . '.edit', $rec->id))->with('success', $mess);

        } else {
            return redirect(route('extsystems.edit', $rec->extsysid))->with('success', $mess);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = extsys_sysobj::delete_by_id($id,$this->sysobjid);
        $sd = array();
        if ($res->err == 1) {
            $route = route($this->objcode . '.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $route = route( 'extsystems.edit',$res->obj['extsysid']);
            $sd['success'] = 'Запись удалена';
        }
        return redirect($route)->with($sd);
    }

    public function admindelete($id)
    {
        $rec = extsys_sysobj::find($id);
        if ($rec) {

            $parent_id = $rec->parent_id;

            $res = $rec->admindelete();

            $sd = array();
            if ($res->err == 1) {
                $route = route($this->objcode . '.edit', $id);
                $sd["error"] = $res->msg;
                objlog::log_info($this->sysobjid, $id, $res->msg, 2);

            } else {
                $route = route( 'extsystems.edit',$res->obj['extsysid']);
                $sd['success'] = 'Запись удалена администратором';
                objlog::log_info($this->sysobjid, 0, "Административное удаление записи id=" . $id, 2);
            }
        } else {
            $sd['warning'] = 'Запись не найдена!';
            $route = route($this->objcode . '.index');
        }
        return redirect($route)->with($sd);
    }
}
