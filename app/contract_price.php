<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class contract_price extends Model
{
    use DeleteTrait;


    protected $guarded = [];

    static public $prefix = 'contract_prices';
    static public $sysobjid = 153;


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
        return $this->hasOne(contract::class, 'id', 'contractid')
            ->withDefault();
    }

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid')
            ->withDefault();
    }

    public function machine()
    {
        return $this->hasOne(machine::class, 'id', 'objid')->where('sysobjid',482)
            ->withDefault();
    }

    public static function listPricesForContract($contractid)
    {
        if (isset($contractid)) {

            $lst = self::from('contract_prices as cp')
                ->leftJoin('machines', function ($j) {
                    $j->on('machines.id', 'cp.objid')
                        ->where('cp.sysobjid', 482);
                })
                ->where('cp.contractid', $contractid)
                ->select('cp.id', 'cp.price', 'cp.sysobjid', 'cp.objid')
                ->selectraw('ifnull(machines.name,concat(cp.sysobjid,":",cp.objid)) as objname')
                ->orderby('objname')
                ->get();
            return $lst;
        }
        return null;

    }

    public static function contractPriceForObj($contractid, $sysobjid, $objid)
    {
        if (isset($contractid) and isset($sysobjid) and isset($objid)) {
            return contract_price::where('contractid', $contractid)
                    ->where('sysobjid', $sysobjid)
                    ->where('objid', $objid)
                    ->first()->price ?? null;
        }
        return null;
    }

}
