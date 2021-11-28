<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class ersup_m15 extends Model
{
    static public $prefix = 'ersup_m15s';
    static public $sysobjid = 883;

    use DeleteTrait;

    protected $guarded = [];

    public function supply_item()
    {
        return $this->belongsTo(eritm_supply::class, 'ersupid', 'id');
    }

    public function m15doc()
    {
        return $this->hasOne(invoice::class, 'id', 'm15docid')->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public static function updQty($ersupid)
    {
        //Обновление кол-ва переданого по иерархии вверх
        $eritm_supply = eritm_supply::find($ersupid);

        if (isset($eritm_supply)) {
            $totQty = ersup_m15::where('ersupid', $ersupid)
                    ->selectRaw('sum(m15_qty) as qty')
                    ->first()->qty ?? 0;
            eritm_supply::where('id', $ersupid)
                ->update(['m15_qty' => $totQty]);
            //dd($totQty);

            //  -- по предложению -----------
            $offerid = $eritm_supply->offerid;
            $totQty = eritm_supply::where('offerid', $offerid)
                    ->selectRaw('sum(m15_qty) as qty')
                    ->first()->qty ?? 0;
            eritm_offer::where('id', $offerid)
                ->update(['m15_qty' => $totQty]);
            //dd($offerid, $totQty);

            //  -- по позиции заявки --------
            $eritmid = $eritm_supply->eritmid;
            $totQty = eritm_offer::where('eritmid', $eritmid)
                    ->selectRaw('sum(m15_qty) as qty')
                    ->first()->qty ?? 0;
            equiprqst_item::where('id', $eritmid)
                ->update(['m15_qty' => $totQty]);
        }
    }
}
