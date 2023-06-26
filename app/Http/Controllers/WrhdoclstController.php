<?php

namespace App\Http\Controllers;

use App\group;
use App\grptype;
use App\ri_compound;
use App\sysobj;
use App\wrh_stock;
use App\wrhdoc;
use App\wrhdoclst;
use App\wrhdoctype;

use App\objlog;
use App\orditem;
use App\ri_detail;

use App\Traits\Result;
use App\usrsysright;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;


class WrhdoclstController extends Controller
{
    protected $sysobjid;
    protected $parsysobjid;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 205;
        $this->parsysobjid = 204;
        $this->sysobjcode = 'wrhdoclst';
    }

    protected function setInterfaceRight($id)
    {

        //по acl указанного объекта
        $acl_sysobjcode = sysobj::where('code', $this->sysobjcode)
                ->select(db::raw("ifnull(acl_sysobjcode, code) as acl_sysobjcode"))
                ->first()->acl_sysobjcode ?? $this->sysobjcode;

        $usrrights = array();
        $usrrights['create'] = false;
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['refitm.edit'] = false;
        $usrrights['price.edit'] = false;

        $userid = \Auth::user()->id;

        $usrrights['create'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.create');

        if ($id == -1) {
            //Новый документ - можно сохранять
            $usrrights['save'] = $usrrights['create'];
            $usrrights['refitm.edit'] = $usrrights['save'];
        } else {
            $rec = wrhdoclst::find($id);
            if (isset($rec)) {
                $docsigned = $rec->wrhdoc->docsigned;
                if (!$docsigned) {
                    $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.update');;
                    $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.delete');;
                }
                //запрет изменения состава для документа, созданного из документа-предшественника
                $MayEditRefItm = (!$rec->wrhdoc->doctype->need_predoc == 1);
                $usrrights['refitm.edit'] = ($usrrights['save'] and $MayEditRefItm);
                $usrrights['delete'] = ($usrrights['delete'] and $MayEditRefItm);
            }
        }
        return $usrrights;
    }


    public function create($docid)
    {
        return $this->edit(-1, $docid);
    }


    public function edit($id, $docid = null)
    {
        //
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {

            //Значения "по-умолчанию" для новой записи

            $rec = new wrhdoclst();
            $rec->id = -1;
            $rec->docid = $docid;
            $rec->doctypeid = $rec->wrhdoc->doctypeid;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = wrhdoclst::find($id);
        }
        if (isset($rec)) {

            $rec->doctypeid = $rec->subtypeid ?? $rec->wrhdoc->doctypeid;

            $rec->subtypes = wrhdoctype::where('parent_id', $rec->wrhdoc->doctypeid)
                ->where('active', 1)
                ->where('lst_editable', 1)
                ->select('id', 'name')
                ->orderBy('ordr')
                ->orderBy('name')
                ->get()
                ->pluck('name', 'id')->toArray();

            if (!$rec->subtypeid) {
                $rec->subtypeid = (count($rec->subtypes) == 1) ? key($rec->subtypes) : null;
            }

            $rec->ri_produced = $rec->wrhdoc->doctype->ri_produced;

            if ($rec->ri_produced = 1) {
                $rec->cmpnd_ownorgid = $rec->wrhdoc->ownorgid;
                $rec->cmpnd_on_date = $rec->wrhdoc->docdate;
                $rec->cmpnd_name = $rec->ri_compound->info;

            }
            $rec->ri_compounds = ri_compound::from('ri_compounds as ric')
                ->join('refitems as ri', 'ri.id', 'ric.refitmid')
                ->where('ric.docsigned', 1)
                ->where('ric.active', 1)
                ->where('ric.ownorgid', $rec->wrhdoc->ownorgid)
                ->select('ric.id', db::raw("concat(ri.name, ' /', ric.notes) as name"))
                ->get()->pluck('name', 'id')->toArray();
//            dd($rec->wrhdoc->ownorgid, $rec->ri_compounds);

            $rec->sysobjs = sysobj::lst_sysobjs4grptypes_cache();


            return view('' . $this->sysobjcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\wrhdoclst $wrhdoclst
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
//        $request->validate([
//            "docid" => "required",
//            "refitmid" => "required",
//            "qty" => "required|gt:0",
//        ]);

        $rules = [
            "docid" => "required",
            "refitmid" => "required",
            "qty" => "required",
        ];

        $messages = [
            "docid.required" => "Потерялась привязка к докуенту. Вернитесь в документ",
            "refitmid.required" => "Укажите товар",
            "qty.required" => "Укажите количество товара",
        ];

        $doc = wrhdoc::find($request->get('docid'));

        if ($doc->doctype->not_gt_preqty == 1) {

            $rules['qty'] = $rules['qty'] . '|not_greater_than_field:preqty';
            $messages['qty.not_greater_than_field'] = 'Количество не может превышать количество в исходном документе!';
        }

        if ($doc->doctype->ri_produced == 1) {

            $rules['cmpnd_name'] = 'required';
            $messages['cmpnd_name.required'] = 'Укажите рецепт по которому произведено изделие!';
        }
        //dd($rules);
        Validator::make($request->all(), $rules, $messages)->validate();


        $userid = \Auth::user()->id;
        if ($id == -1) {
            $rec = new wrhdoclst([
                "docid" => $request->get('docid'),
                "refitmid" => 0,
                "qty" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);

            $msg = "Позиция создана: ";
        } else {
            $rec = wrhdoclst::find($id);
            $msg = "Позиция обновлена: ";
        }

        $pre_rec = $rec->toArray();
        $usr_rec = $request->toArray();
        $refitmid = $request->get('refitmid');

        if (isset($rec->wrhdoc->ordid)) {
            //проверим присутствие указанного товара в заказе
            $oi_cnt = orditem::where('ordid', $rec->wrhdoc->ordid)
                ->where('refitmid', $refitmid)
                ->get()->count();
            if ($oi_cnt == 0) {
                $msgType = "error";
                $msg = 'Изменения не сохранены - товар "' . $usr_rec['refitmid'] . ': ' . $usr_rec['refitmname'] . '" не присутствует в связанном заказе.';

                objlog::log_info($this->sysobjid, $rec->id, $msg, 2);
                return redirect()->back()->with($msgType, $msg)->withInput();
            }
        }

        $rec->subtypeid = $usr_rec['subtypeid'] ?? null;
        $rec->price = isset($usr_rec['price']) ? $usr_rec['price'] : null;
        //dd($rec->price);
        $rec->sum = $rec->price * $rec->qty;
        $rec->cmpndid = $usr_rec['cmpndid'] ?? null;;
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $qty = $request->get('qty');
        $rqstqty = $qty;


        DB::beginTransaction();
        try {

            $pre_qty = $rec->qty;
            if ($rec->refitmid and $refitmid <> $rec->refitmid) {
                //Изменили товар
                // => отменим влияние прежнего товара

                //Произведенм необходимый контроль и манипуляции с кол-вом данного товара на складе и в заказе
                //Вернет реально допустимуое кол-во
                $rslt = wrhdoclst::RefItmQtyAdd2Doc($rec->refitmid, -$rec->qty, $rec->wrhdoc);
                //$add_qty = $rslt->qty;
                //dd($refitmid, $rec->refitmid,-$rec->qty, $add_qty);

                $pre_qty = 0;
            }
            $rec->refitmid = $refitmid;

            //Нужно только измененное кол-во (+ или -)
            $add_qty = $qty - $pre_qty;

            //Произведем необходимый контроль и манипуляции с кол-вом данного товара на складе и в заказе
            //Вернет реально допустимое кол-во
            $rslt = wrhdoclst::RefItmQtyAdd2Doc($refitmid, $add_qty, $rec->wrhdoc);

            $add_qty = $rslt->qty;

            if ($add_qty <> 0) {
                $rec->qty = $pre_qty + $add_qty;
                $rec->save();

                DB::commit();

                $refitminfo = $rec->refitem->name . ': ' . $rec->qty
                    . ' ' . ((is_null($rec->refitem->unittypeid)) ? 'еи' : $rec->refitem->unittype->name);
                $msg = $msg . $refitminfo;
                $msg = $msg . ' (Q: было:' . $pre_rec['qty'] . ' -> хотели:' . $usr_rec['qty'] . ' -> результат:' . $rec->qty . ')';
//dd($pre_rec, $rec);
                $msgType = "success";
                if ($rqstqty <> $rec->qty) {
                    $msgType = "warning";
                    $msg = $msg . ' Но количество меньше, чем было запрошено. '
                        . $rslt->msg;
                }
                objlog::log_info($this->sysobjid, $rec->id, $msg, 5);
                if ($id == -1) {
                    objlog::log_info($this->parsysobjid, $rec->docid, $msg . '; id:' . $rec->id . '; ' . $rec->refitem->name . '; qty:' . $rec->qty, 5);
                }

                //return redirect(route('wrhdocs.edit', $rec->docid))->with($msgType, $msg);

            } else {
//                DB::rollback();
                if ($rslt->msg) {
                    DB::rollback();
                    $msgType = "warning";
                    $msg = ' Указанный товар не доступен: ' . $rslt->msg;
                } else {
                    $rec->save();
                    DB::commit();
                    $msgType = "warning";
                    $msg = ' Без изменений по количеству. ';
                }
                //return redirect()->back()->with($msgType, $msg)->withInput();
            }

            // если добавленный товар имеет спецификацию для сборки, то добавим товары из спецификации
            // в альтернативный список (используемых материалов)
            //dd($rec->refitem->has_detail);
//            if ($rec->refitem->has_detail == 1) {
//            dd($rec->subtype->chlddoctypeid, $rec->refitem->has_detail);
            if ($rec->subtype->chlddoctypeid and $rec->refitem->has_detail) {

                $subtypeid = $rec->subtype->chlddoctypeid; //Тип документа под которым нужно создать дочерние записи

                $details = ri_detail::from('ri_details as rid')
                    ->join('refitems as ri', 'ri.id', 'rid.selfrefitmid')
                    ->where('ri.producttypeid', 2)
                    ->where('rid.refitmid', $rec->refitmid)
                    ->select('rid.selfrefitmid as refitmid', 'rid.in_qty')
                    ->get();
                //dd($details);

                wrhdoclst::where('docid', $rec->docid)
                    ->where('subtypeid', $subtypeid)
                    ->where('prelstid', $rec->id)
                    ->update(['updated_by' => 0]);

                foreach ($details as $detail) {
                    //попробуем найти старую запись
                    $dl = wrhdoclst::where('docid', $rec->docid)
                        ->where('subtypeid', $subtypeid)
                        ->where('prelstid', $rec->id)
                        ->where('refitmid', $detail->refitmid)
                        ->where('updated_by', 0)
                        ->first();
                    if (!isset($dl)) {
                        $dl = new wrhdoclst([
                            'docid' => $rec->docid,
                            'subtypeid' => $subtypeid,
                            'prelstid' => $rec->id,
                            'refitmid' => $detail->refitmid,
                        ]);
                    }
                    $dl->refitmid = $detail->refitmid;
                    $dl->qty = $detail->in_qty * $rec->qty;
                    $dl->updated_by = $userid;
                    $dl->save();
                }
                //удалим лишние записи
                wrhdoclst::where('docid', $rec->docid)
                    ->where('subtypeid', $subtypeid)
                    ->where('prelstid', $rec->id)
                    ->where('updated_by', 0)
                    ->delete();
            }
            //----------------------------------------------------------------------------

        } catch (\Exception $e) {
            // something went wrong
            DB::rollback();
            $msgType = "error";
            $msg = $e->getMessage();
            return redirect()->back()->with($msgType, $msg)->withInput();
        }

        return redirect(route('wrhdocs.edit', $rec->docid))->with($msgType, $msg);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\wrhdoclst $wrhdoclst
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        $rec = wrhdoclst::find($id);

        if (isset($rec)) {
            if ($rec->wrhdoc->docsigned) {

                $sd = array();
                $sd["error"] = "Нельзя изменять состав утвержденного документа!";
                objlog::log_info($this->sysobjid, $rec->id, $sd["error"], 2);

                return redirect(route("wrhdoclst.edit", $id))->with($sd);

            } else {


                $docid = $rec->docid;
                $refitminfo = $rec->refitem->name . ': ' . $rec->qty
                    . ' ' . ((is_null($rec->refitem->unittypeid)) ? 'еи' : $rec->refitem->unittype->name);

                DB::beginTransaction();
                try {

                    //Отменим влияние товара удаляемой позиции на склад и на заказ
                    // с необходимым контролем. Если все OK, то должен вернуть кол-во = запрошенному
                    $rslt = wrhdoclst::RefItmQtyAdd2Doc($rec->refitmid, -$rec->qty, $rec->wrhdoc);

                    if ($rslt->qty == -$rec->qty) {
                        //Освободили все, что планировали

                        //Наконец перейдем к удалению записи
                        $res = wrhdoclst::delete_by_id($rec->id);

                        if ($res->err == 1) {
                            DB::rollback();

                            objlog::log_info($this->sysobjid, $rec->id, $res->msg, 2);
                            return redirect()->back()->with("error", $res->msg)->withInput();

                        } else {
                            DB::commit();

                            $msg = 'Удалена позиция: ' . $refitminfo;
                            objlog::log_info($this->parsysobjid, $docid, $msg, 5);
                            return redirect(route('wrhdocs.edit', $docid))->with("success", $msg);
                        }
                    } else {

                        DB::rollback();

                        $msgType = "error";
                        $msg = "Ошибка при удалении записи. Операция удаления отменена.";

                        $result->route = route('wrhdoclst.edit', $rec->id);
                        $result->sd["error"] = $msg;

                        objlog::log_info($this->sysobjid, $rec->id, $msg, 2);
                        return redirect()->back()->with($msgType, $msg)->withInput();

                    }

                } catch (\Exception $e) {
                    // something went wrong
                    DB::rollback();
                    $msgType = "error";
                    $msg = $e->getMessage();
                    return redirect()->back()->with($msgType, $msg)->withInput();
                }

            }
            return redirect()->back()->with($msgType, $msg)->withInput();
        }

    }

    public
    function loadFromOrder($docid, $ordid)
    {
        //сформировать состав документа с учетом неудовлетворенных потребностей заказа
        // и возможностей склада

        $doc = wrhdoc::find($docid);
        if (isset($doc)) {
            DB::statement('call loadWrhDocLstWithOrdAndStock(:p_DocID);', array($docid));

            $route = route('wrhdocs.edit', $docid);
            return redirect($route)/*->with($sd)*/ ;

        }


    }

}
