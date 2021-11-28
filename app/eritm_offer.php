<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use DB;

class eritm_offer extends Model
{
    static public $prefix = 'eritm_offers';
    static public $sysobjid = 874;

    use DeleteTrait;

    protected $guarded = [];

    public function rqst_item()
    {
        return $this->belongsTo(equiprqst_item::class, 'eritmid', 'id');
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

    public function suporg()
    {
        return $this->hasOne(org::class, 'id', 'suporgid')->withDefault();
    }

    public function invoice()
    {
        return $this->hasOne(invoice::class, 'id', 'invoiceid')->withDefault();
    }

    static public function getOrgSupOfrList($orgid)
    {
        //20201106 SNS. Список товаров  по предложениям/заказам у контрагента
        return static::from('eritm_offers as ofr')
            ->join('equiprqst_items as eri', 'eri.id', '=', 'ofr.eritmid')
            ->leftjoin('refitems as ri', 'ri.id', '=', 'eri.refitmid')
            ->where('ofr.suporgid', '=', $orgid)
            ->select('ofr.id', 'ofr.plngetdate', 'ofr.ord_qty', 'ofr.ord_sum', 'ofr.ord_price', 'eri.unit'
                , db::raw("ifnull(ri.name, ifnull(ofr.itmname,eri.itmname)) as itm_name")

            )
            ->orderBy("itm_name", 'asc')
            ->get();
    }

}
