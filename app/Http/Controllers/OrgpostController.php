<?php

namespace App\Http\Controllers;

use App\objflag;
use App\objlog;
use App\org;
use App\orgdep;
use App\orgpost;
use App\orgstaff;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

class OrgpostController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 119;
        $this->parsysobjid = 111; //Orgs
        $this->objcode = 'orgposts';
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $sysobjcode = $this->objcode;
        $sysobjcode = 'org_acnts';

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        //Право доступа к ставке ЗП и т.п.
        $usrrights['private_acs'] = (usrsysright::isUserHasRightByCode_cached($userid, 'orgstaff.private_acs'));

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $sysobjcode . '.delete');
        }

        return $usrrights;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request, $orgid = null)
    {

        return $this->edit($request, -1, $orgid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\orgpost $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(orgpost $rec)
    public function edit(Request $request, $id, $orgid = null)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $depid = $request->get('depid');

                //Значения "по-умолчанию" для новой записи
                $ordr = orgpost::where(['orgid' => $orgid, 'depid' => $depid])->max('ordr') ?? 0;
                $ordr += 10;

                $rec = new orgpost([
                    'id' => -1,
                    'orgid' => $orgid,
                    'depid' => $depid,
                    'ordr' => $ordr,
                    'stdlimunits' => 1,
                    'tmplimunits' => 0,
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else
                return redirect(route('orgs.index'));
        } else {
            $rec = orgpost::find($id);
        }

        if (!isset($rec))
            return redirect(route('orgs.edit', $orgid));


        $rec->totsalary = $rec->salary + $rec->bns1sum + $rec->bns2sum;

        $rec->orgdeps = orgdep::lstFor(['orgid' => $rec->orgid]);

        $rec->staff = orgstaff::getFor(['postid' => $rec->id],
            ['os.id', 'os.name', 'os.postname', 'os.active', 'os.begdate', 'os.enddate']);

        $rec->retURL = $request->get('returl');

        $ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);

        return view('orgposts.edit', compact('rec', "ObjFlags", "usrrights"));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\orgpost $rec
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, orgpost $rec)
    public function update(Request $request, $id)
    {
        //
        $messages = [
            'orgid.required' => 'Не указана организация',
            'name.required' => 'Укажите название должности',
        ];

        $rules = [
            "orgid" => "required",
            "name" => "required",
        ];

        $request->validate($rules, $messages);

        $orgid = $request->get('orgid');
        $ordr = $request->get('ordr');
        if (!isset($ordr)) {
            $ordr = orgpost::where('orgid', $orgid)->max('ordr') ?? 0;
            $ordr += 10;
        }

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {

            $rec = new orgpost([
                "orgid" => $orgid,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = orgpost::find($id);
            $mess = "Запись о должности обновлена";
        }

        $rec->depid = $request->get('depid');
        $rec->name = mb_substr($request->get('name'), 0, 90);
        $rec->salary = $request->get('salary');
        $rec->bns1pcnt = $request->get('bns1pcnt');
        $rec->bns1sum = $request->get('bns1sum');
        $rec->bns2pcnt = $request->get('bns2pcnt');
        $rec->bns2sum = $request->get('bns2sum');
        $rec->stdlimunits = $request->get('stdlimunits');
        $rec->tmplimunits = $request->get('tmplimunits', 0);

        $rec->wrkduties = mb_substr($request->get('wrkduties'), 0, 360);
        $rec->prsndmnds = mb_substr($request->get('prsndmnds'), 0, 360);
        $rec->wrkconds = mb_substr($request->get('wrkconds'), 0, 360);

        $rec->ordr = $ordr;
        $rec->active = $request->get('active', 0);

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);


        if ($id == -1)
            $retURL = route('orgdeps.edit', $rec->depid);

        else {
            $retURL = $request->get('retURL') ?? route('orgdeps.edit', $rec->depid) . '?#orgposts';
        }

        return redirect($retURL)->with('success', $mess);
        //return redirect(route('orgs.edit', $rec->orgid))->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\orgpost $rec
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = orgpost::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('orgposts.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['orgid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о должности организации: ' . $parobjid, 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //забудем кэшированные данные про ...:
            //Cache::forget('orgpost.lstUserActiveOrgs.' . $parobjid);

            $route = route('orgs.edit', $parobjid);
            $sd['success'] = 'Запись о должности организации удалена';
        }
        return redirect($route)->with($sd);
    }

    static public function list_for(Request $request)
    {
        //2021-05-08 SNS. Обертка для вызова orgpost::lstFor

        $result = "";
        try {

            $list = orgpost::lstFor([
                'postid' => $request->postid,
                'orgid' => $request->orgid,
                'depid' => $request->depid,
                'active' => $request->active,
                'has_vacancy' => $request->has_vacancy,
                'has_vacancies_staff' => $request->has_vacancies_staff,
            ]);


            $result = array('list' => $list);

        } catch (\Exception $e) {
            Log::error('orgpost::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }


    static public function dep_posts(Request $request)
    {
        //2021-05-08 SNS. Список должностей заданного подразделения

        $result = "";
        try {

            $depid = $request->get('depid');
            $staffid = $request->get('staffid');

            $list = orgpost::from('orgposts as op')
                ->leftJoin('orgstaff as os', function ($j) use ($staffid, $depid) {
                    $j->on('os.postid', 'op.id')
                        ->where('os.id', $staffid);
                })
                ->where(['op.depid' => $depid])
                ->whereRaw("op.stdlimunits - op.stdusedunits + ifnull(os.stdpostunit,0) > 0")
                ->select('op.id', db::raw("concat(op.name,' (',op.stdlimunits-op.stdusedunits+ifnull(os.stdpostunit,0),')') as tname"))
                ->pluck('tname', 'id')->toarray();

            $result = array('list' => $list);

        } catch (\Exception $e) {
            Log::error('orgpost::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }


    static public function stdlimunits(Request $request)
    {
        //2021-05-08 SNS. Обертка для вызова orgpost::lstFor

        $result = "";
        try {

            $postid = $request->get('postid');
            $staffid = $request->get('staffid');
            $result = orgpost::from('orgposts as op')
                    ->leftJoin('orgstaff as os', function ($j) use ($postid, $staffid) {
                        $j->on('os.postid', 'op.id')
                            ->where('os.id', $staffid);
                    })
                    ->where(['op.id' => $postid])
                    ->whereRaw("op.stdlimunits-op.stdusedunits+ifnull(os.stdpostunit,0)>0")
                    ->select(db::raw("op.stdlimunits-op.stdusedunits+ifnull(os.stdpostunit,0)  as stdlimunits"))
                    ->first()
                    ->stdlimunits ?? 0;


            //$result = array('list' => $list);

        } catch (\Exception $e) {
            Log::error('stdlimunits::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }


}
