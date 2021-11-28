<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class invoice_item extends Model
{
    use DeleteTrait;

    static public $prefix = 'invoice_items';
    static public $sysobjid = 916;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];


    public function invoice()
    {
        //return $this->hasOne(invoice::class, 'id', 'invoiceid');
        return $this->belongsTo(invoice::class, 'invoiceid', 'id');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')->withDefault();
    }

    public function unittype()
    {
        return $this->hasOne(unittype::class, 'id', 'unittypeid')->withDefault();
    }

    static public function setRefItmIDByNameAndUnit($refitmid, $ri_name, $ri_unittypeid)
    {
        if (isset($refitmid) and isset($ri_name) and isset($ri_unittypeid)) {

            if (1==1) {
                self::from('invoice_items as ii')
                    ->whereNull('refitmid')
                    ->where(['itmname' => $ri_name, 'unittypeid' => $ri_unittypeid])
                    ->update(['refitmid' => $refitmid]);

                Cache::forget('informer_eri_ri_stat');
            }
        }
    }


}
