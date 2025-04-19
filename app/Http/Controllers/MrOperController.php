<?php

namespace App\Http\Controllers;

use App\contract;
use App\mr_oper;
use App\machine;
use App\mchn_raid;
use App\obj_link;
use App\objflag;
use App\objlog;
use App\objtag;
use App\org_charge;
use App\org_place;
use App\orgstaff;
use App\paydoc;
use App\route_point;
use App\stf_chrg_calc;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\user_template;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;

class MrOperController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1107;
        $this->sysobjcode = 'mr_opers';
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
        $usrrights['admindelete'] = false;

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');

        //Права менедежера возьмем по родительской записи
        $usrrights['manager'] = usrsysright::isUserHasRightByCode_cached($userid, sysobj::acl_sysobjcode('mchn_raids') . '.manager');

        $usrrights['paydocs.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'paydocs.read');
        $usrrights['paydocs.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'paydocs.create');

        if ($recid > 0) {
            //для существующих записей проверим открытость периода
            if (mr_oper::isLocked($recid)) {
                $usrrights['save'] = false;
                $usrrights['delete'] = false;
                $usrrights['admindelete'] = false;
            }
        }

        $usrrights['edit'] = $usrrights['save'];

        return $usrrights;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request, $mr_id)
    {
        return $this->edit($request, -1, $mr_id);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit(Request $request, $id, $mr_id = null)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                //Значения "по-умолчанию" для новой записи ----------------

                $ordr = $request->get('ordr')
                    ?? (mr_oper::where('mr_id', $mr_id)->select(db::raw("max(ifnull(ordr,0))+1 as ordr"))->first()->ordr ?? 1);

                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                //dd($tmplt);
                if (isset($tmplt->mr_oper)) {
                    $newData = (array)$tmplt->mr_oper; //конверитруем в массив
                }

                //Добавим свои значения
                $newData['id'] = -1;
                $newData['mr_id'] = $mr_id;
                $newData['ordr'] = $ordr;
                $newData['active'] = 1;
                $newData['created_by'] = \Auth::user()->id;
                //$newData['wrkdate'] = $newData['wrkdate'] ?? $wrkdate;

                //Возьмем что-то из предыдущей записи:
                $pre = mr_oper::where(['mr_id' => $mr_id])
                    ->where('ordr', '<', $ordr)
                    ->orderBy('id', 'desc')
                    ->first();
                if (isset($pre)) {
                    $newData['disp_staffid'] = $pre->disp_staffid;
                    $newData['suporgid'] = $pre->orgid;
                    $newData['sup_placeid'] = $pre->sup_placeid;
                    $newData['sup_placename'] = $pre->sup_placename;
                    $newData['refitmid'] = $pre->refitmid;
                    $newData['itm_qty'] = $pre->itm_qty;
                    $newData['itm_price'] = $pre->itm_price;
                    $newData['itm_sum'] = $pre->itm_sum;
                }

                $rec = new mr_oper($newData);
                //dd($rec);
                //---------------------------------------------------------

            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $rec = mr_oper::from('mr_opers as mro')->where('id', $id)->first();
            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'))
                    ->with(['error' => 'Запись не найдена!']);

            $rec->sup_places = org_place::lstFor([
                'orgid' => $rec->suporgid
            ]);

            $rec->org_places = org_place::lstFor([
                'orgid' => $rec->orgid
            ]);
        }

        $rec->sale_dirs = mr_oper::saledirs();
        $rec->paytypes = mchn_raid::paytypes();
        $rec->contracts = contract::lstFor(['between_orgs' => [$rec->suporgid, $rec->orgid]]);
//        $rec->finopers = $rec->finopers;

        //$rec->sale_places = mr_oper::sale_places();
        // по ранее использованным в операциях
//        $rec->sale_places = mr_oper::from('mr_opers as mro')
//            ->where('mro.sale_dir', 1)
//            ->whereRaw("org_placename is not null")
//            ->select('mro.org_placename as id' , 'mro.org_placename as name')
//            ->distinct()
//            ->orderBy('mro.org_placename', 'asc')
//            ->get();

        // по конечным точкам тарифицированных маршрутов
        $rec->sale_places = route_point::from('route_points as rp')
            ->whereRaw("tgt_placename is not null")
            ->select('tgt_placename as name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->get();


        //dd($rec->sale_places);
        //dd($rec->linked_paydocs);

        //Коррекция прав с учетом менеджерства
        $usrrights['save'] = ($usrrights['save'] and ($rec->created_by == $userid or $usrrights['manager']));
        $usrrights['delete'] = ($usrrights['delete'] and ($rec->created_by == $userid or $usrrights['manager']));
        //dd($rec->created_by == $userid, $usrrights['save']);

        return view('mr_opers.edit', compact('rec', "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {

        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);
        if (!($usrrights['save']))
            return redirect()->back()->with('error', 'У вас нет права на изменение этих данных!');

        $messages = [
            'suporgid.required' => 'Не указан Поставщик',
            'sup_placeid.required' => 'Не указан местонахождение товара/услуги поставщика',
            'orgid.required' => 'Укажите Заказчика',
            'refitmid.required' => 'Укажите товар/услугу',
        ];

        $rules = [
            'suporgid' => 'required',
            //'sup_placeid' => 'required',
            'orgid' => 'required',
            'refitmid' => 'required',
            //'meter_endqty' => 'required|numeric|gte:meter_begqty',
        ];

        $request->validate($rules, $messages);

        if (1 == 0) {
            //проверка что запись не пересекается с другой открытой записью с этого объекта за эту дату

            $wrkdate = $request->get('wrkdate');
            $machineid = $request->get('machineid');

            $rules = [
                "items_count" => [
                    function ($attribute, $value, $fail) use ($id, $wrkdate, $machineid) {
                        //
                        $cnt = mr_oper::where(['machineid' => $machineid, 'wrkdate' => $wrkdate, 'statusid' => 0])
                            ->where('id', '<>', $id)
                            ->count();
                        if ($cnt > 0) {
                            $fail("Есть другой открытый табель для этой технике/даты!");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);
        }


        if ($id == -1) {
            $rec = new mr_oper([
                "mr_id" => $request->get('mr_id'),
                //"active" => 0,
                //"statusid" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = mr_oper::find($id);
            $mess = "Запись обновлена";
        }

        $rec->name = $request->get('name');
        $rec->suporgid = $request->get('suporgid');
        $rec->sup_placeid = $request->get('sup_placeid');
        $rec->sup_placename = $rec->sup_place->name;
        $rec->sup_gk = objflag::IsSetObjFlag(111, $rec->suporgid, 12);


        $rec->orgid = $request->get('orgid');
        $rec->contractid = $request->get('contractid'); //договор между suporgid и orgid

        $rec->org_placeid = $request->get('org_placeid');
        if (isset($rec->org_placeid))
            $rec->org_placename = $rec->org_place->name;
        else
            $rec->org_placename = $request->get('org_placename');

        //dd($rec->org_placeid, isset($rec->org_placeid), $rec->org_placename);

        $rec->org_gk = objflag::IsSetObjFlag(111, $rec->orgid, 12);

        $rec->contractid = $request->get('contractid');
        $rec->sale_dir = $request->get('sale_dir');

        //$rec->notes = mb_substr($request->get('notes'), 0, 300);

        $rec->refitmid = $request->get('refitmid');
        $rec->itm_qty = $request->get('itm_qty') ?? 0;
        $rec->itm_price = $request->get('itm_price') ?? 0;
        $rec->itm_sum = $rec->itm_qty * $rec->itm_price;

        $rec->paytypeid = $request->get('paytypeid');
        $rec->raid_qty = $request->get('raid_qty');

        $rec->auxsvc_sum = $request->get('auxsvc_sum') ?? 0;
        $rec->agent_sum = $request->get('agent_sum') ?? 0;

        // Только для операции "Продажа" и вида работ "Тралы" - 3, "Манипуляторы" - 4, "Реф.перевозки" - 9
        if ($rec->sale_dir == 1
                and (   $rec->mchn_raid->opertypeid == 3
                     or $rec->mchn_raid->opertypeid == 4
                     or $rec->mchn_raid->opertypeid == 9)
            ) {
            $rec->driver_sum = $request->get('driver_sum') ?? 0;
        } else
            $rec->driver_sum = 0;

        $rec->disp_staffid = $request->get('disp_staffid');

        //$rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        // 2025-04-12 Вычислим кол-во баллов для данного маршрута
        $rec->route_points = route_point::points_for_route($rec->sup_placename, $rec->org_placename, $rec->mchn_raid->wrkdate);
        //dd($rec->route_points);


        //соберем строку с измененными полями -------------------------------------------------------------------
        $diffs = $this->field_diff_list($rec, ['id', 'created_by', 'updated_by', 'created_at', 'updated_at']);
        if ($diffs === '')
            $msg_simple = $rslt_msg = "Запись пересохранена без изменений";
        else {
            $msg_simple = 'Запись ' . (($id == -1) ? 'создана' : 'изменена');
            $rslt_msg = $msg_simple . ': ' . $diffs;
        }
        //-------------------------------------------------------------------------------------------------------

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $rslt_msg, 5);
        connectify('success', 'Сохранение изменений', $msg_simple);
        //-------------------------------------------------------------------------------------------------------


        //Выполним действия после обновления записи ---------------------------------------------
        mr_oper::on_update($rec);
        //---------------------------------------------------------------------------------------


        // ----------------------------------------------------------------------------------------------
        // Регистрация расчета ЗП
        // 2024-04-30 To-Do - Нужно сделать на подобие как в DriverWork
        if ( $rec->sale_dir == 1
            and (  $rec->mchn_raid->opertypeid == 3
                or $rec->mchn_raid->opertypeid == 4
                or $rec->mchn_raid->opertypeid == 9)
        ) {
            // Определим - существует ли необходимость привязки начисления этой организации к общей ведомости
            $orgcharge = org_charge::where(['orgid' => $rec->mchn_raid->driver->orgid, 'chargetypeid' => 11])->first();
            if (isset($orgcharge)) {

                //Подсчитаем общую сумму ЗП сотрудника за весь месяц
                $int_begdate = date_create($rec->mchn_raid->wrkdate)->format('Y-m-01');
                $int_enddate = date_create($rec->mchn_raid->wrkdate)->format('Y-m-t');
                $staffid = $rec->mchn_raid->driverid;
                $salary_sum = mchn_raid::from('mchn_raids as mr')
                    ->join('mr_opers as mro', 'mro.mr_id', 'mr.id')
                    ->where('driverid', $staffid)
                    ->wherebetween('wrkdate', [$int_begdate, $int_enddate])
                    ->wherein('opertypeid',[3,4,9])
                    ->where('sale_dir',1)
                    ->sum('mro.driver_sum');
//dd($rec->mchn_raid->wrkdate, $int_begdate, $int_enddate, $salary_sum);
                // Так как привязываем совокупную запись, то берем "общий" идентификатор - "0"
                $stfchrgcalc = stf_chrg_calc::where([
                    'staffid' => $staffid
                    , 'ref_sysobjid' => $this->sysobjid
                    , 'ref_objid' => 0
                    , 'docdate' => $int_begdate
                ])->first();
                if (!isset($stfchrgcalc)) {

                    $stfchrgcalc = new stf_chrg_calc([
                        "staffid" => $staffid,
                        "orgchargeid" => $orgcharge->id,
                        "charge_dir" => $orgcharge->chargetype->dir,
                        "docdate" => $int_begdate,
                        "forbegdate" => $int_begdate,
                        "forenddate" => $int_enddate,
                        "created_by" => $userid,
                        "created_at" => now(),
                        "ref_sysobjid" => $this->sysobjid,
                        "ref_objid" => 0,
                    ]);
                }
                $stfchrgcalc->staffid = $staffid;
                $stfchrgcalc->charge_sum = $salary_sum;
                $stfchrgcalc->notes = 'Трал/Манипулятор/Реф';
                $stfchrgcalc->updated_by = $userid;
                $stfchrgcalc->updated_at = now();
                //dd($stfchrgcalc);
                $stfchrgcalc->save();
            }
        }
        // ----------------------------------------------------------------------------------------------


        //Временно(? до модификации отчетов), для совместимости - модификация mchn_raids ------------------------------
        $raid = $rec->mchn_raid;

        if ($rec->sale_dir == -1) {
            //покупка
            $raid->suporgid = $rec->suporgid;
            $raid->load_placeid = $rec->sup_placeid;
            $raid->load_placename = $rec->sup_place->name ?? $rec->sup_placename;
            $raid->load_refitmid = $rec->refitmid;
            $raid->load_qty = $rec->itm_qty;
            $raid->load_price = $rec->itm_price;
            $raid->load_sum = $rec->load_sum;
            $raid->load_ownorgid = $rec->orgid;
            $raid->updated_by = $userid;    //2024-05-30
            $raid->updated_at = now();
            //$raid->save();

        } elseif ($rec->sale_dir == +1) {
            //продажа
            $raid->orgid = $rec->orgid;
            $raid->org_name = $rec->org->name;
            $raid->unload_placeid = $rec->org_placeid;
            $raid->unload_placename = $rec->org_place->name ?? $rec->org_placename;
            $raid->unload_ownorgid = $rec->suporgid;

            $raid->unload_refitmid = $rec->refitmid;
            $raid->unload_qty = $rec->itm_qty;
            $raid->unload_price = $rec->itm_price;
            $raid->unload_sum = $rec->load_sum;

            $raid->paytypeid = $rec->paytypeid;
            $raid->updated_by = $userid;    //2024-05-30
            $raid->updated_at = now();
            //$raid->save();
        }

        //соберем строку с измененными полями -------------------------------------------------------------------
        $diffs = $this->field_diff_list($raid, ['id', 'created_by', 'updated_by', 'created_at', 'updated_at']);
        if ($diffs === '')
            $msg_simple = $rslt_msg = "Запись пересохранена без изменений";
        else {
            $msg_simple = 'Запись ' . (($id == -1) ? 'создана' : 'изменена');
            $rslt_msg = $msg_simple . ': ' . $diffs;
        }
        //-------------------------------------------------------------------------------------------------------

        $raid->save();

//        objlog::log_info($this->sysobjid, $raid->id, $rslt_msg, 5);
        objlog::log_info(1106, $raid->id, $rslt_msg, 5);
        //-------------------------------------------------------------------------------------------------------

//        if ($id == -1)
//            return redirect(route($this->sysobjcode . '.edit', $rec->id));
//        else
        return redirect(route('mchn_raids.edit', $rec->mr_id) . '#oper_' . $rec->id);
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
        $usrrights = $this->setInterfaceRight($id);
        if (!($usrrights['delete'] or $usrrights['admindelete']))
            return redirect()->back()->with('error', 'У вас нет права на удаление этих данных!');


        $res = mr_oper::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route($this->sysobjcode . '.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('mchn_raids.edit', ['id' => $res->obj['mr_id']]);
            //connectify('success', ($res->obj['name'] ?? '-'), 'Запись удалена.');

            //Выполним действия после удаления записи -----------------------------------------------
            mr_oper::on_delete($res->rec);
            //---------------------------------------------------------------------------------------

        }
        return redirect($route)->with($sd);
    }


    public
    function printform1(Request $request, $id)
    {
        //печать в форме протокола
        $rec = mr_oper::from('mr_opers as p')
            ->select('p.*')->where('id', $id)->first();

        $rec->staff = mr_oper_staff::lstAllFormr_oper($id);

        $rec->items = mr_oper_item::from('mr_oper_items as i')
            ->leftjoin('orgs as o', function ($join) {
                $join->on('o.id', '=', 'i.exeorgid');
            })
            ->leftjoin('orgstaff as os', function ($join) {
                $join->on('os.id', '=', 'i.exestaffid');
            })
            ->where('i.protid', $rec->id)
            ->select('i.*', 'o.name as exeorgname', DB::raw("concat(os.lname,' ',os.fname,' ',os.mname) as exestaffname"))
            ->orderby('ordr')->get();


        return view($this->sysobjcode . '.printform1', compact('rec'));

    }

    public
    function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = mr_oper::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        $document['tags'] = objtag::lstTags($this->sysobjid, $id);
        //dd($document);

        $template_js = [
            'mr_oper' => $document,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

}
