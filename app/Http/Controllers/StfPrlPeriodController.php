<?php

namespace App\Http\Controllers;

use App\objlog;
use App\org;
use App\payrolltype;
use App\stf_prl_period;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StfPrlPeriodController extends Controller
{
    use SearchDataTrait;
    use SearchDataTrait;
    use snsTrait;


    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1235;
        $this->parsysobjid = 121; //orgstaff
        $this->sysobjcode = 'stf_prl_periods';
        $this->model = 'App\stf_prl_period';
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


    public function index(Request $request)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight(-1);
        if (!$usrrights['read']) {
            return view('home');
        }


        session([$this->sysobjcode . '_pageno' => $request->page ?? 1]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 'stf_name' => ''
            , 'charge_dir' => ''
            , 'chargetype_name' => ''
            , 's_ym' => ''
        ];

        $search_params = $this->search_params($request, $param_names);
        //сформируем условие запроса в БД -----------------------
        $sc = stf_prl_period::search_cond($search_params);
        //dd($search_params, $sc);
        //-------------------------------------------------------

        $recs = stf_prl_period::from('stf_prl_periods as spp')
            ->join('orgstaff as os', 'os.id', 'spp.staffid')
            ->join('orgs as o', 'o.id', 'os.orgid')
            //->join('org_charges as oc', 'oc.id', 'spp.payrolltypeid')
            //->join('chargetypes as ct', 'ct.id', 'oc.chargetypeid')
            ->whereraw($sc)
            ->select('spp.id', 'spp.staffid', 'spp.begdate', 'spp.enddate'
                , db::raw("concat(os.lname, ' ', ifnull(os.fname,''), ' ', ifnull(os.mname,'')) as stf_name")
                , 'os.orgid', 'o.name as org_name'
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        $recs = $recs->orderBy('org_name', 'asc');
        $recs = $recs->orderBy('os.orgid', 'asc');
        $recs = $recs->orderBy('stf_name', 'asc');
        $recs = $recs->orderBy('spp.staffid', 'asc');
        $recs = $recs->orderBy('spp.begdate', 'desc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            //$recs = $recs->orderBy('ct.name', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);
        //--------------------------------------------------------------


        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid);

        //номер первой записи на странице:
        $data->rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data->search_params = $search_params;

        $data->ownorgs = org::lstFor([
            'in_org_charge' => 1,
            //'flagtypeid' => $search_params['s_orgflagid'] ?? '',
        ]);

        $data->dirs = [1 => 'начисление', -1 => 'удержание'];

        $data->yms = stf_prl_period::selectRaw("date_format(begdate, '%Y-%m') as ym")->distinct()->orderby('ym', 'desc')
            ->get()->pluck('ym', 'ym')->toArray();
        $month_names = Config::get('constants.monthes');
        foreach ($data->yms as $key => $val) {
            $y = substr($val, 0, 4);
            $m = 0 + substr($val, 5);
            $data->yms[$val] = $month_names[$m] . ' ' . $y;
        }
        //dd($data->yms);

        return view($this->sysobjcode . '.index', compact(['recs', 'data', 'usrrights']));
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
                    'begdate' => date_create()->format('Y-m-d'),
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

//        $rec->orgid = $rec->orgstaff->orgid;
        $rec->_obj_info = $rec->orgstaff->Info;

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
            'begdate.required' => 'Укажите начало периода начисления',
            'enddate.required' => 'Укажите окончание периода начисления',
        ];

        $rules = [
            "staffid" => "required",
            "begdate" => "required",
            "enddate" => "required",
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
        $rec->begdate = $request->get('begdate') ?? date_create()->format('Y-m-d');
        $rec->enddate = $request->get('enddate') ?? date_create()->format('Y-m-d');

        $rec->notes = $request->get('notes');
        $rec->active = $request->get('active') ?? 1;

        $rec->updated_by = $userid;
        $rec->updated_at = now();

        //------------------------------------------------------------
        $rules = [
            "ttt" => [
                function ($attribute, $value, $fail) use($rec) {
                    //проверим на существование пересекающихся периодов начисления для этого пользователя
                    $cnt = stf_prl_period::where('staffid', $rec->staffid)
                        ->where('id', '<>', $rec->id)
                        ->whereRaw("begdate <='" . $rec->enddate . "' and enddate >='" . $rec->begdate . "'")
                        ->count();
                    //dd($cnt);
                    if ($cnt > 0) {
                        $fail("Указан период, пересекающийся с другим периодом!");
                    }
                },
            ],
        ];

        $request->validate($rules, $messages);
        //------------------------------------------------------------

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        // Перерасчет даты окончания периода применения способа расчета ------
//        $recs = stf_prl_period::where('staffid', $rec->staffid)
//            ->select('id', 'begdate', 'enddate')
//            ->orderby('begdate', 'desc')
//            ->get();
//        $enddate = null;
//        foreach ($recs as $r) {
//            $r->enddate = $enddate;
//            $r->save();
//            $enddate = date_create($r->begdate);
//            $enddate = $enddate->modify('-1 day')->format('Y-m-d');
//        }
        //---------------------------------------------------------------------

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
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о периоде начислении ЗП', 5);
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
