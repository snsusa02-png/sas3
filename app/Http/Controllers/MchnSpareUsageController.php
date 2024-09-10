<?php

namespace App\Http\Controllers;

use App\mchn_spare_usage;
use App\machine;
use App\objlog;
use App\objtag;
use App\srs_hr_item;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\user_template;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DateTime;

class MchnSpareUsageController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 489;
        $this->sysobjcode = 'mchn_spare_usages';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }

    /*
     * Установка прав пользователя
     */
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
        $usrrights['set_lockdate'] = false;
        $usrrights['finopers_refresh'] = usrsysright::isUserHasRightByCode_cached($userid, 'admin-global');

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['manager'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.manager');
        $usrrights['set_lockdate'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.set_lockdate');;

        if ($recid > 0) {

            //для существующих записей проверим открытость периода
            if (mchn_spare_usage::isLocked($recid)) {
                $usrrights['save'] = false;
                $usrrights['delete'] = false;
                $usrrights['admindelete'] = false;
            } else {
                // период Открыт - все определяется правами
                $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
                $usrrights['admindelete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.admindelete');
            }
        }

        $usrrights['edit'] = $usrrights['save'];

        return $usrrights;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight(-1);
        if (1 == 0 or !$usrrights['read']) {
            return view('home')->with(['error' => 'Нет доступа!']);
            //return redirect(back())->with(['error'=>'Нет доступа!']);
        }

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска: массив из имени и значенния по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_cardid' => ''
            , 's_machineid' => ''
            , 's_machine_name' => ''
            , 's_driverid' => ''
            , 's_paytypeid' => ''
            , 's_timestatuscode' => 2   //вчера
            , 's_operdate' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_machineid') {
                    $sc = $sc . " and msu.machineid = {$val}";

                } elseif ($item == 's_machine_name') {
                    $sc .= " and exists(select 1 from machines as m
                            where m.id=msu.machineid and concat(m.regnum,' - ', m.name) like '%" . mb_strtoupper($val) . "%')";

                } elseif ($item == 's_driverid') {
                    $sc = $sc . " and msu.driverid = {$val}";

                } elseif ($item == 's_timestatuscode') {
                    if ($val == 1) //сегодня
                        $sc = $sc . " and msu.operdate = curdate()";
                    elseif ($val == 2) //вчера
                        $sc = $sc . " and datediff(curdate(), msu.operdate) = 1";
                    elseif ($val == 3) //за неделю
                        $sc = $sc . " and datediff(curdate(), msu.operdate) <= 7";
                    elseif ($val == 4) //с начала текущего месяца
                        $sc .= " and extract(year_month from msu.operdate) = extract(year_month from curdate())";
                    elseif ($val == 5
                        and DateTime::createFromFormat('Y-m-d', $search_params['s_operdate']) !== false) {
                        //конкретная дата
                        $sc .= " and msu.operdate = '" . $search_params['s_operdate'] . "'";
                    }
                }
            }
        }
        //var_dump($sc);
        //-------------------------------------------------------------------------------------------------------------

        $recs = mchn_spare_usage::from('mchn_spare_usages as msu')
            ->join('machines as m', function ($join) {
                $join->on('m.id', '=', 'msu.machineid');
            })
            ->whereraw($sc)
            ->select('msu.id', 'msu.operdate'
                , 'msu.machineid', 'msu.notes'
                , 'msu.spare_name', 'msu.qty', 'msu.price', 'msu.spare_sum'
                , 'msu.active'
                , db::raw("concat(m.regnum,' ',m.name) as machine_name")
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderBy('msu.operdate', 'desc');
//        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 10);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid);

        $data->machines = machine::getFor(
            ['in_mchn_spare_usages' => 1,], ['m.id', db::raw("concat(m.regnum,' - ',m.name) as name")]
        )->pluck('name', 'id')->toArray();

        $data->statuses = [0 => 'черновик', 2 => 'ожидает согласования', 4 => 'согласован'];
        $data->dates = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц'];
        $data->timestatuses = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц', 5 => 'календарь'];

        $data->yes_no = [1 => 'есть', 0 => 'нет'];

        //Выясним - есть ли у пользователя шаблон для этого типа объектов ИС
        $data->template_id = user_template::where(['sysobjid' => $this->sysobjid, 'userid' => $userid])->first()->id ?? null;

        return view($this->sysobjcode . '.index', compact('recs', 'rec0'
            , 'data', 'search_params', 'sort_params'
            , 'usrrights'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request)
    {
        return $this->edit($request, -1);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit(Request $request, $id)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {
                //Значения "по-умолчанию" для новой записи ----------------

                $paydate = $request->get('operdate');
                $yesterday = new DateTime('yesterday');
                $pd = $yesterday->format('Y-m-d');

                $paydate = (isset($paydate)) ? strftime('%Y-%m-%d', strtotime($paydate)) : $pd;

                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                //dd($tmplt);
                if (isset($tmplt->mchn_spare_usage)) {
                    $newData = (array)$tmplt->mchn_spare_usage; //конверитруем в массив
                }

                $dw_id = null;

                //$curdate = strftime('%Y-%m-%d', strtotime(now()));

                //Добавим свои значения
                $newData['id'] = -1;
                $newData['operdate'] = $newData['operdate'] ?? $paydate;
                $newData['active'] = 1;
                $newData['created_by'] = $userid;
                $newData['qty'] = 1;

                $rec = new mchn_spare_usage($newData);
                //---------------------------------------------------------

            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $sc = '1=1';

            $rec = mchn_spare_usage::from('mchn_spare_usages as msu')->whereRaw($sc)->where('id', $id)->first();

            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'));

        }

        $rec->retURL = $request->get('returl');

        $rec->in_gk = machine::from('machines as m')->where('m.id', $rec->machineid)
                ->selectRaw("(select count(*) from objflags f where f.sysobjid=111 and f.flagtypeid=12 and f.objid=m.orgid) as in_gk")->first()->in_gk ?? -1;
        //dd($rec->in_gk);

        if ($rec->id <> -1) {
            //для не новых записей

            //пересчет фин транзакций
            //mchn_spare_usage::rfr_finopers($rec->id);
        }

        if ($usrrights['save']) {
            //установим минимально-допустимую дату для wrkdate
            $rec->wrkdate_min = mchn_spare_usage::min_wrkdate();
        }
        //dd($usrrights);
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

        $usrrights = $this->setInterfaceRight($id);
        if (!($usrrights['save']))
            return redirect()->back()->with('error', 'У вас нет права на изменение этих данных!');

        $messages = [
            'operdate.required' => 'Укажите дату операции',
            'machineid.required' => 'Не указан автомобиль',
            'spare_sum.required' => 'Укажите общую стоимость запчастей',
        ];

        $rules = [
            'operdate' => 'required',
            'machineid' => 'required',
            'spare_sum' => 'required',
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {

            $rec = new mchn_spare_usage([
                "active" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = mchn_spare_usage::find($id);
            $mess = "Запись обновлена";
        }

        $rec->operdate = $request->get('operdate');
        $rec->machineid = $request->get('machineid');
        $rec->spare_name = mb_substr($request->get('spare_name'), 0, 60);
        $rec->price = $request->get('price');
        $rec->qty = $request->get('qty');
        $rec->spare_sum = $request->get('spare_sum');
        $rec->notes = mb_substr($request->get('notes'), 0, 160);
        $rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        //Выполним действия после обновления записи ---------------------------------------------
        mchn_spare_usage::on_update($rec);
        //---------------------------------------------------------------------------------------

        return redirect(route($this->sysobjcode . '.index') . '?returl=' . $request->get('returl'))
            ->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {

        $usrrights = $this->setInterfaceRight($id);
        if (!($usrrights['delete'] or $usrrights['admindelete']))
            return redirect()->back()->with('error', 'У вас нет права на удаление этих данных!');

        $res = mchn_spare_usage::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route($this->sysobjcode . '.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route($this->sysobjcode . '.index', ['machineid' => $res->obj['machineid'] ?? 0, 'parid' => $res->obj['planid'] ?? 0]);
            connectify('success', ($res->obj['name'] ?? '-'), 'Запись удалена.');

            //Выполним действия после удаления записи -----------------------------------------------
            mchn_spare_usage::on_delete($res->rec);
            //---------------------------------------------------------------------------------------

        }
        return redirect($route)->with($sd);
    }

    public
    function admindelete($id)
    {
        $rec = mchn_spare_usage::find($id);
        if ($rec) {
            $res = $rec->admindelete();
            $sd = array();
            if ($res->err == 1) {
                $route = route($this->sysobjcode . '.edit', $id);
                $sd["error"] = $res->msg;
                objlog::log_info($this->sysobjid, $id, $res->msg, 2);

            } else {

                $route = route($this->sysobjcode . '.index') . '?page=' . session('pageno');
                $sd['success'] = 'Запись о перевозке удалена административно';
                objlog::log_info($this->sysobjid, 0, "Административное удаление перевозки id=" . $id, 2);

            }
            return redirect($route)->with($sd);
        }
        return redirect(route($this->sysobjcode . '.index') . '?page=' . session('pageno'));

    }


    public
    function printform1(Request $request, $id)
    {
        //печать в форме протокола
        $rec = mchn_spare_usage::from('mchn_spare_usages as p')
            ->select('p.*')->where('id', $id)->first();

        $rec->staff = mchn_spare_usage_staff::lstAllFormchn_spare_usage($id);

        $rec->items = mchn_spare_usage_item::from('mchn_spare_usage_items as i')
            ->leftjoin('orgs as o', function ($join) {
                $join->on('o.id', '=', 'i.exeorgid');
            })
            ->leftjoin('orgstaff as os', function ($join) {
                $join->on('os.id', '=', 'i.exestaffid');
            })
            ->where('i.protid', $rec->id)
            ->select('i.*', 'o.name as exeorgname', DB::raw("concat(os.lname,' ',os.fname,' ',os.mname) as exestaffname"))
            ->orderby('ordr')->get();


        return view($this->sysobjcode . '.printform1', compact('rec'));

    }

    public
    function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = mchn_spare_usage::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        $document['tags'] = objtag::lstTags($this->sysobjid, $id);
        //dd($document);


        $template_js = [
            'mchn_spare_usage' => $document,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

    public
    function clone($id)
    {

        if (!isset($id))
            return redirect()->back()->with('error', 'Не задана исходная запись!');

        $userid = \Auth::user()->id;
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');

        if (!$usrrights['create'])
            return redirect()->back()->with('error', 'У вас нет права на создание записей!');

        $rslt = mchn_spare_usage::clone($id);
        if ($rslt->err > 0)
            return redirect()->back()->with(['error' => $rslt->msg]);

        return redirect(route($this->sysobjcode . '.edit', $rslt->obj['id']))
            ->with(['success' => 'Вы находитесь в созданной копии']);
    }


    static public function data_for_driver_works(Request $request)
    {
        //2021-11-27 SNS. Данные разные

        $result = "";
        try {

            $list = mchn_spare_usage::where([
                'driverid' => $request->driverid,
                'wrkdate' => $request->wrkdate,
                'active' => 1,
            ])
                ->select(db::raw("count(1) as raid_qty")
                    , db::raw("sum(raid_salary) as raid_salary_sum"))
                ->first()->toArray();

            //$list['test']=12345;
            $wrkdate = $request->wrkdate;
            $rates = srs_hr_item::from('srs_hr_items as i')
                ->join('salary_rate_sets as srs', 'srs.id', 'i.srs_id')
                ->join('orgstaff as os', 'os.id', '=', DB::raw($request->driverid))
                //->where('srs.payrolltypeid', 1) //to-do - взять из карточки сотрдника
                ->leftJoin('stf_payrolltypes as spt', function ($j) use ($wrkdate) {
                    $j->on('spt.staffid', '=', 'os.id')
                        ->whereRaw("'{$wrkdate}' between spt.begdate and ifnull(spt.enddate,'{$wrkdate}')");
                })
                ->where('srs.payrolltypeid', db::raw("ifnull(spt.payrolltypeid, 1)"))
                ->where('i.wrktypeid', $request->wrktypeid)
                ->whereRaw('ifnull(srs.ownorgid,os.orgid)=os.orgid')
                ->whereRaw("'{$request->wrkdate}' between srs.begdate and ifnull(srs.enddate,'{$request->wrkdate}')")
                ->whereRaw("TIMESTAMPDIFF(month, ifnull(os.begdate,'{$request->wrkdate}'), '{$request->wrkdate}' )/12 between i.min_wrkexp and i.max_wrkexp-0.001")
                ->select('i.hr_day_rate', 'i.hr_night_rate')
                ->first()->toArray();
            $list = $list + $rates;

            $break_rates = srs_hr_item::from('srs_hr_items as i')
                ->join('wrktypes as wt', 'wt.id', 'i.wrktypeid')
                ->join('salary_rate_sets as srs', 'srs.id', 'i.srs_id')
                ->join('orgstaff as os', 'os.id', '=', DB::raw($request->driverid))
                ->where('srs.payrolltypeid', 1) //to-do - взять из карточки сотрдника
                ->where('wt.active', 1)
                ->where('wt.main', 0)
                ->whereRaw('ifnull(srs.ownorgid,os.orgid)=os.orgid')
                ->whereRaw("'{$request->wrkdate}' between srs.begdate and ifnull(srs.enddate,'{$request->wrkdate}')")
                ->whereRaw("TIMESTAMPDIFF(month, ifnull(os.begdate,'{$request->wrkdate}'), '{$request->wrkdate}' )/12 between i.min_wrkexp and i.max_wrkexp-0.001")
                ->select('i.wrktypeid', 'i.hr_day_rate', 'i.hr_night_rate')
                ->orderBy('wt.ordr')
                ->get()->toArray();
            $list['break_rates'] = $break_rates;
            //dd($list);

            $result = array('data' => $list);
            //Log::info(implode('; ', $list));

        } catch (\Exception $e) {
            Log::error('mchn_spare_usage::data_for_driver_works:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
