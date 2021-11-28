<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;

class estdocitm_resource extends Model
{
    protected $guarded = [];

    static public function listMatForOperTypeID($buildopertypeid)
    {
        //Список материалов для заданного вида работ
        if (isset($buildopertypeid)) {
//            $lst = self::from('estdocitm_resources as r')
//                ->where('kind', 'Mat')
//                ->whereraw('r.estdocitmid in (SELECT id FROM estdoc_items as di where di.buildopertypeid=' . $buildopertypeid . ')')
//                ->select('r.id', 'r.code', 'r.name', 'r.units', 'r.qty', 'r.price', 'pricecurr_comment')
//                ->orderby('r.id')
//                ->get();
            //с группировкой по коду, цене, названию
            $lst = self::from('estdocitm_resources as r')
                ->where('kind', 'Mat')
                ->whereraw('r.estdocitmid in (SELECT id FROM estdoc_items as di where di.buildopertypeid=' . $buildopertypeid . ')')
                ->select('r.code', 'r.name', 'r.units', 'r.price', DB::raw('sum(r.qty) as qty'))
                //->select('r.code', DB::raw('sum(r.qty) as qty'))
                ->groupby(['r.code','r.name', 'r.units', 'r.price'])
                ->orderby('r.name')
                ->get();
            return $lst;
        } else
            return null;
    }

}
