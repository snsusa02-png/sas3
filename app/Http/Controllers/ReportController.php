<?php

namespace App\Http\Controllers;

use App\ac;
use App\objtag;
use App\report;
use App\obj_reader;
use App\objfile;
use App\objlog;
use App\sysfunc;
use App\Traits\DeleteFileTrait;
use App\Traits\SearchDataTrait;
use App\User;
use App\usrsysright;
use Illuminate\Http\Request;
use DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Route;

class ReportController extends Controller
{
    use DeleteFileTrait;
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;
        $this->sysobjcode = 'reports'; //Отчеты по системе
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
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.save'),
        );
        if (!$usrrights['read']) {
            return view('home');
        }

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска -------------------------------------------------
        $s_ownorgid = "";
        $s_orgid = "";
        $s_orgname = "";
        $s_name = "";
        $s_buildobjid = "";
        $s_typeid = "";
        $s_docnum = "";
        $s_end_at = "";
        $s_statusid = "";
        $s_orggrpid = "";
        $s_flagtypeid = "";

        if ($request->isMethod('post')) {

            $s_name = $request->get("s_name");
            $s_statusid = $request->get("s_statusid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $this->sysobjcode]);
            session(['search_params' => [
                's_name' => $s_name,
                's_statusid' => $s_statusid,
            ]]);
        } else {
            if (session('search_setname') == $this->sysobjcode) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_name = $params['s_name'] ?? null;
                    $s_statusid = $params['s_statusid'] ?? null;
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_name" => $s_name,
            "s_statusid" => $s_statusid,
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

        if (1 == 1) {
            $sc .= " and (r.public=1
                        or r.created_by={$userid}
                        or ( r.public=0 and exists(select 1 from obj_readers as rdr
                                where rdr.sysobjid={$this->sysobjid} and rdr.objid=r.id and rdr.userid={$userid})
                           )
                )";
        }

        if ($needSearch) {
            if (strlen($s_name) > 0) {
                $sc = $sc . " and concat(ifnull(r.name,' '),' ', ifnull(r.descript,' '))  like '%" . mb_strtoupper($s_name) . "%'";
            }

            if (strlen($s_statusid) > 0) {
                $sc = $sc . " and r.active = '" . $s_statusid . "'";
            }
        }
        // --------------------------------------------------------------------

        $recs = report::from('reports as r')
            ->whereraw($sc)
            ->select('r.*');

        //Сортировка пользователя ----------------------------------------
        //session()->put('sort_params_' . $this->sysobjcode . '.index', []);
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('r.id', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate(20);
        //dd($recs);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;


        $data = new \stdClass();
        //dd($usrrights);

        return view('reports2.index', compact(
            'recs', 'rec0'
            , 'data', 'search_params', 'sort_params'
            , 'usrrights'));
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
            //Значения "по-умолчанию" для новой записи
            $rec = new report([
                'id' => -1,
                'active' => 0,
                //'statusid' => 0,
                'created_by' => $userid,
            ]);
        } else
            $rec = report::find($id);


        if (!isset($rec))
            return redirect(route($this->sysobjcode . '.index'));

        objlog::log_info($this->sysobjid, $rec->id, \Auth::user()->name . ' ознакомился с документом', 3);

        //преобразуем для нормальной работы <INPUT TYPE="DATE"...
        //$contract->begdate = strftime('%Y-%m-%dT%H:%M:%S', strtotime($contract->begdate));
//        if (isset($rec->docdate))
//            $rec->docdate = strftime('%Y-%m-%d', strtotime($rec->docdate));


        if ($rec->id == -1) {
            //для новой записи ---
            $rec->statuses = [
                0 => 'черновик',
                1 => 'доступен для использования',
            ];

        } else {
            //для существующей записи ---
            $rec->statuses = [
                0 => 'черновик',
                1 => 'Доступен для использования',
            ];
        }

        $rec->acs_right_name = sysfunc::find($rec->acs_rightid);
        if (isset($rec->acs_rightid)) {
            $sf = sysfunc::find($rec->acs_rightid);
            if (isset($sf))
                $rec->acs_right_name = $sf->code . ' - ' . $sf->name;
        }

        $rec->acs = ac::lstFor(['active_or_current' => $rec->acsid]);

        $usrrights = $this->setInterfaceRight($id);
        //право изменения категории доступа
        $usrrights['acs.edit'] = User::user_has_acs_cached($userid, $rec->acsid);

        if ($rec->statusid == 0) {

            //Порядок отбора:
            //  1 - buildobjid  - объект
            //  2 - orgid       - поставщик
            //  3 - ownorgid    - получатель

        } elseif ($rec->statusid <> 0) {
            $usrrights['delete'] = false;
        }

        //$data = new \stdClass();
        $rec->users_stat = objlog::from('objlogs as ol')
            ->join('users as u', 'u.id', 'write_by')
            ->select('write_by as userid'
                , db::raw('max(u.name) as user_name')
                , db::raw("count(*) as cnt")
                , db::raw("min(write_at) as min_dt")
                , db::raw("max(write_at) as max_dt")
            )
            ->where(['sysobjid' => 855, 'objid' => $id])
            ->whereRaw("info like 'запрошен%'")
            ->groupby('write_by')
            ->orderby('cnt', 'desc')
            ->orderby('max_dt', 'desc')
            ->get();
//dd($rec->users_stat);

        //обновим статистику открытий для данного пользователя
        obj_reader::addOrUpdateStat($this->sysobjid, $rec->id, $userid);

        return view('reports2.edit', compact('rec', "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $idw
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //проверим текущий статус документа
        $statusid = ($id == -1) ? 0 : report::find($id)->active ?? 0;

        if ($statusid == 0) {
            $messages = [
                'name.required' => 'Укажите тип/название документа',
                'active.required' => 'Укажите текущий статус записи',
            ];

            $rules = [
                "name" => "required",
                "active" => "required",
            ];
        } else {
            $messages = [
                'active.required' => 'Укажите текущий статус записи',
            ];

            $rules = [
                "active" => "required",
            ];
        }

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new report([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = report::find($id);
            $mess = "Запись обновлена";
        }
        if ($statusid == 0) {
            $rec->name = mb_substr($request->get('name'), 0, 160);
            $rec->descript = mb_substr($request->get('descript'), 0, 360);
            $rec->route = $request->get('route');

        }
        //если у пользователя есть право изменения категории доступа
        if (User::user_has_acs_cached($userid, $rec->acsid))
            $rec->acsid = $request->get('acsid', 1) ?? 1;   //1-публичная информация

        $rec->public = $request->get('public', 0) ?? 0;
        $rec->acs_rightid = $request->get('acs_rightid');
        $rec->active = $request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        connectify('success', $rec->info, $mess);

        if ($id == -1 or $rec->statusid <> $statusid) {
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
    public function destroy($id)
    {
        $res = report::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('reports.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'], $res->msg);
        } else {
            $sd['success'] = 'Запись о документе (' . $id . ': '
                . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $pageno = session($this->sysobjcode . '_pageno');
            $route = route($this->sysobjcode . '.index') . '?page=' . $pageno;
            connectify('success', $res->obj['name'], 'Запись удалена.');

            foreach (objfile::where('sysobjid', $this->sysobjid)
                         ->where('objid', $id)->get() as $file) {
                $res = objfile::destroy($file->id);
            }

            objfile::where('sysobjid', $this->sysobjid)
                ->where('objid', $id)
                ->delete();
        }
        return redirect($route)->with($sd);
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function pub_index(Request $request)
    {
        if (\Auth::user()->active == 0)
            return view('home');

        $userid = \Auth::user()->id;

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.create'),
            'edit_report' => usrsysright::isUserHasRightByCode_cached($userid, 'admin-global'),
        );


        //2020-09-28 Меняем концепцию - если у пользователя нет прав на чтение (ВСЕХ записей), то здесь не блокируем,
        //а смотрим дальше по месту - есть ли он в списке читателей для каждого договора

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 1
            //, 's_src_orgid' => ''
            , 's_name' => ''
            , 's_tag' => ''
            , 's_tag_type' => ''
            , 's_tag_val' => ''
            , 's_new4me' => ''
//            , 's_ocl_itmid' => ''
//            , 's_ocl_depid' => ''   //Подразделение организации - через номенклатуру дела
        ];

        $search_params = $this->search_params($request, $param_names);
        //dd($search_params);

        //сформируем условие запроса в БД -----
        $sc = "r.active=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, указанной в записи о типе отчета
        //для того, чтобы работать с ней
        //2 варианта: - Если пользователь имеет доступ к спр-ку ACS, в режиме чтения, то считается,
        //   что он имеет доступ к любой категории информации
        //  - Либо пользователь должен входить в список user_acs для нужной категории информации
        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=r.acsid and uac.userid={$userid})";
        // -----------------------------------------------------------------------------------------------------

        //Если отчет не публичный, то пользователь должен быть включен в список читателей (obj_readers) -----------
        $sc .= " and ( r.public=1 or exists (select 1 from obj_readers rdr where rdr.sysobjid={$this->sysobjid}
                and rdr.objid=r.id and rdr.userid={$userid}) )";
        //---------------------------------------------------------------------------------------------------------

        // Учтем требования отчета к наличию у пользователя определенного права -----------------------------------
        $sc .= " and (r.acs_rightid is null
            or exists(select 1 from usrsysrights as usr where usr.userid={$userid} and usr.sysfuncid=r.acs_rightid
                and usr.active and now() between usr.begdt and ifnull(usr.enddt,now()) ) )";
        //---------------------------------------------------------------------------------------------------------

        $need_search = false;
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;


                if ($item == 's_name') {
                    $sc = $sc . " and concat(ifnull(r.name,' '),' ', ifnull(r.descript,' '))  like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_tag') {
                    if ($val <> '*')
                        $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid={$this->sysobjid} and objid=r.id and ot.tag='" . mb_strtoupper($val) . "')";

                } elseif ($item == 's_tag_type') {
                    $tval = mb_strtoupper($val);
                    $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid={$this->sysobjid}
                     and objid=d.id and ( ot.type='{$tval}' or (ot.type is null and ot.tag='{$tval}')))";

                } elseif ($item == 's_tag_val') {
                    $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid={$this->sysobjid}
                     and objid=d.id and ot.val like '%" . mb_strtoupper($val) . "%')";

                } elseif ($item == 's_new4me') {
                    if ($val == 0)
                        //уже открывал
                        $sc .= " and not exists(select 1 from obj_readers ojr where ojr.sysobjid={$this->sysobjid}
                             and ojr.objid=r.id and ojr.userid={$userid} and ojr.firstread_at is null )";
                    elseif ($val == 1)
                        //еще не открывал
                        $sc .= " and exists(select 1 from obj_readers ojr where ojr.sysobjid={$this->sysobjid}
                             and ojr.objid=r.id and ojr.userid={$userid} and ojr.firstread_at is null )";
                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------

        // --------------------------------------------------------------------
        //dd($need_search, $sc);
        $recs = null;
        $need_search = true;  //поиск даже без заданных параметров
        if ($need_search) {

            $recs = report::from('reports as r')
                ->whereraw($sc)
                ->select('r.*');

            //Базовая сортировка ---------------------------------------------
            $recs = $recs->orderBy('r.use_cnt', 'desc');
            //----------------------------------------------------------------

            //Сортировка пользователя ----------------------------------------
            //session()->put('sort_params_' . $this->sysobjcode . '.index', []);

            $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

            if (isset($sort_params)) {
                foreach ($sort_params as $prm)
                    $recs = $recs->orderByRaw($prm['field'] . " " . $prm['dir']);
            } else {
                $recs = $recs->orderBy('r.id', 'desc');
            }
            //----------------------------------------------------------------

            $recs = $recs->with('tags')->get();

            //убрать записи с несуществующими маршрутами
            $recs = $recs->filter(function ($rec) {
                $route = $rec->route ?? 'reports.rep' . $rec->id;
                return Route::has($route);
            });

            foreach ($recs as $rec) {
                $rec->route = $rec->route ?? 'reports.rep' . $rec->id;
            }

        } else {
            $recs = null;
            $sort_params = null;
        }
        //dd($recs);

        $data = new \stdClass();

        $data->usrrights = $usrrights;

        //Cache::forget("usedtags_{$this->sysobjid}");
        $data->usedtags = Cache::remember("usedtags_{$this->sysobjid}", now()->addMinutes(5)
            , function () use ($userid) {

                // Учтем требования отчета к наличию у пользователя определенного права -----------------------------------
                $sc = " (r.acs_rightid is null
            or exists(select 1 from usrsysrights as usr where usr.userid={$userid} and usr.sysfuncid=r.acs_rightid
                and usr.active and now() between usr.begdt and ifnull(usr.enddt,now()) ) )";
                //---------------------------------------------------------------------------------------------------------
                return objtag::from('objtags as tg')
                    ->join('reports as r', 'r.id', 'tg.objid')
                    ->where('sysobjid', $this->sysobjid)
                    ->whereRaw($sc)
                    ->selectRaw("ifnull(tg.type,tg.tag) as type")
                    ->distinct()
                    ->orderByRaw("ifnull(tg.type,tg.tag)")
                    ->get()
                    ->pluck('type', 'type')
                    ->toArray();
            });

        //Cache::forget("tagtypes_{$this->sysobjid}");
        $data->tagtypes =
            Cache::remember("tagtypes_{$this->sysobjid}", now()->addMinutes(5)
                , function () {
                    return objtag::from('objtags as tg')
                        ->where('sysobjid', $this->sysobjid)
                        //->whereNotNull('type')
                        ->selectRaw("ifnull(tg.type,tg.tag) as type")
                        ->distinct()
                        ->get()
                        ->pluck('type', 'type')
                        ->toArray();
                });

        //dd($data->rep_tagtypes);
        return view('reports2.pub_index', compact(
            'recs', 'data', 'search_params', 'sort_params', 'usrrights'));
    }
}
