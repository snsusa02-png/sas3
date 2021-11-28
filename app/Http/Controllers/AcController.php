<?php

namespace App\Http\Controllers;

use App\ac;
use App\orgpost;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\user_ac;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use App\sysobj;

class AcController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1501;
        $this->sysobjcode = 'acs';
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
                    $sc = $sc . " and concat(ac.name,' ',ifnull(ac.descript,' ')) like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_active') {
                    $sc = $sc . " and ifnull(ac.active,0) = '{$val}'";

                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------


        $recs = ac::from('acs as ac')
            ->whereraw($sc)
            ->select('ac.id', 'ac.name'
                , db::raw("ifnull(ac.ordr, 9999) as ordr")
                , 'ac.active'
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('ac.name', 'asc');
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


        return view('acs.index', compact(['recs', 'data', 'usrrights']));
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
            $rec = ac::find($id);


        if (!isset($rec))
            response(redirect(route('acs.index')));

        $rec->owners = user_ac::getFor(
            ['user_active' => 1,
                'acsid' => $rec->id
            ], [
            'u.id', 'u.name', 'uac.created_at as begdt'
        ], [['u.name', 'asc']]);
        //dd($rec->owners);

        $rec->retURL = $request->get('returl');

        //$data = new \stdClass();

        $usrrights = $this->setInterfaceRight($id);


        return view('acs.edit', compact(['rec', 'usrrights']));
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
            'name.required' => 'Укажите название категории',
        ];

        $rules = [
            "name" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight($id);

        $mess = "";
        if ($id == -1) {
            $rec = new ac();
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о категории информации";
        } else {
            $rec = ac::find($id);
            $mess = "Изменена запись о категории информации";
        }

        $rec->name = $request->get('name');
        $rec->descript = $request->get('descript');

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

        $retURL = $request->get('returl') ?? route('ac.edit', $id);

        if (usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.delete')) {

            $res = ac::delete_by_id($id);

            $sd = array();
            if ($res->err == 1) {
                $sd["error"] = $res->msg;
            } else {

                $retURL = $request->get('returl') ?? route('acs.index', $res->obj['orgid']);
                $sd['success'] = 'Запись удалена';
            }
        } else {
            $sd['success'] = 'У вас нет прав на удаление записей!';
        }
        return redirect($retURL)->with($sd);
    }


}

