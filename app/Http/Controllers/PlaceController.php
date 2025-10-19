<?php

namespace App\Http\Controllers;

use App\ac;
use App\org_place;
use App\place;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PlaceController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 109;
        $this->sysobjcode = 'places';
        $this->model = 'place';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    /*
     * Установка прав пользователя
     */
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

        session([$this->sysobjcode . '_pageno' => $request->page ?? 1]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_active' => '1'
            , 's_name' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and concat(p.name,' ',ifnull(p.descript,' ')) like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_active') {
                    $sc = $sc . " and ifnull(p.active,0) = '{$val}'";

                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------

        $recs = place::from('places as p')
            ->whereraw($sc)
            ->select('p.id', 'p.name'
                //, db::raw("ifnull(p.ordr, 9999) as ordr")
                , 'p.active'
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('p.name', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);
        //--------------------------------------------------------------


        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        //номер первой записи на странице:
        $data->rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data->search_params = $search_params;

        //$data->statuses = [1 => 'актив', 0 => 'архив'];


        return view($this->sysobjcode . '.index', compact(['recs', 'data', 'usrrights']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return $this->edit($request, -1);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        //

        $userid = \Auth::user()->id;

        if ($id == -1) {
            $rec = new ac([
                'id' => -1,
                'active' => 1,
                'created_by' => $userid,
            ]);
        } else
            $rec = place::find($id);


        if (!isset($rec))
            response(redirect(route($this->sysobjcode . '.index')));

//        $rec->owners = user_place::getFor(
//            ['user_active' => 1,
//                'acsid' => $rec->id
//            ], [
//            'u.id', 'u.name', 'up.created_at as begdt'
//        ], [['u.name', 'asc']]);
        //dd($rec->owners);

        $rec->orgplaces = org_place::getFor(
            [
                'placeid' => $rec->id
            ], [
            'p.id', 'p.name', 'p.active', 'o.name as orgname'
        ], [['o.name', 'asc']]);
        //dd($rec->orgplaces);

        $rec->retURL = $request->get('returl');

        //$data = new \stdClass();

        $usrrights = $this->setInterfaceRight($id);


        return view($this->sysobjcode . '.edit', compact(['rec', 'usrrights']));
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
        $messages = [
            'name.required' => 'Укажите название места/локации',
        ];

        $rules = [
            "name" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight($id);

        $mess = "";
        if ($id == -1) {
            $rec = new place();
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о месте(локации)";
        } else {
            $rec = place::find($id);
            $mess = "Изменена запись о месте(локации)";
        }

        $rec->name = $request->get('name');
        $rec->descript = $request->get('descript');
        $rec->address = $request->get('address');

        $rec->active = $request->get('active');
        $rec->updated_by = $userid;
        $rec->save();

        //Cache::forget('org_aux_staff_.' . $rec->orgid);

        $retURL = $request->get('returl') ?? route($this->sysobjcode . '.index')
            . '?page=' . session($this->sysobjcode . '_pageno') . '#' . $rec->id;

        return redirect($retURL)->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {

        $userid = \Auth::user()->id;

        $retURL = $request->get('returl') ?? route('places.edit', $id);

        if (usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.delete')) {

            $res = place::delete_by_id($id);

            $sd = array();
            if ($res->err == 1) {
                $sd["error"] = $res->msg;
            } else {

                $retURL = $request->get('returl') ?? route($this->sysobjcode . '.index');
                $sd['success'] = 'Запись удалена';
            }
        } else {
            $sd['success'] = 'У вас нет прав на удаление записей!';
        }
        return redirect($retURL)->with($sd);
    }


    static public function list_for(Request $request)
    {
        //2021-10-27 SNS. Обертка для вызова place::lstFor

        $result = "";
        try {
            $list = place::lstFor([
                's_name' => $request->s_name,
                's_code' => $request->s_code,
                's_active' => $request->s_active,
                's_for_load' => $request->s_for_load,
                's_for_unload' => $request->s_for_unload,
            ]);


            $result = array('places' => $list);

        } catch (\Exception $e) {
            Log::error('place::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

    static public function list_for_ac(Request $request)
    {
        //2021-10-27 SNS. Для автокомплита

        $result = "";
        try {

            $list = place::getFor([
                's_name' => $request->s_name,
            ],
                ['p.id', 'p.code', 'p.name']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('place::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }

}


