<?php

namespace App;

use Cache;
use DB;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;

class objextid extends Model
{
    use DeleteTrait;

    //protected $fillable = ["objid", "sysobjid", "extsysid", "extid", "created_by", "updated_by"];

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid')->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function org()
    {
        if ($this->sysobjid == 111)
            return $this->hasOne(org::class, 'id', 'objid')->withDefault();
        else return null;
    }

    public function extsys()
    {
        return $this->hasOne(extsystem::class, 'id', 'extsysid');
    }

//    static public function orgid_by_extsysid_extid0($extsysid, $extid)
//    {
//        $org = objextid::where('extsysid', $extsysid)
//            ->where('sysobjid', 111)
//            ->where('extid', $extid)
//            ->select('objid')
//            ->first();
//
//        return (isset($org)) ? $org->objid : null;
//    }

    static public function objid_by_extsysid_extid($extsysid, $sysobjid, $extid)
    {
        if (!isset($extsysid) or !isset($sysobjid) or !isset($extid))
            return null;

        $objid = Cache::remember('objid_by_extsysid_extid.' . $extsysid . '.' . $sysobjid . '.' . $extid, now()
            ->addMinutes(5)
            , function () use ($extsysid, $sysobjid, $extid) {

                $ext = objextid::where([['extsysid', $extsysid], ['sysobjid', $sysobjid], ['extid', $extid]])
                    ->select('objid')
                    ->first();
                return (isset($ext)) ? $ext->objid : null;
            });
        return $objid;
    }

    static public function orgid_by_extsysid_extid($extsysid, $extid)
    {
        //Устарело. 2019-09-19 Можео удалить
        return self::objid_by_extsysid_extid($extsysid, 111, $extid);
    }
}
