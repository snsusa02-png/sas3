<?php

namespace App\Http\Controllers;

use App\grptype;
use App\importgroup;
use App\objextid;
use App\objpref;
use App\sysobj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\itmtype;
use App\ItmSubType;
use App\usrsysright;
use App\it_si_link;
use Auth;

//20190404 SNS
use DB;

class ItmTypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 101;
        $this->sysobjcode = 'itmtypes';
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
                $sc = $sc . " and it.name like '%" . $s_name . "%'";
            }
            if ($s_active != '') {
                $sc = $sc . " and it.active = '" . $s_active . "'";
            }
        }
        // --------------------------------------------------------------------


        $sort_params = session('sort_params');
        if (isset($sort_params)) {
            $sort_by = $sort_params['field'];
            $sort_dir = $sort_params['dir'];
        } else {
//            $sort_by = 'it.ordr';
            $sort_by = DB::raw('ifnull(ordr,999)');
            $sort_dir = 'asc';
        }

        $parent_id = null;
        //Только в пределах контракта пользователя
        $recs = itmtype::from('itmtypes as it');

        $recs = $recs->select('it.id', 'it.name', 'it.active', 'it.ordr', 'it.descript')
            ->whereRaw($sc)
            ->whereNull('parent_id')
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

        $itmtype = new itmtype([
            "name" => $request->get('name'),
            "active" => $request->get('active', 0),
            "photourl" => $request->get('photourl'),
            "descript" => $request->get('descript'),
            "ordr" => $request->get('ordr'),
            "created_by" => \Auth::user()->id,
            "updated_by" => \Auth::user()->id,
        ]);
        $itmtype->save();

        //сохраним значение преференции "31. Товар считается новинкой в течение N дней после начала продаж"
        objpref::setPrefVal($this->sysobjid, $itmtype->id, 31, $request->get('NewGoodsMaxDays'));

        return redirect('/itmtypes')->with('success', "Категория была добавлена");
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
        return redirect()->route('itmtypes.index');
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

            $ordr = itmtype::where('parent_id', $parent_id)->max('ordr') ?? 0;
            $ordr += 10;

            $rec = new itmtype([
                'id' => $id,
                'parent_id' => $parent_id,
                'ordr' => $ordr,
            ]);
        } else {
            $rec = itmtype::find($id);
        }

        $NewGoodsMaxDays = objpref::getPrefVal($this->sysobjid, $id, 31);
        $NewGoodsMaxDays = (isset($NewGoodsMaxDays)) ? round($NewGoodsMaxDays, 0) : null;
        $rec->NewGoodsMaxDays = $NewGoodsMaxDays;

        $rec->extids = objextid::from('objextids as ei')
            ->join('extsystems as s', 's.id', 'ei.extsysid')
            ->where('sysobjid', $this->sysobjid)
            ->where('objid', $rec->id)
            ->select('ei.id', 's.name as extsysname', 'extid')
            ->orderby('s.name')
            ->get();

        //$subtype = ItmSubType::where("itmtypeid", $id)->get();
        $subtype = null;
        $subtype = itmtype::where("parent_id", $id)->get();

//        $spectype = it_si_link::where("itmtypeid", $id)
//            ->join('specinfotypes', 'it_si_links.specinfotypeid', '=', 'specinfotypes.id')
//            ->select("specinfotypes.name", "it_si_links.id")->get();
        $spectype = null;

//        $rec->importgroups = importgroup::select('id', 'name', 'active')
//            ->where('itmtypeid', $id)
//            ->orderby('name')
//            ->get();

        $itmtypes_path = [];
        $itm = $rec;
        $parent_id = $itm->parent_id;
        while (isset($parent_id)) {
            $itmtypes_path[$parent_id] = $itm->parent->name;

            //найдем id родительского бюджета
            $itm = $itm->parent()->first();
            $parent_id = $itm->parent_id;
        }
        $rec->itmtypes_path = $itmtypes_path;
        //dd(array_reverse($rec->itmtypes_path));
        //dd(array_flip($itmtypes_path));

        return view('itmtypes.edit', compact(['rec', 'subtype', 'spectype', 'usrrights']));
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
            $itmtype = new itmtype();
        } else {
            $itmtype = itmtype::find($id);
        }


        $itmtype->parent_id = $request->get('parent_id');
        $itmtype->name = $request->get('name');
        $itmtype->descript = $request->get('descript');
        $itmtype->active = $request->get('active', 0);
        $itmtype->photourl = $request->get('photourl');
        $itmtype->ordr = $request->get('ordr', 255);
        $itmtype->updated_at = now();
        $itmtype->updated_by = $userid;
        $itmtype->save();

        //сохраним значение преференции "31. Товар считается новинкой в течение N дней после начала продаж"
        objpref::setPrefVal($this->sysobjid, $itmtype->id, 31, $request->get('NewGoodsMaxDays'));

        if (isset($itmtype->parent_id))
            return redirect(route('itmtypes.edit', $itmtype->parent_id))->with('success', "Категория обновлена");
        return redirect('/itmtypes')->with('success', "Категория обновлена");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $route = route('itmtypes.edit', $id);
        if (itmtype::find($id)->childs()->count() > 0)
            return redirect($route)->with(['error' => 'Нельзя удалить - есть подчиненные записи!']);

        $res = itmtype::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route('itmtypes.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            if (isset($res->obj['parent_id']))
                $route = route('itmtypes.edit', $res->obj['parent_id']);
            else
                $route = route('itmtypes.index');
            $sd['success'] = 'Категория была удалена';
        }
        return redirect($route)->with($sd);
    }
}
