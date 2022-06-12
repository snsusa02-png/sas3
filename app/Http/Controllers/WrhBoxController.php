<?php

namespace app\Http\Controllers;

use App\objlog;
use App\Traits\snsTrait;
use Auth;
use App\budget_itmsum;
use App\buildobj;
use App\buildopertype;
use App\contract;
use App\wrh_box;
use App\wrhbox_stock;
use App\sysobj;
use App\usrsysright;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;

class WrhBoxController extends Controller
{
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 210;
        $this->sysobjcode = 'wrh_boxes';
        $this->objcode = $this->sysobjcode;
        //$this->moduleref = 'stock::';
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        //по acl указанного объекта
        $acl_sysobjcode = sysobj::where('code', $this->sysobjcode)
                ->select(db::raw("ifnull(acl_sysobjcode, code) as acl_sysobjcode"))
                ->first()->acl_sysobjcode ?? $this->sysobjcode;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.delete');
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
        //dd(1);
        if (usrsysright::isUserHasRightByCode(Auth::id(), $this->objcode . '.read')) {

            $items = wrh_box::select('w.id', 'w.name', 'w.address', 'w.descript', 'w.active', 'forsale')
                ->from('wrhs as w')
                ->orderBy('w.name', 'asc')
                ->paginate(10);

            $orgid = Auth::user()->curorgid;

            $usrrights = $this->setInterfaceRight(-1);

//            return view($this->moduleref . $this->objcode . '.index', compact('items', 'usrrights', 'orgid'));
            return view($this->objcode . '.index', compact('items', 'usrrights', 'orgid'));
        } else
            //return redirect(route('nsi'));
            return redirect(route('home'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $wrhid)
    {
        $usrrights = $this->setInterfaceRight(-1);

        if (!$usrrights['create']) {
            $retURL = (isset($wrhid)) ? route('wrhs.edit', $wrhid) : route('wrhs.index');
            return redirect($retURL)->with(['error' => 'У Вас нет прав на создание записей!']);
        }

        return $this->edit($request, -1, $wrhid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\wrh_box $wrh
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $wrhid = null)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);

        if (!$usrrights['read']) {
            return view('home');
        }


        if ($id == -1) {
            $rec = new wrh_box([
                'id' => -1,
                'wrhid' => $wrhid,
                'active' => 1,
                'created_by' => $userid,
            ]);
        } else
            $rec = wrh_box::find($id);

        if (!isset($rec))
            return redirect(route($this->sysobjcode . '.index'));


        $rec->orgcontractid = $rec->orgid . ':' . $rec->contractid;

        if (false) {
            $rec->buildobjs = buildobj::lstFor([
                'link_wrh' => $rec->wrhid,
            ]);

            if ($rec->id == -1 and count($rec->buildobjs) == 1)
                $rec->buildobjid = array_key_first($rec->buildobjs);

            if (isset($rec->buildobjid)) {
                $rec->buildopertypes = buildopertype::lstFor([
                    'buildobjid' => $rec->buildobjid,
                ]);

                if (isset($rec->buildopertypeid))
                    $rec->orgcontracts = contract::list_orgcontracts_for_buildopertypeid($rec->buildopertypeid);
            }
        }

        if ($rec->id <> -1) {
            $id = $rec->id;
            Cache::forget('wrh_stocks_' . $id);
            $rec->stocks = Cache::remember('wrh_stocks_' . $id, now()->addMinutes(5)
                , function () use ($id) {
                    return wrhbox_stock::from('wrh_stocks as ws')
                        ->join('refitems as ri', 'ri.id', 'ws.refitmid')
                        ->join('unittypes as ut', 'ut.id', 'ri.unittypeid')
                        ->where('ws.boxid', $id)
                        ->select('ws.id', 'ws.refitmid', 'ws.qty'
                            , 'ri.name as ri_name', 'ri.descript as ri_descript', 'ut.name as unit')
                        ->orderby('ri.name')
                        ->get();
                });
        }

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\wrh_box $wrh
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['save'])
//            return redirect(route('nsi'));
            return redirect(route('admin'));

        //
        $messages = [
            'name.required' => 'Опишите назначение отделения склада',
        ];

        $rules = [
            "name" => "required",
        ];

        $request->validate($rules, $messages);


        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new wrh_box([
                "wrhid" => $request->get('wrhid'),
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись о новой группе создана";
        } else {
            $rec = wrh_box::find($id);
            $mess = "Запись обновлена";
        }

        $rec->code = $request->get('code') ?? 'AAA';
        $rec->name = $request->get('name');
        $rec->buildobjid = $request->get('buildobjid');
        $rec->buildopertypeid = $request->get('buildopertypeid');

        $orgcontractid = $request->get('orgcontractid');
        $tids = explode(':', $orgcontractid);
        $rec->orgid = $tids[0] ?? null;   //исходный заказчик (подрядчик)
        $rec->contractid = $tids[1] ?? null;   //договор подряда/бюджета

        $rec->orgid = ($rec->orgid == '') ? null : $rec->orgid;
        $rec->contractid = ($rec->contractid == '') ? null : $rec->contractid;

        $rec->bdgtitmsumid = budget_itmsum::findByContractID_BuildOperTypeID_AcntTypeID(
            $rec->contractid,
            $rec->buildopertypeid,
            21 /*Материалы*/
        );

        $rec->descript = $rec->buildopertype->name . ' / ' . $rec->org->name;

        $rec->active = $request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        //соберем строку с измененными полями -------------------------------------------------------------------
        $diffs = $this->field_diff_list($rec, ['id', 'created_by', 'updated_by', 'created_at', 'updated_at']);
        if ($diffs === '')
            $msg_simple = $rslt_msg = "Запись пересохранена без изменений";
        else {
            $msg_simple = 'Запись ' . (($id == -1) ? 'создана' : 'изменена');
            $rslt_msg = $msg_simple . ': ' . $diffs;
        }
        //-------------------------------------------------------------------------------------------------------

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $rslt_msg, 5);
        //connectify('success', $rec->name, $msg_simple);

        return redirect(route('wrhs.edit', $rec->wrhid))->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\wrh_box $wrh
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['delete'])
            return redirect(route('wrh_boxes.edit', $id))->with(['error' => 'У Вас нет прав на эту операцию!']);

        $res = wrh_box::delete_by_id($id);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route('wrh_boxes.edit', $id);
            $sd["error"] = $res->msg;
            objlog::log_info($this->sysobjid, $res->obj['id'], "Попытка удаления записи: " . $res->msg, 4);
        } else {
            $wrhid = $res->obj['wrhid'];
            $route = route('wrhs.edit', $wrhid);
            $msg = 'Запись об отделении склада удалена';
            $sd['success'] = $msg;
            objlog::log_info($this->sysobjid, $res->obj['id'], $msg, 5);

            $tname = $res->obj['name'];
            $msg = "Запись об отделении склада ({$res->obj['id']}.{$tname}) удалена";
            objlog::log_info(202, $res->obj['wrhid'], $msg, 5);
        }
        return redirect($route)->with($sd);
    }


    static public function list_for(Request $request)
    {
        //2021-05-26 SNS. Обертка для вызова wrh_box::lstFor

        $result = "";
        try {

            $list = wrh_box::lstFor([
                'wrhid' => $request->wrhid,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
            ]);


            $result = array('boxes' => $list);

        } catch (\Exception $e) {
            Log::error('wrh_boxes::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }
}
