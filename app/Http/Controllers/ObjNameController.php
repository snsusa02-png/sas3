<?php

namespace App\Http\Controllers;

use App\contract;
use App\equiprqst_item;
use App\invoice_item;
use App\refitem;
use App\Jobs\SendNotify;
use App\news;
use App\obj_name;

use App\objlog;
use App\Post;
use App\qcheck;
use App\qcheck_item;
use App\sysobj;
use App\Traits\DeleteFileTrait;
use App\user_notice;
use App\usrsysright;
use Illuminate\Http\Request;

class ObjNameController extends Controller
{
    use DeleteFileTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 971;
        $this->objcode = 'obj_names';
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
     * @param \App\obj_name $obj_name
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $sysobjid = null, $objid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new obj_name();
            $rec->id = -1;
            $rec->sysobjid = $sysobjid;
            $rec->objid = $objid;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = obj_name::find($id);
        }

        if (isset($rec)) {


            //Возьмем права от родительской системы:
            $sysobj = sysobj::find($rec->sysobjid);
            $usrrights = $this->setInterfaceRight($rec->id, $sysobj->code);


            $rec->objname = null;
            if (isset($rec->objid)) {


                if ($rec->sysobjid == 151) {
                    //Posts
                    $obj = contract::select('docnum')->find($rec->objid);
                    $rec->objname = ($obj->info ?? '-?-');
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
                } elseif (isset($sysobj->model_class)) {

                    $class_name = 'App\\' . $sysobj->model_class;
                    $blade_folder = $sysobj->code;

                    $obj = $class_name::find($rec->objid);
                    //dd($obj);
                    $rec->objname = ($obj->info ?? $obj->name) ?? '-?-';
                    //dd($rec->objname);
                    $rec->retRoute = route($blade_folder . '.edit', $rec->objid);
                    //dd($obj,$rec);
                }
            }

            return view($this->objcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\obj_name $obj_name
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        $messages = [
            'sysobjid.required' => 'Тип объекта локальной системы должен быть задан',
            'objid.required' => 'Объект локальной системы должен быть задан',
            'name.required' => 'Укажите название',
        ];

        $rules = [
            "sysobjid" => "required",
            "objid" => "required",
            "name" => "required",
        ];

        $request->validate($rules, $messages);


        $userid = \Auth::user()->id;

        $retURL = $request->get('retURL') ?? '/';

        $sysobjid = $request->sysobjid;
        $objid = $request->objid;
        $name = $request->name;
        //dd($sysobjid, $objid, $userids);

        //Проверим чтобы не было одинаковых названий в этой системной категории
        $prm = new \stdClass();
        $prm->sysobjid = $sysobjid;
        $prm->objid = $objid;
        $prm->name = $name;

        $rules = [
            //В форме должно быть поле ttt
            "ttt" => [
                function ($attribute, $value, $fail) use ($prm) {

                    //поиск по альтернативным названиям других записей этого же системного типа
                    $cnt = obj_name::where(['sysobjid' => $prm->sysobjid, 'name' => $prm->name])
                        ->where('objid', '<>', $prm->objid)->count();

                    //если мы находимся в спр-ке Номенклатуры, то проверим и по основному названию
                    if ($cnt == 0 and $prm->sysobjid == 105) {
                        $cnt = refitem::where(['name' => $prm->name])
                            ->where('id', '<>', $prm->objid)->count();
                    }
                    if ($cnt > 0) {
                        $fail("Такое название уже использовано для другой записи!");
                    }
                },
            ],
        ];

        $request->validate($rules, $messages);


        $mess = "";

        $mess = "Запись обновлена";
        if ($id == -1) {
            //попробуем поискать - возможно такое название для объекта уже есть?
            $rec = obj_name::where([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'name' => $name,
            ])->first();

            if (!isset($rec)) {
                $rec = new obj_name([
                    "sysobjid" => $sysobjid,
                    "objid" => $objid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $mess = "Запись создана";
            }
        } else {
            $rec = obj_name::find($id);
        }
        $rec->name = $name;
        $rec->active = 1;
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        if ($rec->sysobjid == 105) {
            //Название для позиции номенклатуры

            //Свяжем вакантные позиции заявок на материалы - по идентичному названию и ЕИ
            equiprqst_item::setRefItmIDByNameAndUnit($rec->objid
                , $rec->name
                , refitem::find($rec->objid)->unittypeid);

            //Свяжем вакантные позиции счета - по идентичному названию и ЕИ
            invoice_item::setRefItmIDByNameAndUnit($rec->objid
                , $rec->name
                , refitem::find($rec->objid)->unittypeid);

        }


        return redirect($retURL)->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\obj_name $obj_name
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
            $res = obj_name::delete_by_id($id, $this->sysobjid);

            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $route = route('obj_names.edit', $id);
                $sd["error"] = $res->msg;
            } else {

                $route = $request->get('retURL') ?? '/';
                $sd['success'] = 'Запись удалена';
            }
        } else {
            $route = route('obj_names.edit', $id);
            $sd["error"] = "Нет прав на удаление";
        }
        return redirect($route)->with($sd);
    }
}
