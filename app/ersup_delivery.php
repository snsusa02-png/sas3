<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class ersup_delivery extends Model
{
    static public $prefix = 'ersup_deliveries';
    static public $sysobjid = 884;

    use DeleteTrait;

    protected $guarded = [];

    public function supply_item()
    {
        return $this->belongsTo(eritm_supply::class, 'ersupid', 'id');
    }

    public function dlvrydoc()
    {
        return $this->hasOne(invoice::class, 'id', 'dlvrydocid');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public static function updQty00($ersupid)
    {
        //Обновление кол-ва полученного на линии/складе по иерархии вверх
        $eritm_supply = eritm_supply::find($ersupid);

        if (isset($eritm_supply)) {
            $totQty = ersup_delivery::where('ersupid', $ersupid)
                    ->selectRaw('sum(dlvrd_qty) as qty')
                    ->first()->qty ?? 0;

            eritm_supply::where('id', $ersupid)->update(['dlvrd_qty' => $totQty]);
            //dd($totQty);

            //  -- по предложению -----------
            $offerid = $eritm_supply->offerid;
            $totQty = eritm_supply::where('offerid', $offerid)
                    ->selectRaw('sum(dlvrd_qty) as qty')
                    ->first()->qty ?? 0;

            eritm_offer::where('id', $offerid)->update(['dlvrd_qty' => $totQty]);
            //dd($offerid, $totQty);

            //  -- по позиции заявки --------
            $eritmid = $eritm_supply->eritmid;
            $totQty = eritm_offer::where('eritmid', $eritmid)
                    ->selectRaw('sum(dlvrd_qty) as qty')
                    ->first()->qty ?? 0;

            equiprqst_item::where('id', $eritmid)->update(['dlvrd_qty' => $totQty]);
        }
    }

    public static function updQty($erofrid)
    {
        //Обновление кол-ва полученного на линии/складе по иерархии вверх
        $eritm_offer = eritm_offer::find($erofrid);

        if (isset($eritm_offer)) {
            //Итого по предложению(счету) -offer
            $totQty = ersup_delivery::where('erofrid', $erofrid)
                    ->selectRaw('sum(dlvrd_qty) as qty')
                    ->first()->qty ?? 0;

            eritm_offer::where('id', $erofrid)->update(['dlvrd_qty' => $totQty]);
            //dd($offerid, $totQty);

            //todo:: если получено==заказано (полное получение), то можно заполнить eritm_supplies.dlvrd_qty

            //  -- по позиции заявки --------
            $eritmid = $eritm_offer->eritmid;
            $totQty = eritm_offer::where('eritmid', $eritmid)
                    ->selectRaw('sum(dlvrd_qty) as qty')
                    ->first()->qty ?? 0;

            equiprqst_item::where('id', $eritmid)->update(['dlvrd_qty' => $totQty]);
        }
    }
}
