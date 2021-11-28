<?php

namespace App\Http\Controllers;

use App\ac;
use App\buildobj;
use App\Jobs\SendNotify;
use App\obj_reader;
use App\obj_staff;
use App\objlog;
use App\org;
use App\orgstaff;
use App\sysobj;
use App\user_notice;
use App\usrsysright;
use Illuminate\Http\Request;

class ObjStaffController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 1601;
        $this->sysobjcode = 'obj_staffs';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }


    protected function setInterfaceRight($id, $sysobjcode = null)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $sysobjcode = $sysobjcode ?? $this->objcode;

        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['edtrights'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.delete');
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
            $ordr = obj_staff::where(['sysobjid' => $sysobjid, 'objid' => $objid])->max('ordr') ?? 0;
            $ordr += 1;

            $rec = new obj_staff([
                'id' => -1,
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'ordr' => $ordr,
                'active' => 1,
                'created_by' => \Auth::user()->id,
            ]);
        } else
            $rec = obj_staff::find($id);


        if (!isset($rec))
            return redirect(route('home'));

        $rec->retURL = $request->get('returl');

        //$data = new \stdClass();

        $rec->userid = orgstaff::find($rec->staffid)->userid ?? null;

        //Возьмем права от родительской системы:
        $sysobj = sysobj::find($rec->sysobjid);
        $usrrights = $this->setInterfaceRight($rec->id, $sysobj->code);

        if ($usrrights['save'] ?? false) {
            //$rec->orgs = org::lstActiveOrgs();
            $rec->orgs = org::lstFor(['in_orgstaff' => 1]);
            //$rec->staffs = orgstaff::listActiveForOrg($rec->orgid);
            $rec->staffs = orgstaff::lstFor(['orgid' => $rec->orgid ?? 0]);
        } else {
            $rec->orgs = null;
            $rec->staffs = null;
        }
        //dd($rec);
        if ($id != -1 and $usrrights['edtrights'])
            $rec->usrsysrights = usrsysright::UserRightLst($rec->userid, 466, $rec->buildobjid);

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
        $request->validate([
            "sysobjid" => "required",
            "objid" => "required",
            //"staffid.*" => "required",
            "rolename.*" => "required",
        ]);

        $userid = \Auth::user()->id;

        $sysobjid = $request->sysobjid;
        $objid = $request->objid;
        $staffids = $request->staffid;
        $roletypeids = $request->roletypeid;
        $roletypenames = $request->roletypename;
        $reasons = $request->reason;
        $signeds = $request->signed;
        //dd($sysobjid, $objid, $staffids, $roletypeids, $reasons);
        //dd($signeds);

        $mess = "";

        foreach ($staffids as $key => $staffid) {

            $staffid = $staffids[$key];
            $roletypeid = $roletypeids[$key];
            $roletypename = $roletypenames[$key];
            $reason = $reasons[$key];
            $signed = $signeds[$key];
            //dd($staffid, $roletypeid);

            $ordr = obj_staff::where('sysobjid', $sysobjid)->max('ordr') ?? 0;

            if (isset($staffid)) {

                $mess = "Запись обновлена";
                if ($id == -1) {
                    //попробуем поискать - возможно сотрудник уже в списке?
                    $rec = obj_staff::where([
                        'sysobjid' => $sysobjid,
                        'objid' => $objid,
                        'staffid' => $staffid,
                    ])->first();

                    if (!isset($rec)) {
                        $ordr += 10;

                        $rec = new obj_staff([
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
                    $rec = obj_staff::find($id);
                }
                $rec->staffid = $staffid;
                $rec->roletypeid = $roletypeid;
                $rec->rolename = $roletypename;
                $rec->reason = $reason;
                $rec->signed = $signed;
                $rec->staffname = $rec->orgstaff->name;
                $rec->orgid = $rec->orgstaff->orgid;
                $rec->updated_by = $userid;
                $rec->updated_at = now();

                $rec->save();
                objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

                $sysobj = sysobj::find($sysobjid);
                if (1 == 0 and isset($sysobj)) {

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


        if (1 == 0) {
            $mess = "";
            if ($id == -1) {
                $rec = new obj_staff([
                    "sysobjid" => $request->get('sysobjid'),
                    "objid" => $request->get('objid'),
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $mess = "Запись создана";
            } else {
                $rec = obj_staff::find($id);
                $mess = "Запись обновлена";
            }
            $ordr = $request->get('ordr');
            if (!isset($ordr)) {
                $ordr = obj_staff::where('sysobjid', $rec->sysobjid)->max('ordr') ?? 0;
                $ordr += 10;
            }
            $rec->roletypeid = $request->get('roletypeid') ?? 1;
            $rec->rolename = $request->get('rolename');
            $rec->orgid = $request->get('orgid');
            $rec->staffid = $request->get('staffid');
            $rec->staffname = $rec->orgstaff->name;
            $rec->reason = $request->get('reason');
            $rec->ordr = $ordr;
            $rec->active = 1; //$request->get('active', 0);
            $rec->updated_by = $userid;
            $rec->updated_at = now();
            //dd($rec);
            $rec->save();
            objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

            connectify('success', $rec->rolename, $mess);
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
        $retURL = $request->get('returl') ?? route('obj_staffs.edit', $id);
        $userid = \Auth::user()->id;


        //Возьмем права от родительской системы:
        $sysobjid = $request->get('sysobjid');
        $sysobj = sysobj::find($sysobjid);

        if (usrsysright::isUserHasRightByCode_cached($userid, $sysobj->code . '.delete')) {

            $res = obj_staff::delete_by_id($id);

            $sd = array();
            if ($res->err == 1) {
                $sd["error"] = $res->msg;
            } else {

                $retURL = $request->get('returl') ?? route('home');
                $sd['success'] = 'Запись о сотруднике удалена';
            }
        } else {
            $sd['success'] = 'У вас нет прав на удаление записей!';
        }
        return redirect($retURL)->with($sd);
    }


    static public function listbuildobj_staffs(Request $request)
    {
        $result = "";
        try {
            $buildobjid = $request->buildobjid;
            $buildobj = buildobj::find($buildobjid);
            $buildobj_staffs = obj_staff::where('buildobjid', $buildobjid)
                ->select('id', 'name')
                ->get()->pluck('name', 'id')->toArray();
            //rqListRefitem4Auto($orgid, $request)

            $result = array('address' => $buildobj->address, 'opertypes' => $buildobj_staffs);

        } catch (\Exception $e) {
        }
        return response()->json($result);

    }
}
