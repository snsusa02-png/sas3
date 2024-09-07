<?php

namespace App\Http\Controllers;

use App\objlog;
use App\org;
use App\orgstaff;
use App\salary_rate_set;
use App\srs_hr_item;
use App\stf_payrolltype;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class SalaryRateSetController extends Controller
{
    use SearchDataTrait;
    use SearchDataTrait;
    use snsTrait;


    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1222;
        $this->parsysobjid = 1221; //payrolltypeid
        $this->sysobjcode = 'salary_rate_sets';
        $this->model = 'App\salary_rate_set';
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
    public function edit(Request $request, $id, $payrolltypeid = null)
    {
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $rec = new $this->model([
                    'id' => -1,
                    'payrolltypeid' => $payrolltypeid,
                    'begdate' => date_create()->format('Y-m-d'),
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else {
                $rslt = ['error' => 'У вас нет права на это действие!'];
                if (isset($payrolltypeid))
                    return redirect(route('payrolltypes.edit', $payrolltypeid))->with($rslt);
                else
                    return redirect(route('payrolltypes.index'))->with($rslt);
            }
        } else {
            $rec = $this->model::find($id);
        }
        if (!isset($rec))
            return redirect(route('payrolltypes.edit', $payrolltypeid));

        $rec->_obj_info = $rec->payrolltype->Info;

        $rec->ownorgs = org::lstFor_cached([
            'flagtypeid' => 12,
            'in_userorgs' => $userid
        ]);

        $rec->srs_hr_items = srs_hr_item::from('srs_hr_items as i')
            ->join('wrktypes as wt', 'wt.id', 'i.wrktypeid')
            ->where('i.srs_id', $rec->id)
            ->select('i.*', 'wt.name as wrktype_name', 'wt.active as wrktype_active')
            ->orderBy('wt.active', 'desc')
            ->orderBy('wt.ordr')
            ->orderBy('wrktype_name')
            ->orderBy('i.wrktypeid')
            ->orderBy('i.min_wrkexp')
            ->get();
        //dd($rec->id, $rec->srs_hr_items);

        $rec->retURL = $request->get('returl') ?? route('payrolltypes.edit', $rec->payrolltypeid);

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
            'payrolltypeid.required' => 'Укажите сотрудника',
            'payrolltypeid.required' => 'Укажите способ расчета заработной платы',
            'begdate.required' => 'Укажите начало периода применения',
        ];

        $rules = [
            "payrolltypeid" => "required",
            "payrolltypeid" => "required",
            "begdate" => "required",
//            "forenddate" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {

            //$payrolltypeid = $request->get('payrolltypeid');
            $rec = new $this->model([
                "payrolltypeid" => $request->get('payrolltypeid'),
                //"ownorgid" => $request->get('ownorgid'),
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

        $rec->name = $request->get('name') ?? ' ';
        $rec->ownorgid = $request->get('ownorgid');
        $rec->begdate = $request->get('begdate') ?? date_create()->format('Y-m-d');
        $rec->active = ($request->get('active') == 1) ? 1 : 0;
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        // Перерасчет даты окончания периода действия группы ставок ------
        $recs = salary_rate_set::where('payrolltypeid', $rec->payrolltypeid)
            ->where('active', 1)
            ->select('id', 'begdate', 'enddate')
            ->orderby('begdate', 'desc')
            ->get();
        $enddate = null;
        foreach ($recs as $r) {
            $r->enddate = $enddate;
            $r->save();
            $enddate = date_create($r->begdate);
            $enddate = $enddate->modify('-1 day')->format('Y-m-d');
        }
        //---------------------------------------------------------------------

        //Cache::forget("user_{$usrid}_has_acs_{$rec->acsid}");

        $retURL = $request->get('retURL') ?? route('payrolltypes.edit', $rec->payrolltypeid) . '?#salary_rate_sets';
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
            $route = route('orgstaff.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['payrolltypeid'];
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
