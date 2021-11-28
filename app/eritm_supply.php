<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class eritm_supply extends Model
{
    static public $prefix = 'eritm_supplies';
    static public $sysobjid = 882;

    use DeleteTrait;

    protected $guarded = [];

    public function offer()
    {
        return $this->belongsTo(eritm_offer::class, 'offerid', 'id');
    }

    public function equiprqst_item()
    {
        return $this->belongsTo(equiprqst_item::class, 'eritmid', 'id');
    }

    public function invoice()
    {
        return $this->hasOne(invoice::class, 'id', 'invoiceid')->withDefault();
    }

    public function upd()
    {
        return $this->hasOne(invoice::class, 'id', 'invoiceid')->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    public static function recalc_getqty($offerid, $eritmid)
    {
        //Пересчитать общее полученное кол-во ------------------------------
        //  -- по предложению -----------
        if (isset($offerid)) {
            $totQty = eritm_supply::where('offerid', $offerid)
                    ->selectRaw('sum(get_qty) as qty, min(fctgetdate) as mingetdate, max(fctgetdate) as maxgetdate')
                    ->first()->qty ?? 0;
            eritm_offer::where('id', $offerid)
                ->update(['get_qty' => $totQty]);
        }

        //  -- по позиции заявки --------
        if (isset($eritmid)) {
            $totQty = eritm_supply::where('eritmid', $eritmid)
                    ->selectRaw('sum(get_qty) as qty')
                    ->first()->qty ?? 0;
            equiprqst_item::where('id', $eritmid)
                ->update(['get_qty' => $totQty]);
            //dd($totQty);
        }
        //------------------------------------------------------------------

    }


}
