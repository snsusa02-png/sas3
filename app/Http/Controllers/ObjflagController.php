<?php

namespace App\Http\Controllers;

use App\estdocitm_resource;
use App\extsystem;
use App\flagtype;
use App\group;
use App\machine;
use App\objflag;
use App\objlog;
use App\org;
use App\orgstaff;
use App\refitem;
use App\sysobj;
use App\Traits\DeleteFileTrait;
use App\Traits\snsTrait;
use App\User;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ObjflagController extends Controller
{
    //use DeleteFileTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 10;
        $this->sysobjcode = 'objflags';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    protected function setInterfaceRight($sysobjid, $id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $this->acl_sysobjcode = sysobj::acl_sysobjcode_by_id($sysobjid);
        //dd($sysobjid, $this->acl_sysobjcode);

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
//            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.update');
            $usrrights['save'] = false; // Договоримся, что флаги для объектов/субъектов можгл только создавать и удалять
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.delete');
        }
        //dd($this->acl_sysobjcode, $usrrights);

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
     * @param \App\objflag $objextid
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $sysobjid = null, $objid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new objflag();
            $rec->id = -1;
            $rec->sysobjid = $sysobjid;
            $rec->objid = $objid;
            $rec->created_by = $userid;
            $rec->created_at = now();

            // только для новых записей
            $rec->flagtypes = flagtype::lstFor(['new_or_curr_for_'.$rec->sysobjid => $rec->objid]);

        } else {
//            $rec = objflag::where('sysobjid', $sysobjid)->findOrFail($id);
            $rec = objflag::findOrFail($id);
        }

        if (isset($rec)) {

            //$rec->flagtypes = flagtype::lstFor(['new_or_curr_for_'.$rec->sysobjid => $rec->objid]);
            //dd($rec, $rec->flagtype->name);

            $rec->objname = null;
            if (isset($rec->objid)) {
                if ($rec->sysobjid == 3) {
                    //Users
                    $obj = User::select('name')->find($rec->objid);
                    $rec->objname = $obj->name ?? '-?-';
                    $rec->retRoute = route('usermanage.edit', $rec->objid);

                } elseif ($rec->sysobjid == 121) {
                    //orgstaff
                    $obj = orgstaff::select(db::raw("trim(concat(lname,' ',ifnull(fname,''),' ',ifnull(mname,''))) as name"))
                        ->find($rec->objid);
                    //dd($obj);
                    $rec->objname = $obj->name ?? '-?-';
                    $rec->retRoute = route('orgstaff.edit', $rec->objid);

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
            //dd($this->objcode, $rec);
            $usrrights = $this->setInterfaceRight($rec->sysobjid, $id);

            return view($this->objcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\objflag $objextid
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        $messages = [
            //'extsysid.required' => 'Укажите внешнюю систему',
            //'extsysid.unique' => 'Связь с указанной Внешней системой уже задана',
            'flagtypeid.required' => 'Поле "Характеристика" обязательно для заполнения',
            'objid.required' => 'Объект локальной системы должен быть задан',
            'sysobjid.required' => 'Тип объекта локальной системы должен быть задан',
        ];

        $request->validate([
            "sysobjid" => "required",
            "objid" => "required",
            "flagtypeid" => "required",

            //'extsysid' => 'required',
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

            $rec = new objflag();
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о доп. характеристике";

        } else {

            $rec = objflag::find($id);
            $mess = "Изменена запись о доп. характеристике";
        }

        $rec->flagtypeid = $request->get('flagtypeid');
        $rec->sysobjid = $request->get('sysobjid');
        $rec->objid = $request->get('objid');
        //$rec->updated_by = $userid;
        $rec->save();

        $retURL = $request->get('retURL') ?? '/';

//        //2021-11-24 SNS попробуем идентифицировать записи по указанному коду во внешней системе
//        if ($rec->sysobjid == 105) {
//            //Refitems - Номенклатура
//
//            if ($rec->extsysid == 6) {
//                //Гранд-смета
//                // => попробуем идентифицировать ресурсы позиций смет
//                //dd(estdocitm_resource::where('code', $rec->extid)->whereNull('refitmid')->count('*'));
//                $rslt = estdocitm_resource::where('code', $rec->extid)
//                    ->whereNull('refitmid')
//                    ->update([
//                        'refitmid' => $rec->objid,
//                        'updated_at' => DB::raw("updated_at")
//                    ]);
//                if ($rslt > 0)
//                    objlog::log_info($this->sysobjid, $rec->id, "По коду '{$rec->extid}' идентифицировано {$rslt} записей в спр-ке Номенклатуры", 5);
//            }
//        }

        return redirect($retURL)->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\objflag $objextid
     * @return \Illuminate\Http\Response
     */
    public
    function destroy(Request $request, $id)
    {
        $retURL = $request->get('retURL') ?? '/';
        $sysobjid = $request->get('sysobjid');
        $usrrights = $this->setInterfaceRight($sysobjid, $id);
        //dd($sysobjid, $id, $usrrights);

        if ($usrrights['delete']) {
            $res = objflag::delete_by_id($id, $this->sysobjid);

            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $route = route('objflags.edit', $id);
                $sd["error"] = $res->msg;
            } else {

                $route = $request->get('retURL') ?? '/';
                $sd['success'] = 'Запись удалена';
            }
        } else {
            $route = route('objflags.edit', $id);
            $sd["error"] = "Нет прав на удаление";
        }
        return redirect($route)->with($sd);
    }
}
