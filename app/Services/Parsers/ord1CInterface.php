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
use App\objmsg;
use App\objpref;
use App\oi_qty;
use App\order;
use App\orditem;
use App\org;
use App\objextid;
use App\orgdog;
use App\refitem;
use App\sysobj;
use App\Events\notifyEvent;

use App\unittype;
use App\User;
use App\userorg;
use DB;
use Cache;

use App\Services\Readers;
use App\Services;
use App\Services\ParserLogger;

use App\Traits\Result;
use App\Traits\StringUtil;


class ord1CInterface implements impFileParser
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
        info('parser ord1CInterface: userid: ' . $this->userid);

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
        //Вариант для запуска Job-ом. Быстрее в несколько раз, не зависит от тайм-аута веб-сервера

        // --- НАСТРОЙКИ ----------------------------------------------------

        $refModel = 'Заказы';

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
            info("Начат парсинг файла с заказми из 1С: " . $filename);

            $userid = $this->userid;
            info("userid: " . $userid);

            $xml = simplexml_load_file($filename);

            $dataModel = $xml['Модель'];
            if (is_null($dataModel)) {
                $this->log->fatalerror("Не задана модель данных! Ожидалось '$refModel'. Импорт остановлен.");
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . 'Не задана модель данных! Ожидалось "' . $refModel . '".';
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($this->userid, $resMsg));
                return;
            }
            //Один обработчик и для Заказы и для БыстраяПродажа
            $dataModel = ($dataModel == 'БыстраяПродажа') ? $refModel : $dataModel;

            if (strtolower($dataModel) <> strtolower($refModel)) {
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . 'Задана неизвестная модель данных: "' . $dataModel . '"! Ожидалось "' . $refModel . '".';
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($this->userid, $resMsg));
                return;
            }
            $dataModelVersion = $xml['Версия'];
            info("parser: Модель данных: $dataModel, версия: $dataModelVersion");
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

//            $stock = resolve('App\Http\Middleware\IStock');
//            $stockActive = (isset($stock) and $stock->active());

            $ins = 0;
            $upd = 0;
            $skp = 0;
            $parseCnt = 0;
            $newOrgs = 0;
            $newRefItems = 0;

            $totDocCnt = count($xml->Документы->Документ);
            $resMsg .= $lf . now() . ' - Всего документов: ' . $totDocCnt;
            $this->log->info('всего документов: ' . $totDocCnt);

            $statuses = [
                'Предварительный' => 1,
                'Остановлено' => 1,
                'Старая версия' => 1,
                'Ожидает изготовления' => 2,
                'Частично изготовлен' => 4,
                'Полностью изготовлен' => 4,
                'Готов' => 4,
                'Закрыто' => 4,
            ];

            foreach ($xml->Документы->Документ as $doc) {

                try {
                    DB::beginTransaction();

                    //\Log::debug($doc['НомерДокумента']);

                    //$docdate = date_create_from_format($dateFormat, $doc['ДатаДокумента']);

                    $strDate = trim($doc['ДатаДокумента']);
                    $strDate = (strlen($strDate) < 17) ? $strDate . ' 00:00:00' : $strDate;
                    $docdate = date_create_from_format($datetimeFormat, $strDate);

                    $minDocDate = ($docdate < $minDocDate) ? $docdate : $minDocDate;
                    $maxDocDate = ($docdate > $maxDocDate) ? $docdate : $maxDocDate;
//                    dd($docdate, $minDocDate,$maxDocDate);

                    $extdoctype = $doc['ТипДокумента'];
//                    $this->log->info("внешний тип документа: " . $extdoctype);

//                    if (!isset($extdoctype)) {
                    if (is_null($extdoctype)) {
                        $skp++;
                        throw new \Exception('Не удалось найти аттрибут "ТипДокумента"! Неподходящий формат файла. Импорт отменен.');
                    }
                    if ($extdoctype == 'Заказ' || $extdoctype == 'БыстраяПродажа') {
                        //обрабатываем только заказы. Остальное - пропускаем

                        $ordtypeid = ($extdoctype == 'Заказ') ? 2 : 1;

                        //$docnum = $doc['НомерДокумента'];
                        //2020-02-26 Переход на номер документа из тега "НомерЗаказа", так как при создании нескольких
                        // версий одного заказа в 1С НомерДокумента каждый раз новый, а НомерЗаказа - постоянный
                        if ($extdoctype == 'Заказ')
                            //$docnum = $doc['НомерЗаказа'];      //Для "Заказ"
                            $docnum = $doc['НомерЗаказа'] ?? $doc['НомерДокумента'];  //для 2018 года почему-то нет поля 'НомерЗаказа'
                        else
                            $docnum = $doc['НомерДокумента'];   //Для "БыстраяПродажа"
                        //$this->log->info("№: " . $docnum);


                        $wrhid = null;


                        $stageCode = trim($doc['Статус']);
                        $stageid = $statuses[$stageCode] ?? 1;


                        //2020-02-26 Пока(?) обрабатываем все
                        //временно - обрабатываем только заказы начиная с 4-го этапа
                        if (1 == 0 and $stageid < 4) {
                            $skp++;
                            $this->log->error("$docnum - пропуск по несоответствию этапа: $stageid - $stageCode", null, null);

                            continue;
                        }


                        //считаем, что если контрагент не указан, то это - Физ. лицо
                        $orgextid = trim($doc['КлиентКод']);
                        $orgextid = (empty($orgextid)) ? trim($doc['ЗаказчикКод']) : $orgextid;
                        $orgextid = (empty($orgextid)) ? '-UNKNOWN-' : $orgextid;
                        //$this->log->info("orgextid: $orgextid");
                        if (is_null($orgextid) or !isset($orgextid) or empty($orgextid)) {
                            $skp++;
//                            $msg = 'В документе №' . $docnum . ' не задан код контрагента (аттрибут "КонтрагентКод")! Документ не загружен!';
                            $msg = 'В документе №' . $docnum . ' не задан код контрагента (аттрибут "КлиентКод / ЗаказчикКод")! Документ не загружен!';
                            $this->log->error($msg);
                            continue;

                            throw new \Exception($msg);
                        }
                        $orgid = objextid::objid_by_extsysid_extid($extsysid, 111, $orgextid);
                        //$this->log->info("orgid: $orgid");

                        if (!isset($orgid) and 1 == 1) {
                            //Добавим запись о новой организации по коду во внешней системе
                            $orgname = $doc['КонтрагентНаименование'];
//                        $this->log->info("Неизвестная организация, пробуем добавить: "
//                            . $extsysid . ': ' . $orgextid . ': "' . $doc['КонтрагентНаименование']
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

                            //Исполнитель по заказу -------------------------------------------
                            $workuserid = null;
                            $workuser_extid = $doc['ИсполнительКод'];

                            if ($workuser_extid) {
                                $workuserid = objextid::objid_by_extsysid_extid($extsysid, 3, $workuser_extid);

                                if (!isset($workuserid)) {
                                    //Добавим запись о новом пользователе - исполнителе
                                    $workuser_name = $doc['Исполнитель'];
                                    $workuserid = User::newItmByExtID($extsysid, $workuser_extid
                                        , [
                                            'name' => $workuser_name,
                                            'active' => 1,
                                            'email' => uniqid('staff_'), //компенсируем отсутствие email сотрудника
                                        ]
                                        , $userid);
                                    $this->log->info("новый сотрудник: '$workuser_extid': '$workuser_name' ($workuserid)");


                                }
                            }
                            //-----------------------------------------------------------------

                            $doctotsum = $doc['СуммаЗаказа'] ?? 0;

                            //Ищем документ по номеру - в пределах документов Этого Продавца
                            $ord = order::where([['ordnum', $docnum], ['ownorgid', $ownorgid]])
                                ->first();

                            if (!isset($ord)) {
                                $ord = new order();
                                $ord->ownorgid = $ownorgid;
                                $ord->ordnum = $docnum;
                                $ord->created_at = $docdate;
                                $ord->updated_at = $docdate;
                                $ins++;
                            } else
                                $upd++;

                            //выясним версию сохраненного заказа
                            $ord_preVersion = objpref::getPrefVal_noCache(131, $ord->id, 208) ?? -1;

                            $ord_Version = trim($doc['Версия']);    //Версия загружаемого сейчас заказа

                            if ($ord_Version >= $ord_preVersion) {
                                //версия заказа в принимаемых данных не ниже версии заказа в БД => продолжаем...

                                //сохраним текущий статус обработки, чтобы сравнить его с тем что прийдет из файла
                                $ord_pre_prep_statuscode = $ord->prep_statuscode ?? '';


                                $ord->stageid = $stageid; //этап подготовки/изготовления заказа
                                $ord->orgid = $orgid;
                                $ord->orddate = $docdate;
                                $ord->ordtypeid = $ordtypeid;   //Тип заказа: 1-Быстрая продажа, 2-Заказ
                                $ord->remarks = mb_substr(trim($doc['Комментарий']), 0, 200);

                                $ord->pricegrpid = (trim($doc['ГруппаЦен']) == '') ? null : trim($doc['ГруппаЦен']);
                                //dd($ord->pricegrpid);

                                $deadline_dt = (trim($doc['Дедлайн']) == '') ? null : trim($doc['Дедлайн']);;
                                $ord->deadline_dt = (isset($deadline_dt) and ($deadline_dt != ''))
                                    ? date_create_from_format($datetimeFormat, $deadline_dt)
                                    : null;

                                $data_folder = trim($doc['Макет']) ?? null;
                                if (isset($data_folder)) {
                                    $data_folder = str_replace('Z:\\', 'orders/', $data_folder);
                                    $data_folder = str_replace('\\', '/', $data_folder);
                                }
                                //$ord->data_folder = mb_substr($data_folder, 0, 120); //todo: отказаться от хранения
                                // пути к макетам внутри orders, так как есть преференция 210

                                $ord->prep_statuscode = trim($doc['СтатусКод']);
                                $ord->prep_statuscode = ($ord->prep_statuscode == '') ? null : $ord->prep_statuscode;
                                $ord->prep_statusname = trim($doc['Статус']);

                                $ord->aprv_statuscode = trim($doc['СтатусУтвержденияКод']);
                                $ord->aprv_statuscode = ($ord->aprv_statuscode == '') ? null : $ord->aprv_statuscode;
                                $ord->aprv_statusname = trim($doc['СтатусУтверждения']);

                                $ord->pay_statuscode = trim($doc['СтатусОплатКод']);
                                $ord->pay_statuscode = ($ord->pay_statuscode == '') ? null : $ord->pay_statuscode;
                                $ord->pay_statusname = trim($doc['СтатусОплат']);

                                $ord->out_statuscode = trim($doc['СтатусВыдачиКод']);
                                $ord->out_statuscode = ($ord->out_statuscode == '') ? null : $ord->out_statuscode;
                                $ord->out_statusname = trim($doc['СтатусВыдачи']);

                                $ord->exist_in_acntsys = 1; //1-признак наличия заказа в учетной системе

                                $ord->workuserid = $workuserid; //Исполнитель по заказу

                                $ord->created_by = 1; //1 - system (robot)
                                $ord->updated_by = 1; //1

                                //$ord->totalsum = $doctotsum;
                                $ord->save();


                                if (1 == 1) {
                                    $prefval = trim($doc['МатериалыЗаказчикаСтр']);
                                    $prefval = ($prefval == '') ? null : $prefval;
                                    objpref::setOrClrPrefVal(131, $ord->id, 201, $prefval, $userid);

                                    $prefval = trim($doc['СтоимостьМатериаловЗаказчика']);
                                    $prefval = ($prefval == '0' or $prefval == '') ? null : $prefval;
                                    objpref::setOrClrPrefVal(131, $ord->id, 202, $prefval, $userid);

                                    $prefval = trim($doc['Безнал']);
                                    $prefval = ($prefval == '') ? null : $prefval;
                                    objpref::setOrClrPrefVal(131, $ord->id, 203, $prefval, $userid);

                                    $prefval = trim($doc['ОплатитьСоСчетаКлиента']);
                                    $prefval = ($prefval == '') ? null : $prefval;
                                    objpref::setOrClrPrefVal(131, $ord->id, 204, $prefval, $userid);

                                    $prefval = trim($doc['Контакт']);
                                    $prefval = ($prefval == '') ? null : $prefval;
                                    objpref::setOrClrPrefVal(131, $ord->id, 205, $prefval, $userid);

                                    $prefval = trim($doc['ДолжностьКонтакта']);
                                    $prefval = ($prefval == '') ? null : $prefval;
                                    objpref::setOrClrPrefVal(131, $ord->id, 206, $prefval, $userid);

//                            $prefval = trim($doc['ИнформацияОКлиенте']);
//                            $prefval = ($prefval == '') ? null : $prefval;
//                            objpref::setOrClrPrefVal(131, $ord->id, 207, $prefval, $userid);

                                    //$prefval = trim($doc['Версия']);
                                    $prefval = $ord_Version;
                                    $prefval = ($prefval == '') ? null : $prefval;
                                    objpref::setOrClrPrefVal(131, $ord->id, 208, $prefval, $userid);

                                    $prefval = trim($doc['ПредыдущийЗаказНомер']);
                                    $prefval = ($prefval == '') ? null : $prefval;
                                    objpref::setOrClrPrefVal(131, $ord->id, 209, $prefval, $userid);

                                    $prefval = trim($doc['Макет']);
                                    $prefval = ($prefval == '') ? null : $prefval;
                                    objpref::setOrClrPrefVal(131, $ord->id, 210, $prefval, $userid);
                                }

                                if (1 == 0) {

                                    //Изменим подход - сразу удалим все преференции этого заказа с типами, которые мы можем
                                    // получить из импорта:
                                    objpref::where([['sysobjid', 131], ['objid', $ord->id]])
                                        ->whereIn('preftypeid', [201, 202, 203, 204, 205, 206, 207, 208, 209, 210])
                                        ->delete();

                                    //а теперь только добавим
                                    $set_cache = false;
                                    $prefval = trim($doc['МатериалыЗаказчикаСтр']);
                                    if (!empty($prefval))
                                        objpref::setPrefVal(131, $ord->id, 201, $prefval, $userid, $set_cache);

                                    $prefval = trim($doc['СтоимостьМатериаловЗаказчика']);
                                    if (!empty($prefval))
                                        objpref::setPrefVal(131, $ord->id, 202, $prefval, $userid, $set_cache);

                                    $prefval = trim($doc['Безнал']);
                                    if (!empty($prefval))
                                        objpref::setPrefVal(131, $ord->id, 203, $prefval, $userid, $set_cache);

                                    $prefval = trim($doc['ОплатитьСоСчетаКлиента']);
                                    if (!empty($prefval))
                                        objpref::setPrefVal(131, $ord->id, 204, $prefval, $userid, $set_cache);

                                    $prefval = trim($doc['Контакт']);
                                    if (!empty($prefval))
                                        objpref::setPrefVal(131, $ord->id, 205, $prefval, $userid, $set_cache);

                                    $prefval = trim($doc['ДолжностьКонтакта']);
                                    if (!empty($prefval))
                                        objpref::setPrefVal(131, $ord->id, 206, $prefval, $userid, $set_cache);

//                            $prefval = trim($doc['ИнформацияОКлиенте']);
//                                if (!empty($prefval))
//                                    objpref::setPrefVal(131, $ord->id, 207, $prefval, $userid, $set_cache);

                                    //$prefval = trim($doc['Версия']);
                                    $prefval = $ord_Version;
                                    if (!empty($prefval))
                                        objpref::setPrefVal(131, $ord->id, 208, $prefval, $userid, $set_cache);

                                    $prefval = trim($doc['ПредыдущийЗаказНомер']);
                                    if (!empty($prefval))
                                        objpref::setPrefVal(131, $ord->id, 209, $prefval, $userid, $set_cache);

                                    $prefval = trim($doc['Макет']);
                                    if (!empty($prefval))
                                        objpref::setPrefVal(131, $ord->id, 210, $prefval, $userid, $set_cache);
                                    //----------------------------------------------------------------------------
                                }


                                //Сохраним комментарий в сообщении к заказу: -----------------------------------
                                //сначала удалим пред. сообщения этого заказа
                                objmsg::where([['sysobjid', 131], ['objid', $ord->id]])->delete();

                                if ($ord->remarks <> '') {
                                    $objmsg = new objmsg([
                                        'sysobjid' => 131,
                                        'objid' => $ord->id,
                                        'subj' => 'примечание к заказу',
                                        'text' => trim($doc['Комментарий']),
                                        'created_by' => 1,
                                        'updated_by' => 1,
                                        'sent_by' => 0,
                                    ]);
                                    $objmsg->save();
                                }
                                if (trim($doc['ИнформацияОКлиенте']) <> '') {
                                    $objmsg = new objmsg([
                                        'sysobjid' => 131,
                                        'objid' => $ord->id,
                                        'subj' => 'информация о клиенте',
                                        'text' => trim($doc['ИнформацияОКлиенте']),
                                        'created_by' => 1, //1-system account
                                        'updated_by' => 1,
                                        'sent_by' => 0,
                                    ]);
                                    $objmsg->save();
                                }
                                //------------------------------------------------------------------------------


                                if (1 == 1) {
                                    //Пометим все записи состава документа "неправильным" значением в поле updated_by
                                    //чтобы не удалть всё, и не создавать повторные записи при каждой загрузке
                                    // но потом удалить все незатронутые записи
                                    orditem::where('ordid', $ord->id)->update(['updated_by' => 0]);;


                                    $doctotsum = 0;
                                    foreach ($doc->ТаблицаТовары as $itm) {
//                                    dd($itm);
//                                    $this->log->info("ordid: $ord->id, товар: " . $itm['НоменклатураНаименование']
//                                        . ' / ' . $itm['Свойства'] . ' / ' . $itm['УИ']);

                                        $prntItmCode = $itm['НоменклатураКод'];
                                        $itmCode = 'opt_' . $itm['СвойстваКод'];
                                        $itmName = $itm['Свойства'] ?? '-';
                                        $itmSearchName = $itm['НоменклатураНаименование'] . '|' . $itm['Свойства'];
                                        //$itmPrice = StringUtil::to_number($itm['ЦенаЗаЕдиницу']);
                                        $itmPrice = StringUtil::to_number($itm['ЦенаРасчетная']);

                                        $itmQty = StringUtil::to_number($itm['ОбщееКоличество']);

                                        // --------------------------------------------------------------------------------------
                                        //Единица измерения
                                        $unittypeid = null;
                                        $unit_extid = trim($itm['ЕдиницаИзмеренияКод']);
                                        $unit_extname = trim($itm['ЕдиницаИзмерения']);

                                        if ($unit_extid) {
                                            $unittypeid = objextid::objid_by_extsysid_extid($extsysid, 301, $unit_extid);

                                            if (!isset($unittypeid)) {
                                                //$this->log->info("для номенклатуры: '$itmCode': '$itmName'");
                                                //Добавим запись о новой единице измерения товара  по коду во внешней системе
                                                $unittypeid = unittype::newItmByExtID($extsysid, $unit_extid
                                                    , [
                                                        'name' => mb_substr($unit_extname, 0, 16),
                                                        'descript' => mb_substr($unit_extname, 0, 60),
                                                        'active' => 1,
                                                    ]
                                                    , $userid);
                                                $this->log->info("новая единица измерения: '$unit_extid': '$unit_extname'");
                                            }
                                        }

                                        //вариант с добавлением неизвестных товаров в справочник
                                        $refitmid = objextid::objid_by_extsysid_extid($extsysid, 105, $itmCode);
//                                info('extsysid='.$extsysid.', refitem: ' . $itmCode . ' - ' . $refitmid . ' - ' . $itmName);

                                        if (!isset($refitmid) and 1 == 1) {
                                            info(' new refitem: ' . $itmCode . ' - ' . $itmName . ' - ' . $itm['УИ']);
                                            //Добавим запись о новом товаре по коду во внешней системе
                                            $refitmid = refitem::newItemByExtID($extsysid, $itmCode, [
                                                'name' => $itmName,
                                                'unittypeid' => $unittypeid,
                                                'unit' => mb_substr($unit_extname, 0, 16),
                                                'price' => $itmPrice,
                                                //'itmtypeid' => 1,
                                                'salebegdate' => $docdate,
                                                'active' => 1,
                                                'created_by' => 1,
                                            ]);
                                            $newRefItems++;
                                        }

                                        $di = orditem::where('ordid', $ord->id)->where('updated_by', 0)->first();
                                        if (!isset($di)) {
                                            $di = new orditem();
                                            $di->ordid = $ord->id;
                                        }

                                        $di->refitmid = $refitmid;
                                        $di->itmname = $itmSearchName;
                                        $di->unittypeid = $unittypeid;
                                        $di->unit = mb_substr($unit_extname, 0, 16);
                                        $di->price = $itmPrice;
                                        $di->updated_by = 1; //1=system account
                                        $di->save();

                                        //сохраним кол-во в отдельной таблице OI_Qtys - для соответствующего этапа заказа --
                                        //предварительно - если этап больше этапа согласования,
                                        // то сохраним это же количество для этапа заявки и согласования
                                        if ($ord->stageid > 1)
                                            oi_qty::setQtyForStage($di->id, 1, $itmQty, $userid);
                                        if ($ord->stageid > 2)
                                            oi_qty::setQtyForStage($di->id, 2, $itmQty, $userid);

                                        // и конечно - для текущего этапа:
                                        oi_qty::setQtyForStage($di->id, $ord->stageid, $itmQty, $userid);

                                        //----------------------------------------------------------------------------------

                                        $doctotsum += $itmQty * $itmPrice;
                                    }
                                    //Удалим все незатронутые записи
                                    orditem::where('ordid', $ord->id)->where('updated_by', 0)->delete();

                                    $ord->totalsum = $doctotsum;
                                    $ord->save();
                                }

                                //Создадим / Обновим квитанцию к этому заказу - чтобы не подхватить этот
                                // заказ процессом экспорта обратно в 1С как новый
                                objextsysrcpt::refresh_data(131, $ord->id, $extsysid, $ord->updated_at);
                                //-------------------------------------------------------------------------------------



                                //Проверим, возможно изменился статус подготовки заказа
                                //Если да - отправим уведомление инициатору заказа
                                if (1 == 1
                                    and $ord->prep_statuscode <> ''
                                    and $ord->prep_statuscode <> $ord_pre_prep_statuscode) {

                                    $subj = 'ЛК "Интерфейс-ДВ": Изменение статуса подготовки заказа';
                                    $msg = "Статус заказа №$ord->id изменился на $ord->prep_statusname";
                                    event(new notifyEvent(103, 131, $ord->id, 1, $subj, $msg));
                                }
                            } // checkVersion

                        } else {
                            $skp++;
                            $this->log->error("Не найдено соответствие для контрагента "
                                . $doc['КонтрагентКод'] . ': ' . $doc['КонтрагентНаименование']
                                , null, null);
                        }
                    } else {
                        $this->log->info("Пропущен документ с неизвестным типом: '$extdoctype'.");
                        $skp++;
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

//            $res->msg = "Добавлено документов: $ins; обновлено: $upd; пропущено: $skp;";
            //$this->log->info($res->msg);


            $resMsg .= $lf . now() . ' - обработка завершена: '
                . "Тип данных: $dataModel"
                . "\nДобавлено документов: $ins; обновлено: $upd; пропущено: $skp;";
            $resMsg .= $lf . '       период документов: ' . $minDocDate->format("Y-m-d") . ' - ' . $maxDocDate->format("Y-m-d");

            $res->msg = "Обработка завершена: Добавлено документов: $ins; обновлено: $upd; пропущено: $skp."
                . "\n Новых контрагентов: $newOrgs. Новых товаров: $newRefItems.";
            //$this->log->info($res->msg);
            info($resMsg); //запишем в системный лог

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
