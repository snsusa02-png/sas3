<?php

namespace App\Http\Controllers;

use App\contract;
use App\Jobs\SendNotify;
use App\news;
use App\obj_org;
use App\obj_reader;
use App\objlog;
use App\org;
use App\orgstaff;
use App\Post;
use App\qcheck;
use App\qcheck_item;
use App\roletype;
use App\sysobj;
use App\user_notice;
use App\usrsysright;
use Illuminate\Http\Request;

class ObjOrgController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 1611;
        $this->sysobjcode = 'obj_orgs';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }


    protected function setInterfaceRight($id, $acl_sysobjcode = null)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        //Как-правило - наследуем права от системы из которой произведено обращение
        $acl_sysobjcode = $acl_sysobjcode ?? $this->acl_sysobjcode;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['edtrights'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.delete');
            $usrrights['edtrights'] = usrsysright::isUserHasRightByCode($userid, 'admin-global');
        }

        return $usrrights;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $sysobjid, $objid)
    {
        return $this->edit($request, -1, $sysobjid, $objid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $sysobjid = null, $objid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи
            $ordr = obj_org::where(['sysobjid' => $sysobjid, 'objid' => $objid])->max('ordr') ?? 0;
            $ordr += 1;

            $rec = new obj_org([
                'id' => -1,
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'ordr' => $ordr,
                'active' => 1,
                'created_by' => \Auth::user()->id,
            ]);
        } else
            $rec = obj_org::find($id);


        if (!isset($rec))
            return redirect(route('home'));

        $rec->retURL = $request->get('returl');

        //$data = new \stdClass();

//        $rec->userid = orgstaff::find($rec->staffid)->userid ?? null;
//
//        $usrrights = $this->setInterfaceRight($id);
//        if ($usrrights['save'] ?? false) {
//            //$rec->orgs = org::lstActiveOrgs();
//            $rec->orgs = org::lstFor(['in_orgstaff' => 1]);
//            //$rec->staffs = orgstaff::listActiveForOrg($rec->orgid);
//            $rec->staffs = orgstaff::lstFor(['orgid' => $rec->orgid ?? 0]);
//        } else {
//            $rec->orgs = null;
//            $rec->staffs = null;
//        }
//        //dd($rec);
//        if ($id != -1 and $usrrights['edtrights'])
//            $rec->usrsysrights = usrsysright::UserRightLst($rec->userid, 466, $rec->buildobjid);


        $sysobj = sysobj::find($rec->sysobjid);
        $rec->sysobj = $sysobj;

        //Возьмем права от родительской системы:
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


        //Типы ролей организаций для данного типа документа
        //заляпуха 1
        if ($rec->sysobjid == 1701) {
            $ref_sysobjid = 920;   //doctypes
            $ref_objid = $obj->doctypeid;
            $tgt_sysobjid = 111;   //orgs
        }
        if (isset($ref_objid)) {
            $rec->roletypes = roletype::lstfor([
                'sysobjid' => $ref_sysobjid
                , 'objid' => $ref_objid
                , 'tgt_sysobjid' => $tgt_sysobjid
            ]);
            //dd($ref_objid, $rec->roletypes);
            if($rec->roletypes==[])
                //заляпуха 2
                $rec->roletypes = roletype::lstfor([
                    'sysobjid' => $rec->sysobjid
                ]);

        }
        else{
            //Типы ролей пользователей для данной системы
            $rec->roletypes = roletype::lstfor([
                'sysobjid' => $rec->sysobjid
            ]);
            //dd($ref_objid, $rec->roletypes);
        }
        //dd($sysobjid, $rec->sysobjid, $rec->roletypes);
        //dd($sysobj->code, $usrrights);

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
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
            'sysobjid.required' => 'Тип объекта инф. системы должен быть задан',
            'objid.required' => 'Объект инф. системы должен быть задан',
            'rolename.required' => 'Укажите роль участника',
        ];

        $rules = [
            "sysobjid" => "required",
            "objid" => "required",
//            "rolename" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $sysobjid = $request->sysobjid;
        $objid = $request->objid;
        $orgids = $request->orgid;
        $roletypeids = $request->roletypeid;
        $orgdepnames = $request->orgdepname;
        //dd($sysobjid, $objid, $orgids,$orgdepname,$roletypeids);

        //Упорядочим в порядке добавления
        $ordr = obj_org::where(['sysobjid' => $sysobjid, 'objid' => $objid])->max('ordr') ?? 0;

        foreach ($orgids as $key => $orgid) {

            $orgid = $orgids[$key];
            $roletypeid = $roletypeids[$key];
            $orgdepname = $orgdepnames[$key];
            //dd($orgid, $roletypeid);

            if (isset($orgid)) {


                $mess = "Запись обновлена";
                if ($id == -1) {
                    //попробуем поискать - возможно контрагент уже в списке?
                    $rec = obj_org::where([
                        'sysobjid' => $sysobjid,
                        'objid' => $objid,
                        'orgid' => $orgid,
                    ])->first();

                    if (!isset($rec)) {
                        $ordr += 10;
                        $rec = new obj_org([
                            "sysobjid" => $sysobjid,
                            "objid" => $objid,
                            "ordr" => $ordr,
                            "created_by" => $userid,
                            "created_at" => now(),
                            "updated_by" => $userid,
                            "updated_at" => now()]);
                        $mess = "Запись создана";
                    }
                } else {
                    $rec = obj_org::find($id);
                }
                $rec->orgid = $orgid;
                $rec->orgdepname = $orgdepname;
                $rec->roletypeid = $roletypeid ?? 1;
                $rec->updated_by = $userid;
                $rec->updated_at = now();

                $rec->save();
                objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

            }
        }


        $retURL = $request->get('returl');
        if (!isset($retURL)) {
            $sysobjcode = sysobj::find($rec->sysobjid)->code;
            $retURL = (isset($sysobjcode))
                ? route($sysobjcode . '.edit', $rec->objid)
                : route('home');
        }
        return redirect($retURL)->with('success', $mess);
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $retURL = $request->get('returl') ?? route('obj_orgs.edit', $id);
        $userid = \Auth::user()->id;

        //Возьмем права от родительской системы:
        $rec = obj_org::find($id);
        if (isset($rec)) {
            $sysobj = sysobj::find($rec->sysobjid);
            $this->acl_sysobjcode = sysobj::acl_sysobjcode($sysobj->code);

            if (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete')) {

                $retURL = (isset($rec->objid))
                    ? route($sysobj->code . '.edit', $rec->objid)
                    : route($sysobj->code . '.index');

                $res = obj_org::delete_by_id($id);

                $sd = array();
                if ($res->err == 1) {
                    $sd["error"] = $res->msg;
                } else {

                    //$retURL = $request->get('returl') ?? route('home');
                    $sd['success'] = 'Запись об участнике удалена';
                }
            } else {
                $sd['success'] = 'У вас нет прав на удаление записей!';
            }
        } else {
            $sd['success'] = 'Не найдена родительская запись!';
        }

        return redirect($retURL)->with($sd);
    }


}
