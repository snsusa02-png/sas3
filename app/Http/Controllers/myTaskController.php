<?php

namespace App\Http\Controllers;

use App\document;
use App\org_curator;
use App\proj_category;
use App\sysobj;
use App\task;
use App\task_user;
use App\task_reptype;
use App\tasktype;
use App\task_report;
use App\Jobs\SendNotify;
use App\obj_link;
use App\obj_reader;
use App\objflag;
use App\objlog;
use App\objtag;
use App\contracttype;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\User;
use App\user_notice;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class myTaskController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 961;
        $this->sysobjcode = 'tasks';
        $this->objcode = $this->sysobjcode;
    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = true; //usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read');
        $usrrights['create'] = true; //usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['take'] = false;
        $usrrights['breakwork'] = false;
        $usrrights['adminbreakwork'] = false;
        $usrrights['complete'] = false;
        $usrrights['back2work'] = false;
        $usrrights['task_reports.create'] = false;
        $usrrights['link_tasks'] = false;


        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
            $usrrights['link_tasks'] = usrsysright::isUserHasRightByCode_cached($userid, 'tasks.create');

            $usrrights['task_reports.create'] = $usrrights['save'];
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
        $sysobjid = $this->sysobjid;

        $usrrights = array(
            'read' => true,
            'create' => true,
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.save'),
        );

//        if (!$usrrights['read']) {
//            return view('home');
//        }
        //2020-09-28 Меняем концепцию - если у пользователя нет прав на чтение (ВСЕХ записей), то здесь не блокируем,
        //а смотрим дальше по месту - есть ли он в списке читателей индивидуально для каждого события

        session([$this->objcode . '_pageno' => $request->page]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 15
            , 's_statusid' => ''
            , 's_plnbegdate' => ''
            , 's_name' => ''
            , 's_tag' => ''
            , 's_tag_type' => ''
            , 's_tag_val' => ''
            , 's_task_userid' => ''
            , 's_user_roleid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);
        //dd($search_params);

        $sc = "1=1 ";
        $need_search = false;
        if (!$usrrights['read']) {
            $sc .= ' and ( e.public_lvl=2 or e.inituserid=' . $userid . '
                or exists (select 1 from task_users r where r.taskid=e.id and userid=' . $userid . ') )';
        }

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;

                if ($item == 's_name') {
                    $find = explode(" ", $val);
                    if (count($find) > 0) {
                        $sc .= ' and (1=1';
                        foreach ($find as $f) {
                            $sc .= " and concat(ifnull(e.name,' '),' ', ifnull(e.descript,' ')) like '%" . $f . "%'";
                        }
                        $sc .= ')';
                    }

                } elseif ($item == 's_plnbegdate') {
                    $sc = $sc . " and date(e.plnbegdt) = '" . $val . "'";

                } elseif ($item == 's_statusid') {
                    if ($val == 0)  //предстоят
                        $sc = $sc . " and e.plnbegdt >= curdate()";
                    elseif ($val == 2)  //сегодня
                        $sc = $sc . " and date(e.plnbegdt) = curdate()";
                    elseif ($val == 8)  //прошли
                        $sc = $sc . " and e.plnenddt < now()";

                } elseif ($item == 's_task_userid') {
                        $sc .= " and exists (select 1 from task_users as tu where tu.taskid = e.id and tu.userid={$val})";

                } elseif ($item == 's_user_roleid') {
                    if ($val == 1)
                        $sc .= " and e.inituserid = {$userid}";
                    else
                        $sc = $sc . " and exists (select 1 from task_users as tu where tu.taskid = e.id
                        and tu.roletypeid={$val} and tu.userid={$userid})";

                } elseif ($item == 's_tag') {
                    $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid={$this->sysobjid}
                                                and objid=e.id and ot.tag='" . mb_strtoupper($val) . "')";

                } elseif ($item == 's_tag_type') {
                    $tval = mb_strtoupper($val);
                    $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid={$this->sysobjid}
                     and objid=e.id and ( ot.type='{$tval}' or (ot.type is null and ot.tag='{$tval}')))";

                } elseif ($item == 's_tag_val') {
                    $sc = $sc . " and exists (select 1 from objtags as ot where ot.sysobjid={$this->sysobjid}
                     and objid=e.id and ot.val like '%" . mb_strtoupper($val) . "%')";

                }
            }
        }
        //var_dump($sc);
        //var_dump($search_params);
        // --------------------------------------------------------------------


        $recs = task::from('tasks as e')
            ->Join('users as iu', function ($j) {
                $j->on('iu.id', 'e.inituserid');
            })
            ->whereraw($sc)
            ->select('e.id', 'e.name', 'e.descript', 'e.active'
                , 'e.plnbegdt', 'e.plnenddt' //, 'e.place'
                , 'e.statusid', 'e.progress'
                , 'iu.name as inituser_name'
            )
            ->with('tags');

        //Базовая сортировка ---------------------------------------------
        //$recs = $recs->orderBy('e.plnbegdt', 'asc');
        //----------------------------------------------------------------

        //Сортировка пользователя ----------------------------------------
        //session()->put('sort_params_' . $this->objcode . '.index', []);

        $sort_params = session('sort_params_' . $this->objcode . '.index');

        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('e.plnbegdt', 'desc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 15);
        //dd($recs);

        $data = new \stdClass();

        $data->sysobj = $this->sysobjid;
        $data->exe_statuses = task::exe_statuses();
        $data->user_roles = task::user_roles($userid);
        $data->task_users = task::task_users($userid);  //пользователи в задачах, которые доступны текущему пользователю

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $objgroups = []; //group::lstOrgGroups_cache();

        $data->regnumstatuses = [0 => 'не присвоен', 1 => 'присвоен'];

//      $usedtypes = contracttype::usedTypes();

        $data->usedtags = task::usedTags();

        $data->end_variants = [
            0 => 'истек',
            30 => 'истечет в ближайшие 30 дней',
            60 => 'истечет в ближайшие 60 дней',
            90 => 'истечет в ближайшие 90 дней',
            120 => 'истечет в ближайшие 120 дней',
        ];
        $data->statuses = [
            0 => 'предстоят',
            2 => 'сегодня',
            8 => 'прошли',
        ];

        return view('tasks.index', compact(
            'recs', 'rec0'
            , 'data'
            , 'search_params', 'sort_params'
            , 'usrrights'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request)
    {
        //dd($request, $sysobjid,$objid);
        return $this->edit($request, -1);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit(Request $request, $id)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {

            $srcsysobjid = $request->get('srcsysobjid');
            $srcobjid = $request->get('srcobjid');

            //если задача связана с контрагентом, то подтянем куратора как исполнителя
            if ($srcsysobjid == 111) {
                $exeuserid = org_curator::curOrgCurator($srcobjid);
                $exeuser_name = User::getFIO($exeuserid);
            }

            //Значения "по-умолчанию" для новой записи
            $rec = new task([
                'id' => -1,
                'srcsysobjid' => $srcsysobjid,
                'srcobjid' => $srcobjid,
                'inituserid' => $userid,
                'exeuserid' => $exeuserid ?? null,
                'exeuser_name' => $exeuser_name ?? null,
                'plnbegdt' => now(),
                'public_lvl' => 0,
                'active' => 1,
                'created_by' => $userid,
            ]);
        } else {
            $rec = task::find($id);
        }

        if (!isset($rec))
            return redirect(route($this->objcode . '.index'));


        //Права -----------------------------------------------------------------------------------
        $usrrights = $this->setInterfaceRight($rec->id);

        //доступ все - к общедоступным записям. Автору - к своим
        $usrrights['read'] = ($rec->public or ($rec->inituserid == $userid));

        $isInitiator = ($rec->inituserid == $userid);
        $isRegistrator = ($rec->created_by == $userid);
        $isExecutor = ($rec->exeuserid == $userid);
        $inWork = (!is_null($rec->fctbegdt) and is_null($rec->fctenddt) and !is_null($rec->exeuserid));
        $isComplete = (!is_null($rec->fctenddt) and $rec->progress = 100);

        //Изменять задачу может или ее Инициатор, или Регистратор - но если задача еще не выполняется
        if (($isInitiator or $isRegistrator) and !$inWork and !$isComplete) {
            $usrrights['save'] = true;
            if ($id <> -1)
                $usrrights['delete'] = true;
        } else {
            $usrrights['save'] = false;
            $usrrights['delete'] = false;
        }

        //Право взятия задачи в работу / Отпускания
        if (is_null($rec->exeuserid)) {
            $pln_executors = task_user::pln_executors($rec->id);
            $usrrights['take'] = in_array($userid, $pln_executors);
        } else {
            $usrrights['breakwork'] = ($inWork and $isExecutor); //если текущий исполнитель это текущий пользователь
            $usrrights['adminbreakwork'] = ($inWork and !$usrrights['breakwork'] and ($isInitiator or $isRegistrator));

            $usrrights['complete'] = ($rec->id <> -1 and !$isComplete and ($rec->exeuserid == $userid or $isInitiator or $isRegistrator));
        }

        if ($isExecutor) {
            $usrrights['task_reports.create'] = true;
        }
        //-----------------------------------------------------------------------------------------

        if ($usrrights['save']) {
            //преобразуем для нормальной работы <INPUT TYPE="DATE"...
            if (isset($rec->plnbegdt))
                //$rec->plnbegdt = strftime('%Y-%m-%dT%H:%M:%S', strtotime($rec->plnbegdt));
                $rec->plnbegdt = strftime('%Y-%m-%dT%H:%M', strtotime($rec->plnbegdt));
            if (isset($rec->plnenddt))
                $rec->plnenddt = strftime('%Y-%m-%dT%H:%M:%S', strtotime($rec->plnenddt));
        }

        $rec->users = task_user::where('taskid', $rec->id)->get();

        $rec->reptypes = task_reptype::lstActive();
        $rec->reports = task_report::where('taskid', $rec->id)->get();

        $rec->categories = proj_category::lstActive();

        $rec->public_lvls = task::public_lvls(); //[0 => 'личная запись', 1 => 'видны дата/время', 2 => 'публичная'];

        if ($rec->id <> -1)
            $rec->statuses = task::exe_statuses();  //[1 => 'в ожидании', 2 => 'выполняется', 3 => 'выполнено', 4 => 'отменено'];

        $rec->tags = objtag::lstTags($this->sysobjid, $id);

        //$rec->exe_statuses = task::exe_statuses();

        //инфо по объекту-источнику задачи
        $rec->src_info = null;
        $rec->src_url = null;
        if (isset($rec->srcsysobjid) and isset($rec->srcobjid)) {

            $srcsysobj = sysobj::find($rec->srcsysobjid);

            $model = $srcsysobj->model_class;
            if (isset($model)) {
                $model = '\App\\' . $model;
                $src_obj = $model::find($rec->srcobjid);
                $rec->src_info = $src_obj->info;
                $rec->src_url = route($srcsysobj->code . '.edit', $rec->srcobjid);
                //dd($srcsysobj, $rec->src_info, $rec->src_url);

            } else {

                if ($rec->srcsysobjid == 1701) {
                    $src_obj = document::find($rec->srcobjid);
                    $rec->src_info = 'документ: ' . $src_obj->name . ' №' . $src_obj->docnum;
                    $rec->src_url = route('documents.edit', $rec->srcobjid);

                } elseif ($rec->srcsysobjid == 961) {
                    $src_obj = task::find($rec->srcobjid);
                    $rec->src_info = $src_obj->info;
                    $rec->src_url = route('tasks.edit', $rec->srcobjid);

                }
            }
        }

        $data = new \stdClass();
        $data->sysobjid = $this->sysobjid;

        //обновим статистику открытий для данного пользователя
        obj_reader::addOrUpdateStat($this->sysobjid, $rec->id, $userid);

        return view('tasks.edit', compact('rec', "usrrights", 'data'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        $messages = [
            'inituserid.required' => 'Укажите инициатора',
            'name.required' => 'Укажите суть задачи',
            'descript.required' => 'Опишите задачу',
            'plnbegdt.required' => 'Укажите время начала',
        ];

        $rules = [
            //'name' => 'required',
            //'plnbegdt' => 'required',
            'inituserid' => 'required',
            'descript' => 'required',
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $mess = "";
        if ($id == -1) {
            $rec = new task([
                "inituserid" => $userid,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = task::find($id);
            $mess = "Запись обновлена";
        }

        //защита от действий пользователя без прав
        $usrrights = $this->setInterfaceRight($rec->id);
        $usrrights['save'] = ($usrrights['save'] and $rec->inituserid == $userid);
        if (!$usrrights['save'] ?? false)
            return redirect(route($this->objcode . ' . index'));


        //Привязка задачи к объекту ИС ------------
        $rec->srcsysobjid = $request->get('srcsysobjid');
        $rec->srcobjid = $request->get('srcobjid');
        if (isset($rec->srcsysobjid) and isset($rec->srcobjid))
            $src_model = sysobj::find($rec->srcsysobjid)->model_class;
        else
            $src_model = null;
        if (isset($src_model)) {
            $src_model = "\App\\" . $src_model;
            $rec->srcobjinfo = $src_model::find($rec->srcobjid)->info ?? '';
        } else
            $rec->srcobjinfo = '';
        //-----------------------------------------

        $rec->inituserid = $request->get('inituserid');

        //$rec->name = mb_substr($request->get('name'), 0, 160);
        $rec->name = mb_substr($request->get('descript'), 0, 160);
        $rec->descript = mb_substr($request->get('descript'), 0, 300);

        $rec->plnbegdt = $request->get('plnbegdt');
        $rec->plnenddt = $request->get('plnenddt') ?? $rec->plnbegdt;
        //$rec->place = mb_substr($request->get('place'), 0, 160);
        $rec->categoryid = $request->get('categoryid');
        $rec->statusid = $request->get('statusid');
        $rec->progress = $request->get('progress');
        //$rec->notes = mb_substr($request->get('notes'), 0, 300);
        $rec->public_lvl = $request->get('public_lvl', 0);
        $rec->active = 1;//($rec->statusid == 0) ? 0 : 1;

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        //connectify('success', $rec->name, $mess);


        // Сохранение тэгов -----------------------------------------------------------------
        //dd($request->get('tags'));
        objtag::attach($this->sysobjid, $rec->id, $request->get('tags'));
        //-----------------------------------------------------------------------------------

        // Привязка к объекту ---------------------------------------------------------------
//        $buildobjid = $request->get('buildobjid');
//        obj_link::addOrUpdateSingle($this->sysobjid, $rec->id, 466, $buildobjid);
        //-----------------------------------------------------------------------------------

        // для новой записи -------------------------------------
        if ($id == -1) {
            /*
            //для однообразия (поиска и т.п.), добавим инициатора в список пользователей по задаче
            task_user::addOrUpdate(
                ['taskid' => $rec->id,
                    'userid' => $rec->inituserid,
                    'roletypeid' => 1   //инициатор
                ]
                , [
                'active' => 1,
                'updated_by' => $userid,
                'updated_at' => now(),
            ]);
            */

            // если не указан исполнитель, то сделаем им инициатора
            $exeuserid = $request->get('exeuserid');
            task_user::addOrUpdate(
                ['taskid' => $rec->id,
                    'userid' => $exeuserid ?? $rec->inituserid,
                    'roletypeid' => 7   //исполнитель
                ]
                , [
                'active' => 1,
                'updated_by' => $userid,
                'updated_at' => now(),
            ]);

        }
        //-----------------------------------------------------------------------------------

        //Дополнительные действия при сохранении изменений
        task::on_update($rec);


        $retURL = $request->get('retURL');
        if (isset($retURL))
            return redirect($retURL);

        if ($id == -1) {
            return redirect(route($this->objcode . ' . edit', $rec->id));
        } else {
            $pageno = session($this->objcode . '_pageno');
            return redirect(route($this->objcode . ' . index') . ' ? page = ' . $pageno . '#' . $rec->id);
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
        $res = task::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('tasks.edit', $id);
            $sd["error"] = $res->msg;
            //connectify('error', $res->obj['name'], $res->msg);
        } else {
            $sd['success'] = 'Запись о событии (' . $id . ': '
                . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $pageno = session($this->objcode . '_pageno');
            $route = route($this->objcode . '.index') . '?page=' . $pageno;
            //connectify('success', $res->obj['name'], 'Запись удалена.');

            //Выполним действия после удаления записи -----------------------------------------------
            task::on_delete($res->rec);
            //---------------------------------------------------------------------------------------

        }
        return redirect($route)->with($sd);
    }


    static public function listtasks(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $ownorgid = $request->ownorgid;
            $orgid = $request->orgid;
            $list = task::from("tasks as c")
                ->join("orgs as oo", 'oo.id', "e . ownorgid")
                ->where('e.ownorgid', $ownorgid);
            if (isset($orgid))
                $list = $list->where('e.orgid', $orgid);

            $list = $list->where('e.active', 1)
                ->select('e.id', 'e.name as tname')
                ->orderBy('tname')
                ->get()->pluck('tname', 'id')->toArray();

            $result = array('tasks' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);

    }


    public
    function notify_mustreaders(Request $request, $id)
    {

        $userid = \Auth::user()->id;

        $ret_url = route($this->objcode . '.edit', $id);    //для возврата
        $ref_url = route($this->objcode . '.edit', $id);  // для уведомления

        //objlog::log_info($this->sysobjid, $id, 'Запрос отправки уведомления' . $ret_url, 5);

        $tasktypecode = $this->objcode . '.mustread';
        $task = tasktype::where('code', $tasktypecode)->first();

        if (isset($task) and $task->active) {

            $tasktypeid = $task->id;  //1895;


            $rcpts = obj_reader::from('obj_readers as r')
                ->join('users as u', 'u.id', 'r.userid')
                ->where('sysobjid', $this->sysobjid)
                ->where('objid', $id)
                ->where('mustread', 1)//обязательное прочтение
                ->whereNull('firstread_at')// тем, кто еще не заходил/ не открывал
                ->select('r.userid', 'u.lname', 'u.fname', 'u.mname', 'u.email')
                ->get();
//        dd($rcpts);

            if (count($rcpts) > 0) {

                //сформируем сообщение
                $obj = task::find($id);

                $subj = "Уведомление о документе(" . $obj->name . ': ' . $obj->docnum . ' / ' . $obj->docdate . ")";


                $lstrcpts = ''; //список персон которым отправлено уведомление
                foreach ($rcpts as $rcpt) {
                    if (isset($rcpt)) {
                        $email = $rcpt->email;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                            //для журнала сформируем список получателей
                            $lstrcpts .= ' ' . $rcpt->lname
                                . ' ' . mb_substr($rcpt->fname, 0, 1) . '.'
                                . mb_substr($rcpt->mname, 0, 1) . '. (' . $email . ');';

                            //$email = 'shevchenko.s@basko.su';
                            //$email = 'snsusa02@gmail.com';

                            $msg = "Здравствуйте, " . $rcpt->fname . " " . $rcpt->mname . "!"
                                . " < br>"
                                . " < br>Вам необходимо ознакомиться с документом: <b > " . $obj->info . "</b > "
                                . "<br ><hr > "
                                . " <a href = '" . $ref_url . "' > Перейти к документу </a > ";
                            //dd($subj, $msg);
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        }

                        if (isset($rcpt->userid)) {

                            $subj = "Новый документ";
                            $msg = $obj->info;
                            user_notice::addOrUpdate($tasktypeid, $ref_url, $rcpt->userid, $subj, $msg, now(), null, $userid);
                        }
                    }
                }

                objlog::log_info($this->sysobjid, $id, 'Уведомление о необходимости ознакомления отправлено: ' . $lstrcpts, 5);
//            dd($meeting, $rcpts, $lstrcpts);
            } else {
                objlog::log_info($this->sysobjid, $id, 'Нет кандидатов для отправки уведомления - либо уже есть отметка об ознакомлении, либо у участника не указана ЭП', 3);
            }


            //убрать из списка уведомлений те, что относятся к данному документу и где пользователь уже ознакомился
            $rcpts = obj_reader::from('obj_readers as r')
                ->where('sysobjid', $tasktypeid)
                ->where('objid', $id)
                ->where('mustread', 1)//обязательное прочтение
                ->whereNotNull('firstread_at')// тем, кто еще не заходил/ не открывал
                ->select('r.userid')
                ->get();

            if (count($rcpts) > 0) {
                foreach ($rcpts as $rcpt) {
                    user_notice::Remove($tasktypeid, $ref_url, $rcpt->userid);
                }
            }

        }

        return redirect($ret_url);
    }

    function notify()
    {
        task::make_notifies();

        return redirect(route("tasks . index"))->with(['success' => 'ok']);
    }


    public
    function take($taskid)
    {
        $userid = \Auth::user()->id;

        $rec = task::find($taskid);

        if (!isset($rec))
            return redirect(route($this->sysobjcode . '.index'));

        if (isset($rec->exeuserid))
            return redirect(route($this->sysobjcode . '.edit', $taskid))->with('error', 'Задача уже взята в работу!');

        $pln_executors = task_user::pln_executors($rec->id);

        if (!in_array($userid, $pln_executors)) {
            return redirect(route($this->sysobjcode . '.edit', $taskid))->with('error', 'У Вас нет полномочий для выполнения этой задачи!');
        }

        $rec->exeuserid = $userid;
        $rec->fctbegdt = $rec->fctbegdt ?? now();
        $rec->statusid = 2; //выполняется
        $rec->save();

        $msg = "Задача взята в работу";
        //connectify('success', '', $msg);
        objlog::log_info($this->sysobjid, $rec->id, $msg, 3);

        return redirect(route($this->sysobjcode . '.edit', $taskid));

    }

    public
    function breakwork($taskid)
    {
        $rec = task::find($taskid);
        if (!isset($rec))
            return redirect(route($this->sysobjcode . '.index'))->with('error', 'Задача не найдена!');

        $userid = \Auth::user()->id;

        $isInitiator = ($rec->inituserid == $userid);
        $isRegistrator = ($rec->created_by == $userid);
        $isExecutor = ($rec->exeuserid == $userid);
        $inWork = (!is_null($rec->fctbegdt) and is_null($rec->fctenddt) and !is_null($rec->exeuserid));
        $isComplete = (!is_null($rec->fctenddt) and $rec->progress = 100);

        if (!$isExecutor and !($isInitiator or $isRegistrator))
            return redirect(route($this->sysobjcode . '.edit', $taskid))->with('error', 'Вы не работаете с этой задачей!');

        $rec->exeuserid = null;
        $rec->statusid = 1; //в ожидании
        $rec->save();

        //connectify('success', '', 'Приостановлена работа по задаче.');
        $msg = "Работа с задачей приостановлена";
        objlog::log_info($this->sysobjid, $rec->id, $msg, 3);

        return redirect(route($this->sysobjcode . '.edit', $taskid));

    }

}
