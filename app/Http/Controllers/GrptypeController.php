<?php

namespace App\Http\Controllers;

use App\group;
use App\grptype;
use App\usrsysright;
use App\sysobj;
use Config;
use DateTime;
use DB;
use Illuminate\Http\Request;

class GrptypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 821;
        $this->objcode = 'grptypes';
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.delete');
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

        $usrrights = Array(
            'read' => usrsysright::isUserHasRightByCode($userid, $this->objcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode($userid, $this->objcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode($userid, $this->objcode . '.save'),
        );

        // - параметры поиска -------------------------------------------------
        $s_name = "";
        $s_regnum = "";
        $s_flag = "";

        if ($request->isMethod('post')) {
            $s_name = $request->get("s_name");
            $s_regnum = $request->get("s_regnum");
            $s_flag = $request->get("s_flag");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $this->objcode]);
            session(['search_params' => [
                's_name' => $s_name,
                's_regnum' => $s_regnum,
                's_flag' => $s_flag,
            ]]);
        } else {
            if (session('search_setname') == $this->objcode) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_name = $params['s_name'];
                    $s_regnum = $params['s_regnum'];
                    $s_flag = $params['s_flag'];
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_name" => $s_name,
            "s_regnum" => $s_regnum,
            "s_flag" => $s_flag,
        ];

        $sc = "1=1 ";
        $needSearch = false;
        foreach ($search_params as $p) {
            if (isset($p)) {
                $needSearch = true;
                break;
            }
        }

        if ($needSearch) {

            if (strlen($s_name) > 0) {
                $sc = $sc . " and gt.name like '%" . $s_name . "%'";
            }
        }
        // --------------------------------------------------------------------


        $sort_params = session('sort_params');
        if (isset($sort_params)) {
            $sort_by = $sort_params['field'];
            $sort_dir = $sort_params['dir'];
        } else {
            $sort_by = 'gt.ordr';
            $sort_dir = 'asc';
        }

        $parent_id = null;
        //Только в пределах контракта пользователя
        $recs = grptype::from('grptypes as gt');

        $recs = $recs->select('gt.*')
            ->whereRaw($sc)
            ->orderBy($sort_by, $sort_dir)
            ->paginate(10);

        return view($this->objcode . '.index', compact('recs', 'usrrights', 'search_params'));
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
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $userid = \Auth::user()->id;


        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new grptype();
            $rec->id = -1;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = grptype::find($id);
        }
        if (isset($rec)) {

            $rec->groups = group::where('grptypeid', $id)->select('id', 'name', 'ordr')
                ->orderBy('ordr')
                ->orderBy('name')
                ->get();

            $rec->sysobjs = sysobj::lst_sysobjs4grptypes_cache();

            $usrrights = $this->setInterfaceRight($id);

            return view($this->objcode . '.edit', compact(['rec', 'usrrights']));
        }
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
            'name.required' => 'Укажите название типа',
        ];

        $request->validate([
            "name" => "required",
        ], $messages);

        $userid = \Auth::user()->id;

        $mess = "";
        if ($id == -1) {

            $rec = new grptype();
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись";

        } else {

            $rec = grptype::find($id);
            $mess = "Изменена запись ";
        }

        $ordr = $request->get('ordr');
        $rec->ordr = $ordr ?? 255;
        $rec->name = $request->get('name');
        $rec->forsysobjid = $request->get('forsysobjid');
        $rec->updated_by = $userid;
        $rec->save();


        if ($id == -1) {
            //останемся в созданной записи
            return redirect(route($this->objcode . '.edit', $rec->id))->with('success', $mess);

        } else {
            return redirect(route($this->objcode . '.index'))->with('success', $mess);
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
        $res = grptype::delete_by_id($id,$this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route($this->objcode . '.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $route = route($this->objcode . '.index');
            $sd['success'] = 'Запись удалена';
        }
        return redirect($route)->with($sd);
    }

    public function admindelete($id)
    {
        $rec = grptype::find($id);
        if ($rec) {

            $parent_id = $rec->parent_id;

            $res = $rec->admindelete();

            $sd = array();
            if ($res->err == 1) {
                $route = route($this->objcode . '.edit', $id);
                $sd["error"] = $res->msg;
                objlog::log_info($this->sysobjid, $id, $res->msg, 2);

            } else {
                $route = route($this->objcode . '.index');
                $sd['success'] = 'Запись удалена администратором';
                objlog::log_info($this->sysobjid, 0, "Административное удаление записи id=" . $id, 2);
            }
        } else {
            $sd['warning'] = 'Запись не найдена!';
            $route = route($this->objcode . '.index');
        }
        return redirect($route)->with($sd);
    }
}
