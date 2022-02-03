<?php

namespace App\Traits;

use App\obj_finoper;
use DB;
use App\Traits\Result;

//Трайт для
trait FinOpersTrait
{
    public function finopers()
    {
        //возращает список финансовых транзакций, порожденных исходным объектом
        return $this->hasMany(obj_finoper::class, 'objid', 'id')
            ->join('orgs as s_o', 's_o.id', 'obj_finopers.srcorgid')
            ->join('orgs as t_o', 't_o.id', 'obj_finopers.tgtorgid')
            ->leftjoin('opertypes as ot', 'ot.id', 'obj_finopers.opertypeid')
            ->where('sysobjid', self::$sysobjid)
            ->select('obj_finopers.*'
                , 's_o.name as srcorg_name'
                , 't_o.name as tgtorg_name'
                , 'ot.name as opertype_name'
            )
            ->orderBy('obj_finopers.operdate');
    }

}
