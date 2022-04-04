<?php

namespace App\Http\Controllers;

use App\machine;
use App\mchn_opertype;
use App\mchnrqst;
use App\org;
use App\mchntype;
use App\usrsysright;
use App\group;
use App\objflag;
use App\objlog;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Traits\Result;

class MachineController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 482;
        $this->sysobjcode = 'machines';
        $this->objcode = $this->sysobjcode;
    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create');
        $usrrights['load'] = usrsysright::isUserHasRightByCode_cached($userid, 'admin-global');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['mchn_opertypes.create'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
            $usrrights['mchn_opertypes.create'] = $usrrights['save'];
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


        session([$this->objcode . '_pageno' => $request->page ?? 1]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_name' => ''
            , 's_type' => ''
            , 's_regnum' => ''
            , 's_orgid' => ''
            , 's_flagtypeid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and m.name like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_regnum') {
                    $sc = $sc . " and m.regnum like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_type') {
                    $sc = $sc . " and m.mchntypeid = '{$val}'";

                } elseif ($item == 's_orgid') {
                    $sc = $sc . " and m.orgid = '{$val}'";

                } elseif ($item == 's_flagtypeid') {
                    $sc .= " and exists(select 1 from objflags f where f.sysobjid={$this->sysobjid}
                        and f.objid=m.id and f.flagtypeid={$val})";
                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------


        $recs = machine::from('machines as m')
            ->leftJoin('orgs as o', function ($j) {
                $j->on('o.id', 'm.orgid');
            })
            ->leftJoin('mchntypes as mt', function ($j) {
                $j->on('mt.id', 'm.mchntypeid');
            })
            ->whereraw($sc)
            ->select('m.id', 'm.name', 'm.active', 'm.regnum', 'm.orgid', 'o.name as org_name', 'mt.name as typename');

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->objcode . '.index');

        $recs = $recs->orderBy('org_name', 'asc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('m.name', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->orgs = org::lstFor(['in_machines' => 1]);

        $objgroups = group::lstOrgGroups_cache();

        $usedflags = objflag::lstUsedFlagsForSysObj_cache(482);

        $usedmchntypes = mchntype::usedTypes();


        //dd($usrrights);

        return view('machines.index', compact(
            'recs', 'rec0', 'data'
            , 'objgroups', 'usedflags', 'usedmchntypes'
            , 'search_params', 'sort_params'
            , 'usrrights'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return $this->edit(-1);
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $machine = machine::find($id);
        return view('machines.show', compact('machine'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи
            $machine = new machine([
                'id' => -1,
                'active' => 1,
                'created_by' => \Auth::user()->id,
            ]);
            $machine->mchntypes = mchntype::lstTypes();

            //$usrrights = $this->setInterfaceRight($machine->id);

            //return view('machines.edit', compact(['machine', 'usrrights']));

        } else
            $machine = machine::findOrFail($id);

        //$machine->fueltypes = machine::fueltypes();
        $machine->fueltypes = machine::$ft;

        $machine->mchntypes = mchntype::lstTypes();

        //кандидаты организаций-владельцев техники. Отберем по признаку "141-Транспорт", или та организация которая указана сейчас
        $machine->ownorgs = org::lstFor(['flagtypeid_or_id' => [141, $machine->orgid]]);

        $machine->prices = machine::activePrices($id);

        $machine->controrgs = machine::contrOrgs($id);

        $machine->opertypes = mchn_opertype::getFor([
            'machineid' => $machine->id,
        ], [
            'mot.id', 'mot.name', 'mot.active', 'mot.hour_work_cost', 'mot.hour_fuel_cost'
        ]);

//dd($machine->controrgs);
        $usrrights = $this->setInterfaceRight($id);

        $auxinfo = machine::AuxInfo($machine);

        for ($x = 0; $x <= count($auxinfo) - 1; $x++) {
            if ($auxinfo[$x]["reccount"] > 0 and $usrrights['delete'])
                $usrrights['delete'] = false;
        }

        $ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);


        return view($this->sysobjcode . '.edit', compact('machine', 'auxinfo', "ObjFlags", "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $messages = [
            'name.required' => 'Укажите название техники',
            'mchntypeid.required' => 'Укажите тип техники',
            'regnum.required' => 'Укажите государственный регистрационный номер',
            'orgid.required' => 'Укажите организацию - владельца техники',
        ];

        $rules = [
            "name" => "required",
            "mchntypeid" => "required",
            "regnum" => "required",
            "orgid" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new machine([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
        } else {
            $rec = machine::find($id);
        }
        $rec->name = $request->get('name');
        $rec->regnum = $request->get('regnum');
        $rec->mchntypeid = $request->get('mchntypeid');
        $rec->fueltypeid = $request->get('fueltypeid');
        $rec->descript = mb_substr($request->get('descript'), 0, 300);
        $rec->orgid = $request->get('orgid');
        $rec->fuelper100km = $request->get('fuelper100km');
        $rec->fuelper1hour = $request->get('fuelper1hour');
//        $rec->hour_work_cost = $request->get('hour_work_cost');
//        $rec->hour_fuel_cost = $request->get('hour_fuel_cost');
        $rec->active = $request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        //соберем строку с измененными полями -------------------------------------------------------------------
        $diffs = $this->field_diff_list($rec, ['id', 'created_by', 'updated_by', 'created_at', 'updated_at']);
        if ($diffs === '')
            $msg_simple = $rslt_msg = "Запись пересохранена без изменений";
        else {
            $msg_simple = 'Запись ' . (($id == -1) ? 'создана' : 'изменена');
            $rslt_msg = $msg_simple . ': ' . $diffs;
        }
        //-------------------------------------------------------------------------------------------------------

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $rslt_msg, 5);
        connectify('success', $rec->name, $msg_simple);

//        $retURL = $request->get('retURL') ?? route($this->objcode . '.index')
//            . '?page=' . session($this->objcode . '_pageno') . '#' . $rec->id;

        if ($id == -1) {
            return redirect(route($this->objcode . '.edit', $rec->id));
        } else {
            $pageno = session($this->objcode . '_pageno');
            return redirect(route($this->objcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = machine::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('machines.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'], $res->msg);
        } else {
            $sd['success'] = 'Запись о проекте (' . $id . ': '
                . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $pageno = session($this->objcode . '_pageno');
            $route = route($this->objcode . '.index') . '?page=' . $pageno;
            //connectify('success', $res->obj['name'], 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }

    /**
     * Список будущих заввок на технику
     */
    public function mchn_rqsts($id)
    {
        $machine = machine::findOrFail($id);

        $recs = mchnrqst::from('mchnrqsts as mr')
            ->Join('mchnrqsttypes as mrt', function ($j) {
                $j->on('mrt.id', '=', 'mr.rqsttypeid');
            })
            ->leftJoin('users as u', function ($j) {
                $j->on('u.id', '=', 'mr.inituserid');
            })
            ->where('mr.asgnmachineid', $machine->id)
            ->whereRaw('now()<mr.plnenddt')
            ->select('mr.id', 'mrt.name as rqsttypename', 'mr.tgt_addr', 'mr.descript', 'mr.plnbegdt', 'mr.plnenddt'
                , 'mr.inituserid', 'mr.active'
                , DB::raw('timediff(mr.plnenddt, mr.plnbegdt) as duration')
                , 'u.name as initusername'
            )
            //->orderby('evnttypename')
            ->orderby('plnbegdt')
            ->get();

        return view('machines.machine_rqsts', compact('machine', 'recs'));
    }


    static public function list_for(Request $request)
    {
        //2021-04-05 SNS. Список для select-ов {id,name}

        $result = "";
        try {

            $list = machine::lstFor([
                's_name' => $request->s_name,
                's_regnum' => $request->s_regnum,
                'mchntypeid' => $request->mchntypeid,
                'orgid' => $request->orgid,
                'active' => $request->active,
            ]);

            $result = array('list' => $list);

        } catch (\Exception $e) {
            Log::error('machine::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

    static public function list_for_ac(Request $request)
    {
        //2021-04-05 SNS. Для автокомплита

        $result = "";
        try {

            $list = machine::getFor([
                's_name' => $request->s_name,
                's_regnum' => $request->s_regnum,
                'mchntypeid' => $request->mchntypeid,
                'opertypeid' => $request->opertypeid,
                'orgid' => $request->orgid,
                'active' => $request->active ?? 1,
            ],
                ['m.id', 'm.name', 'm.regnum', 'm.orgid', 'o.name as orgname'
                , db::raw("(select count(*) from objflags f where f.sysobjid=111 and f.flagtypeid=12 and f.objid=m.orgid) as in_gk")
                ]);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('machine::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }


    public function load()
    {
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight(-1);

        $rec = new \stdClass();

        return view($this->sysobjcode . '.load', compact('rec', "usrrights"));
    }


    public function import(Request $request)
    {
        //Импорт без сохранения файла на диск. Только обработка

        $messages = [
            'doc.required' => 'Не указан файл с данными',
        ];

        $rules = [
            "doc" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        //$returl = $request->get('retroute');

        $usrrights = $this->setInterfaceRight(-1);
        $result = new Result();
        $rec = new \stdClass();

        $rec->extsysid = 9;   // ? М.б. использовать для связывания по кодам во внешней системе
        //dd($rec);

        if ($request->hasfile('doc')) {

            $file = $request->doc;

            $filesize = $file->getSize();
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            //dd($name, $extension, $filesize);

            if (1 == 1)
                $rec = machine::import_001($file, $rec);
            else {
                $result->err = 1;
                $result->msg = 'Не определена процедура импорта!';
            }
            //--------------------------------------------------------------------------------
            //dd($result->msg);


        } else {
            $result->err = 1;
            $result->msg = 'Файл с данными не загружен!';
        }

        return view($this->sysobjcode . '.load', compact('rec', "usrrights"));
    }

}
