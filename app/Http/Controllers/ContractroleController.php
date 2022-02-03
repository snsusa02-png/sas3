<?php

namespace App\Http\Controllers;

use App\contractrole;
use App\contractroletype;
use Illuminate\Http\Request;
use Cache;

class ContractroleController extends Controller
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
     * @param \App\contractroletype $contractrole
     * @return \Illuminate\Http\Response
     */
    public function show(contractrole $contractrole)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\contractrole $contractrole
     * @return \Illuminate\Http\Response
     */
    public function edit(contractrole $contractrole)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\contractrole $contractrole
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, contractrole $contractrole)
    {
        //

        //Cache::forget('contractroles_typeid_' . $contracttypeid);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\contractrole $contractrole
     * @return \Illuminate\Http\Response
     */
    public function destroy(contractrole $contractrole)
    {
        //
        //Cache::forget('contractroles_typeid_' . $contracttypeid);
    }

    static public function list_for_contracttypeid(Request $request)
    {
        $result = "";
        try {
            $contracttypeid = $request->typeid;

            $list = contractrole::list_for_contracttypeid($contracttypeid);

            $result = array('roles' => $list);

        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }
        return response()->json($result);

    }


}
