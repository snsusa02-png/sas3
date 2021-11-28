<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\objlog;
use DB;
use App\Traits\Result;


class wrh_stock extends Model
{
    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function wrh()
    {
        return $this->hasOne(wrh::class, 'id', 'wrhid');
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid');
    }

    static public function recalc_stock()
    {
        DB::unprepared('CALL recalc_stock()');
        objlog::write(206, 0, 'Произведен пересчет товарных запасов на складах', null, 3, 1);
    }

    static public function take_from_stock($refitmid, $ownorgid, $wrhid
        , $sysobjid, $objid, $grpid
        , $rqstqty)
    {
        //Попробовать взять заданное кол-во товара со склада из запаса ($ownorgid, $wrhid, $sysobjid, $objid, $grpid)

        if (!isset($refitmid) or !isset($ownorgid) or !isset($wrhid)) {
            return 0;
        }

        $stock = wrh_stock::where('refitmid', $refitmid)
            ->where('ownorgid', $ownorgid)
            ->where('wrhid', $wrhid);

        if (isset($sysobjid))
            $stock = $stock
                ->where([
                    ['sysobjid', $sysobjid],
                    ['objid', $objid]
                ]);
        else {
            $stock = $stock
                ->whereNull('sysobjid')
                ->whereNull('objid');
        }

        if (isset($grpid))  //не нулл
            $stock = $stock->where('grpid', $grpid);
        else
            $stock = $stock->whereNull('grpid');

        $stock = $stock->first();

        if (!isset($stock)) {
            //не контролируем достоверность товара => можем создать "лишнюю" запись. Правда все-равно с нулевым остатком
            $stock = new wrh_stock([
                'refitmid' => $refitmid,
                'ownorgid' => $ownorgid,
                'wrhid' => $wrhid,
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'grpid' => $grpid,
                'qty' => 0,
                'plnincqty' => 0,
                'plnoutqty' => 0,
            ]);
        }

        //не можем опускаться ниже 0
        $stock_qty = $stock->qty ?? 0;  //wrh_stock не содержит записи с нулевыми остатками

        if ($stock_qty < $rqstqty) {
            //остаток на складе меньше, чем нужно. Но возьмем сколько есть
            $rqstqty = $stock_qty;
        }
        //уменьшим остаток
        $stock->qty -= $rqstqty;
        $stock->save();

        return $rqstqty;
    }

    static public function place_to_stock($refitmid, $ownorgid, $wrhid, $sysobjid, $objid, $grpid, $placeQty)
    {
        //Поместить указанное кол-во товара на склада в запас для указанного $sysobjid, $objid

        if (!isset($refitmid) or !isset($ownorgid) or !isset($wrhid)) {
            return 0;
        }
        $placeQty = ($placeQty < 0) ? 0 : $placeQty;

        $stock = wrh_stock::where('refitmid', $refitmid)
            ->where('ownorgid', $ownorgid)
            ->where('wrhid', $wrhid);

        if (isset($sysobjid))
            $stock = $stock
                ->where([
                    ['sysobjid', $sysobjid],
                    ['objid', $objid]
                ]);
        else {
            $stock = $stock
                ->whereNull('sysobjid')
                ->whereNull('objid');
        }

        if (isset($grpid))  //не нулл
            $stock = $stock->where('grpid', $grpid);
        else
            $stock = $stock->whereNull('grpid');

        $stock = $stock->first();

        if (!isset($stock)) {
            //не контролируем достоверность товара => можем создать "лишнюю" запись.
            // Правда все-равно с нулевым остатком
            $stock = new wrh_stock([
                'refitmid' => $refitmid,
                'ownorgid' => $ownorgid,
                'wrhid' => $wrhid,
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'grpid' => $grpid,
                'qty' => 0,
                'plnincqty' => 0,
                'plnoutqty' => 0,
            ]);
        }

//        $stock_qty = $stock->qty ?? 0;  //так как wrh_stock не содержит записи с нулевыми остатками


        //увеличим остаток
        $stock->qty += $placeQty;
        $stock->save();

        return $placeQty;
    }

    static public function take_from_commonstock($refitmid, $ownorgid, $wrhid, $rqstqty)
    {
        //Попробовать взять заданное кол-во товара со склада из общего запаса (ordid is null)

        if (!isset($refitmid) or !isset($ownorgid) or !isset($wrhid)) {
            return 0;
        }

        $stock = wrh_stock::where('refitmid', $refitmid)
            ->where('ownorgid', $ownorgid)
            ->where('wrhid', $wrhid)
            ->first();

        if (!isset($stock)) {
            //не контролируем достоверность товара => можем создать "лишнюю" запись. Правда все-равно с нулевым остатком
            $stock = new wrh_stock([
                'refitmid' => $refitmid,
                'ownorgid' => $ownorgid,
                'wrhid' => $wrhid,
                'qty' => 0,
                'plnincqty' => 0,
                'plnoutqty' => 0,
            ]);
        }

        //не можем опускаться ниже 0
        $stock_qty = $stock->qty ?? 0;  //wrh_stock не содержит записи с нулевыми остатками

        if ($stock_qty < $rqstqty) {
            //остаток на складе меньше, чем нужно. Но возьмем сколько есть
            $rqstqty = $stock_qty;
        }
        //уменьшим остаток
        $stock->qty -= $rqstqty;
        $stock->save();

        return $rqstqty;
    }

    static public function place_to_ordstock($refitmid, $ownorgid, $wrhid, $ordid, $placeQty)
    {
        //Поместить указанное кол-во товара на склада в запас для заказа ordid

        if (!isset($refitmid) or !isset($ownorgid) or !isset($wrhid) or !isset($ordid)) {
            return 0;
        }
        $placeQty = ($placeQty < 0) ? 0 : $placeQty;

        $stock = wrh_stock::where('refitmid', $refitmid)
            ->where('ownorgid', $ownorgid)
            ->where('wrhid', $wrhid)
            ->where('ordid', $ordid)
            ->first();

        if (!isset($stock)) {
            //не контролируем достоверность товара => можем создать "лишнюю" запись.
            // Правда все-равно с нулевым остатком
            $stock = new wrh_stock([
                'refitmid' => $refitmid,
                'ownorgid' => $ownorgid,
                'wrhid' => $wrhid,
                'ordid' => $ordid,
                'qty' => 0,
                'plnincqty' => 0,
                'plnoutqty' => 0,
            ]);
        }

//        $stock_qty = $stock->qty ?? 0;  //так как wrh_stock не содержит записи с нулевыми остатками


        //увеличим остаток
        $stock->qty += $placeQty;
        $stock->save();

        return $placeQty;
    }
}
