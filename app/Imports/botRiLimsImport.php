<?php

namespace App\Imports;

use App\bot_ri_lim;
use Maatwebsite\Excel\Concerns\ToModel;

class botRiLimsImport implements ToModel
{
    /**
     * @param array $row
     *
     * @return \Illuminate\Database\Eloquent\Model|null
     */
    public function model(array $row)
    {
        return new bot_ri_lim([
            'buildopertypeid' => $row[0],
            'refitmid' => $row[1],
            'lim_qty' => $row[2],
            'smet_price' => $row[3],
        ]);
    }
}
