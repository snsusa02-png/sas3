<?php

namespace App\Http\Controllers;

use App\contract;
use App\contract_org;
use App\contractrole;
use App\roletype;
use App\objlog;
use App\sysobj;
use App\usrsysright;
use Illuminate\Http\Request;

class ContractOrgController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 152;
        $this->sysobjcode = 'contract_orgs';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }


    protected function setInterfaceRight($id, $acl_sysobjcode = null)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        //Как-правило - наследуем права от системы из которой произведено обращение
        $acl_sysobjcode = $acl_sysobjcode ?? $this->acl_sysobjcode;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['edtrights'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.delete');
            $usrrights['edtrights'] = usrsysright::isUserHasRightByCode($userid, 'admin-global');
        }

        return $usrrights;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $contractid)
    {
        return $this->edit($request, -1, $contractid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $contractid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи
            //$ordr = contract_org::where(['contractid' => $contractid])->max('ordr') ?? 0;
            //$ordr += 1;

            $rec = new contract_org([
                'id' => -1,
                'contractid' => $contractid,
                //'ordr' => $ordr,
                'active' => 1,
                'created_by' => \Auth::user()->id,
            ]);
        } else
            $rec = contract_org::find($id);


        if (!isset($rec))
            return redirect(route('home'));

        $rec->retURL = $request->get('returl');


        //Возьмем права от родительской системы:
        $usrrights = $this->setInterfaceRight($rec->id);

        $obj = contract::select('docnum')->find($rec->objid);
        $rec->objname = 'Договор №' . ($obj->docnum ?? '-?-');
        $rec->retRoute = route('contracts.edit', $rec->contractid);

        //Типы ролей пользователей для данной системы
//        $rec->roletypes = roletype::lstfor([
//            'sysobjid' => $this->sysobjid
//        ]);
        $rec->roletypes = contractrole::list_for_contracttypeid($rec->contract->contracttypeid);

        //dd( $rec->roletypes);
        //dd($sysobj->code, $usrrights);

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

        $messages = [
            //'sysobjid.required' => 'Тип объекта инф. системы должен быть задан',
            'contractid.required' => 'Не задан договор',
            'rolename.required' => 'Укажите роль участника',
        ];

        $rules = [
            //"sysobjid" => "required",
            "contractid" => "required",
//            "rolename" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $contractid = $request->contractid;

        $orgids = $request->orgid;
        $roletypeids = $request->roletypeid;
        //dd($sysobjid, $objid, $orgids,$roletypeids);

        //Упорядочим в порядке добавления
        //$ordr = contract_org::where(['contractid' => $contractid])->max('ordr') ?? 0;

        foreach ($orgids as $key => $orgid) {

            $orgid = $orgids[$key];
            $roletypeid = $roletypeids[$key];
            //dd($orgid, $roletypeid);

            if (isset($orgid)) {


                $mess = "Запись обновлена";
                if ($id == -1) {
                    //попробуем поискать - возможно контрагент уже в списке?
                    $rec = contract_org::where([
                        'contractid' => $contractid,
                        'orgid' => $orgid,
                    ])->first();

                    if (!isset($rec)) {
                        //$ordr += 10;
                        $rec = new contract_org([
                            "contractid" => $contractid,
//                            "ordr" => $ordr,
                            "created_by" => $userid,
                            "created_at" => now(),
                            "updated_by" => $userid,
                            "updated_at" => now()]);
                        $mess = "Запись создана";
                    }
                } else {
                    $rec = contract_org::find($id);
                }
                $rec->orgid = $orgid;
                //$rec->roletypeid = $roletypeid ?? 1;
                $rec->roleid = $roletypeid ?? 1;
                $rec->updated_by = $userid;
                $rec->updated_at = now();

                $rec->save();
                objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

            }
        }


        $retURL = $request->get('returl');
        if (!isset($retURL)) {
            $retURL = (isset($rec->contractid))
                ? route('contracts.edit', $rec->contractid)
                : route('contracts.index');
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
        $retURL = $request->get('returl') ?? route('contract_orgs.edit', $id);
        $userid = \Auth::user()->id;

        //Возьмем права от родительской системы:
        $rec = contract_org::find($id);
        if (isset($rec)) {

            if (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete')) {

                $retURL = (isset($rec->contractid))
                    ? route('contracts.edit', $rec->contractid)
                    : route('contracts.index');

                $res = contract_org::delete_by_id($id);

                $sd = array();
                if ($res->err == 1) {
                    $sd["error"] = $res->msg;
                } else {

                    //$retURL = $request->get('returl') ?? route('home');
                    $sd['success'] = 'Запись удалена';
                }
            } else {
                $sd['success'] = 'У вас нет прав на удаление записей!';
            }
        } else {
            $sd['success'] = 'Не найдена родительская запись!';
        }

        return redirect($retURL)->with($sd);
    }


}
