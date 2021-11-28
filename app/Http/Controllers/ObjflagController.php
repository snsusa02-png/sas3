<?php

namespace App\Http\Controllers;

use App\objflag;
use Illuminate\Http\Request;

class ObjflagController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
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
     * @param  \App\objflag  $objflag
     * @return \Illuminate\Http\Response
     */
    public function show(objflag $objflag)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\objflag  $objflag
     * @return \Illuminate\Http\Response
     */
    public function edit(objflag $objflag)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\objflag  $objflag
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, objflag $objflag)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\objflag  $objflag
     * @return \Illuminate\Http\Response
     */
    public function destroy(objflag $objflag)
    {
        //
    }
}
