<?php

namespace App\Http\Controllers;

use App\fuelcard;
use App\group;
use App\machine;
use App\objflag;
use App\objlog;
use App\org;
use App\sysobj;
use App\Traits\Result;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class FuelcardController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 561;
        $this->sysobjcode = 'fuelcards';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }


    protected function setInterfaceRight($id)
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

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
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
            , 's_num' => ''
            , 's_orgid' => ''
            , 's_suporgid' => ''
            , 's_ref_machineid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and fc.name like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_num') {
                    $sc = $sc . " and fc.num like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_orgid') {
                    $sc = $sc . " and fc.orgid = '{$val}'";

                } elseif ($item == 's_suporgid') {
                    $sc = $sc . " and fc.suporgid = '{$val}'";

                } elseif ($item == 's_ref_machineid') {
                    $sc = $sc . " and fc.ref_machineid = '{$val}'";

                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------


        $recs = fuelcard::from('fuelcards as fc')
            ->leftJoin('orgs as o', function ($j) {
                $j->on('o.id', 'fc.orgid');
            })
            ->leftJoin('orgs as so', function ($j) {
                $j->on('so.id', 'fc.suporgid');
            })
            ->leftJoin('machines as m', function ($j) {
                $j->on('m.id', 'fc.ref_machineid');
            })
            ->leftJoin('orgs as mo', function ($j) {
                $j->on('mo.id', 'm.orgid');
            })
            ->whereraw($sc)
            ->select('fc.id', 'fc.num', 'fc.name', 'fc.active', 'fc.orgid', 'o.name as org_name'
                , 'so.name as suporg_name'
                , db::raw("concat(m.regnum, ' (', m.name, ', ', mo.name, ')' ) as ref_machine_name") );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->objcode . '.index');

        $recs = $recs->orderBy('org_name', 'asc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('fc.num', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->orgs = org::lstFor(['in_fuelcards' => 1]);

        $data->suporgs = org::lstFor(['in_fuelcards_suporgid' => 1]);

        $data->ref_machines = machine::lstFor(['in_fuelcards' => 1]);

        $objgroups = group::lstOrgGroups_cache();

        $usedflags = objflag::lstUsedFlagsForSysObj_cache($this->sysobjid);

        //dd($usrrights);

        return view('fuelcards.index', compact(
            'recs', 'rec0', 'data'
            , 'objgroups', 'usedflags'
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
            $rec = new fuelcard([
                'id' => -1,
                'active' => 1,
                'created_by' => \Auth::user()->id,
            ]);

        } else
            $rec = fuelcard::findOrFail($id);

        $data = new \stdClass();

        //кандидаты организаций-владельцев техники. Отберем по признаку "12-ГК", или та организация которая указана сейчас
//        $data->orgs = org::lstFor(['flagtypeid_or_id' => [12, $rec->orgid]]);

//        $data->ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);

        $usrrights = $this->setInterfaceRight($id);

        return view($this->sysobjcode . '.edit', compact('rec', "data", "usrrights"));
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
            'num.required' => 'Укажите номер карты',
            'suporgid.required' => 'Укажите организацию-поставщика',
            'orgid.required' => 'Укажите организацию - владельца карты',
        ];

        $rules = [
            "num" => "required",
            "suporgid" => "required",
            "orgid" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new fuelcard([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
        } else {
            $rec = fuelcard::find($id);
        }
        $rec->name = $request->get('name');
        $rec->num = $request->get('num');
        $rec->notes = mb_substr($request->get('notes'), 0, 160);
        $rec->suporgid = $request->get('suporgid');
        $rec->orgid = $request->get('orgid');
        $rec->ref_machineid = $request->get('ref_machineid');
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
        //connectify('success', $rec->name, $msg_simple);

        if (1 == 0 and $id == -1) {
            return redirect(route($this->sysobjcode . '.edit', $rec->id));
        } else {
            $pageno = session($this->sysobjcode . '_pageno');
            return redirect(route($this->sysobjcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
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
        $res = fuelcard::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('fuelcards.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'], $res->msg);
        } else {
            $sd['success'] = 'Запись о карте (' . $id . ': '
                . $res->obj['num'] . ' - ' . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $pageno = session($this->objcode . '_pageno');
            $route = route($this->objcode . '.index') . '?page=' . $pageno;
            //connectify('success', $res->obj['name'], 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }

    static public function list_for(Request $request)
    {
        //2021-04-05 SNS. Список для select-ов {id,name}

        $result = "";
        try {

            $list = fuelcard::lstFor([
                's_name' => $request->s_name,
                's_num' => $request->s_num,
                'orgid' => $request->orgid,
                'active' => $request->active,
            ]);

            $result = array('list' => $list);

        } catch (\Exception $e) {
            Log::error('fuelcard::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

    static public function list_for_ac(Request $request)
    {
        //2021-04-05 SNS. Для автокомплита

        $result = "";
        try {

            $list = fuelcard::getFor([
                's_name' => $request->s_name,
                's_num' => $request->s_num,
                'orgid' => $request->orgid,
                'active' => $request->active ?? 1,
            ],
                ['fc.id', 'fc.name', 'fc.num', 'fc.orgid', 'o.name as orgname'
                    , db::raw("(select count(*) from objflags f where f.sysobjid=111 and f.flagtypeid=12 and f.objid=fc.orgid) as in_gk")
                ]);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('fuelcard::list_for_ac:' . $e->getMessage());
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
                $rec = fuelcard::import_001($file, $rec);
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

    static public function data_for_card(Request $request)
    {
        //2023-09-24 SNS. Данные разные

        $result = "";
        try {

            $list = fuelcard::from('fuelcards as fc')
                ->leftJoin('orgs as o', function ($j) {
                    $j->on('o.id', 'fc.orgid');
                })
                ->leftJoin('machines as m', function ($j) {
                    $j->on('m.id', 'fc.ref_machineid');
                })
                ->leftJoin('orgs as mo', function ($j) {
                    $j->on('mo.id', 'm.orgid');
                })
                ->where([
                    'fc.id' => $request->cardid,
//                'active' => 1,
                ])
                ->select(
                    'fc.orgid', 'o.name as org_name'
                    , 'fc.ref_machineid', db::raw("concat(m.regnum, ' (', m.name, ', ', mo.name, ')' ) as ref_machine_name")
                    , 'fc.suporgid'
                )
                ->first()->toArray();

//            dd($list);

            $result = array('data' => $list);
            //Log::info(implode('; ', $list));

        } catch (\Exception $e) {
            Log::error('fuelcard::data_for_card:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
