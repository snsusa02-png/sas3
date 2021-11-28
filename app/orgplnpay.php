<?php

namespace App;

use App\Events\notifyEvent;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use DateTime;
use Illuminate\Support\Facades\Log;

class orgplnpay extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'orgplnpays';
    static public $sysobjid = 901;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function ownorg()
    {//Плательщик
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    //связь с позициями
    public function items()
    {
        return $this->hasMany(orgplnpay_item::class, 'docid', 'id')
            ->with('org')
            ->with('category');
    }

    public static function make_notifies()
    {
        //формирование уведомлений по текущему состоянию всей БД на сегодня ------------------------------

        //определим остаток, доступный для согласования


        //отберем записи по которым нет согласия
        $recs = orgplnpay_item::from('orgplnpay_items as i')
            ->join('orgplnpays as p', 'p.id', 'i.docid')
            ->join('orgs as oo', 'oo.id', 'p.ownorgid')
            ->where('p.docdate', now()->format('Y-m-d'))
            ->whereRaw("ifnull(i.agr1_sum,-1) <> ifnull(i.agr2_sum,-2)")
            //->whereraw('1=0')
            ->select('p.id', 'p.ownorgid', 'i.agr1_by', 'i.agr2_by', 'oo.name as orgname')
            ->get();

        $enddt = new DateTime('tomorrow');

        if (count($recs) > 0) {
            foreach ($recs as $rec) {
                //определим - есть ли пользователи с нужными правами

                if (!isset($rec->agr1_by)) {
                    $users = User::UsersWithRightCodeInOrg('orgplnpays.agr1', $rec->ownorgid);

                    foreach ($users as $user) {
                        user_notice::addOrUpdate(1902, route("orgplnpays.edit", $rec->id), $user->id
                            , 'Согласовать платежи "' . $rec->orgname . '" на ' . today(), ""
                            , now(), $enddt);
                    }
                }

                if (!isset($rec->agr2_by)) {
                    $users = User::UsersWithRightCodeInOrg('orgplnpays.agr2', $rec->ownorgid);

                    foreach ($users as $user) {
                        user_notice::addOrUpdate(1902, route("orgplnpays.edit", $rec->id), $user->id
                            , 'Согласовать платежи "' . $rec->orgname . '" на сегодня', "", now(), $enddt);
                    }
                }

            }
        } else {
            user_notice::removeByEventID(1902);
        }


        //отберем согласованные записи по которым еще нет платежа
        $recs = orgplnpay_item::from('orgplnpay_items as i')
            ->join('orgplnpays as p', 'p.id', 'i.docid')
            ->join('orgs as oo', 'oo.id', 'p.ownorgid')
            ->where('p.docdate', now()->format('Y-m-d'))
            ->whereRaw("ifnull(i.agr1_sum,-1) = ifnull(i.agr2_sum,-2) and i.fctpay_by is null")
            //->whereraw('1=0')
            ->select('p.id', 'p.ownorgid', 'oo.name as orgname')
            ->get();

        if (count($recs) > 0) {
            foreach ($recs as $rec) {
                //определим - есть ли пользователи с нужными правами

                $users = User::UsersWithRightCodeInOrg('orgplnpays.regpay', $rec->ownorgid);

                foreach ($users as $user) {
                    user_notice::addOrUpdate(1903, route("orgplnpays.edit", $rec->id), $user->id
                        , 'Провести согласованные платежи "' . $rec->orgname . '"', "", now(), $enddt);
                }
            }
        } else {
            user_notice::removeByEventID(1903);
        }

        user_notice::removeEnded(); //удалить ззавершенные (по времени отображения) уведомления
    }


    public static function updTodayRestSum($orgid)
    {
        //создадим/обновим запись для плана оплат на сегодня - на основании остатков на р/счетах ---
        if (isset($orgid)) {

            $userid = \Auth::user()->id;
            $today = now()->format('Y-m-d');

            // 1. Подсчитаем сумму доступных остатков на дату
            $restsum = orgacnt_sum::from('orgacnt_sums as oas')
                    //->where('forpay', 1)
                    ->whereraw(" oas.ondate=curdate()
                        and oas.acntid in (select id from org_acnts as oa
                                where oa.orgid={$orgid} and oa.forpay=1)")
                    //->selectraw("sum(oas.restsum) as restsum")
                    ->selectraw("sum(oas.restsum+oas.inpsum-oas.outsum) as restsum")
                    ->first()
                    ->restsum ?? 0;

            //поищем запись о плане на сегодня
            $plan = orgplnpay::where('ownorgid', $orgid)
                ->where('docdate', $today)
                ->first();
            //dd($plan);
            if (!isset($plan)) {
                $plan = new orgplnpay([
                    'ownorgid' => $orgid,
                    'docdate' => $today,
                    'active' => 1,
                    'restbegsum' => -1,
                    'created_at' => now(),
                    'created_by' => $userid,
                ]);
            }
            $pre_restbegsum = $plan->restbegsum;
            $plan->restbegsum = $restsum;
            $plan->inituserid = $userid;
            $plan->save();

            //Перенесем, если есть, неоплаченные заявки из предыдущей даты
            $rsltStatus = self::fillItemsFromPrev($plan->id, $userid);

            if ($plan->docdate == date('Y-m-d')
                and ($plan->restbegsum <> $pre_restbegsum)) {
                //уведомим кого-нибудь
                event(new notifyEvent('orgplnpays.restsum_changed', 901, $plan->id, $userid, 'Введен/изменен остаток на р/с ' . $plan->ownorg->name, '-'));
            }
        }
    }

    public static function updDateRestSum($orgid, $date = null)
    {
        //создадим/обновим запись для плана оплат на $date - на основании остатков на р/счетах ---
        // - возвращает запись о плане
        if (isset($orgid)) {

            $userid = \Auth::user()->id;

            $date = ($date) ?? now()->format('Y-m-d');
            $date = date_create($date)->format('Y-m-d');

            // 1. Подсчитаем сумму доступных остатков на дату
            $restsum = orgacnt_sum::from('orgacnt_sums as oas')
                    //->where('forpay', 1)
                    ->whereraw(" oas.ondate='{$date}'
                        and oas.acntid in (select id from org_acnts as oa
                                where oa.orgid={$orgid} and oa.forpay=1)")
                    //->selectraw("sum(oas.restsum) as restsum")
                    ->selectraw("sum(oas.restsum+oas.inpsum-oas.outsum) as restsum")
                    ->first()
                    ->restsum ?? 0;

            //поищем запись о плане на нужную дату
            $plan = orgplnpay::where(['ownorgid' => $orgid, 'docdate' => $date])->first();
            //dd($plan);
            if (!isset($plan)) {
                $plan = new orgplnpay([
                    'ownorgid' => $orgid,
                    'docdate' => $date,
                    'active' => 1,
                    'restbegsum' => -1,
                    'created_at' => now(),
                    'created_by' => $userid,
                ]);
            }
            $pre_restbegsum = $plan->restbegsum;
            $plan->restbegsum = $restsum;
            $plan->inituserid = $userid;
            $plan->save();

            //Перенесем, если есть, неоплаченные заявки из предыдущей даты
            $rsltStatus = self::fillItemsFromPrev($plan->id, $userid);

            //если "сегодня"
            if ($plan->docdate == date('Y-m-d')
                and ($plan->restbegsum <> $pre_restbegsum)) {
                //уведомим кого-нибудь
                event(new notifyEvent('orgplnpays.restsum_changed', 901, $plan->id, $userid, 'Введен/изменен остаток на р/с ' . $plan->ownorg->name, '-'));
            }

            return $plan;
        }
        return null;
    }


    public static function fillItemsFromPrev($id, $userid = null)
    {
        $userid = $userid ?? \Auth::user()->id;

        if (usrsysright::isUserHasRightByCode_cached($userid, 'orgplnpay_items.create')) {

            $rec = orgplnpay::find($id);
            if (isset($rec)) {
                //найдем ближайший пред. план для этой же организации
                $pre = orgplnpay::where('ownorgid', $rec->ownorgid)
                    ->where('docdate', '<', $rec->docdate)
                    ->orderby('docdate', 'desc')
                    ->first();

                if (isset($pre)) {

                    $ttt = orgplnpay_item::where('docid', $pre->id)
                        ->wherenull('initpay_by')   //если платеж передан в банк, то не перемещать
                        ->wherenull('fctpay_by')
                        ->where('active', 1)
                        ->update(['docid' => $rec->id]);

                    $rsltStatus = ['success' => 'ok'];

                    $msg = $ttt . " записей перенесены из предыдущего плана оплат";
                    connectify('success', $msg, 'ok');
                    objlog::log_info(self::$sysobjid, $id, $msg, 5);

                    //сформируем уведомления
                    orgplnpay::make_notifies();
                }
            }
        } else
            $rsltStatus = ['error' => 'У вас нет прав на это действие!'];

        return $rsltStatus;
    }


    public static function removeExpiredItems($planid = null)
    {
        // 2021-08-05 ZinovievAN, SNS: заблокируем пока(?)
        return;

        //Удаление заявок на оплату с истекшим сроком предельной оплаты и не имеющих отметки об оплате
        //2021-06-07

        Log::info("Проверка и удаление несогласованных и просроченных заявок на оплату");

        $sc = "src_sysobjid=915";
        if (isset($planid))
            $sc .= " and docid={$planid}";

        $recs = orgplnpay_item::where('limpaydate', '<', today())
            ->whereNull('fctpay_at')
            ->whereRaw("ifnull(agr1_sum,-1)<>ifnull(agr2_sum,-2)")
            ->whereRaw($sc)
            ->get();

        $eventtypeid = 91505;
        $eventtype = eventtype::find($eventtypeid);
        $subj = $eventtype->dflt_subj ?? 'Счет снят с оплаты';

        $del_cnt = 0;
        foreach ($recs as $rec) {
            //отметим, что счет был отозван
            $invoice = invoice::where('id', $rec->src_objid)->first();
            if (isset($invoice)) {
                $invoice->status = 'Снят с оплаты (окончание срока действия)';
                $invoice->save();

                //сохраним в журнале
                objlog::log_info(915, $rec->src_objid, 'Снят с оплаты (окончание срока действия)', 3);

                //Уведомим инициаторов счета о снятии с оплаты
                $users = obj_reader::where(['sysobjid' => 915, 'objid' => $rec->src_objid, 'roletypeid' => 1])->get();
                if ($users->count() > 0) {
                    $msg = "Ваш счет снят с оплаты №{$invoice->docnum} ({$invoice->org->name})";
                    foreach ($users as $user) {
                        user_notice::addOrUpdate($eventtypeid, route("invoices.edit", $invoice->id)
                            , $user->userid
                            , $subj, $msg
                            , now(), date_add(now(), date_interval_create_from_date_string("6 days"))
                        );
                    }
                }
            }

            //удалим заявку на оплату
            $rec->delete();
            $del_cnt++;
        }
        if ($del_cnt > 0) {
            //сохраним в журнале
            objlog::log_info(901, $planid ?? 0, "{$del_cnt} заявок на оплату отозвано в связи с превышением допустимого срока оплаты", 3);
        }
    }


}

