<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\buildopertype;
use App\contract;
use App\driver_work;
use App\mchn_raid;
use App\machine;
use App\mchn_opertype;
use App\objlog;
use App\objtag;
use App\org;
use App\orgstaff;
use App\refitem;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\unittype;
use App\User;
use App\user_template;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\place;
use Illuminate\Support\Facades\Log;

class MchnRaidController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1106;
        $this->sysobjcode = 'mchn_raids';
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

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
        $usrrights['manager'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.manager');

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

        //$usrrights = $this->setInterfaceRight(-1);
        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.save'),
            'manager' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.manager'),
        );
        if (!$usrrights['read']) {
            return view('home');
        }

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска: массив из имени и значенния по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_ri_name' => ''
            , 's_machineid' => ''
            , 's_driverid' => ''
            , 's_paytypeid' => ''
            , 's_date' => ''
            , 's_load_placeid' => ''
            , 's_unload_placeid' => ''
            , 's_orgid' => ''
            , 's_disp_userid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";


        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_ri_name') {
                    $sc = $sc . " and exists(select 1 from refitems as ri where ri.id=mr.refitmid and ri.name like '%" . mb_strtoupper($val) . "%')";

                } elseif ($item == 's_machineid') {
                    $sc = $sc . " and mr.machineid = {$val}";

                } elseif ($item == 's_driverid') {
                    $sc = $sc . " and mr.driverid = {$val}";

                } elseif ($item == 's_date') {
                    if ($val == 1) //сегодня
                        $sc = $sc . " and mr.wrkdate = curdate()";
                    elseif ($val == 2) //вчера
                        $sc = $sc . " and datediff(curdate(), mr.wrkdate) = 1";
                    elseif ($val == 3) //за неделю
                        $sc = $sc . " and datediff(curdate(), mr.wrkdate) <= 7";
                    elseif ($val == 4) //с начала текущего месяца
                        $sc = $sc . " and extract(year_month from mr.wrkdate) = extract(year_month from curdate())";

                } elseif ($item == 's_load_placeid') {
                    $sc = $sc . " and mr.load_placeid = {$val}";

                } elseif ($item == 's_unload_placeid') {
                    $sc = $sc . " and mr.unload_placeid = {$val}";

                } elseif ($item == 's_orgid') {
                    $sc = $sc . " and mr.orgid = {$val}";

                } elseif ($item == 's_paytypeid') {
                    $sc = $sc . " and mr.paytypeid = {$val}";

                } elseif ($item == 's_disp_userid') {
                    $sc = $sc . " and mr.disp_userid = {$val}";

                } elseif ($item == 's_statusid') {
                    $sc = $sc . " and mr.statusid = {$val}";

                }

            }
        }
        //var_dump($sc);
        //-------------------------------------------------------------------------------------------------------------

        //по-старому ---------------
        //для совместимости со старым методом формированя условия отбора - инициализируем переменные поиска
//        foreach ($search_params as $item => $val) {
//            $$item = $val;
//        }
        // --------------------------------------------------------------------


        $recs = mchn_raid::from('mchn_raids as mr')
            ->join('orgstaff as os', function ($join) {
                $join->on('os.id', '=', 'mr.driverid');
            })
            ->join('machines as m', function ($join) {
                $join->on('m.id', '=', 'mr.machineid');
            })
            ->leftjoin('users as du', function ($join) {
                $join->on('du.id', '=', 'mr.disp_userid');
            })
            ->whereraw($sc)
            ->select('mr.*'
                , db::raw("TIME_FORMAT(mr.wrkbegdt, '%H:%i') as beg_hm")
                , db::raw("TIME_FORMAT(mr.wrkenddt, '%H:%i') as end_hm")
                , 'os.lname as staff_name'
                , 'du.lname as disp_name'
                , 'm.regnum as machine_name'

            );


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderBy('mr.wrkdate', 'desc')
            ->orderby('mr.id');
//        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 10);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->machines = machine::getFor(
            ['in_mchn_raids' => 1,], ['m.id', db::raw("concat(m.regnum,' - ',m.name) as name")]
        )->pluck('name', 'id')->toArray();

        $data->drivers = orgstaff::lstFor([
            'driver_in_mchn_raids' => 1,
        ]);

        $data->refitems = refitem::lstFor_cached([
            'in_mchn_raids' => 1,
        ], 5);

        $data->load_places = place::lstFor_cached([
            'loadplace_in_mchn_raids' => 1,
        ], 5);

        $data->unload_places = place::lstFor_cached([
            'unloadplace_in_mchn_raids' => 1,
        ], 5);

        $data->orgs = org::lstFor_cached([
            'in_mchn_raids_orgid' => 1,
        ], 5);

        $data->dispatchers = User::lstFor_cached([
            'in_mchn_raids_dispuserid' => 1,
        ], 5);

        $data->paytypes = mchn_raid::paytypes();

        $data->statuses = [0 => 'черновик', 2 => 'ожидает согласования', 4 => 'согласован'];
        $data->dates = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц'];
        $data->yes_no = [1 => 'есть', 0 => 'нет'];

        //Выясним - есть ли у пользователя шаблон для этого типа объектов ИС
        $data->template_id = user_template::where(['sysobjid' => $this->sysobjid, 'userid' => $userid])->first()->id ?? null;

        return view('mchn_raids.index', compact('recs', 'rec0'
            , 'data', 'search_params', 'sort_params'
            , 'usrrights'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request, $dw_id = null)
    {
//dd($dw_id);

//        $userid = \Auth::user()->id;
//        $cnt = mchn_raid::where(['created_by' => $userid, 'statusid' => 0])->count();
//        if ($cnt > 0) {
//            return redirect(route($this->sysobjcode . '.index'))->with(['error' => "У вас есть незавершенные документы({$cnt})! Перед созданием нового документа, завершите их."]);
//        }
        return $this->edit($request, -1, $dw_id);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit(Request $request, $id, $dw_id = null)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                //Значения "по-умолчанию" для новой записи ----------------

                $wrkdate = $request->get('wrkdate');
                $wrkdate = (isset($wrkdate)) ? strftime('%Y-%m-%d', strtotime($wrkdate)) : '';

                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                //dd($tmplt);
                if (isset($tmplt->mchn_raid)) {
                    $newData = (array)$tmplt->mchn_raid; //конверитруем в массив
                }

                //Если задан отчет о работе водителя, то его значения имеют приоритет над значениями шаблона
                if (isset($dw_id) and $dw_id > 0) {
                    // возьмем часть значений из отчета о работе водителя
                    $driver_work = driver_work::find($dw_id);
                    if (isset($driver_work)) {
                        $newData['wrkdate'] = $driver_work->wrkdate;
                        $newData['driverid'] = $driver_work->staffid;
                        $newData['machineid'] = $driver_work->machineid;
                    }
                } else {
                    $dw_id = null;
                }

                //$curdate = strftime('%Y-%m-%d', strtotime(now()));

                //Добавим свои значения
                $newData['id'] = -1;
                $newData['dw_id'] = $dw_id;
                $newData['wrkdate'] = $newData['wrkdate'] ?? $wrkdate;
                $newData['disp_userid'] = $userid;
                $newData['statusid'] = 0;
                $newData['active'] = 1;
                $newData['created_by'] = $userid;

                $rec = new mchn_raid($newData);
                //---------------------------------------------------------

            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $sc = '1=1';

            $rec = mchn_raid::from('mchn_raids as crs')->whereRaw($sc)->where('id', $id)->first();


            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'));

        }

        $rec->retURL = $request->get('returl');

        $rec->begtime = (isset($rec->wrkbegdt)) ? strftime('%H:%M', strtotime($rec->wrkbegdt)) : '';
        $rec->endtime = (isset($rec->wrkenddt)) ? strftime('%H:%M', strtotime($rec->wrkenddt)) : '';

        //сформируем комплексный идентификатор организации/контракта субподряда
        $rec->orgcontractid = ($rec->exe_orgid ?? '') . ':' . ($rec->exe_contractid ?? '');

        //ограничитель для времени - не в будущем
        $max_dt = date_create(date('Y-m-d H:i:s', strtotime('+1 day -1 second', strtotime($rec->wrkdate))));
        $max_dt = ($max_dt > now()) ? now() : $max_dt;
        //dd($max_dt, $max_dt->format('H:i'));
        $rec->maxtime = $max_dt->format('H:i');
        //dd($rec->begtime, $rec->endtime, $rec->maxtime);

        //доступные режимы эксплуатации техники
        $rec->mots = mchn_opertype::lstFor(['machineid' => $rec->machineid]);

        //Единицы измерения кол-ва груза
        $rec->unittypes = [8 => 'м3', 10 => 'т'];

        //Типы оплат
        $rec->paytypes = mchn_raid::paytypes();

        if (!isset($rec->mot_id) and count($rec->mots) == 1)
            $rec->mot_id = array_key_first($rec->mots);


        $rec->status_name = 'черновик';
        $rec->status_style = 'background-color:silver';
        if ($rec->active == 1) {
            $rec->status_name = 'активно';
            $rec->status_style = 'background-color:#b7f192;';
        }

        $statuses = [];

        $rec->statusid = 0;

        if ($rec->statusid == 0) {
            //на этапе "Редактирование"

            $statuses = [
                0 => 'редактирование',
            ];

            if ($rec->created_by == $userid)
                $statuses[2] = 'подготовлен для согласования';


            //--------------------------------------------------------------------------


        } elseif ($rec->statusid == 2) {
            //на этапе "Согласование мастером/прорабом"

            if ($rec->created_by == $userid)
                $statuses[0] = 'редактирование';

            $statuses[2] = 'согласование';

            //если есть право согласовывать
            if (1 == 1)
                $statuses[4] = 'согласовать';

        } elseif ($rec->statusid == 4) {
            //на этапе "Согласовано мастером/прорабом"

            $statuses[4] = 'согласовано';

            //если есть право согласовывать
            if (1 == 1)
                $statuses[2] = 'вернуть на согласование';

        } else {
            $rec->statuses = [
                0 => 'редактирование',
                2 => 'подготовлен для согласования',
            ];
        }
        $rec->statuses = $statuses;
        //dd($rec->statuses);

        //$usrrights['edit'] = ($rec->created_by == $userid and $rec->statusid == 0);
        $usrrights['delete'] = ($usrrights['delete'] and ($rec->created_by == $userid or $usrrights['manager']) and $rec->statusid == 0);
        $usrrights['save'] = ($usrrights['save'] and ($rec->created_by == $userid or $usrrights['manager']) and $rec->statusid == 0);
        $usrrights['edit'] = (($rec->created_by == $userid or $usrrights['manager']) and $rec->statusid == 0);
        //можно ли изменить wrkDate, Machineid, Driverid (DMD)
        $usrrights['edit_dmd'] = ($usrrights['edit'] and (!isset($rec->dw_id)));
        $usrrights['change_status'] = (($rec->created_by == $userid or $usrrights['manager']));

        //корректировка прав с учетом статуса -------------------------------------------

        if ($rec->statusid != 0) {
            $usrrights['create'] = $usrrights['delete'] = false;
        }
        //-------------------------------------------------------------------------------

//        //сконструируем права для внутренних списков
//        $usrrights['mchn_raid_items.create'] = $usrrights['create'];
//        $usrrights['mchn_raid_items.save'] = $usrrights['save'];

        return view('mchn_raids.edit', compact('rec', "usrrights"));
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
        //проверим текущий статус документа
        $statusid = ($id == -1) ? 0 : mchn_raid::find($id)->statusid ?? 0;

        if ($statusid == 0) {
            //черновик
            $messages = [
                'machineid.required' => 'Не указан автомобиль',
                'driverid.required' => 'Не указан водитель',
                'wrkdate.required' => 'Укажите дату проведения работ',
                'statusid.required' => 'Укажите статус готовности документа',
            ];

            $rules = [
                'machineid' => 'required',
                'driverid' => 'required',
                'wrkdate' => 'required',
            ];
        } else {
            $messages = [
                'statusid.required' => 'Укажите статус готовности документа',
            ];

            $rules = [
                "statusid" => "required",
            ];
        }

        $request->validate($rules, $messages);

        if ($statusid == 0 and $request->get('statusid') == 2) {
            //перевод табеля из "Черновик" в "Подготовлено работником для утверждения руководителем"

            $rules = [
                'begtime' => 'required',
                'endtime' => 'required',
                'meter_begqty' => 'required',
                'meter_endqty' => 'required',
                'fuel_begqty' => 'required',
                'fuel_inpqty' => 'required',
                'fuel_endqty' => 'required',
            ];
            $messages = [
                'begtime.required' => 'Укажите время начала работы',
                'endtime.required' => 'Укажите время окончания работы',
                'meter_begqty.required' => 'Укажите показания спидометра перед началом работ',
                'meter_endqty.required' => 'Укажите показания спидометра после окончания работ',
                'fuel_begqty.required' => 'Укажите остаток топлива перед началом работ',
                'fuel_inpqty.required' => 'Укажите сколько топлива было получено в теченние периода работы. 0 - если ничего',
                'fuel_endqty.required' => 'Укажите остаток топлива после окончания работ',
            ];
            //dd($rules);
            $request->validate($rules, $messages);
            //-----------------------------------------------------------------------------------------------------

        }

        if (1 == 0) {
            //проверка что запись не пересекается с другой открытой записью с этого объекта за эту дату

            $wrkdate = $request->get('wrkdate');
            $machineid = $request->get('machineid');

            $rules = [
                "items_count" => [
                    function ($attribute, $value, $fail) use ($id, $wrkdate, $machineid) {
                        //
                        $cnt = mchn_raid::where(['machineid' => $machineid, 'wrkdate' => $wrkdate, 'statusid' => 0])
                            ->where('id', '<>', $id)
                            ->count();
                        if ($cnt > 0) {
                            $fail("Есть другой открытый табель для этой технике/даты!");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);
        }

        if ($statusid == 2 and $request->get('statusid') == 0) {
            //перевод табеля из "Подготовлено" в "Черновик"

            $wrkdate = mchn_raid::find($id)->wrkdate ?? null;
            $days = date_diff(date_create($wrkdate), today())->days;

            $rules = [
                "items_count" => [
                    function ($attribute, $value, $fail) use ($days) {
                        //проверка что "возраст" открываемой записи не более 1 дней ------------
                        if ($days > 1) {
                            $fail("Запрещено изменять КУРСИА, созданную более суток назад ({$days})!");
                        }
                        //--------------------------------------------------------------------------------------
                    },
                ],
            ];

            $request->validate($rules, $messages);
        }

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {

            //проверим - задана ли связь с driver_works
            $dw_id = $request->get('dw_id');
            if (isset($dw_id))
                $driver_work = driver_work::find($dw_id);

            //если нет, то попробуем поискать по соответствию wrkdate/machineid/driverid
            if (!isset($driver_work)) {
                $wrkdate = $request->get('wrkdate');
                $machineid = $request->get('machineid');
                $driverid = $request->get('driverid');

                $driver_work = driver_work::find_or_create([
                    'wrkdate' => $wrkdate,
                    'machineid' => $machineid,
                    'staffid' => $driverid,
                ]);
            }

            $rec = new mchn_raid([
                "dw_id" => $driver_work->id,
                "active" => 0,
                //"statusid" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = mchn_raid::find($id);
            $mess = "Запись обновлена";
        }

        if ($statusid == 0) {

            $rec->wrkdate = $request->get('wrkdate');
            $rec->driverid = $request->get('driverid');
            $rec->drivername = $request->get('drivername');
            $rec->machineid = $request->get('machineid');
            $rec->mot_id = $request->get('mot_id');

            //если до сих пор рейс не привязан к отчету о работе
            if (!isset($rec->dw_id)) {
                //то найдем/создадим такой отчет и привяжем
                $driver_work = driver_work::find_or_create([
                    'wrkdate' => $rec->wrkdate,
                    'machineid' => $rec->machineid,
                    'staffid' => $rec->driverid,
                ]);
                $rec->dw_id = $driver_work->id;
            }

//            $rec->wrk_descript = mb_substr($request->get('wrk_descript'), 0, 360);

//            $orgcontractid = $request->get('orgcontractid');
//            $tids = explode(':', $orgcontractid);
//            $rec->exe_orgid = $tids[0];   //исходный заказчик (подрядчик)
//            $rec->exe_orgid = ($rec->exe_orgid == '') ? null : $rec->exe_orgid;
//
//            $rec->exe_contractid = $tids[1] ?? null;   //договор подряда/бюджета
//            $rec->exe_contractid = ($rec->exe_contractid == '') ? null : $rec->exe_contractid;


            $rec->notes = mb_substr($request->get('notes'), 0, 300);

//            $rec->break_hrs = $request->get('break_hrs', 0);    //Продолжительность перерыва
            $rec->wrkbegdt = date_create($rec->wrkdate)->format('Y-m-d') . ' ' . $request->get('begtime');
            $endtime = $request->get('endtime');
            if ($endtime != '') {
                $rec->wrkenddt = date_create($rec->wrkdate)->format('Y-m-d') . ' ' . $endtime;
                $rec->mchnwrkhrs = round((date_create($rec->wrkenddt)->getTimestamp() - date_create($rec->wrkbegdt)->getTimestamp()) / 3600, 1);
            } else {
                $rec->wrkenddt = null;
                $rec->mchnwrkhrs = null;
            }

            $rec->stfwrkhrs = $request->get('stfwrkhrs');

            $rec->refitmid = $request->get('refitmid');
            $rec->cargo_name = mb_substr($request->get('cargo_name'), 0, 60);

            $rec->suporgid = $request->get('suporgid');
            $rec->load_placeid = $request->get('load_placeid');
            $rec->load_placename = $request->get('load_placename');

            $rec->load_qty = $request->get('load_qty');
            $rec->qty_unittypeid = $request->get('qty_unittypeid');
            $rec->qty_unit = unittype::find($rec->qty_unittypeid)->name ?? '';
            $rec->load_price = $request->get('load_price');
            $rec->load_sum = $rec->load_qty * $rec->load_price;

            $rec->unload_placeid = $request->get('unload_placeid');
            $rec->unload_placename = $request->get('unload_placename');

            $rec->unload_qty = $request->get('unload_qty');
            $rec->unload_price = $request->get('unload_price');
            $rec->unload_sum = $rec->unload_qty * $rec->unload_price;

            $rec->raid_qty = $request->get('raid_qty');
            $rec->raid_salary = $request->get('raid_salary');

            $rec->orgid = $request->get('orgid');
            $rec->org_name = $request->get('org_name');
            $rec->paytypeid = $request->get('paytypeid');

            $rec->disp_userid = $request->get('disp_userid');

//            $rec->meter_begqty = $request->get('meter_begqty');
//            $rec->meter_endqty = $request->get('meter_endqty');
//            if (isset($rec->meter_endqty) and isset($rec->meter_begqty))
//                $rec->meter_qty = $rec->meter_endqty - $rec->meter_begqty;
//            else
//                $rec->meter_qty = null;
//
//            $rec->fuel_begqty = $request->get('fuel_begqty');
//            $rec->fuel_inpqty = $request->get('fuel_inpqty', 0);
//            $rec->fuel_endqty = $request->get('fuel_endqty');
//
//            if (isset($rec->fuel_begqty) and isset($rec->fuel_inpqty) and isset($rec->fuel_endqty))
//                $rec->fuel_spentqty = $rec->fuel_begqty + $rec->fuel_inpqty - $rec->fuel_endqty;
//            else
//                $rec->fuel_spentqty = null;


//            if ($request->get('statusid') == 2) {
//                //переводим на "Согласование"
//                $rec->stf_signed = 1;
//                $rec->stf_signed_at = now();
//            } else {
//                $rec->stf_signed = null;
//                $rec->stf_signed_at = null;
//                $rec->mngr_signed = null;
//                $rec->mngr_signed_by = null;
//                $rec->mngr_signed_at = null;
//            }


        } elseif ($statusid == 2) {
            //согласование

            if ($request->get('statusid') == 4) {
                //переводим на "Согласовано"
                $rec->mngr_signed = 1;
                $rec->mngr_signed_by = $userid;
                $rec->mngr_signed_at = now();

            } elseif ($request->get('statusid') == 0) {
                //переводим на "Редактирование"
                $rec->stf_signed = null;
                $rec->stf_signed_at = null;

                $rec->mngr_signed = null;
                $rec->mngr_signed_by = null;
                $rec->mngr_signed_at = null;
            }

        }

        //$rec->statusid = $request->get('statusid', 0);
        $rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();


        //пересчитаем кол-во рейсов и ЗП аодителя за рейсы --------------------------------------
        if (isset($rec->dw_id)) {
            $raid_info = mchn_raid::where('dw_id', $rec->dw_id)
                ->selectRaw("sum(raid_qty) as qty, sum(raid_qty*raid_salary) as sum")
                ->first();

            $driver_work = driver_work::find($rec->dw_id);
            //$driver_work->salary_sum = $driver_work->salary_sum - $driver_work->raid_sum + $raid_info->sum; //коррекция общей суммы ЗП
            $driver_work->salary_sum = $raid_info->sum + $driver_work->pdt_sum + $driver_work->repair_sum; //коррекция общей суммы ЗП
            $driver_work->raid_qty = $raid_info->qty;
            $driver_work->raid_sum = $raid_info->sum;
            $driver_work->save();
        }
        //---------------------------------------------------------------------------------------

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);


        if ($id == -1 or $rec->statusid <> $statusid)
            return redirect(route('mchn_raids.edit', $rec->id) . '?returl=' . $request->get('returl'));
        else {
            $retURL = $request->get('returl') ?? route($this->sysobjcode . '.index')
                . '?page=' . session($this->sysobjcode . '_pageno') . '#' . $rec->id;

            return redirect($retURL)->with('success', $mess);
        }
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
        $res = mchn_raid::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('mchn_raids.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('mchn_raids.index', ['machineid' => $res->obj['machineid'] ?? 0, 'parid' => $res->obj['planid'] ?? 0]);
            connectify('success', ($res->obj['name'] ?? '-'), 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    public
    function printform1(Request $request, $id)
    {
        //печать в форме протокола
        $rec = mchn_raid::from('mchn_raids as p')
            ->select('p.*')->where('id', $id)->first();

        $rec->staff = mchn_raid_staff::lstAllFormchn_raid($id);

        $rec->items = mchn_raid_item::from('mchn_raid_items as i')
            ->leftjoin('orgs as o', function ($join) {
                $join->on('o.id', '=', 'i.exeorgid');
            })
            ->leftjoin('orgstaff as os', function ($join) {
                $join->on('os.id', '=', 'i.exestaffid');
            })
            ->where('i.protid', $rec->id)
            ->select('i.*', 'o.name as exeorgname', DB::raw("concat(os.lname,' ',os.fname,' ',os.mname) as exestaffname"))
            ->orderby('ordr')->get();


        return view('mchn_raids.printform1', compact('rec'));

    }

    public function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = mchn_raid::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        $document['tags'] = objtag::lstTags($this->sysobjid, $id);
        //dd($document);

        $template_js = [
            'mchn_raid' => $document,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

    static public function data_for_driver_works(Request $request)
    {
        //2021-11-27 SNS. Данные разные

        $result = "";
        try {

            $list = mchn_raid::where([
                'driverid' => $request->driverid,
                'wrkdate' => $request->wrkdate,
                'active' => 1,
            ])
                ->select(db::raw("count(1) as raid_qty")
                    , db::raw("sum(raid_salary) as raid_salary_sum"))
                ->first();

            $result = array('data' => $list);
            //Log::info(implode('; ', $list));

        } catch (\Exception $e) {
            Log::error('mchn_raid::data_for_driver_works:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
