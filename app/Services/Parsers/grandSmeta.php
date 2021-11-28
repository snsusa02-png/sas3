<?php
//Парсер/загрузчик документов "Локальна смета" формата Grand-Смета

namespace App\Services\Parsers;

use App\estdoc_section;
use App\estdocitm_work;
use App\extsys_sysobj;
//use App\Jobs\Parse1CDocs;
use App\importfile;
use App\Jobs\Parse1COrgs;
use App\Events\docs1CLoadedEvent;

use App\extsystem;
use App\objextsysrcpt;
use App\objflag;
use App\objlog;
use App\meeting;
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
use Log;
use App\wrktyperefbook;
use App\wtrb_item;
use App\estDoc;
use App\estdocItem;
use App\estdocitm_resource;
use App\estdocitm_koeff;


class grandSmeta implements impFileParser
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
        info("parser: grandSmeta->doit $reader->filename");

        $res = new Result();

        $res = $this->parse($reader->filename, $reader->fileid);

        return $res;
    }


    public function parse($filename, $impfileid)
    {
        // --- НАСТРОЙКИ ----------------------------------------------------

        $refModel = '-GrandSmeta-'; //
        $refGenerator = "GrandSmeta";
        $refDocumentType = "{2B0470FD-477C-4359-9F34-EEBE36B7D340}";

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
            info("Начат парсинг файла с локальной сметой Гранд-Смета: " . $filename);

            $userid = $this->userid;
            info("userid: " . $userid);

            $xml = simplexml_load_file($filename);

            $dataGenerator = $xml['Generator'];
            $DocumentType = $xml['DocumentType'];

            if (is_null($dataGenerator)) {
                $res->err = 1;
                $res->msg = 'Не задан генератор данных! Ожидалось "' . $refGenerator . '".';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;

                throw new \Exception($res->msg);
            }

            if (strtolower($dataGenerator) <> strtolower($refGenerator)) {
                $res->err = 1;
                $res->msg = 'Задана неизвестный генератор данных: "' . $dataGenerator . '"! Ожидалось "' . $refModel . '".';
                $resMsg .= $lf . now() . ' - ОШИБКА: ' . $res->msg;

                throw new \Exception($res->msg);
            }

            $dataModelVersion = $xml['ProgramVersion'];
            info("parser: генератор данных: $dataGenerator, версия: $dataModelVersion");
            $this->log->info('генератор данных: "' . $dataGenerator . '", версия ' . $dataModelVersion);

            $extsyscode = $dataGenerator; //$xml['КодСистемы'];

            //dd($xml, $dataGenerator, $DocumentType);
            Log::debug('Модель: ' . $dataGenerator . '-' . $DocumentType);

            $ins = 0;
            $upd = 0;
            $skp = 0;

            if ($dataGenerator . '-' . $DocumentType == $refGenerator . '-' . $refDocumentType) {
                //то, что нужно - СМЕТА из Гранд-Сметы

                $docName = $xml['Generator'];
                //dd($xml, $xml['ProgramVersion'], $xml->Properties, $xml->Properties['Constr']);


                // - Актуализация справочника видов работ -----------------------
                foreach ($xml->VidRab_Catalog->Vids_Rab as $vid_rab_book) {
                    $catfile = trim($vid_rab_book['CatFile']);

                    $refbook = wrktyperefbook::where('catfile', $catfile)->first();
                    if (!isset($refbook)) {
                        $refbook = new wrktyperefbook([
                            'catfile' => $catfile,
                        ]);
                    }
                    $refbook->type = trim($vid_rab_book['CatFile']);
                    $refbook->nrspfile = trim($vid_rab_book['NrspFile']);
                    $refbook->save();

                    foreach ($vid_rab_book->VidRab_Group as $vid_rab_group) {
                        foreach ($vid_rab_group->Vid_Rab as $vid_rab) {

                            $code = $vid_rab['ID'];
                            if (isset($code)) {
                                $itm = wtrb_item::where([
                                    ['bookid', $refbook->id],
                                    ['code', $code],
                                ])->first();

                                if (!isset($itm)) {
                                    $itm = new wtrb_item([
                                        'bookid' => $refbook->id,
                                        'grpname' => $vid_rab_group['Caption'],
                                        'grpid' => $vid_rab_group['ID'],
                                        'code' => $code,
                                    ]);
                                }
                                $itm->name = $vid_rab['Caption'] ?? '-';
                                $itm->nacl = $vid_rab['Nacl'];
                                $itm->plan = $vid_rab['Plan'];
                                $itm->naclcurr = $vid_rab['NaclCurr'] ?? $itm->nacl;
                                $itm->plancurr = $vid_rab['PlanCurr'] ?? $itm->plan;
                                $itm->naclmask = $vid_rab['NaclMask'];
                                $itm->planmask = $vid_rab['PlanMask'];
                                $itm->save();
                            }
                        }
                    }
                }

                // --------------------------------------------------------------

                $impfilename = $filename;
                if (isset($impfileid)) {
                    $impfile = importfile::find($impfileid);
                    if (isset($impfile))
                        $impfilename = $impfile->clientfilename;
                }


                // - Сохранение/обновление документа сметы ----------------------
                $doc = new estDoc([
                    'buildopertypeid' => null,
//                        'name' => mb_substr(trim($file . ' ' . $xml->Properties['Comment'] ?? '' . ' ' . $xml->Properties['Description'] ?? ''), 0, 200),
                    //'name' => mb_substr($file, strlen($directory) + 1, 200),
                    'name' => $xml->Properties['Comment'] ?? $impfilename,
                    'importfileid' => $impfileid,
                    'srcfile' => $filename,
                    'programVersion' => $xml['ProgramVersion'],
                    'generator' => $xml['Generator'],
                    'documentType' => $xml['DocumentType'],
                    'locnum' => $xml->Properties['LocNum'],
                    'constr' => $xml->Properties['Constr'],
                    'comment' => $xml->Properties['Comment'],
                    'description' => $xml->Properties['Description'],
                    //'docnum' => 'ЛС-',
                    'docdate' => date_format(now(),'Y-m-d'),
                ]);
                $doc->save();
                $ins++;

                $docDrctSum = 0;
                $docNaclSum = 0;
                $docSPSum = 0;

                $section_ordr = 0;

                $i = 0;
                foreach ($xml->Chapters->Chapter as $chapter) {

                    //извращение из-за кривого XML. Position должен быть подчинен Header.
                    // Но здесь они на одном уровне.
                    //
                    $ttt = $chapter->asXML();
                    //Найдем все позиции где встречается "<Header" - поместим в массив $aHdr
                    preg_match_all("/<Header/", $ttt, $aHdr, PREG_OFFSET_CAPTURE);
                    //Найдем все позиции где встречается "<Position" - поместим в массив $aPosition
                    preg_match_all("/<Position/", $ttt, $aPosition, PREG_OFFSET_CAPTURE);
                    //var_dump($aPosition);

                    //сформируем массив $h где укажаем название Header и место его встречи
                    $ii = 0;
                    $jj = $chapter->Header->count() - 1; //будем формировать задом не перед
                    $h = array();
                    foreach ($chapter->Header as $header) {

                        $h[$jj][0] = $header['Caption']->__toString();
                        //$aHdr[0][$ii][0] = $header['Caption']->__toString();
                        $h[$jj][1] = $aHdr[0][$ii][1];
                        $ii++;
                        $jj--;
                    }
                    $h = array_reverse($h); // перевернем

                    //Сформируем итоговый массив, который для i-го элемена Position будет содержать его Header
                    $aPositionHeader = [];
                    $ii = 0;
                    foreach ($aPosition[0] as $tPos) {
                        //идем от последнего Header к первому
                        foreach ($h as $curHdr) {
                            //поэтому, как только позиция Header станет меньше позиции Position, то значит нашли то,
                            // что нужно. Присвоим и выйдем
                            if ($curHdr[1] < $tPos[1]) {
                                $aPositionHeader[$ii] = $curHdr[0];
                                break;
                            }
                        }
                        $ii++;
                    }

                    $estdoc_section = estdoc_section::where('estdocid', $doc->id)
                        ->where('sysid', $chapter['SysID'])
                        ->first();
                    if (!isset($estdoc_section)) {
                        $estdoc_section = new estdoc_section([
                            'estdocid' => $doc->id,
                            'sysid' => $chapter['SysID'],
                            'name' => $chapter['Caption'],
                            'ordr' => ++$section_ordr,
                        ]);
                        $estdoc_section->save();
                    }
                    $sectionid = $estdoc_section->id;
//                    dd($chapter['Caption'], $estdoc_section, $estdoc_section->id);

                    $i++;
                    $position_npp = -1;
                    $j = 0;
                    $parid = null; //id родительской позиции для текущей позиции сметы

                    foreach ($chapter->Position as $position) {
                        $position_npp++;

                        //print_r('<br>____' . $i . '.' . $chapter['Caption'] . ' ' . ++$j . ' ' . $position['Caption']);
                        //если позиция имеет ресурсы, то она уже не может быть кому-то подчинена
                        if ($position->Resources->count() > 0)
                            $parid = null;

                        $itmtypeid = null;  //1-работа, 2-материал

                        $docitem = new estdocItem([
                            'estdocid' => $doc->id,
                            'parid' => $parid,
                            'sectionid' => $sectionid,
                            'chapter_sysid' => $chapter['SysID'],
                            'chapter_name' => $chapter['Caption'],
                            'header' => $aPositionHeader[$position_npp] ?? '',
                            'title' => $position['Caption'],
                            'identifier' => $position['Identifier'],
                            'number' => $position['Number'],
                            'reasoncode' => $position['Code'],
                            'units' => $position['Units'],
                            'sysid' => $position['SysID'],
                            'quantity' => $position['Quantity'] ?? null,
                            'quantity_Fx' => $position->Quantity['Fx'] ?? null,
                            'quantity_Precision' => $position->Quantity['Precision'] ?? null,
                            'pricelevel' => $position['PriceLevel'],
                            'DBComment' => $position['DBComment'],
                            'DBFlags' => $position['DBFlags'],
                            'PzSync' => $position['PzSync'],
                            'Vr2001' => $position['Vr2001'],
                        ]);
                        if (isset($position->Quantity['Result']))
                            $docitem->qty = str_replace(',', '.', $position->Quantity['Result']);

                        if (isset($position->PriceCurr['EM'])) {
                            $docitem->pricecurr_em = str_replace(',', '.', $position->PriceCurr['EM']);
                            $itmtypeid = 1;
                        }

                        if (isset($position->PriceCurr['MT'])) {
                            $docitem->pricecurr_mt = str_replace(',', '.', $position->PriceCurr['MT']);
                            //$docitem->price = $docitem->pricecurr_mt;
                            $itmtypeid = 2;
                        }
                        $docitem->price = $docitem->pricecurr_em ?? $docitem->pricecurr_mt ?? null;

                        if (isset($position->PriceCurr->Fx['MT']))
                            $docitem->pricecurr_fx_mt = $position->PriceCurr->Fx['MT'];

                        if (isset($docitem->qty) and isset($docitem->price)) {
                            //для позиций без ресурсов здесь указаны прямые затраты
                            $docitem->itmsum = round($docitem->qty * $docitem->price, 2);
                            $docitem->drct_sum = round($docitem->qty * $docitem->price, 2);
                        }

                        if (isset($position->PriceCurr['Comment'])) {
                            $docitem->pricecurr_comment = $position->PriceCurr['Comment'];
                        }

                        if (isset($docitem->Vr2001)) {
                            $wtrb_item = wtrb_item::where('bookid', 1)
                                ->where('code', trim($docitem->Vr2001))
                                ->select('naclcurr', 'plancurr')->first();
                            if (isset($wtrb_item)) {
                                $docitem->nacl_pcnt = $wtrb_item->naclcurr;
                                $docitem->sp_pcnt = $wtrb_item->plancurr;
                            }
                        }
                        //признак наличия ресурсов
                        $docitem->rescnt = $position->Resources->count();
                        // если есть ресурсы, то считаем работой
                        $docitem->itmtypeid = $itmtypeid ?? ($docitem->rescnt > 0 ? 1 : null); //тип строки
                        if ($docitem->itmtypeid == 1) {
                            //Если текущая строка = Работа, то считаем ее самостоятельной позицией
                            $docitem->parid = null;
                        }

                        if ($docitem->itmtypeid == 2 and isset($parid)) {
                            // если позиция - Материал, то не будем сохранять ее как позицию, а сохраним как материал для предыдущей позиции
                            $ressum = round($docitem->qty * $docitem->price, 2);
                            $itm_resource = new estdocitm_resource([
                                'estdocitmid' => $parid,
                                'kind' => 'Mat',
                                'name' => $docitem->title,
                                'code' => $docitem->reasoncode ?? '-',
                                'units' => $docitem->units,
                                'qty' => $docitem->qty,
                                'price' => $docitem->price,
                                'options' => $docitem->options,
                                'useitmqty' => 0,  //кол-во задано полностью. Не требуется умножать на кол-во из позиции
                                'ressum' => $ressum,
                            ]);
                            $itm_resource->save();

                            //увеличим сумму материалов для соотв. позиции
                            $paritm = estdocItem::find($parid);
                            //скорректируем итоги родительской записи
                            $paritm->mat_sum += $ressum;
                            $paritm->drct_sum += $ressum;
                            $paritm->tot_sum += $ressum;
                            $paritm->save();

                        } else {
                            $docitem->save();
                        }

                        //обработка коэффициентов -----------------------------
                        // todo: (!) коэффициенты могут быть привязаны не только к позиции типа "Работа",
                        // но и к позиции типа "Материал". Например "Пассажирский лифт WBSS P1000-2S90-23/23"
                        // В таком случае возникает проблема из-за предыдущего решения прикреплять отдельные материалы
                        // к предыдущей работе как ресурс. Ресурс не имеет своей таблицы коэффициентов
                        //
                        // Временно, в таком случае не будем сохранять коэффициенты вообще.

                        if (isset($docitem->id)) {
                            $k_oz = 1;
                            $k_em = 1;
                            if (isset($position->Koefficients)) {
                                foreach ($position->Koefficients->K as $itm) {
                                    //dd($itm->PriceCurr, $itm->PriceCurr['Value']);
                                    //var_dump(str_replace(',', '.', $itm->PriceCurr['Value']));
                                    //var_dump($itm['Quantity']);
                                    $value_oz = 1;
                                    if (isset($itm['Value_OZ']))
                                        $value_oz = str_replace(',', '.', $itm['Value_OZ']);

                                    $value_em = 1;
                                    if (isset($itm['Value_EM']))
                                        $value_em = str_replace(',', '.', $itm['Value_EM']);

                                    $itm_koeff = new estdocitm_koeff([
                                        'estdocitmid' => $docitem->id,
                                        'code' => $itm['Code'],
                                        'name' => $itm['Caption'],
                                        'options' => $itm['Options'],
                                        'value_oz' => $value_oz,
                                        'value_em' => $value_em,
                                        'level' => $itm['Level'],
                                    ]);
                                    $itm_koeff->save();
                                    $k_oz = (isSet($itm_koeff->value_oz) ? $k_oz * $itm_koeff->value_oz : $k_oz);
                                    $k_em = (isSet($itm_koeff->value_em) ? $k_em * $itm_koeff->value_em : $k_em);
                                }
                            }
                        }

                        //обрабтка ресурсов: <Resources>
                        if (1 == 1) {

                            $res_count = 0;
                            $itmsum = 0;
                            $mat_sum = 0;
                            $fot_r_sum = 0;
                            $fot_m_sum = 0;
                            $exp_m_sum = 0;

                            if ($position->Resources->count() > 0)
                                $parid = $docitem->id;    //для последующих позиций;

                            //var_dump($docitem->title, $position->Resources->count(), $parid);

                            //проход по ресурсам позиции -----
                            $resource_kinds = ['Tzr', 'Tzm', 'Mch', 'Mat'];

                            foreach ($resource_kinds as $kind) {

                                $tItm = $position->Resources->{$kind};
                                if (isset($tItm)) {
                                    $res_count++;
                                    foreach ($tItm as $itm) {
                                        //dd($itm->PriceCurr, $itm->PriceCurr['Value']);
                                        //var_dump(str_replace(',', '.', $itm->PriceCurr['Value']));
                                        //var_dump($itm['Quantity']);

                                        $itm_resource = new estdocitm_resource([
                                            'estdocitmid' => $docitem->id,
                                            'kind' => $kind,
                                            'name' => $itm['Caption'],
                                            'code' => $itm['Code'],
                                            'units' => $itm['Units'],
                                            'options' => $itm['Options'],
                                            'workclass' => $itm['WorkClass'],
                                            'attribs' => $itm['Attribs'] ?? '',
                                            'cargo' => $itm['Cargo'],
                                            'mass' => $itm['Mass'] ?? null,
                                            'k_oz' => $k_oz ?? 1,
                                        ]);

                                        if (isset($itm['Quantity'])) {
                                            $itm_resource->qty = str_replace(',', '.', $itm['Quantity']);
                                        }

                                        $calcQty = $docitem->qty * $itm_resource->qty;

                                        if (isset($itm->PriceCurr)) {
                                            $itm_resource->pricecurr_value = str_replace(',', '.', $itm->PriceCurr['Value'] ?? null);
                                            $itm_resource->price = $itm_resource->pricecurr_value;

                                            if ($kind == 'Tzr') {
                                                $itm_resource->fot_r = round($itm_resource->pricecurr_value * $calcQty, 2);
                                                $itm_resource->fot_r = round($itm_resource->fot_r * $k_oz, 2);
                                            }

                                            if (isset($itm->PriceCurr['ZM'])) {
                                                $itm_resource->pricecurr_zm = str_replace(',', '.', $itm->PriceCurr['ZM'] ?? null);

                                                if ($kind == 'Mch') {
                                                    $itm_resource->exp_m = round($itm_resource->pricecurr_value * $calcQty, 2);
                                                    $itm_resource->exp_m = round($itm_resource->exp_m * $k_em, 2);

                                                    $itm_resource->fot_m = round($itm_resource->pricecurr_zm * $calcQty, 2);
                                                    $itm_resource->fot_m = round($itm_resource->fot_m * $k_oz, 2);
                                                }
                                            }
                                            $itm_resource->pricecurr_comment = str_replace(',', '.', $itm->PriceCurr['Comment'] ?? null);
                                        }
                                        if ($itm_resource->attribs <> 'Deleted'
                                            and isset($itm_resource->qty)
                                            and isset($itm_resource->price))
                                            $itmsum += round($calcQty * $itm_resource->price, 2);

                                        $fot_r_sum += $itm_resource->fot_r ?? 0;
                                        $fot_m_sum += $itm_resource->fot_m ?? 0;
                                        $exp_m_sum += $itm_resource->exp_m ?? 0;

                                        if ($kind == 'Mat')
                                            $mat_sum += round($itm_resource->qty * $itm_resource->price * $docitem->qty, 2);

                                        $itm_resource->save();
                                    }
                                }
                            }

                            if ($res_count > 0) {

                                $docitem->rescnt = $res_count;
                                $docitem->itmsum = $itmsum;
                                $docitem->fot_r_sum = $fot_r_sum;
                                $docitem->fot_m_sum = $fot_m_sum;
                                $docitem->exp_m_sum = $exp_m_sum;
                                $docitem->fot_sum = $fot_r_sum + $fot_m_sum;

                                $docitem->mat_sum = $mat_sum;
                                //прямые затраты
                                $docitem->drct_sum = $docitem->fot_r_sum + $docitem->exp_m_sum + $docitem->mat_sum;

                                $docitem->nacl_sum = round($docitem->fot_sum * $docitem->nacl_pcnt / 100, 2);
                                $docitem->sp_sum = round($docitem->fot_sum * $docitem->sp_pcnt / 100, 2);

                                //Всего
                                $docitem->tot_sum = $docitem->drct_sum + $docitem->nacl_sum + $docitem->sp_sum;

                                $docitem->save();
                                //dd($docitem);
                            }

                            $docDrctSum += $docitem->itmsum ?? 0;
                            $docNaclSum += $docitem->nacl_sum ?? 0;
                            $docSPSum += $docitem->sp_sum ?? 0;
                        }


                        // обрабтка работ: <WorksList>  ------------------------------------
                        if (1 == 1) {

                            if ($position->WorksList->count() > 0) {
                                $wrk_count = 0;

                                //проход по работам позиции -----
                                foreach ($position->WorksList->Work as $itm) {
                                    //dd($itm->Caption);

                                    $itm_work = new estdocitm_work([
                                        'estdocitmid' => $docitem->id,
                                        'name' => $itm['Caption'],
                                    ]);
                                    $itm_work->save();
                                    $wrk_count++;
                                }

                                if ($wrk_count > 0) {

                                    $docitem->workcnt = $wrk_count;
                                    $docitem->save();
                                    //dd($docitem);
                                }
                            }
                        }
                        //end of обрабтка работ: <WorksList>  ------------------------------------

                    }  //foreach ($chapter->Position as $position)
                } //foreach ($xml->Chapters->Chapter as $chapter)

                //Сохранение итогов ------
                $doc->drct_sum = $docDrctSum;
                $doc->nacl_sum = $docNaclSum;
                $doc->sp_sum = $docSPSum;
                $doc->docsum = $doc->drct_sum + $doc->nacl_sum + $doc->sp_sum;

                $doc->save();
                //dd($doc);
                //---------------------------------------------------------------


                // - Актуализация справочника видов работ -----------------------
                if ($xml->ImplemActs->count() > 0) {
                    foreach ($xml->ImplemActs->ImplemAct as $itm) {

                        $name = trim($itm['Caption']);
                        $docnum = trim($itm['Number']);
                        $docdate = $itm['MakingDate'];
                        $period_year = $itm['Year'];
                        $period_month = $itm['Month'];
                        $period_daystart = $itm['DayStart'];
                        $period_dayfinish = $itm['DayFinish'];
                        $actindex = $itm['ActIndex'];

                        $compiled_info = null;
                        $compiled_by = null;
                        $inspected_by = null;
                        if (isset($itm->GsDocSignatures)) {
                            foreach ($itm->GsDocSignatures->Item as $signer) {
                                if ($signer['Caption'] == 'Дата составления сметы')
                                    $compiled_info = $signer['Value'];

                                if ($signer['Caption'] == 'Составил')
                                    $compiled_by = $signer['ID'];

                                if ($signer['Caption'] == 'Проверил')
                                    $inspected_by = $signer['ID'];
                            }
                        }

                        $implemdoc = implemdoc::where([
                            ['estdocid', $doc->id],
                            ['docnum', $docnum],
                            ['docdate', $docdate],
                        ])->first();
                        if (!isset($implemdoc)) {
                            $implemdoc = new implemdoc([
                                'estdocid' => $doc->id,
                                'docnum' => $docnum,
                                'docdate' => $docdate,
                            ]);
                        }
                        $implemdoc->period_year = $period_year;
                        $implemdoc->period_month = $period_month;
                        $implemdoc->period_daystart = $period_daystart;
                        $implemdoc->period_dayfinish = $period_dayfinish;
                        $implemdoc->actindex = $actindex;
                        $implemdoc->compiled_info = $compiled_info;
                        $implemdoc->compiled_by = $compiled_by;
                        $implemdoc->inspected_by = $inspected_by;

                        $implemdoc->save();
                    }
                }
            }
            //---------------------------------------------------------------


            $resMsg .= $lf . now() . ' - обработка завершена: '
                . "Тип данных: $dataGenerator"
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
