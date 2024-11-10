<?php

namespace App\Http\Controllers;

use App\objlog;
use App\srs_hr_item;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use App\wrktype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class SrsHrItemController extends Controller
{
    use SearchDataTrait;
    use SearchDataTrait;
    use snsTrait;


    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1223;
        $this->parsysobjid = 1222; //salary_rate_sets
        $this->sysobjcode = 'srs_hr_items';
        $this->model = 'App\srs_hr_item';
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
    function create(Request $request, $payrolltypeid = null)
    {
        $usrrights = $this->setInterfaceRight(-1);
        if (!$usrrights['create'])
            return redirect()->back()->with('error', 'У вас нет права на создание записей!');

        return $this->edit($request, -1, $payrolltypeid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\user_ac $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(user_ac $rec)
    public function edit(Request $request, $id, $srs_id = null)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $rec = new $this->model([
                    'id' => -1,
                    'srs_id' => $srs_id,
                    'wrktypeid' => $request->get('wrktypeid'),
                    'created_by' => $userid,
                ]);
            } else {
                $rslt = ['error' => 'У вас нет права на это действие!'];
                if (isset($srs_id))
                    return redirect(route('salary_rate_sets.edit', $srs_id))->with($rslt);
                else
                    return redirect(route('payrolltypes.index'))->with($rslt);
            }
        } else {
            $rec = $this->model::find($id);
        }

        $rec->_obj_info = $rec->salary_rate_set->Info;

        $rec->wrktypes = wrktype::lstFor(['active_or_current' => $rec->wrktypeid]);

        if (!isset($rec))
            return redirect(route('payrolltypes.edit', $srs_id));

        $rec->retURL = $request->get('returl') ?? route('salary_rate_sets.edit', $rec->srs_id);

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
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');
        if ($id == -1 and !$usrrights['create'])
            return redirect()->back()->with('error', 'У вас нет права на создание записей!');
        if ($id <> -1 and !$usrrights['save'])
            return redirect()->back()->with('error', 'У вас нет права на изменение записей!');
        //
        $messages = [
            'srs_id.required' => 'Группа ставок должна быть определена',
            'wrktypeid.required' => 'Укажите вид работ',
            'min_wrkexp.required' => 'Укажите минимальный рабочий стаж',
        ];

        $rules = [
            "srs_id" => "required",
            "wrktypeid" => "required",
            "min_wrkexp" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";

        if ($id == -1) {

            $rec = new $this->model([
                "srs_id" => $request->get('srs_id'),
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

        $rec->wrktypeid = $request->get('wrktypeid');
        $rec->min_wrkexp = $request->get('min_wrkexp');
        $rec->hr_day_rate = $request->get('hr_day_rate');
        $rec->hr_night_rate = $request->get('hr_night_rate');
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        // Перерасчет максимального значения стажа ----------------------------
        srs_hr_item::recalc_max_wrkexp($rec->srs_id, $rec->wrktypeid);
        //---------------------------------------------------------------------

        //Cache::forget("user_{$usrid}_has_acs_{$rec->acsid}");

        $retURL = $request->get('retURL') ?? route('salary_rate_sets.edit', $rec->srs_id) . '?#srs_hr_items';
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
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['delete'])
            return redirect()->back()->with('error', 'У вас нет права на удаление записей!');

        $res = $this->model::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('srs_hr_items.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['srs_id'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о по-часовой ставке ЗП', 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //Выполним действия после удаления записи -----------------------------------------------
            $this->model::on_delete($res->rec);
            //---------------------------------------------------------------------------------------

            //забудем кэшированные данные про ...:
            //Cache::forget("user_{$usrid}_has_acs_{$acsid}");

            $route = route('salary_rate_sets.edit', $parobjid);
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
