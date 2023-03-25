<?php

namespace App\Http\Controllers;

use App\objlog;
use App\org_charge;
use App\stf_chrg_calc;
use App\sysobj;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class StfChrgCalcController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1213;
        $this->parsysobjid = 121; //orgstaff
        $this->sysobjcode = 'stf_chrg_calcs';
        $this->model = 'App\stf_chrg_calc';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    protected function setInterfaceRight($recid)
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

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');

        if ($recid > 0) {
            //для существующих записей проверим открытость периода
            //if ($this->model::isLocked($recid)) {

//                $usrrights['save'] = false;
//                $usrrights['delete'] = false;
//                $usrrights['admindelete'] = false;
            //}
        } else {
            $usrrights['delete'] = false;
            $usrrights['admindelete'] = false;
        }

        $usrrights['edit'] = $usrrights['save'];

        return $usrrights;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request, $staffid = null)
    {
        return $this->edit($request, -1, $staffid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\user_ac $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(user_ac $rec)
    public function edit(Request $request, $id, $staffid = null)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $rec = new $this->model([
                    'id' => -1,
                    'staffid' => $staffid,
                    'docdate' => date_create()->format('Y-m-d'),
                    'forenddate' => date_create()->format('Y-m-d'),
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else {
                $rslt = ['error' => 'У вас нет права на это действие!'];
                if (isset($staffid))
                    return redirect(route('orgstaff.edit', $staffid))->with($rslt);
                else
                    return redirect(route('orgstaff.index'))->with($rslt);
            }
        } else {
            $rec = $this->model::find($id);
        }

        $rec->orgid = $rec->orgstaff->orgid;
        $rec->_obj_info = $rec->orgstaff->Info;

        //$rec->orgcharges = org_charge::lstFor_cached([
        $rec->orgcharges = org_charge::lstFor([
            'orgid' => $rec->orgid,
//            'period_not_once' => 1,
            'active_or_current' => $rec->orgchargeid??-1,
        ]);

        if (!isset($rec))
            return redirect(route('orgstaff.edit', $staffid));

        $rec->retURL = $request->get('returl') ?? route('orgstaff.edit', $rec->staffid);

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\user_ac $rec
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $messages = [
            'staffid.required' => 'Укажите сотрудника',
            'orgchargeid.required' => 'Укажите вид начисления/удержания',
            'forbegdate.required' => 'Укажите начало периода работы',
            'forenddate.required' => 'Укажите окончание периода работы',
            'charge_sum.required' => 'Укажите сумму',
        ];

        $rules = [
            "staffid" => "required",
            "orgchargeid" => "required",
            "charge_sum" => "required",
//            "forbegdate" => "required",
//            "forenddate" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        $staffid = $request->get('staffid');
        if ($id == -1) {

            $rec = new $this->model([
                "staffid" => $staffid,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = $this->model::find($id);
            $mess = "Запись обновлена";
        }

        $rec->staffid = $staffid;
        $rec->orgchargeid = $request->get('orgchargeid');
        $rec->charge_dir = $rec->org_charge->chargetype->dir;
        $rec->charge_sum = $request->get('charge_sum');
        $rec->docdate = $request->get('docdate')??date_create()->format('Y-m-d');
        $rec->docnum = $request->get('docnum');

        //$rec->forbegdate = $request->get('forbegdate');
        //$rec->forenddate = $request->get('forenddate');
        //ЦУУпрощенный вариант, вычислим  от даты начисления/удержания
        $rec->forbegdate = ''.date_create($rec->docdate)->format('Y-m-01');
        $rec->forenddate = ''.date_create($rec->docdate)->format('Y-m-t');
        //$rec->forenddate = $request->get('forenddate');

        $rec->active = $request->get('active') ?? 1;

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        //Cache::forget("user_{$usrid}_has_acs_{$rec->acsid}");

        $retURL = $request->get('retURL') ?? route('orgstaff.edit', $rec->staffid) . '?#chrg_calcs';
        return redirect($retURL)->with('success', $mess);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\user_ac $rec
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = $this->model::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('orgstaff.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['staffid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о начислении ЗП', 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //забудем кэшированные данные про ...:
            //Cache::forget("user_{$usrid}_has_acs_{$acsid}");

            $route = route('orgstaff.edit', $parobjid);
            $sd['success'] = 'Запись удалена';
        }
        return redirect($route)->with($sd);
    }

    static public function list_for(Request $request)
    {
        //2021-06-09 SNS. Обертка для вызова user_ac::lstFor

        $result = "";
        try {

            $list = $this->model::lstFor([
                'orgid' => $request->orgid,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
                'with_posts' => $request->with_posts,
                'with_post_vacancies' => $request->with_post_vacancies,
                'with_post_vacancies_staff' => $request->with_post_vacancies_staff,
            ]);


            $result = array('user_acs' => $list);

        } catch (\Exception $e) {
            Log::error('user_acs::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
