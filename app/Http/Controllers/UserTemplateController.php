<?php

namespace App\Http\Controllers;

use App\user_template;
use App\sysobj;
use App\usrsysright;

use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class UserTemplateController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 887;
        $this->sysobjcode = 'user_templates';
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

        //по acl указанного объекта
        $acl_sysobjcode = sysobj::where('code', $this->sysobjcode)
                ->select(db::raw("ifnull(acl_sysobjcode, code) as acl_sysobjcode"))
                ->first()->acl_sysobjcode ?? $this->sysobjcode;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.delete');
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
            's_pageitmcnt' => 20
            , 's_active' => '1'
            , 's_name' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and concat(pc.name,' ',ifnull(pc.descript,' ')) like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_active') {
                    $sc = $sc . " and ifnull(pc.active,0) = '{$val}'";

                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------


        $recs = user_template::from('user_templates as t')
            ->join('sysobjs as so', 'so.id', 't.sysobjid')
            ->join('users as u', 'u.id', 't.userid')
            ->whereraw($sc)
            ->select('t.id', 'u.name', 'so.name', 't.active'
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('u.name', 'asc');
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
            $rec = new user_template([
                'id' => -1,
                'active' => 1,
                'created_by' => $userid,
            ]);
        } else
            $rec = user_template::find($id);


        if (!isset($rec))
            response(redirect(route($this->sysobjcode . '.index')));


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
            $rec = new user_template();
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о категории информации";
        } else {
            $rec = user_template::find($id);
            $mess = "Изменена запись о категории информации";
        }

        $rec->name = $request->get('name');
        $rec->descript = $request->get('descript');

        $rec->ordr = $request->get('ordr') ?? 9999;
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

        $retURL = $request->get('returl') ?? route($this->sysobjcode . '.edit', $id);

        $rec = user_template::find($id);

        //if (usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.delete')) {
        if (isset($rec) and $rec->userid == $userid) {

            $res = user_template::delete_by_id($id);
            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $sd["error"] = $res->msg;
            } else {

                $retURL = $request->get('returl') ?? route($this->sysobjcode . '.index');
                $sd['success'] = 'Запись шаблона удалена';
            }
        } else {
            $sd['success'] = 'У вас нет прав на удаление записей!';
        }

        return redirect($retURL)->with($sd);
    }


}

