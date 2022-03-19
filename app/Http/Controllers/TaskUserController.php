<?php

namespace App\Http\Controllers;

use App\Jobs\SendNotify;
use App\roletype;
use App\task_user;
use App\objlog;
use App\sysobj;
use App\Traits\DeleteFileTrait;
use App\user_notice;
use App\User;
use App\usrsysright;
use Illuminate\Http\Request;

class TaskUserController extends Controller
{
    use DeleteFileTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 963;
        $this->sysobjcode = 'task_users';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        //$sysobjcode = $this->objcode;
        $sysobjcode = 'tasks';

        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.create');
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.delete');
        }

        return $usrrights;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($taskid)
    {
        return $this->edit(-1, $taskid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\task_user $task_user
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $taskid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new task_user();
            $rec->id = -1;
            $rec->taskid = $taskid;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = task_user::find($id);
        }

        if (isset($rec)) {

            $usrrights = $this->setInterfaceRight($rec->id);

            if ($rec->task->inituserid == $userid) {
                $usrrights['create'] = true;
                $usrrights['save'] = true;
            } else {
                $usrrights['create'] = false;
                $usrrights['save'] = false;
            }
            //dd($this->acl_sysobjcode, $usrrights);

            $rec->roletypes = roletype::lstFor(['sysobjid' => $this->sysobjid]);

            return view($this->objcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\task_user $task_user
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        $messages = [
            'taskid.required' => 'Не задана задача',
        ];

        $request->validate([
            "taskid" => "required",
        ], $messages);

        $userid = \Auth::user()->id;

        $taskid = $request->taskid;
        $userids = $request->userid;
        $roletypeids = $request->roletypeid;
        //dd($sysobjid, $objid, $userids);

        $mess = "";

        foreach ($userids as $key => $usrid) {
            if (isset($usrid)) {

                $mess = "Запись обновлена";
                if ($id == -1) {
                    //попробуем поискать - возможно пользователь уже в списке?
                    $rec = task_user::where([
                        'taskid' => $taskid,
                        'userid' => $usrid,
                    ])->first();

                    if (!isset($rec)) {
                        $rec = new task_user([
                            "taskid" => $taskid,
                            "created_by" => $userid,
                            "created_at" => now(),
                            "updated_by" => $userid,
                            "updated_at" => now()]);
                        $mess = "Запись создана";
                    }
                } else {
                    $rec = task_user::find($id);
                }
                $rec->userid = $usrid;
                $rec->roletypeid = $roletypeids[$key];
                $rec->updated_by = $userid;
                $rec->updated_at = now();

                $rec->save();
                objlog::log_info($this->sysobjid, $rec->id, $mess, 5);


                if (1 == 1) {

                    $info_name = $rec->task->name;

                    $subj = 'Новая задача';
                    $msg = $info_name;
                    $ref_url = route("tasks.edit", $taskid);

                    //добавление колокольчика
                    user_notice::addOrUpdate(961 * 1000000 + $taskid
                        , $ref_url, $rec->userid
                        , $subj
                        , $msg
                        , now()
                        , null);


                    //
                    $rcpt = User::find($rec->userid);
                    if (isset($rcpt) and isset($rcpt->email)) {

                        $email = $rcpt->email;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {

                            //для журнала сформируем список получателей
                            $lstrcpts = ' ' . $rcpt->lname
                                . ' ' . mb_substr($rcpt->fname, 0, 1) . '.'
                                . mb_substr($rcpt->mname, 0, 1) . '. (' . $email . ');';

                            //$email = 'shevchenko.s@basko.su';
                            //$email = 'snsusa02@gmail.com';

                            $msg = "Здравствуйте, " . $rcpt->fname . " " . $rcpt->mname . "!"
                                . "<br>"
                                . "<br>Вам необходимо ознакомиться с новой задачей"
                                . "<br><hr>"
                                . " <a href='" . $ref_url . "'>Перейти к задаче</a>";
                            //dd($subj, $msg);
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        }
                    }


                }
            }
        }

        $retURL = $request->get('retURL') ?? '/';

        return redirect($retURL)->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\task_user $task_user
     * @return \Illuminate\Http\Response
     */
    public
    function destroy(Request $request, $id)
    {
        $retURL = $request->get('retURL') ?? '/';

        //Возьмем права от родительской системы:
//        $sysobjid = $request->get('sysobjid');
//        $sysobj = sysobj::find($sysobjid);
//        dd($sysobj);

        $usrrights = $this->setInterfaceRight($id, $this->sysobjcode);
        //dd($usrrights);

        if ($usrrights['delete']) {
            $res = task_user::delete_by_id($id, $this->sysobjid);

            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $route = route('task_users.edit', $id);
                $sd["error"] = $res->msg;
            } else {

                $route = $request->get('retURL') ?? '/';
                $sd['success'] = 'Запись удалена';
            }
        } else {
            $route = route('task_users.edit', $id);
            $sd["error"] = "Нет прав на удаление";
        }
        return redirect($route)->with($sd);
    }
}
