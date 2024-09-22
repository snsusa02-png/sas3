<?php

namespace App\Http\Controllers;

use App\mchn_spare_usage;
use App\objlog;
use App\opertype;
use App\org;
use App\org_charge;
use App\orgstaff;
use App\stf_chrg_calc;
use App\stf_wrkhr;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class StfWrkhrController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1227;
        $this->parsysobjid = 121; //orgstaff
        $this->sysobjcode = 'stf_wrkhrs';
        $this->model = 'App\stf_wrkhr';
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
            , 's_orgid' => ''
            , 'stf_name' => ''
            , 's_ym' => ''
        ];

        $search_params = $this->search_params($request, $param_names);
        //сформируем условие запроса в БД -----------------------
        $sc = stf_wrkhr::search_cond($search_params);
        //dd($search_params, $sc);
        //-------------------------------------------------------

        $recs = stf_wrkhr::from('stf_wrkhrs as swh')
            ->join('orgstaff as os', 'os.id', 'swh.staffid')
            ->join('orgs as o', 'o.id', 'os.orgid')
            ->whereraw($sc)
            ->select('swh.id', 'swh.staffid', 'swh.yr', 'swh.mn'
                , db::raw("concat(os.lname, ' ', ifnull(os.fname,''), ' ', ifnull(os.mname,'')) as stf_name")
                , 'swh.day_hrs'
                , 'swh.day_tot_hrs'
                , 'swh.day_hr_cost'
                , 'swh.tot_sum'
                , 'os.orgid', 'o.name as org_name'
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        $recs = $recs->orderBy('org_name', 'asc');
        $recs = $recs->orderBy('os.orgid', 'asc');
        $recs = $recs->orderBy('swh.yr', 'desc');
        $recs = $recs->orderBy('swh.mn', 'desc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('stf_name', 'asc');
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
            'in_stf_wrkhrs' => 1,
            //'flagtypeid' => $search_params['s_orgflagid'] ?? '',
            'flagtypeid' => '12',
        ]);

        $data->dirs = [1 => 'начисление', -1 => 'удержание'];

        //$data->yms = stf_wrkhr::selectRaw("date_format(forbegdate, '%Y-%m') as ym")->distinct()->orderby('ym', 'desc')
        //$data->yms = stf_wrkhr::selectRaw("concat(yr, '-', mn) as ym")->distinct()->orderby('ym', 'desc')
        $data->yms = stf_wrkhr::selectRaw("concat(yr, '-', mn) as ym")->distinct()->orderby('ym', 'desc')
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
    public function edit(Request $request, $id, $staffid = null)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');

        $usrrights['edit_date'] = false;

        $retURL = $request->get('returl');

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $rec = new $this->model([
                    'id' => -1,
                    'staffid' => $staffid,
                    'yr' => date_create()->format('Y'),
                    'mn' => date_create()->format('m'),
                    'active' => 1,
                    'created_by' => $userid,
                ]);
                $usrrights['edit_date'] = true;
            } else {
                $rslt = ['error' => 'У вас нет права на это действие!'];
                if (isset($staffid))
//                    return redirect(route('orgstaff.edit', $staffid))->with($rslt);
                    return redirect(route('stf_wrkhrs.index'))->with($rslt);
                else
                    return redirect(route('stf_wrkhrs.index'))->with($rslt);
            }
        } else {
            $rec = $this->model::find($id);
            if (!isset($rec)) {
                $rslt = ['error' => 'Указанная запись не найдена!'];
                return redirect(route('stf_wrkhrs.index'))->with($rslt);
            }
        }
        //$usrrights['edit_date'] = false;

        $rec->orgid = $rec->orgstaff->orgid;
        $rec->_obj_info = $rec->orgstaff->Info;

        $begdate = date('Y-m-d', strtotime($rec->yr . '-' . $rec->mn . '-01'));
        $enddate = date('Y-m-d', strtotime($begdate . ' +1 Months -1 days'));
        $rec->begdate = $begdate;
        $rec->enddate = $enddate;
        $rec->endday = date('d', strtotime($enddate)) + 0;

        if (!isset($rec))
            return redirect(route('orgstaff.edit', $staffid));

        $rec->dhr = explode(';', $rec->day_hrs);

        $rec->retURL = $retURL ?? route('stf_wrkhrs.index');
        //dd($rec->retURL );

        $data = new \stdClass();
        $data->opertypes = opertype::lstFor_cached([
            'active' => 1,
        ], 5);

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights", 'data'));
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
//            'orgchargeid.required' => 'Укажите вид начисления/удержания',
            'begdate.required' => 'Укажите дату в пределах учетного периода работы',
//            'forenddate.required' => 'Укажите окончание периода работы',
            'day_hr_cost.required' => 'Укажите ставку руб/час',
        ];

        $rules = [
            "staffid" => "required",
            //"orgchargeid" => "required",
            "day_hr_cost" => "required",
            "begdate" => "required",
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
        //$begdate = date_create(date_create($request->get('begdate'))->format('Y-m-01'));
        $basedate = $request->get('begdate');
        if (isset($basedate)) {
            $begdate = date_create($basedate);
            $rec->yr = $begdate->format("Y");
            $rec->mn = $begdate->format("m");

            //$basedate = $request->begdate;
            $d_max = date('t', strtotime($basedate)) + 0;
            //dd($basedate, $d_max);
            $dhr = $request->day_hr;
            //$day_hr = array_flip($request->day_hr);
            //$day_hrs = implode(';', $request->day_hr);
            $day_hr_qty = 0;
            $d = 1;
            $day_hrs = '';
            if (isset($dhr)) {
                foreach ($dhr as $hr) {
//                    if ($d>0 and $d <= $d_max) {
                    if ($d <= $d_max) {
                        $day_hr_qty += $hr;
                        $day_hrs .= ';' . $hr;
                    }
                    $d++;
                }
                $day_hrs = substr($day_hrs, 1);
                $rec->day_hrs = $day_hrs;   // список
                $rec->day_tot_hrs = $day_hr_qty;
                $rec->night_tot_hrs = 0;

                //dd($basedate, $dhr, $day_hrs, $day_hr_qty);
            }

        } else {
            $rec->yr = '';
            $rec->mn = '';
            $rec->day_hrs = '';
            $rec->day_tot_hrs = 0;
            $rec->night_hrs = '';
            $rec->night_tot_hrs = 0;
        }
        $rec->staffid = $staffid;
        $rec->day_hr_cost = $request->get('day_hr_cost');
        $rec->opertypeid = $request->get('opertypeid');
        $rec->notes = $request->get('notes');

        $rec->day_tot_sum = $rec->day_tot_hrs * $rec->day_hr_cost;
        $rec->night_tot_sum = $rec->night_tot_hrs * $rec->night_hr_cost;
        $rec->tot_sum = $rec->day_tot_sum + $rec->night_tot_sum;

        //$rec->forbegdate = $request->get('forbegdate');
        //$rec->forenddate = $request->get('forenddate');
        //Упрощенный вариант, вычислим  от даты начисления/удержания
//        $rec->forbegdate = '' . date_create($rec->begdate)->format('Y-m-01');
//        $rec->forenddate = '' . date_create($rec->begdate)->format('Y-m-t');
        //$rec->forenddate = $request->get('forenddate');

        $rec->active = $request->get('active') ?? 1;

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        //dd($rec);
        $rec->save();
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);


        //Выполним действия после обновления записи ---------------------------------------------
        stf_wrkhr::on_update($rec);

        //Привязка начисления к общей ведомости наяислений и удержаний ----------------------------
        //   проверим - есть ли у организации сотрудника начисление с ид 11 - "Заработная плата"
        $orgcharge = org_charge::where(['orgid' => $rec->orgstaff->orgid, 'chargetypeid' => 11])->first();
        if (isset($orgcharge)) {

            $stfchrgcalc = stf_chrg_calc::where(
                ['ref_sysobjid' => $this->sysobjid
                , 'ref_objid' => $rec->id])->first();

            if (!isset($stfchrgcalc)) {

                $stfchrgcalc = new stf_chrg_calc([
                    "staffid" => $staffid,
                    "orgchargeid" => $orgcharge->id,
                    "charge_dir" => $orgcharge->chargetype->dir,
                    "docdate" => $basedate,
                    "forbegdate" => date_create($basedate)->format('Y-m-01'),
                    "forenddate" => date_create($basedate)->format('Y-m-t'),
                    "created_by" => $userid,
                    "created_at" => now(),
                    "ref_sysobjid" => $this->sysobjid,
                    "ref_objid" => $rec->id,
                ]);
            }
            $stfchrgcalc->staffid = $rec->staffid;
            $stfchrgcalc->charge_sum = $rec->tot_sum;
            $stfchrgcalc->updated_by = $userid;
            $stfchrgcalc->updated_at = now();
            $stfchrgcalc->save();
        }

        //---------------------------------------------------------------------------------------

        //Cache::forget("user_{$usrid}_has_acs_{$rec->acsid}");

        //$retURL = $request->get('retURL') ?? route('orgstaff.edit', $rec->staffid) . '?#chrg_calcs';
        $retURL = route($this->sysobjcode . '.edit', $rec->id);
        return redirect($retURL)->with('success', $mess);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\user_ac $rec
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        $res = $this->model::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('orgstaff.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['staffid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о рабочих часах сотрудника', 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //Выполним действия после обновления записи ---------------------------------------------
            stf_wrkhr::on_delete($res->rec);
            //---------------------------------------------------------------------------------------
            //забудем кэшированные данные про ...:
            //Cache::forget("user_{$usrid}_has_acs_{$acsid}");

            $route = route('stf_wrkhrs.index');
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
