<?php

namespace App\Http\Controllers;

use App\machine;
use App\sysfunc;
use App\sysobj;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class sysfuncController extends Controller
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    static public function list_for(Request $request)
    {
        //2021-10-19 SNS. Обертка для вызова sysfunc::lstFor

        $result = "";
        try {

            $list = sysfunc::lstFor([
                's_name' => $request->s_name,
                's_code' => $request->s_code,
            ]);


            $result = array('sysfuncs' => $list);

        } catch (\Exception $e) {
            Log::error('sysfunc::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

    static public function list_for_ac(Request $request)
    {
        //2021-10-19 SNS. Для автокомплита

        $result = "";
        try {

            $list = sysfunc::getFor([
                's_name' => $request->s_name,
            ],
                ['sf.id', 'sf.code', 'sf.name']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('sysfunc::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
