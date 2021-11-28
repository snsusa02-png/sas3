<?php

namespace App\Http\Controllers;

use App\objflag;
use App\objlog;
use App\org;
use App\org_place;
use App\place;
use App\project;
use App\qcheck;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Validator;

class OrgPlaceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 116;
        $this->parsysobjid = 111; //Orgs
        $this->objcode = 'org_places';
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

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
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
        return $this->edit(-1, $orgid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\org_place $rec
     * @return \Illuminate\Http\Response
     */
//    public function edit(org_place $rec)
    public function edit($id, $orgid = null)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                $rec = new org_place([
                    'id' => -1,
                    'orgid' => $orgid,
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else
                return redirect(route('orgs.index'));
        } else {
            $rec = org_place::find($id);
        }


        if (isset($rec)) {

            $rec->placetypes = [1 => 'офис', 2 => 'склад'];

            $ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);

            return view('org_places.edit', compact('rec'
                , "ObjFlags", "usrrights"));
        }
        return redirect()->route('home');
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\org_place $rec
     * @return \Illuminate\Http\Response
     */
//    public function update(Request $request, org_place $rec)
    public function update(Request $request, $id)
    {
        //
        $rules = ["orgid" => "required",
        ];
        $messages = [
            "orgid.required" => "Обязательно укажите представляемую организацию",
        ];

        $messages = [
            'orgid.required' => 'Не указан контрагент',
            'name.required' => 'Укажите название',
            'placetypeid.required' => 'Укажите тип места',
            'address.required' => 'Укажите адрес (индекс, город, улица, дом, корпус, офис',
        ];

        $rules = [
            "orgid" => "required",
            "name" => "required",
            "placetypeid" => "required",
        ];

        $request->validate($rules, $messages);


        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new org_place([
                "orgid" => $request->get('orgid'),
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()
            ]);
            $mess = "Запись создана";
        } else {
            $rec = org_place::find($id);
            $mess = "Запись обновлена";
        }

        $rec->name = $request->get('name');
        $rec->placetypeid = $request->get('placetypeid') ?? 1;
        $rec->address = $request->get('address');
        $rec->active = $request->get('active', 0);

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);


        if ($id == -1)
            //return redirect(route('org_places.edit', $rec->id))->with('success', $mess);
            return redirect(route('orgs.edit', $rec->orgid))->with('success', $mess);
        else
            return redirect(route('orgs.edit', $rec->orgid))->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\org_place $rec
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = org_place::delete_by_id($id, $this->sysobjid);

        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('org_places.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $parobjid = $res->obj['orgid'];
            objlog::log_info($this->parsysobjid, $parobjid, 'Удалена запись о месте присутствия организации: ' . $parobjid, 5);
            objlog::log_info($this->sysobjid, $id, 'Запись удалена', 5);

            //забудем кэшированные данные про ...:
            //Cache::forget('org_place.lstUserActiveOrgs.' . $parobjid);

            $route = route('orgs.edit', $parobjid);
            $sd['success'] = 'Запись о представляемой организации удалена';
        }
        return redirect($route)->with($sd);
    }



    static public function addrs_params(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $orgid = $request->orgid;
            $list = org_place::from('org_places as p')
                ->select('address')
                ->orderBy('address')
                ->get()->pluck('address')->toArray();

            $result = array('addresses' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);
    }


    static public function list_for(Request $request)
    {
        //2021-11-07 SNS. Обертка для вызова org_place::lstFor

        $result = "";
        try {
            $list = org_place::lstFor([
                's_name' => $request->s_name,
                's_orgid' => $request->s_orgid,
                's_active' => $request->s_active,
                's_placetypeid' => $request->s_placetypeid,
            ]);

            $result = array('org_places' => $list);

        } catch (\Exception $e) {
            Log::error('org_place::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

    static public function list_for_ac(Request $request)
    {
        //2021-11-27 SNS. Для автокомплита

        $result = "";
        try {

            $list = org_place::getFor([
                'name_address' => $request->name_address,
                'orgid' => $request->orgid,
                'active' => $request->active,
                'placetypeid' => $request->placetypeid,
            ],
                ['p.id', 'p.name', 'p.address']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('org_place::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
