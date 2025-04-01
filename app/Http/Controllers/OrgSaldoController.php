<?php

namespace App\Http\Controllers;

use App\org;
use App\org_saldo;
use App\Traits\DeleteFileTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class OrgSaldoController extends Controller
{
    use DeleteFileTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 114;
        $this->objcode = 'org_saldos';
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create');
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
        }

        return $usrrights;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $orgid, $ownorgid = null)
    {
        return $this->edit($request, -1, $orgid, $ownorgid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\objextid $objextid
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $orgid = null, $ownorgid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new org_saldo();
            $rec->id = -1;
            $rec->orgid = $orgid;
            $rec->ownorgid = $ownorgid ?? \Auth::user()->curorgid;
            $rec->ondate = today()->format('Y-m-d');
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = org_saldo::findOrFail($id);
        }
        if (isset($rec)) {

            $rec->retURL = $request->get('returl');

            $rec->ownorgs = org::lstFor_cached(['flagtypeid' => 12]);

            //ограничитель для даты - не в будущем
            $rec->maxdate = today()->format('Y-m-d');
            $rec->maxdate = strftime('%Y-%m-%d', strtotime($rec->maxdate));

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
            'ownorgid.required' => 'Укажите контрагента (Владельца)',
            //'ondate.unique' => 'Сальдо на эту дату уже задано',
            'orgid.required' => 'Укажите контрагента (Клиента)',
            'ondate.required' => 'Укажите дату сальдо',
            'saldo.required' => 'Укажите сумму сальдо',
        ];

        $request->validate([
            "ownorgid" => "required",
            "orgid" => "required",
            "ondate" => "required",
            "saldo" => "required",

            //The (undocumented) format for the unique rule is:
            //table[,column[,ignore value[,ignore column[,where column,where value]...]]]
            //Закоментировал требование единственности связи с внешней системой, так как снаружи встречаются разночтения
            // Например бренд "АВЕДОВЬ" может быть задан и как "АВЕДОВЬ" и как "АВЕДОВ"
//            'extsysid' => 'required|unique:objextids,extsysid,' . $id . ',,sysobjid,' . $request->input('sysobjid')
//                . ',objid,' . $request->input('objid'),
        ], $messages);
//        'column_to_validate' => 'unique:table_name,column_to_validate,id_to_ignore,other_column,value,other_column_2,value_2,other_column_N,value_N',

        $prm = new \stdClass();
        $prm->id = $id;
        $prm->ownorgid = $request->get('ownorgid');
        $prm->orgid = $request->get('orgid');

        $rules = [
            //В форме должно быть поле ttt
            "ttt" => [
                function ($attribute, $value, $fail) use ($prm) {

                    //проверка на уникальность сочетания ownorgid/orgid. Игнорируем текущую запись
                    $cnt = org_saldo::where(['ownorgid' => $prm->ownorgid, 'orgid' => $prm->orgid])
                        ->where('id', '<>', $prm->id)->count();

                    if ($cnt > 0) {
                        $fail("Сальдо для данных организаций уже внесено!");
                    }
                },
            ],
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $mess = "";
        if ($id == -1) {

            $rec = new org_saldo();
            $rec->orgid = $request->get('orgid');;
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о сальдо операций с контрагентом";

        } else {

            $rec = org_saldo::find($id);
            $mess = "Изменена запись о сальдо операций с контрагентом";
        }

        //2025-04-01
        $rec->orgid = $request->get('orgid');;

        $rec->ownorgid = $request->get('ownorgid');
        $rec->ondate = $request->get('ondate');
        $rec->saldo = $request->get('saldo');
        $rec->active = 1; //$request->get('active') ?? 0;
        $rec->aligmentdate = $request->get('aligmentdate');
        $rec->updated_by = $userid;
        $rec->save();

        //Выполним действия после обновления записи ---------------------------------------------
        org_saldo::on_update($rec);
        //---------------------------------------------------------------------------------------

        $retURL = $request->get('retURL') ?? '/';
        //dd($rec, $retURL);

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
        //dd($usrrights);

        if ($usrrights['delete']) {
            $res = org_saldo::delete_by_id($id, $this->sysobjid);

            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $route = route('org_saldos.edit', $id);
                $sd["error"] = $res->msg;
            } else {

                $route = $request->get('retURL') ?? '/';
                $sd['success'] = 'Запись удалена';

                //Выполним действия после удаления записи -----------------------------------------------
                //Cache::forget('lstSaldos_' . $res->obj['orgid']);

                org_saldo::on_delete($res->rec);
                //---------------------------------------------------------------------------------------

            }
        } else {
            $route = route('org_saldos.edit', $id);
            $sd["error"] = "Нет прав на удаление";
        }
        return redirect($route)->with($sd);
    }

    static public function saldo_for_orgs(Request $request)
    {
        //2023-09-24 SNS. Данные разные

        $result = "";
        try {

            $ownorgid = $request->get("ownorgid");
            $orgid = $request->get("orgid");
            $ondate = $request->get("ondate");
//dd($ownorgid,$orgid,$ondate );
            $sess_var_lbl = "saldo_{$ownorgid}_{$orgid}_{$ondate}_params";
            if (1 == 1 and null !== session([$sess_var_lbl]))
                return session([$sess_var_lbl]);
            else {
                $sql = "select sum( if(srcorgid = {$ownorgid}, - 1, + 1) * opersum) as saldo
                        from `obj_finopers` as `fo`
                        where {$ownorgid} in (fo.srcorgid, fo.tgtorgid)
                          and {$orgid} in (fo.srcorgid, fo.tgtorgid)
                          and fo.operdate<'{$ondate}'";
                $data = "";
                try {
                    $data = DB::select(DB::raw($sql));
                    $data = array('saldo' => $data[0]->saldo ?? '');
                    //$data = array('data' => $data);

                    //dd($data);
                    session([$sess_var_lbl => response()->json($data)]);

                } catch (\Exception $e) {
                }
            }
            return response()->json($data);

            //$result = array('data' => $list);
            //Log::info(implode('; ', $list));

        } catch (\Exception $e) {
            Log::error('fuelcard::data_for_card:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
