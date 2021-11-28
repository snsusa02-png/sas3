<?php

namespace App\Http\Controllers;

use App\objlog;
use App\sysobj;
use DB;
use Illuminate\Http\Request;

class ObjlogController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param \App\objlog $objlog
     * @return \Illuminate\Http\Response
     */
    public function show(objlog $objlog)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\objlog $objlog
     * @return \Illuminate\Http\Response
     */
    public function edit(objlog $objlog)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\objlog $objlog
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, objlog $objlog)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\objlog $objlog
     * @return \Illuminate\Http\Response
     */
    public function destroy(objlog $objlog)
    {
        //
    }

    function evntlog($sysobjid, $objid, $route)
    {
        $obj = new \stdClass();
        $obj->id = $objid;
        $obj->sysobjid = $sysobjid;
        $obj->sysobjname = sysobj::find($sysobjid)->name;

        $recs = objlog::from('objlogs as e')
            ->join('users as u', 'u.id', '=', 'e.write_by')
            ->where('sysobjid', $sysobjid)
            ->where('objid', $objid)
            ->select(DB::raw('e.*'), 'u.name as username')
            ->selectRaw('case
                          when errlvl = 1 then "fatalerror"
                          when errlvl = 2 then "error"
                          when errlvl = 3 then "info"
                          when errlvl = 4 then "warning"
                          when errlvl = 5 then "debug"
                          when errlvl = 6 then "trace"
                          else "?"
                        end as errlvlname')
            ->orderby('write_at', 'desc')
            ->paginate(20);
        //номер первой записи на странице:
        $obj->rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;
        return view('objlog.evntlog', compact('obj', 'recs', 'route'));
    }

}
