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

    public function ri_compound()
    {
        return $this->hasOne(ri_compound::class, 'id', 'cmpndid')
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
        // по ограничениям запаса у МОЛ
        //
        // сохраняет изменения
        // - по позициям заказа
        // - по запасу склада
        // - по запасу МОЛ

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
        $mol_staffid = $doc->mol_staffid; //МОЛ


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

        //Проверим возможность и повлияем на запас на складе ---------

        $sc = "1=1";
        // если для типа документа запрещено брать товар из запасов любой организации, то
        if ($doc->doctype->any_ownorg == 0) $sc = "ownorgid={$ownorgid}";

        $sc .= ' and qty>=0';

        // запас на складе
        $wrh_stock = wrh_stock::where('refitmid', $refitmid)
            ->where(['wrhid' => $wrhid, 'boxid' => $boxid])
            ->whereRaw($sc)
            ->first();
        //dd($refitmid, $ownorgid, $wrhid, $stock);

        if (!isset($wrh_stock)) {
            $userid = \Auth::user()->id;
            $wrh_stock = new wrh_stock([
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

        $for_wrh_stock = $doc->doctype->forstock;
        if ($for_wrh_stock < 0) {
            //проверим возможность уменьшения
            $avlqty = $wrh_stock->qty - $wrh_stock->plnoutqty;
            if ($avlqty < $add_qty)
                $restrictReasons = $restrictReasons . ';по запасу на складе (' . $avlqty . ')';
            $add_qty = min($avlqty, $add_qty);
//            echo(' ' . $add_qty);
        }

        $for_mol_stock = $doc->doctype->formol;
        if ($for_mol_stock <> 0) {

            //Проверим возможность и повлияем на запас МОЛ ---------------------------------------
            $sc = "1=1";
            // если для типа документа запрещено брать товар из запасов любой организации, то
            if ($doc->doctype->any_ownorg == 0) $sc = "ownorgid={$ownorgid}";

            $sc .= ' and qty>=0';

            $mol_stock = mol_stock::where('refitmid', $refitmid)
                ->where(['staffid' => $mol_staffid])
                ->whereRaw($sc)
                ->first();
            //dd($refitmid, $ownorgid, $mol_staffid, $mol_stock);

            if (!isset($mol_stock)) {
                $userid = \Auth::user()->id;
                $mol_stock = new mol_stock([
                    "ownorgid" => $ownorgid,
                    "staffid" => $mol_staffid,
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

            if ($for_mol_stock < 0) {
                //проверим возможность уменьшения
                $avlqty = $mol_stock->qty - $mol_stock->plnoutqty;
                if ($avlqty < $add_qty)
                    $restrictReasons = $restrictReasons . ';по запасу МОЛ (' . $avlqty . ')';
                $add_qty = min($avlqty, $add_qty);
//            echo(' ' . $add_qty);
            }

            // изменение wrh_stock и mol_stock в одном месте - по итоговому значению $add_qty,
            // полученносу после прохождения контроля и по WRH, и по MOL

            // итоговое обновление регистров и по Складу, и по МОЛ
            if ($add_qty <> 0) {
                //Не сохраняем нулевые добавления
//                if ($for_wrh_stock < 0) {
//                    $wrh_stock->plnoutqty = $wrh_stock->plnoutqty + $add_qty;
//                }
//                if ($for_wrh_stock > 0) {
//                    $wrh_stock->plnincqty = $wrh_stock->plnincqty + $add_qty;
//                }

                if ($for_mol_stock < 0) {
                    $mol_stock->plnoutqty = $mol_stock->plnoutqty + $add_qty;
                }
                if ($for_mol_stock > 0) {
                    $mol_stock->plnincqty = $mol_stock->plnincqty + $add_qty;
                }

                $mol_stock->save();
            }
        }

        if ($add_qty <> 0) {
            //Не сохраняем нулевые добавления
            if ($for_wrh_stock < 0) {
                $wrh_stock->plnoutqty = $wrh_stock->plnoutqty + $add_qty;
            }
            if ($for_wrh_stock > 0) {
                $wrh_stock->plnincqty = $wrh_stock->plnincqty + $add_qty;
            }
            $wrh_stock->save();

        }
        //--------------------------------------------------------------------------------


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

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи

        //Забудем связанный кэш -----------------
        //self::cache_clear($rec);

    }


}
