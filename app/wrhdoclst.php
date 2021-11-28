<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class wrhdoclst extends Model
{
    protected $table = 'wrhdoclst';

    use DeleteTrait;

    //protected $fillable = ['created_by'];

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function prelst()
    {
        return $this->hasOne(wrhdoclst::class, 'id', 'prelstid')
            ->withDefault();
    }

    public function wrhdoc()
    {
        return $this->belongsTo(wrhdoc::class, 'docid', 'id');
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')
            ->with('unittype')
            ->withDefault();
    }

    public function subtype()
    {
        return $this->hasOne(wrhdoctype::class, 'id', 'subtypeid')
            ->withDefault();
    }

    public function items()
    {
        return $this->hasMany(wrhdoclst::class, 'docid', 'id');
    }

    public static function RefItmQtyAdd2Doc($refitmid, $add_qty, $doc)

        //Решает вопросы возможности добавления указанного кол-ва
        // по ограничениям заказа
        // по ограничениям товарного запаса на складе
        //
        // сохраняет изменения
        // - по позициям заказа
        // - по запасу склада

        // $add_qty - добавляемое / убавляемое кол-во.
        // Для изменяемых записей = разнице между текущим (введенным) значением
        // и ранее сохраненным значением (из БД)
    {
        $result = new \stdClass();
        $result->qty = 0;
        $result->message = null;

        $ownorgid = $doc->ownorgid; //Владелец товара
        $wrhid = $doc->wrhid;       //Склад
        $boxid = $doc->boxid;       //Отделение / Ячейка хранения на складе

        $restrictReasons = "";

        //Если документ связан с заказом, то определим "вместимость" заказа -----
        if ($doc->ordid) {
            $avlqty = orditem::where('ordid', $doc->ordid)
                ->where('refitmid', $refitmid)
                ->selectraw('sum(qty-plnshipQty-aprvshipqty) as avlqty')
                ->first()
                ->avlqty;
            //Скорректируем добавляемое кол-во с учетом текущей вместимости заказа
            //echo (' '.$add_qty);
            if ($avlqty < $add_qty)
                $restrictReasons = $restrictReasons . ';по емкости заказа (' . $avlqty . ')';
            $add_qty = min($avlqty, $add_qty);
            //echo(' ' . $add_qty);
        }
        //------------------------------------------------------------------------

        //Проверим возможность и повлияем на запас на складе
        $stock = wrh_stock::where('refitmid', $refitmid)
            ->where(['ownorgid' => $ownorgid, 'wrhid' => $wrhid, 'boxid' => $boxid])
            ->first();
        //dd($refitmid, $ownorgid, $wrhid, $stock);

        if (!isset($stock)) {
            $userid = \Auth::user()->id;
            $stock = new wrh_stock([
                "ownorgid" => $ownorgid,
                "wrhid" => $wrhid,
                "boxid" => $boxid,
                "refitmid" => $refitmid,
                "qty" => 0,
                "plnincqty" => 0,
                "plnoutqty" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
        }
        //dd($stock);

        $forstock = $doc->doctype->forstock;
        if ($forstock < 0) {
            //проверим возможность уменьшения
            $avlqty = $stock->qty - $stock->plnoutqty;
            if ($avlqty < $add_qty)
                $restrictReasons = $restrictReasons . ';по запасу на складе (' . $avlqty . ')';
            $add_qty = min($avlqty, $add_qty);
//            echo(' ' . $add_qty);
            $stock->plnoutqty = $stock->plnoutqty + $add_qty;
        }
        if ($forstock > 0) {
            $stock->plnincqty = $stock->plnincqty + $add_qty;
        }
        //Не сохраняем нулевые добавления
        if ($add_qty <> 0)
            $stock->save();

        //влияние на заказ --------------------------
        if ($doc->ordid and $add_qty <> 0) {

            if ($add_qty > 0) {
                //добавим добавляемое кол-во вполя OrdItems.plnshipqty
                //учитывая, что в заказе может быть несколько записей об одном товаре,
                // придется пройти по всем кандидатам с "ненулевой вместимостью"
                $orditems = orditem::where('ordid', $doc->ordid)
                    ->where('refitmid', $refitmid)
                    ->whereraw('qty - aprvshipqty > 0')
                    ->get();

                $restQty = $add_qty;
                foreach ($orditems as $oi) {
                    if ($restQty == 0) break;

                    //вместимость строки заказа
                    $q = $oi->qty - $oi->aprvshipqty;

                    if ($q > $restQty) {
                        $setQty = $restQty;
                        $restQty = 0;
                    } else {
                        $setQty = $q;
                        $restQty = $restQty - $q;
                    }
                    if ($setQty <> 0) {
                        //Добавим в планируемое кол-во строки заказа
                        $oi->plnshipqty = $oi->plnshipqty + $setQty;
                        $oi->save();
                    }
                }
                if ($restQty > 0) {
                    //!!!Что-то не так!!!
                    throw new \Exception('Заказ "хотел", но не "смог" вместить нужное количество товара (' . $restQty . ' из ' . $add_qty . ')!');
                }
            } else {
                // $add_qty < 0
                //
                //отнимем отнимаемое кол-во из поля OrdItems.plnshipqty
                //учитывая, что в заказе может быть несколько записей об одном товаре,
                // придется пройти по всем кандидатам с ненулевым plnshipqty
                $orditems = orditem::where('ordid', $doc->ordid)
                    ->where('refitmid', $refitmid)
                    ->where('plnshipqty', '>', 0)
                    ->get();

                $restQty = -$add_qty;
                foreach ($orditems as $oi) {
                    if ($restQty == 0) break;

                    //вместимость строки заказа
                    $q = $oi->plnshipqty;

                    if ($q > $restQty) {
                        $setQty = $restQty;
                        $restQty = 0;
                    } else {
                        $setQty = $q;
                        $restQty = $restQty - $q;
                    }
                    if ($setQty <> 0) {
                        //Отнимем из планируемого кол-во строки заказа
                        $oi->plnshipqty = $oi->plnshipqty - $setQty;
                        $oi->save();
                    }
                }
                if ($restQty > 0) {
                    //!!!Что-то не так!!!
                    throw new \Exception('Заказ "хотел", но не "смог" отдать нужное количество товара (' . $restQty . ' из ' . $add_qty . ')!');
                }
            }
        }
        //-------------------------------------------
        $result->qty = $add_qty;
        if ($restrictReasons) $restrictReasons = mb_substr($restrictReasons, 1);
        $result->msg = $restrictReasons;
        return $result;
    }

    public static function RefItmQtyRmvFrmDoc($refitmid, $qty, $doc)
    {
        //Уменьшим соответствующее кол-во на складе
        $stock = wrh_stock::where('refitmid', $refitmid)
            ->where('ownorgid', $doc->ownorgid)
            ->where('wrhid', $doc->wrhid)
            ->first();

        if (isset($stock)) {
            $forstock = $doc->doctype->forstock;
            if ($forstock < 0) {
                $stock->plnoutqty = $stock->plnoutqty - $qty;
            }
            if ($forstock > 0) {
                $stock->plnincqty = $stock->plnincqty - $qty;
            }
            $stock->save();
        }
        //Уменьшим запланированное (зарезервированное) кол-во в соответствующих позициях заказа
        $ordid = $doc->ordid;
        if (isset($ordid)) {
            //обновим поля OrdItems.plnshipqty и aprvshipqty
            //учитывая, что в заказе может быть несколько записей об одном товаре,
            // придется пройти по всем кандидатам с "ненулевой вместимостью"
            $orditems = orditem::where('ordid', $ordid)
                ->where('refitmid', $refitmid)
                ->where('plnshipqty', '>', 0)
                ->get();
            $restQty = $qty;
            foreach ($orditems as $oi) {
                if ($restQty == 0) break;

                $q = $oi->plnshipqty; //вместимость строки заказа
                if ($q > $restQty) {
                    $setQty = $restQty;
                    $restQty = 0;
                } else {
                    $setQty = $q;
                    $restQty = $restQty - $q;
                }
                if ($setQty > 0) {
                    //переведем из согласованного обратно в планируемое
                    $oi->plnshipqty = $oi->plnshipqty - $setQty;
                    $oi->save();
                }
            }
            if (1 == 0 and $restQty > 0) {
                $msg = "Кол-во не смогло распределиться по заказу - не найден такой товар";
                DB::rollback();
                return false;
            }
        }
        // all good
        DB::commit();
        return true;


    }

}
