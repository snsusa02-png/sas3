<?php

namespace App\Exports;

use App\prodplan;
use App\prodplan_item;
use App\prodplan_items;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\FromCollection;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Illuminate\Support\Facades\DB;

//class ppr_Export implements FromCollection
class ppr_Export implements FromView, ShouldAutoSize
{
    public function __construct( $docid)
    {
        $this->docid = $docid;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
    public function collection()
    {
        return prodplan_item::where('docid',$this->docid)->get();
    }

    public function view(): View
    {
        $data = new \stdClass();
        $data->doc = prodplan::find($this->docid);

        //$data->items = prodplan_item::where('docid',$this->docid)->get();
        $data->items = prodplan_item::from('prodplan_items as i')
            ->leftJoin('prodplan_items as i1', 'i1.id', 'i.parid')
            ->leftJoin(DB::raw('(SELECT p.ppiid, sum(edi.itmsum/edi.qty*p.plnqty) as getsum
                    FROM ppi_edi_parts as p
                    join estdoc_items as edi on edi.id=p.ediid
                    group by p.ppiid) as s'),
                function ($join) {
                    $join->on('i.id', '=', 's.ppiid');
                })
            ->where('i.docid', $this->docid)
            ->where('i.lvltypeid', '>', 1)
            ->whereNotNull('i.drctbegdt')
            ->whereNotNull('i.drctenddt')
            ->select('i1.name as par_name', 'i.parid', 'i1.ordr as par_ordr', 'i1.plnqty as par_plnqty', 'i1.unit as par_unit'
                , 'i.id', 'i.name', 'i.plnqty', 'i.unit', 'i.ordr'
                , db::raw("date(i.drctbegdt) as begdate")
                , db::raw("date(i.drctenddt) as enddate")
                //, 'i.plncost'
                , 's.getsum as plncost')
            ->orderBy('i1.ordr')
            ->orderBy('i1.id')
            ->orderBy('i.drctbegdt')
            ->orderBy('i.drctenddt')
            ->orderBy('i.id')
            ->get();


        return view('prodplans.prnt_table_xls', [
            'data' => $data,
        ]);
    }
}
