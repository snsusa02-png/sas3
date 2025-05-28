<?php

namespace App\Http\Controllers;

use App\fuelcard;
use App\fuelcard_pay;
use App\machine;
use App\mchntype;
use App\obj_finoper;
use App\objlog;
use App\objtag;
use App\org;
use App\orgstaff;
use App\srs_hr_item;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\User;
use App\user_template;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DateTime;

class FuelcardPayController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 562;
        $this->sysobjcode = 'fuelcard_pays';
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
            if (fuelcard_pay::isLocked($recid)) {
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
            , 's_suporgid' => ''
            , 's_cardid' => ''
            , 's_cardnum' => ''
            , 's_machineid' => ''
            , 's_machine_name' => ''
            , 's_driverid' => ''
            , 's_paytypeid' => ''
            , 's_timestatuscode' => 2   //вчера
            , 's_paydate' => ''
            , 's_mchntypeid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_cardid') {
                    $sc = $sc . " and fcp.cardid = {$val}";

                } elseif ($item == 's_cardnum') {
                    $sc .= " and fc.num like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_suporgid') {
                    $sc = $sc . " and fc.suporgid = {$val}";

                } elseif ($item == 's_machineid') {
                    $sc = $sc . " and fcp.machineid = {$val}";

                } elseif ($item == 's_machine_name') {
                    $sc .= " and exists(select 1 from machines as m
                            where m.id=fcp.machineid and concat(m.regnum,' - ', m.name) like '%" . mb_strtoupper($val) . "%')";

                } elseif ($item == 's_mchntypeid') {
                    $sc .= " and exists(select 1 from machines as m
                            where m.id=fcp.machineid and mchntypeid = {$val})";

                } elseif ($item == 's_driverid') {
                    $sc = $sc . " and fcp.driverid = {$val}";

                } elseif ($item == 's_timestatuscode') {
                    if ($val == 1) //сегодня
                        $sc = $sc . " and fcp.paydate = curdate()";
                    elseif ($val == 2) //вчера
                        $sc = $sc . " and datediff(curdate(), fcp.paydate) = 1";
                    elseif ($val == 3) //за неделю
                        $sc = $sc . " and datediff(curdate(), fcp.paydate) <= 7";
                    elseif ($val == 4) //с начала текущего месяца
                        $sc .= " and extract(year_month from fcp.paydate) = extract(year_month from curdate())";
                    elseif ($val == 5
                        and DateTime::createFromFormat('Y-m-d', $search_params['s_paydate']) !== false) {
                        //конкретная дата
                        $sc .= " and fcp.paydate = '" . $search_params['s_paydate'] . "'";
                    }

                } elseif ($item == 's_paytypeid') {
                    $sc .= " and mro.paytypeid = {$val}";
                }
            }
        }
        //var_dump($sc);
        //-------------------------------------------------------------------------------------------------------------


        $recs = fuelcard_pay::from('fuelcard_pays as fcp')
            ->join('fuelcards as fc', function ($join) {
                $join->on('fc.id', '=', 'fcp.cardid');
            })
            ->leftjoin('orgs as so', function ($join) {
                $join->on('so.id', '=', 'fc.suporgid');
            })
            ->join('machines as m', function ($join) {
                $join->on('m.id', '=', 'fcp.machineid');
            })
            ->leftjoin('mchntypes as mt', function ($join) {
                $join->on('mt.id', '=', 'm.mchntypeid');
            })
            ->leftjoin('orgstaff as os', function ($join) {
                $join->on('os.id', '=', 'fcp.driverid');
            })
            ->whereraw($sc)
            ->select('fcp.id', 'fcp.paydate', 'fcp.cardid'
                , 'fcp.machineid', 'mt.name as mchntype_name'
                , 'fcp.driverid', 'fcp.notes'
                , 'fcp.paydir', 'fcp.paysum', 'fcp.fuel_qty'
                , 'fcp.active'
                //, db::raw("concat(fc.num,' - ',ifnull(fc.name, ' ')) as card_num")
                , 'fc.num as card_num'
                , 'fc.name as card_name'
                , 'os.lname as driver_name'
                , db::raw("concat(m.regnum,' ',m.name) as machine_name")
                , 'so.name as suporg_name'
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderBy('fcp.paydate', 'desc')
            ->orderBy('fc.name', 'asc');
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
            ['in_fuelcard_pays' => 1,], ['m.id', db::raw("concat(m.regnum,' - ',m.name) as name")]
        )->pluck('name', 'id')->toArray();

        $data->mchntypes = mchntype::lstFor([
            'in_fuelcard_pays' => 1,
        ]);
        //dd($search_params['s_mchntypeid'],$data->mchntypes);

        $data->drivers = orgstaff::lstFor([
            'staff_in_fuelcard_pays' => 1,
        ]);

        $data->cards = fuelcard::lstFor([
            'in_fuelcard_pays' => 1,
        ]);

        $data->suporgs = org::lstFor([
            'in_fuelcards_suporgid' => 1,
        ]);
//        dd($data->suporgs);

        $data->paytypes = fuelcard_pay::paydirs();

        $data->statuses = [0 => 'черновик', 2 => 'ожидает согласования', 4 => 'согласован'];
        $data->dates = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц'];
        $data->timestatuses = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц', 5 => 'календарь'];

        $data->yes_no = [1 => 'есть', 0 => 'нет'];

        //Выясним - есть ли у пользователя шаблон для этого типа объектов ИС
        $data->template_id = user_template::where(['sysobjid' => $this->sysobjid, 'userid' => $userid])->first()->id ?? null;

        return view('fuelcard_pays.index', compact('recs', 'rec0'
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

                $paydate = $request->get('paydate');
                $yesterday = new DateTime('yesterday');
                $pd = $yesterday->format('Y-m-d');

                $paydate = (isset($paydate)) ? strftime('%Y-%m-%d', strtotime($paydate)) : $pd;

                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                //dd($tmplt);
                if (isset($tmplt->fuelcard_pay)) {
                    $newData = (array)$tmplt->fuelcard_pay; //конверитруем в массив
                }

                $dw_id = null;

                //$curdate = strftime('%Y-%m-%d', strtotime(now()));

                //Добавим свои значения
                $newData['id'] = -1;
                $newData['paydate'] = $newData['paydate'] ?? $paydate;
                $newData['active'] = 1;
                $newData['created_by'] = $userid;

                $rec = new fuelcard_pay($newData);
                //---------------------------------------------------------

            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $sc = '1=1';

            $rec = fuelcard_pay::from('fuelcard_pays as fcp')->whereRaw($sc)->where('id', $id)->first();

            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'));

        }

        $rec->retURL = $request->get('returl');

        $rec->in_gk = machine::from('machines as m')->where('m.id', $rec->machineid)
                ->selectRaw("(select count(*) from objflags f where f.sysobjid=111 and f.flagtypeid=12 and f.objid=m.orgid) as in_gk")->first()->in_gk ?? -1;
        //dd($rec->in_gk);

        $rec->cards = fuelcard::lstFor(['active_or_current' => 1]);

        //Типы оплат
        $rec->paytypes = fuelcard_pay::paydirs();


        if ($rec->id <> -1) {
            //для не новых записей

            //пересчет фин транзакций
            fuelcard_pay::rfr_finopers($rec->id);
        }

        if ($usrrights['save']) {
            //установим минимально-допустимую дату для wrkdate
            $rec->wrkdate_min = fuelcard_pay::min_wrkdate();
        }
        //dd($usrrights);
        return view('fuelcard_pays.edit', compact('rec', "usrrights"));
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
            'paydate.required' => 'Укажите дату операции',
            'cardid.required' => 'Укажите топливную карту',
            'machineid.required' => 'Не указан автомобиль',
//                'driverid.required' => 'Не указан водитель',
            'fuel_qty.required' => 'Укажите объем топлива в литрах',
        ];

        $rules = [
            'paydate' => 'required',
            'cardid' => 'required',
            'machineid' => 'required',
//                'driverid' => 'required',
//            'fuel_qty' => 'gt:0',
            'fuel_qty' => 'required',
        ];

        $request->validate($rules, $messages);

        if (1 == 0) {
            //проверка что запись не пересекается с другой открытой записью с этого объекта за эту дату

            $wrkdate = $request->get('wrkdate');
            $machineid = $request->get('machineid');

            $rules = [
                "items_count" => [
                    function ($attribute, $value, $fail) use ($id, $wrkdate, $machineid) {
                        //
                        $cnt = fuelcard_pay::where(['machineid' => $machineid, 'wrkdate' => $wrkdate, 'statusid' => 0])
                            ->where('id', '<>', $id)
                            ->count();
                        if ($cnt > 0) {
                            $fail("Есть другой открытый табель для этой техники/даты!");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);
        }

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {

            $rec = new fuelcard_pay([
                "active" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = fuelcard_pay::find($id);
            $mess = "Запись обновлена";
        }

        $rec->cardid = $request->get('cardid');
        $rec->paydate = $request->get('paydate');
        //$rec->driverid = $request->get('driverid');
        $rec->machineid = $request->get('machineid');
        $rec->fuel_qty = $request->get('fuel_qty');
        $rec->paydir = -1;
        $rec->paysum = $request->get('paysum');
        $rec->notes = mb_substr($request->get('notes'), 0, 160);
        $rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        //Выполним действия после обновления записи ---------------------------------------------
        fuelcard_pay::on_update($rec);
        //---------------------------------------------------------------------------------------

        return redirect(route('fuelcard_pays.index') . '?returl=' . $request->get('returl'))
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

        $res = fuelcard_pay::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('fuelcard_pays.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('fuelcard_pays.index', ['machineid' => $res->obj['machineid'] ?? 0, 'parid' => $res->obj['planid'] ?? 0]);
            connectify('success', ($res->obj['name'] ?? '-'), 'Запись удалена.');

            //Выполним действия после удаления записи -----------------------------------------------
            fuelcard_pay::on_delete($res->rec);
            //---------------------------------------------------------------------------------------

        }
        return redirect($route)->with($sd);
    }

    public
    function admindelete($id)
    {
        $rec = fuelcard_pay::find($id);
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
        $rec = fuelcard_pay::from('fuelcard_pays as p')
            ->select('p.*')->where('id', $id)->first();

        $rec->staff = fuelcard_pay_staff::lstAllForfuelcard_pay($id);

        $rec->items = fuelcard_pay_item::from('fuelcard_pay_items as i')
            ->leftjoin('orgs as o', function ($join) {
                $join->on('o.id', '=', 'i.exeorgid');
            })
            ->leftjoin('orgstaff as os', function ($join) {
                $join->on('os.id', '=', 'i.exestaffid');
            })
            ->where('i.protid', $rec->id)
            ->select('i.*', 'o.name as exeorgname', DB::raw("concat(os.lname,' ',os.fname,' ',os.mname) as exestaffname"))
            ->orderby('ordr')->get();


        return view('fuelcard_pays.printform1', compact('rec'));

    }

    public
    function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = fuelcard_pay::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        $document['tags'] = objtag::lstTags($this->sysobjid, $id);
        //dd($document);


        $template_js = [
            'fuelcard_pay' => $document,
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

        $rslt = fuelcard_pay::clone($id);
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

            $list = fuelcard_pay::where([
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
            Log::error('fuelcard_pay::data_for_driver_works:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
