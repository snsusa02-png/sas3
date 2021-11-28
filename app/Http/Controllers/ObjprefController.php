<?php

namespace App\Http\Controllers;

use App\objpref;
use App\preftype;
use App\objlog;
use Cache;
use Illuminate\Http\Request;
use Route;

class ObjprefController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 14;
        $this->objcode = 'objprefs';

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
     * @param \App\objpref $objpref
     * @return \Illuminate\Http\Response
     */
    public function show(objpref $objpref)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\objpref $objpref
     * @return \Illuminate\Http\Response
     */
    public function edit(objpref $objpref)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\objpref $objpref
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, objpref $objpref)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\objpref $objpref
     * @return \Illuminate\Http\Response
     */
    public function destroy(objpref $objpref)
    {
        //
    }


    public function editGlblPref25($msg = null)
    {
        //

        //варианты значений для преференции 25
        $vals = [];
        $lst = preftype::select('valsrcdef')->find(25);
        if (isset($lst)) {
            $lst = explode(';', $lst->valsrcdef);
            foreach ($lst as $item) {
                $t = explode('|', $item);
                $vals += [$t[0] => $t[1]];
            }
        }

        $rec = objpref::where('preftypeid', 25)->where('sysobjid', 3)->wherenull('objid')
            ->select('id', 'prefvalue')
            ->first();
        if (!isset($rec)) {
            $rec = new  objpref([
                'sysobjid' => 3,
                'preftypeid' => 25,
                'objid' => null,
            ]);
        }
        $rec->prefvals = $vals;

        return view('objprefs.pref_catqtyfmt', compact(['rec']))->with('success', $msg);
    }


    public
    function setGlblPref25(Request $request)
    {
        $messages = [
            'prefvalue.required' => 'Укажите вариант',
        ];

        $request->validate([
            "prefvalue" => "required",
        ], $messages);

        $userid = \Auth::user()->id;

        $rec = objpref::where('preftypeid', 25)->where('sysobjid', 3)->wherenull('objid')->first();

        $mess = "Запись изменена";
        if (!isset($rec)) {

            $rec = new objpref();
            $rec->preftypeid = 25;
            $rec->sysobjid = 3;
            $rec->objid = null;
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись";
        }

        $preval = $rec->prefvalue;
        $prefvalue = $request->get('prefvalue');
        $rec->prefvalue = $prefvalue;
        $rec->n_val = $prefvalue;
        $rec->updated_by = $userid;
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, "установлено: $prefvalue. Ранее было: $preval, ", 2);

        Cache::forget('objpref:' . 3 . ':' . '_' . ':' . 25);

        //останемся в созданной записи
//        return view('stocksimple::refitems.pref_catqtyfmt', compact(['rec']))->with('success', $mess);
//        return $this->editGlblPref25($mess);
        return redirect("/nsi?tab=nsi-stock");
    }
}
