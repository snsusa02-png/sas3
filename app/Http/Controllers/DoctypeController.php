<?php

namespace App\Http\Controllers;

use App\ac;
use App\doc;
use App\document;
use App\dt_collector;
use App\objextid;
use App\objfile;
use App\orgdep;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Route;
use App\doctype;
use App\usrsysright;
use Auth;
use DB;
use Cache;

class DoctypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 920;
        $this->sysobjcode = 'doctypes';
    }


    protected function setInterfaceRight($id)
    {
        $userid = Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $this->sysobjcode . ".read");
        $usrrights['create'] = usrsysright::isUserHasRightByCode($userid, $this->sysobjcode . ".create");

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

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode($userid, $this->sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode($userid, $this->sysobjcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode($userid, $this->sysobjcode . '.save'),
        );

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
                $sc = $sc . " and dt.name like '%" . $s_name . "%'";
            }
            if ($s_active != '') {
                $sc = $sc . " and dt.active = '" . $s_active . "'";
            }
        }
        // --------------------------------------------------------------------


        $sort_params = session('sort_params');
        if (isset($sort_params)) {
            $sort_by = $sort_params['field'];
            $sort_dir = $sort_params['dir'];
        } else {
//            $sort_by = DB::raw('ifnull(ordr,999)');
            $sort_by = 'dt.name';
            $sort_dir = 'asc';
        }

        $recs = doctype::from('doctypes as dt');

        $recs = $recs->select('dt.id', 'dt.name', 'dt.active')
            ->whereRaw($sc)
            ->orderBy($sort_by, $sort_dir)
            ->paginate(20);

        $s_actives = [
            1 => 'используется',
            0 => 'не используется',
        ];

        return view($this->sysobjcode . '.index', compact('recs', 'usrrights'
            , 'search_params', 's_actives'));
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
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $parent_id = null)
    {
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read']) return view('home'); // Справочник доступен если у пользователя есть право

        if ($id == -1) {
            //new record
            $rec = new doctype([
                'id' => $id,
                'parent_id' => $parent_id,
            ]);
        } else {
            $rec = doctype::find($id);
        }

        if (!isset($rec))
            return redirect(route('doctypes.index'))->with(['error' => 'Запись не найдена!']);

        $rec->extids = objextid::from('objextids as ei')
            ->join('extsystems as s', 's.id', 'ei.extsysid')
            ->where('sysobjid', $this->sysobjid)
            ->where('objid', $rec->id)
            ->select('ei.id', 's.name as extsysname', 'extid')
            ->orderby('s.name')
            ->get();

        $rec->acs = ac::lstFor(['active_or_current' => $rec->acsid]);

        $rec->collectors = dt_collector::from('dt_collectors as dtc')
            ->join('collectors as c', 'c.id', 'dtc.collectorid')
            ->where('dtc.doctypeid', $rec->id)
            ->select('dtc.id', 'c.name as name', 'dtc.active')
            ->orderby('c.name')
            ->get();

        $rec->doc_samples = document::from('documents as d')
            ->join('orgs as oo', 'oo.id', 'd.ownorgid')
            ->leftjoin('orgs as o', 'o.id', 'd.orgid')
            ->where('d.doctypeid', $rec->id)
            ->select('d.id', 'd.name', 'd.docnum', 'd.docdate'
                , 'oo.name as ownorgname', 'd.dirtypeid', 'd.ownorg_regnum', 'd.ownorg_regdate'
                , 'o.name as orgname', 'd.org_regnum', 'd.org_regdate')
            ->orderby('d.docdate', 'desc')
            ->orderby('d.id', 'desc')
            ->limit(21)
            ->get();
        //dd(($rec->doc_samples));

        $rec->usage_cnt = objfile::where('doctypeid', $rec->id)->count()
            + doc::where('doctypeid', $rec->id)->count();

        return view('doctypes.edit', compact(['rec', 'usrrights']));
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
        ]);
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //new record
            $doctype = new doctype();
        } else {
            $doctype = doctype::find($id);
        }

        $doctype->parent_id = $request->get('parent_id');
        $doctype->name = $request->get('name');
        $doctype->acsid = $request->get('acsid', 1) ?? 1;
        $doctype->active = $request->get('active', 0);
        $doctype->updated_at = now();
        $doctype->updated_by = $userid;
        $doctype->save();

        Cache::forget('doctypes_lstTypes');

        if (isset($doctype->parent_id))
            return redirect(route('doctypes.edit', $doctype->parent_id))->with('success', "Тип документа обновлен");
        else
            return redirect(route('doctypes.index'))->with('success', "Тип документа обновлен");
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = doctype::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route('doctypes.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $route = route('doctypes.index');
            $sd['success'] = 'Запись была удалена';
        }
        return redirect($route)->with($sd);
    }


    static public function list_for(Request $request)
    {
        //2021-07-03 SNS. Обертка для вызова doctype::lstFor

        $result = "";
        try {

            $list = doctype::lstFor([
                'parent_id' => $request->parent_id,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
                'in_documents' => $request->in_documents,
            ]);


            $result = array('doctypes' => $list);
            //$result = $list;

        } catch (\Exception $e) {
            Log::error('doctypes::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

    static public function get_for(Request $request)
    {
        //2021-07-03 SNS. Обертка для вызова doctype::getFor

        $result = "";
        try {

            $list = doctype::getFor([
                'parent_id' => $request->parent_id,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
                'in_documents' => $request->in_documents,
                'name' => $request->name,
            ], [
                'id', 'name'
            ]);


            //$result = array('doctypes' => $list);
            $result = $list;

        } catch (\Exception $e) {
            Log::error('doctypes::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
