<?php

namespace App;

use App\order;
use App\orditem;
use App\oi_qty;
use App\orgstaff;
use App\ri_detail;
use App\sysobj;
use App\Traits\Result;
use App\User;
use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Cache;
use Log;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;


use App\org;
use App\objlog;
use App\usrsysright;
use VK\Actions\Auth;

class wrhdoc extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'wrhdocs';
    static public $sysobjid = 204;

    //protected $fillable = ['created_by'];

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function predoc()
    {
        return $this->hasOne(wrhdoc::class, 'id', 'predocid')
            ->withDefault();
    }

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', self::$sysobjid)
            ->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    public function place()
    {
        return $this->hasOne(org_place::class, 'id', 'placeid')
            ->withDefault();
    }

    public function saleorg()
    {
        return $this->hasOne(org::class, 'id', 'saleorgid')
            ->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function dispatcher()
    {
        return $this->hasOne(orgstaff::class, 'id', 'disp_staffid')->withDefault();
    }

    public function respstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'respstaffid')
            ->withDefault();
    }

    public function respuser()
    {
        return $this->hasOne(User::class, 'id', 'respuserid')
            ->withDefault();
    }

    public function doctype()
    {
        return $this->hasOne(wrhdoctype::class, 'id', 'doctypeid')
            ->withDefault();
    }

    public function wrh()
    {
        return $this->hasOne(wrh::class, 'id', 'wrhid')
            ->withDefault();
    }

    public function box()
    {
        return $this->hasOne(wrh_box::class, 'id', 'boxid')
            ->withDefault();
    }

    public function relwrh()
    {
        return $this->hasOne(wrh::class, 'id', 'relwrhid')
            ->withDefault();
    }

    public function relbox()
    {
        return $this->hasOne(wrh_box::class, 'id', 'relboxid')
            ->withDefault();
    }

    public function order()
    {
        return $this->hasOne(order::class, 'id', 'ordid')
            ->withDefault();
    }

    public function src_sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'src_sysobjid')
            ->withDefault();
    }


    public function items()
    {
        return $this->hasMany(wrhdoclst::class, 'docid', 'id');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function getOperdateAttribute()
    {
        return $this->docdate;
    }

    public function getInfoAttribute()
    {
        $rslt = null;
        if (isset($this->id)) {
            $rslt = $this->doctype->name . ' № ' . ($this->docnum ?? '-') . ' от ' . date_create($this->docdate)->format('d.m.Y');
        }
        return $rslt;
    }

    public function child_docs()
    {
        return $this->hasMany(wrhdoc::class, 'predocid', 'id');
    }


    public static function min_docdate()
    {
        //определим минимально-допустимую дату для поля docdate
        $min_date = sysobj_lockdate::where('sysobjid', self::$sysobjid)->first()->lock_before ?? null;
        if (isset($min_date)) {
            return $min_date;
        }
        return null;
    }

    public static function auxInfo($wrhid)
    {
        $info = [];
        return $info;
    }

    public static function createDoc3FromOrder($ordid)
    {
        $wrhid = 1; //времено
        $userid = \Auth::user()->id;
        $mess = "";
        $docid = "";
        //Заказ должен быть сформирован, но еще не отгружен
        if (objflag::IsSetObjFlag(131, $ordid, 5)
            and order::cntUnShipQty($ordid) > 0) {

            try {
                $docid = DB::transaction(function () use ($ordid, $wrhid, $userid) {

                    $doc = new wrhdoc();
                    $doc->doctypeid = 3;   //расходная накладная
                    $doc->wrhid = $wrhid;  //склад отгрузки
                    $doc->ordid = $ordid;
                    $doc->ownorgid = 1;
                    $doc->docnum = "-";
                    $doc->docdate = date("Y-m-d", strtotime(now()));
                    $doc->created_by = $userid;
                    $doc->updated_by = $userid;
                    $doc->save();
                    $docid = $doc->id;
                    objlog::log_info(204, $docid, 'Документ создан из заказа');


                    $orditems = orditem::where('ordid', $ordid)
                        ->select('id', 'refitmid', 'qty', 'plnshipqty', 'price')
                        ->get();

                    //$list = array();
                    foreach ($orditems as $key => $value) {
                        $qty = $value['qty'] - $value['plnshipqty'];

                        $itm = new wrhdoclst();
                        $itm->docid = $docid;
                        $itm->refitmid = $value['refitmid'];
                        $itm->qty = $qty;
                        $itm->price = $value['price'];
                        $itm->sum = $itm->qty * $itm->price;
                        $itm->created_at = $userid;
                        $itm->updated_at = $userid;
                        //Не оптимально (нужно разобраться как вставить пачкой)
                        wrhdoclst::insert($itm->toArray());

                        orditem::where('id', $value['id'])->increment('plnshipqty', $itm->qty);

                    }

                    //objflag::AddObjFlag(131, $ordid, 6); //Заказ отгружен
                    return $docid;
                });
            } catch
            (\Exception $e) {
                $mess = "Ошибка создания документа";
                echo($e->getMessage());
                Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
                $docid = null;
                return $docid;
            }
        } else {
            $docid = null;
            $mess = "Статус заказа не соответствует запрошенной операции!";
        }
        $res = array($docid, $mess);
        return $res;
    }


    public static function mayUnsignDoc($docid)
    {
        //Проверка на возможность рассогласования документа.

        //сначала проверим требуется ли особое право для подписания/разподписания
        // документов этого типа:
        $doc = wrhdoc::with('doctype')->find($docid);
        if (isset($doc)) {

            $signrightid = $doc->doctype->signrightid ?: 104;

            $userid = \Auth::user()->id;
            $may = usrsysright::isUserHasRight_cached($userid, $signrightid);

            if ($may) {
                //по правам - можно, но

                //проверим, если этот документ в цепочке 7->8->9,
                // и у него есть уже согласованный документ-наследник,
                // то тогда разсогласовывать его нельзя
                $nxtdoc = wrhdoc::where('predocid', $docid)->first();
                if (isset($nxtdoc)) $may = !($nxtdoc->docsigned == 1);

                if ($may) {

                    // Нельзя, если при этом остаток на складе снизится меньше нуля
                    $may = wrhdoc::selectraw('MayUnsignWrhDoc(' . $docid . ') as may')->find($docid)->may;
                    if (isset($may)) {
                        return ($may);
                    }
                }
            }
        }
        return false;
    }

    public static function mayCreateLst($docid)
    {
        //Проверка на возможность добавления позиции в состав документа
        $rec = wrhdoc::with('doctype')
            ->find($docid);

        if (isset($rec)) {
            //пока грубо запрещаем менять состав, если документ "порожден" от предыдущего документа
            //TODO:ввести особый признак в тип документа
            return !($rec->doctype->need_predoc == 1);
        }

        return false;
    }


    public static function createDocFromDoc($predocid)
    {
        //Создает очередной документ(8) для цепочки 7->8
        $docid = null;
        $predoc = wrhdoc::find($predocid);

        if (isset($predoc)) {

            //Определим, какой тип должен быть у создаваемого документа.
            //Тип текущего документа должен быть равен wrhdoctypes.predoctypeid для нужного типа
            //$doctypeid = wrhdoctype::where('predoctypeid', $predoc->doctypeid)->first()->id;

            //Как выше ^, дает ложные срабатывания на документ типа 8, и вместо
            // созадния авта разногласия создает приходную накладную перемещения

            $doctypeid = null;
            if ($predoc->doctypeid == 7) $doctypeid = 8; //перемещение: расход(7) -> приход(8)


            if (isset($doctypeid)) {
                //$doctypeid определен, => можно создавать связанный документ
                // для документа-источника $predocid

                //но возможно он уже создан?
                $docid = wrhdoc::where('predocid', $predocid)->first();
                //dd($docid);
                if (isset($docid)) {
                    //исходим из того, что может быть только один связанный документ
                    return $docid;
                }

                $mess = "";

                try {
                    $userid = \Auth::user()->id;

                    DB::beginTransaction();
                    $doc = new wrhdoc();
                    $doc->predocid = $predoc->id;
                    $doc->doctypeid = $doctypeid;
                    $doc->wrhid = $predoc->relwrhid;  //склад получения
                    $doc->relwrhid = $predoc->wrhid;  //склад отгрузки
                    $doc->boxid = $predoc->relboxid;  //отделение получения
                    $doc->relboxid = $predoc->boxid;  //отделение отгрузки
                    $doc->ownorgid = $predoc->ownorgid;
                    $doc->docnum = "";
                    $doc->docdate = date("Y-m-d", strtotime(now()));
                    $doc->created_by = $userid;
                    $doc->updated_by = $userid;
                    $doc->save();

                    $docid = $doc->id;

                    objlog::log_info(204, $docid, 'Документ создан из предшествующего документа (' . $predocid . ')');

                    $docitems = wrhdoclst::where('docid', $predocid)
                        ->get();

                    foreach ($docitems as $key => $value) {

                        //Произведем необходимый контроль и манипуляции с кол-вом данного товара на складе и в заказе
                        //Вернет реально допустимуое кол-во
                        $add_qty = $value['qty'];
                        $rslt = wrhdoclst::RefItmQtyAdd2Doc($value['refitmid'], $add_qty, $doc);
                        $add_qty = $rslt->qty;

                        $itm = new wrhdoclst();
                        $itm->docid = $docid;
                        $itm->prelstid = $value['id'];
                        $itm->refitmid = $value['refitmid'];
                        $itm->qty = $add_qty;
                        $itm->price = $value['price'];
                        $itm->sum = $itm->qty * $itm->price;
                        $itm->created_by = $userid;
                        $itm->updated_by = $userid;
                        wrhdoclst::insert($itm->toArray());

//                        orditem::where('id', $value['id'])->increment('plnshipqty', $itm->qty);
                    }
                    DB::commit();
                } catch
                (\Exception $e) {
                    DB::rollback();
                    $mess = "Ошибка создания документа";
                    echo($e->getMessage());
                    Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
                    return null;
                }
            }
        } else {
            $mess = "Неподходящий исходный документ!";
        }
        //$res = array($docid, $mess);
        //return $res;
        return $docid;
    }

    public static function createDiffDocFromDoc($docid)
    {
        //создает документ(9), содержащий разницу по составу
        // между заданным документом(8) и его прародителем(7) (если тот есть)

        $diffdocid = null;

        $doc = wrhdoc::find($docid);
        if (isset($doc)) {

            //цепочка перемещения: расход(7) -> приход(8) -> акт разногласий(потеря) (9)
            //Определим, какой тип должен быть у создаваемого документа.
            //Тип текущего документа должен быть равен wrhdoctypes.predoctypeid
            // для нужного типа
            //$doctypeid = wrhdoctype::where('predoctypeid', $doc->doctypeid)->first()->id;

            $doctypeid = null;
            if ($doc->doctypeid == 8) $doctypeid = 9; //перемещение: расход(8) -> приход(7)

            if (isset($doctypeid)) {
                //$doctypeid определен, => нужно создавать связанный документ
                // для документа-источника $docid

                //Но так как этот документ содрежит разность между
                // первым и вторым документом в цепочке перемещения:
                //   1.расход(7) -> 2.приход(8) -> 3.акт разногласий(потеря)(9)
                //, то нужно, что бы существовал и документ-предшественник для $doc
                $predocid = $doc->predocid;
                if (isset($predocid)) {

                    $predoc = wrhdoc::find($predocid);
                    if (isset($predoc)) {

                        //все исходные документы определены:
                        //  1.расход(7):$predoc -> 2.приход(8):$predoc
                        // Нужно проверить, может уже есть и третий:
                        // -> 3.акт разногласий(потеря)(9):$diffdocid

                        //акт должен ссылаться на приходный документ (8): $docid
                        //исходим из того, что всегда есть только один документ
                        $diffdocid = wrhdoc::where('predocid', $docid)
                            ->where('doctypeid', $doctypeid)
                            ->first();
                        if (isset($diffdocid)) {
                            //исходим из того, что может быть только один связанный документ
                            return $diffdocid;
                        }

                        $mess = "";

                        try {
                            $userid = \Auth::user()->id;

                            DB::beginTransaction();
                            $diffdoc = new wrhdoc();
                            $diffdoc->predocid = $docid;
                            $diffdoc->doctypeid = $doctypeid;
                            $diffdoc->wrhid = $doc->wrhid;  //склад получения
                            $diffdoc->relwrhid = $doc->relwrhid;  //склад отгрузки
                            $diffdoc->ownorgid = $doc->ownorgid;
                            $diffdoc->docnum = "";
                            $diffdoc->docdate = date("Y-m-d", strtotime(now()));
                            $diffdoc->created_by = $userid;
                            $diffdoc->updated_by = $userid;
                            $diffdoc->save();

                            $diffdocid = $diffdoc->id;

                            objlog::log_info(204, $diffdocid, 'Документ создан из предшествующего документа (' . $predocid . ')');

                            //позиции промежуточного документа
                            $docitems = wrhdoclst::from('wrhdoclst as dl')
                                ->where('docid', $docid)
                                ->with('prelst')
                                ->get();
                            $lstcnt = 0;
                            foreach ($docitems as $preitm) {
                                //Акт разногласий для внутреннего перемещения не влияет
                                // на остаток на складе, так как приходная накладная
                                // на меньшее количество уже его уменьшила.
                                // Поэтому не используем wrhdoclst::RefItmQtyAdd2Doc
                                $add_qty = $preitm->prelst->qty - $preitm->qty;

                                if ($add_qty > 0) {
                                    $itm = new wrhdoclst();
                                    $itm->docid = $diffdocid;
                                    $itm->prelstid = $preitm->id;
                                    $itm->refitmid = $preitm->refitmid;
                                    $itm->qty = $add_qty;
                                    $itm->price = $preitm->price;
                                    $itm->sum = $itm->qty * $itm->price;
                                    $itm->created_at = $userid;
                                    $itm->updated_at = $userid;
                                    wrhdoclst::insert($itm->toArray());
                                    $lstcnt++;
                                }
                            }
//                            dd('lstcnt:'.$lstcnt);
                            if ($lstcnt > 0) {
                                //если хоть что-то добавили, то сохраним
                                DB::commit();
                            } else
                                DB::rollback();


                        } catch
                        (\Exception $e) {
                            DB::rollback();
                            $mess = "Ошибка создания документа";
                            echo($e->getMessage());
                            Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
                            return null;
                        }
                        return $diffdocid;
                    }
                } else {
                    $mess = "Неподходящий исходный документ!";
                }
            }
        }
        return $diffdocid;
    }


    public static function deleteDiffDocByPreDoc($predocid)
    {
        //удаляет заданный документ(9), который был ранее автоматически создан
        // как разница по составу между документом(8) и его прародителем(7)
        // (если тот есть)

        //Цепочка внутреннего перемещения товара (по типам док-тов): 7 -> 8 -> 9
        //Нам передали $predocid - id 8-го документа.
        // На него должен ссылаться 9-й документ - если он существует
//        $docid = wrhdoc::find($predocid);

        $doc = wrhdoc::where('predocid', $predocid)
            ->where('doctypeid', 9)->first();
        if (isset($doc)) {

            try {
                $docid = $doc->id;
                DB::beginTransaction();

                //позиции документа
                $docitems = wrhdoclst::from('wrhdoclst as dl')
                    ->where('docid', $docid)
                    ->get();
                foreach ($docitems as $itm) {
                    $itm->delete();
                }
                $doc->delete();
                DB::commit();

                objlog::log_info(204, $docid, 'Удален документ о разногласиях (' . $docid . ')');

            } catch
            (\Exception $e) {
                DB::rollback();
                $mess = "Ошибка удаления документа";
                echo($e->getMessage());
                Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
                return null;
            }
            return true;
        } else {
            $mess = "Неподходящий документ!";
        }
        return null;
    }

    static public function createDocOrdReserv($ordid, $userid)
    {
        //Формирование документа резервирующего товар/материалы для заказа (doctypeid=14)
        $rslt = new Result();

        $doctypeid = 14;
        $book_stageid = 3;

        //Проверим - есть ли в заказе товары? А то может быть только услуги?
        $goods = orditem::from('orditems as oi')
            ->join('refitems as ri', 'ri.id', 'oi.refitmid')
            ->where('ri.producttypeid', 2)//товар
            ->where('oi.ordid', $ordid)
            ->get();

        if (isset($goods) and count($goods) > 0) {

            $wrhid = 1; //todo - нужно откуда-то взять. может нужно указывать в заказе: srcwrhid

            //понадобится информация из заказа
            $ord = order::find($ordid);

            try {
                DB::beginTransaction();

                //установим семафор захвата склада
                if (wrh::lock_by_user($wrhid, $userid)) {

                    //Возможно такой документ уже есть?
                    // он может быть только один для заказа

                    $doc = wrhdoc::where('doctypeid', $doctypeid)
                        ->where('ordid', $ordid)->first();

                    if (!isset($doc)) {

                        $doc = new wrhdoc([
                            'doctypeid' => $doctypeid,
                            'docsigned' => 1, //сразу подписан, чтобы сразу забрать товар
                            'ordid' => $ordid,
                            'ownorgid' => $ord->ownorgid,
                            'orgid' => $ord->orgid,
                            'docnum' => $ord->ordnum,
                            'docdate' => $date = date('Y-m-d'),
                            'wrhid' => $wrhid,
                            'remarks' => 'автоматически создан из заказа',
                            'created_by' => $userid,
                        ]);
                        $doc->save();
                    }

                    if (isset($doc)) {

                        $docid = $doc->id;

                        //отметим позиции документа для последующего удаления лишних
                        wrhdoclst::where('docid', $docid)
                            ->update(['updated_by' => 0]);

                        //1-й проход - по публичным позициям документа с товарами (ispublic=1)
                        $goods = orditem::from('orditems as oi')
                            ->join('refitems as ri', 'ri.id', 'oi.refitmid')
                            ->where('ri.producttypeid', 2)//товар
                            ->join('oi_qtys as iq', 'iq.oiid', 'oi.id')
                            ->where('iq.stageid', 1)//1-заявка / 2-согласование. Скорее нужен 2 этап
                            ->where('oi.ordid', $ordid)
                            ->where('oi.ispublic', 1)
                            ->select('oi.id as oiid', 'oi.refitmid', 'iq.qty')
                            ->get();
                        foreach ($goods as $itm) {

                            $di = wrhdoclst::where('docid', $docid)
                                ->where('updated_by', 0)
                                ->where('refitmid', $itm->refitmid)
                                ->first();

                            if (!isset($di)) {
                                $di = new wrhdoclst([
                                    'docid' => $docid,
                                    'refitmid' => $itm->refitmid,
                                    'oiid' => $itm->oiid,
                                    'qty' => 0,
                                ]);
                            }

                            $addQty = $itm->qty - $di->qty; //кол-во, которое нужно добавить

                            //Попробуем найти его на складе и забрать в документ ----------------
                            $stock = wrh_stock::where('refitmid', $itm->refitmid)
                                ->where('ownorgid', $ord->ownorgid)
                                ->where('wrhid', $wrhid)
                                ->first();

                            //не можем опускаться ниже 0
                            $stock_qty = $stock->qty ?? 0;  //wrh_stock не содержит записи с нулевыми остатками
                            if ($stock_qty < $addQty) {
                                //меньше, чем нужно. Но возьмем сколько есть
                                $addQty = $stock_qty;
                            }
                            if (isset($stock)) {
                                //только если запись существует. Если не существует, то всяко $addQty = 0
                                $stock->qty -= $addQty;
                                $stock->save();
                            }
                            //-------------------------------------------------------------------


                            $di->qty += $addQty;
                            $di->updated_by = $userid;
                            $di->save();

                            //создадим/обновим отметку в позиции заказа для этапа "3.Резервирование материалов"
                            $oiq = oi_qty::where('oiid', $itm->oiid)->where('stageid', $book_stageid)->first();
                            if (!isset($oiq)) {
                                $oiq = new oi_qty([
                                    'oiid' => $itm->oiid,
                                    'stageid' => $book_stageid,
                                ]);
                            }
                            $oiq->qty = $di->qty;
                            $oiq->updated_by = $userid;
                            $oiq->save();
                        }


                        //второй проход - найдем товары которых не хватает, но их можно собрать по спецификации -----
                        $goods = orditem::from('orditems as oi')
                            ->join('refitems as ri', 'ri.id', 'oi.refitmid')
                            ->where('ri.producttypeid', 2)//товар
                            ->where('ri.has_detail', 1)//есть спецификация
                            ->join('oi_qtys as iq', 'iq.oiid', 'oi.id')
                            ->where('iq.stageid', 1)//1-заявка / 2-согласование. Скорее нужен 2 этап
                            ->leftJoin('oi_qtys as iqb', function ($j) {
                                $j->on('iqb.oiid', '=', 'oi.id')
                                    ->where('iqb.stageid', 3); //обеспечение товарами/материалами
                            })
                            ->where('oi.ordid', $ordid)
                            ->whereRaw('ifnull(iqb.qty,0)<iq.qty')//не достаточно
                            ->select('oi.id as oiid', 'oi.refitmid', DB::raw('iq.qty-ifnull(iqb.qty,0) as needqty'))
                            ->get();
                        foreach ($goods as $prnt) {
                            //для каждого товара будем смотреть на спецификацию и стараться захватить необходимые материалы
                            $details = ri_detail::from('ri_details as rid')
                                ->join('refitems as ri', 'ri.id', 'rid.selfrefitmid')
                                ->where('ri.producttypeid', 2)//товар
                                ->where('rid.refitmid', $prnt->refitmid)
                                ->where('rid.in_qty', '>', 0)
                                ->select('rid.selfrefitmid as refitmid', 'rid.in_qty')
                                ->get();

                            foreach ($details as $itm) {

                                //потребное кол-во материала
                                $addQty = ceil($itm->in_qty * $prnt->needqty); //округляем вверх до целого

                                // найдем позицию материала в составе заказа, и кол-во, которое уже зарезервировано
                                $oi = orditem::from('orditems as oi')
                                    ->leftJoin('oi_qtys as iqb', function ($j) {
                                        $j->on('iqb.oiid', '=', 'oi.id')
                                            ->where('iqb.stageid', 3); //этап обеспечения товарами/материалами
                                    })
                                    ->where('ordid', $ordid)
                                    ->where('parent_id', $prnt->oiid)
                                    ->where('ispublic', 0)
                                    ->where('refitmid', $itm->refitmid)
                                    ->select('oi.id', DB::raw('ifnull(iqb.qty,0) as booked_qty'))
                                    ->first();

                                //уменьшим нужное кол-во с учетом уже выделенного ранее
                                $addQty -= $oi->booked_qty;


                                //Попробуем найти его на складе и забрать в документ ----------------
                                $stock = wrh_stock::where('refitmid', $itm->refitmid)
                                    ->where('ownorgid', $ord->ownorgid)
                                    ->where('wrhid', $wrhid)
                                    ->first();
                                if (!isset($stock)) {
                                    $stock = new wrh_stock([
                                        'refitmid' => $itm->refitmid,
                                        'ownorgid' => $ord->ownorgid,
                                        'wrhid' => $wrhid,
                                        'qty' => 0,
                                        'plnincqty' => 0,
                                        'plnoutqty' => 0,
                                    ]);
                                }

                                //не можем опускаться ниже 0
                                $stock_qty = $stock->qty ?? 0;  //wrh_stock не содержит записи с нулевыми остатками
                                //dd($itm->refitmid, $addQty, $stock_qty);

                                if ($stock_qty < $addQty) {
                                    //меньше, чем нужно. Но возьмем сколько есть
                                    $addQty = $stock_qty;
                                }
                                $stock->qty -= $addQty;
                                $stock->save();
                                //-------------------------------------------------------------------

                                //строка в составе складского документа о данном материале
                                $di = wrhdoclst::where('docid', $docid)
                                    ->where('updated_by', 0)
                                    ->where('refitmid', $itm->refitmid)
                                    ->where('oiid', $oi->id)
                                    ->first();

                                if (!isset($di)) {
                                    $di = new wrhdoclst([
                                        'docid' => $docid,
                                        'refitmid' => $itm->refitmid,
                                        'qty' => 0,
                                    ]);
                                }
                                $di->qty += $addQty;
                                $di->oiid = $oi->id;
                                $di->updated_by = $userid;
                                $di->save();

                                //создадим/обновим отметку в позиции заказа для этапа "3.Резервирование материалов"
                                $oiq = oi_qty::where('oiid', $oi->id)->where('stageid', $book_stageid)->first();

//                                dd($di->qty,$oi->id,$oiq);
                                if (!isset($oiq)) {
                                    $oiq = new oi_qty([
                                        'oiid' => $oi->id,
                                        'stageid' => $book_stageid,
                                    ]);
                                }
                                $oiq->qty += $addQty;
                                $oiq->updated_by = $userid;
                                $oiq->save();

                            }

                        }
                        // end of Второй проход ----------------------------------------------------------------------

                        $doc->updated_by = $userid;
                        $doc->save();

                        //удалим лишние позиции документа
                        wrhdoclst::where('docid', $docid)
                            ->where('updated_by', 0)
                            ->delete();

                        DB::commit();

                        //разблокируем склад для работы других пользователей --------
                        wrh::unlock_by_user($wrhid, $userid);
                        //-----------------------------------------------------------

                        objlog::log_info(204, $docid, 'Создан документ с резервом товаров/материалов для заказа (' . $docid . ')');
                    } else {
                        //не удалось захваатить склад
                    }
                }
            } catch
            (\Exception $e) {
                DB::rollback();

                $rslt->err = 1;
                $rslt->msg = "Ошибка создания документа с резервированием материалов для заказа";
                Log::error($e->getMessage() . "\n" . $e->getTraceAsString());

            }

        } else {
            //документ не требуется.
            $rslt->msg = "Резервирование товаров/материалов не требуется";

            //на всякий случай проверим - если он есть, то нужно удалить/отменить
            $doc = wrhdoc::where('doctypeid', $doctypeid)
                ->where('ordid', $ordid)->first();
            if (isset($doc)) {
                //вроде как вне зависимосит от статуса черновик/проведен - достаточно просто удалить
                //и пересчитать остатки

                //удалим состав документа
                wrhdoclst::where('docid', $doc->id)->delete();

                //удалим документ
                wrhdoc::where('id', $doc->id)->delete();

                //пересчитаем остатки
                DB::unprepared('CALL recalc_stock()');

            }

        }

        return $rslt;
    }

    static public function reserv4ord($ordid, $userid, $src_wrhid, $tgt_wrhid = null)
    {
        //операция резервирования товаров, необходимых для заказа $ordid

        //Резервирование - это перемещение товаров со склада ($src_wrhid) на склад+ordid ($tgt_wrhid+$ordid)
        //проводится 2-я документами: 7-перемещение товара (расход) и 8-перемещение товара (приход)

        $rslt = new Result();

        $out_doctypeid = 7;
        $in_doctypeid = 8;

        $needqty_stageid = 2;   //Этап Согласования
        $book_stageid = 3;      //Этап Комплектования

        $tgt_wrhid = $tgt_wrhid ?? $src_wrhid;

        $protocol = '';

        //пересчитаем кол-ва зарезервированного в oi_qtys, чтобы избежать возможных расхождений
        order::recalcReserv($ordid, $userid);
        //-------------------------------------------------------------------------------------

        //сначала узнаем потребность заказа в товарах:
        //отберем только публичные записи из orditems для номенклатуры типа "товар":

        $items = orditem::from('orditems as oi')
            ->join('refitems as ri', 'ri.id', 'oi.refitmid')
            ->where('ri.producttypeid', 2)//товар
            ->join('oi_qtys as iq2', 'iq2.oiid', 'oi.id')
            ->where('iq2.stageid', $needqty_stageid)// 2-согласование

            //собираем удовлетворенную потребность непосредственно по складским документам, формирующиим запас Заказа
            ->leftJoin(DB::raw('(select wdi.refitmid, sum(wdt.forstock*wdi.qty) as stockqty
                from wrhdoclst as wdi
                join wrhdocs as wd on wd.id=wdi.docid and wd.docsigned=1
                        and wd.sysobjid=131 and wd.objid=' . $ordid . '
                join wrhdoctypes as wdt on wdt.id=wd.doctypeid
                group by refitmid) as wdd'),
                function ($join) {
                    $join->on('wdd.refitmid', '=', 'oi.refitmid');
                })
            //учтем уже отгруженное количество. Это снижает текущую потребность в резерве
            ->leftJoin('oi_qtys as iq5', function ($j) {
                $j->on('iq5.oiid', 'oi.id')
                    ->where('iq5.stageid', 5);
            })
            ->where('oi.ordid', $ordid)
            ->where('oi.ispublic', 1)
            ->whereRaw('(iq2.qty - ifnull(iq5.qty,0) - ifnull(wdd.stockqty,0)  > 0 )')
            ->select('oi.id as oiid', 'oi.refitmid'
                , 'iq2.qty as aprvqty', 'wdd.stockqty', 'iq5.qty as supplyqty'
                , DB::raw('iq2.qty - ifnull(iq5.qty,0) - ifnull(wdd.stockqty,0) as needqty')
                , 'ri.searchname as refitmname', 'ri.is_make_in', 'ri.qty_dec_digits')
            ->get();
        //dd($items);
        if (count($items) > 0) {

            //понадобится информация из заказа
            $ord = order::find($ordid);

            try {
                DB::beginTransaction();

                //установим семафор захвата склада
                if (wrh::lock_by_user($src_wrhid, $userid)) {
                    //$protocol .= "Склад ($src_wrhid) открыт для операций резервирования товаров...\n";

                    //признак отсутствия документов взятия со склада и приема на склад для заказа
                    $wrhdocs_created = false;


                    foreach ($items as $itm) {

                        $needQty = $itm->needqty;

                        if ($needQty > 0) {

                            //найдем товар на заданном складе и попробуем взять
                            // из общего запаса sysobjid=null, objid=null, grpid=null
                            $takeQty = wrh_stock::
                            take_from_stock($itm->refitmid, $ord->ownorgid, $src_wrhid
                                , null, null, null, $needQty);

                            //-------------------------------------------------------------------
                            //dd($itm->refitmid, $needQty, $takeQty);
                            $protocol .= " '$itm->refitmname' зарезервировано $takeQty из $needQty; \n";

                            if ($takeQty > 0) {

                                //поместим товар на заданный склад-приемник и отметим, что он для заказа 131/$ordid/1
                                $addQty = wrh_stock::
                                place_to_stock($itm->refitmid, $ord->ownorgid, $tgt_wrhid, 131, $ordid, 1, $takeQty);
                                //dd($takeQty, $addQty);

                                //добавим строки в расходный и приходный документы -------------------

                                //но сначала проверим - создан ли сам документ
                                if (!$wrhdocs_created) {
                                    //
                                    $doc_out = new wrhdoc([
                                        'doctypeid' => $out_doctypeid,
                                        'ownorgid' => $ord->ownorgid,

                                        'wrhid' => $src_wrhid,
                                        'sysobjid' => null, //берем с общего запаса склада wrhid
                                        'objid' => null,
                                        'grpid' => null,

                                        'orgid' => $ord->orgid,
                                        'docnum' => $ord->ordnum,
                                        'docdate' => $date = date('Y-m-d'),
                                        'remarks' => 'передача в резерв заказа id:' . $ordid,
                                        'docsigned' => 1, //сразу подписан, чтобы сразу забрать товар
                                        'created_by' => $userid,
                                    ]);
                                    $doc_out->save();

                                    $doc_in = new wrhdoc([
                                        'doctypeid' => $in_doctypeid,
                                        'predocid' => $doc_out->id, //ссылка на пред. доку-т (Расход)
                                        'ownorgid' => $ord->ownorgid,

                                        'wrhid' => $tgt_wrhid,
                                        'sysobjid' => 131,  //привязываем к заказу 131/$ordid
                                        'objid' => $ordid,
                                        'grpid' => 1,       //первичный резерв

                                        'orgid' => $ord->orgid,
                                        'docnum' => $ord->ordnum,
                                        'docdate' => $date = date('Y-m-d'),
                                        'remarks' => 'приход для резерва заказа id:' . $ordid,
                                        'docsigned' => 1, //сразу подписан, чтобы сразу забрать товар
                                        'created_by' => $userid,
                                    ]);
                                    $doc_in->save();

                                    $wrhdocs_created = true;

                                    objlog::log_info(131, $ordid, 'Создан документ (' . $doc_in->id . ') с резервом товаров/материалов для заказа');
                                    objlog::log_info(204, $doc_in->id, 'Создан документ с резервом товаров/материалов для заказа (' . $ordid . ')');
                                }

                                $di = new wrhdoclst([
                                    'docid' => $doc_out->id,
                                    'oiid' => $itm->oiid,   //(!) - запас создается ИНДИВИДУАЛЬНО для каждой позиции заказа
                                    'refitmid' => $itm->refitmid,
                                    'qty' => $addQty,
                                ]);
                                $di->save();

                                $di = new wrhdoclst([
                                    'docid' => $doc_in->id,
                                    'prelstid' => $di->id,
                                    'oiid' => $itm->oiid,
                                    'refitmid' => $itm->refitmid,
                                    'qty' => $addQty,
                                ]);
                                $di->save();
                                // -------------------------------------------------------------------

                                //добавим добавленое кол-во в oi_qtys
                                //todo: скорее всего - устарело! Запасы смотрим по wrh_stocks - для разных получателей
                                oi_qty::addQtyForStage($itm->oiid, $book_stageid, $addQty, $userid);

                            }


                            $needQty -= $takeQty;
                            // ------------------------------------------------------------------------------------
                            if ($needQty > 0 and $itm->is_make_in) {
                                //потребность осталась неудовлетворенной, и товар можно собрать из деталей

                                // возьмем спецификацию: -----------------------
                                //из спецификации отберем товары с кол-вом для сборки >0
                                $oiid = $itm->oiid;
                                $details = ri_detail::from('ri_details as rid')
                                    ->join('refitems as ri', 'ri.id', 'rid.selfrefitmid')
                                    ->where('ri.producttypeid', 2)//товар
                                    ->leftJoin('orditems as oi', function ($j) use ($oiid) {
                                        $j->on('oi.ri_detailid', '=', 'rid.id')
                                            ->where('oi.parent_id', $oiid);
                                    })
                                    ->where('rid.refitmid', $itm->refitmid)
                                    ->where('rid.in_qty', '>', 0)//используется в сборке
                                    ->select('rid.selfrefitmid as refitmid'
                                        , 'oi.id as oiid'
                                        , DB::raw('ifnull(ri.searchname, ri.name) as itmname')
                                        , 'rid.in_qty'
                                        , 'ri.qty_dec_digits')
                                    ->get();
                                //dd($oiid, $needQty, $details);
                                foreach ($details as $detail) {

                                    //потребное кол-во материала для детали.
                                    //Расчитываем с учетом точности заданной в справочнике товаров refitems.qty_dec_digits
                                    //вдруг этот материал - рулонный, и там идет фактическое использование,
                                    // без округления до листа
                                    $k = pow(10, $detail->qty_dec_digits);
                                    $partNeedQty = ceil($detail->in_qty * $needQty * $k) / $k;

                                    //dd($itm->oiid, $needQty, $detail->refitmid, $partNeedQty, $detail->qty_dec_digits);//

                                    // найдем позицию материала в составе заказа, и кол-во,
                                    // которое уже зарезервировано в заказе (131)  и в документах изготовления(831)

                                    $oi = orditem::from('orditems as oi')
                                        //собираем удовлетворенную потребность непосредственно по складским документам
                                        ->leftJoin(DB::raw('(select wdi.refitmid, sum(wdt.forstock*wdi.qty) as stockqty
                                            from wrhdoclst as wdi
                                            join wrhdocs as wd on wd.id=wdi.docid and wd.docsigned=1
                                                    and wd.sysobjid=131 and wd.objid=' . $ordid . '
                                            join wrhdoctypes as wdt on wdt.id=wd.doctypeid
                                            group by refitmid) as wdd'),
                                            function ($join) {
                                                $join->on('wdd.refitmid', '=', 'oi.refitmid');
                                            })
                                        //часть запаса может находиться в рабочих документах изготовления
                                        ->leftJoin(DB::raw('(select wdi.refitmid, sum(wdt.forstock*wdi.qty) as stockqty
                                                from wrhdoclst as wdi
                                                join wrhdocs as wd on wd.id=wdi.docid and wd.docsigned=1
                                                        and wd.sysobjid=831
                                                        and wd.objid in (select id from makedocs as md
                                                        where md.orditemid in ( select id from orditems as oi2 where oi2.ordid=' . $ordid . '))
                                                join wrhdoctypes as wdt on wdt.id=wd.doctypeid
                                                group by refitmid) as mdd'),
                                            function ($join) {
                                                $join->on('mdd.refitmid', '=', 'oi.refitmid');
                                            })
                                        //учтем количество уже подготовленного
                                        ->leftJoin('oi_qtys as iq4', function ($j) {
                                            $j->on('iq4.oiid', '=', 'oi.id')
                                                ->where('iq4.stageid', 4); //этап подготовки/изготовления
                                        })
                                        ->where('oi.ordid', $ordid)
                                        ->where('oi.parent_id', $itm->oiid)
                                        ->where('oi.id', $detail->oiid)
                                        //->where('oi.refitmid', $detail->refitmid)
                                        ->select(
                                            DB::raw('ifnull(wdd.stockqty,0) as ord_stockqty')
                                            , DB::raw('ifnull(mdd.stockqty,0) as md_stockqty')
                                            , DB::raw('ifnull(iq4.qty,0) as prepqty')
                                        )
                                        ->first();

                                    //dd($needQty, $partNeedQty, $oi);

                                    $partNeedQty = $partNeedQty - $oi->ord_stockqty - $oi->md_stockqty - $oi->prepqty;
                                    // !!!! - todo: подумать о формуле  - так ли нужно учитывать  $oi->prepqty???

                                    //dd( $detail->refitmid, $partNeedQty, $oi);

                                    if ($partNeedQty > 0) {

                                        //dd($detail->refitmid,$partNeedQty);

                                        //найдем товар в общем стоке на заданном складе и попробуем взять
                                        $takeQty = wrh_stock::
                                        take_from_stock($detail->refitmid, $ord->ownorgid, $src_wrhid
                                            , null, null, null, $partNeedQty);
                                        //dd($detail->refitmid, $partNeedQty, $takeQty);
                                        //-------------------------------------------------------------------
                                        //dd($itm->refitmid, $needQty, $addQty);
                                        $protocol .= " '$detail->itmname' зарезервировано $takeQty из $partNeedQty; \n";

                                        if ($takeQty > 0) {

                                            //поместим товар на заданный склад-приемник и отметим, что он для заказа
                                            $addQty = wrh_stock::
                                            place_to_stock($detail->refitmid, $ord->ownorgid
                                                , $tgt_wrhid, 131, $ordid, 1
                                                , $takeQty);
                                            //dd($itm->refitmid, $takeQty, $addQty);


                                            //добавим строки в расходный и приходный документы -------------------

                                            //но сначала проверим - создан ли сам документ
                                            if (!$wrhdocs_created) {
                                                //
                                                $doc_out = new wrhdoc([
                                                    'doctypeid' => $out_doctypeid,
                                                    'docsigned' => 1, //сразу подписан, чтобы сразу забрать товар
                                                    'ownorgid' => $ord->ownorgid,
                                                    'orgid' => $ord->orgid,
                                                    'docnum' => $ord->ordnum,
                                                    'docdate' => $date = date('Y-m-d'),
                                                    'wrhid' => $src_wrhid,
                                                    'sysobjid' => null,  //общий сток
                                                    'objid' => null,
                                                    'grpid' => null,
                                                    'remarks' => 'автоматически создан из заказа ' . $ordid,
                                                    'created_by' => $userid,
                                                ]);
                                                $doc_out->save();

                                                $doc_in = new wrhdoc([
                                                    'doctypeid' => $in_doctypeid,
                                                    'predocid' => $doc_out->id,
                                                    'docsigned' => 1, //сразу подписан, чтобы сразу забрать товар
                                                    'ownorgid' => $ord->ownorgid,
                                                    'orgid' => $ord->orgid,
                                                    'docnum' => $ord->ordnum,
                                                    'docdate' => $date = date('Y-m-d'),
                                                    'wrhid' => $tgt_wrhid,
                                                    'sysobjid' => 131,  //запас закза
                                                    'objid' => $ordid,
                                                    'grpid' => 1,       //первичный резерв
                                                    'remarks' => 'автоматически создан из заказа ' . $ordid,
                                                    'created_by' => $userid,
                                                ]);
                                                $doc_in->save();

                                                $wrhdocs_created = true;

                                                objlog::log_info(131, $ordid, 'Создан документ (' . $doc_in->id . ') с резервом товаров/материалов для заказа');
                                                objlog::log_info(204, $doc_in->id, 'Создан документ с резервом товаров/материалов для заказа (' . $ordid . ')');
                                            }

                                            $di = new wrhdoclst([
                                                'docid' => $doc_out->id,
                                                'oiid' => $detail->oiid,
                                                'refitmid' => $detail->refitmid,
                                                'qty' => $addQty,
                                            ]);
                                            $di->save();

                                            $di = new wrhdoclst([
                                                'docid' => $doc_in->id,
                                                'prelstid' => $di->id,
                                                'oiid' => $detail->oiid,
                                                'refitmid' => $detail->refitmid,
                                                'qty' => $addQty,
                                            ]);
                                            $di->save();
                                            // -------------------------------------------------------------------

                                            //добавим добавленое кол-во в oi_qtys
                                            //todo: скорее всего - устарело! Запасы смотрим по wrh_stocks - для разных получателей
                                            oi_qty::addQtyForStage($detail->oiid, $book_stageid, $addQty, $userid);

                                        }
                                    } //if ($partNeedQty > 0)


                                }

                            }
                            // --- end потребность осталась неудовлетворенной, и товар можно собрать из деталей ---

                        } //if ($needQty > 0)
                    }
                    //dd($doc_in, $doc_in->items());

                    DB::commit();

                    //разблокируем склад для работы других пользователей --------
                    wrh::unlock_by_user($src_wrhid, $userid);
                    //-----------------------------------------------------------
                } else {
                    //не удалось захватить склад
                    $protocol .= "Не удалось открыть склад для операций! Попробуйте немного позднее...";
                }
                $rslt->msg = $protocol;
            } catch
            (\Exception $e) {
                DB::rollback();

                $rslt->err = 1;
                $rslt->msg = "Ошибка создания документа с резервированием материалов для заказа";
                Log::error($e->getMessage() . "\n" . $e->getTraceAsString());

            }
        } //(count($items) > 0)
        else {
            $protocol .= 'Нет потребности в резервировании товаров/материалов. Возможно нет согласованного количества.';
        }

        //notify($protocol);
        $rslt->msg = $protocol;
        return $rslt;
    }


    public static function create_doc($hdrData, $items, $userid)
    {
        //Создает документ склада на приход товара $refitmid (из создания), на склад $wrhid
        // и сразу под резерв для заказа $ordid

        $rslt = new Result();

        //проверки на обязательные данные ----------------------------------------------

        if (!$hdrData['doctypeid']) {
            $rslt->err = 1;
            $rslt->msg = "В документе склада должен быть определен 'Тип документва'";
            Log::error($rslt->msg);
            return $rslt;
        }
        if (!$hdrData['ownorgid']) {
            $rslt->err = 1;
            $rslt->msg = "В документе склада должен быть определена 'Организация-владелец'";
            Log::error($rslt->msg);
            return $rslt;
        }
        if (!$hdrData['wrhid']) {
            $rslt->err = 1;
            $rslt->msg = "В документе склада должен быть определен 'Склад'";
            Log::error($rslt->msg);
            return $rslt;
        }

        $hdrData['docdate'] = $hdrData['docdate'] ?? date('Y-m-d');
        $hdrData['respuserid'] = $hdrData['respuserid'] ?? $userid;
        $hdrData['created_by'] = $hdrData['created_by'] ?? $userid;
        $hdrData['updated_by'] = $hdrData['updated_by'] ?? $userid;
        $hdrData['docsigned'] = 0;
        //------------------------------------------------------------------------------

        try {
            //DB::beginTransaction();

            $doc = new wrhdoc($hdrData);
            $doc->save();

            foreach ($items as $itm) {

                $itm['docid'] = $doc->id;
                $itm['created_by'] = $itm['created_by'] ?? $userid;
                $itm['updated_by'] = $itm['updated_by'] ?? $userid;
                $di = new wrhdoclst($itm);
                $di->save();
            }

            //проведем документ
            self::sign($doc, $userid);

            //DB::commit();

            $rslt->obj['id'] = $doc->id;

        } catch (\Exception $e) {
            //DB::rollback();

            $rslt->err = 1;
            $rslt->msg = "Ошибка создания документа склада";
            Log::error($e->getMessage() . "\n" . $e->getTraceAsString());
        }
        return $rslt;
    }

    public static function sign(wrhdoc $doc, $userid)
    {
        //подписывание документа /проведение / пересчет остатков на складе
        //$doc = self::find($docid);
        Log::debug(" wrhdoc::sign ");

        // если док-т существует
        if (isset($doc)) {

            // если док-т еще не утвержден
            if (($doc->docsigned ?? 0) <> 1) {

                //Если дата документа не попадает в заблокирванный период
                if (!wrhdoc::isLocked($doc->id)) {

                    if (!isset($doc->docnum))
                        $doc->docnum = wrhdocnum::NxtDocNum($doc->doctypeid, $doc->ownorgid, $doc->docdate);

                    $doc->docsigned = 1;
                    $doc->updated_by = $userid;
                    $doc->save();
                    //Log::debug(" wrhdoc::docsigned = $doc->docsigned ");

                    //пересчитаем остатки
                    DB::unprepared('CALL recalc_stock()');

                    return true;

                } else {
                    Log::debug(" wrhdoc::Not signed! Doc`s Date in locked period!");
                    return false;
                }

            }
        }
    }

    public static function on_sign($rec)
    {
        // Доп. действия при подписании/утверждении документа
        //dd($rec);
        //сформируем/обновим фин. операции ------
        self::rfr_finopers($rec);

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function on_unsign($rec)
    {
        // Доп. действия при разутверждении документа

        //сформируем/обновим фин. операции ------
        self::clr_finopers($rec);

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    static public function rfr_finopers($rec)
    {
        if (!isset($rec))
            return;

        $userid = \Auth::user()->id;

        //dd($rec, self::$sysobjid, $rec->id, $rec->doctype->need_org);
        if ($rec->doctype->need_org == 1) {

            // Передача товара от продавца (если указан) или владельца товара - сразу конечному покупателю
            //сформируем фин. операцию --------------------------------------------------------------
            obj_finoper::addOrUpdate(
                ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 1],
                ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 1
                    , 'operdate' => $rec->docdate
                    , 'opersum' => $rec->docsum
                    , 'qty' => 1
                    , 'price' => $rec->docsum
                    , 'descript' => 'Отпуск товара' // $rec->refitem->name . ', ' . $rec->refitem->unittype->name
                    , 'sumtypeid' => 2  //1-деньги, 2-товар
                    , 'srcorgid' => $rec->saleorgid ?? $rec->ownorgid   //продавца (если указан) или владельца товара
                    , 'tgtorgid' => $rec->orgid
//                , 'contractid' => $rec->contractid
//                , 'opertypeid' => $rec->mchn_raid->opertypeid
                    , 'updated_by' => $userid
                    , 'updated_at' => now()
                ]);
        }
        //удалим записи из obj_finopers, для которых уже нет соответствующих записей в wrhdocs
        obj_finoper::from('obj_finopers as f')
            ->where('sysobjid', self::$sysobjid)
            ->whereRaw("not exists (select 1 from wrhdocs as d where d.id=f.objid)")
            ->delete();
    }

    static public function clr_finopers($rec)
    {
        if (!isset($rec))
            return;

        $userid = \Auth::user()->id;

        //удалим записи из obj_finopers, связанные с текущей записью
        obj_finoper::from('obj_finopers as f')
            ->where('sysobjid', self::$sysobjid)
            ->where('objid', $rec->id)
            ->delete();

        //удалим записи из obj_finopers, для которых уже нет соответствующих записей в mr_opers
        obj_finoper::from('obj_finopers as f')
            ->where('sysobjid', self::$sysobjid)
            ->whereRaw("not exists (select 1 from wrhdocs as d where d.id=f.objid)")
            ->delete();
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lock_before = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lock_before)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->docdate < $lock_before);
            }
        }
        return false;
    }


    public function admindelete()
    {
        $result = new Result;
        //Удаляем себя вместе с дочками
        try {
            DB::transaction(function () {
                $this->items()->delete();
                $this->files()->delete();  //TODO: ? ->deleteOne() ? Так как не удаляется файл с диска

                //удалим записи из obj_finopers, для которых уже нет соответствующих записей в wrhdocs
                obj_finoper::from('obj_finopers as f')
                    ->where('sysobjid', self::$sysobjid)
                    ->whereRaw("not exists (select 1 from wrhdocs as d where d.id=f.objid)")
                    ->delete();
                //удалим записи из obj_expenses, для которых уже нет соответствующих записей в wrhdocs
                obj_expense::from('obj_expenses as t')
                    ->where('sysobjid', self::$sysobjid)
                    ->whereRaw("not exists (select 1 from wrhdocs as d where d.id=t.objid)")
                    ->delete();
                return parent::delete();
            });
        } catch (\Exception $e) {
            $result->err = 1;
            $result->msg = 'Ошибка удаления записи: ' . $e->getMessage();
        }
        return $result;
    }


    public static function cache_clear($rec)
    {
        //Забудем связанный кэш -------------------------------------
        if (isset($rec)) {
        }
//        Cache::forget('informer_saldos');
        //-----------------------------------------------------------
    }

    public static function clone($id)
    {
        // Клонируем указанную запись wrhdocs со всем содержимым

        $result = new Result;
        $userid = \Auth::user()->id;

        $wrhdoc = self::find($id);
        if (!isset($wrhdoc)) {
            $result->err = 1;
            $result->msg = 'Исходная запись не найдена!';
            return $result;
        }

        $new_wrhdoc = $wrhdoc->replicate();
        //дата записи не может быть ранее sysobj_lockdates.lock_before
        $new_wrhdoc->docdate = max($new_wrhdoc->docdate, sysobj_lockdate::mindate(self::$sysobjid));
        $new_wrhdoc->docnum = wrhdocnum::NxtDocNum($new_wrhdoc->doctypeid, $new_wrhdoc->ownorgid, $new_wrhdoc->docdate);;
        $new_wrhdoc->docsigned = 0;
        $new_wrhdoc->created_by = $userid;
        $new_wrhdoc->updated_by = $userid;
        $new_wrhdoc->save();
        self::on_update($new_wrhdoc);

        //перенесем операции
        $items = wrhdoclst::where('docid', $wrhdoc->id)->get();
        foreach ($items as $item) {
            $new_item = $item->replicate();
            $new_item->docid = $new_wrhdoc->id;
            $new_item->created_by = $userid;
            $new_item->updated_by = $userid;
            $new_item->save();
            wrhdoclst::on_update($new_item);
        }

        $result->obj = $new_wrhdoc->toArray();

        return $result;
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи


        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи


        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public function linked_paydocs()
    {
        return $this->hasMany(obj_link::class, 'objid', 'id')
            ->join('paydocs as pd', 'pd.id', 'obj_links.lnkobjid')
            ->join('orgs as oo', 'oo.id', 'pd.ownorgid')
            ->join('orgs as o', 'o.id', 'pd.orgid')
            ->where([
                'sysobjid' => self::$sysobjid,
                'lnksysobjid' => 520,
            ])
            ->select('obj_links.*', 'pd.*', 'oo.name as ownorg_name', 'o.name as org_name');
    }

}
