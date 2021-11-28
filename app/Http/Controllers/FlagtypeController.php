<?php

namespace App\Http\Controllers;

use App\flagtype;
use Illuminate\Http\Request;

class FlagtypeController extends Controller
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
     * @param  \App\flagtype  $flagtype
     * @return \Illuminate\Http\Response
     */
    public function show(flagtype $flagtype)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  \App\flagtype  $flagtype
     * @return \Illuminate\Http\Response
     */
    public function edit(flagtype $flagtype)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \App\flagtype  $flagtype
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, flagtype $flagtype)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  \App\flagtype  $flagtype
     * @return \Illuminate\Http\Response
     */
    public function destroy(flagtype $flagtype)
    {
        //
    }
}
