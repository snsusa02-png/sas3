<?php

namespace App\Listeners;

use App\equiprqst;
use App\equiprqst_staff;
use App\Events\notifyEvent;
use App\invoice;
use App\mchnrqst;
use App\meeting;
use App\meeting_staff;
use App\myChat;
use App\obj_msg;
use App\objlog;
use App\order;
use App\org_extservice;
use App\orgplnpay;
use App\orgplnpay_item;
use App\sysobj;
use App\task;
use App\task_report;
use App\user_notice;
use App\User;
use App\usrsysright;
use App\wrkplan;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\SendNotify;


class notifyEventListener implements ShouldQueue
{
    public $queue = 'high';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param notifyEvent $event
     * @return void
     */
    public function handle(notifyEvent $event)
    {
        info("EVENT: typeid: $event->eventtypeid,  источник: $event->src_sysobjid - $event->src_objid, инициатор: $event->inituserid");


        if ($event->eventtypeid == 'user.new_message') {
            //уведомление о новом сообщении для $event->inituserid

            $rec = obj_msg::find($event->src_objid);
            if (isset($rec)) {

                $subj = "Вам оставлено сообщение (от " . $rec->author->FirstLast . ")";

                $sysobj = sysobj::find($rec->sysobjid);
                $retURL = '';
                if (isset($sysobj)) {
                    $retURL = route($sysobj->code . '.edit', $rec->objid);
                }
                $msg = $rec->author->FirstLast . ": <br><br>" . $rec->body;

                $msg .= "<br><br><hr>(!) Не отвечайте на это уведомление, так как оно сформировано роботом.";

                if (isset($retURL)) {
                    $msg .= "<br><br><a href='" . $retURL . "'>Ответить можно здесь</a>";
                }

                $rcpt = User::find($event->inituserid);
                //dd($rcpt);
                //foreach ($rcpts as $rcpt) {
                $email = $rcpt->email;
                //$email = "sns@itqua.ru";
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                    objlog::log_info($rec->sysobjid, $rec->objid, 'Уведомление о сообщении направлен пользователю (' . $email . ')', 5);
                }
                //}
            }


        } elseif ($event->eventtypeid == 'mchnrqsts.needapprove') {
            //запрос на согласование исполнителем заявки на спец-технику ------------------------
            $rqst = mchnrqst::find($event->src_objid);
            if (isset($rqst)) {

                $subj = "Необходимо согласовать заявку №$rqst->id на спец-технику (" . $rqst->rqstmachine->name . ")";
                $msg = "№ " . $rqst->id . " " . $rqst->rqstmachine->name
                    . "<br><hr><a href='" . route('mchnrqsts.edit', $rqst->id)
                    . "'>Перейти к заявке</a>";

                $rcpts = User::UsersWithRightID(262); //'mchnrqsts.approve'
                //$rcpts = User::UsersWithRightCode('mchnrqsts.approve'); //
                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    echo("email:$email");
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info(483, $event->src_objid, 'Запрос на согласование заявки направлен пользователю (' . $email . ')', 5);
                    }
                }
            }
        } elseif ($event->eventtypeid == 'mchnrqsts.mngr_decision') {
            // Менеджер принял решение по заявке
            $rqst = mchnrqst::find($event->src_objid);
            if (isset($rqst)) {

                if (!is_null($rqst->decision)) {

                    if ($rqst->decision == 0)
                        $subj = "Отказ по заявке №$rqst->id на спец-технику (" . $rqst->rqstmachine->name . ")";
                    elseif ($rqst->decision == 1)
                        $subj = "Согласована заявка №$rqst->id на спец-технику (" . $rqst->rqstmachine->name . ")";

                    $msg = "№ " . $rqst->id . " " . $rqst->rqstmachine->name
                        . "<br><hr><a href='" . route('mchnrqsts.edit', $rqst->id)
                        . "'>Перейти к заявке</a>";

                    $rcpt = User::select('email')->find($rqst->inituserid);
                    if (isset($rcpt)) {
                        $email = $rcpt->email;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));

                            objlog::log_info(483, $event->src_objid, 'Уведомление о решении менеджера направлено инициатору заявки (' . $email . ')', 5);
                        }

                    }
                }
            }
        } elseif ($event->eventtypeid == 'wrkplans.reg_plan') {
            // Опубликован план работ
            $rqst = wrkplan::find($event->src_objid);
            if (isset($rqst)) {

                $subj = "Необходимо ознакомиться с планом работ №$rqst->id (" . $rqst->inituser->name . ")";
                $msg = "№ " . $rqst->id . " " . $rqst->inituser->name
                    . "<br>компания: " . $rqst->org->name
                    . "<br>период: " . $rqst->begdate . " - " . $rqst->enddate
                    . "<br><hr><a href='" . route('wrkplans.edit', $rqst->id)
                    . "'>Перейти к плану работ</a>";

                $rcpts = User::UsersWithRightID(373); //'wrkplans.check'
                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    echo("email:$email");
                    if (filter_var($email, FILTER_VALIDATE_EMAIL))
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }
            }
        } elseif ($event->eventtypeid == 'wrkplans.reg_report') {
            // Опубликован план работ
            $rqst = wrkplan::find($event->src_objid);
            if (isset($rqst)) {

                $subj = "Необходимо ознакомиться с отчетом о выполнении плана работ №$rqst->id (" . $rqst->inituser->name . ")";
                $msg = "№ " . $rqst->id . " " . $rqst->inituser->name
                    . "<br>компания: " . $rqst->org->name
                    . "<br>период: " . $rqst->begdate . " - " . $rqst->enddate
                    . "<br><hr><a href='" . route('wrkplans.edit', $rqst->id)
                    . "'>Перейти к отчету</a>";

                $rcpts = User::UsersWithRightID(373); //'wrkplans.check'
                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    echo("email:$email");
                    if (filter_var($email, FILTER_VALIDATE_EMAIL))
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }
            }
        } elseif ($event->eventtypeid == 'equiprqsts.reg_rqst' or $event->eventtypeid == 'equiprqsts.set_stage_2') {
            // Опубликована заявка на материалы - передана на согласование технолога (инженера ПТО)
            $rqst = equiprqst::find($event->src_objid);
            if (isset($rqst) and $rqst->stageid == 2) {


                $subj = "Необходимо проверить и согласовать заявку на материалы №$rqst->id (" . $rqst->inituser->name . ")";
                $msg = "№ " . $rqst->id . " " . $rqst->inituser->name
                    . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                    . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id)
                    . "'>Перейти к заявке</a>";

                //отберем всех активных пользователей с правом на согласование технолога (382), входящих в состав рабочей группы объекта
                $rcpts = User::getFor([
                    'active' => 1,
                    'right_id' => 382,
                    'in_buildobj_staff' => $rqst->buildobjid,
                ], ['u.email']);

                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    //echo("email:$email");
                    if (filter_var($email, FILTER_VALIDATE_EMAIL))
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }

            }

        } elseif ($event->eventtypeid == 'equiprqsts.set_stage_3') {
            // Заявка передана на этап утверждения
            $rqst = equiprqst::find($event->src_objid);
            if (isset($rqst) and $rqst->stageid == 3) {

                //Уведомим инициатора ----------------------------------------------------------------
                $subj = "Заявка №$rqst->id передана на утверждение руководства";
                $msg = "№ " . $rqst->id
                    . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                    . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id)
                    . "'>Перейти к заявке</a>";

                $email = $rqst->inituser->email;
                if (isset($email) and filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }
                //------------------------------------------------------------------------------------


                $subj = "Необходимо проверить и утвердить заявку на материалы №$rqst->id (" . $rqst->inituser->name . ")";
                $msg = "№ " . $rqst->id . " " . $rqst->inituser->name
                    . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                    . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id)
                    . "'>Перейти к заявке</a>";

                //воспользуемся тем, что в obj_approvals есть записи для согласования для каждого этапа, где указаны необходимые права
                $rcpts = User::from('users as u')
                    ->join('usrsysrights as usr', 'usr.userid', 'u.id')
                    ->join('userorgs as uo', 'uo.userid', 'u.id')
                    ->join('obj_approvals as oa', 'oa.dcsn_rightid', 'usr.sysfuncid')
                    ->where('u.active', 1)
                    ->where('usr.active', 1)
                    ->where('uo.active', 1)
                    ->whereRaw('now() between usr.begdt and ifnull(usr.enddt,now())')
                    ->where('oa.sysobjid', 870)
                    ->where('oa.objid', $rqst->id)
                    ->where('oa.stageid', $rqst->stageid)
                    ->whereRaw('now() between uo.begdt and ifnull(uo.enddt,now())')
                    ->whereColumn('uo.orgid', 'oa.dcsn_orgid')
                    ->select('u.lname', 'u.fname', 'u.mname', 'u.email')
                    ->get();

                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    $fio = $rcpt->lname;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info(870, $rqst->id, "Запрос на утверждение направлен $fio на адрес $email", 5);
                    }
                }
            }

        } elseif ($event->eventtypeid == 'equiprqsts.set_stage_4') {
            // Заявка передана на этап уточнения бюджета

            //проверим, что заявка существует и находится на этапе утверждения бюджета
            $rqst = equiprqst::find($event->src_objid);
            if (isset($rqst) and $rqst->stageid == 4) {

                $subj = "Необходимо указать статьи бюджета для финансирования заявки №$rqst->id (" . $rqst->inituser->name . ")";
                $msg = "№ " . $rqst->id . " " . $rqst->inituser->name
                    . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                    . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id)
                    . "'>Перейти к заявке</a>";

                //Нужны пользователи обладающие правом "equiprqsts.equiprqsts.setbdgtacnts" / 399 / Согласование статей бюджета
                //todo: ?переделать с учетом представляемых организаций, как для stage=3
                $rcpts = User::UsersWithRightID(399);

                $rndnum = rand(0, 1000);
                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    $fio = $rcpt->lname;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info(870, $rqst->id, $rndnum . " Запрос на указание бюджета направлен $fio на адрес $email", 5);
                    }
                }
            }
        } elseif ($event->eventtypeid == 'equiprqsts.set_stage_5') {
            // Заявка передана на этап обработки

            $rqst = equiprqst::find($event->src_objid);
            if (isset($rqst) and $rqst->stageid == 5) {

                //Уведомим инициатора
                $subj = "Заявка №$rqst->id передана в отдел снабжения";
                $msg = "№ " . $rqst->id
                    . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                    . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id)
                    . "'>Перейти к заявке</a>";

                $email = $rqst->inituser->email;
                if (isset($email) and filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }


                //Нужно уведомить сотрудников с правом 384 (Обработка заявок)
                // 2020-11-10 - но с учетом что м.б. указана предпочтительная организация-закупщик equiprqsts.pln_suporgid

                $buildopertypename = $rqst->buildopertype->name;
                $buildobjname = $rqst->buildobj->name;

                $subj = "Необходимо сделать заказ по заявке №$rqst->id (" . $rqst->inituser->name . ")";
                $msg = "№ " . $rqst->id . " " . $rqst->inituser->name
                    . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                    . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id)
                    . "'>Перейти к заявке</a>";

                //Нужны пользователи обладающие правом "equiprqsts.process" / 384
                $rcpts = User::UsersWithRightForOrgByCode('equiprqsts.process', $rqst->pln_suporgid);

                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    $fio = $rcpt->lname;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info(870, $rqst->id, "Запрос на обработку заявки направлен $fio на адрес $email", 5);
                    }
                }
            }
        } elseif ($event->eventtypeid == 'equiprqsts.set_stage_11') {
            // Заявка передана на этап Подведения итогов

            $rqst = equiprqst::find($event->src_objid);

            //если заявка действительно находится на этапе 11
            if (isset($rqst) and $rqst->stageid == 11) {

                //Уведомим инициатора
                $subj = "Заявка №$rqst->id передана в бухгалтерию компании-закупщика, для подведения итогов (регистрации доп. затрат)";
                $msg = "№ " . $rqst->id
                    . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                    . "<br>объект: " . $rqst->buildobj->name . ', вид работ: ' . $rqst->buildopertype->name
                    . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id)
                    . "'>Перейти к заявке</a>";

                $email = $rqst->inituser->email;
                if (isset($email) and filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }


                //Нужно уведомить сотрудников с правом 435 (Создание записей в Equiprqst_Expenses)
                //Нужны пользователи обладающие правом "equiprqst_expenses.create" / 435
                $rcpts = User::UsersWithRightCodeInOrg('equiprqst_expenses.create', $rqst->exeorgid);

                if (isset($rcpts) and count($rcpts) > 0) {

                    $subj = "Необходимо подвести итоги по заявке №$rqst->id (" . $rqst->inituser->name . ")";
                    $msg = "№ " . $rqst->id . " " . $rqst->inituser->name
                        . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                        . "<br>объект: " . $rqst->buildobj->name . ', вид работ: ' . $rqst->buildopertype->name
                        . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id)
                        . "'>Перейти к заявке</a>";

                    foreach ($rcpts as $rcpt) {
                        $email = $rcpt->email;
                        $fio = $rcpt->lname;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                            objlog::log_info(870, $rqst->id, "Запрос на обработку заявки направлен $fio на адрес $email", 5);
                        }
                    }
                } else {
                    //нет нужных специалистов - уведомим Инициатора и админиcтраторов

                    $subj = "Нет специалистов обработки заявки №$rqst->id на этапе 'Подведение итогов'";
                    $msg = "№ " . $rqst->id
                        . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                        . "<br>объект: " . $rqst->buildobj->name . ', вид работ: ' . $rqst->buildopertype->name
                        . "<br><br>Сообщите руководителю!"
                        . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id) . "'>Перейти к заявке</a>";

                    //Инициатор
                    $rcpt = User::find($rqst->inituserid);
                    if (isset($rcpt)) {

                        $email = $rcpt->email;
                        $fio = $rcpt->lname;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                            objlog::log_info(870, $rqst->id, "Уведомление об отсутствии специалиста для обработки заявки направлен $fio на адрес $email", 5);
                        }
                    }

                    //Администраторы
                    $rcpts = User::UsersWithRightCode('admin-global');
                    if (isset($rcpts) and count($rcpts) > 0) {
                        foreach ($rcpts as $rcpt) {
                            $email = $rcpt->email;
                            $fio = $rcpt->lname;
                            if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                                dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                                objlog::log_info(870, $rqst->id, "Уведомление об отсутствии специалиста для обработки заявки направлен $fio на адрес $email", 5);
                            }
                        }
                    }
                }
            }

        } elseif ($event->eventtypeid == 'equiprqsts.mngr_decision') {
            //Принято решение по заявке

            $rqst = equiprqst::find($event->src_objid);
            if (isset($rqst)) {

                //Уведомим инициатора
                $subj = "Принято решение по заявке №$rqst->id";
                $msg = "№ " . $rqst->id
                    . "<br>компания: " . $rqst->initorg->name . ' / ' . $rqst->inituser->name
                    . "<br><hr><a href='" . route('equiprqsts.edit', $rqst->id)
                    . "'>Перейти к заявке</a>";

                $email = $rqst->inituser->email;
                if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                    dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }
            }

        } elseif ($event->eventtypeid == 'meeting_staffs.set_decision') {
            //Принято решение по участию в собрании

            // $event->src_objid - содержит id записи об учыастнике

            $rec = meeting_staff::find($event->src_objid);
            if (isset($rec)) {

                //Уведомим инициатора совещания
                $meeting = $rec->meeting;

                $email = $meeting->inituser->email;

                if (isset($email) and filter_var($email, FILTER_VALIDATE_EMAIL)) {

                    $subj = "Принято решение о участии в совещании №$meeting->id ($meeting->descript)";
                    $msg = " " . $rec->orgstaff->name
                        . "<br>решение: " . (($rec->time_accepted == 1) ? "Участвую" : "Не участвую") . ' / ' . $rec->dcsn_descript;

                    if (isset($rec->free_begdt)) {
                        $msg .= '<br><br>Могу в другое время: ' . $rec->free_begdt . ' - ' . $rec->free_enddt;
                    }

                    $msg .= "<br><hr><a href='" . route('meetings.edit', $rec->protid)
                        . "'>Перейти к совещанию</a>";

                    dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }
            }

        } elseif ($event->eventtypeid == 'orgplnpays.restsum_changed') {
            //Введен/Изменился доступный остаток на р/с

            // $event->src_objid - содержит id записи orgplnpays.id

            $orgplnpay = orgplnpay::find($event->src_objid);
            if (isset($orgplnpay)) {

                //Уведомим представителей компании $rec.ownorgid, обладающих правами на размещение

                $subj = 'Изменен остаток средств на р/с ' . $orgplnpay->ownorg->name;
                $msg = "Текущий остаток: " . number_format($orgplnpay->restbegsum, 2)
                    . "<br><br><a href='" . route('orgplnpays.edit', $orgplnpay->id) . "'>Перейти к заявке</a>";

                $rcpts = User::UsersWithRightCodeInOrg('orgplnpay_items.create', $orgplnpay->ownorgid);

                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    $fio = $rcpt->lname;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info($event->src_sysobjid, $event->src_objid, "Уведомление о наличии остатка средств на р/сч направлен $fio на адрес $email", 5);
                    }
                }
            }

        } elseif ($event->eventtypeid == 'orgplnpay_items.pay_agree') {
            //Согласована оплата в плане платежей

            // $event->src_objid - содержит id записи orgplnpay_items.id

            $orgplnpay_item = orgplnpay_item::find($event->src_objid);
            if (isset($orgplnpay_item)) {

                //Уведомим обладателей права на регистрацию оплаты счета
                $eventtypeid = 90201;
                $ref_url = route('orgplnpay_items.regpay', $orgplnpay_item->id);

                $subj = 'Согласована оплата - необходимо провести/зарегистрировать оплату: ' . $orgplnpay_item->reason;
                $msg = "<br><br><a href='{$ref_url}'>Перейти к заявке</a>";
                $notice_msg = "";

                $rcpts = User::UsersWithRightCodeInOrg('orgplnpays.regpay', $orgplnpay_item->doc->ownorgid);
                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    $fio = $rcpt->lname;

                    //добавим в колокольчик
                    user_notice::addOrUpdate($eventtypeid, $ref_url, $rcpt->id, $subj, $notice_msg, now(), null, $event->inituserid);

                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info($event->src_sysobjid, $event->src_objid, "Уведомление о необходимости проведения оплаты направлен $fio на адрес $email", 5);
                    }
                }
            }

        } elseif ($event->eventtypeid == 'invoices.need_make_child_bill') {
            //Требуеся перевыставить счет от своей компании

            $invoice = invoice::find($event->src_objid);
            if (isset($invoice)) {

                //Уведомим пользователей имеющих право 'invoices.make_child_bill' для нужной организации

                $eventtypeid = 91501;
                $ref_url = route('invoices.edit', $invoice->id);
                $subj = 'Необходимо перевыставить счет №' . $invoice->docnum;

                $msg = "Поставщик: " . $invoice->org->name
                    . "<br>Получатель: " . $invoice->ownorg->name
                    . "<br>Примечание: " . $invoice->notes
                    . "<br><br><a href='" . $ref_url . "'>Перейти к документу</a>";

                $plain_msg = "\r\nНеобходимо перевыставить счет №" . $invoice->docnum
                    . "\r\nПоставщик: " . $invoice->org->name
                    . "\r\nПолучатель: " . $invoice->ownorg->name
                    . "\r\nПримечание: " . $invoice->notes
                    . "\r\n\r\nПерейти к документу: {$ref_url}";

                $notice_msg = "";

                $rcpts = User::UsersWithRightCodeInOrg('invoices.make_child_bill', $invoice->ownorgid);

                foreach ($rcpts as $rcpt) {

                    //добавим в колокольчик
                    user_notice::addOrUpdate($eventtypeid, $ref_url, $rcpt->id, $subj, $notice_msg, now(), null, $event->inituserid);

                    //Попробуем отправить сообщение в MyChat - если у пользователя есть/известен UIN
                    myChat::sendMsg($event->src_sysobjid, $event->src_objid, $notice_msg, $rcpt->id);

                    $email = $rcpt->email;
                    $fio = $rcpt->lname;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        //отправим по почте
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info($event->src_sysobjid, $event->src_objid, "Уведомление о необходимости перевыставить счет отправлено $fio на адрес $email", 5);
                    }
                }

            }

        } elseif ($event->eventtypeid == 'invoices.child_bill_maked') {
            //Счет перевыставлен

            // $event->src_objid - передан ИД исходного счета (который требовалось перевыставить)
            $invoice = invoice::find($event->src_objid);
            if (isset($invoice)) {

                //уберем колокольчики о необходимости перевыставить родит. счет  - для всех получателей
                user_notice::Remove(91501, route('invoices.edit', $invoice->id));


                //Уведомим пользователя, последним сохранившего этот счет

                //найдем новый счет (перевыставленный от заданного $event->src_objid)
                $new_invoice = invoice::where('pardocid', $event->src_objid)
                    ->where('doctypeid', 1)->first();

                //если нашли и редактор того счета не равен инициатору этого события (иначе зачем себя же уведомлять)
                //if (isset($new_invoice) and $new_invoice->updated_by<>$event->inituserid) {
                if (isset($new_invoice)) {

                    $eventtypeid = 91502;
                    $ref_url = route('invoices.edit', $new_invoice->id);
                    $subj = 'Перевыставлен счет №' . $invoice->docnum;

                    $msg = "\r\nНовый счет № " . $new_invoice->docnum
                        . "<br>Поставщик: " . $new_invoice->org->name
                        . "<br>Получатель: " . $new_invoice->ownorg->name
                        . "<br>Примечание: " . $new_invoice->notes
                        . "<br><br><a href='" . $ref_url . "'>Перейти к документу</a>";

                    $plain_msg = 'Перевыставлен счет №' . $invoice->docnum
                        . "\r\n\r\nНовый счет № " . $new_invoice->docnum
                        . "\r\nПоставщик: " . $new_invoice->org->name
                        . "\r\nПолучатель: " . $new_invoice->ownorg->name
                        . "\r\nПримечание: " . $new_invoice->notes
                        . "\r\n\r\nПерейти к документу: {$ref_url}";

                    $notice_msg = "";

                    $rcpt = User::find($invoice->updated_by);

                    if (isset($rcpt)) {

                        //добавим в колокольчик
                        user_notice::addOrUpdate($eventtypeid, $ref_url, $rcpt->id, $subj, $notice_msg, now(), null, $event->inituserid);

                        //Попробуем отправить сообщение в MyChat - если у пользователя есть/известен UIN
                        myChat::sendMsg($event->src_sysobjid, $event->src_objid, $notice_msg, $rcpt->id);


                        $email = $rcpt->email;
                        $fio = $rcpt->lname;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                            //отправим по почте
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                            objlog::log_info($event->src_sysobjid, $event->src_objid, "Уведомление о том, что счет перевыставлен отправлено $fio на адрес $email", 5);
                        }
                    }
                }
            }

        } elseif ($event->eventtypeid == 'invoices.need_make_child_upd') {
            //Зарегистрирован УПД к счету, который был/должен быть перевыставлен => возможно требуется перевыставить этот УПД
            // - уведомим пользователей имеющих право на перевыставление

            $invoice = invoice::find($event->src_objid);
            if (isset($invoice)) {

                //Уведомим пользователей имеющих право 'invoices.make_child_bill' для нужной организации

                $eventtypeid = 91503;
                $ref_url = route('invoices.edit', $invoice->id);
                $subj = 'Необходимо перевыставить УПД №' . $invoice->docnum;

                $msg = "Поставщик: " . $invoice->org->name
                    . "<br>Получатель: " . $invoice->ownorg->name
                    . "<br>Примечание: " . $invoice->notes
                    . "<br><br><a href='" . $ref_url . "'>Перейти к документу</a>";

                $plain_msg = "\r\nНеобходимо перевыставить УПД №" . $invoice->docnum
                    . "\r\nПоставщик: " . $invoice->org->name
                    . "\r\nПолучатель: " . $invoice->ownorg->name
                    . "\r\nПримечание: " . $invoice->notes
                    . "\r\n\r\nПерейти к документу: {$ref_url}";

                $notice_msg = "";

                $rcpts = User::UsersWithRightCodeInOrg('invoices.make_child_bill', $invoice->ownorgid);

                foreach ($rcpts as $rcpt) {

                    //добавим в колокольчик
                    user_notice::addOrUpdate($eventtypeid, $ref_url, $rcpt->id, $subj, $notice_msg, now(), null, $event->inituserid);

                    //Попробуем отправить сообщение в MyChat - если у пользователя есть/известен UIN
                    myChat::sendMsg($event->src_sysobjid, $event->src_objid, $notice_msg, $rcpt->id);

                    $email = $rcpt->email;
                    $fio = $rcpt->lname;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        //отправим по почте
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info($event->src_sysobjid, $event->src_objid, "Уведомление о необходимости перевыставить УПД отправлено $fio на адрес $email", 5);
                    }
                }

            }

            //Требуеся перевыставить счет от своей компании

            $invoice = invoice::find($event->src_objid);
            if (isset($invoice)) {

                //Уведомим пользователей имеющих право 'invoices.make_child_bill' для нужной организации

                $eventtypeid = 91501;
                $ref_url = route('invoices.edit', $invoice->id);
                $subj = 'Необходимо перевыставить счет №' . $invoice->docnum;

                $msg = "Поставщик: " . $invoice->org->name
                    . "<br>Получатель: " . $invoice->ownorg->name
                    . "<br>Примечание: " . $invoice->notes
                    . "<br><br><a href='" . $ref_url . "'>Перейти к документу</a>";

                $plain_msg = "\r\nНеобходимо перевыставить счет №" . $invoice->docnum
                    . "\r\nПоставщик: " . $invoice->org->name
                    . "\r\nПолучатель: " . $invoice->ownorg->name
                    . "\r\nПримечание: " . $invoice->notes
                    . "\r\n\r\nПерейти к документу: {$ref_url}";

                $notice_msg = "";

                $rcpts = User::UsersWithRightCodeInOrg('invoices.make_child_bill', $invoice->ownorgid);

                foreach ($rcpts as $rcpt) {

                    //добавим в колокольчик
                    user_notice::addOrUpdate($eventtypeid, $ref_url, $rcpt->id, $subj, $notice_msg, now(), null, $event->inituserid);

                    //Попробуем отправить сообщение в MyChat - если у пользователя есть/известен UIN
                    myChat::sendMsg($event->src_sysobjid, $event->src_objid, $notice_msg, $rcpt->id);

                    $email = $rcpt->email;
                    $fio = $rcpt->lname;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        //отправим по почте
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info($event->src_sysobjid, $event->src_objid, "Уведомление о необходимости перевыставить счет отправлено $fio на адрес $email", 5);
                    }
                }

            }


        } elseif ($event->eventtypeid == 'invoices.items_no_refitmid') {
            //Зарегистрирован счет, который должен иметь состав со всеми позициями "в Номенклатуре"
            // - уведомим пользователей имеющих право "equiprqsts.add2refitems"

            $invoice = invoice::find($event->src_objid);
            if (isset($invoice)) {

                //Уведомим пользователей имеющих право 'equiprqsts.add2refitems' для нужной организации (?)

                $eventtypeid = 91506;
                $ref_url = route('invoices.edit', $invoice->id);
                $subj = "В счете №{$invoice->docnum} все позиции состава должны быть связаны с Номенклатурой";

                $msg = "Поставщик: " . $invoice->org->name
                    . "<br>Получатель: " . $invoice->ownorg->name
                    . "<br>Примечание: " . $invoice->notes
                    . "<br><br><a href='" . $ref_url . "'>Перейти к документу</a>";

                $plain_msg = "\r\nНеобходимо все позиции счета {$invoice->docnum} связать с Номенклатурой №"
                    . "\r\nПоставщик: " . $invoice->org->name
                    . "\r\nПолучатель: " . $invoice->ownorg->name
                    . "\r\nПримечание: " . $invoice->notes
                    . "\r\n\r\nПерейти к документу: {$ref_url}";

                $notice_msg = "";

                //$rcpts = User::UsersWithRightCodeInOrg('refitems.operator', $invoice->ownorgid);
                //$rcpts = User::UsersWithRightCode('refitems.operator');
                $rcpts = User::getFor([
                    'active' => 1,
                    'right_code' => 'equiprqsts.add2refitems'],
                    ['id', 'email', 'lname']);
                //dd($rcpts);

                foreach ($rcpts as $rcpt) {

                    //добавим в колокольчик
                    user_notice::addOrUpdate($eventtypeid, $ref_url, $rcpt->id, $subj, $notice_msg, now(), null, $event->inituserid);
                    //user_notice::addOrUpdate($eventtypeid, $event->src_sysobjid, $event->src_objid, $ref_url, $rcpt->id, $subj, $notice_msg, now(), null, $event->inituserid);

                    //Попробуем отправить сообщение в MyChat - если у пользователя есть/известен UIN
                    myChat::sendMsg($event->src_sysobjid, $event->src_objid, $notice_msg, $rcpt->id);

                    $email = $rcpt->email;
                    $fio = $rcpt->lname;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        //отправим по почте
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info($event->src_sysobjid, $event->src_objid, "Уведомление о необходимости связывания позиций счета с Номенклатурой отправлено $fio на адрес $email", 5);
                    }
                }

            }


        } elseif ($event->eventtypeid == 'org_extservices.low_sum') {
            //событие о низком остатке оплаты у провайдера
            //event(new notifyEvent('extsrvc_sums.low_sum', self::$sysobjid, $rec->id, 0));


            $org_extservice = org_extservice::find($event->src_objid);
            if (isset($org_extservice)) {
                //Уведомим пользователей имеющих право 'org_extservices.make_pay' для нужной организации

                $eventtypeid = 981001;
                $ref_url = route('org_extservices.edit', $event->src_objid);
                $subj = 'Необходимо пополнить аванс на субсчете у поставщика';

                $msg = "<br><br><a href='" . $ref_url . "'>Перейти к документу</a>";

                $plain_msg = "\r\n{$subj}" . "\r\n\r\nПерейти к документу: {$ref_url}";

                $notice_msg = "";

                $rcpts = User::UsersWithRightCodeInOrg('org_extservices.make_pay', $org_extservice->orgid);

                foreach ($rcpts as $rcpt) {

                    //добавим в колокольчик
                    user_notice::addOrUpdate($eventtypeid, $ref_url, $rcpt->id, $subj, $notice_msg, now(), null, $event->inituserid);

                    //Попробуем отправить сообщение в MyChat - если у пользователя есть/известен UIN
                    myChat::sendMsg($event->src_sysobjid, $event->src_objid, $notice_msg, $rcpt->id, $event->inituserid);

                    $email = $rcpt->email;
                    $fio = $rcpt->lname;
                    if (filter_var($email, FILTER_VALIDATE_EMAIL)) {
                        //отправим по почте
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                        objlog::log_info($event->src_sysobjid, $event->src_objid, "Уведомление о необходимости пополнить аванс на субсчете у поставщика отправлено $fio на адрес $email", 5);
                    }
                }
            }

        }


        if ($event->eventtypeid == 101) {
            //101 - Новый заказ.
            // Требуется:
            //  - выгрузить в XML все заказы не имеющие отметки о наличии их в 1С
            //  - уведомить сотрудников с правом 204/orders.approve - о необходимости согласовать новый заказ
            //  - уведомить кураторов клиента для этой организации о размещении нового заказа (userorgs.curator=1)

            if ($event->src_sysobjid == 131) {
                $order = order::find($event->src_objid);
                if (isset($order)) {

                    //  Выгрузить все заказы, неизвестные в учетной системе (1С)
                    $extsysid = 5; //todo: взять из настройки
                    order::export_neworders_xml($order->ownorgid, $extsysid, $event->inituserid);


                    //  Сотрудники с правом согласования
                    $subj = "Необходимо согласовать новый заказ - (" . $order->org->name . ")";
                    $msg = "№ " . $order->ordnum . " " . $order->org->name
                        . "<br><hr><a href='" . route('orders.edit', $order->id)
                        . "'>Перейти к заказу</a>";

                    $rcpts = User::UsersWithRightID(204);
                    foreach ($rcpts as $rcpt) {
                        $email = $rcpt->email;
                        echo("email:$email");
                        if (filter_var($email, FILTER_VALIDATE_EMAIL))
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                    }


// Пользователи, курирующие данного контрагента
                    $subj = "В курируемой Вами организации размешен новый заказ ($order->ordnum)";
                    $msg = "№ " . $order->ordnum . " " . $order->org->name
                        . "<br><hr><a href='" . route('client.orders.edit', $order->id)
                        . "'>Перейти к заказу</a>";

                    $rcpts = User::ClientCuratorsForOrgID($order->orgid);
                    foreach ($rcpts as $rcpt) {
                        $email = $rcpt->email;
                        if (filter_var($email, FILTER_VALIDATE_EMAIL))
                            dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                    }
                }
            }


        } elseif
        ($event->eventtypeid == 102) {
            // 102 - Экспортированы новые/измененные заказы для передачи в 1с

            //  Сотрудники с правом согласования
            $subj = "Сформирован XML-файл с новыми/измененными заказами для загрузки в 1с";
            $msg = $event->msg . "\n";
            $attach = $event->subj; //временно

            $rcpts = User::UsersWithRightID(204);

            foreach ($rcpts as $rcpt) {
                $email = $rcpt->email;
                echo("email:$email");
                if (filter_var($email, FILTER_VALIDATE_EMAIL))
                    dispatch((new SendNotify($email, $subj, $msg, $attach))->onQueue('default'));
            }

        } elseif ($event->eventtypeid == 'tasks.taked') {
            // Задача взята в работу
            $task = task::find($event->src_objid);
            if (isset($task)) {

                $executor_name = $task->executor->name;
                $begdt = date_create($task->fctbegdt)->format('d.m.Y H:i');

                $subj = "Задача взята в работу ({$task->name})";
                $msg = "{$executor_name}: {$begdt}";

                //отберем всех активных пользователей с правом на согласование технолога (382), входящих в состав рабочей группы объекта
                $rcpts = User::from('users as u')
                    ->whereIn('id', [$task->inituserid, $task->created_by])
                    ->select('email')
                    ->get();

                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    //echo("email:$email");
                    if (filter_var($email, FILTER_VALIDATE_EMAIL))
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }
            }

        } elseif ($event->eventtypeid == 'tasks.complete') {
            // Задача выполнена
            $task = task::find($event->src_objid);
            if (isset($task)) {

                $executor_name = $task->executor->name;
                $enddt = date_create($task->fctenddt)->format('d.m.Y H:i');

                $subj = "Задача выполнена ({$task->name})";
                $msg = "{$executor_name}: {$enddt}";

                //отберем всех активных пользователей, указанных как инициатор или создатель задачи
                //также уведомим пользователей, с заданной ролью "Инициатор" или "Куратор"
                $first = User::from('users as u')
                    ->whereIn('id', [$task->inituserid, $task->created_by])
                    ->where('Active',1)
                    ->select('email');

                $rcpts = User::from('users as u')
                    ->whereRaw('exists(select 1 from task_users as tu where tu.taskid=' . $task->id . ' and tu.userid = u.id and tu.RoleTypeid in (1,8))')
                    ->where('Active',1)
                    ->select('email')
                    ->union($first)
                    ->get();

                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    //echo("email:$email");
                    if (filter_var($email, FILTER_VALIDATE_EMAIL))
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }

            }

        } elseif ($event->eventtypeid == 'task_reports.prepared') {
            // Подготовлен отчет по выполнению задачи
            $rep = task_report::find($event->src_objid);
            if (isset($rep)) {

                $task = task::find($rep->taskid);
                $reporter_name = $rep->user->name;

                $subj = "Есть отчет по выполнению задачи ({$task->name})";
                $msg = "{$reporter_name}: {$rep->report}";

                //отберем всех активных пользователей, указанных как инициатор или создатель задачи
                //также уведомим пользователей, с заданной ролью "Инициатор" или "Куратор"
                $first = User::from('users as u')
                    ->whereIn('id', [$task->inituserid, $task->created_by])
                    ->where('Active',1)
                    ->select('email');

                $rcpts = User::from('users as u')
                    ->whereRaw('exists(select 1 from task_users as tu where tu.taskid=' . $task->id . ' and tu.userid = u.id and tu.RoleTypeid in (1,8))')
                    ->where('Active',1)
                    ->select('email')
                    ->union($first)
                    ->get();

                //User::from('users as u')->whereIn([12,25])->select('email')->get();
                foreach ($rcpts as $rcpt) {
                    $email = $rcpt->email;
                    //echo("email:$email");
                    if (filter_var($email, FILTER_VALIDATE_EMAIL))
                        dispatch((new SendNotify($email, $subj, $msg))->onQueue('high'));
                }
            }

        }
    }
}
