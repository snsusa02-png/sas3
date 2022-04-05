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

//class PayPlanExport implements FromQuery, WithHeadings
class PayPlanExport implements FromView
{
    use SearchDataTrait;

    public function __construct($recs, $data)
    {
        $this->recs = $recs;
        $this->data = $data;
    }

    /**
     * @return \Illuminate\Support\Collection
     */
//    public function collection()
//    {
//        return orgplnpay::all();
//    }


//    public function headings(): array
//    {
//        return [
//            'Id',
//            'name',
//            'email',
//            'createdAt',
//            'updatedAt',
//        ];
//    }
//
//    public function query()
//    {
//        //return Bulk::query();
//        /*you can use condition in query to get required result
//         return Bulk::query()->whereRaw('id > 5');*/
//    }
//
//    public
//    function map($bulk): array
//    {
//        return [
//            $bulk->id,
//            $bulk->name,
//            $bulk->email,
//            Date::dateTimeToExcel($bulk->created_at),
//            Date::dateTimeToExcel($bulk->updated_at),
//        ];
//    }

    public
    function view(): View
    {
        return view('exports.rep48xls', [
            'data' => $this->data,
            'items' => $this->recs
        ]);
    }

}
