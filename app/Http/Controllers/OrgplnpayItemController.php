<?php

namespace App\Http\Controllers;

use App\budget_item;
use App\budget_itmsum;
use App\contract;
use App\equiprqst;
use App\equiprqst_item;
use App\eritm_offer;
use App\Events\notifyEvent;
use App\invoice;
use App\objfile;
use App\objlog;
use App\org;
use App\org_acnt;
use App\orgplnpay;
use App\orgplnpay_item;
use App\orgstaff;
use App\pay_category;
use App\sysobj;
use App\usrsysright;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class OrgplnpayItemController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 902;
        $this->sysobjcode = 'orgplnpay_items';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
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


        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = ($recid <> -1 and usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.admindelete'));

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');

        $usrrights['set_funding'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.set_funding');
        $usrrights['already_pay'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.already_pay');

        //Часть прав возьмем от родительской системы ------
        $tmp_sysobjcode = $this->sysobjcode;
        $this->sysobjcode = 'orgplnpays';

        $usrrights['agr1'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.agr1');
        $usrrights['agr2'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.agr2');
        $usrrights['cancel_agr1'] = $usrrights['agr1']; //начальное значение. будет изменено
        $usrrights['cancel_agr2'] = $usrrights['agr2']; //начальное значение. будет изменено

        //$usrrights['regpay'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.regpay');

        $usrrights['only_own_items'] = !usrsysright::isUserHasRightByCode_cached($userid, 'orgplnpay.read');

        $this->sysobjcode = $tmp_sysobjcode;

        return $usrrights;
    }

    public function create(Request $request, $docid = null)
    {
        $userid = \Auth::user()->id;
        if (!isset($docid))
            return redirect(route('orgplnpays.index'))
                ->with(['error' => 'Не задан план платежей!']);

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.create'))
            return redirect(route('orgplnpays.index'))->with(['error' => 'У Вас нет прав на добавление записей в план платежей!']);

        return $this->edit(-1, $docid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $docid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {

            //Значения "по-умолчанию" для новой записи
            $ordr = orgplnpay_item::where('docid', $docid)->max('ordr') ?? 0;
            $ordr += 1;

            $rec = new orgplnpay_item([
                'id' => -1,
                'docid' => $docid,
                'ordr' => $ordr,
                'active' => 1,
                'created_by' => $userid,
            ]);
        } else {

            $rec = orgplnpay_item::find($id);

            if (!isset($rec))
                return redirect(route('orgplnpays.index'));
        }

        //шапка --------
        $orgplnpay = orgplnpay::find($rec->docid);
        if (!isset($orgplnpay))
            return redirect(route('orgplnpays.index'));


        $bSelfItem = ($rec->created_by == $userid);
        $usrrights = $this->setInterfaceRight($id);

        //если нет базового права на чтение и эта запись инициирована не пользователем, отправим к общему списку
        if (!$usrrights['read'] and !$bSelfItem)
            return redirect(route('orgplnpays.index'))->with(['error' => 'У вас нет прав на доступ к заданной записи!']);

        //2021-06-03 Инициатор имеет право изменять/удалять запись, не имея базовых прав в системе
        if (!$usrrights['save'])
            $usrrights['save'] = $usrrights['delete'] = $bSelfItem;

        //if (isset($rec->agr1_at) or isset($rec->agr2_at)) {
        //if ((isset($rec->agr1_at) and !isset($rec->agr2_at)) or (!isset($rec->agr1_at) and isset($rec->agr2_at))) {
        //Если есть оба согласования - запрещаем удалять, но если есть одно - не изменяем права
        if (isset($rec->agr1_at) and isset($rec->agr2_at)) {
            $usrrights['save'] = false;
            $usrrights['delete'] = false;
        }

        if ($id == -1 or isset($rec->fctpaysum)) {
            //нельзя согласовывать - новую запись и уже оплаченную запись
            $usrrights['agr1'] = false;
            $usrrights['agr2'] = false;
            $usrrights['cancel_agr1'] = false;
            $usrrights['cancel_agr2'] = false;

        } else {
            //отмена решения о согласовании возможна при наличии базового права на согласования и наличии решения о согласовании
            $usrrights['cancel_agr1'] = ($usrrights['cancel_agr1'] and isset($rec->agr1_at) and $rec->agr1_by == $userid);
            $usrrights['cancel_agr2'] = ($usrrights['cancel_agr2'] and isset($rec->agr2_at) and $rec->agr2_by == $userid);
        }

        //избежим наложения прав / оставим ближайшее нужное право
        if ($usrrights['agr1'] and $usrrights['agr2']) {

            //если согласование от рук-ля уже есть, но нет от финслужбы, то оставим только право финслужбы
            if (isset($rec->agr1_at) and !isset($rec->agr2_at))
                $usrrights['agr1'] = false;

            //если согласование от финслужбы уже есть, но нет от руководителя, то оставим только право руководителя
            elseif (isset($rec->agr2_at) and !isset($rec->agr1_at))
                $usrrights['agr2'] = false;

            if ($usrrights['agr1'] and $usrrights['agr2'])
                $usrrights['agr2'] = false;
        }

        //Если сохранилось право на удаление, то не нужно право на административное удаление
        if ($usrrights['delete'])
            $usrrights['admindelete'] = false;

        //Принудительно удалить можно только не проплаченные заявки
        if (isset($rec->fctpaysum))
            $usrrights['admindelete'] = false;


        //Виды платежей
        $rec->pay_categories = pay_category::lstActive();

        // Доступные договоры ------------------------------------------------------
        $rec->contracts = contract::lstFor([
            'ownorgid' => $rec->ownorgid,
            'orgid' => $rec->orgid,
            'categoryid' => 2,  //расходный
            'for_userid' => $userid,
            'actual' => 1,
        ]);


        // Другие записи плана платежей организации, который текущий пользователь имеет право видеть ------------
        $sc = "i.id<>{$rec->id}";
        if ($usrrights['only_own_items'] ?? true)
            $sc .= " and i.created_by={$userid}";

        $rec->pre_items = orgplnpay_item::from('orgplnpay_items as i')
            ->join('orgs as o', 'o.id', 'i.orgid')
            ->where('docid', $rec->docid)
            ->whereRaw($sc)
            //->where('i.id', '<>', $rec->id)
            ->select('i.*', 'o.name as orgname')
            ->orderby('i.id')
            ->get();
        //dd($rec->id, $rec->docid, $rec->pre_items, $sc);
        // -------------------------------------------------------------------------------------------------------


        //Максимально-допустимая сумма для согласования
        // = разности нач остатка и уже согласованного\

        //подсчитаем сумму уже согласованного
//        $usrrights['agr1'] = false;
//        $usrrights['agr2'] = true;
        if ($usrrights['agr1'])
            $agr_sum = orgplnpay_item::where('docid', $rec->docid)
                    ->select(db::raw('sum(agr1_sum) as agr_sum'))
                    ->first()->agr_sum ?? 0;
        elseif ($usrrights['agr2'])
            $agr_sum = orgplnpay_item::where('docid', $rec->docid)
                    ->select(db::raw('sum(agr2_sum) as agr_sum'))
                    ->first()->agr_sum ?? 0;
        else
            $agr_sum = orgplnpay_item::where('docid', $rec->docid)
                    ->wherecolumn('agr1_sum', '=', 'agr2_sum')
                    ->select(db::raw('sum(agr1_sum) as agr_sum'))
                    ->first()->agr_sum ?? 0;


        $rec->cur_restsum = $rec->doc->restbegsum - $agr_sum;
        $rec->cur_restsum = ($rec->cur_restsum < 0) ? 0 : $rec->cur_restsum;
        //dd($rec->cur_restsum);

        //2021-07-10 SNS. Теперь будем различать предельную сумму при согласовании руководителя (agr1) и фин. директора (agr2)
        // для руководителя (lim1_sum) - ограничена суммой планируемой оплаты
        // для фин. директора (lim2_sum) - как раньше - минимальной из плановой суммы оплаты и текущим нераспределенным остатком на р/сч
        //$rec->lim_sum = min($rec->plnpaysum, $rec->cur_restsum);
        $rec->lim1_sum = $rec->plnpaysum;
        $rec->lim2_sum = min($rec->plnpaysum, $rec->cur_restsum);

        if ($usrrights['already_pay'] ?? false) {
            // рассчитаем предположительную дату оплаты
            $rec->alreadypaydate = (isset($rec->agr2_at))
                ? $rec->agr2_at
                : (isset($rec->agr1_at) ? $rec->agr1_at : null);

            if (isset($rec->alreadypaydate))
                $rec->alreadypaydate = strftime('%Y-%m-%d', strtotime($rec->alreadypaydate));

            $rec->alreadypaysum = $rec->plnpaysum;
        }

        //2021-06-24 SNS. Если заявка связана со счетом, то вероятно в счете есть Объект, Вид работ
        // => можем найти данные по бюджету
        if ($rec->src_sysobjid == 915 and isset($rec->src_objid)) {

            $invoice = invoice::find($rec->src_objid);

            if (isset($invoice->buildopertypeid)) {

                //Найдем раздел бюджета:
                $budget_item = budget_item::from('budget_items as bi')
                    ->whereRaw("bi.buildopertypeid={$invoice->buildopertypeid}
                    and exists(select 1 from budgets as b where b.id=bi.budgetid and b.orgid={$invoice->ownorgid})")
                    ->select('id', 'name')->first();

                if (isset($budget_item))
                    $rec->budget_itmsums = budget_itmsum::from('budget_itmsums as bis')
                        ->join('bdgtacnttypes as bat', 'bat.id', 'bis.acnttypeid')
                        ->where(['itmid' => $budget_item->id, 'bat.dir' => -1])
                        ->select('bis.acnttypeid', 'bat.name as acnt_name', 'bis.fctsum', 'bis.fctinpsum', 'bis.fctoutsum'
                            , db::raw("(select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=bis.id) as fctopersum")
                        )->orderby('bat.ordr')
                        ->get();

                $rec->budget_item = $budget_item;
            }

            //подтянем связанные УПД
            $rec->upds = invoice::where(['pardocid' => $invoice->id, 'doctypeid' => 2])
                ->select('id', 'docnum', 'docdate', 'docsum')
                ->get();
            //dd($rec->upds);
        }


        //Заявки на материалы - кандидаты для оплаты для категории платежа "Материалы"
        $itms = DB::select(DB::raw(
            "select a.*, (select sum(fctpaysum) from orgplnpay_items as pi where pi.equiprqst_id=a.rqstid) as fctpaysum
            from ( SELECT rqstid, suporgid, sum(ord_qty*ord_price) as ord_sum, max(r.updated_at) as rqstdate
                FROM equiprqst_items as i
                join equiprqsts as r
                on r.id=i.rqstid and i.suporgid='" . $rec->orgid . "' where suporgid is not null group by  rqstid, suporgid) as a"
        ));
        $lst = [];
        foreach ($itms as $itm) {
            if ($itm->ord_sum > $itm->fctpaysum) {
                $lst[$itm->rqstid] = '№' . $itm->rqstid
                    . ', ' . date_format(date_create($itm->rqstdate), 'd.m.Y')
                    . ', заказ: ' . number_format($itm->ord_sum, 2)
                    . ', ранее оплачено: ' . number_format($itm->fctpaysum, 2);
            }
        }
        //dd($rec->equiprqst_id);

        if (isset($rec->equiprqst_id)) {
            $itm = equiprqst::find($rec->equiprqst_id);
            $lst[$itm->id] = '№' . $itm->id;
        }

        $rec->equiprqsts = $lst;
        //dd($rec->equiprqst_id, $rec->equiprqsts);
        //dd($rec->agr1_sum);

        $rec->reason = null;
        $rec->rsn_url = null;
        if (isset($rec->src_sysobjid)) {
            if ($rec->src_sysobjid == 915) {

                //счет
                $obj = invoice::find($rec->src_objid);

                $rec->reason = "Счет №" . $obj->docnum;
                $rec->rsn_url = route("invoices.edit", $rec->src_objid);
            }
        }


        if ($rec->src_sysobjid == 915) {

            invoice::chk_upd_fullpay($rec->src_objid);

        }

        //файлы к документу-основанию оплаты
        $rec->src_files = objfile::from('objfiles as f')
            ->where(['sysobjid' => $rec->src_sysobjid, 'objid' => $rec->src_objid])
            ->get();
        //dd($rec->src_files);

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
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
        //

        $userid = \Auth::user()->id;

        $docid = $request->docid;
        $doc = orgplnpay::find($docid);
        if (!isset($doc))
            return redirect(route('orgplnpays.index'));

        //проверим, находимся ли мы на этапе, подходящем для редактирования
        if ($doc->approved == 1)
            return redirect(route('orgplnpays.edit', $doc->id))->with('error', 'Нельзя изменять состав утвержденного документа');


        $categoryid = $request->get('categoryid');

        $messages = [
            'docid.required' => 'Не указан документ',
            'orgid.required' => 'Не указан получатель (контрагент)',
            'categoryid.required' => 'Не указан вид платежа',
            'reason.required' => 'Не указано основание оплаты',
            'plnpaysum.required' => 'Не указана сумма оплаты',
            'equiprqst_num.required' => 'Укажите номер заявки на материалы',
            //'docdate.before' => 'Начало работ не может быть в будущем',
        ];

        $rules = [
            "docid" => "required",
            "orgid" => "required",
            "categoryid" => "required",
            "reason" => "required",
            "plnpaysum" => "required",
        ];

        if ($categoryid == 7) {
            $rules["equiprqst_num"] = "required";
            $rules['equiprqst_num'] = 'exists:equiprqsts,id';
            //$rules["equiprqst_id"] = "required";
        }

        $request->validate($rules, $messages);


        $equiprqst_id = $request->get('equiprqst_id');
        $orgid = $request->get('orgid');

//        if (isset($equiprqst_num)) {
//            $itm = equiprqst_item::where(['rqstid' => $equiprqst_num, 'suporgid' => $orgid])->first();
//            if (!isset($itm)) {
//                return redirect()->back()->with("error", "В указанной заявке нет такого поставщика!");
//            }
//            $equiprqst_id = $itm->rqstid;
//        }
        //dd($equiprqst_id);


        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {

            if (!$usrrights['create'])
                return redirect(route('orgplnpays.index'))
                    ->with(['error' => 'У вас нет права на создание записей!']);


            $ordr = orgplnpay_item::where('docid', $docid)->selectraw('max(ordr) as ordr')->first()->ordr ?? 0;
            //dd($ordr);

            $rec = new orgplnpay_item(["docid" => $docid,
                "ordr" => ++$ordr,
                "approved" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            //dd($rec);

            $mess = "Запись создана";
        } else {
            $rec = orgplnpay_item::find($id);

            $mess = "Запись обновлена";
        }

        //Если у пользователя нет Базового права на сохранение, но эта запись Его, то разрешим сохранение
        if (!$usrrights['save'])
            $usrrights['save'] = ($rec->created_by == $userid);

        //Но если запись уже согласована всеми, то изменять ничего нельзя
        if (isset($rec->agr1_at) and isset($rec->agr2_at))
            $usrrights['save'] = false;

        //если все-таки нет права на запись, отправим к общему списку
        if (!$usrrights['save'])
            return redirect(route('orgplnpays.edit', $rec->docid))->with(['error' => 'Запись изменить нельзя!']);


        $rec->orgid = $request->get('orgid');
        $rec->contractid = $request->get('contractid');
        $rec->categoryid = $categoryid;
        $rec->reason = mb_substr($request->get('reason'), 0, 160);
        $rec->plnpaysum = $request->get('plnpaysum');
        $rec->limpaydate = $request->get('limpaydate');
        $rec->have_funding = $request->get('have_funding', 0);
        $rec->notes = mb_substr($request->get('notes'), 0, 160);
        $rec->equiprqst_id = $equiprqst_id;


        $rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();
//dd($rec);

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        connectify('success', $rec->reason, $mess);

        if ($id == -1)
            return redirect(route('orgplnpay_items.create', ['docid' => $rec->docid]));

        return redirect(route('orgplnpays.edit', ['id' => $rec->docid]) . '#items');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        $userid = \Auth::user()->id;
        $route = route('orgplnpay_items.edit', $id);

        $rec = orgplnpay_item::find($id);
        if (isset($rec) and isset($rec->fctpaysum))
            return redirect($route)->with(['error' => 'Нельзя удалить запись с зарегистрированной оплатой!']);

        $usrrights = $this->setInterfaceRight($id);
        //Если у пользователя нет Базового права на удаление, но эта запись Его, то разрешим сохранение
        if (!$usrrights['delete'])
            $usrrights['delete'] = ($rec->created_by == $userid);

        //Но если запись уже согласована всеми, то удалять нельзя
        if (isset($rec->agr1_at) and isset($rec->agr2_at))
            $usrrights['delete'] = false;

        //если все-таки нет права на запись, отправим к общему списку
        if (!$usrrights['delete'])
            return redirect(route('orgplnpay_items.edit', $rec->id))->with(['error' => 'Запись удалить нельзя!']);

        //изменить статус связанного счета
        if ($rec->src_sysobjid == 915) {
            invoice::where('id', $rec->src_objid)
                ->update(['locked' => 0,
                    'status' => '']);
        }


        $res = orgplnpay_item::delete_by_id($id, $this->sysobjid);
        $obj_name = $res->obj['reason'];
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('orgplnpay_items.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $obj_name, $res->msg);
        } else {
            $sd['success'] = 'Запись удалена (' . $id . ': '
                . ($obj_name ?? '') . ')';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('orgplnpays.edit', ['id' => $res->obj['docid']]);
            connectify('success', $obj_name ?? '', 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    public function update_agr1(Request $request, $id)
    {
        //
        //dd($request);
        $messages = [
            'id.required' => 'Не указана позиция документа',
            'agr_sum.required' => 'Не указана сумма оплаты',
        ];

        $rules = [
            "id" => "required",
            "agr_sum" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $rec = orgplnpay_item::find($id);

        //проверим, находимся ли мы на этапе, подходящем для редактирования
        if (isset($rec->fctpaysum))
            return redirect(route('orgplnpays.edit', $rec->id))->with('error', 'Нельзя изменять оплаченный документ');


        $rec->agr1_sum = $request->get('agr_sum');
        $rec->agr1_notes = $request->get('agr1_notes');
        $rec->agr1_by = $userid;
        $rec->agr1_at = now();
        //dd($rec);
        $rec->save();

        $mess = "Согласована оплата: " . $rec->agr1_sum;
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        connectify('success', ' ', $mess);

        //2021-07-07 SNS. Зиновьев - после согласования выходить в общий список
        if (1 == 0) {
            //найдем следующую запись без решения:
            $nxt = orgplnpay_item::where('docid', $rec->docid)
                ->wherenull('agr1_by')
                ->wherenull('fctpaysum')
                ->select('id')
                ->first();
            if (isset($nxt))
                return redirect(route('orgplnpay_items.edit', ['id' => $nxt->id]));
        }

        return redirect(route('orgplnpays.edit', ['id' => $rec->docid]) . "?#itm_{$rec->id}");

    }


    public function cancel_agr1(Request $request, $id)
    {
        //Отмена решения о согласовании
        // Проверим, что отменяет решение тот же пользователь, который его принимал
        // и что еще нет регистрации оплаты

        $userid = \Auth::user()->id;

        $rec = orgplnpay_item::find($id);
        if (isset($rec)) {

            if ($rec->agr1_by !== $userid)
                return redirect(route('orgplnpay_items.edit', $id))->with('error', 'Нельзя отменить чужое решение!');
        }

        $pre_agr_sum = $rec->agr1_sum; //для сообщения

        $cnt = orgplnpay_item::where('id', $id)
            ->where('agr1_by', $userid)//Защита от фонового изменения данных
            ->whereNull('fctpaysum')//Защита от фоновой оплаты
            ->update([
                'agr1_sum' => null,
                'agr1_by' => null,
                'agr1_at' => null,
            ]);
        if ($cnt > 0)
            $mess = "Отменено решение о согласовании оплаты: " . $pre_agr_sum;
        else
            $mess = "Попытка отменить решение о согласовании оплаты: " . $pre_agr_sum;
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        return redirect(route('orgplnpay_items.edit', ['id' => $id]));
    }


    public function cancel_agr2(Request $request, $id)
    {
        //Отмена решения о согласовании
        // Проверим, что отменяет решение тот же пользователь, который его принимал
        // и что еще нет регистрации оплаты

        $userid = \Auth::user()->id;

        $rec = orgplnpay_item::find($id);
        if (isset($rec)) {

            if ($rec->agr2_by !== $userid)
                return redirect(route('orgplnpay_items.edit', $id))->with('error', 'Нельзя отменить чужое решение!');
        }

        $pre_agr_sum = $rec->agr2_sum; //для сообщения

        $cnt = orgplnpay_item::where('id', $id)
            ->where('agr2_by', $userid)//Защита от фонового изменения данных
            ->whereNull('fctpaysum')//Защита от фоновой оплаты
            ->update([
                'agr2_sum' => null,
                'agr2_by' => null,
                'agr2_at' => null,
            ]);
        if ($cnt > 0)
            $mess = "Отменено решение о согласовании оплаты: " . $pre_agr_sum;
        else
            $mess = "Попытка отменить решение о согласовании оплаты: " . $pre_agr_sum;
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        return redirect(route('orgplnpay_items.edit', ['id' => $id]) . "?#itm_{$rec->id}");
    }


    public
    function update_agr2(Request $request, $id)
    {
        //
        //dd($request);
        $messages = [
            'id.required' => 'Не указана позиция документа',
            'agr_sum.required' => 'Не указана сумма оплаты',
        ];

        $rules = [
            "id" => "required",
            "agr_sum" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        //проверим, находимся ли мы на этапе, подходящем для редактирования
//        if (isset($itm->fctpaysum))
//            return redirect(route('orgplnpays.edit', $doc->id))->with('error', 'Нельзя изменять состав утвержденного документа');

        $rec = orgplnpay_item::find($id);

        $rec->agr2_sum = $request->get('agr_sum');
        $rec->agr2_notes = $request->get('agr2_notes');
        $rec->agr2_by = $userid;
        $rec->agr2_at = now();
        //dd($rec);
        $rec->save();

        $mess = "Согласована оплата: " . $rec->agr2_sum;
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        //connectify('success', ' ', $mess);

        if (isset($rec->agr1_sum) and isset($rec->agr2_sum) and $rec->agr2_sum > 0) {
            //предупредить пользователей с правами на рег. оплаты
            $subj = "Согласована оплата в плане платежей - (" . $rec->reason . " / " . $rec->agr2_sum . ")";
            $msg = "<hr><a href='" . route('orgplnpay_items.regpay', $rec->id) . "'>Перейти к заявке</a>";

            event(new notifyEvent('orgplnpay_items.pay_agree', $this->sysobjid, $rec->id, $userid, $subj, $msg));
        }

        if (1 == 0) {
            //найдем следующую запись без решения:
            $nxt = orgplnpay_item::where('docid', $rec->docid)
                ->wherenull('agr2_by')
                ->wherenull('fctpaysum')
                ->select('id')
                ->first();
            if (isset($nxt))
                return redirect(route('orgplnpay_items.edit', ['id' => $nxt->id]));
        }

        return redirect(route('orgplnpays.edit', ['id' => $rec->docid]) . "?#itm_{$rec->id}");
    }


    public
    function regpay($id)
    {
        $userid = \Auth::user()->id;

        $rec = orgplnpay_item::find($id);

        if (!isset($rec))
            return redirect(route('orgplnpays.index'));

        //шапка
        $orgplnpay = orgplnpay::find($rec->docid);
        if (!isset($orgplnpay))
            return redirect(route('orgplnpays.index'));

        //допустимые р/счета плательщика
        $rec->orgacnts = org_acnt::lstActiveForPay($rec->doc->ownorgid);
        //dd($rec->orgacnts,count($rec->orgacnts),key($rec->orgacnts));
        if (count($rec->orgacnts) == 1)
            $rec->orgacntid = key($rec->orgacnts);  //ключ первой записи в массиве


        //файлы к документу-основанию оплаты
        $rec->src_files = objfile::from('objfiles as f')
            ->where(['sysobjid' => $rec->src_sysobjid, 'objid' => $rec->src_objid])
            ->get();
        //dd($rec->src_files);


        //другие согласованные платежи
        $rec->forpay_items = orgplnpay_item::from('orgplnpay_items as i')
            ->join('orgs as o', 'o.id', 'i.orgid')
            ->where('docid', $rec->docid)
            //->whereColumn('i.agr1_sum', '=', 'i.agr2_sum')
            ->where('i.agr1_sum', '>', 0)//руководитель должен согласовать хоть какую-то сумму
            ->where('i.agr2_sum', '>', 0)//фин.директор должен согласовать хоть какую-то сумму
            ->select('i.*', 'o.name as orgname')
            ->orderby('i.id')
            ->get();
        //dd($rec->id, $rec->docid, $rec->pre_items);

        $usrrights = $this->setInterfaceRight($id);

        //если строка не согласована, то не дать зарегистрировать оплату
        if (!isset($rec->agr1_at) or !isset($rec->agr2_at)) {
            $usrrights['regpay'] = false;
        } elseif ($rec->agr1_sum <> $rec->agr2_sum)
            $usrrights['regpay'] = false;


        $usrrights['init_pay'] = (!isset($rec->initpay_by));

        //признак отправки ПП в банк
        $rec->pay_inited_checked = (isset($rec->initpay_by)) ? 'checked' : '';
        //признак получения подтверждения банка об оплате
        $rec->bank_confirmed_checked = (isset($rec->fctpay_by)) ? 'checked' : '';


        return view($this->sysobjcode . '.regpay', compact('rec', "usrrights"));
    }

    public
    function update_regpay(Request $request, $id)
    {
        //регистрация отправки ПП в банк

        $messages = [
            'id.required' => 'Не указана позиция документа',
            'fctpaysum.required' => 'Не указана сумма оплаты',
        ];

        $rules = [
            "id" => "required",
            "fctpaysum" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;

        $rec = orgplnpay_item::find($id);

        //проверим, находимся ли мы на этапе, подходящем для редактирования
        if (!(isset($rec->agr1_sum) and isset($rec->agr2_sum)
            //and $rec->agr1_sum === $rec->agr2_sum
            and $rec->agr1_sum > 0 and $rec->agr2_sum > 0
        ))
            return redirect(route('orgplnpay_items.edit', $id))->with('error', 'Позиция еще не согласована!');


        $split_rest = false;
        $chk_invoice_fullpay = false;

        //порядок Важен - сначала проверяем подтверждение банка, затем отправку пп в банк,
        // так как отмена отправки в банк должна затирать и подтверждение банка
        if ($request->get('bank_confirmed') == 1 and !isset($rec->fctpay_by)) {
            //регистрация получения подтверждения оплаты от банка

            $rec->fctpay_by = $userid;
            $rec->fctpay_at = now();

            objlog::log_info($this->sysobjid, $rec->id, "Зарегистрировано подтверждение банка об оплате", 3);

            $split_rest = true;             //проверим на необходимость выделения остатка в отдельную заявку на оплату
            $chk_invoice_fullpay = true;    //Необходимо проверить полная ли оплата у счета
        }

        if ($request->get('bank_confirmed') != 1 and isset($rec->fctpay_by)) {
            //регистрация отмены подтверждения оплаты от банка

            $rec->fctpay_by = null;
            $rec->fctpay_at = null;

            objlog::log_info($this->sysobjid, $rec->id, "Отмена подтверждения банка об оплате", 3);

            $chk_invoice_fullpay = true;    //Необходимо проверить полная ли оплата у счета
        }


        if ($request->get('pay_inited') == 1 and !isset($rec->initpay_by)) {
            //регистрация отправки ПП в банк на оплату

            $rec->fctpaysum = $request->get('fctpaysum');
            $rec->orgacntid = $request->get('orgacntid');

            $rec->initpay_by = $userid;
            $rec->initpay_at = now();

            objlog::log_info($this->sysobjid, $rec->id, "Зарегистрирована отправка ПП в банк на оплату", 3);
        }

        if ($request->get('pay_inited') <> 1 and isset($rec->initpay_by)) {
            //отмена регистрация отправки ПП на оплату в банк

            $rec->fctpaysum = $request->get('fctpaysum');
            $rec->orgacntid = $request->get('orgacntid');

            $rec->initpay_by = null;
            $rec->initpay_at = null;

            //отменим также и факт оплаты
            $rec->fctpay_by = null;
            $rec->fctpay_at = null;

            //dd($rec);
            objlog::log_info($this->sysobjid, $rec->id, "Отмена регистрации отправки в банк", 3);

        }

        $rec->save();

        //$mess = "Зарегистрирована передача ПП в банк на оплату: " . $rec->fctpaysum;
        //objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
        //connectify('success', ' ', $mess);

        //только при наличии отметки об отправке ПП в банк
        if ($split_rest) {
            //Если оплачено не ВСЁ, то создадим запись для остатка
            orgplnpay_item::split_rest($rec);
        }


        //только при наличии подтверждения банка об оплате
        if ($chk_invoice_fullpay) {

            //Если оплачивали счет (src_sysobjid==915), то проверим - может оплата была полной,
            // тогда в счете отметим дату полной оплаты (invoice.fullpaydate)
            // и, как минимум, рассчитаем план. дату поставки по позицим счета, связанным с заявкой (eritm_offers.plngetdate)
            if ($rec->src_sysobjid == 915) {
                invoice::chk_upd_fullpay($rec->src_objid);
            }
        }


        //найдем следующую согласованную запись без оплаты:
        $nxt = orgplnpay_item::where('docid', $rec->docid)
            ->whereColumn('agr1_sum', '=', 'agr2_sum')
            //->wherenull('fctpaysum')
            ->whereColumn('fctpaysum', '<', 'agr2_sum')
            ->select('id')
            ->first();
        if (isset($nxt))
            return redirect(route('orgplnpay_items.regpay', ['id' => $nxt->id]));

        return redirect(route('orgplnpays.edit', ['id' => $rec->docid]));
    }


    static public function toggle_funding($id)
    {
        $userid = \Auth::user()->id;
        //dd($userid, usrsysright::isUserHasRightByCode_cached($userid, 902 . '.set_funding'));

        if (usrsysright::isUserHasRightByCode_cached($userid, 'orgplnpay_items.set_funding')) {

            $rec = orgplnpay_item::find($id);
            if (isset($rec)) {

                $rec->have_funding = !$rec->have_funding;
                $rec->save();

                if ($rec->have_funding)
                    objlog::log_info(902, $rec->id, "Установлена отметка о налчиии финансирования", 5);
                else
                    objlog::log_info(902, $rec->id, "Снята отметка о налчиии финансирования", 5);
            }
        }

        return redirect(route('orgplnpay_items.edit', $id));
    }

    static public function upd_alreadypay(Request $request, $id)
    {
        $messages = [
            'id.required' => 'Не указана позиция документа',
            'alreadypaydate.required' => 'Не указана дата оплаты',
            'alreadypaysum.required' => 'Не указана сумма оплаты',
        ];

        $rules = [
            "alreadypaydate" => "required",
            "alreadypaysum" => "gt:0",
        ];

        $request->validate($rules, $messages);


        $alreadypaydate = $request->get('alreadypaydate');
        $alreadypaysum = $request->get('alreadypaysum');
        //dd($alreadypaydate);

        $userid = \Auth::user()->id;

        $mess = "";
        $rec = orgplnpay_item::find($id);

        $pre_docid = $rec->docid; //Текущее подчинение

        //найдем или создадим план платежей этой организации на указанную дату
        $doc = orgplnpay::updDateRestSum($rec->doc->ownorgid, $alreadypaydate);

        if (isset($doc)) {

            try {
                DB::beginTransaction();

                $rec->docid = $doc->id; //Переподчиним запись

                if (!isset($rec->agr1_by)) {
                    $rec->agr1_sum = $alreadypaysum;
                    $rec->agr1_at = now();
                    $rec->agr1_by = $userid;
                }
                if (!isset($rec->agr2_by)) {
                    $rec->agr2_sum = $alreadypaysum;
                    $rec->agr2_at = now();
                    $rec->agr2_by = $userid;
                }
                $rec->fctpaysum = $alreadypaysum;
                $rec->fctpay_by = $userid;
                $rec->fctpay_at = $alreadypaydate;
                //$rec->notes = $rec->notes . '/Зарегистрировано как оплаченное ранее';
                //dd($rec);
                //dd($rec, $request->get('alreadypaydate'));
                $rec->save();

                objlog::log_info(902, $rec->id, "Зарегистрировано как оплаченное ранее. Сумма: {$alreadypaysum}", 5);

                //Если оплачено не все, то создадим запись для остатка
                orgplnpay_item::split_rest($rec);

                //Если оплачивали счет (src_sysobjid==915), то проверим - может оплата была полной,
                // тогда в счете отметим дату полной оплаты (invoice.fullpaydate)
                // и, как минимум, рассчитаем план. дату поставки по позицим счета, связанным с заявкой (eritm_offers.plngetdate)
                if ($rec->src_sysobjid == 915) {
                    invoice::chk_upd_fullpay($rec->src_objid);
                }

                DB::commit();

            } catch (\Exception $e) {
                \Log::debug($e->getMessage());
                return $e->getMessage();
            } finally {
            }


        }

        //return redirect(route('orgplnpay_items.edit', $id));
        return redirect(route('orgplnpays.edit', $pre_docid) . '#items');
    }
}
