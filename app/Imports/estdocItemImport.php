<?php

namespace App\Imports;

use App\estdocItem;
use Maatwebsite\Excel\Concerns\ToModel;

class estdocItemImport implements ToModel
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
        //dd($row);

        return new estdocItem([
            //
        ]);
    }
}
