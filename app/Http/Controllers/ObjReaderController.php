<?php

namespace App\Http\Controllers;

use App\contract;
use App\obj_reader;
use App\objlog;
use App\qcheck;
use App\Post;
use App\qcheck_item;
use App\news;
use App\roletype;
use App\sysobj;
use App\Traits\DeleteFileTrait;
use App\User;
use App\user_notice;
use App\usrsysright;
use App\Jobs\SendNotify;
use Illuminate\Http\Request;

class ObjReaderController extends Controller
{
    use DeleteFileTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 896;
        $this->objcode = 'obj_readers';
    }

    protected function setInterfaceRight($id, $sysobjcode = null)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $sysobjcode = $sysobjcode ?? $this->objcode;

        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $sysobjcode . '.read');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $sysobjcode . '.create');
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $sysobjcode . '.delete');
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
    public function create($sysobjid, $objid)
    {
        return $this->edit(-1, $sysobjid, $objid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\obj_reader $obj_reader
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $sysobjid = null, $objid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new obj_reader();
            $rec->id = -1;
            $rec->sysobjid = $sysobjid;
            $rec->objid = $objid;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = obj_reader::find($id);
        }

        if (isset($rec)) {


            //Возьмем права от родительской системы:
            $sysobj = sysobj::find($rec->sysobjid);
            $usrrights = $this->setInterfaceRight($rec->id, $sysobj->code);


            $rec->objname = null;
            if (isset($rec->objid)) {
                if (isset($sysobj->model_class)) {

                    $class_name = "App\\" . $sysobj->model_class;
                    $blade_folder = $sysobj->code;

                    $obj = $class_name::find($rec->objid);
                    $rec->objname = ($obj->title ?? $obj->name) ?? '-?-';
                    $rec->retRoute = route($blade_folder . '.edit', $rec->objid);

                } elseif ($rec->sysobjid == 151) {
                    //Posts
                    $obj = contract::select('docnum')->find($rec->objid);
                    $rec->objname = 'Договор №' . ($obj->docnum ?? '-?-');
                    $rec->retRoute = route('contracts.edit', $rec->objid);
                } elseif ($rec->sysobjid == 895) {
                    //Posts
                    $obj = Post::select('title')->find($rec->objid);
                    $rec->objname = $obj->title ?? '-?-';
                    $rec->retRoute = route('posts.edit', $rec->objid);

                } elseif ($rec->sysobjid == 862) {
                    //QChecks
                    $obj = qcheck::select('name')->find($rec->objid);
                    $rec->objname = $obj->name ?? '-?-';
                    $rec->retRoute = route('qchecks.edit', $rec->objid);

                } elseif ($rec->sysobjid == 863) {
                    //QCheck_Items
                    $obj = qcheck_item::find($rec->objid);
                    $rec->objname = $obj->info ?? '-?-';
                    $rec->retRoute = route('qcheck_items.edit', $rec->objid);
                } elseif ($rec->sysobjid == 904) {
                    //News
                    $obj = news::find($rec->objid);
                    $rec->objname = $obj->title ?? '-?-';
                    $rec->retRoute = route('news.edit', $rec->objid);
                } elseif ($rec->sysobjid == 951) {
                    //todo: завести поля в sysobjs, брать оттуда
                    $class_name = 'App\event';
                    $blade_folder = 'events';

                    $obj = $class_name::find($rec->objid);
                    $rec->objname = ($obj->title ?? $obj->name) ?? '-?-';
                    $rec->retRoute = route($blade_folder . '.edit', $rec->objid);
                    //dd($obj,$rec);
                }
            }

            //Типы ролей пользователей для данной системы
            $rec->roletypes = roletype::lstfor([
                'sysobjid' => $rec->sysobjid
            ]);
            //dd($sysobjid, $rec->sysobjid, $rec->roletypes);

            return view($this->objcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\obj_reader $obj_reader
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        $messages = [
            'sysobjid.required' => 'Тип объекта локальной системы должен быть задан',
            'objid.required' => 'Объект локальной системы должен быть задан',
        ];

        $request->validate([
            "sysobjid" => "required",
            "objid" => "required",
        ], $messages);

        $userid = \Auth::user()->id;

        $sysobjid = $request->sysobjid;
        $objid = $request->objid;
        $userids = $request->userid;
        $roletypeids = $request->roletypeid;
        //dd($sysobjid, $objid, $userids);

        $mess = "";

        foreach ($userids as $key => $usrid) {

            $usrid = $userids[$key];
            $roletypeid = $roletypeids[$key];
            //dd($usrid, $roletypeid);

            if (isset($usrid)) {


                $mess = "Запись обновлена";
                if ($id == -1) {
                    //попробуем поискать - возможно пользователь уже в списке?
                    $rec = obj_reader::where([
                        'sysobjid' => $sysobjid,
                        'objid' => $objid,
                        'userid' => $usrid,
                    ])->first();

                    if (!isset($rec)) {
                        $rec = new obj_reader([
                            "sysobjid" => $sysobjid,
                            "objid" => $objid,
                            "created_by" => $userid,
                            "created_at" => now(),
                            "updated_by" => $userid,
                            "updated_at" => now()]);
                        $mess = "Запись создана";
                    }
                } else {
                    $rec = obj_reader::find($id);
                }
                $rec->userid = $usrid;
                $rec->mustread = 1;
                $rec->roletypeid = $roletypeid;
                $rec->updated_by = $userid;
                $rec->updated_at = now();

                $rec->save();
                objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

                $sysobj = sysobj::find($sysobjid);
                if (isset($sysobj)) {

                    $info_name = $sysobj->name;

                    $model = $sysobj->model_class;
                    if (isset($model)) {
                        $model = "App\\" . $model;
                        $obj = ($model)::find($objid);
                        if (isset($obj)) {
                            $info_name = $obj->info ?? '"' . $info_name . '"';
                            //dd($info_name);
                        }
                    }

                    $subj = 'Новая информация';
                    $msg = $info_name;
                    $ref_url = route($sysobj->code . ".edit", $objid);
                    //добавление колокольчика
                    user_notice::addOrUpdate($sysobjid * 1000000 + $objid
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
                                . " < br>"
                                . " < br>Вам необходимо ознакомиться с новой информацией"
                                . " < br><hr > "
                                . " <a href = '" . $ref_url . "' > Перейти к документу </a > ";
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
     * @param \App\obj_reader $obj_reader
     * @return \Illuminate\Http\Response
     */
    public
    function destroy(Request $request, $id)
    {
        $retURL = $request->get('retURL') ?? '/';

        //Возьмем права от родительской системы:
        $sysobjid = $request->get('sysobjid');
        $sysobj = sysobj::find($sysobjid);
        $usrrights = $this->setInterfaceRight($id, $sysobj->code);
        //dd($usrrights);

        if ($usrrights['delete']) {
            $res = obj_reader::delete_by_id($id, $this->sysobjid);

            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $route = route('obj_readers.edit', $id);
                $sd["error"] = $res->msg;
            } else {

                $route = $request->get('retURL') ?? '/';
                $sd['success'] = 'Запись удалена';
            }
        } else {
            $route = route('obj_readers.edit', $id);
            $sd["error"] = "Нет прав на удаление";
        }
        return redirect($route)->with($sd);
    }
}
