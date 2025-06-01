<?php

namespace App\Http\Controllers;

use App\brand;
use App\estdocItem;
use App\estdocitm_resource;
use App\extsystem;
use App\group;
use App\objlog;
use App\org;
use App\refitem;
use App\machine;
use App\sysobj;
use App\User;
use App\usrsysright;
use App\objextid;

use Illuminate\Http\Request;

use App\Traits\DeleteFileTrait;
use Illuminate\Support\Facades\DB;

class ObjextidController extends Controller
{
    use DeleteFileTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 32;
        $this->sysobjcode = 'objextids';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.read');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.create');
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.delete');
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
    public function create($sysobjid = null, $objid = null)
    {
        return $this->edit(-1, $sysobjid, $objid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\objextid $objextid
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $sysobjid = null, $objid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new objextid();
            $rec->id = -1;
            $rec->sysobjid = $sysobjid;
            $rec->objid = $objid;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
//            $rec = objextid::where('sysobjid', $sysobjid)->findOrFail($id);
            $rec = objextid::findOrFail($id);
        }
        if (isset($rec)) {

//            $rec->extsystems = extsystem::from('extsystems as s')
//                ->join('extsys_sysobjs as eo', 'eo.extsysid', '=', 's.id')
//                ->where('eo.sysobjid', $sysobjid)
//                ->select('s.id', 's.name')
//                ->orderby('s.name')
//                ->get()->pluck('name', 'id')->toArray();
            $rec->extsystems = extsystem::listAll_cache($sysobjid);
//dd($sysobjid,$rec->extsystems);
            $rec->objname = null;
            if (isset($rec->objid)) {
                if ($rec->sysobjid == 3) {
                    //Users
                    $obj = User::select('name')->find($rec->objid);
                    $rec->objname = $obj->name ?? '-?-';
                    $rec->retRoute = route('usermanage.edit', $rec->objid);

                } elseif ($rec->sysobjid == 104) {
                    //Brand
                    $obj = brand::select('name')->find($rec->objid);
                    $rec->objname = $obj->name ?? '-?-';
                    $rec->retRoute = route('brands.edit', $rec->objid);

                } elseif ($rec->sysobjid == 105) {
                    //RefItems
                    $obj = refitem::select('name')->find($rec->objid);
                    $rec->objname = $obj->name ?? '-?-';
                    $rec->retRoute = route('refitems.edit', $rec->objid);

                } elseif ($rec->sysobjid == 111) {
                    //Orgs
                    $obj = org::select('name')->find($rec->objid);
                    $rec->objname = $obj->name ?? '-?-';
                    $rec->retRoute = route('orgs.edit', $rec->objid);

                } elseif ($rec->sysobjid == 482) {
                    //Machines
                    $obj = machine::select('name')->find($rec->objid);
                    $rec->objname = $obj->name ?? '-?-';
                    $rec->retRoute = route('machines.edit', $rec->objid);

                } elseif ($rec->sysobjid == 202) {
                    //Wrhs
                    $stock = resolve('App\Http\Middleware\IStock');
                    if (isset($stock) and $stock->active())
                        $rec->objname = $stock->wrh_name($rec->objid);
                    else
                        $rec->objname = '-?-';
                    $rec->retRoute = route('wrhs.edit', $rec->objid);
                } elseif ($rec->sysobjid == 822) {
                    //Groups
                    $obj = group::select('name')->find($rec->objid);
                    $rec->objname = $obj->name ?? '-?-';
                    $rec->retRoute = route('groups.edit', $rec->objid);
                }
            }

            $usrrights = $this->setInterfaceRight($id);

            return view($this->objcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\objextid $objextid
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        $messages = [
            'extsysid.required' => 'Укажите внешнюю систему',
            'extsysid.unique' => 'Связь с указанной Внешней системой уже задана',
            'extid.required' => 'Поле "Идентификатор" обязательно для заполнения',
            'objid.required' => 'Объект локальной системы должен быть задан',
            'sysobjid.required' => 'Тип объекта локальной системы должен быть задан',
        ];

        $request->validate([
            "sysobjid" => "required",
            "objid" => "required",
            "extid" => "required",

            'extsysid' => 'required',
            //The (undocumented) format for the unique rule is:
            //table[,column[,ignore value[,ignore column[,where column,where value]...]]]
            //Закоментировал требование единственности связи с внешней системой, так как снаружи встречаются разночтения
            // Например бренд "АВЕДОВЬ" может быть задан и как "АВЕДОВЬ" и как "АВЕДОВ"
//            'extsysid' => 'required|unique:objextids,extsysid,' . $id . ',,sysobjid,' . $request->input('sysobjid')
//                . ',objid,' . $request->input('objid'),
        ], $messages);
//        'column_to_validate' => 'unique:table_name,column_to_validate,id_to_ignore,other_column,value,other_column_2,value_2,other_column_N,value_N',

        $userid = \Auth::user()->id;

        $mess = "";
        if ($id == -1) {

            $rec = new objextid();
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о внешнем идентификаторе";

        } else {

            $rec = objextid::find($id);
            $mess = "Изменена запись о внешнем идентификаторе";
        }

        $rec->extsysid = $request->get('extsysid');
        $rec->extid = $request->get('extid');
        $rec->sysobjid = $request->get('sysobjid');
        $rec->objid = $request->get('objid');
        $rec->updated_by = $userid;
        $rec->save();

        $retURL = $request->get('retURL') ?? '/';

        //2021-11-24 SNS попробуем идентифицировать записи по указанному коду во внешней системе
        if ($rec->sysobjid == 105) {
            //Refitems - Номенклатура

            if ($rec->extsysid == 6) {
                //Гранд-смета
                // => попробуем идентифицировать ресурсы позиций смет
                //dd(estdocitm_resource::where('code', $rec->extid)->whereNull('refitmid')->count('*'));
                $rslt = estdocitm_resource::where('code', $rec->extid)
                    ->whereNull('refitmid')
                    ->update([
                        'refitmid' => $rec->objid,
                        'updated_at' => DB::raw("updated_at")
                    ]);
                if ($rslt > 0)
                    objlog::log_info($this->sysobjid, $rec->id, "По коду '{$rec->extid}' идентифицировано {$rslt} записей в спр-ке Номенклатуры", 5);

            }

        }

        return redirect($retURL)->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\objextid $objextid
     * @return \Illuminate\Http\Response
     */
    public
    function destroy(Request $request, $id)
    {
        $retURL = $request->get('retURL') ?? '/';
        $usrrights = $this->setInterfaceRight($id);
        if ($usrrights['delete']) {
            $res = objextid::delete_by_id($id, $this->sysobjid);

            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $route = route('objextids.edit', $id);
                $sd["error"] = $res->msg;
            } else {

                $route = $request->get('retURL') ?? '/';
                $sd['success'] = 'Запись удалена';
            }
        } else {
            $route = route('objextids.edit', $id);
            $sd["error"] = "Нет прав на удаление";
        }
        return redirect($route)->with($sd);
    }
}
