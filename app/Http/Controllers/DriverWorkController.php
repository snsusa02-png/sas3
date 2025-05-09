<?php

namespace App\Http\Controllers;

use App\driver_work;
use App\dw_break;
use App\mchn_raid;
use App\opertype;
use App\org_charge;
use App\orgstaff;
use App\srs_hr_item;
use App\stf_chrg_calc;
use App\sysobj;
use App\usrsysright;
use App\machine;
use App\user_template;
use App\objtag;
use App\objlog;

use App\wrktype;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use DateTime;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;


class DriverWorkController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1141;
        $this->sysobjcode = 'driver_works';
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
        //$usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($recid > 0) {

            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');

            //для существующих записей проверим открытость периода
            if (driver_work::isLocked($recid)) {

                $usrrights['save'] = false;
                $usrrights['delete'] = false;
                $usrrights['admindelete'] = false;
            }
        }

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
        if (!$usrrights['read']) {
            return view('home');
        }

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска: массив из имени и значенния по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_timestatuscode' => 2   //вчера
            , 's_docdate' => ''
            , 's_name' => ''
            , 's_machineid' => ''
            , 's_staffid' => ''
            , 's_statusid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);
        //var_dump($search_params);
        //сформируем условие запроса в БД -----
        $sc = "1=1";

        //если пользователь может читать записи в buildobjs, то показываем все табели без ограничений
        if (1 == 0 and !usrsysright::isUserHasRightByCode_cached($userid, 'buildobjs.read')) {
            //доступ ограничен только объектами, где пользователь указан как ответственный сотрудник (входит в buildobj_staffs)
            $sc .= " and exists (select 1 from buildobj_staffs as bos
                join orgstaff as os on os.id=bos.staffid and os.userid={$userid}
                where bos.machineid=dw.machineid )";
        }


        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and exists(select 1 from users as u where u.id=dw.inituserid  and u.name like '%" . mb_strtoupper($val) . "%')";

                } elseif ($item == 's_machineid') {
                    $sc = $sc . " and dw.machineid = {$val}";

                } elseif ($item == 's_staffid') {
                    $sc = $sc . " and dw.staffid = {$val}";

                } elseif ($item == 's_timestatuscode') {
                    if ($val == 1) //сегодня
                        $sc = $sc . " and dw.wrkdate = curdate()";
                    elseif ($val == 2) //вчера
                        $sc = $sc . " and datediff(curdate(), dw.wrkdate) = 1";
                    elseif ($val == 3) //за неделю
                        $sc = $sc . " and datediff(curdate(), dw.wrkdate) <= 7";
                    elseif ($val == 4) //с начала текущего месяца
                        $sc .= " and extract(year_month from dw.wrkdate) = extract(year_month from curdate())";
                    elseif ($val == 6) //за 30 дней
                        $sc = $sc . " and datediff(curdate(), dw.wrkdate) <= 30";
                    elseif ($val == 5
                        and DateTime::createFromFormat('Y-m-d', $search_params['s_docdate']) !== false) {
                        //конкретная дата
                        $sc .= " and dw.wrkdate = '" . $search_params['s_docdate'] . "'";
                    }

                } elseif ($item == 's_statusid') {
                    $sc = $sc . " and dw.statusid = {$val}";

                }

            }
        }
        //        var_dump($sc);
        //-------------------------------------------------------------------------------------------------------------


        $recs = driver_work::from('driver_works as dw')
            ->join('orgstaff as os', function ($join) {
                $join->on('os.id', '=', 'dw.staffid');
            })
            ->join('machines as m', function ($join) {
                $join->on('m.id', '=', 'dw.machineid');
            })
            ->leftjoin('wrktypes as wt', function ($join) {
                $join->on('wt.id', '=', 'dw.wrktypeid');
            })
            ->whereraw($sc)
            ->select('dw.id as id', 'dw.wrkdate'
                , 'dw.wrktypeid', 'wt.name as wrktype_name', 'dw.wrktype_notes'
                , 'dw.wrkplacename'
                , 'dw.active', 'dw.notes', 'dw.staffid'
                , 'dw.hrs_salary', 'dw.breaks_sum'
                , 'dw.raid_qty', 'dw.raid_sum'
                //, 'pdt_sum', 'repair_sum'
                , 'salary_sum'
                , 'os.name as staff_name'
                , db::raw("concat(m.regnum,' ', m.name) as machine_name")

            );


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderBy('dw.wrkdate', 'desc')
            ->orderby('dw.id');
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
            ['in_driver_works' => 1,], ['m.id', db::raw("concat(m.regnum,' - ',m.name) as name")]
        )->pluck('name', 'id')->toArray();

        $data->staffs = orgstaff::lstFor_cached([
            'in_driver_works' => 1,
        ], 5);

        $data->statuses = [0 => 'черновик', 2 => 'ожидает согласования', 4 => 'согласован'];
        //$data->dates = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц'];
        $data->timestatuses = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц', 6 => 'за 30 дн.', 5 => 'календарь'];
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
        $userid = \Auth::user()->id;

//        $cnt = driver_work::where(['created_by' => $userid, 'statusid' => 0])->count();
//        if ($cnt > 0) {
//            return redirect(route($this->sysobjcode . '.index'))->with(['error' => "У вас есть незавершенные документы({$cnt})! Перед созданием нового документа, завершите их."]);
//        }
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

                $wrkdate = $request->get('wrkdate');

                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                //dd($tmplt);
                if (isset($tmplt->driver_work)) {
                    $newData = (array)$tmplt->driver_work; //конверитруем в массив
                }

                //Добавим свои значения
                $newData['id'] = -1;
                $newData['statusid'] = 0;
                $newData['active'] = 1;
                $newData['created_by'] = \Auth::user()->id;
                $newData['wrkdate'] = $newData['wrkdate'] ?? $wrkdate;
                $newData['pdt_cost'] = $newData['pdt_cost'] ?? 200;
                $newData['repair_cost'] = $newData['repair_cost'] ?? 200;
                $newData['raid_qty'] = 0;
                $newData['raid_sum'] = 0;
                $newData['pdt_hrs'] = 0;
                $newData['pdt_sum'] = 0;
                $newData['repair_hrs'] = 0;
                $newData['repair_sum'] = 0;
                $newData['salary_sum'] = 0;
                $newData['opertypeid'] = 1; //самосвальные перевозки

                $rec = new driver_work($newData);
                //---------------------------------------------------------

            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $rec = driver_work::from('driver_works as dw')->where('id', $id)->first();

            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'));

            //попробуем захватить подходящие записи о рейсах, которые не связаны с другими отчетами
            mchn_raid::where([
                'wrkdate' => $rec->wrkdate,
                'machineid' => $rec->machineid,
                'driverid' => $rec->staffid,
                'wrktypeid' => $rec->wrktypeid,
            ])->whereNull('dw_id')
                ->update(['dw_id' => $rec->id]);
            //---------------------------------------------------------------------------------------

        }
//        $rec->begtime = (isset($rec->wrkbegdt)) ? strftime('%H:%M', strtotime($rec->wrkbegdt)) : '';
        //2023-07-16 используем пустоту wrkenddt как признак "новой" записи и для удобства ввода занулим и начальное время
        // - по настоятельной просьбе Анастасии
        $rec->begtime = (isset($rec->wrkbegdt) and isset($rec->wrkenddt)) ? strftime('%H:%M', strtotime($rec->wrkbegdt)) : '';

        $rec->wrkenddate = (isset($rec->wrkenddt)) ? date_create($rec->wrkenddt)->format('Y-m-d') : '';
        $rec->endtime = (isset($rec->wrkenddt)) ? strftime('%H:%M', strtotime($rec->wrkenddt)) : '';
        if (isset($rec->wrkbegdt) and isset($rec->wrkenddt)) {
            $diff = date_diff(date_create($rec->wrkbegdt), date_create($rec->wrkenddt));
            $rec->stfwrkhrs = round($diff->days * 24 + $diff->h + $diff->i / 60, 2);
        } else
            $rec->stfwrkhrs = 0;

        //сформируем комплексный идентификатор организации/контракта субподряда
        $rec->orgcontractid = ($rec->exe_orgid ?? '') . ':' . ($rec->exe_contractid ?? '');

        //ограничитель для времени - не в будущем
        $max_dt = date_create(date('Y-m-d H:i:s', strtotime('+1 day -1 second', strtotime($rec->wrkdate))));
        $max_dt = ($max_dt > now()) ? now() : $max_dt;
        //dd($max_dt, $max_dt->format('H:i'));
        $rec->maxtime = $max_dt->format('H:i');
        //dd($rec->begtime, $rec->endtime, $rec->maxtime);


//        $rec->breaktypes = dw_break::breaktypes();

        //Основные виды работ водителя
        $rec->main_wrktypes = wrktype::main_wrktypes();
        //$rec->aux_wrktypes = wrktype::aux_wrktypes();
        //dd($rec->aux_wrktypes);

//        if ( $id == -1){
        if (1 == 1) {
            $rec->aux_wrk_rates = wrktype::from('wrktypes as wt')
                ->leftjoin('dw_breaks as dwi', function ($join) use ($rec) {
                    $join->on('dwi.wrktypeid', '=', 'wt.id')
                        ->where('dwi.dw_id', '=', DB::raw($rec->id));
                })
                ->where('wt.active', 1)
                ->where('wt.main', '<>', 1)
                ->select('wt.id as wrktypeid'
                    , 'wt.name as wrktype_name'
                    , 'dwi.hr_day_rate'
                    , 'dwi.hr_night_rate'
                    , 'dwi.id as dwi_id'
                    , 'dwi.day_hrs'
                    , 'dwi.night_hrs'
                    , 'dwi.aux_sum'
                )
                ->orderby('wt.ordr')
                ->orderby('wt.name')
                ->get();

        } else {
            $rec->aux_wrk_rates = srs_hr_item::from('srs_hr_items as i')
                ->join('salary_rate_sets as srs', 'srs.id', 'i.srs_id')
                ->join('wrktypes as wt', 'wt.id', 'i.wrktypeid')
                ->leftjoin('orgstaff as os', 'os.id', '=', DB::raw($rec->staffid ?? 0))
                ->leftjoin('dw_breaks as dwi', function ($join) use ($rec) {
                    $join->on('dwi.wrktypeid', '=', 'wt.id')
                        ->where('dwi.dw_id', '=', DB::raw($rec->id));
                })
                ->where('srs.payrolltypeid', 1) //to-do - взять из карточки сотрдника
                ->where('wt.active', 1)
                ->where('wt.main', '<>', 1)
                ->whereRaw('ifnull(srs.ownorgid,os.orgid)=os.orgid')
                ->whereRaw("'{$rec->wrkdate}' between srs.begdate and ifnull(srs.enddate,'{$rec->wrkdate}')")
                ->whereRaw("TIMESTAMPDIFF(month, ifnull(os.begdate,'{$rec->wrkdate}'), '{$rec->wrkdate}' )/12 between i.min_wrkexp and i.max_wrkexp-0.001")
                ->select('i.wrktypeid', 'wt.name as wrktype_name', 'i.hr_day_rate', 'i.hr_night_rate'
                    , 'dwi.id as dwi_id', 'dwi.day_hrs', 'dwi.night_hrs', 'dwi.aux_sum')
                ->orderby('wt.ordr')
                ->orderby('wt.name')
                ->get();
        }
//        dd($rec->aux_wrk_rates);


        $rec->status_name = 'черновик';
        $rec->status_style = 'background-color:silver';
        if ($rec->active == 1) {
            $rec->status_name = 'активно';
            $rec->status_style = 'background-color:#b7f192;';
        }

        $statuses = [];

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

        $usrrights['edit'] = ($rec->statusid == 0);
        $usrrights['change_status'] = ($rec->created_by == $userid);

        //корректировка прав с учетом статуса -------------------------------------------
        if ($rec->statusid != 0) {
            $usrrights['create'] = $usrrights['delete'] = false;
        }
        //-------------------------------------------------------------------------------

        if ($usrrights['save']) {
            //установим минимально-допустимую дату для wrkdate
            $rec->wrkdate_min = driver_work::min_wrkdate();

            //Получим по-часовые ставки оплаты
            if (isset($rec->staffid) and isset($rec->wrkdate)) {
                $rates = srs_hr_item::from('srs_hr_items as i')
                    ->join('salary_rate_sets as srs', 'srs.id', 'i.srs_id')
                    ->join('orgstaff as os', 'os.id', '=', DB::raw($rec->staffid))
                    ->where('i.wrktypeid', $rec->wrktypeid)
                    ->where('srs.payrolltypeid', 1) //to-do - взять из карточки сотрдника
                    ->whereRaw('ifnull(srs.ownorgid,os.orgid)=os.orgid')
                    ->whereRaw("'{$rec->wrkdate}' between srs.begdate and ifnull(srs.enddate,'{$rec->wrkdate}')")
                    ->whereRaw("TIMESTAMPDIFF(month, os.begdate, '{$rec->wrkdate}' )/12 between i.min_wrkexp and i.max_wrkexp-0.001")
                    ->select('i.hr_day_rate', 'i.hr_night_rate')
                    ->first();
                //dd($rates);
                if (isset($rates)) {
                    $rec->day_hr_rate = $rates->hr_day_rate;
                    $rec->night_hr_rate = $rates->hr_night_rate;
                }
            }
        }

        //2024-09-23
        $data = new \stdClass();
        $data->opertypes = opertype::lstFor_cached([
            'active' => 1,
        ], 5);

        return view('driver_works.edit', compact('rec', "usrrights", 'data'));
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

        //проверим текущий статус документа
        //$pre_statusid = ($id == -1) ? 0 : driver_work::find($id)->statusid ?? 0;
        $pre_statusid = 0;
        $nxt_statusid = $request->get('statusid') ?? 2;

        if ($pre_statusid == 0) {
            //черновик
            $messages = [
                'machineid.required' => 'Не указана техника/автомобиль',
                'staffid.required' => 'Не указан работник',
                'wrktypeid.required' => 'Укажите тип работ',
                'wrkdate.required' => 'Укажите дату проведения работ',
                'opertypeid.required' => 'Укажите тип деятельности',
                'statusid.required' => 'Укажите статус готовности документа',
                'meter_endqty.required' => 'Укажите показания спидометра на окончание работы',
                'meter_endqty.gte' => 'Показания спидометра на окончание работы должны быть не менее чем на начало работы',
            ];

            $rules = [
                'machineid' => 'required',
                'staffid' => 'required',
                'wrktypeid' => 'required',
//                'opertypeid' => 'required',
                'wrkdate' => 'required',
//                'meter_begqty' => 'required|numeric',
//                'meter_endqty' => 'required|numeric|gte:meter_begqty',
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

        if ($pre_statusid == 0 and $nxt_statusid == 2) {
            //перевод табеля из "Черновик" в "Подготовлено работником для утверждения руководителем"

            $rules = [
                'machineid' => 'required',
                'staffid' => 'required',
                'wrkdate' => 'required',

                'begtime' => 'required',
                'endtime' => 'required',
//                'meter_begqty' => 'required|numeric',
//                'meter_endqty' => 'required|numeric|gte:meter_begqty',
//                'fuel_begqty' => 'required|numeric',
//                'fuel_inpqty' => 'required|numeric',
//                'fuel_endqty' => 'required|numeric',
            ];
            $messages = [
                'machineid.required' => 'Не указана техника/автомобиль',
                'staffid.required' => 'Не указан работник',
                'wrkdate.required' => 'Укажите дату проведения работ',

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
                //В форме должно быть поле ttt
                "ttt" => [
                    function ($attribute, $value, $fail) use ($id, $wrkdate, $machineid) {
                        //
                        $cnt = driver_work::where(['machineid' => $machineid, 'wrkdate' => $wrkdate, 'statusid' => 0])
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

        if (1 == 1) {
            //2025-05-04 проверка, что для этого водителя нет пересекающихся периодов работы

            $staffid = $request->get('staffid');
            $wrkbegdt = date_create($request->get('wrkdate'))->format('Y-m-d') . ' ' . $request->get('begtime');
            $wrkenddt = date_create($request->get('wrkenddate'))->format('Y-m-d') . ' ' . $request->get('endtime');
            //dd($wrkbegdt, $wrkenddt, $staffid, $id);

            $rules = [
                //В форме должно быть поле ttt
                "ttt" => [
                    function ($attribute, $value, $fail) use ($id, $wrkbegdt, $wrkenddt, $staffid) {
                        //
                        $cnt = driver_work::where(['staffid' => $staffid])
                            ->where('id', '<>', $id)
                            ->whereRaw("wrkbegdt < '{$wrkenddt}' and wrkenddt > '{$wrkbegdt}'")
                            ->count();
                        //dd($cnt);
                        if ($cnt > 0) {
                            $fail("У этого водителя есть другой табель с пересекающимся периодом работы!");
                        }
                    },
                ],
            ];
            //dd($rules);
            $request->validate($rules, $messages);
            //dd($rules, $messages);
        }

        if ($pre_statusid == 2 and $nxt_statusid == 0) {
            //перевод табеля из "Подготовлено" в "Черновик"

            $wrkdate = driver_work::find($id)->wrkdate ?? null;
            $days = date_diff(date_create($wrkdate), today())->days;

            $rules = [
                "ttt" => [
                    function ($attribute, $value, $fail) use ($days) {
                        //проверка что "возраст" открываемой записи не более 3 дней ------------
                        if ($days > 3) {
                            $fail("Запрещено изменять табель, созданный более 3 суток назад ({$days})!");
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
            $rec = new driver_work([
                "active" => 0,
                "statusid" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = driver_work::find($id);
            $mess = "Запись обновлена";
        }

        if ($pre_statusid == 0) {


            $rec->wrkdate = $request->get('wrkdate');
            $rec->staffid = $request->get('staffid');
            $rec->machineid = $request->get('machineid');

            //попробуем захватить подходящие записи о рейсах, которые не связаны с другими отчетами
            mchn_raid::where([
                'wrkdate' => $rec->wrkdate,
                'machineid' => $rec->machineid,
                'driverid' => $rec->staffid,
            ])->whereNull('dw_id')
                ->update(['dw_id' => $rec->id]);
            //---------------------------------------------------------------------------------------

            $rec->notes = mb_substr($request->get('notes'), 0, 300);

            //$rec->break_hrs = $request->get('break_hrs', 0);    //Продолжительность перерыва

//            $rec->wrkbegdt = date_create($rec->wrkdate)->format('Y-m-d') . ' ' . $request->get('begtime');
//            $endtime = $request->get('endtime');
//            if ($endtime != '') {
//                $rec->wrkenddt = date_create($rec->wrkdate)->format('Y-m-d') . ' ' . $endtime;
//                $rec->wrkhrs = round((date_create($rec->wrkenddt)->getTimestamp() - date_create($rec->wrkbegdt)->getTimestamp()) / 3600, 1);
//            } else {
//                $rec->wrkenddt = null;
//                $rec->wrkhrs = null;
//            }

            //-------------------------------------------------------
            $rec->wrkbegdt = date_create($rec->wrkdate)->format('Y-m-d') . ' ' . $request->get('begtime');
            $rec->wrkenddt = date_create($request->get('wrkenddate'))->format('Y-m-d') . ' ' . $request->get('endtime');

            $begdt = new DateTime($rec->wrkbegdt);
            $enddt = new DateTime($rec->wrkenddt);
            $diff = $begdt->diff($enddt);

            // Getting the difference between two given DateTime objects
            //dd($diff->d, $diff->h, $diff->i, $diff->d*24 + $diff->h + $diff->i/60  );
            //$rec->stfwrkhrs = round($diff->d * 24 + $diff->h + $diff->i / 60, 1);

            $pre_dt = clone $begdt;
            $cur_dt = clone $begdt;
            $minutes_to_add = 60 - $cur_dt->format('i');
//            dd($minutes_to_add);
            $day_hrs = 0;
            $night_hrs = 0;
            while ($cur_dt < $enddt) {
                //$cur_dt->add(new DateInterval('PT' . $minutes_to_add . 'M'));
                $cur_dt->modify('+' . $minutes_to_add . ' minutes');
                if ($cur_dt > $enddt)
                    $cur_dt = clone $enddt;

                //var_dump($cur_dt, '<hr>');
                $diff = $cur_dt->diff($pre_dt);
                $diff_hrs = $diff->d * 24 + $diff->h + $diff->i / 60;
                //dd($diff, $diff_hrs);
                if ($pre_dt->format('H:i') >= '07:00' and $pre_dt->format('H:i') <= '20:00'
                    and $cur_dt->format('H:i') >= '07:00' and $cur_dt->format('H:i') <= '20:00') {
                    // День
                    $day_hrs += $diff_hrs;
                } else {
                    $night_hrs += $diff_hrs;
                }

                $pre_dt = clone $cur_dt;
                $minutes_to_add = 60;
            }
//            dd($cur_dt, $day_hrs, $night_hrs);
            $day_hrs = round($day_hrs, 2);
            $night_hrs = round($night_hrs, 2);
//            dd($cur_dt, $day_hrs, $night_hrs);

            $rec->wrktypeid = $request->get('wrktypeid');
            $rec->wrktype_notes = $request->get('wrktype_notes');
            $rec->opertypeid = $request->get('opertypeid');

            $rec->wrkplaceid = $request->get('wrkplaceid');
            $rec->wrkplacename = $request->get('wrkplacename');

            $rec->day_hr_rate = $request->get('day_hr_rate');
            $rec->night_hr_rate = $request->get('night_hr_rate');


            // Считаем данные по доп. работам/простоям --------------------
            $aux_worktypeid = $request->get('aux_wrktypeid');
            $aux_dwi_id = $request->get('aux_dwi_id');
            $aux_day_hrs = $request->get('aux_day_hrs');
            $aux_night_hrs = $request->get('aux_night_hrs');
            $aux_hr_day_rate = $request->get('aux_hr_day_rate');
            $aux_hr_night_rate = $request->get('aux_hr_night_rate');
            $aux_aux_sum = $request->get('aux_aux_sum');
            //dd( $aux_day_hrs,  $aux_hr_day_rate);
            //dd($aux_night_hrs, $aux_hr_night_rate);

//            $rec->day_brkhrs = $request->get('day_brkhrs') ?? 0;
//            $rec->night_brkhrs = $request->get('night_brkhrs') ?? 0;

            // Подсчитаем кол-во простоев - по записям, внесенным в разрезе видов доп. деятельности -------
            $day_brkhrs = 0.0;
            $night_brkhrs = 0.0;
            $aux_sum = 0.00;
            foreach ($aux_worktypeid as $i => $itm) {
                $day_brkhrs += 1 * $aux_day_hrs[$i];
                $night_brkhrs += 1 * $aux_night_hrs[$i];

                $salary_sum = $day_brkhrs * $aux_hr_day_rate[$i]
                    + $night_brkhrs * $aux_hr_night_rate[$i]
                    + 1 * $aux_aux_sum[$i];
                $aux_sum += $salary_sum;
            }
            $rec->day_brkhrs = $day_brkhrs;
            $rec->night_brkhrs = $night_brkhrs;
            // Нужно исправить - сейчас "с натяжкой" всю сумму за доп-работу ставим в сумму ремонта.
            //$rec->repair_sum = $aux_sum;
            //2024-01-04 Исправил на breaks_sum
            $rec->breaks_sum = $aux_sum;
            $rec->repair_sum = 0; // для совместимости
            //---------------------------------------------------------------------------------------------

            // Скорректируем кол-во рабочих часов с учетом часов простоя/ремонта/сна
            $rec->day_wrkhrs = $day_hrs - min($day_hrs, $rec->day_brkhrs);
            $rec->night_wrkhrs = $night_hrs - min($night_hrs, $rec->night_brkhrs);
            $rec->day_wrkhrs = round($rec->day_wrkhrs, 2);
            $rec->night_wrkhrs = round($rec->night_wrkhrs, 2);
            //dd( $rec->day_wrkhrs , $rec->night_wrkhrs );

            $rec->hrs_salary = driver_work::calc_hr_salary(
                $rec->wrkdate
                , $rec->staffid
                , $rec->day_wrkhrs
                , $rec->night_wrkhrs
                , $rec->wrktypeid);
            //dd( $rec->hrs_salary);
            //-------------------------------------------------------

            //2024-01-04 - Кажется мешает расчету чуть выше
            //$rec->breaks_sum = $request->get('breaks_sum') ?? 0;

            //$rec->mchnwrkhrs = $request->get('mchnwrkhrs');

            //Получим текущие данные от рейсов:
            if (!is_null($rec->id)) {
                $raid_info = mchn_raid::where('dw_id', $rec->id)
                    ->selectRaw("sum(raid_qty) as qty, sum(raid_qty*raid_salary) as sum")->first();
                $rec->raid_qty = $raid_info->qty;
                $rec->raid_sum = $raid_info->sum;
            } else {
                $rec->raid_qty = 0;
                $rec->raid_sum = 0;
            }

//            $rec->pdt_hrs = $request->get('pdt_hrs');
//            $rec->pdt_cost = $request->get('pdt_cost');
//            $rec->pdt_sum = $rec->pdt_hrs * $rec->pdt_cost;
//
//            $rec->repair_hrs = $request->get('repair_hrs');
//            $rec->repair_cost = $request->get('repair_cost');
//            $rec->repair_sum = $rec->repair_hrs * $rec->repair_cost;

            $rec->salary_sum = $rec->hrs_salary + $rec->raid_sum + $rec->breaks_sum;

            $rec->meter_begqty = $request->get('meter_begqty');
            $rec->meter_endqty = $request->get('meter_endqty');
            if (isset($rec->meter_endqty) and isset($rec->meter_begqty))
                $rec->meter_qty = $rec->meter_endqty - $rec->meter_begqty;
            else
                $rec->meter_qty = null;

            $rec->fuel_begqty = $request->get('fuel_begqty');
            $rec->fuel_inpqty = $request->get('fuel_inpqty', 0);
            $rec->fuel_endqty = $request->get('fuel_endqty');

            if (isset($rec->fuel_begqty) and isset($rec->fuel_inpqty) and isset($rec->fuel_endqty))
                $rec->fuel_spentqty = $rec->fuel_begqty + $rec->fuel_inpqty - $rec->fuel_endqty;
            else
                $rec->fuel_spentqty = null;


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


        } elseif ($pre_statusid == 2) {
            //согласование

//            if ($request->get('statusid') == 4) {
//                //переводим на "Согласовано"
//                $rec->mngr_signed = 1;
//                $rec->mngr_signed_by = $userid;
//                $rec->mngr_signed_at = now();
//
//            } elseif ($request->get('statusid') == 0) {
//                //переводим на "Редактирование"
//                $rec->stf_signed = null;
//                $rec->stf_signed_at = null;
//
//                $rec->mngr_signed = null;
//                $rec->mngr_signed_by = null;
//                $rec->mngr_signed_at = null;
//            }

        }

        $rec->statusid = $request->get('statusid', 0);
        $rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();
//        dd($rec);
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        // Регистрация расчета ЗП сотрудника за месяц
        driver_work::refr_stf_month_chrg_calc(11, $rec->staffid, $rec->wrkdate, $userid);
        if(1==0) {
            // ----------------------------------------------------------------------------------------------
            // Регистрация расчета ЗП

            //Подсчитаем общую сумму ЗП сотрудника за весь месяц
            $int_begdate = date_create($rec->wrkdate)->format('Y-m-01');
            $int_enddate = date_create($rec->wrkdate)->format('Y-m-t');
            $salary_sum = driver_work::where('staffid', $rec->staffid)
                ->wherebetween('wrkdate', [$int_begdate, $int_enddate])
                ->sum('salary_sum');
//dd($salary_sum);
            // Определим - существует ли необходимость привязки начисления этой организации к общей ведомости
            $orgcharge = org_charge::where(['orgid' => $rec->orgstaff->orgid, 'chargetypeid' => 11])->first();

            if (isset($orgcharge)) {

                // Сформируем детали расчета - для сохранения в поле примечания (stf_chrg_calc.notes)
                $staffid = $rec->staffid;
                $sql = "select group_concat(notes separator '; ') notes from (SELECT concat(
        		SUM(day_wrkhrs), ' ч * ', day_hr_rate, ' руб (день)'
                , ' + ', SUM(night_wrkhrs), ' ч * ', night_hr_rate, ' руб (ночь)'
                , ' + ', SUM(breaks_sum), ' руб (простой)'
		        ) as notes
                FROM driver_works as dw
                where staffid={$staffid}
                  and wrkdate between '{$int_begdate}' and '{$int_enddate}'
                  and salary_sum>0
                GROUP BY day_hr_rate, night_hr_rate) a";
                $rslt = DB::select(DB::raw($sql));
                $notes = $rslt[0]->notes ?? '';
                //dd($sql, $notes);


                // Так как привязываем совокупную запись, то берем "общий" идентификатор - "0"
                $stfchrgcalc = stf_chrg_calc::where([
                    'staffid' => $rec->staffid
                    , 'ref_sysobjid' => $this->sysobjid
                    , 'ref_objid' => 0
                    , 'docdate' => $int_begdate
                ])->first();
                if (!isset($stfchrgcalc)) {

                    $stfchrgcalc = new stf_chrg_calc([
                        "staffid" => $rec->staffid,
                        "orgchargeid" => $orgcharge->id,
                        "charge_dir" => $orgcharge->chargetype->dir,
                        "docdate" => $int_begdate,
                        "forbegdate" => $int_begdate,
                        "forenddate" => $int_enddate,
                        "created_by" => $userid,
                        "created_at" => now(),
                        "ref_sysobjid" => $this->sysobjid,
                        "ref_objid" => 0,
                    ]);
                }
                $stfchrgcalc->staffid = $rec->staffid;
                $stfchrgcalc->charge_sum = $salary_sum;
                $stfchrgcalc->notes = $notes;

                $stfchrgcalc->updated_by = $userid;
                $stfchrgcalc->updated_at = now();
                //dd($stfchrgcalc);
                $stfchrgcalc->save();
            }
            //---------------------------------------------------------------------------------------
        }

        //-------------------------------------------------------
        // Сохраним данные о простоях/ремонтах/доп.работах

        foreach ($aux_worktypeid as $i => $itm) {

            $hr_sum = 1 * $aux_day_hrs[$i] * $aux_hr_day_rate[$i]
                + 1 * $aux_night_hrs[$i] * $aux_hr_night_rate[$i];

            $brk_sum = $hr_sum + 1 * $aux_aux_sum[$i];

            dw_break::addOrUpdate(
                ['dw_id' => $rec->id, 'wrktypeid' => $aux_worktypeid[$i]],
                ['dw_id' => $rec->id, 'wrktypeid' => $aux_worktypeid[$i]
                    , 'begdt' => null
                    , 'enddt' => null
                    , 'day_hrs' => $aux_day_hrs[$i] * 1
                    , 'night_hrs' => $aux_night_hrs[$i] * 1
                    , 'hr_day_rate' => $aux_hr_day_rate[$i] * 1
                    , 'hr_night_rate' => $aux_hr_night_rate[$i] * 1
                    , 'hr_sum' => $hr_sum
                    , 'aux_sum' => $aux_aux_sum[$i] * 1
                    , 'brk_sum' => $brk_sum
                    , 'updated_by' => $userid
                    , 'updated_at' => now()
                ]);
        }
        //-------------------------------------------------------


        if ($id == -1 or $rec->statusid <> $pre_statusid)
            return redirect(route($this->sysobjcode . '.edit', $rec->id));
        else
            return redirect(route($this->sysobjcode . '.index'));
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

        $res = driver_work::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route($this->sysobjcode . '.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            // Регистрация расчета ЗП сотрудника за месяц
            driver_work::refr_stf_month_chrg_calc(11, $res->obj['staffid'], $res->obj['wrkdate'], \Auth::user()->id);
            //dd($res->obj);

            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route($this->sysobjcode . '.index', ['machineid' => $res->obj['machineid'] ?? 0, 'parid' => $res->obj['planid'] ?? 0]);
            connectify('success', ($res->obj['name'] ?? '-'), 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    public
    function printform1(Request $request, $id)
    {
        //печать в форме протокола
        $rec = driver_work::from('driver_works as p')
            ->select('p.*')->where('id', $id)->first();

        $rec->staff = driver_work_staff::lstAllFordriver_work($id);

        $rec->items = driver_work_item::from('driver_work_items as i')
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

    public function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = driver_work::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        $document['tags'] = objtag::lstTags($this->sysobjid, $id);
        //dd($document);

        $template_js = [
            'driver_work' => $document,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

}
