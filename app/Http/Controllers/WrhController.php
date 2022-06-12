<?php

namespace app\Http\Controllers;

use App\objflag;
use App\objlog;
use App\org;
use App\orgdep;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\wrh;
use App\buildobj_wrh;
use App\wrh_stock;
use Auth;
use App\usrsysright;
use App\grptype;
use App\group;
use App\grpitem;
use App\objextid;
use App\wrh_box;

use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WrhController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 202;
        $this->sysobjcode = 'wrhs';
        $this->objcode = $this->sysobjcode;
        //$this->moduleref = 'stock::';
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.delete');
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

            $items = wrh::select('w.id', 'w.name', 'w.address', 'w.descript', 'w.active', 'forsale')
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


    //Поиск
    public function search_low(Request $request/*, $orgid*/, $blade_name, $skip_null)
    {
        //dd($request);
        //снесем концевые пробелы
        $s_name = mb_ereg_replace("(^\s+)|(\s+$)/", "",
            $request->get("s_name"));
//        $search_type = mb_ereg_replace("(^\s+)|(\s+$)/", "",
//            $request->get("s_type"));
        $s_address = mb_ereg_replace("(^\s+)|(\s+$)/", "",
            $request->get("s_address"));


        //Если параметры поиска не заданы, то уйдем на index
        if ($skip_null and strlen($s_name . $s_address) == 0) return redirect()->route('wrhs.index');

        $items = wrh::rqListWrhByCond($s_name, $s_address)
            ->orderBy('name', 'asc')
            ->paginate(10);

        $usrrights = $this->setInterfaceRight(-1);

        //номер первой записи на странице:
        $rec0 = $items->currentPage() * $items->perPage() - $items->perPage() + 1;
        return view($blade_name, compact('items', 'usrrights', 'rec0', 's_name', 's_address'));
    }

    public function list(Request $request)
    {
        return $this->search_low($request, $this->objcode . '.list', false);
    }

    public function search(Request $request)
    {
        return $this->search_low($request, $this->objcode . '.index', true);
    }

    //получение информации о складе (только id, имя)
    public function getshortinfo(Request $request)
    {
        $id = $request->get('id');
        $ref = wrh::rqShortInfo($id)->first();
        $result = array('id' => null, 'name' => null);
        if (!is_null($ref)) {
            $result['id'] = $ref->id;
            $result['name'] = $ref->name;
        }
        return response()->json($result);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return $this->edit($request, -1);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\wrh $wrh
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);
        //$usrrights['org_places.read'] = true; //usrsysright::isUserHasRightByCode_cached($userid, 'org_places.read');
        //$usrrights['org_places.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_places.read');
        if (!$usrrights['read']) {
            return view('home');
        }


        if ($id == -1) {
            $rec = new wrh([
                'id' => -1,
                'active' => 1,
                'created_by' => $userid,
            ]);
        } else
            $rec = wrh::find($id);

        if (!isset($rec))
            return redirect(route($this->sysobjcode . '.index'));


//        $auxinfo = wrh::AuxInfo($id);
//        for ($x = 0; $x <= count($auxinfo) - 1; $x++) {
//            if ($auxinfo[$x]["reccount"] > 0 and $usrrights['delete'])
//                $usrrights['delete'] = false;
//        }


        /*$rec->buildobjs = buildobj_wrh::from('buildobj_wrhs as bow')
            ->join('buildobjs as bo', 'bo.id', 'bow.buildobjid')
            ->where(['bow.wrhid' => $rec->id])
            ->select(['bow.id', 'bo.name as buildobj_name', 'bow.active', 'bow.notes'])
            ->orderBy('buildobj_name')
            ->get();
        */

        /*$rec->boxes = wrh_box::from('wrh_boxes as wb')
            ->leftJoin('buildopertypes as bot', 'bot.id', 'wb.buildopertypeid')
            ->leftJoin('contracts as c', 'c.id', 'wb.contractid')
            ->leftJoin('orgs as o', 'o.id', 'wb.orgid')
            ->where('wrhid', $rec->id)
            ->select('wb.*', 'bot.name as bot_name', 'o.name as org_name'
                , db::raw("concat(c.docnum,' ',c.docdate) as contract_info")
            )
            ->get();*/

        $rec->boxes = wrh_box::from('wrh_boxes as wb')
            ->leftJoin('contracts as c', 'c.id', 'wb.contractid')
            ->leftJoin('orgs as o', 'o.id', 'wb.orgid')
            ->where('wrhid', $rec->id)
            ->select('wb.*', 'o.name as org_name'
                , db::raw("concat(c.docnum,' ',c.docdate) as contract_info")
            )
            ->get();

        $id = $rec->id;
        Cache::forget('wrh_stocks_' . $id);
        $rec->stocks = Cache::remember('wrh_stocks_' . $id, now()->addMinutes(5)
            , function () use ($id) {
                return wrh_stock::from('wrh_stocks as ws')
                    ->join('wrh_boxes as wb', 'wb.id', 'ws.boxid')
                    ->join('refitems as ri', 'ri.id', 'ws.refitmid')
                    ->join('unittypes as ut', 'ut.id', 'ri.unittypeid')
                    ->where('wb.wrhid', $id)
                    ->select('ws.refitmid', db::raw("sum(ws.qty) as qty")
                        , 'ri.name as ri_name', 'ri.descript as ri_descript', 'ut.name as unit')
                    ->groupby('ws.refitmid')
                    ->orderby('ri.name')
                    ->get();
            });

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\wrh $wrh
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['save'])
//            return redirect(route('nsi'));
            return redirect(route('admin'));

        //
        $request->validate([
            "name" => "required",
        ]);
        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new wrh([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись о новом складе создана";
        } else {
            $rec = wrh::find($id);
            $mess = "Запись о складе обновлена";
        }
        $rec->name = $request->get('name');
        $rec->address = $request->get('address');
        $rec->descript = $request->get('descript');

        $rec->active = $request->get('active', 0);
        $rec->forsale = $request->get('forsale', 0);
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

        return redirect(route('wrhs.index'))->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\wrh $wrhid
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['delete'])
//            return redirect(route('nsi'));
            return redirect(route('admin'));

        $res = wrh::delete_by_id($id);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route('wrhs.edit', $id);
            $sd["error"] = $res->msg;
            objlog::log_info($this->sysobjid, $res->obj['id'], "Попытка удаления записи: " . $res->msg, 4);
        } else {
            $route = route('wrhs.index');
            $msg = 'Запись о складе удалена';
            $sd['success'] = $msg;
            objlog::log_info($this->sysobjid, $res->obj['id'], $msg, 5);
        }
        return redirect($route)->with($sd);
    }

    public function wrh_groups_edit($wrhid)
    {
        $userid = \Auth::user()->id;
        $wrh = wrh::find($wrhid);

        $usrrights = $this->setInterfaceRight($wrhid);
        $usrrights['delete'] = false;

        $recs = Cache::remember('wrh_gt_groups_.' . $wrhid, now()->addMinutes(15)
            , function () use ($wrhid) {
                return grptype::from('grptypes as t')
                    ->select('g.grptypeid', 't.name as typename', 'g.id as grpid', 'g.name as grpname', 'i.objid')
                    ->join('groups as g', 'g.grptypeid', '=', 't.id')
                    ->leftJoin('grpitems as i', function ($j) use ($wrhid) {
                        $j->on('i.grpid', '=', 'g.id')
                            ->where('i.sysobjid', $this->sysobjid)
                            ->where('i.objid', $wrhid);
                    })
                    ->where('t.forsysobjid', $this->sysobjid)
                    ->orderby('t.ordr')
                    ->orderby('t.name')
                    ->orderby('g.ordr')
                    ->orderBy('g.name')
                    ->get()->toArray();
            });

        $gt_groups = [];
        $curGrpTypeID = null;
        $curGrpTypeName = null;
        $groups = [];
        $value = null;
        foreach ($recs as $rec) {
            if ($rec['grptypeid'] <> $curGrpTypeID) {
                if (isset($curGrpTypeID)) {
                    $gt_groups[] = [
                        'id' => $curGrpTypeID,
                        'name' => $curGrpTypeName,
                        'groups' => $groups,
                        'value' => $value,
                    ];

                }
                $curGrpTypeID = $rec['grptypeid'];
                $curGrpTypeName = $rec['typename'];
                $groups = [];
                $value = null;
            }
//            $groups[] = [$rec['grpid'] => $rec['grpname']];   //Добавляет лишний уровень
            $groups += [$rec['grpid'] => $rec['grpname']];

            $value = (!isset($value) and $rec['objid']) ? $rec['grpid'] : $value;
        }
        if (isset($curGrpTypeID)) {
            $gt_groups[] = [
                'id' => $curGrpTypeID,
                'name' => $curGrpTypeName,
                'groups' => $groups,
                'value' => $value,
            ];

        }
        //dd($gt_groups);
        $wrh->gt_groups = $gt_groups;

        return view($this->moduleref . 'wrhs.wrh_groups_edit', compact('wrh', "usrrights"));
    }

    public function wrh_groups_update(Request $request, $wrhid)
    {
        //$userid = \Auth::user()->id;
        $mess = '';
        $objname = wrh::select('name')->findOrFail($wrhid)->name;

        $gt_vals = $request->gt;
        foreach ($gt_vals as $grptypeid => $grpid) {
            grpitem::addOrRmvItem($grptypeid, $grpid, $this->sysobjid, $wrhid, $objname);
        }

        return redirect(route('wrhs.edit', $wrhid))->with('success', $mess);
    }

    public function wrh_extids($id)
    {
        $obj = wrh::findOrFail($id);

        $recs = objextid::where('sysobjid', $this->sysobjid)
            ->where('objid', $id)
            ->with('extsys')->get();
        return view($this->moduleref . $this->objcode . '.wrh_extids', compact('obj', 'recs'));
    }

    static public function list_for(Request $request)
    {
        //2021-05-26 SNS. Обертка для вызова wrh::lstFor

        $result = "";
        try {

            $list = wrh::lstFor([
                'buildobjid' => $request->buildobjid,
                'active' => $request->active,
            ]);


            $result = array('wrhs' => $list);

        } catch (\Exception $e) {
            Log::error('wrhs::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }
}
