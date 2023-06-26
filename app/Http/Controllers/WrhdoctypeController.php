<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\wrhdoctype;

class WrhdoctypeController extends Controller
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
     * @param \App\wrhdoctype $wrhdoctype
     * @return \Illuminate\Http\Response
     */
    public function show(wrhdoctype $wrhdoctype)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\wrhdoctype $wrhdoctype
     * @return \Illuminate\Http\Response
     */
    public function edit(wrhdoctype $wrhdoctype)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\wrhdoctype $wrhdoctype
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, wrhdoctype $wrhdoctype)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\wrhdoctype $wrhdoctype
     * @return \Illuminate\Http\Response
     */
    public function destroy(wrhdoctype $wrhdoctype)
    {
        //
    }

    public function params(Request $request)
        //, $wrhdoctypeid
    {
        $doctypeid = $request->get("doctypeid");

        $sess_var_lbl = 'wrhdoctype_' . $doctypeid . '_params';
        if (1 == 1 and null !== session([$sess_var_lbl]))
            return session([$sess_var_lbl]);
        else {
            $data = "";
            try {
                $data = wrhdoctype::
                select('need_relwrh', 'need_predoc', 'need_org', 'ri_produced', 'ownorg_label', 'wrh_label', 'relwrh_label', 'box_label', 'relbox_label')
                    ->find($doctypeid)->toArray();
                session([$sess_var_lbl => response()->json($data)]);

            } catch (\Exception $e) {
            }
        }
        return response()->json($data);
    }
}
