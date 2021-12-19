<?php

namespace App\Imports;

use App\invoice;
use Maatwebsite\Excel\Concerns\ToModel;

class invoiceImport implements ToModel
{
    /**
    * @param array $row
    *
    * @return \Illuminate\Database\Eloquent\Model|null
    */
    public function model(array $row)
    {


        //если нет чего-либо - выходим
        if (!isset($row[1])) {
            return null;
        }


//        if (!is_numeric(str_replace(',', '.', $row[1]))) {
//            return null;
//        }

        //dd($row);


        return new invoice([
            //
        ]);
    }
}
