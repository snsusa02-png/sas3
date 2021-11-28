<?php
//Парсер/загрузчик оплат заказов из 1С в ЛК

namespace App\Services\Parsers;

use App\extsys_sysobj;
//use App\Jobs\Parse1CDocs;
use App\Jobs\Parse1COrgs;
use App\Events\docs1CLoadedEvent;

use App\extsystem;
use App\objflag;
use App\objpref;
use App\order;
use App\orditem;
use App\ordpay;
use App\org;
use App\objextid;
use App\orgdog;
use App\paydoc;
use App\pd_link;
use App\refitem;
use App\sysobj;

use App\unittype;
use App\User;
use DB;
use Cache;

use App\Services\Readers;
use App\Services;
use App\Services\ParserLogger;

use App\Traits\Result;
use App\Traits\StringUtil;
use DateTime;


class pays1CInterface implements impFileParser
{

    public function __construct()
    {
//        $this->needActivate = false;
        $this->userid = null; //в setting будет установлен через переданное значение
    }

    public function setting($param, \App\Services\ParserLogger $log)
    {
        $this->log = $log;

//        $this->log->info(var_export($param, false));
//
        foreach ($param as $val) {
//            if ($val['preftypeid'] == "33") {
//                if ($val['prefvalue'] == "1") {
//                    $this->needActivate = true;
//                }
//                $this->log->info("Установлен параметр: " . $val['name'] . "=" . ($this->needActivate ? 'true' : 'false'));
//            } else if ($val['preftypeid'] == "user") {

            if ($val['preftypeid'] == "user") {
                if (is_numeric($val['prefvalue'])) {
                    $this->userid = intval($val['prefvalue']);
                }
            }
        }
        info('parser pays1CInterface: userid: ' . $this->userid);

    }

    public function doit(\App\Services\Readers\impFileReader $reader)
    {
        //dd($reader->filename);
        $res = new Result();

        $res = $this->load_1sdoc_xml_job($reader->filename);

        return $res;
    }


    public function load_1sdoc_xml_job($filename)
    {
        //Вариант для запуск Job-ом. Быстрее в несколько раз, не зависит от тайм-аута веб-сервера

        $res = new Result();
        $resMsg = '';

        if (isset($filename)) {

            $lf = '<br>';

            $datetime0 = new DateTime("now"); //Начальный момент времени - для расчета времени в цикле
            $resMsg .= $lf . now() . ' - начало обработки';

            $minDocDate = date_create_from_format('Y-m-d', '2900-01-01');
            $maxDocDate = date_create_from_format('Y-m-d', '1900-01-01');
            //dd('test'.$minDocDate->format('Y-m-d'));
            //Занесем в системный журнал:
            info("Начат парсинг файла с оплатами заказов из 1С: " . $filename);

            $userid = $this->userid;
            info("userid: " . $userid);

            $xml = simplexml_load_file($filename);

            $refModel = 'Платежи';

            $dataModel = $xml['Модель'];
            if (is_null($dataModel)) {
                $this->log->fatalerror("Не задана модель данных! Ожидалось '$refModel'. Импорт остановлен.");
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . 'Не задана модель данных! Ожидалось "' . $refModel . '".';
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($this->userid, $resMsg));
                return;
            }
            if (strtolower($dataModel) <> strtolower($refModel)) {
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . 'Задана неизвестная модель данных: "' . $dataModel . '"! Ожидалось "' . $refModel . '".';
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($this->userid, $resMsg));
                return;
            }
            $dataModelVersion = $xml['Версия'];
            info('parser: Версия модели данных: ' . $dataModelVersion);
            $this->log->info('модель данных: "' . $dataModel . '", версия ' . $dataModelVersion);

            $extsyscode = $xml['КодСистемы'];
            if (is_null($extsyscode)) {
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . 'Не задана внешняя система! Вероятно неподходящий файл.';
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($this->userid, $resMsg));
                return;
            }

            $resMsg .= $lf . now() . " - Внешняя система: $extsyscode";
            $this->log->info("Внешняя система: " . $extsyscode);

            $extsys = extsystem::where('code', $extsyscode)->select('id')->first();
            $extsysid = isset($extsys) ? $extsys->id : null;
            if (!isset($extsysid)) {
                if (1 == 0) {
                    //Добавим новую внешнюю систему
                    $extsys = new extsystem([
                        "code" => $extsyscode,
                        "name" => $xml['ВнешняяСистема'],
                        "active" => 1,
                        "created_by" => $userid,
                        "created_at" => now(),
                        "updated_by" => $userid,
                        "updated_at" => now()]);
                    $extsys->save();
                    $extsysid = $extsys->id;

                    //добавим потребителей данных, которые могут быть импортированы в данном типе загрузки:
                    //Товары, Клиенты, Склады
                    $recs = [
                        ['extsysid' => $extsysid, 'sysobjid' => 105],
                        ['extsysid' => $extsysid, 'sysobjid' => 111],
                        ['extsysid' => $extsysid, 'sysobjid' => 202],
                    ];
                    extsys_sysobj::insert($recs);
                }
            }
            if (!isset($extsysid)) {
                $res->err = 1;
                $res->msg = 'Неизвестная внешняя система: "' . $extsyscode . '"! Импорт отменен.';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;

                throw new \Exception($res->msg);
            }


            $ownorg_extid = $xml['ОрганизацияКод'];
            $ownorgid = objextid::objid_by_extsysid_extid($extsysid, 111, $ownorg_extid);
            if (!isset($ownorgid) and 1 == 1) {
                //Добавим запись о новой организации-владельце по коду во внешней системе
                $ownorgname = $xml['ОрганизацияНаименование'];
                $ownorgid = org::newOrgByExtID($extsysid, $ownorg_extid, $ownorgname, $userid);

                if (isset($ownorgid)) {
                    $this->log->info(" новый Продавец: $ownorgname");

                    //Добавим признак организации-продавца - флаг 12
                    objflag::AddObjFlag(111, $ownorgid, 12, $userid);
                }
                if (!isset($ownorgid)) {
                    throw new \Exception('Не удалось добавить запись о новой организации-продавце "'
                        . $ownorgname . '" код' . $ownorg_extid . '! Импорт отменен.');
                }
            }

            //настройки на версию модели ----------------------
            $dateFormat = 'Y-m-d';
            if ($dataModelVersion == '1.0') {
                $dateFormat = 'Y-m-d';
                $datetimeFormat = 'Y-m-d H:i:s';
            } elseif ($dataModelVersion == '1.1') {
                $dateFormat = 'd.m.Y';
                $datetimeFormat = 'd.m.Y H:i:s';
            }
            //------------------------------------------------

            $ins = 0;
            $upd = 0;
            $skp = 0;
            $parseCnt = 0;
            $newOrgs = 0;
            $pay_wo_order_cnt = 0;

            $totDocCnt = count($xml->Платежи->Регистратор);
            $resMsg .= $lf . now() . ' - Всего документов: ' . $totDocCnt;
            $this->log->info('всего документов: ' . $totDocCnt);

            // сформируем массив типов источников оплаты, чтобы не дергать каждый раз базу ------
            $pd_srctypes = objextid::where([['extsysid', $extsysid], ['sysobjid', 510]])
                ->select('extid', 'objid')->get()->pluck('objid', 'extid')->toArray();
            //log(var_dump($pd_srctypes));
            // ----------------------------------------------------------------------------------
            // сформируем массив форм оплаты, чтобы не дергать каждый раз базу ------------------
            $payforms = objextid::where([['extsysid', $extsysid], ['sysobjid', 402]])
                ->select('extid', 'objid')->get()->pluck('objid', 'extid')->toArray();
            //log(var_dump($payforms));

//            \Log::debug("--------000000111--------:" . $payforms['000000111']);
//            \Log::debug("--------000000112--------:" . $payforms['000000112']);
//            \Log::debug("--------000000115--------:" . $payforms['000000115']);
            // ----------------------------------------------------------------------------------

            foreach ($xml->Платежи->Регистратор as $doc) {
                try {
                    DB::beginTransaction();

                    //\Log::debug($doc['НомерДокумента']);

                    //$docdate = date_create_from_format($datetimeFormat, $doc['РегистраторДата']);

                    $strDate = trim($doc['РегистраторДата']);
                    $strDate = (strlen($strDate) < 17) ? $strDate . ' 00:00:00' : $strDate;
                    $docdate = date_create_from_format($datetimeFormat, $strDate);

                    $docsum = StringUtil::to_number($doc['РегистраторСуммаОбщая']
                            ?? $doc['РегистраторСумма']
                            ?? $doc['РегистраторСуммаВозврата'])
                        ?? 0;
                    $freesum = $docsum; //сумма несвязанного остатка

                    $minDocDate = ($docdate < $minDocDate) ? $docdate : $minDocDate;
                    $maxDocDate = ($docdate > $maxDocDate) ? $docdate : $maxDocDate;
//                    dd($docdate, $minDocDate,$maxDocDate);

                    $extdocid = $doc['РегистраторНомер']; //может быть использовать для хранения кода
                    //$this->log->info("РегистраторНомер: " . $doc['РегистраторНомер'] . ' сумма=' . $docsum);
                    //\Log::debug("РегистраторНомер: $extdocid");

                    $payreason = $doc['РегистраторВозвращаемаяОплата']; //


                    $payNo = 0; //номер платежа. Будем использовать для обновления PayDocs только при первом проходе
                    foreach ($doc->Платеж as $pay) {

                        $payNo++;

                        $orgid = null;

                        $orgextid = trim($pay['КлиентКод']);
                        $orgextid = ($orgextid == '') ? trim($pay['ЧекКлиентКод']) : $orgextid;
                        //$this->log->info("КлиентКод: " . $pay['КлиентКод'] . ", ЧекКлиентКод: " . $pay['ЧекКлиентКод'] . '  => ' . $orgextid);

                        $ordnum = $pay['НомерЗаказа'] ?? $pay['НомерБыстраяПродажа'];
                        //\Log::debug("связанный документ: $ordnum");

                        if (is_null($orgextid) or !isset($orgextid) or empty($orgextid)) {

                            //нет прямого указания контрагента, попытаемся взять контрагента из заказа
                            //Ищем документ по номеру - в пределах документов Этого Продавца
                            $ord = order::where('ordnum', $ordnum)
                                ->where('ownorgid', $ownorgid)
                                ->first();

                            if (isset($ord)) {
                                $orgid = $ord->orgid;

                            } else {

                                $skp++;
                                $msg = $skp . ' В документе №' . $extdocid . ' не задан код клиента (атрибут "КлиентКод / ЧекКлиентКод").'
                                    . ' И не удалось взять контрагента из заказа "' . $ordnum . '".'
                                    . ' Документ не загружен! ';
                                $this->log->error($msg);
                                continue;
                                //throw new \Exception($msg);
                            }
                        } else
                            $orgid = objextid::objid_by_extsysid_extid($extsysid, 111, $orgextid);

                        //dd($orgid);

                        if (!isset($orgid) and 1 == 1 and isset($doc['КонтрагентНаименование'])) {
                            //Добавим запись о новой организации по коду во внешней системе
                            $orgname = $doc['Клиент'] ?? $doc['ЧекКлиент'];
//                        $this->log->info("Неизвестная организация, пробуем добавить: "
//                            . $extsysid . ': ' . $orgextid . ': "' . $orgname
//                        );
                            $orgid = org::newOrgByExtID($extsysid, $orgextid, $orgname, $userid);

                            if (isset($orgid)) {
                                $newOrgs++;
                                $this->log->info(" новый клиент: $orgname");

                                //Добавим связку в OrgDogs
                                orgdog::add_orgdog_low($ownorgid, $orgid, $docdate);

                            } //else $this->log->error(" - ошибка при добавлении!", null, null);
                        }

                        if (isset($orgid)) {

                            //Сохраним запись о платеже в глобальный реестр платежей - DocPays

                            //РегистраторВид="Чек"
                            //РегистраторВид="ОплатаЗаказа"
                            //РегистраторВид="ОплатаСчета"
                            //РегистраторВид="ВозвратОплатыЗаказа"
                            $objextid = trim($doc['РегистраторВид']);

                            //$srctypeid = objextid::objid_by_extsysid_extid($extsysid, 510, $objextid);
                            //\Log::debug("внешний код источника оплаты: $objextid");
                            $srctypeid = $pd_srctypes[$objextid] ?? null;
                            //\Log::debug("код источника оплаты: $srctypeid");

                            if (!isset($srctypeid)) {
                                $res->err = 1;
                                $res->msg = 'Неизвестный источник платежа: "' . $objextid . '"! Импорт отменен.';
                                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;

                                throw new \Exception($res->msg);
                            }


                            //определим форму оплаты и направление платежа------------------------------------------
                            $paydir = $pay['PAYDIR'] ?? 1;
                            //\Log::debug("paydir= $paydir");

                            $payformid = 1; //Временно?

                            if (1 == 1) {
                                $extid = $doc['РегистраторСпособОплатыКод'];
                                if (isset($extid)) {
                                    //формат "Быстрая продажа" => всегда приход

                                    $payformid = objextid::objid_by_extsysid_extid($extsysid, 402, $extid);

                                    //$payformid = $payforms[(string)$extid] ?? null;   //!!! НЕ РАБОТАЕТ !?

                                    if (!isset($payformid)) {
                                        $res->err = 1;
                                        $res->msg = 'Неизвестный код оплаты: "' . $extid . '"! Импорт отменен.';
                                        \Log::debug($res->msg);

                                        throw new \Exception($res->msg);
                                    }

                                } elseif (isset($doc['РегистраторБезнал'])) {
                                    //формат для заказа
                                    //РегистраторВозврат="Нет" РегистраторБезнал="Нет"
                                    $payformid = ($doc['РегистраторБезнал'] == 'Да') ? 11 : 1;

                                } elseif ($doc['РегистраторВид'] == 'ВозвратОплатыЗаказа')
                                    //В данных нет формы оплаты - считаем, что "нал"
                                    $payformid = 1;

                                else
                                    $payformid = 1;
                                //dd($payformid,$paydir);
                            }
                            //--------------------------------------------------------------------------------------

                            $paysum = StringUtil::to_number($pay['СуммаОплаты'] ?? 0);
                            //\Log::debug("paysum= $paysum");

                            if ($payNo == 1) {
                                $paydoc = paydoc::where('srctypeid', $srctypeid)
                                    ->where('srcextid', $extdocid)
                                    ->first();
                                if (!isset($paydoc)) {
                                    $paydoc = new paydoc([
                                        'srctypeid' => $srctypeid,
                                        'srcextid' => $extdocid,
                                    ]);
                                }

                                $paydoc->ownorgid = $ownorgid;
                                $paydoc->orgid = $orgid;
                                $paydoc->docnum = $extdocid;
                                $paydoc->docdate = $docdate;
                                $paydoc->paydate = $docdate;
                                $paydoc->paysum = $docsum;
                                $paydoc->paytax = null;
                                $paydoc->paydir = $paydir;
                                $paydoc->payformid = $payformid;
                                $paydoc->crncy = 'RUB';
                                $paydoc->crncyrate = 1;
                                $paydoc->crncydiv = 1;
                                $paydoc->crncysum = $docsum;
                                $paydoc->rubsum = $docsum;
                                $paydoc->advpay = 1;
                                $paydoc->stable = 1;
                                $paydoc->payreason = $doc['РегистраторВозвращаемаяОплата'] ?? 'заказ ' . $ordnum;
                                $paydoc->reguserid = 1; //от имени системы

                                $paydoc->save();
                                $paydocid = $paydoc->id;

                                //добавим/обновим свободный остаток платежа ---
                                $pd_link_freesum = pd_link::where('docid', $paydocid)
                                    ->whereNull('objid')->first();
                                if (!isset($pd_link_freesum)) {
                                    $pd_link_freesum = new pd_link([
                                        'docid' => $paydocid,
                                        'sysobjid' => null,
                                        'objid' => null,
                                    ]);
                                }
                                $pd_link_freesum->partsum = $freesum;
                                $pd_link_freesum->parttax = null;
                                $pd_link_freesum->save();
                                //-----------------------------------------
                                //------------------------------------------------------------------
                            }

                            //Ищем документ по номеру - в пределах документов Этого Контрагента и Продавца
                            $ord = order::where([['ordnum', $ordnum], ['ownorgid', $ownorgid]])
                                //->where('orgid', $orgid) //приводим оплату в заказ вне зависимости
                                // от несовпадения клиента в заказе и плательщика
                                ->first();

                            if (isset($ord)) {
                                //Заказ нашелся


                                //Поиск записи об оплате заказа
                                // Предполагаем, что в один заказ может попасть оплата
                                // только от одного документа "Регистратор".
                                //Иначе говоря - один "Регистратор" если содержит несколько платежей, то ВСЕГДА для разных заказов
                                //Иначе все плохо - следующий платеж для этого же заказа затрет предыдущий.
                                $ordpay = ordpay::where([['ordid', $ord->id], ['extdocid', $extdocid]])
                                    ->first();

                                if (!isset($ordpay)) {
                                    $ordpay = new ordpay([
                                        'ordid' => $ord->id,
                                        'extdocid' => $extdocid,
                                    ]);
                                    $ins++;
                                } else $upd++;

                                $ordpay->paydocid = $paydocid;
                                $ordpay->ownorgid = $ownorgid;
                                $ordpay->orgid = $orgid;
                                $ordpay->docnum = $extdocid;
                                $ordpay->docdate = $docdate;
                                $ordpay->payformid = $payformid;
                                $ordpay->paydir = $paydir;

                                $ordpay->paysum = $paysum;
                                $ordpay->save();

                                //добавим связь между ordpays и paydocs ----------------------------------
                                $pd_link = pd_link::where([['docid', $paydocid], ['sysobjid', 135], ['objid', $ordpay->id]])
                                    ->first();
                                if (!isset($pd_link)) {
                                    $pd_link = new pd_link([
                                        'docid' => $paydocid,
                                        'sysobjid' => 135,
                                        'objid' => $ordpay->id,
                                    ]);
                                }

                                $pd_link->partsum = $paysum;
                                $pd_link->parttax = null;

                                $pd_link->save();

                                //уменьшим свободный остаток
                                $freesum -= $paysum;
                                $pd_link_freesum->partsum = $freesum;
                                $pd_link_freesum->save();
                                //------------------------------------------------------------------------

                                // обновим общую сумму оплаты заказа -------------
//                                $ord->totpaysum = ordpay::where('ordid', $ord->id)
//                                        ->selectraw('sum(paydir*paysum) as paysum')
//                                        ->first()->paysum ?? 0;
//                                $ord->save();
                                //------------------------------------------------

                            } else {
                                $pay_wo_order_cnt++;
                                if ($ordnum != '')
                                    $this->log->warning("paydocid:$paydocid - Не найден заказ № $ordnum", null, null);
                            }
                        } else {
                            $skp++;
                            $this->log->error("Не найдено соответствие для контрагента "
                                . $pay['КлиентКод'] . ': ' . $pay['Клиент']
                                , null, null);
                        }
                    }
                } catch
                (\Exception $e) {
                    DB::rollback();
                    \Log::debug('------Exception---------');
                    \Log::debug($e->getMessage());
                    \Log::debug($e->getTraceAsString());

                    $res->err = 1;
                    $res->msg = $e->getFile() . '(' . $e->getLine() . '): ' . $e->getMessage();
                    $resMsg .= $lf . now() . ' - ОШИБКА: ' . $e->getMessage();
                    $this->log->fatalerror(' ОШИБКА: ' . $res->msg);
                    break;

                } finally {
                    //\Log::debug('------finally---------');

                    DB::commit();   //для отображения записей через  $this->log->info/error/...
                }

                $parseCnt++;
                if ($parseCnt % 1000 == 0) {
                    info('parser pays1CInterface: ' . $parseCnt . ' - processed, ' . ($totDocCnt - $parseCnt) . ' - remained');
                    $this->log->info(" документов обработано: $parseCnt; осталось: " . ($totDocCnt - $parseCnt) . " " . round(($totDocCnt - $parseCnt) / $totDocCnt * 100, 1) . "%");

                    $interval = $datetime0->diff(new DateTime('now'));
                    //$this->log->info($interval->format('%R%Y лет, %R%M месяцев, %R%D дней, %R%H часов, %R%I минут, %R%S секунд'));
                    info($interval->format('время обработки: %R%H часов, %R%I минут, %R%S секунд'));
                }
            }

            //\Log::debug('update orders as o set o.totpaysum=ifnull((select sum(p.paydir*p.paysum) from ordpays as p where p.ordid=o.id),0)');
            //пересчитаем суммы оплат по всем заказам:
            //DB::raw('update orders as o set o.totpaysum=ifnull((select sum(p.paydir*p.paysum) from ordpays as p where p.ordid=o.id),0)');

            $this->log->info("Расчет сумм оплат заказов ...");
            order::from('orders as o')->update(['o.totpaysum'
            => DB::raw('ifnull((select sum(p.paydir*p.paysum) from ordpays as p where p.ordid=o.id),0)')]);

            $this->log->info("Обработка завершена.");

//            $res->msg = "Добавлено документов: $ins; обновлено: $upd; пропущено: $skp;";
            //$this->log->info($res->msg);


            $resMsg .= $lf . now() . ' - обработка завершена: '
                . "Добавлено документов: $ins; обновлено: $upd; пропущено: $skp; платежей без заказов: $pay_wo_order_cnt;";
            $resMsg .= $lf . '       период документов: ' . $minDocDate->format("Y-m-d") . ' - ' . $maxDocDate->format("Y-m-d");
            $res->msg = "Обработка завершена: Добавлено документов: $ins; обновлено: $upd; пропущено: $skp; платежей без заказов: $pay_wo_order_cnt."
                . " Новых контрагентов: $newOrgs.";
            //$this->log->info($res->msg);

            //Запустим событие об окончании обработки файла
            event(new docs1CLoadedEvent($this->userid, $resMsg));
            //dispatch((new Send1CDocsLoaded($user, $resMsg))->onQueue('high'));
        }

        return $res;
    }

    private function SetSysObj()
    {
        $rec = sysobj::where('code', 'FinDocs')->first();
        if (isset($rec)) {
            $this->log->setSysObj($rec->id);
        }
    }

}

?>
