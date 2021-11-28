<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class dlvrydoc extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'dlvrydocs';
    static public $sysobjid = 933;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'inituserid')->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function tgt_wrh()
    {
        return $this->hasOne(wrh::class, 'id', 'tgt_wrhid')->withDefault();
    }

    public function getstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'getstaffid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')->withDefault();
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

    public static $statuses = [
        0 => 'черновик',
        1 => 'формирование состава',
        2 => 'подготовлен',
    ];

    public static function lstDocsForErSup($ownorgid)
    {
        return dlvrydoc::from('dlvrydocs as inv')
            ->join('orgs as o', 'o.id', 'inv.orgid')
            ->whereraw("'" . $ownorgid . "' in (inv.orgid)")
            ->where('inv.statusid', 1)//Активен, открыт для использования
            ->select('inv.id'
                , db::raw("concat('№',inv.docnum, ' от ', inv.docdate, ' / ', o.name, ' / ', inv.notes) as name"))
            ->get()
            ->pluck('name', 'id')->toArray();
    }
}
