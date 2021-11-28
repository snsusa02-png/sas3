<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use DB;

class contract_org extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'contract_orgs';
    static public $sysobjid = 152;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid');
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', self::$sysobjid)
            ->withDefault();
    }

    static public function contract_orgs($contractid)
    {// коллекция участников договора $contractid. С названиями ролей. С сортировкой по приоритету роли
        $data = self::from('contract_orgs as co')
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

    static public function addOrg2Party($contractid, $orgid, $party)
    {
        //Создать или обновить запись об оранизации в контракте
        // $roleid = 1 - 1-я сторона, 2 - 2-я сторона

        $contract = contract::find($contractid);
        if (isset($contract)) {

            $roleid = null;
            if (isset($party)) {
                $role = contractrole::where('contracttypeid', $contract->contracttypeid)
                    ->where('party', $party)
                    ->first();
                if (isset($role)) {
                    $roleid = $role->id;
                    $rolename = $role->name;
                }
            }

            //считаем, что организация в договоре может быть только 1 раз
            $rec = self::from('contract_orgs as co')
                ->where('contractid', $contractid)
                ->where('orgid', $orgid)
                ->first();
            if (!isset($rec)) {
                $rec = new contract_org([
                    'contractid' => $contractid,
                    'orgid' => $orgid,
                ]);
            }
            $rec->roleid = $roleid;
            $rec->rolename = $rolename ?? '-?-';
            $rec->save();
        }
    }


    static public function addOrUpdate($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $rec = self::where($search_params)->first();

            if (!isset($rec)) {
                $rec = new self($search_params);
            }
            $rec->fill($set_params);
            $rec->save();

            return $rec;
        }
        return null;
    }


}
