<?php

namespace App\Http\Controllers;

use App\budget;
use App\buildobj;
use App\estDoc;
use App\group;
use App\objflag;
use App\objlog;
use App\org;
use App\project;
use App\proj_category;
use App\proj_status;
use App\proj_risk;
use App\proj_milestone;
use App\projbudgetitem;
use App\usrsysright;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Cache;

class ProjectController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 461;
        $this->objcode = 'projects';
    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

//        $usrrights['org_saldos.read'] = false;
//        $usrrights['org_saldos.create'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');

            $usrrights['proj_mailboxes.read'] = $usrrights['read'];
            $usrrights['proj_mailboxes.create'] = $usrrights['create'];
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

        //$usrrights = $this->setInterfaceRight(-1);
        $usrrights = Array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.save'),
        );
        if (!$usrrights['read']) {
            return view('home');
        }

        session([$this->objcode . '_pageno' => $request->page]);

        // - параметры поиска -------------------------------------------------
        $s_name = "";
        $s_inn = "";
        $s_leadname = "";
        $s_statuscode = "";
        $s_orggrpid = "";
        $s_flagtypeid = "";

        if ($request->isMethod('post')) {
            $s_name = $request->get("s_name");
            $s_inn = $request->get("s_inn");
            $s_leadname = $request->get("s_leadname");
            $s_statuscode = $request->get("s_statuscode");
            $s_orggrpid = $request->get("s_orggrpid");
            $s_flagtypeid = $request->get("s_flagtypeid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $this->objcode]);
            session(['search_params' => [
                's_name' => $s_name,
                's_leadname' => $s_leadname,
                's_statuscode' => $s_statuscode,
                's_orggrpid' => $s_orggrpid,
                's_flagtypeid' => $s_flagtypeid,
            ]]);
        } else {
            if (session('search_setname') == $this->objcode) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_name = $params['s_name'] ?? null;
                    $s_leadname = $params['s_leadname'] ?? null;
                    $s_statuscode = $params['s_statuscode'] ?? null;
                    $s_orggrpid = $params['s_orggrpid'] ?? null;
                    $s_flagtypeid = $params['s_flagtypeid'] ?? null;
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_name" => $s_name,
            "s_inn" => $s_inn,
            "s_leadname" => $s_leadname,
            "s_statuscode" => $s_statuscode,
            "s_orggrpid" => $s_orggrpid,
            "s_flagtypeid" => $s_flagtypeid,
        ];
        //dd($search_params);

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
            if (strlen($s_name) > 0) {
                $sc = $sc . " and p.name like '%" . mb_strtoupper($s_name) . "%'";
            }
            if (strlen($s_leadname) > 0) {
                $sc = $sc . " and p.leadusername like '%" . mb_strtoupper($s_leadname) . "%'";
            }
            if (strlen($s_statuscode) > 0) {
                $sc = $sc . " and p.statusid='" . $s_statuscode . "'";
            }

            if (strlen($s_orggrpid) > 0)
                $sc .= " and exists(select 1 from grpitems gl where gl.sysobjid=" . $this->sysobjid
                    . " and gl.objid=p.id and gl.grpid=" . $s_orggrpid . ')';

            if (strlen($s_flagtypeid) > 0)
                $sc .= " and exists(select 1 from objflags f where f.sysobjid=" . $this->sysobjid
                    . " and f.objid=p.id and f.flagtypeid=" . $s_flagtypeid . ')';
        }
        // --------------------------------------------------------------------


        $recs = project::from('projects as p')
            ->whereraw($sc)
            ->select('p.id', 'p.name', 'p.active', 'p.descript', 'p.leadusername'
                , 'p.plnbegdt', 'p.fctbegdt', db::raw("datediff(ifnull(p.fctbegdt,p.plnbegdt),p.plnbegdt) as beg_delta")
                , 'p.plnenddt', 'p.fctenddt', db::raw("datediff(ifnull(p.fctenddt,p.plnenddt),p.plnenddt) as end_delta")
            )
            ->selectRaw("(select group_concat(concat(pms.typeid,'|',pms.statusid,'|', pms.name, '|', ps.name) separator '; ')
                    from proj_milestones as pms
                    join milestone_statuses as ps on ps.id=pms.statusid
                    where pms.projid=p.id order by pms.ordr) as lstMilestones");


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->objcode . '.index');

        //$orgs = $orgs->orderBy('ownmark', 'desc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('p.name', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate(10);

//номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $objgroups = group::lstOrgGroups_cache();

        $usedflags = objflag::lstUsedFlagsForSysObj_cache($this->sysobjid);

//dd($usrrights);

        return view('projects.index', compact('recs', 'rec0'
            , 'objgroups', 'usedflags'
            , 'search_params', 'sort_params'
            , 'usrrights'));
    }

//Поиск
    public function search(Request $request)
    {
        $searchval = "";
        $s_leadnameess = "";
        $s_statuscode = "";

        if ($request->isMethod('post')) {
            $searchval = $request->get("searchval");
            $s_leadnameess = $request->get("s_leadnameess");
            $s_statuscode = $request->get("s_statuscode");

            //сохраним параметры поиска в сессии
            session(['search_setname' => "orgs"]);
            session(['search_params' => [
                'searchval' => $searchval,
                's_leadnameess' => $s_leadnameess,
                's_statuscode' => $s_statuscode,
            ]]);
        } else {
            if (session('search_setname') == "orgs") {
                //dd(session('search_params'));
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $search_params = session('search_params');
                    $searchval = $search_params['searchval'];
                    $s_leadnameess = $search_params['s_leadnameess'];
                    $s_statuscode = $search_params['s_statuscode'];
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        //в поиске не задано ничего, уйдем на index
        if (strlen($searchval . $s_leadnameess . $s_statuscode) == 0) return redirect()->route('projects.index');

        $sc = "1=1";
        if (strlen($searchval) > 0) {
            $sc = $sc . " and p.name like '%" . mb_strtoupper($searchval) . "%'";
        }
        if (strlen($s_leadnameess) > 0) {
            $sc = $sc . " and p.address like '%" . mb_strtoupper($s_leadnameess) . "%'";
        }
        if (strlen($s_statuscode) > 0) {
            $sc = $sc . " and p.statusid='" . $s_statuscode . "'";
        }
        $recs = project::from('projects as p')
            ->whereRaw($sc)
            ->orderBy('name', 'asc')
            /*->toSql();*/
            ->paginate(10);

        $objgroups = group::lstOrgGroups_cache();

        $usedflags = objflag::lstUsedFlagsForSysObj_cache($this->sysobjid);

        $usrrights = $this->setInterfaceRight(-1);
        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
        return view('projects.index', compact('recs', 'rec0'
            , 'objgroups', 'usedflags'
            , 'search_params', 'sort_params'
            , 'usrrights'));
    }


    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $project = project::find($id);
        return view('projects.show', compact('project'));
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
            $rec = new project(
                [
                    'id' => $id,
                    'leaduserid' => $userid,
                    'plnbegdt' => now(),
                ]);

        } else
            $rec = project::find($id);

        if (!isset($rec))
            response . redirect(route('projects.index'));

        $rec->plnbegdt = (isset($rec->plnbegdt)) ? strftime('%Y-%m-%dT%H:%M:%S', strtotime($rec->plnbegdt)) : null;
        $rec->plnenddt = (isset($rec->plnenddt)) ? strftime('%Y-%m-%dT%H:%M:%S', strtotime($rec->plnenddt)) : null;

        $usrrights = $this->setInterfaceRight($id);

        $auxinfo = project::AuxInfo($rec);

        for ($x = 0; $x <= count($auxinfo) - 1; $x++) {
            if ($auxinfo[$x]["reccount"] > 0 and $usrrights['delete'])
                $usrrights['delete'] = false;
        }

        $usrrights['buildobjs.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'buildobjs.create');
        $usrrights['buildobjs.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'buildobjs.read');
        $usrrights['budgets.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'budgets.read');

        $rec->initorgs = org::lstBuyers();

        $rec->categories = proj_category::lstActive();
        $rec->statuses = proj_status::lstActive();

        //объекты проекта
        $rec->buildobjs = buildobj::lstForProject($rec->id);

        $rec->budgets = budget::lstForProject($rec->id);

        $ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);

        return view('projects.edit', compact('rec', 'auxinfo', "ObjFlags", "usrrights"));
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
            'name.required' => 'Укажите название контрагента',
            'initorgid.required' => 'Укажите Заказчика',
        ];
        $rules = [
            "name" => "required|max:160",
            "initorgid" => "required",
        ];

        $request->validate($rules, $messages);


        $request->validate([
            "name" => "required",
            "initorgid" => "required",
        ]);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new project([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = project::find($id);
            $mess = "Запись обновлена";
        }
        $rec->name = $request->get('name');
        $rec->categoryid = $request->get('categoryid');
        $rec->initorgid = $request->get('initorgid');
        $rec->descript = $request->get('descript');
        $rec->leadusername = $request->get('leadusername');
        $rec->plnbegdt = $request->get('plnbegdt');
        $rec->plnenddt = $request->get('plnenddt');
        //dd($request->get('plnenddt'), $rec->plnenddt);
        $rec->statusid = $request->get('statusid', 1);
        //$rec->active = $request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        Cache::forget('projects_lstActive_0');

        connectify('success', $rec->name, $mess);
        if ($id == -1) {
            return redirect(route($this->objcode . '.edit', $rec->id));
        } else {
            $pageno = session($this->objcode . '_pageno');
            return redirect(route($this->objcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
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
        $res = project::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('projects.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'], $res->msg);
        } else {
            $sd['success'] = 'Запись о проекте (' . $id . ': '
                . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $pageno = session($this->objcode . '_pageno');
            $route = route($this->objcode . '.index') . '?page=' . $pageno;
            connectify('success', $res->obj['name'], 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    /**
     * Список рисков проекта
     */
    public function proj_risks($id)
    {
        $project = project::find($id);
        if (isset($project)) {
            $recs = proj_risk::from('proj_risks as r')
                ->where('r.projid', $project->id)
                ->select('r.id', 'r.name', 'r.descript', 'severity', 'chance', 'weight', 'decision')
                ->orderby('weight', 'desc')
                ->get();
            return view('projects.proj_risks', compact('project', 'recs'));
        } else {
            $pageno = session($this->objcode . '_pageno');
            return redirect(route($this->objcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
        }
    }

    //Вехи проекта
    public function proj_milestones($id)
    {
        $project = project::find($id);
        if (isset($project)) {
            $recs = proj_milestone::from('proj_milestones as pm')
                ->leftjoin('proj_statuses as ps', 'ps.id', 'pm.statusid')
                ->where('pm.projid', $project->id)
                ->select('pm.id', 'pm.name', 'pm.descript', 'pm.active', 'plnbegdt', 'plnenddt', 'fctbegdt', 'fctenddt'
                    , 'ps.name as statusname')
                ->orderby('pm.ordr')
                ->get();
            return view('projects.proj_milestones', compact('project', 'recs'));
        } else {
            $pageno = session($this->objcode . '_pageno');
            return redirect(route($this->objcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
        }
    }

    public function proj_estdocs($id)
    {
        $project = project::findOrFail($id);
        if (isset($project)) {
            $recs = estDoc::from('estdocs as d')
                ->where('d.buildobjid', $project->id)
                ->select('d.id', 'd.name', 'd.description', 'd.drct_sum', 'd.nacl_sum', 'd.sp_sum', 'd.docsum', 'created_at')
                ->get();
            return view('projects.proj_estdocs', compact('project', 'recs'));
        } else {
            $pageno = session($this->objcode . '_pageno');
            return redirect(route($this->objcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
        }
    }

    /**
     * Список смет по проекту
     */
    public function proj_budgetitems($id)
    {
        $project = project::findOrFail($id);
        if (isset($project)) {
            // $recs = projbudgetitem::from('projbudgetitems as pbi')
            // ->leftjoin('pbi_opers as op', function ($join) {
            //     $join->on('op.itmid', '=', 'pbi.id')
            //         ->where("op.active", 1)
            //         ->selectRaw('sum(if(dir=1, opersum,0)) as inpsum, sum(if(dir=-1, opersum,0)) as outsum')
            //         ->groupby('op.itmid');
            // })
            //
            //     ->where('pbi.projectid', $project->id)
            //     ->select('pbi.id', 'pbi.name', 'pbi.descript', 'pbi.limsum', 'pbi.cursum, op.inpsum,op.outsum')
            //     ->selectRaw('if(dir=1, )')
            //     ->get();
            //
            $recs = DB::select('select op.*, bi.*
                from projbudgetitems as bi
                left join (
                SELECT itmid
                , sum(if(dir=1, opersum,0)) as inpsum
                , sum(if(dir=-1, opersum,0)) as outsum
                 FROM basco.pbi_opers
                 group by itmid ) as op
                 on op.itmid=bi.id');

            //dd($recs);
            return view('projects.proj_budgetitms', compact('project', 'recs'));
        } else {
            $pageno = session($this->objcode . '_pageno');
            return redirect(route($this->objcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
        }
    }


    static public function listbuildobjs(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $projectid = $request->projectid;
            $list = buildobj::where('projectid', $projectid)
                ->where('active', 1)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()->pluck('name', 'id')->toArray();

            $result = array('buildobjs' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);
    }

}
