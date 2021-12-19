<?php

namespace App\Http\Controllers;

use App\obj_finoper;
use App\sysobj;
use Illuminate\Http\Request;

class ObjFinoperController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 525;
        $this->sysobj = sysobj::find($this->sysobjid);
        $this->sysobjcode = $this->sysobj->code;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }

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
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  \App\obj_finoper  $obj_finoper
     * @return \Illuminate\Http\Response
     */
    public function show(obj_finoper $obj_finoper)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\obj_finoper  $obj_finoper
     * @return \Illuminate\Http\Response
     */
    public function edit(obj_finoper $obj_finoper)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\obj_finoper  $obj_finoper
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, obj_finoper $obj_finoper)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\obj_finoper  $obj_finoper
     * @return \Illuminate\Http\Response
     */
    public function destroy(obj_finoper $obj_finoper)
    {
        //
    }
}
