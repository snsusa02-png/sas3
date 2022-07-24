<?php

namespace App\Http\Controllers;

use App\ac;
use App\obj_reader;
use App\objfile;
use App\objlog;
use App\task_report;
use App\task;
use App\sysobj;
use App\task_user;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use App\Events\notifyEvent;

class TaskReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1712;
        $this->sysobjcode = 'task_reports';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

        $this->class = 'App\task_report';
    }

    /*
     * Установка прав пользователя
     */
    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        if (\Auth::user()->active == 0)
            return view('home');

        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['make_template'] = false;        //Право создать шаблон на основе данных теущей записи

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
            $usrrights['make_template'] = true;
        }

        return $usrrights;
    }

    public function create(Request $request, $taskid = null)
    {
        return $this->edit($request, -1, $taskid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $taskid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {


            $task = task::find($taskid);
            if (!isset($task))
                return redirect(route('tasks.index'))->with(['error' => 'Задача не найдена!']);

            //создавать отчет по задаче может только пользователь, который указан исполнителем в task_users - roletypeid=7
            $cnt = task_user::where(['taskid' => $taskid, 'userid' => $userid, 'roletypeid' => 7])->count();
            //$isExecutor = ($task->exeuserid == $userid);  // уже не так
            $isExecutor = !($cnt == 0);
            if (!$isExecutor)
                return redirect(route('tasks.edit', $taskid))->with(['error' => 'Вы не можете отчитываться по данной задаче, так как не назначены исполнителем!']);

            //определим самый свежий отчет по этой задаче
            $last_rep = task_report::where('taskid', $taskid)->orderBy('wrkenddt', 'desc')->first();
            //начало отчетного периода или равно окончанию отчетного периода предыдущего отчета, либо факт. началу работ по задаче
            $wrkbegdt = (isset($last_rep)) ? $last_rep->wrkenddt : $task->fctbegdt ?? $task->plnbegdt ?? $task->created_at;
            //dd($wrkbegdt);

            $rec = new task_report([
                'id' => -1,
                'taskid' => $taskid,
                'userid' => $userid,
                'wrkbegdt' => $wrkbegdt,
                'wrkenddt' => now(),
                'created_by' => \Auth::user()->id,
            ]);
        } else {

            $rec = task_report::find($id);

            if (!isset($rec))
                return redirect(route('tasks.index'))->with(['error' => 'Отчет не найден!']);

            if ($rec->created_by <> $userid)
                return redirect(route('tasks.index'))->with(['error' => 'Вы не можете редактировать чужой отчет!']);

            //objlog::log_info($this->sysobjid, $rec->id, \Auth::user()->name . ' ознакомился с замечанием', 3);
        }


        //преобразуем для нормальной работы <INPUT TYPE="DATE"...
        //$rec->begdt = strftime('%Y-%m-%dT%H:%M:%S', strtotime($rec->begdt));
        //$rec->enddt = strftime('%Y-%m-%dT%H:%M:%S', strtotime($rec->enddt));
        //$rec->wrkbegdt = (isset($rec->wrkbegdt)) ? strftime('%H:%M', strtotime($rec->wrkbegdt)) : '';
        //$rec->wrkenddt = (isset($rec->wrkenddt)) ? strftime('%H:%M', strtotime($rec->wrkenddt)) : '';

        $rec->wrkbegdt = strftime('%Y-%m-%dT%H:%M', strtotime($rec->wrkbegdt));
        $rec->wrkenddt = strftime('%Y-%m-%dT%H:%M', strtotime($rec->wrkenddt));
        $rec->max_dt = strftime('%Y-%m-%dT%H:%M', strtotime(now()->format('Y-m-d H:i')));

        $usrrights = $this->setInterfaceRight($id);
        $usrrights['save'] = true;


        //изменять отчет может только тот, пользователь, который создал этот отчет

        //Скорректируем права с учетом открытости/закрытости документа ---------------------------------
        if ($rec->task->statusid != 0) {
            // если Документ находится не в режиме редактирования, то запрещаем любые изменения всем.
//            $usrrights['create'] = $usrrights['save'] = $usrrights['delete'] = false;
        }
        //----------------------------------------------------------------------------------------------

//        //право изменения категории доступа
//        $usrrights['acs.edit'] = User::user_has_acs_cached($userid, $rec->acsid??1);

//        $rec->acs = ac::lstFor(['active_or_current' => $rec->acsid]);


        //обновим статистику открытий для данного пользователя
        obj_reader::addOrUpdateStat($this->sysobjid, $rec->id, $userid);

        return view('task_reports.edit', compact('rec', "usrrights"));
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
            'taskid.required' => 'Не указан документ',
            'staffid.required' => 'Укажите сотрудника',
            'begtime.required' => 'Укажите время начала работы',
            'endtime.after' => 'Время окончания работы должно быть позже начала работ',
            'progress.required' => 'Оцените процент выполнения залачи 0-100%',
            //'chkreport.max' => 'Количество символов в поле "Описание" не может превышать 600',
            //'itmtypeid.required' => 'Укажите тип записи',
            //'docdate.before' => 'Начало работ не может быть в будущем',
        ];

        $rules = [
            "taskid" => "required",
            "wrkbegdt" => "required",
            "wrkenddt" => "required|after:wrkbegdt",
            "report" => "required",
            "progress" => "required",

            //"chkreport" => "required|string|max:600",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $taskid = $request->taskid;
        $task = task::find($taskid);
        if (!isset($task))
            return redirect(route('tasks.index'))->with(['error' => 'Задача не найдена!']);


        $mess = "";
        if ($id == -1) {

            $rec = new task_report([
                "taskid" => $taskid,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);

            $mess = "Запись создана";
        } else {
            $rec = task_report::find($id);
            $mess = "Запись обновлена";
        }

        $request->validate($rules, $messages);

        //-------------------------------------------------------------------------------

        $rec->report = mb_substr($request->get('report'), 0, 300);

        $rec->wrkbegdt = $request->get('wrkbegdt');
        $rec->wrkenddt = $request->get('wrkenddt');
        $rec->progress = $request->get('progress');

        //$rec->active = $request->get('active', 0);
        $rec->active = 1;

        $rec->updated_by = $userid;
        $rec->updated_at = now();

        //dd($rec);
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        //connectify('success', $rec->chkreport, $mess);

        if ($rec->active == 1)
            event(new notifyEvent('task_reports.prepared', $this->sysobjid, $rec->id, $userid));


        //обновим статус исполнения задачи
        $last_rep = task_report::where('taskid', $rec->taskid)->orderby('wrkenddt', 'desc')->first();
        if ($last_rep->progress == 100) {
            $statusid = 3;
            $fctenddt = now();
        } else {
            $statusid = 2;
            $fctenddt = null;
        }

        task::where('id', $rec->taskid)->update(['progress' => $last_rep->progress
            , 'statusid' => $statusid
            , 'fctenddt' => $fctenddt
        ]);

        if ($last_rep->progress == 100)
            event(new notifyEvent('tasks.complete', 961, $rec->id, $userid));

        //------------------------------------------------------------------

        if ($id == -1)
            //return redirect(route('task_reports.edit', $rec->id));
            return redirect(route('tasks.edit', $rec->taskid));
        else
            //return redirect(route('jobtimesheets.index', ['id' => $rec->taskid]) . '#items');
            return redirect(route('tasks.edit', $rec->taskid));

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = task_report::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('task_reports.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['id'], $res->msg);
        } else {

            $sd['success'] = 'Запись (' . $id . ':  удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('jobtimesheets.edit', ['id' => $res->obj['taskid']]);
            //connectify('success', $id, 'Запись удалена.');

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


    static public function list_for(Request $request)
    {
        //2021-07-03 SNS. Обертка для вызова task_report::lstFor

        $result = "";
        try {

            $list = task_report::lstFor([
                'orgid' => $request->orgid,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
                'in_documents' => $request->in_documents,
            ]);


            $result = array('task_reports' => $list);
            //$result = $list;

        } catch (\Exception $e) {
            Log::error('task_reports::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
