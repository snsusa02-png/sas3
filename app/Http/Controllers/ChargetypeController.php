<?php

namespace App\Http\Controllers;

use App\chargetype;
use App\objextid;
use App\objpref;
use App\org;
use App\org_charge;
use App\sysobj;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class ChargetypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 1210;
        $this->sysobjcode = 'chargetypes';
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
                $sc = $sc . " and ct.name like '%" . $s_name . "%'";
            }
            if ($s_active != '') {
                $sc = $sc . " and ct.active = '" . $s_active . "'";
            }
        }
        // --------------------------------------------------------------------


        $sort_params = session('sort_params');
        if (isset($sort_params)) {
            $sort_by = $sort_params['field'];
            $sort_dir = $sort_params['dir'];
        } else {
//            $sort_by = 'ct.ordr';
            $sort_by = DB::raw('ifnull(ordr,999999)');
            $sort_dir = 'asc';
        }

        $parent_id = null;
        //Только в пределах контракта пользователя
        $recs = chargetype::from('chargetypes as ct');

        $recs = $recs->select('ct.id', 'ct.name', 'ct.dir', 'ct.active', 'ct.ordr', 'ct.descript')
            ->whereRaw($sc)
            ->orderBy('dir', 'desc')
            ->orderBy($sort_by, $sort_dir)
            ->paginate(10);


        $data = new \stdClass();

        $data->dirs = [
            1 => 'Начисления',
            -1 => 'Удержания',
            0 => 'Справки',
        ];
        //варианты кол-ва записей на страницу
        //$data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid);


        return view($this->sysobjcode . '.index', compact('recs', 'usrrights'
            , 'search_params', 'data'));
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

        $chargetype = new chargetype([
            "name" => $request->get('name'),
            "active" => $request->get('active', 0),
            "photourl" => $request->get('photourl'),
            "descript" => $request->get('descript'),
            "ordr" => $request->get('ordr'),
            "created_by" => \Auth::user()->id,
            "updated_by" => \Auth::user()->id,
        ]);
        $chargetype->save();

        //сохраним значение преференции "31. Товар считается новинкой в течение N дней после начала продаж"
        objpref::setPrefVal($this->sysobjid, $chargetype->id, 31, $request->get('NewGoodsMaxDays'));

        return redirect('/chargetypes')->with('success', "Категория была добавлена");
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
        return redirect()->route('chargetypes.index');
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
            $parent_id = ($parid == 0) ? null : $parid;

            //$ordr = chargetype::where('parent_id', $parent_id)->max('ordr') ?? 0;
            $ordr = chargetype::max('ordr') ?? 0;
            $ordr += 10;

            $rec = new chargetype([
                'id' => $id,
                //'parent_id' => $parent_id,
                'ordr' => $ordr,
            ]);
        } else {
            $rec = chargetype::find($id);
        }

        $rec->dirs = [-1=>'удержание', 1=>'начисление', 0=>'нейтрально (справка)'];

        $rec->extids = objextid::from('objextids as ei')
            ->join('extsystems as s', 's.id', 'ei.extsysid')
            ->where('sysobjid', $this->sysobjid)
            ->where('objid', $rec->id)
            ->select('ei.id', 's.name as extsysname', 'extid')
            ->orderby('s.name')
            ->get();

        $rec->charge_orgs = org_charge::from('org_charges as oc')
            ->join('orgs as o', 'o.id', 'oc.orgid')
            ->where('chargetypeid', $rec->id)
            ->select('oc.id', 'o.name as orgname')
            ->orderby('o.name')
            ->get();
        //dd($rec->charge_orgs);


        //$subtype = ItmSubType::where("itmtypeid", $id)->get();
        $subtype = null;
        //$subtype = chargetype::where("parent_id", $id)->get();

//        $spectype = it_si_link::where("itmtypeid", $id)
//            ->join('specinfotypes', 'it_si_links.specinfotypeid', '=', 'specinfotypes.id')
//            ->select("specinfotypes.name", "it_si_links.id")->get();
        $spectype = null;

//        $rec->importgroups = importgroup::select('id', 'name', 'active')
//            ->where('itmtypeid', $id)
//            ->orderby('name')
//            ->get();

        $chargetypes_path = [];
        $itm = $rec;
        $rec->chargetypes_path = $chargetypes_path;
        //dd(array_reverse($rec->chargetypes_path));
        //dd(array_flip($chargetypes_path));

        return view('chargetypes.edit', compact(['rec', 'subtype', 'spectype', 'usrrights']));
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
            "ordr" => "integer|min:1|max:999",
        ]);
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //new record
            $chargetype = new chargetype();
        } else {
            $chargetype = chargetype::find($id);
        }


        $chargetype->name = $request->get('name');
        $chargetype->dir = $request->get('dir');
        $chargetype->descript = $request->get('descript');
        $chargetype->active = $request->get('active', 0);
//        $chargetype->photourl = $request->get('photourl');
        $chargetype->ordr = $request->get('ordr', 255);
        $chargetype->use_price = $request->get('use_price', 0);
        $chargetype->updated_at = now();
        $chargetype->updated_by = $userid;
        $chargetype->save();

        //сохраним значение преференции "31. Товар считается новинкой в течение N дней после начала продаж"
//        objpref::setPrefVal($this->sysobjid, $chargetype->id, 31, $request->get('NewGoodsMaxDays'));

//        if (isset($chargetype->parent_id))
//            return redirect(route('chargetypes.edit', $chargetype->parent_id))->with('success', "Категория обновлена");

        return redirect('/chargetypes')->with('success', "Категория обновлена");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $route = route('chargetypes.edit', $id);
        if (chargetype::find($id)->childs()->count() > 0)
            return redirect($route)->with(['error' => 'Нельзя удалить - есть подчиненные записи!']);

        $res = chargetype::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route('chargetypes.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            if (isset($res->obj['parent_id']))
                $route = route('chargetypes.edit', $res->obj['parent_id']);
            else
                $route = route('chargetypes.index');
            $sd['success'] = 'Категория была удалена';
        }
        return redirect($route)->with($sd);
    }
}
