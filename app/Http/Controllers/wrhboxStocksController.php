<?php

namespace Modules\Stock\Http\Controllers;

use App\refitem;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Routing\Controller;
use Modules\Stock\Entities\wrh_stock;
use Modules\Stock\Entities\wrhbox_stock;

class wrhboxStocksController extends Controller
{
    /**
     * Display a listing of the resource.
     * @return Response
     */
    public function index()
    {
        return view('stock::index');
    }

    public function ri_index($refitmid, $wrhid = null)
    {
        $refitem = refitem::select('id', 'searchname as name', 'unit')->find($refitmid);

        $wrhstock_qty = wrh_stock::where('refitmid', $refitmid);
        if (isset($wrhid))
            $wrhstock_qty = $wrhstock_qty->where('wrhid', $wrhid);

        $refitem->wrhstock_qty = $wrhstock_qty->selectraw('sum(qty) as qty')->first()->qty ?? 0;
//dd($wrhstock_qty);

        $box_stocks = wrhbox_stock::from('wrhbox_stocks as wbs')
            ->join('wrh_boxes as wb', 'wb.id', '=', 'wbs.boxid')
            ->join('wrhs as w', 'w.id', '=', 'wb.wrhid')
            ->select('wb.wrhid', 'w.name as wrhname'
                , 'wb.code as boxcode'
                , 'wbs.id', 'wbs.refitmid', 'wbs.qty')
            ->where('wbs.refitmid', $refitmid);

        if (isset($wrhid))
            $box_stocks = $box_stocks->where('wb.wrhid', $wrhid);

        $box_stocks = $box_stocks->orderby('w.name')->orderby('w.id')
            ->orderby('wb.code')
            ->get();

        return view('stock::refitems.box_stocks', compact('refitem', 'box_stocks'));
    }

    /**
     * Show the form for creating a new resource.
     * @return Response
     */
    public function create()
    {
        return view('stock::create');
    }

    /**
     * Store a newly created resource in storage.
     * @param Request $request
     * @return Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Show the specified resource.
     * @param int $id
     * @return Response
     */
    public function show($id)
    {
        return view('stock::show');
    }

    /**
     * Show the form for editing the specified resource.
     * @param int $id
     * @return Response
     */
    public function edit($id)
    {
        return view('stock::edit');
    }

    /**
     * Update the specified resource in storage.
     * @param Request $request
     * @param int $id
     * @return Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     * @param int $id
     * @return Response
     */
    public function destroy($id)
    {
        //
    }
}
