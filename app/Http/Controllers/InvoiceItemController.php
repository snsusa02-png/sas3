<?php

namespace App\Http\Controllers;

use App\equiprqst_item;
use App\invoice;
use App\invoice_item;
use App\objfile;
use App\objlog;
use App\refitem;
use App\unittype;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Cache;

class InvoiceItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 916;
        $this->sysobjcode = 'invoice_items';
    }

    /*
     * Установка прав пользователя
     */
    protected function setInterfaceRight($recid)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        //права наследуем от системы "Счета"
        $tmp_sysobjcode = $this->sysobjcode;
        $this->sysobjcode = 'invoices';

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.create');
        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.update');
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;


        if ($recid <> -1) {
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.delete');
        }

        $this->sysobjcode = $tmp_sysobjcode;

        return $usrrights;
    }

    public function create(Request $request, $invoiceid = null)
    {
        return $this->edit(-1, $invoiceid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $invoiceid = null)
    {
        $userid = Auth::user()->id;

        if ($id == -1) {

            //Значения "по-умолчанию" для новой записи
            $ordr = invoice_item::where('invoiceid', $invoiceid)->max('ordr') ?? 0;
            $ordr += 1;

            //$todayDate = date("Y-m-d");// current date
            $todayDate = today();

            $date = date("Y-m-d", (strtotime(date("Y-m-d", strtotime($todayDate)) . " +14 day")));
            //dd(session('maxreqdate'), $date);
            $date = session('maxreqdate') ?? $date;


            $rec = new invoice_item([
                'id' => -1,
                'invoiceid' => $invoiceid,
                'ordr' => $ordr,
                'unit' => 'шт',
                'created_by' => Auth::user()->id,
            ]);

            $rec->pre_items = invoice_item::from('invoice_items as ii')
                ->join('unittypes as ut', 'ut.id', 'ii.unittypeid')
                ->where('invoiceid', $rec->invoiceid)
                ->select('ii.*', 'ut.decimal_dgts as unit_decimal_dgts')
                ->orderby('ii.id', 'desc')
                ->limit(11)
                ->get();

        } else {

            $rec = invoice_item::find($id);


            if (!isset($rec))
                return redirect(route('invoices.index'));
        }

        $invoice = invoice::find($rec->invoiceid);

        $rec->lock_reason = '';

        $usrrights = $this->setInterfaceRight($id);
        if (isset($rec->eritmid)) {
            $rec->lock_reason = 'связана с позицией заявки';
            $usrrights['save'] = false;
            $usrrights['delete'] = false;
        }

        $usrrights['setord'] = false;

        $rec->unittypes = unittype::unittypes_cache();

        return view('invoice_items.edit', compact('rec', "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $messages = [
            'invoiceid.required' => 'Не указан документ',
            'itmtypeid.required' => 'Укажите тип записи',
            'rqst_qty.required' => 'Укажите требуемое количество',
            'unittypeid.required' => 'Укажите единицу измерения количества',
            'itmsum.required' => 'Укажите общую стоимость',
        ];

        $rules = [
            "invoiceid" => "required",
            "itmname" => "required",
            "qty" => "required",
            "itmsum" => "required",
            "unittypeid" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $invoiceid = $request->invoiceid;
        $invoice = invoice::find($invoiceid);
        if (!isset($invoice))
            return redirect(route('invoices.index'));

        $qty = $request->get('qty');

        $mess = "";
        if ($id == -1) {
            $invoiceid = $request->invoiceid;
            $ordr = invoice_item::where('invoiceid', $invoiceid)->selectraw('max(ordr) as ordr')->first()->ordr ?? 0;
            //dd($ordr);
            $rec = new invoice_item([
                "invoiceid" => $invoiceid,
                "ordr" => ++$ordr,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);

            $mess = "Запись создана";
        } else {
            $rec = invoice_item::find($id);
            $mess = "Запись обновлена";
        }
        $rec->code = mb_substr($request->get('code'), 0, 36);
        $rec->refitmid = $request->get('refitmid');
        $rec->itmname = mb_substr($request->get('itmname'), 0, 300);
        //$rec->itmdescript = mb_substr($request->get('itmdescript'), 0, 360);

        //dd($request->get('unittypeid'));
        $rec->unittypeid = $request->get('unittypeid');
        $rec->unit = mb_substr($rec->unittype->name, 0, 16);

        $rec->qty = $qty;
        $rec->itmsum = $request->get('itmsum');
        $rec->price = ($qty > 0) ? ($rec->itmsum / $qty) : null;

        //сохраним введенное значение для последующего использования
        //session(['maxreqdate' => $rec->maxreqdate]);
        //dd(session);

        //$rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();
        //dd($rec);

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        connectify('success', $rec->itmname, $mess);
        //------------------------------------------------------------------

        if ($id == -1)
            // добавим еще
            return redirect(route('invoice_items.create', $rec->invoiceid));
        else
            return redirect(route('invoices.edit', ['id' => $rec->invoiceid]) . '#items');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = invoice_item::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('invoice_item.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['itmname'], $res->msg);
        } else {

            $sd['success'] = 'Запись (' . $id . ':  удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('invoices.edit', ['id' => $res->obj['invoiceid']]);
            connectify('success', $id, 'Запись удалена.');

            foreach (objfile::where('sysobjid', $this->sysobjid)
                         ->where('objid', $id)->get() as $file) {
                $res = objfile::destroy($file->id);
            }

            objfile::where('sysobjid', $this->sysobjid)
                ->where('objid', $id)
                ->delete();
        }
        return redirect($route)->with($sd);
    }


    static public function list_items(Request $request)
    {
        //для AJAX-запросов/ Возвращает массив состава заданного счета
        $result = "";
        try {

            $invoiceid = $request->invoiceid;
            //Log::debug("invoiceid=$invoiceid");

            if (isset($invoiceid)) {

                Cache::forget('list_invitems_' . $invoiceid);
                $list = Cache::remember('list_invitems_' . $invoiceid, now()->addMinutes(5)
                    , function () use ($invoiceid) {
                        return invoice_item::from('invoice_items as ii')
                            ->leftjoin('unittypes as ut', 'ut.id', 'ii.unittypeid')
                            ->leftjoin('refitems as ri', 'ri.id', 'ii.refitmid')
                            ->where("ii.invoiceid", $invoiceid)

//todo: может быть оставлять в списке только не полностью использованные позиции
//                            ->whereRaw("
//                                  ii.id in (select distinct ...)
//                                    ")

                            ->selectraw("ii.id, concat(ii.ordr,'. ', ii.itmname,' / ', format(ii.qty,ifnull(ut.decimal_dgts,3)),' '
                            , ii.unit, '/ всего: ',format(ii.itmsum,2),' руб.') as tname")
                            ->orderBy('tname')
                            ->get()->pluck('tname', 'id')->toArray();
                    });

                $result = array('items' => $list);
            }

        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }
        return response()->json($result);
    }

    static public function get_item(Request $request)
    {
        //для AJAX-запросов
        $result = "";
        try {

            $invitmid = $request->invitmid;
            //Log::debug("invitmid=$invitmid");

            if (isset($invitmid)) {

                //Cache::forget('get_invitem_' . $invitmid);
                $item = Cache::remember('list_invitems_' . $invitmid, now()->addMinutes(5)
                    , function () use ($invitmid) {
                        return invoice_item::where('id', $invitmid)
                            ->select('id', 'itmname', 'qty', 'unit', 'price', 'itmsum')
                            ->first()
                            ->toArray();
                    });
                $result = array('item' => $item);
            }

        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }
        return response()->json($result);
    }


    public function add2refitems($id)
    {
        //Добавление позиции счета в спр-к Номенклатуры refitems


        if (isset($id)) {

            $userid = \Auth::user()->id;

            $item = invoice_item::find($id);

            // Убедимся, что есть все нужное
            if (isset($item) and !isset($item->refitmid) and isset($item->unittypeid)) {

                //dd($item->refitmid,isset($item->refitmid));

                //Попробуем найти запись с таким названием (в т.ч. и в доп. названиях)
                $refitmid = refitem::idByName($item->itmname);

                if (isset($refitmid)) {
                    connectify('error', $item->itmname, "Товар с таким наименованием уже существует в спр-ке Номенклатуры");

                    //проверим - соответствуют ли ЕИ
                    if (refitem::where(['id' => $refitmid, 'unittypeid' => $item->unittypeid])->count() == 0) {
                        connectify('error', $item->itmname, " ... ЕИ товара в справочнике не соответствует ЕИ в заявке");
                        $refitmid = null;
                    }


                } else {

                    //Добавим в справочник
                    $refitmid = refitem::newItem([
                        'name' => $item->itmname,
                        'unittypeid' => $item->unittypeid,
                        //'descript' => $item->itmdescript,
                        'producttypeid' => 2,
                        'qty_dec_digits' => 3,
                        'price' => $item->est_price,
                        //'manufacturer' => $item->manufacturer,
                        //'partnumber' => $item->mfq_partnumber,
                        'active' => 1,
                        'created_by' => $userid,
                    ]);

                    if (isset($refitmid))
                        connectify('success', $item->itmname, "Товар успешно добавлен в спр-к Номенклатуры");
                    else
                        connectify('error', $item->itmname, "Товар не удалось добавить в спр-к Номенклатуры");
                }

                if (isset($refitmid)) {
                    //можно связать позицию заявки с записью из справочника
                    $item->refitmid = $refitmid;
                    $item->save();
                    connectify('success', $item->itmname, "Позиция заявки связана со спр-ком Номенклатуры");

                    //Свяжем вакантные позиции заявок на материалы - по идентичному названию и ЕИ
                    equiprqst_item::setRefItmIDByNameAndUnit($refitmid, $item->itmname, $item->unittypeid);

                    Cache::forget('informer_eri_ri_stat');

                }
                //dd($refitem, $item->itmname);
            }
            return redirect(route('invoices.edit', $item->invoiceid) . '#items');
        }

        return redirect(route('invoices.index'));
    }


}
