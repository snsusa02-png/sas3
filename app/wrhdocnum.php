<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class wrhdocnum extends Model
{
    protected $fillable = [];

    public static function NxtDocNum($doctypeid, $ownorgid, $docdate)
    {   //возвращает очередной номер для заданного типа документа, продавца и даты документа
        $num = -1;

        $docdate = $docdate ?? today()->format('Y-m-d');

//        $num = wrhdoctype::selectraw('nxtwrhdocnum(id,' . $ownorgid . ',"' . $docdate . '") as num ')
//            ->find($doctypeid)
//            ->num;


        $num = DB::select("select nxtwrhdocnum({$doctypeid},{$ownorgid},'{$docdate}') AS num")[0]->num;
        //dd("select nxtwrhdocnum({$doctypeid},{$ownorgid},'{$docdate}') AS num", $doctypeid, $ownorgid, $docdate, $num);

        return $num;
    }
}
