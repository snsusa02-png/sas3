<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use App\document;

class obj_org extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'obj_orgs';
    static public $sysobjid = 1611;


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
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid')
            ->withDefault();
    }

    static public function obj_orgs($contractid)
    {// коллекция участников договора $contractid. С названиями ролей. С сортировкой по приоритету роли
        $data = self::from('obj_orgs as co')
            ->leftjoin('contractroles as crt', 'crt.id', 'co.roleid')
            ->where('contractid', $contractid)
            ->select('co.id', db::raw("ifnull(crt.name, co.rolename) as rolename"), 'co.orgid', 'co.active')
            ->orderby('crt.party')
            ->orderby('crt.name')
            ->orderby('co.active', 'desc')
            ->with('org')
            ->get();

        return $data;
    }


    static public function addOrgWithRole($sysobjid, $objid, $orgid, $roletypeid)
    {
        //Создать или обновить запись об организации  для инф. объекта

        if (!isset($sysobjid) or !isset($objid) or !isset($orgid) or !isset($roletypeid))
            return false;

        //считаем, что организация в инф. объекте может быть только 1 раз
        $rec = self::from('obj_orgs')
            ->where([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'orgid' => $orgid,
            ])
            ->first();

        if (!isset($rec)) {
            $rec = new self([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'orgid' => $orgid,
            ]);
        }
        $rec->roletypeid = $roletypeid;
        $rec->save();
    }


}
