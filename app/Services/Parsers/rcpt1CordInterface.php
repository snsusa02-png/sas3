<?php
//Парсер/загрузчик заказов из 1С в ЛК

namespace App\Services\Parsers;

use App\extsys_sysobj;
//use App\Jobs\Parse1CDocs;
use App\Jobs\Parse1COrgs;
use App\Events\docs1CLoadedEvent;

use App\extsystem;
use App\objextsysrcpt;
use App\objflag;
use App\objlog;
use App\order;
use App\org;
use App\objextid;

use App\User;
use DB;
use Cache;

use App\Services\Readers;
use App\Services;
use App\Services\ParserLogger;

use App\Traits\Result;
use App\Traits\StringUtil;


class rcpt1CordInterface implements impFileParser
{

    public function __construct()
    {
        $this->userid = null; //в setting будет установлен через переданное значение
    }

    public function setting($param, \App\Services\ParserLogger $log)
    {
        $this->log = $log;

        foreach ($param as $val) {

            if ($val['preftypeid'] == "user") {
                if (is_numeric($val['prefvalue'])) {
                    $this->userid = intval($val['prefvalue']);
                }
            }
        }
        info('parser rcpt1CordInterface: userid: ' . $this->userid);

    }

    public function doit(\App\Services\Readers\impFileReader $reader)
    {
        //dd($reader->filename);
        info("rcpt1CordInterface->doit $reader->filename");

        $res = new Result();

        $res = $this->parse($reader->filename);

        return $res;
    }


    public function parse($filename)
    {
        // --- НАСТРОЙКИ ----------------------------------------------------

        $refModel = 'КвитанцииЗагрузкиЗаказов'; //

        // ------------------------------------------------------------------


        $res = new Result();
        $resMsg = '';

        if (isset($filename)) {

            $lf = '<br>';

            $resMsg .= $lf . now() . ' - начало обработки';

            $minDocDate = date_create_from_format('Y-m-d', '2900-01-01');
            $maxDocDate = date_create_from_format('Y-m-d', '1900-01-01');
            //dd('test'.$minDocDate->format('Y-m-d'));
            //Занесем в системный журнал:
            info("Начат парсинг файла с квитанциями о импорте заказов в 1С: " . $filename);

            $userid = $this->userid;
            info("userid: " . $userid);

            $xml = simplexml_load_file($filename);

            $dataModel = $xml['Модель'];
            if (is_null($dataModel)) {
                $res->err = 1;
                $res->msg = 'Не задана модель данных! Ожидалось "' . $refModel . '".';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;

                throw new \Exception($res->msg);
            }

            if (strtolower($dataModel) <> strtolower($refModel)) {
                $res->err = 1;
                $res->msg = 'Задана неизвестная модель данных: "' . $dataModel . '"! Ожидалось "' . $refModel . '".';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;

                throw new \Exception($res->msg);
            }

            $dataModelVersion = $xml['Версия'];
            info("parser: Модель данных: $dataModel, версия: $dataModelVersion");
            $this->log->info('модель данных: "' . $dataModel . '", версия ' . $dataModelVersion);

            $extsyscode = $xml['КодСистемы'];
            if (is_null($extsyscode)) {
                $res->err = 1;
                $res->msg = 'Не задана внешняя система! Вероятно неподходящий файл.';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;

                throw new \Exception($res->msg);
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
                        "created_by" => 1,
                        "created_at" => now(),
                        "updated_by" => 1,
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

            $totDocCnt = count($xml->Квитанции->Квитанция);
            $resMsg .= $lf . now() . ' - Всего документов: ' . $totDocCnt;
            $this->log->info('всего документов: ' . $totDocCnt);

            foreach ($xml->Квитанции->Квитанция as $doc) {

                try {
                    DB::beginTransaction();

                    //\Log::debug($doc['НомерДокумента']);

                    //$docdate = date_create_from_format($dateFormat, $doc['ДатаДокумента']);

                    //$strDate = trim($doc['ДатаДанных']);
                    //компромисный вариант, надеюсь временно, так как ДатаДанных, это время изменения данных в ЛК,
                    // а ДатаОбработки это время импорта заказа в 1С. И если за это время исходные данные в ЛК изменились,
                    // но ранее чем ДатаОбработки, то эти изменения до 1С не дойдут.
                    $strDate = trim($doc['ДатаДанных'] ?? $doc['ДатаОбработки'] ?? '');

                    $strDate = (strlen($strDate) < 17) ? $strDate . ' 00:00:00' : $strDate;
                    $data_dt = date_create_from_format($datetimeFormat, $strDate);


                    $docnum = $doc['НомерЗаказа'];
                    //$this->log->info("№: " . $docnum);
                    info("Квитанция: НомерЗаказа: $docnum, Дата: $strDate");

                    if (isset($docnum)) {

                        //Ищем заказ по номеру - в пределах документов Этого Продавца
                        $ord = order::where([['ordnum', $docnum], ['ownorgid', $ownorgid]])
                            ->select('id', 'updated_at')
                            ->first();

                        if (!isset($ord)) {
                            $this->log->error(" Не найден заказ : $docnum");
                            $skp++;
                            continue;
                        } else {

                            info("Заказ: ID: $ord->id, Дата изменений: $ord->updated_at");

                            //Поищем квитанцию для этого заказа и этой внешней системе
                            $rcpt = objextsysrcpt::where([
                                ['sysobjid', 131],
                                ['objid', $ord->id],
                                ['extsysid', $extsysid]
                            ])->first();

                            $bNewRcpt = false;
                            if (!isset($rcpt)) {
                                $bNewRcpt = true;
                                $ins++;
                                $rcpt = new objextsysrcpt([
                                    'sysobjid' => 131,
                                    'objid' => $ord->id,
                                    'extsysid' => $extsysid,
                                    //'data_dt' => $data_dt,
                                ]);
                            } else $upd++;

                            //выясним момент изменения данных заказа.
                            // Если дата-время квитанции об импорте заказа в 1с больше или равно,
                            // чем дата-время изменения данных заказа,
                            // то сохраняем дату-время квитанции в заказе

                            //dd($ord->updated_at, $data_dt, $ord->updated_at <= $data_dt);
                            $updated_at = date_create_from_format('Y-m-d H:i:s', $ord->updated_at);

                            //if ($ord->updated_at <= $data_dt) {
                            if ($updated_at <= $data_dt) {

                                //установим новое время в квитанции и сохраним
                                $rcpt->data_dt = $data_dt;
                                $rcpt->save();

                                //Сохраним запись в журнале к заказу: -----------------------------------
                                objlog::log_info(131, $ord->id, "Получено подтверждения импорта заказа в учетную систему ($extsysid)", 3);

                            } else {
                                $this->log->error("Квитанция не принята! Заказ №$docnum был изменен уже после экспорта.");
                                info("Квитанция не принята! Заказ №$docnum снова был изменен уже после экспорта.");
                                $skp++;
                                if ($bNewRcpt)
                                    $ins--;
                                else
                                    $upd--;
                            }
                        }
                    } else {
                        $skp++;
                        $this->log->error("Не указан номер заказа", null, null);
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
                    info('parser ord1CInterface: ' . $parseCnt . ' - processed, ' . ($totDocCnt - $parseCnt) . ' - remained');
                    $this->log->info(" документов обработано: $parseCnt; осталось: " . ($totDocCnt - $parseCnt));
                }
            }

            $resMsg .= $lf . now() . ' - обработка завершена: '
                . "Тип данных: $dataModel"
                . "\n добавлено: $ins; обновлено: $upd; пропущено: $skp;";

            $res->msg = "Обработка завершена: добавлено: $ins; обновлено: $upd; пропущено: $skp.";

            info($resMsg); //запишем в системный лог

            //Запустим событие об окончании обработки файла
            event(new docs1CLoadedEvent($this->userid, $resMsg));
            //dispatch((new Send1CDocsLoaded($user, $resMsg))->onQueue('high'));
        }

        return $res;
    }
}

?>
