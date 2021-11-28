<?php

namespace App\Traits;

use App\obj_link;
use App\objextid;
use App\objfile;
use DB;
use App\Traits\Result;

//Трайт для
trait FilesTrait
{
    public function files()
    {
        //возращает список файлов, привязанных к объекту. С учетом прав доступа пользователя
        $userid = \Auth::user()->id;
        return $this->hasMany(objfile::class, 'objid', 'id')
            ->leftjoin('doctypes as dt', 'dt.id', 'objfiles.doctypeid')
            ->leftjoin('doctypes as dst', 'dst.id', 'objfiles.docsubtypeid')
            ->select('objfiles.*', 'dt.name as doctype_name', 'dst.name as docsubtype_name')
            ->where('sysobjid', self::$sysobjid)

            //Пользователь должен иметь доступ к соотв. категории информации
            ->whereRaw("exists (select 1 from doctypes as dt0
                join user_acs as uac on uac.acsid=dt0.acsid and uac.userid={$userid}
                where dt0.id=objfiles.doctypeid)")
            ->orderBy('objfiles.ordr')
            ->orderBy('objfiles.id');
    }

    public function photo()
    {
        $userid = \Auth::user()->id;
        return $this->hasOne(objfile::class, 'objid', 'id')
            ->where(['sysobjid' => self::$sysobjid,
                'doctypeid' => 254])
            //Пользователь должен иметь доступ к соотв. категории информации
            ->whereRaw("exists (select 1 from doctypes as dt
                join user_acs as uac on uac.acsid=dt.acsid and uac.userid={$userid}
                where dt.id=objfiles.doctypeid)");
    }

}
