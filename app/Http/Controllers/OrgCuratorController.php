<?php

namespace App\Http\Controllers;

use App\opertype;
use App\org_curator;
use App\paydoc;
use App\sysobj;
use App\user_template;
use App\usrsysright;
use App\objlog;
use App\User;

use Illuminate\Http\Request;
use Auth;
use DateTime;
use Cache;

class OrgCuratorController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 122;
        $this->sysobjcode = 'org_curators';
        $this->parsysobjid = 111;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = Auth::id();

        $usrrights = array();
        $usrrights['create'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.create');
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $this->acl_sysobjcode . '.read');
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
        $orgcurators = org_curator::OrgCuratorList('orgid')->paginate(5);
        $rec0 = $orgcurators->currentPage() * $orgcurators->perPage() - $orgcurators->perPage() + 1;
        return view('org_curators.index', compact(['orgcurators', 'rec0']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create1($orgid)
    {
        if (isset($orgid)) {
            $usrrights = $this->setInterfaceRight(-1);

            $rec = new org_curator();
            $rec->id = -1;
            $rec->orgid = $orgid;
            $rec->begdt = date('Y-m-d', strtotime(now()));
            $rec->active = 1;
            $rec->curators = org_curator::AllCurators()->pluck("name", "id")->prepend("", "");

            return view('org_curators.edit', compact('rec', "usrrights"));
        } else
            return view('orgs.index');
    }

    public
    function create(Request $request, $orgid)
    {
        return $this->edit($request, -1, $orgid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\org_curator $org_curator
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $orgid = null)
    {
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                //Значения "по-умолчанию" для новой записи ----------------

                //$paydate = $request->get('paydate');

                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                //dd($tmplt);

                //Добавим свои значения
                $newData['id'] = -1;
                $newData['orgid'] = $orgid;
                $newData['begdt'] = date('Y-m-d', strtotime(now()));
                $newData['active'] = 1;
                $newData['created_by'] = $userid;

                $rec = new org_curator($newData);
                //---------------------------------------------------------

            } else
                return redirect(route('orgs.index'));
        } else {

            $sc = '1=1';

            $rec = org_curator::from('org_curators as c')->whereRaw($sc)->where('id', $id)->first();

            if (!isset($rec))
                return redirect(route('orgs.index'));
        }

        //
        $rec->curators = org_curator::AllCurators()->pluck("name", "id")->toArray();
        $rec->opertypes = opertype::lstFor(['active_or_current' => 1]);

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\org_curator $org_curator
     * @return \Illuminate\Http\Response
     */
    //public function update(Request $request, org_curator $org_curator)
    public function update(Request $request, $id)
    {
        $request->validate(
            [
                "staffid.required" => 'Укажите сотрудника',
            ], [
            //"userid" => "required",
            "staffid" => "required",
        ], );

        $userid = Auth::id();
        $mess = "";

        if ($id == -1) {
            $rec = new org_curator();
            $rec->orgid = $request->get('orgid');
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о кураторе клиента";
        } else {
            $rec = org_curator::find($id);
            $mess = "Изменена запись о кураторе клиента";
        }
        //$rec->userid = $request->get('userid');
        $rec->staffid = $request->get('staffid');

//        $staffid = User::find($rec->userid)->StaffID;
//        $staffid = isset($staffid) ? $staffid : 0;
//        $rec->staffid = $staffid; //временно, так как переходим на users

        $rec->opertypeid = $request->get('opertypeid');

        $begdt = $request->get('begdt') ?? now();
        $rec->begdt = $begdt;
        $enddt = $request->get('enddt');
        if (isset($enddt)) {
            $enddt = new DateTime($enddt);
            $enddt->modify("+1 day")->modify("-1 second");
        }
        $rec->enddt = $enddt;
        $rec->active = $request->get('active', 0);

        $rec->updated_by = $userid;
        $rec->save();

        Cache::forget('org_aux_curator_' . $rec->orgid);

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

//        return redirect(route('orgstaff.index'))->with('success',$mess);
        return redirect(route('org_curators.index', $rec->orgid))->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\org_curator $org_curator
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $dd = org_curator::find($id);
        $orgid = $dd->orgid;
        $dd->delete();
        Cache::forget('org_aux_curator_' . $orgid);
        return redirect(route('org_curators.index', $orgid))->with('success', 'Запись о кураторе удалена');
    }
}
