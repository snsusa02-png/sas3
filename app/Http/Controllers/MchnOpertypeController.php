<?php

namespace App\Http\Controllers;

use App\machine;
use App\mchn_opertype;
use App\cwp_fact;
use App\cwp_work;
use App\mot_price;
use App\objlog;
use App\opertype;
use App\sysobj;
use App\unittype;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MchnOpertypeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 486;
        $this->sysobjcode = 'mchn_opertypes';
    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        //по acl указанного объекта
        $acl_sysobjcode = sysobj::where('id', $this->sysobjid)
                ->select('acl_sysobjcode')->first()
                ->acl_sysobjcode ?? $this->sysobjcode;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        $usrrights['mot_prices.create'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            //$obj = mchn_opertype::find($id);

            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.delete');

            $t_aclcode = sysobj::where('code', 'mot_prices')
                    ->select(db::raw("ifnull(acl_sysobjcode, code) as acl_sysobjcode"))
                    ->first()->acl_sysobjcode ?? null;
            if (isset($t_aclcode)) {
                $usrrights['mot_prices.create'] = usrsysright::isUserHasRightByCode_cached($userid, $t_aclcode . '.create');
            }
        }

        return $usrrights;
    }


    public function create(Request $request, $machineid = null)
    {
        return $this->edit($request, -1, $machineid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $machineid = null)
    {
        //dd($buildobjid, $machineid);
        //dd($request->get('returl'));

        $userid = \Auth::user()->id;

        if ($id == -1) {

            $rec = new mchn_opertype([
                'id' => -1,
                'machineid' => $machineid,
                'active' => 1,
                'created_by' => \Auth::user()->id,
            ]);
        } else
            $rec = mchn_opertype::find($id);

        if (!isset($rec))
            return redirect(route('machines.edit', $machineid));

        $rec->retURL = $request->get('returl');

        //преобразуем для нормальной работы <INPUT TYPE="DATE"...
        //$rec->begdate = strftime('%Y-%m-%dT%H:%M:%S', strtotime($rec->begdate));

        $rec->opertypes = opertype::lstFor(['active_or_current' => 1]);

        $usrrights = $this->setInterfaceRight($rec->id);

        if ($rec->id <> -1) {

//            $rec->mot_prices = mot_price::getFor([
//                'mot_id' => $rec->id,
//            ]);

            $rec->mot_prices = mot_price::where(['mot_id' => $rec->id])
                ->select('id', 'begdt', 'enddt', 'hour_work_cost', 'hour_fuel_cost'
                    , db::raw("case
                          when now() between begdt and ifnull(enddt,now()) then 1
                          else 0 end as cur_price"))
                ->orderBy('begdt', 'desc')
                ->get();

        }

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
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

        $userid = \Auth::user()->id;
        //
        $messages = [
            'machineid.required' => 'Не задана техника',
            'opertypeid.required' => 'Укажите режим эксплуатации техники',
        ];

        $rules = [
            "machineid" => "required",
            "opertypeid" => "required",
        ];

        $request->validate($rules, $messages);

        $machineid = $request->get('machineid');

        $userid = \Auth::user()->id;


        $mess = "";
        if ($id == -1) {

            $rec = new mchn_opertype([
                "machineid" => $machineid,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = mchn_opertype::find($id);
            $mess = "Запись обновлена";
        }
        $rec->opertypeid = $request->get('opertypeid');
        $rec->name = $rec->opertype->name;
        $rec->descript = $request->get('descript');
        $rec->active = $request->get('active', 1);

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();


        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        //connectify('success', $rec->docinfo . ' / ' . $rec->docsum, $mess);

        //---------------------------------------------------------------------------------------------


        $retURL = $request->get('retURL') ?? route('machines.edit', $rec->machineid) . '?#mchn_opertypes';

        return redirect($retURL)->with('success', $mess);

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        $userid = \Auth::user()->id;

        $res = mchn_opertype::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('mchn_opertypes.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . $res->obj['name'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('machines.edit', $res->obj['machineid']);
            connectify('success', $res->obj['name'], 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    static public function list_for(Request $request)
    {
        //2021-05-14 SNS. Список для select-ов {id,name}

        $result = "";
        try {

            $list = mchn_opertype::lstFor([
                'machineid' => $request->machineid,
                'active' => $request->active,
            ]);

            $result = array('list' => $list);
            //Log::info(implode('; ', $list));

        } catch (\Exception $e) {
            Log::error('machine::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }
}
