<?php

namespace App\Http\Controllers;

use App\mchntype;
use App\objextid;
use App\objpref;
use App\sysobj;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Auth;

class MchntypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 481;
        $this->sysobjcode = 'mchntypes';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }


    protected function setInterfaceRight($id)
    {
        $userid = Auth::user()->id;

        $usrrights = array();

        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . ".read");
        $usrrights['create'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.create');

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

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode($userid, $this->sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode($userid, $this->sysobjcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode($userid, $this->sysobjcode . '.save'),
        );

        if (!$usrrights['read'])
            return redirect('home')->with('error', "Нет доступа!");


        // - параметры поиска -------------------------------------------------
        $s_name = '';
        $s_active = '';
        $s_flag = '';

        if ($request->isMethod('post')) {
            $s_name = $request->get("s_name");
            $s_active = $request->get("s_active");
            $s_flag = $request->get("s_flag");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $this->sysobjcode]);
            session(['search_params' => [
                's_name' => $s_name,
                's_active' => $s_active,
                's_flag' => $s_flag,
            ]]);
        } else {
            if (session('search_setname') == $this->sysobjcode) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_name = $params['s_name'];
                    $s_active = $params['s_active'];
                    $s_flag = $params['s_flag'];
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_name" => $s_name,
            "s_active" => $s_active,
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
                $sc = $sc . " and mt.name like '%" . $s_name . "%'";
            }
            if ($s_active != '') {
                $sc = $sc . " and mt.active = '" . $s_active . "'";
            }
        }
        // --------------------------------------------------------------------


        $sort_params = session('sort_params');
        if (isset($sort_params)) {
            $sort_by = $sort_params['field'];
            $sort_dir = $sort_params['dir'];
        } else {
            $sort_by = 'mt.name';
            $sort_dir = 'asc';
        }

        $parent_id = null;
        //Только в пределах контракта пользователя
        $recs = mchntype::from('mchntypes as mt');

        $recs = $recs->select('mt.id', 'mt.name', 'mt.active', 'mt.descript')
            ->whereRaw($sc)
            ->orderBy($sort_by, $sort_dir)
            ->paginate(10);

        $s_actives = [
            1 => 'используется',
            0 => 'не используется',
        ];

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        //$data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid);


        return view($this->sysobjcode . '.index', compact('recs', 'usrrights'
            , 'search_params', 's_actives', 'data'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($parid = null)
    {
        return $this->edit(-1, $parid);
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
        $this->validate($request, [
            'name' => 'required',
            "ordr" => "integer|min:1|max:255",
        ]);
        //integer|max:255

        $mchntype = new mchntype([
            "name" => $request->get('name'),
            "active" => $request->get('active', 0),
            "photourl" => $request->get('photourl'),
            "descript" => $request->get('descript'),
            "ordr" => $request->get('ordr'),
            "created_by" => \Auth::user()->id,
            "updated_by" => \Auth::user()->id,
        ]);
        $mchntype->save();

        //сохраним значение преференции "31. Товар считается новинкой в течение N дней после начала продаж"
        objpref::setPrefVal($this->sysobjid, $mchntype->id, 31, $request->get('NewGoodsMaxDays'));

        return redirect('/mchntypes')->with('success', "Категория была добавлена");
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //При попытке пройти по этому маршруту пробросим
        //пользователя на index
        return redirect()->route('mchntypes.index');
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $parid = null)
    {

        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read']) return view('home'); // Справочник доступен если у пользователя есть право

        if ($id == -1) {
            //new record
            $rec = new mchntype([
                'id' => $id,
            ]);
        } else {
            $rec = mchntype::find($id);
        }

        return view('mchntypes.edit', compact(['rec', 'usrrights']));
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
        $request->validate([
            "name" => "required",
            "ordr" => "integer|min:1|max:255",
        ]);
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //new record
            $mchntype = new mchntype();
        } else {
            $mchntype = mchntype::find($id);
        }


        $mchntype->parent_id = $request->get('parent_id');
        $mchntype->name = $request->get('name');
        $mchntype->descript = $request->get('descript');
        $mchntype->active = $request->get('active', 0);
        $mchntype->photourl = $request->get('photourl');
        $mchntype->ordr = $request->get('ordr', 255);
        $mchntype->updated_at = now();
        $mchntype->updated_by = $userid;
        $mchntype->save();

        //сохраним значение преференции "31. Товар считается новинкой в течение N дней после начала продаж"
        objpref::setPrefVal($this->sysobjid, $mchntype->id, 31, $request->get('NewGoodsMaxDays'));

        if (isset($mchntype->parent_id))
            return redirect(route('mchntypes.edit', $mchntype->parent_id))->with('success', "Категория обновлена");
        return redirect('/mchntypes')->with('success', "Категория обновлена");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $route = route('mchntypes.edit', $id);

        //        if (mchntype::find($id)->childs()->count() > 0)
//            return redirect($route)->with(['error' => 'Нельзя удалить - есть подчиненные записи!']);

        $res = mchntype::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route('mchntypes.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $route = route('mchntypes.index');
            $sd['success'] = 'Категория была удалена';
        }
        return redirect($route)->with($sd);
    }
}
