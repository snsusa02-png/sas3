<?php

namespace App\Http\Controllers;

use App\objflag;
use App\objlog;
use App\org;
use App\orgstaff;
use App\userorg;
use App\usrsysright;
use Illuminate\Http\Request;
use Validator;
use App\User;
use App\org_curator;
use Illuminate\Support\Facades\Cache;

class UserorgController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 126;
        $this->parsysobjid = 3; //Users
        $this->objcode = 'userorgs';
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
        $usrrights['edtrights'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
            $usrrights['edtrights'] = usrsysright::isUserHasRightByCode($userid, 'admin-global');
        }

        return $usrrights;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($userid)
    {

        //Значения "по-умолчанию" для новой записи
        $rec = new userorg();
        $rec->id = -1;
        $rec->userid = $userid;
        $rec->begdt = now();
        $rec->active = 1;
        $rec->created_by = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($rec->id);

//        $orgs = org::whereActive(1)->select('id', 'name')
//            ->orderby('name')
//            ->pluck("name", "id")
//            ->prepend("", "");

//        return view('userorgs.edit', compact(['rec', 'usrrights', 'orgs']));
        return view('userorgs.edit', compact(['rec', 'usrrights']));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\userorg $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(userorg $rec)
    public function edit($id)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->route('home');

        $rec = userorg::find($id);

        if (isset($rec)) {
            //является ли пользователь куратором этой организации
            $rec->curator = org_curator::isUserIsOrgCurator($rec->userid, $rec->orgid);

            $rec->usrsysrights = usrsysright::UserRightLst($rec->userid, 111, $rec->orgid);
            //dd($rec->orgid, $rec->usrsysrights);


            $auxinfo = "";

            $ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);

            return view('userorgs.edit', compact('rec'
                , 'auxinfo', "ObjFlags", "usrrights"));
        }
        return redirect()->route('home');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\userorg $rec
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, userorg $rec)
    public function update(Request $request, $id)
    {
        //
        $rules = ["orgid" => "required",
        ];
        $messages = [
            "orgid.required" => "Обязательно укажите представляемую организацию",
        ];
        Validator::make($request->all(), $rules, $messages)->validate();;

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new userorg([
                "userid" => $request->get('userid'),
                "orgid" => $request->get('orgid'),
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = userorg::find($id);
            $mess = "Запись о представляемой организации обновлена";
        }
        //$rec->curator = $request->get('curator', 0);

        $rec->postname = $request->get('postname');

        $rec->begdt = $request->get('begdt') ?? now();
        //dd($rec->begdt, isset($rec->begdt), is_null($rec->begdt), empty($rec->begdt));
        //if (is_null($rec->begdt)) $rec->begdt = now();

        $rec->enddt = $request->get('enddt');
        $rec->active = $request->get('active', 0);
        $rec->acs_contracts = $request->get('acs_contracts', 0);
        //$rec->acs_orgplnpays = $request->get('acs_orgplnpays', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        //Сохраним признак кураторства в стандартной структуре: org_curators ---------
        $isCurator = ($request->get('curator', 0) == 1) ? 1 : 0;
        org_curator::updOrgCurator($rec->orgid, $rec->userid, $isCurator, now(), now(), $userid);
        //-----------------------------------------------------------------------------


        //если пользователь представляет только одну организацию, то сделаем ее текущей
        $cnt = userorg::userActiveOrgsCnt($rec->userid);
        //dd($cnt);
        if ($cnt == 0)
            //Зачистим текущую организацию пользователя
            User::setCurOrgID($rec->userid, null);
        elseif ($cnt == 1)
            //Допущение - предполагаем, что сейчас завели представительство как активное и уже действующее
            User::setCurOrgID($rec->userid, $rec->orgid);


        //забудем кэшированные данные про организации пользователя:
        Cache::forget('userorg.lstUserActiveOrgs.' . $rec->userid);
        // и данные для строки в меню
        Cache::forget('UserInfo' . $rec->userid);
        //и то, является ли он куратором данной оргнизации:
        Cache::forget('userorg.isUser' . $rec->userid . 'Org' . $rec->orgid . 'Curator');


        if ($id == -1)
            //return redirect(route('userorgs.edit', $rec->id))->with('success', $mess);
            return redirect(route('users.edit', $rec->userid))->with('success', $mess);
        else
            return redirect(route('users.edit', $rec->userid))->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\userorg $rec
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = userorg::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('userorgs.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['userid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о представляемой организации: ' . $parobjid, 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //забудем кэшированные данные про организации пользователя:
            Cache::forget('userorg.lstUserActiveOrgs.' . $parobjid);

            $route = route('users.edit', $parobjid);
            $sd['success'] = 'Запись о представляемой организации удалена';
        }
        return redirect($route)->with($sd);
    }

}
