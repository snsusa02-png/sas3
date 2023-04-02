<?php

namespace App\Exports;

use App\orgplnpay;
use App\orgplnpay_item;
use App\Traits\SearchDataTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;

use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\WithTitle;

//class PayPlanExport implements FromQuery, WithHeadings
class rep46_2_Export implements FromView, WithTitle
{
    use SearchDataTrait;

    public function __construct( $data, $recs)
    {
        $this->data = $data;
        $this->recs = $recs;
    }



    /**
     * @return string
     */
    public function title(): string
    {
        return 'Карьеры';
    }

    public
    function view(): View
    {
        return view('exports.rep46_2xls', [
            'data' => $this->data,
            'recs' => $this->recs
//            'recs3' => $this->recs3
        ]);
    }

}
