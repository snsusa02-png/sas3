<?php

namespace App\Http\Controllers;

use App\route_point;
use App\objflag;
use App\objlog;
use App\sysobj;
use App\Traits\Result;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RoutePointController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 1228;
        $this->sysobjcode = 'route_points';
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
            //$usrrights['mchn_opertypes.create'] = $usrrights['save'];
        }
//dd($usrrights);
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
            , 's_src_placename' => ''
            , 's_tgt_placename' => ''
            , 's_date' => ''
            , 's_active' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_src_placename') {
                    $sc = $sc . " and rp.src_placename like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_tgt_placename') {
                    $sc = $sc . " and rp.tgt_placename like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_date') {
                    $sc = $sc . " and rp.begdate <= '{$val}' and rp.enddate >= '{$val}'";

                } elseif ($item == 's_active') {
                    if ($val == '1')
                        $sc = $sc . " and rp.active=1 and curdate() between rp.begdate and rp.enddate";
                    elseif ($val == '0')
                        $sc = $sc . " and not (rp.active=1 and curdate() between rp.begdate and rp.enddate)";
                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------

        $recs = route_point::from('route_points as rp')
//            ->leftJoin('orgs as o', function ($j) {
//                $j->on('o.id', 'rp.orgid');
//            })
            ->whereraw($sc)
            ->select('rp.id', 'rp.src_placename', 'rp.tgt_placename', 'rp.points', 'rp.active', 'rp.begdate', 'rp.enddate');

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->objcode . '.index');

        // 2025-04-13 todo: Доработать
//        if (isset($sort_params) and count($sort_params) == 0 ) {
//            $sort_params[]=['field'=>'rp.src_placename', 'dir'=>'asc'];
//            $sort_params[]=['field'=>'rp.tgt_placename', 'dir'=>'asc'];
//            $sort_params[]=['field'=>'rp.begdate', 'dir'=>'desc'];
//        }

            if (isset($sort_params) and count($sort_params) > 0 ) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs
                ->orderBy('rp.src_placename', 'asc')
                ->orderBy('rp.tgt_placename', 'asc')
                ->orderBy('rp.begdate', 'desc');

        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

//        $data->orgs = org::lstFor(['in_fuelcards' => 1]);
//        $data->ref_machines = machine::lstFor(['in_fuelcards' => 1]);

        //$objgroups = group::lstOrgGroups_cache();

        $usedflags = objflag::lstUsedFlagsForSysObj_cache($this->sysobjid);

        //dd($usrrights);

        return view('route_points.index', compact(
            'recs', 'rec0', 'data'
            , 'usedflags'
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
            $rec = new route_point([
                'id' => -1,
                'active' => 1,
                'begdate' => date_create(now())->format('Y-m-01'),   //Первый день месяца
                'created_by' => \Auth::user()->id,
            ]);

        } else
            $rec = route_point::findOrFail($id);

        $data = new \stdClass();

        //кандидаты организаций-владельцев техники. Отберем по признаку "12-ГК", или та организация которая указана сейчас
//        $data->orgs = org::lstFor(['flagtypeid_or_id' => [12, $rec->orgid]]);

//        $data->ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);

        $data->places = DB::select("SELECT distinct src_placename as name FROM `route_points`
                    UNION SELECT distinct tgt_placename as name FROM `route_points` order by 1");
        //dd($data);

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
            'src_placename.required' => 'Укажите начало маршрута',
            'tgt_placename.required' => 'Укажите окончание маршрута',
            'points.required' => 'Укажите баллы',
        ];

        $rules = [
            "src_placename" => "required",
            "tgt_placename" => "required",
            "points" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new route_point([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
        } else {
            $rec = route_point::find($id);
        }
        $rec->src_placename = mb_strtoupper($request->get('src_placename'));
        $rec->tgt_placename = mb_strtoupper($request->get('tgt_placename'));
        $rec->points = $request->get('points');
        $rec->begdate = $request->get('begdate');
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

        // Перерасчет даты окончания периода действия ставки по данному маршруту ------
        $recs = route_point::where('active', 1)
            ->where('src_placename', $rec->src_placename)
            ->where('tgt_placename', $rec->tgt_placename)
            ->select('id', 'begdate', 'enddate')
            ->orderby('begdate', 'desc')
            ->get();
        $enddate = '3000-01-01';
        foreach ($recs as $r) {
            $r->enddate = $enddate;
            $r->save();
            $enddate = date_create($r->begdate);
            $enddate = $enddate->modify('-1 day')->format('Y-m-d');
        }
        //---------------------------------------------------------------------

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
        $res = route_point::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('route_points.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['src_placename'].' - '.$res->obj['tgt_placename'], $res->msg);
        } else {
            $sd['success'] = 'Запись о карте (' . $id . ': '
                . $res->obj['src_placename'].' - '.$res->obj['tgt_placename'] . ') удалена';
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
//todo: Переделать!
        $result = "";
        try {

            $list = route_point::lstFor([
                's_src_placename' => $request->s_src_placename,
                's_tgt_placename' => $request->s_tgt_placename,
                'orgid' => $request->orgid,
                'active' => $request->active,
            ]);

            $result = array('list' => $list);

        } catch (\Exception $e) {
            Log::error('route_point::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

    static public function list_for_ac(Request $request)
    {
        //2021-04-05 SNS. Для автокомплита
//todo: Переделать!
        $result = "";
        try {

            $list = route_point::getFor([
                's_src_placename' => $request->s_src_placename,
                's_tgt_placename' => $request->s_tgt_placename,
                'orgid' => $request->orgid,
                'active' => $request->active ?? 1,
            ],
                ['rp.id', 'rp.name', 'rp.num', 'rp.orgid', 'o.name as orgname'
                    , db::raw("(select count(*) from objflags f where f.sysobjid=111 and f.flagtypeid=12 and f.objid=rp.orgid) as in_gk")
                ]);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('route_point::list_for_ac:' . $e->getMessage());
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
//todo: Переделать!
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
                $rec = route_point::import_001($file, $rec);
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
        //todo: Переделать!
        //2023-09-24 SNS. Данные разные

        $result = "";
        try {

            $list = route_point::from('route_points as rp')
                ->where([
                    'rp.id' => $request->rp_id,
//                'active' => 1,
                ])
                ->select(
                    'rp.orgid', 'o.name as org_name'
                    , 'rp.ref_machineid', db::raw("concat(m.regnum, ' (', m.name, ', ', mo.name, ')' ) as ref_machine_name")
                )
                ->first()->toArray();

//            dd($list);

            $result = array('data' => $list);
            //Log::info(implode('; ', $list));

        } catch (\Exception $e) {
            Log::error('route_point::data_for_card:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
