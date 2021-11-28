<?php

namespace Modules\Stock\Http\Controllers;

use App\Http\Middleware\IStock;
use App\objlog;
use App\org;
use Modules\Stock\Entities\org_wrh;
use Illuminate\Http\Request;
use Modules\Stock\Entities\wrh;

class OrgWrhController extends Controller
{
    public function __construct(IStock $stock)
    {

        $this->middleware('auth');
        $this->sysobjid = 208;
        $this->parsysobjid = 111;   //Orgs

        $this->stock = $stock;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index($orgid)
    {
        $org = org::find($orgid);
        $recs = org_wrh::where('orgid', $orgid)->paginate(5);
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
        return view('stock::wrhs.org_wrhs', compact(['org', 'recs', 'rec0']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($orgid)
    {
        $rec = new org_wrh();
        $rec->id = -1;
        $rec->orgid = $orgid;
        $org = org::find($orgid);
        $rec->$org;
        $rec->wrhs = wrh::from("wrhs as w")
            ->whereNotExists(function ($query) use ($rec) {
                $query->selectRaw(1)
                    ->from('org_wrhs as ow')
                    ->whereraw('ow.wrhid=w.id')
                    ->where('ow.orgid', $rec->orgid)
                    ->where("ow.id", '<>', $rec->id);
            })
            ->get()
            ->pluck("name", "id")->prepend("", "");
        return view('stock::org_wrhs.edit', compact(['rec', "org"]));
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\org_wrh $org_wrh
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $rec = org_wrh::find($id);
        $org = org::find($rec->orgid);
        $rec->org = $org;
        $rec->wrhs = wrh::from("wrhs as w")
            ->whereNotExists(function ($query) use ($rec) {
                $query->selectRaw(1)
                    ->from('org_wrhs as ow')
                    ->whereraw('ow.wrhid=w.id')
                    ->where('ow.orgid', $rec->orgid)
                    ->where("ow.id", '<>', $rec->id);
            })
            ->get()
            ->pluck("name", "id")->prepend("", "");
        return view('stock::org_wrhs.edit', compact(['rec', "org"]));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\org_wrh $org_wrh
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $request->validate([
            "wrhid" => "required",
        ]);

        $userid = \Auth::user()->id;
        $msg = "";
        if ($id == -1) {
            $rec = new org_wrh();
            $rec->id = null;
            $rec->orgid = $request->get('orgid');
            $rec->created_by = $userid;
            $rec->created_at = now();
            $msg = "Создана связь клиента и склада";
        } else {
            $rec = org_wrh::find($id);
            $msg = "Изменена запись о складе клиента";
        }
        $rec->wrhid = $request->get('wrhid');
        $rec->active = $request->get('active', 0);

        $rec->updated_by = $userid;
        //dd($rec);
        $rec->save();
        objlog::log_info(208, $rec->id, $msg, 5);

        return redirect(route('org_wrhs.index', $rec->orgid))->with('success', $msg);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\org_curator $org_curator
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $rec = org_wrh::find($id);
        $res = org_wrh::delete_by_id($id);
        //dd($res, $res->obj['orgid']);
        $sd = array();
        if ($res->err == 1) {
            $route = route('org_wrhs.edit', $id);
            $sd["error"] = $res->msg;
            objlog::log_info($this->sysobjid, $id, $res->msg, 2);

        } else {
            $parid = $res->obj['orgid'];
            $route = route('org_wrhs.index', $parid);
            $sd['success'] = 'Удалена связь со складом "' . $rec->wrhid . ': ' . $rec->wrh->name . '"';
            objlog::log_info($this->parsysobjid, $parid, $sd['success'], 5);
        }
        return redirect($route)->with($sd);
    }
}
