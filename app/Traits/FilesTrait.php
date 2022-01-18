<?php

namespace App\Traits;

use App\obj_address;
use App\obj_contact;
use App\obj_link;
use App\objextid;
use App\objfile;
use App\objflag;
use DB;
use App\Traits\Result;
use App\task;

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

    public function flags()
    {
        //возращает список флагов, связанных с объектом
        $userid = \Auth::user()->id;
        return $this->hasMany(objflag::class, 'objid', 'id')
            ->join('flagtypes as ft', 'ft.id', 'objflags.flagtypeid')
            ->select('objflags.*', 'ft.name as flagtype_name')
            ->where('sysobjid', self::$sysobjid)
            ->orderBy('flagtype_name');
    }

    public function contacts()
    {
        //возращает список контактной информации (телефоны, адреса ЭП, ...)
        $userid = \Auth::user()->id;
        return $this->hasMany(obj_contact::class, 'objid', 'id')
            ->join('contacttypes as ct', 'ct.id', 'obj_contacts.contacttypeid')
            ->select('obj_contacts.*', 'ct.name as contacttype_name')
            ->where('sysobjid', self::$sysobjid)
            ->orderBy('contacttype_name');
    }

    public function linked_tasks()
    {
        return $this->hasMany(task::class, 'srcobjid', 'id')
            ->where([
                'srcsysobjid' => self::$sysobjid,
            ]);
    }

    public function addresses()
    {
        //возращает список адресов субъекта ИС
        $userid = \Auth::user()->id;
        return $this->hasMany(obj_address::class, 'objid', 'id')
            ->join('addresstypes as at', 'at.id', 'obj_addresses.addresstypeid')
            ->select('obj_addresses.*', 'at.name as addresstype_name')
            ->where('sysobjid', self::$sysobjid)
            ->orderBy('addresstype_name');
    }


}
