<?php

namespace App;

use App\doc;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use DB;
use Cache;

class m15doc extends doc
{
    //
    use DeleteTrait;
    use FilesTrait;

    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'inituserid')->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = ' №' . $this->docnum
                . ' от ' . date_format(date_create($this->docdate), "d.m.Y");
            if (isset($this->docsum))
                $rslt .= ' сумма: ' . number_format($this->docsum, 2);
            return $rslt;
        } else
            return null;
    }



    public static function lstDocsForErSup($ownorgid)
    {
        return m15doc::from('m15docs as inv')
            ->join('orgs as o', 'o.id', 'inv.orgid')
            ->whereraw("'" . $ownorgid . "' in (inv.orgid)")
            ->where('inv.statusid', 1)//Активен, открыт для использования
            //->whereraw('ifnull(enddate,curdate())>=curdate()-90')
            ->select('inv.id'
                , db::raw("concat('№',inv.docnum, ' от ', inv.docdate, ' / ', o.name, ' / ', inv.notes) as name"))
            ->get()
            ->pluck('name', 'id')->toArray();
    }
}
