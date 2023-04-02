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
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

//class PayPlanExport implements FromQuery, WithHeadings
class rep46Export implements WithMultipleSheets
{
    use SearchDataTrait;

    public function __construct($data, $recs, $recs2, $recs3)
    {
        $this->data = $data;
        $this->recs = $recs;
        $this->recs2 = $recs2;
        $this->recs3 = $recs3;
    }

    /**
     * @return array
     */
    public function sheets(): array
    {
        $sheets = [];

//        for ($month = 1; $month <= 12; $month++) {
//            $sheets[] = new InvoicesPerMonthSheet($this->year, $month);
//        }
        $sheets[] = new rep46_1_Export($this->data, $this->recs);
        $sheets[] = new rep46_2_Export($this->data, $this->recs2);
        $sheets[] = new rep46_3_Export($this->data, $this->recs3);

        return $sheets;
    }

}
