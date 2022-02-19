<?php

namespace App\Http\Controllers;

use App\contract_org;
use App\driver_work;
use App\mchn_raid;
use App\machine;
use App\mchn_opertype;
use App\mr_oper;
use App\obj_finoper;
use App\obj_staff;
use App\objlog;
use App\objtag;
use App\opertype;
use App\org;
use App\org_place;
use App\orgstaff;
use App\refitem;
use App\sysobj;
use App\sysobj_lockdate;
use App\place;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\unittype;
use App\User;
use App\user_template;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

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
        $usrrights['set_lockdate'] = false;

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
        $usrrights['manager'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.manager');
        $usrrights['set_lockdate'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.set_lockdate');;

        if ($recid > 0) {
            //для существующих записей проверим открытость периода
            if (mchn_raid::isLocked($recid)) {
                $usrrights['save'] = false;
                $usrrights['delete'] = false;
                $usrrights['admindelete'] = false;
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
            , 's_ri_name' => ''
            , 's_machineid' => ''
            , 's_driverid' => ''
            , 's_paytypeid' => ''
            , 's_date' => ''
            , 's_load_placeid' => ''
            , 's_unload_placeid' => ''
            , 's_suporgid' => ''
            , 's_orgid' => ''
            , 's_disp_staffid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_ri_name') {
                    $sc = $sc . " and exists(select 1 from mr_opers as mro
                        join refitems as ri on ri.id=mro.refitmid
                        where mro.mr_id=mr.id and ri.name like '%" . mb_strtoupper($val) . "%')";

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
                    //$sc = $sc . " and mr.load_placeid = {$val}";
                    $sc = $sc . " and exists(select 1 from mr_opers as mro where mro.mr_id=mr.id and mro.sup_placeid = {$val})";

                } elseif ($item == 's_unload_placeid') {
//                    $sc = $sc . " and mr.unload_placeid = {$val}";
                    $sc = $sc . " and exists(select 1 from mr_opers as mro where mro.mr_id=mr.id and mro.org_placeid = {$val})";

                } elseif ($item == 's_suporgid') {
                    //Поставщик в операциях покупки (от ГК)
//                    $sc = $sc . " and exists(select 1 from mr_opers as mro where mro.mr_id=mr.id and mro.suporgid = {$val} and mro.sale_dir=-1)";
                    $sc .= " and mro.suporgid={$val}";

                } elseif ($item == 's_orgid') {
                    //Заказчик в операциях продажи (от ГК)
                    //$sc = $sc . " and exists(select 1 from mr_opers as mro where mro.mr_id=mr.id and mro.orgid = {$val} and mro.sale_dir=1)";
                    $sc .= " and mro.orgid={$val}";

                } elseif ($item == 's_paytypeid') {
                    //$sc = $sc . " and mr.paytypeid = {$val}";
                    //$sc = $sc . " and exists(select 1 from mr_opers as mro where mro.mr_id=mr.id and mro.paytypeid = {$val})";
                    $sc .= " and mro.paytypeid = {$val}";

                } elseif ($item == 's_disp_staffid') {
                    $sc = $sc . " and mr.disp_staffid = {$val}";

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
            ->join('opertypes as ot', function ($join) {
                $join->on('ot.id', '=', 'mr.opertypeid');
            })
            ->join('orgstaff as os', function ($join) {
                $join->on('os.id', '=', 'mr.driverid');
            })
            ->join('machines as m', function ($join) {
                $join->on('m.id', '=', 'mr.machineid');
            })
            ->join('mr_opers as mro', function ($join) {
                $join->on('mr.id', '=', 'mro.mr_id')
                    ->where('mro.sale_dir', '<>', 0);
            })
            ->leftjoin('orgstaff as ds', function ($join) {
                $join->on('ds.id', '=', 'mro.disp_staffid');
            })
            ->join('refitems as ri', function ($join) {
                $join->on('ri.id', '=', 'mro.refitmid');
            })
            ->join('orgs as s_o', function ($join) {
                $join->on('s_o.id', '=', 'mro.suporgid');
            })
            ->join('orgs as o', function ($join) {
                $join->on('o.id', '=', 'mro.orgid');
            })
            ->whereraw($sc)
            ->select('mr.id', 'mr.wrkdate'
                , 'mro.sale_dir', 'mro.refitmid', 'mro.itm_qty', 'mro.itm_price', 'mro.itm_sum', 'mro.paytypeid'
                , 'mro.raid_qty', 'mro.sup_placename', 'mro.org_placename', 'mro.active'
//                , db::raw("TIME_FORMAT(mr.wrkbegdt, '%H:%i') as beg_hm")
//                , db::raw("TIME_FORMAT(mr.wrkenddt, '%H:%i') as end_hm")
                , 'os.lname as staff_name'
                , 'ds.lname as disp_name'
                , db::raw("concat(m.regnum,' ',m.name) as machine_name")
                , 'mr.opertypeid'
                , 'ot.name as opertype_name'
                , 's_o.name as suporg_name'
                , 'o.name as org_name'
                , 'ri.name as refitem_name'
                , 'ri.unit as refitem_unit'
            );


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderBy('mr.wrkdate', 'desc')
            ->orderBy('ot.name', 'asc')
            ->orderby('mr.id');
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
            ['in_mchn_raids' => 1,], ['m.id', db::raw("concat(m.regnum,' - ',m.name) as name")]
        )->pluck('name', 'id')->toArray();

        $data->drivers = orgstaff::lstFor([
            'driver_in_mchn_raids' => 1,
        ]);

        $data->refitems = refitem::lstFor_cached([
            'in_mchn_raids' => 1,
        ], 5);

        $data->load_places = org_place::lstFor_cached([
            'loadplace_in_mchn_raids' => 1,
        ], 5);

        $data->unload_places = place::lstFor_cached([
            'unloadplace_in_mchn_raids' => 1,
        ], 5);

        $data->suporgs = org::lstFor_cached([
            //'in_mchn_raids_orgid' => 1,
            //'in_mr_opers_orgid' => 1,
            'in_mr_opers_suporgid' => 1,
        ], 5);

        $data->orgs = org::lstFor_cached([
            //'in_mchn_raids_orgid' => 1,
            //'in_mr_opers_orgid' => 1,
            'in_mr_opers_orgid_sale' => 1,
        ], 5);

        $data->dispatchers = orgstaff::lstFor_cached([
            'dispatcher_in_mr_opers' => 1,
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

    public function index0(Request $request)
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
            , 's_ri_name' => ''
            , 's_machineid' => ''
            , 's_driverid' => ''
            , 's_paytypeid' => ''
            , 's_date' => ''
            , 's_load_placeid' => ''
            , 's_unload_placeid' => ''
            , 's_orgid' => ''
            , 's_disp_staffid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";


        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_ri_name') {
                    $sc = $sc . " and exists(select 1 from mr_opers as mro
                        join refitems as ri on ri.id=mro.refitmid
                        where mro.mr_id=mr.id and ri.name like '%" . mb_strtoupper($val) . "%')";

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
                    //$sc = $sc . " and mr.load_placeid = {$val}";
                    $sc = $sc . " and exists(select 1 from mr_opers as mro where mro.mr_id=mr.id and mro.sup_placeid = {$val})";

                } elseif ($item == 's_unload_placeid') {
//                    $sc = $sc . " and mr.unload_placeid = {$val}";
                    $sc = $sc . " and exists(select 1 from mr_opers as mro where mro.mr_id=mr.id and mro.org_placeid = {$val})";

                } elseif ($item == 's_orgid') {
                    //Заказчик в операциях продажи (от ГК)
                    //$sc = $sc . " and mr.orgid = {$val}";
                    $sc = $sc . " and exists(select 1 from mr_opers as mro where mro.mr_id=mr.id and mro.orgid = {$val} and mro.sale_dir=1)";

                } elseif ($item == 's_paytypeid') {
                    //$sc = $sc . " and mr.paytypeid = {$val}";
                    $sc = $sc . " and exists(select 1 from mr_opers as mro where mro.mr_id=mr.id and mro.paytypeid = {$val})";

                } elseif ($item == 's_disp_staffid') {
                    $sc = $sc . " and mr.disp_staffid = {$val}";

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
            ->join('opertypes as ot', function ($join) {
                $join->on('ot.id', '=', 'mr.opertypeid');
            })
            ->join('orgstaff as os', function ($join) {
                $join->on('os.id', '=', 'mr.driverid');
            })
            ->join('machines as m', function ($join) {
                $join->on('m.id', '=', 'mr.machineid');
            })
            ->leftjoin('refitems as l_ri', function ($join) {
                $join->on('l_ri.id', '=', 'mr.load_refitmid');
            })
            ->leftjoin('refitems as u_ri', function ($join) {
                $join->on('u_ri.id', '=', 'mr.unload_refitmid');
            })
            ->leftjoin('org_places as l_op', function ($join) {
                $join->on('l_op.id', '=', 'mr.load_placeid');
            })
            ->leftjoin('orgstaff as ds', function ($join) {
                $join->on('ds.id', '=', 'mr.disp_staffid');
            })
            ->whereraw($sc)
            ->select('mr.*'
                , db::raw("TIME_FORMAT(mr.wrkbegdt, '%H:%i') as beg_hm")
                , db::raw("TIME_FORMAT(mr.wrkenddt, '%H:%i') as end_hm")
                , 'os.lname as staff_name'
                , 'ds.lname as disp_name'
                , db::raw("concat(m.regnum,' ',m.name) as machine_name")
                , 'mr.opertypeid'
                , 'ot.name as opertype_name'
                , 'l_op.name as load_place_name'
                , 'l_ri.name as load_refitem_name'
                , 'l_ri.unit as load_refitem_unit'
                , 'u_ri.name as unload_refitem_name'
                , 'u_ri.unit as unload_refitem_unit'
                , db::raw("(select group_concat( o.name SEPARATOR '; ')
                            from mr_opers as mro
                            join orgs as o on o.id=mro.orgid
                            where mro.mr_id=mr.id and mro.sale_dir=1
                            order by mro.id
                            ) as orgs")
            );


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderBy('mr.wrkdate', 'desc')
            ->orderBy('ot.name', 'asc')
            ->orderby('mr.id');
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
            ['in_mchn_raids' => 1,], ['m.id', db::raw("concat(m.regnum,' - ',m.name) as name")]
        )->pluck('name', 'id')->toArray();

        $data->drivers = orgstaff::lstFor([
            'driver_in_mchn_raids' => 1,
        ]);

        $data->refitems = refitem::lstFor_cached([
            'in_mchn_raids' => 1,
        ], 5);

        $data->load_places = org_place::lstFor_cached([
            'loadplace_in_mchn_raids' => 1,
        ], 5);

        $data->unload_places = place::lstFor_cached([
            'unloadplace_in_mchn_raids' => 1,
        ], 5);

        $data->orgs = org::lstFor_cached([
            //'in_mchn_raids_orgid' => 1,
            //'in_mr_opers_orgid' => 1,
            'in_mr_opers_orgid_sale' => 1,
        ], 5);

        $data->dispatchers = orgstaff::lstFor_cached([
            'dispatcher_in_mchn_raids' => 1,
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
                //$newData['disp_staffid'] = '';
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

        $rec->ownorgs = org::lstFor(['flagtypeid' => 12, 'active_or_current' => 1]);

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

        $rec->opertypes = opertype::lstFor(['in_machines' => 1]);

        if ($rec->id <> -1) {
            //для не новых записей

            $rec->opers = mr_oper::from('mr_opers as mro', 'mro.mr_id', $rec->id)
                ->join('refitems as ri', 'ri.id', 'mro.refitmid')
                ->leftjoin('unittypes as ut', 'ut.id', 'ri.unittypeid')
                ->join('orgs as so', 'so.id', 'mro.suporgid')
                ->leftjoin('org_places as sp', 'sp.id', 'mro.sup_placeid')
                ->join('orgs as o', 'o.id', 'mro.orgid')
                ->leftjoin('org_places as p', 'p.id', 'mro.org_placeid')
                ->where('mr_id', $rec->id)
                ->select('mro.*'
                    , 'ri.name as itm_name'
                    , 'ut.name as unittype_name'
                    , 'so.name as sup_name'
                    , 'sp.name as sup_place_name'
                    , 'o.name as org_name'
                    , 'p.name as org_place_name'
                )
                ->get();
            //dd($rec->opers);

            //пересчет фин транзакций
            mchn_raid::rfr_finopers($rec->id);

            if ($userid == 12)
                $rec->finopers = obj_finoper::from('obj_finopers as fo')
                    ->join('orgs as s_o', 's_o.id', 'fo.srcorgid')
                    ->join('orgs as t_o', 't_o.id', 'fo.tgtorgid')
                    ->leftjoin('opertypes as ot', 'ot.id', 'fo.opertypeid')
                    ->leftjoin('contracts as c', 'c.id', 'fo.contractid')
                    ->where('sysobjid', 1107)   //по дочерним - mr_opers
                    ->whereRaw(" exists(select 1 from mr_opers as mro where mro.mr_id={$rec->id} and mro.id=fo.objid)")
                    ->select('fo.*'
                        , 's_o.name as srcorg_name'
                        , 't_o.name as tgtorg_name'
                        , 'ot.name as opertype_name'
                    )
                    ->orderBy('fo.operdate')
                    ->get();

        }

        $rec->load_places = org_place::lstFor(['orgid' => $rec->suporgid]);
        $rec->unload_places = org_place::lstFor(['orgid' => $rec->orgid]);
        //dd($rec->load_places);

        $rec->saledirs = mr_oper::saledirs();

        //$usrrights['delete'] = ($usrrights['delete'] and ($rec->created_by == $userid or $usrrights['manager']) and $rec->statusid == 0);
        $usrrights['delete'] = ($usrrights['delete'] and ($rec->id <> -1 and count($rec->opers) == 0) and $rec->statusid == 0);
        $usrrights['save'] = ($usrrights['save'] and ($rec->created_by == $userid or $usrrights['manager']) and $rec->statusid == 0);
        $usrrights['edit'] = ($usrrights['save'] and ($rec->created_by == $userid or $usrrights['manager']) and $rec->statusid == 0);
        //можно ли изменить wrkDate, Machineid, Driverid (DMD)
        $usrrights['edit_dmd'] = ($usrrights['edit'] and (!isset($rec->dw_id)));
        $usrrights['change_status'] = ($usrrights['save'] and ($rec->created_by == $userid or $usrrights['manager']));

        //корректировка прав с учетом статуса -------------------------------------------

        if ($rec->statusid != 0) {
            $usrrights['create'] = $usrrights['delete'] = false;
        }
        //-------------------------------------------------------------------------------

        if ($usrrights['save']) {
            //установим минимально-допустимую дату для wrkdate
            $rec->wrkdate_min = mchn_raid::min_wrkdate();
        }

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

        $usrrights = $this->setInterfaceRight($id);
        if (!($usrrights['save']))
            return redirect()->back()->with('error', 'У вас нет права на изменение этих данных!');

        //проверим текущий статус документа
        $statusid = ($id == -1) ? 0 : mchn_raid::find($id)->statusid ?? 0;

        if ($statusid == 0) {
            //черновик
            $messages = [
                'opertypeid.required' => 'Укажите тип работ',
                'machineid.required' => 'Не указан автомобиль',
                'driverid.required' => 'Не указан водитель',
                'wrkdate.required' => 'Укажите дату проведения работ',
                'statusid.required' => 'Укажите статус готовности документа',
            ];

            $rules = [
                'opertypeid' => 'required',
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
                //$driver_work = driver_work::find($dw_id);
                $driver_work = null;

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

            $rec->opertypeid = $request->get('opertypeid');
            $rec->wrkdate = $request->get('wrkdate');
            $rec->driverid = $request->get('driverid');
            //$rec->drivername = $request->get('drivername');
            $rec->drivername = $rec->driver->name;
            $rec->machineid = $request->get('machineid');
            $rec->mot_id = $request->get('mot_id');

            //если до сих пор рейс не привязан к отчету о работе
            $rec->dw_id = null;
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


            $rec->raid_salary = $request->get('raid_salary');
            $rec->notes = mb_substr($request->get('notes'), 0, 300);

            if (1 == 0) {
                //2022-02-08 Оставляем в mchn_raids минимум полей

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

                $rec->load_refitmid = $request->get('load_refitmid');
                $rec->unload_refitmid = $request->get('unload_refitmid');
                //$rec->cargo_name = mb_substr($request->get('cargo_name'), 0, 60);

                $rec->suporgid = $request->get('suporgid');
                $rec->load_ownorgid = $request->get('load_ownorgid');
                $rec->load_placeid = $request->get('load_placeid');
                //$rec->load_placename = $request->get('load_placename');
                //$rec->load_placename = $rec->load_place->name;

                $rec->load_qty = $request->get('load_qty');
                $rec->qty_unittypeid = $request->get('qty_unittypeid');
                $rec->qty_unit = unittype::find($rec->qty_unittypeid)->name ?? '';
                $rec->load_price = $request->get('load_price');
                $rec->load_sum = $rec->load_qty * $rec->load_price;

                $rec->unload_ownorgid = $request->get('unload_ownorgid');
                $rec->unload_placeid = $request->get('unload_placeid');
                $rec->unload_placename = $request->get('unload_placename');

                $rec->unload_qty = $request->get('unload_qty');
                $rec->unload_price = $request->get('unload_price');
                $rec->unload_sum = $rec->unload_qty * $rec->unload_price;

                $rec->ownorg_sum = $request->get('ownorg_sum');

                $rec->raid_qty = $request->get('raid_qty');
                $rec->orgid = $request->get('orgid');
                $rec->org_name = $request->get('org_name');
                $rec->paytypeid = $request->get('paytypeid');

                $rec->disp_staffid = $request->get('disp_staffid');

            }


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

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);


        //для новой записи возьмем значения из шаблона ---------------------------------------------------
        if ($id == -1) {

            $tmplt = user_template::getTemplate($userid, $this->sysobjid);
            if (isset($tmplt->mchn_raid)) {

                if (isset($tmplt->mr_opers)) {
                    //извлечем данные об операциях
                    $opers = $tmplt->mr_opers;
                    foreach ($opers as $oper) {
                        mr_oper::add(['mr_id' => $rec->id], (array)$oper);
                    }
                }

            }
        }//----------------------------------------------------------------------------------------------


        //Выполним действия после обновления записи ---------------------------------------------
        mchn_raid::on_update($rec);
        //---------------------------------------------------------------------------------------

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

        $usrrights = $this->setInterfaceRight($id);
        if (!($usrrights['delete'] or $usrrights['admindelete']))
            return redirect()->back()->with('error', 'У вас нет права на удаление этих данных!');

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

            //Выполним действия после удаления записи -----------------------------------------------
            mchn_raid::on_delete($res->rec);
            //---------------------------------------------------------------------------------------

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

        $opers = mr_oper::where(['mr_id' => $rec->id])->get()
            ->makeHidden(['id', 'mr_id', 'created_at', 'updated_at', 'created_by', 'updated_by'])->toArray();
        //уберем пустые элементы в каждой записи массива
        foreach ($opers as $elm) {
            $elm = array_filter($elm);
        }
        //dd($document, $opers);

        $template_js = [
            'mchn_raid' => $document,
            'mr_opers' => $opers,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

    public function clone($id)
    {

        if (!isset($id))
            return redirect()->back()->with('error', 'Не задана исходная запись!');


        $userid = \Auth::user()->id;

        $rslt = mchn_raid::clone($id);
        if ($rslt->err > 0)
            return redirect()->back()->with(['error' => $rslt->msg]);

        //$document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        //$document['tags'] = objtag::lstTags($this->sysobjid, $id);
        //dd($document);

        return redirect(route($this->sysobjcode . '.edit', $rslt->obj['id']))
            ->with(['success' => 'Вы находитесь в созданной копии']);

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

    static public function rfr_all_finopers()
    {
        //2022-01-27 SNS. Пересчет фин-результата для всех записей mchn_raids

        if (\Auth::user()->id <> 12)
            return false;

        try {

            foreach (mchn_raid::select('id')->get() as $rec) {
                mchn_raid::rfr_finopers($rec->id);
            }

        } catch (\Exception $e) {
            Log::error('mchn_raid::rfr_all_finopers:' . $e->getMessage());
            return redirect(route('mchn_raids.index'))
                ->with(['error' => 'Ошибка пересчета финансовых операций по заездам: ' . $e->getMessage()]);
        }
        return redirect(route('mchn_raids.index'))->with(['success' => 'Пересчитаны финансовые операции по заездам!']);
    }

    static public function rfr_all_mchnraids()
    {
        //2022-02-08 SNS. Пересчет вспомогательных полей mchn_raids по данным mr_opers

        if (\Auth::user()->id <> 12)
            return false;

        try {

            foreach (mr_oper::select('id')->get() as $rec) {

                if ($rec->sale_dir <> 0) {

                    $raid = $rec->mchn_raid;

                    if ($rec->sale_dir == -1) {
                        //покупка
                        $raid->suporgid = $rec->suporgid;
                        $raid->load_placeid = $rec->sup_placeid;
                        $raid->load_placename = $rec->sup_place->name ?? $rec->sup_placename;
                        $raid->load_refitmid = $rec->refitmid;
                        $raid->load_qty = $rec->itm_qty;
                        $raid->load_price = $rec->itm_price;
                        $raid->load_sum = $rec->load_sum;
                        $raid->load_ownorgid = $rec->orgid;
                        $raid->save();

                    } elseif ($rec->sale_dir == +1) {
                        //продажа
                        $raid->orgid = $rec->orgid;
                        $raid->org_name = $rec->org->name;
                        $raid->unload_placeid = $rec->org_placeid;
                        $raid->unload_placename = $rec->org_place->name ?? $rec->org_placename;
                        $raid->unload_ownorgid = $rec->suporgid;

                        $raid->unload_refitmid = $rec->refitmid;
                        $raid->unload_qty = $rec->itm_qty;
                        $raid->unload_price = $rec->itm_price;
                        $raid->unload_sum = $rec->load_sum;

                        $raid->paytypeid = $rec->paytypeid;
                        $raid->save();
                    }

                }
            }

        } catch (\Exception $e) {
            Log::error('mchn_raid::rfr_all_mchnraids:' . $e->getMessage());
            return redirect(route('mchn_raids.index'))
                ->with(['error' => 'Ошибка пересчета индикаторных полей рейсов: ' . $e->getMessage()]);
        }
        return redirect(route('mchn_raids.index'))->with(['success' => 'Пересчитаны индикаторные поля заездам!']);
    }

}
