<?php
//Парсер/загрузчик данных номаенклатуры в формате "Интерфейс"

namespace App\Services\Parsers;

use App\itmtype;
use App\ItmSubType;

use App\Jobs\Parse1COrgs;
use App\Events\docs1CLoadedEvent;

use App\extsystem;
use App\objextid;
use App\group;
use App\orditem;
use App\refitem;
use App\ri_detail;
use App\ri_itmtype;
use App\ri_qtymarkup;
use App\unittype;
use DB;
use Cache;
use Log;

use App\Services\Readers;
use App\Services;

use App\sysobj;
use App\Traits\Result;


class product1C implements impFileParser
{


    public function __construct()
    {
        $this->needActivate = false;
        $this->userid = 0;
    }

    public function setting($param, \App\Services\ParserLogger $log)
    {
        $this->log = $log;

//        $this->log->info(var_export($param, false));

        foreach ($param as $val) {
            if ($val['preftypeid'] == "user") {
                if (is_numeric($val['prefvalue'])) {
                    $this->userid = intval($val['prefvalue']);
                }
            }
        }
        info('parser Parse1COrgs: userid: ' . $this->userid);
    }

    public function doit(\App\Services\Readers\impFileReader $reader)
    {
        //Загрузка через JOB
        //отправим в очередь с низким приоритетом
        //dispatch((new Parse1COrgs($reader->filename))->onQueue('low'));

        $res = $this->load_1sproduct_xml_job($reader->filename);

        //Заглушка для совместимости с вызывающей процедурой
        //$res = new Result();

        return $res;
    }


    public function load_1sproduct_xml_job($filename)
    {
        //Вариант для запуск через Job.
        // Быстрее в несколько раз, не зависит от тайм-аута веб-сервера

        $res = new Result();
        $resMsg = '';

        if (isset($filename)) {

            $lf = '<br>';

            $resMsg .= $lf . 'Протокол обработки: <hr size="1">';
            $resMsg .= now() . ' - начало обработки';

            //Занесем в системный журнал:
            info("Начат парсинг файла с продуктами (товары/услуги) из 1С: " . $filename);

            $userid = $this->userid;

            $xml = simplexml_load_file($filename);


//            $refModel = 'Контрагенты';
//            $refModel = 'Клиенты_менеджеры_группы';
            $refModel = 'Номенклатура';

            $dataModel = $xml['Модель'];
            if (is_null($dataModel)) {
                $res->err = 1;
                $res->msg = 'Не задана модель данных! Ожидалось "' . $refModel . '". Импорт отменен.';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($userid, $resMsg));

                throw new \Exception($res->msg);
            }
            if (strtolower($dataModel) <> strtolower($refModel)) {
                $res->err = 1;
                $res->msg = 'Задана неизвестная модель данных: "' . $dataModel . '"! Ожидалось "' . $refModel . '".';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($userid, $resMsg));

                throw new \Exception($res->msg);
            }


            $extsyscode = $xml['КодСистемы'];

            if (is_null($extsyscode)) {

                $res->err = 1;
                $res->msg = 'Не задана внешняя система! Вероятно неподходящий файл.';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($userid, $resMsg));

                throw new \Exception($res->msg);
            }

            $resMsg .= $lf . now() . " - Внешняя система: $extsyscode";
            $this->log->info("Внешняя система: $extsyscode");
            $this->log->info("Модель данных: $dataModel");

            $extsys = extsystem::where('code', $extsyscode)->select('id')->first();
            $extsysid = isset($extsys) ? $extsys->id : null;
            if (!isset($extsysid)) {
                $res->err = 1;
                $res->msg = 'Неизвестная внешняя система: "' . $extsyscode . '"!';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;
                //Запустим событие об окончании обработки файла
                event(new docs1CLoadedEvent($userid, $resMsg));

                throw new \Exception($res->msg);
            }

            $ins = 0;
            $upd = 0;
            $skp = 0;
            $parseCnt = 0;
            $npp = 0;
            $mngr_unknown = []; //Список несопоставленных менеджеров
            $grp_unknown = [];  //Список несопоставленных групп
            //$curdate = date_create_from_format('Y-m-d', now()); //- не работает
            $curdate = date('Y-m-d');
            //info("curdate: $curdate");

            //$totDocCnt = count($xml->Контрагенты->Контрагент);
            $totDocCnt = count($xml->СправочникНоменклатура->Номенклатура);
            $resMsg .= $lf . now() . ' - Всего продуктов: ' . $totDocCnt;
            $this->log->info("всего записей о продуктах: $totDocCnt");


            //Формат файла с данными модели "Номенклатура" предполагает, что будут теги "УровеньВложения1" и "УровеньВложения2"
            // Возможно в локальной системе для них определено соответствие с записями в спр-к "Типов групп "GrpTypes"".
            // Попробуем их найти. Доп условие типы групп должны иметь sysobjid=105 (refitems)

            $grptype1id = objextid::objid_by_extsysid_extid($extsysid, 821, 'УровеньВложения1');
            //$this->log->info("УровеньВложения1: $grptype1id");
            $grptype2id = objextid::objid_by_extsysid_extid($extsysid, 821, 'УровеньВложения2');
            //$this->log->info("УровеньВложения2: $grptype2id");


//            $grp_cache = []; //кэш сопоставленных ключей для групп (groups)
            $grp_cache = array(); //кэш сопоставленных ключей для групп (groups)

            foreach ($xml->СправочникНоменклатура->Номенклатура as $doc) {

                $npp++;

                try {
                    DB::beginTransaction();


                    $itmextid = trim($doc['НоменклатураКод']);
                    if (is_null($itmextid) or !isset($itmextid) or empty($itmextid)) {
                        $skp++;
                        $res->err = 1;
                        $res->msg = 'В записи #' . $npp . ' не задан код продукта (аттрибут "НоменклатураКод")! Запись не загружена!';

                        throw new \Exception($res->msg);
                    }


                    //Начнем не с товара а с категории и подкатегории, чтобы при добавлении/обновлении товара "на руках"
                    // уже были категория и подкатегория товара

                    $grpids = [];   //Массив идентификаторов групп, куда нужно будет добавить товар
                    $itmtypeid = 1;  // 1 = 'Разное' - категория, которая есть всегда, далее - фактические значения из источника

//                    $grpextid = trim($doc['УровеньВложения1']); //ОВОЩИ,ФРУКТЫ СВЕЖИЕ
//                    $grpextid = trim($doc['ТипМатериалаКод']); //F00000032
//                    $grpextname = trim($doc['ТипМатериалаНаименование']); //Оборудование //Печать

                    $root_itmtype_extid = trim($doc['ВидНоменклатуры']); //Товар / Производство / Услуга
                    $root_itmtype_extname = $root_itmtype_extid;

                    if ($root_itmtype_extid <> "") {

                        // "ВидНоменклатуры" соответствует родительской(корневой) категории товара (sysobjid=101). -----
                        $parent_itmtypeid = null;
                        // Попробуем найти категорию по коду внешней системы
                        $itmtypeid = objextid::objid_by_extsysid_extid($extsysid, 101, $root_itmtype_extid);

                        if (!isset($itmtypeid)) {
                            //Не нашли - Добавим запись о новой категории товара  по коду во внешней системе
                            $itmtypeid = itmtype::newItmTypeByExtID($extsysid, $root_itmtype_extid, $root_itmtype_extname
                                , $parent_itmtypeid, $userid);
                            //$ins++;
                            $this->log->info("новая корневая категория: '$root_itmtype_extid' - '$root_itmtype_extname'");
                        }
                        //-------------------------------------------------------------------------------------
                    }

                    $itmsubtypeid = null;
                    $grpextid = trim($doc['ТипМатериалаКод']); //F00000032
                    $grpextname = trim($doc['ТипМатериалаНаименование']); //Оборудование //Печать
                    if ($grpextid <> "" and isset($itmtypeid)) {

                        // "ТипМатериалаКод" соответствует подкатегории товара (sysobjid=101). ------------------
                        // Попробуем найти подкатегорию или создать
                        $itmsubtypeid = objextid::objid_by_extsysid_extid($extsysid, 101, $grpextid);

                        if (!isset($itmsubtypeid)) {
                            //Добавим запись о новой подкатегории товара  по коду во внешней системе
                            //$itmsubtypeid = ItmSubType::newItmSubTypeByExtID($extsysid, $grpextid, $itmtypeid, $grpextid, $userid);
                            $itmsubtypeid = itmtype::newItmTypeByExtID($extsysid, $grpextid, $grpextname
                                , $itmtypeid, $userid);
                            //$ins++;
                            $this->log->info("новая подкатегория: '$grpextid': '$grpextname'");
                        }
                        //переместим товар на более частную категорию (от более общей)
                        $itmtypeid = $itmsubtypeid;
                        //--------------------------------------------------------------------------------------
                    }

                    // --------------------------------------------------------------------------------------
                    //Единица измерения
                    $unittypeid = null;
                    $unit_extid = trim($doc['БазоваяЕдиницаИзмеренияКод']);
                    $unit_extname = trim($doc['БазоваяЕдиницаИзмеренияНаименование']);

                    if ($unit_extid) {
                        $unittypeid = objextid::objid_by_extsysid_extid($extsysid, 301, $unit_extid);

                        if (!isset($unittypeid)) {
                            //Добавим запись о новой единице измерения товара  по коду во внешней системе
                            $unittypeid = unittype::newItmByExtID($extsysid, $unit_extid
                                , [
                                    'name' => $unit_extname,
                                    'active' => 1,
                                ]
                                , $userid);
                            //$this->log->info("новая единица измерения: '$unit_extid': '$unit_extname'");
                        }
                    }

                    // --------------------------------------------------------------------------------------
                    // Теперь займемся товаром:
                    $itmname = trim($doc['НоменклатураНаименование']);

                    $itmPrice = trim($doc['НоменклатураЦена']);
                    $itmPrice = ($itmPrice == '') ? null : $itmPrice;
//                    $this->log->info("цена: /$itmPrice/");


                    $refitmid = objextid::objid_by_extsysid_extid($extsysid, 105, $itmextid);

                    if (isset($refitmid)) {
                        //перепроверим, что на самом деле есть такая запись в спр-ке RefItems
                        $refitem = refitem::find($refitmid);
                        $refitmid = $refitem->id ?? null;
                    }

                    if (!isset($refitmid)) {
                        //Добавим запись о новом продукте по коду во внешней системе

                        $refitmid = refitem::newItemByExtID($extsysid, $itmextid, [
                            'name' => $itmname,
                            'unittypeid' => $unittypeid,
                            'unit' => $unit_extname ?? 'шт',
                            'price' => $itmPrice,
                            'itmtypeid' => $itmtypeid ?? 1,
                            //'itmsubtypeid' => $itmsubtypeid,
                            'salebegdate' => $curdate,
//                            'active' => isset($itmPrice) ? 1 : 0,
                            'producttypeid' => 1,   //1=услуга
                            'active' => 1,
                            'created_by' => 1,
                        ]);
                        $ins++;
                        //$this->log->info("новый товар: ($itmextid) $itmname");

                    } else {

                        //Обновим что можем
                        $refitem->name = $itmname;
                        $refitem->unittypeid = $unittypeid;
                        $refitem->unit = $unit_extname ?? 'шт';
                        $refitem->costprice = $itmPrice;
                        $refitem->price = $itmPrice;
                        $refitem->itmtypeid = $itmtypeid;
                        //$refitem->itmsubtypeid = $itmsubtypeid;
                        $refitem->producttypeid = 1; //услуга
                        $refitem->active = 1;
                        $refitem->updated_by = $userid;
                        $refitem->updated_at = now();
                        $refitem->save();
                        $upd++;
                        $refitmid = $refitem->id;
                    }

                    if (isset($refitmid)) {

                        //Добавим товар в категории
                        if ($itmtypeid) {
                            ri_itmtype::addRefItem($itmtypeid, $refitmid, $userid);
                        }

                        //Заполним опци -------------------------------------------------------------------
                        //считаем, что  если опции нет в XML, то ее нужно удалить из БД
                        //Пометим все записи состава документа "неправильным" значением в поле updated_by
                        //чтобы не удалть всё, и не создавать повторные записи при каждой загрузке
                        // но потом удалить все незатронутые записи


                        if (1 == 1) {
                            //вариант с refitems.parent_id, refitems.has_child

                            //пометим все нынешние дочерние записи, чтобы можно было их потом удалить
                            refitem::where('parent_id', $refitmid)->update(['updated_by' => 0]);

                            if (isset($doc->ВариантыНоменклатуры)) {

                                $parent_itmname = $itmname;
                                foreach ($doc->ВариантыНоменклатуры->Вариант as $option) {
                                    //$this->log->info($option['ВариантыНоменклатурыНаименование']);
                                    //$this->log->info($option->ТабБазЦена['СтрТабБазЦенаБазоваяЦена']);

                                    $extid = 'opt_' . $option['ВариантНоменклатурыКод']; //добавим искусственный префикс,
                                    // чтобы избежать возможной неуникальности внешних ключей, приходящих из двух источников
                                    $itmname = $option['ВариантНоменклатурыНаименование'] ?? '-';
                                    $itmPrice = $option['БазоваяЦена'] ?? null;
                                    $itmPrice = str_replace(',', '.', $itmPrice);
                                    $itmPrice = ($itmPrice == '') ? null : $itmPrice;

                                    //$this->log->info('вариант - ' . $itmPrice . ': ' . $itmname);

                                    $rioptid = objextid::objid_by_extsysid_extid($extsysid, 105, $extid);

                                    if (isset($rioptid)) {
                                        //перепроверим, что на самом деле есть такая запись в спр-ке refitems
                                        $riopt = refitem::find($rioptid);
                                        $rioptid = $riopt->id ?? null;
                                    }

                                    if (!isset($rioptid)) {
                                        //Добавим запись о новой опции продукта по коду во внешней системе

                                        $rioptid = refitem::newItemByExtID($extsysid, $extid, [
                                            'parent_id' => $refitmid,
                                            'name' => $itmname, //не может быть Null
                                            'price' => $itmPrice,
                                            'unittypeid' => $unittypeid,
                                            'unit' => $unit_extname ?? 'шт',
                                            'created_by' => 1, //1=system account
                                        ]);
                                        //$this->log->info("новая опция: ($itmextid) $itmname (id=$rioptid)");
                                        $riopt = refitem::find($rioptid);

                                    }

                                    //Обновим что можем
                                    $riopt->name = $itmname;
                                    $riopt->costprice = $itmPrice;
                                    $riopt->price = $itmPrice;
                                    $riopt->unittypeid = $unittypeid;
                                    $riopt->unit = $unit_extname ?? 'шт';

                                    $riopt->itmtypeid = $itmtypeid; //Продублируем как в родительской записи - для облегчения поиска
                                    $riopt->searchname = $parent_itmname . '|' . $itmname;

                                    $riopt->updated_by = 1; //1=system account
                                    $riopt->updated_at = now();
                                    $riopt->save();

                                    //$rioptid = $riopt->id;


                                    if (isset($rioptid)) {

                                        // обработаем надбавки к базовой цене в зависимости от кол-ва ---------------
                                        ri_qtymarkup::where('refitmid', $rioptid)->delete();
                                        if (isset($option->Цены)) {
                                            $ordr = 0;
                                            foreach ($option->Цены->Значения as $markup) {
                                                $minqty = $markup['Кмин'];
                                                $maxqty = $markup['Кмакс'];
                                                //2147483647
                                                //1999999999
                                                $maxqty = ($maxqty > 1999999999) ? 1999999999 : $maxqty;
                                                $pcnt = $markup['Коэффициент'];
                                                $pcnt = str_replace(',', '.', $pcnt);
                                                $roundtype = $markup['Округление'];

                                                $ceil_to = 0.01;
                                                switch ($roundtype) {
                                                    case 'До целого':
                                                        $ceil_to = 1;
                                                        break;
                                                    case 'До 5 руб':
                                                        $ceil_to = 5;
                                                        break;
                                                    case 'До 50 коп':
                                                        $ceil_to = 0.5;
                                                        break;
                                                    case 'Нет':
                                                        $ceil_to = 0.01;
                                                        break;
                                                    default:
                                                        $ceil_to = 0.01;
                                                }

                                                //$this->log->info("наценка от кол-ва: ($minqty .. $maxqty) $pcnt %");
                                                $ri_qtymarkup = new ri_qtymarkup([
                                                    'refitmid' => $rioptid,
                                                    'ordr' => ++$ordr,
                                                    'minqty' => $minqty,
                                                    'maxqty' => $maxqty,
                                                    'markuppcnt' => $pcnt,
                                                    'ceil_to' => $ceil_to,
                                                ]);
                                                $ri_qtymarkup->save();
                                                //$this->log->info("наценка для $rioptid: ($minqty..$maxqty) $pcnt");
                                            }
                                        }
                                        //---------------------------------------------------------------------------

                                    }
                                }
                            }

                            //Удалим все незатронутые записи (лишние)
                            refitem::where('parent_id', $refitmid)->where('updated_by', 0)->delete();

                            //подсчитаем кол-во дочерних записей
                            $cnt = refitem::where('parent_id', $refitmid)->count();
                            //$this->log->info("дочерних записей для $refitmid: $cnt");

                            //обновим признак наличия дочерних записей
                            refitem::where('id', $refitmid)->update(['has_child' => ($cnt > 0) ? 1 : 0]);
                        }
                        //--------------------------------------------------------------------------------

                    } else {
                        $skp++;
//                        $this->log->error("Не найдено соответствие для товара "
                    }
                } catch
                (\Exception $e) {
                    DB::rollback();
                    \Log::debug('------Exception---------');
                    \Log::debug($e->getMessage());
                    \Log::debug($e->getTraceAsString());

                    $this->log->error($e->getMessage());
                    $resMsg .= $lf . now() . ' - ОШИБКА: ' . $e->getMessage();

                } finally {
                    //\Log::debug('------finally---------');
                    DB::commit();   //для отображения записей через  $this->log->info/error/...
                }

                $parseCnt++;
                if ($parseCnt % 100 == 0) {
                    $msg = 'загрузка: ' . $parseCnt . ' - обработано, ' . ($totDocCnt - $parseCnt) . ' - осталось';
                    info($msg);
                    $this->log->info($msg);
                }
            }


            //второй проход - для добавления детализации номенклатуры. -----------------------------------
            $this->log->info('Второй проход - для обработки спецификаций номенклатуры...');
            $parseCnt = 0;
            $npp = 0;
            foreach ($xml->СправочникНоменклатура->Номенклатура as $doc) {

                $npp++;

                try {
                    DB::beginTransaction();

                    $itmextid = trim($doc['НоменклатураКод']);
                    if (is_null($itmextid) or !isset($itmextid) or empty($itmextid)) {
                        $skp++;
                        $res->err = 1;
                        $res->msg = 'В записи #' . $npp . ' не задан код продукта (атрибут "НоменклатураКод")! Запись не загружена!';

                        throw new \Exception($res->msg);
                    }

                    $refitmid = objextid::objid_by_extsysid_extid($extsysid, 105, $itmextid);
                    //все что могли - добавили при первом проходе, поэтому не делаем повторных проверок/добавлений
                    if (isset($refitmid)) {

                        //проход по вариантам(опциям номенклатуры)
                        if (isset($doc->ВариантыНоменклатуры)) {
                            foreach ($doc->ВариантыНоменклатуры->Вариант as $option) {
                                //$this->log->info($option['ВариантыНоменклатурыНаименование']);
                                //$this->log->info($option->ТабБазЦена['СтрТабБазЦенаБазоваяЦена']);
//                            $hasSpecs = (isset($option->СтрСпецификации)) ? 'да' : 'нет';
//                            $this->log->info("has Specs: $hasSpecs");

                                if (isset($option->Спецификации)) {
                                    $extid = 'opt_' . $option['ВариантНоменклатурыКод']; //добавим искусственный префикс,
                                    // чтобы избежать возможной неуникальности внешних ключей, приходящих из двух источников
                                    $rioptid = objextid::objid_by_extsysid_extid($extsysid, 105, $extid);

                                    if (isset($rioptid)) {
                                        $riopt = refitem::find($rioptid);
                                        $rioptid = $riopt->id ?? null;

                                        // Обработаем спецификации, если они есть для этого варианта номенклатуры ---

                                        //пометим все нынешние записи, чтобы можно было потом удалить те,
                                        // которые не были обновлены
                                        ri_detail::where('refitmid', $rioptid)->update(['updated_by' => 0]);

                                        $detail_cnt = 0;
                                        foreach ($option->Спецификации->Спецификация as $detail) {

                                            $detail_cnt++;

                                            $extid = $detail['СпецификацииКод'];
                                            $itmname = $detail['СпецификацииНаименование'];

                                            //$this->log->info("$rioptid: '$itmname'");

                                            $itmqty = $detail['СпецификацииКоличество'];
                                            $itmqty = str_replace(',', '.', $itmqty);
                                            $itmqty = ($itmqty == '') ? null : $itmqty;

                                            $inunitqty = $detail['СпецификацииКоличествоВЕдинице'];
                                            $inunitqty = str_replace(',', '.', $inunitqty);
                                            $inunitqty = ($inunitqty == '') ? null : $inunitqty;

                                            $itmprice = $detail['СпецификацииЦена'];
                                            $itmprice = str_replace(',', '.', $itmprice);
                                            $itmprice = ($itmprice == '') ? null : $itmprice;

                                            $itmsum = $detail['СпецификацииСтоимость'];
                                            $itmsum = str_replace(',', '.', $itmsum);
                                            $itmsum = ($itmsum == '') ? null : $itmsum;

                                            //Единица измерения ------------------------------------------------------------
                                            $unittypeid = null;
                                            $unit_extid = trim($detail['СпецификацииЕдиницаИзмеренияКод']);
                                            $unit_extname = trim($detail['СпецификацииЕдиницаИзмеренияНаименование']);

                                            if ($unit_extid) {
                                                $unittypeid = objextid::objid_by_extsysid_extid($extsysid, 301, $unit_extid);

                                                if (!isset($unittypeid)) {
                                                    //Добавим запись о новой единице измерения товара  по коду во внешней системе
                                                    $unittypeid = unittype::newItmByExtID($extsysid, $unit_extid
                                                        , [
                                                            'name' => $unit_extname,
                                                            'active' => 1,
                                                        ]
                                                        , $userid);
                                                    //$this->log->info("новая единица измерения: '$unit_extid': '$unit_extname'");
                                                }
                                            }
                                            // -----------------------------------------------------------------------------


                                            //найдем идентификатор товара для детализации в справочнике товаров
                                            $selfrefitmid = objextid::objid_by_extsysid_extid($extsysid, 105, $extid);

                                            //$this->log->info("детализация для $rioptid: ($extid) $itmname ($selfrefitmid)");

                                            if (isset($selfrefitmid)) {
                                                //перепроверим, что на самом деле есть такая запись в спр-ке refitems
                                                $selfrefitm = refitem::find($selfrefitmid);
                                                $selfrefitmid = $selfrefitm->id ?? null;
                                            }

                                            if (isset($selfrefitmid)) {
                                                //продлжаем, только если смогли определить товар в справочнике

                                                //найдем запись о детализации.
                                                // (!) Предполагаем, что в детализации selfrefitmid - уникален
                                                $ri_detail = ri_detail::where('refitmid', $rioptid)
                                                    ->where('selfrefitmid', $selfrefitmid)->first();

                                                if (!isset($ri_detail)) {
                                                    //Добавим запись о детализации продукта

                                                    $ri_detail = new ri_detail([
                                                        'refitmid' => $rioptid,
                                                        'selfrefitmid' => $selfrefitmid,
                                                        'created_by' => 1,
                                                    ]);

                                                    //$this->log->info("новая опция: ($itmextid) $itmname (id=$rioptid)");
                                                }

                                                //Обновим, что можем
                                                $ri_detail->in_qty = $itmqty;
                                                $ri_detail->out_qty = 0;

//                                                $ri_detail->name = $itmname;
//                                                $ri_detail->price = $itmprice;
//                                                $ri_detail->itmsum = $itmsum;
//                                                $ri_detail->unit = $unit_extname;
//                                                $ri_detail->unittypeid = $unittypeid;
//                                                $ri_detail->inunitqty = $inunitqty;

                                                $ri_detail->updated_by = $userid;
                                                $ri_detail->updated_at = now();
                                                $ri_detail->save();
                                            }
                                        }
                                        //Удалим все незатронутые записи (лишние)
                                        ri_detail::where('refitmid', $rioptid)->where('updated_by', 0)->delete();

                                        $riopt->has_detail = ($detail_cnt == 0) ? 0 : 1;
                                        $riopt->save();
                                        //---------------------------------------------------------------------------

                                    }
                                }
                            }
                        }
                    }


                } catch
                (\Exception $e) {
                    DB::rollback();
                    \Log::debug('------Exception---------');
                    \Log::debug($e->getMessage());
                    \Log::debug($e->getTraceAsString());

                    $this->log->error($e->getMessage());
                    $resMsg .= $lf . now() . ' - ОШИБКА: ' . $e->getMessage();

                } finally {
                    //\Log::debug('------finally---------');
                    DB::commit();   //для отображения записей через  $this->log->info/error/...
                }

                $parseCnt++;
                if ($parseCnt % 100 == 0) {
                    $msg = 'загрузка: ' . $parseCnt . ' - обработано, ' . ($totDocCnt - $parseCnt) . ' - осталось';
                    info($msg);
                    $this->log->info($msg);
                }
            }

//            \Log::debug('------------------------------------');
//            \Log::debug(var_dump($grp_cache));


            //Заполним бренды в товарах
//            $this->log->info('Установка соответствия с товарными брендами...');
//            DB::unprepared('CALL fill_brands2refitems()');

            $res->msg = "обработка завершена: Записей добавлено : $ins; обновлено: $upd; пропущено: $skp;";
            $resMsg .= $lf . now() . ' - ' . $res->msg;
            //$this->log->info($res->msg);

            //Запустим событие об окончании обработки файла
            event(new docs1CLoadedEvent($userid, $resMsg));

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
