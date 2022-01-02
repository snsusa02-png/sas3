<?php

namespace App\Http\Controllers;

use App;
use App\unittype;
use App\objflag;
use App\objlog;
use App\org;
use App\org_saldo;
use App\objextid;
use App\User;
use App\usrsysright;
use App\group;
use App\grpitem;
use Auth;
use Cache;
use DB;
use Illuminate\Http\Request;
use App\obj_name;


class UnittypeController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 301;
        $this->sysobjcode = 'unittypes';
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        $usrrights['org_saldos.read'] = false;
        $usrrights['org_saldos.create'] = false;
        $usrrights['org_acnts.read'] = false;
        $usrrights['org_acnts.create'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.delete');
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

        //Проверка прав пользователя.
//        if (!\App\usrsysright::isUserHasRightByCode(Auth::user()->id, 'orgs.read')) {
//            return view('home');
//        }

        //$usrrights = $this->setInterfaceRight(-1);
        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.save'),
        );
        if (!$usrrights['read']) {
            return view('home');
        }


        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска -------------------------------------------------
        $s_name = "";
        $s_boss_name = "";
        $s_inn = "";
        $s_addr = "";
        $s_statuscode = "";
        $s_orggrpid = "";
        $s_flagtypeid = "";
        $s_rating = "";

        if ($request->isMethod('post')) {
            $s_name = $request->get("s_name");
            $s_boss_name = $request->get("s_boss_name");
            $s_inn = $request->get("s_inn");
            $s_addr = $request->get("s_addr");
            $s_statuscode = $request->get("s_statuscode");
            $s_orggrpid = $request->get("s_orggrpid");
            $s_flagtypeid = $request->get("s_flagtypeid");
            $s_rating = $request->get("s_rating");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $this->sysobjcode]);
            session(['search_params' => [
                's_name' => $s_name,
                '$s_boss_name' => $s_boss_name,
                's_inn' => $s_inn,
                's_addr' => $s_addr,
                's_statuscode' => $s_statuscode,
                's_orggrpid' => $s_orggrpid,
                's_flagtypeid' => $s_flagtypeid,
                's_rating' => $s_rating,
            ]]);
        } else {
            if (session('search_setname') == $this->sysobjcode) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_name = $params['s_name'] ?? null;
                    $s_boss_name = $params['s_boss_name'] ?? null;
                    $s_inn = $params['s_inn'] ?? null;
                    $s_addr = $params['s_addr'] ?? null;
                    $s_statuscode = $params['s_statuscode'] ?? null;
                    $s_orggrpid = $params['s_orggrpid'] ?? null;
                    $s_flagtypeid = $params['s_flagtypeid'] ?? null;
                    $s_rating = $params['s_rating'] ?? null;
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_name" => $s_name,
            "s_boss_name" => $s_boss_name,
            "s_inn" => $s_inn,
            "s_addr" => $s_addr,
            "s_statuscode" => $s_statuscode,
            "s_orggrpid" => $s_orggrpid,
            "s_flagtypeid" => $s_flagtypeid,
            "s_rating" => $s_rating,
        ];

        $needSearch = false;
        foreach ($search_params as $p) {
            if (isset($p)) {
                $needSearch = true;
                break;
            }
        }

        //var_dump($search_params);

        $sc = "1=1 ";
        if ($needSearch) {
//            if (strlen($s_name) > 0) {
//                $sc = $sc . " and concat(ut.name,' ',ifnull(ut.descript,' ')) like '%" . mb_strtoupper($s_name) . "%'";
//            }

            if (strlen($s_name) > 0) {
                $search_str = $s_name;

                $search_str = mb_strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
                $search_str = preg_replace('| +|', ' ', $search_str);
                $find = explode(" ", $search_str);

                $sc = ' 1=1';
                $sc2 = ' ';     //для поиска по obj_names

                if (count($find) > 0) {
                    $sc .= ' and ( (1=1';
                    foreach ($find as $f) {
                        $sc .= " and LCASE( concat(ut.name, ' ', ifnull(ut.descript,' ')))";
                        $sc .= " like '%" . $f . "%'";

                        //для поиска по obj_names
                        $sc2 .= " and n.name like '%" . $f . "%'";
                    }
                    $sc .= ')';
                    $sc2 = ' or exists(select 1 from obj_names as n where n.sysobjid=301 and n.objid=ut.id' . $sc2 . ')';

                    $sc = $sc . $sc2 . ')';
                }
            }
            //dd($sc , $sc2);
        }
        // --------------------------------------------------------------------


        $recs = unittype::from('unittypes as ut')
            ->leftjoin('unittypes as pu', 'pu.id', 'ut.parent_by')
            ->whereraw($sc)
            ->select('ut.id', 'ut.name', 'ut.active', 'ut.descript', 'ut.decimal_dgts',
                'ut.parent_by', 'ut.k2prnt_unit', 'pu.name as parent_name');

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_orgs.index');

        //$recs = $recs->orderBy('parent_by', 'asc');
        $recs = $recs->orderByRaw("if(ut.parent_by,1,0)");
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('ut.name', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate(10);

//номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        return view($this->sysobjcode . '.index', compact('recs', 'rec0'
            , 'search_params', 'sort_params'
            , 'usrrights'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($parent_id = null)
    {
        return $this->edit(-1, $parent_id);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $unittype = unittype::find($id);
        return view($this->sysobjcode . '.show', compact('org'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $parent_id = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            $parent_id = ($parent_id == 0) ? null : $parent_id;
            $rec = new unittype([
                'id' => -1,
                'active' => 1,
                'parent_by' => $parent_id,
                'created_by' => $userid,
            ]);
        } else
            $rec = unittype::find($id);

        if (!isset($rec))
            return redirect(route($this->sysobjcode . '.index'));

        $usrrights = $this->setInterfaceRight($id);
        $usrrights['delete'] = ($usrrights['delete'] and count($rec->childs) == 0);

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
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
        $userid = \Auth::user()->id;
        //$staffid=\Auth::user()->StaffID;

        $messages = [
            'name.required' => 'Укажите название единицы измерения',
        ];

        $request->validate([
            "name" => "required|max:16",
        ], $messages);


        $mess = "";
        if ($id == -1) {
            $rec = new unittype([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = unittype::find($id);
            $mess = "Запись обновлена";
        }

        //проверка на дубль по названию
        $name = $request->get('name');

        $prm = new \stdClass();
        $prm->id = $rec->id;
        $prm->name = $name;

        $rules = [
            //В форме должно быть поле ttt
            "ttt" => [
                function ($attribute, $value, $fail) use ($prm) {
                    //
                    $cnt = unittype::where([
                        'name' => $prm->name,
                    ])
                        ->where('id', '<>', ($prm->id ?? -1))
                        ->count();
                    //dd($cnt);
                    if ($cnt > 0) {
                        $fail("Единица измерения с таким названием уже зарегистрирована!");
                    } else {
                        //будем искать среди альтернативных названий
                        $cnt = obj_name::where([
                            'sysobjid' => $this->sysobjid,
                            'name' => $prm->name,
                        ])->count();
                        if ($cnt > 0) {
                            $fail("Единица измерения с таким названием уже зарегистрирована!");
                        }
                    }
                },
            ],
        ];

        $request->validate($rules, $messages);


        $rec->name = mb_substr($request->get('name'), 0, 16);
        $rec->descript = mb_substr($request->get('descript'), 0, 60);
        $rec->decimal_dgts = $request->get('decimal_dgts');
        $rec->k2prnt_unit = $request->get('k2prnt_unit');
        $rec->active = $request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        connectify('success', $rec->name, $mess);
        if ($id == -1) {
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
    public
    function destroy($id)
    {
        $res = unittype::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route($this->sysobjcode . '.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'], $res->msg);
        } else {

            //todo:: сделать удаление по ключу
            objflag::where('sysobjid', $this->sysobjid)->where('objid', $id)->delete();

            $sd['success'] = 'Запись о контрагенте (' . $id . ': '
                . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            //$route = route($this->sysobjcode.'.index');
            $pageno = session($this->sysobjcode . '_pageno');
            $route = route($this->sysobjcode . '.index') . '?page=' . $pageno;
            connectify('success', $res->obj['name'], 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    static public function info_params(Request $request)
    {
        $result = "";
        $rqst_id = $request->id;

        return Cache::remember('unittypes_info_params_' . $rqst_id, now()->addMinutes(5)
            , function () use ($rqst_id) {
                try {
                    $rec = unittype::select('id', 'name', 'descript', 'decimal_dgts', 'active')
                        ->find($rqst_id);

                    //$result = array('address' => $rec->address, 'opertypes' => $buildopertypes);
                    $result = $rec;

                } catch (\Exception $e) {
                }
                return response()->json($result);
            });
    }

}
