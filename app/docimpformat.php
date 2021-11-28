<?php

namespace App;

use App\Imports\invoiceImport;
use App\Imports\tabelMultiImport;
use App\Traits\DeleteTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Config;
use DateTime;
use App\Events\notifyEvent;

class docimpformat extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'docimpformats';
    static public $sysobjid = 925;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function doctype()
    {
        return $this->hasOne(doctype::class, 'id', 'doctypeid');
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid');
    }


    public static function active()
    {
        return static::where('active', true)->get();
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }


    public static function import_001($file, $rec)
    {
        //Импорт счета на оплату из xlsx-файла в формате ООО Либерти


        $userid = \Auth::user()->id;
        $result = new Result();

        //$fileuri = "/home/vagrant/code/basco/storage/app/public/files/879/1/Ведомость ресурсов материалы Отопление ИТП.xlsx";
        //Excel::import(new invoiceImport(), $fileuri, null, \Maatwebsite\Excel\Excel::XLSX);

        //Excel::import(new invoiceImport(), request()->file('doc'));
//            $collection = Excel::toCollection(new invoiceImport, request()->file('doc'));
//            dd($collection);

        //$array = Excel::toArray(new invoiceImport, request()->file('doc'));
        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);
        $doc_num_date = $array[9][1];

        if (!isset($doc_num_date) or !substr($doc_num_date, 1, 16) == 'Счет на оплату №') {
            $result->err = 1;
            $result->msg = 'Файл не соответствует формату "Счет на оплату от ООО Либерти"!';

            $rec->result = $result;
            return $rec;
        }

        $str = explode(' ', $doc_num_date);
        $docnum = $str[4];
        $docmonth_str = $str[7];
        $month_names = [
            'января' => 1,
            'февраля' => 2,
            'марта' => 3,
            'апреля' => 4,
            'мая' => 5,
            'июня' => 6,
            'июля' => 7,
            'августа' => 8,
            'сентября' => 9,
            'октября' => 10,
            'ноября' => 11,
            'декабря' => 12,
        ];
        $docmonth = $month_names[$str[7]] ?? '0';
        $docdate = date_create_from_format('Y-m-d', $str[8] . '-' . $docmonth . '-' . $str[6]);
        $doc_year = $docdate->format('Y');
        $docdate = $docdate->format('Y-m-d');
        //dd($docdate, $doc_year);

        //$result->msg .= "- со справочником Номенклатуры по Коду и ЕИ: {$cnt}" . PHP_EOL;

        //--- поиск организации по справочнику ----------------------------
        $str = explode(' ', $array[13][6]);

        $key = array_search('ИНН', $str);
        $org_inn = str_replace(',', '', $str[$key + 1] ?? '');

        $key = array_search('КПП', $str);
        $org_kpp = str_replace(',', '', $str[$key + 1] ?? '');

        if (isset($org_inn) and isset($org_kpp)) {
            $suporg = org::where(['inn' => $org_inn, 'kpp' => $org_kpp])->first();
        }
        if (isset($suporg)) {
            $suporg = org::find($rec->orgid); //129 - ООО Либерти
        }
        //dd($str, $key, $org_inn, $org_kpp, $suporg);
        //$suporgname = ;
        //-----------------------------------------------------------------

        //--- поиск своей организации по справочнику ----------------------
        //-----------------------------------------------------------------

        //dd($array);

        //--- поиск записи об этом документе -------------
        $invoice = invoice::where([
            'orgid' => $suporg->id,
            'doctypeid' => 1,
            'docnum' => $docnum,
        ])
            ->whereRaw("year(docdate)={$doc_year}")
            ->first();

        if (!isset($invoice)) {
            $invoice = new invoice([
                'doctypeid' => 1,
                'orgid' => $suporg->id,
                'docnum' => $docnum,
                'docdate' => $docdate,
                'created_by' => $userid,
            ]);
        }
        $invoice->ownorgid = $rec->ownorgid;   //взяли из формы
        $invoice->reason = $array[19][6] ?? ''; //Основание для счета (договор поставки)
        $invoice->save();
        //------------------------------------------------
        //dd($str, $docnum, $docdate, $suporg->name, $suporg->id, $invoice);

        //Состав документа ----------------------------------------------------------------

        //пометим существующие строки, для последующего удаления неиспользованных ---
        invoice_item::where('invoiceid', $invoice->id)->update(['updated_by' => 0]);

        //dd($array[22]);
        $items_cnt = 0;
        $docsum = 0;
        for ($i = 22; $i < count($array); $i++) {

            //ориентируемся на колонку с количеством. Если она не пуста - работаем
            if (isset($array[$i][24])) {

                $itmname = ($array[$i][8] == '#NULL!') ? null : $array[$i][8];
                //Название - обязательно
                if (is_null($itmname))
                    continue;

                $items_cnt++;

                $ordr = (($array[$i][1] == '#NULL!') ? null : $array[$i][1]) ?? $items_cnt;

                //попробуем найти прежнюю запись
                $item = invoice_item::where([
                    'invoiceid' => $invoice->id,
                    'ordr' => $ordr,
                ])->first();
                if (!isset($item)) {
                    $item = new invoice_item([
                        'invoiceid' => $invoice->id,
                        'ordr' => $ordr,
                        'created_by' => $userid,
                    ]);
                }
                $item->code = ($array[$i][3] == '#NULL!') ? null : $array[$i][3];
                $item->itmname = $itmname;
                $item->qty = ($array[$i][24] == '#NULL!') ? null : $array[$i][24];
                $item->unit = ($array[$i][28] == '#NULL!') ? null : $array[$i][28];
                $item->unittypeid = unittype::idByName($item->unit);
                $item->price = ($array[$i][31] == '#NULL!') ? null : $array[$i][31];
                $item->itmsum = ($array[$i][36] == '#NULL!') ? null : $array[$i][36];

                if (isset($item->code)) {
                    // попробуем найти номенклатуру по коду поставщика
                    $item->refitmid = objextid::objid_by_extsysid_extid($rec->extsysid, 105, $item->code);
                }

                if (!isset($item->refitmid) and isset($item->unittypeid)) {
                    //Добавим позицию в спр-к Номенклатуры
                    $item->refitmid = refitem::newItemByExtID($rec->extsysid, $item->code, [
                        'name' => $item->itmname,
                        'unittypeid' => $item->unittypeid,
                        'unit' => $item->unit,
                        'price' => $item->price,
                        'created_by' => $userid,
                        'updated_by' => $userid,
                    ]);
                }

                $item->updated_by = $userid;
                $item->save();

                $docsum += $item->itmsum;
            } else
                break;

        }
        $result->msg .= "- позиций в документе: {$items_cnt}" . PHP_EOL;
        $result->msg .= "- сумма документа: {$docsum}" . PHP_EOL;

        //удалим незатронутые записи ---
        invoice_item::where(['invoiceid' => $invoice->id, 'updated_by' => 0])->delete();


        //извлечем нужные данные из блока полсе состава документа ------------------
        for ($i = $i; $i < count($array); $i++) {

            if (stripos($array[$i][1], 'Оплатить не позднее') !== false) {
                $str = explode(' ', $array[$i][1]);
                $enddate = date_create_from_format('d.m.Y', $str[3])->format('Y-m-d');
                $invoice->enddate = $enddate;
            }

        }

        $invoice->docsum = $docsum;
        $invoice->updated_by = $userid;
        $invoice->save();

        $rec->invoiceid = $invoice->id;
        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }

    public static function import_002($file, $rec)
    {
        //Импорт счета на оплату из xlsx-файла в формате ООО МПК


        $userid = \Auth::user()->id;
        $result = new Result();

        //$fileuri = "/home/vagrant/code/basco/storage/app/public/files/879/1/Ведомость ресурсов материалы Отопление ИТП.xlsx";
        //Excel::import(new invoiceImport(), $fileuri, null, \Maatwebsite\Excel\Excel::XLSX);

        //Excel::import(new invoiceImport(), request()->file('doc'));
//            $collection = Excel::toCollection(new invoiceImport, request()->file('doc'));
//            dd($collection);

        //$array = Excel::toArray(new invoiceImport, request()->file('doc'));
        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);

        $doc_num_date = $array[10][1];
//        dd($doc_num_date,$array);


        if (!isset($doc_num_date) or !substr($doc_num_date, 1, 16) == 'Счет на оплату №') {
            $result->err = 1;
            $result->msg = 'Файл не соответствует формату "Счет на оплату от ООО Либерти"!';

            $rec->result = $result;
            return $rec;
        }

        $str = explode(' ', $doc_num_date);
        $docnum = $str[4];
        $docmonth_str = $str[7];
        $month_names = [
            'января' => 1,
            'февраля' => 2,
            'марта' => 3,
            'апреля' => 4,
            'мая' => 5,
            'июня' => 6,
            'июля' => 7,
            'августа' => 8,
            'сентября' => 9,
            'октября' => 10,
            'ноября' => 11,
            'декабря' => 12,
        ];
        $docmonth = $month_names[$str[7]] ?? '0';
        $docdate = date_create_from_format('Y-m-d', $str[8] . '-' . $docmonth . '-' . $str[6]);
        $doc_year = $docdate->format('Y');
        $docdate = $docdate->format('Y-m-d');
        //dd($docdate, $doc_year);

        //$result->msg .= "- со справочником Номенклатуры по Коду и ЕИ: {$cnt}" . PHP_EOL;

        //--- поиск организации по справочнику ----------------------------
        $str = explode(' ', $array[14][5]);

        $key = array_search('ИНН', $str);
        $org_inn = str_replace(',', '', $str[$key + 1] ?? '');

        $key = array_search('КПП', $str);
        $org_kpp = str_replace(',', '', $str[$key + 1] ?? '');

        if (isset($org_inn) and isset($org_kpp)) {
            $suporg = org::where(['inn' => $org_inn, 'kpp' => $org_kpp])->first();
        }
        //dd($str, $key, $org_inn, $org_kpp, $suporg);

        if (!isset($suporg)) {
            $suporg = org::find($rec->orgid); //129 - ООО Либерти
        }
        //dd($str, $key, $org_inn, $org_kpp, $suporg);
        //$suporgname = ;
        //-----------------------------------------------------------------

        //--- поиск своей организации по справочнику ----------------------
        //-----------------------------------------------------------------

        //dd($array);

        //--- поиск записи об этом документе -------------
        $invoice = invoice::where([
            'orgid' => $suporg->id,
            'doctypeid' => 1,
            'docnum' => $docnum,
        ])
            ->whereRaw("year(docdate)={$doc_year}")
            ->first();

        if (!isset($invoice)) {
            $invoice = new invoice([
                'doctypeid' => 1,
                'orgid' => $suporg->id,
                'docnum' => $docnum,
                'docdate' => $docdate,
                'created_by' => $userid,
            ]);
        }
        $invoice->ownorgid = $rec->ownorgid;   //взяли из формы
        $invoice->reason = $array[18][5] ?? ''; //Основание для счета (договор поставки)
        //dd($invoice);
        $invoice->save();
        //------------------------------------------------
        //dd($str, $docnum, $docdate, $suporg->name, $suporg->id, $invoice);

        //Состав документа ----------------------------------------------------------------

        //пометим существующие строки, для последующего удаления неиспользованных ---
        invoice_item::where('invoiceid', $invoice->id)->update(['updated_by' => 0]);

        //dd($array[22]);
        $items_cnt = 0;
        $docsum = 0;

        for ($i = 22; $i < count($array); $i++) {

            //ориентируемся на колонку с количеством. Если она не пуста - работаем
            if (isset($array[$i][20])) {

                $itmname = ($array[$i][6] == '#NULL!') ? null : $array[$i][6];
                //Название - обязательно
                if (is_null($itmname))
                    continue;

                $items_cnt++;

                $ordr = (($array[$i][1] == '#NULL!') ? null : $array[$i][1]) ?? $items_cnt;

                //попробуем найти прежнюю запись
                $item = invoice_item::where([
                    'invoiceid' => $invoice->id,
                    'ordr' => $ordr,
                ])->first();
                if (!isset($item)) {
                    $item = new invoice_item([
                        'invoiceid' => $invoice->id,
                        'ordr' => $ordr,
                        'created_by' => $userid,
                    ]);
                }

                $item->code = ($array[$i][3] == '#NULL!') ? null : $array[$i][3];
                $item->itmname = $itmname;
                $item->qty = ($array[$i][20] == '#NULL!') ? null : $array[$i][20];
                $item->unit = ($array[$i][23] == '#NULL!') ? null : $array[$i][23];
                $item->unittypeid = unittype::idByName($item->unit);
                $item->price = ($array[$i][25] == '#NULL!') ? null : $array[$i][25];
                $item->itmsum = ($array[$i][29] == '#NULL!') ? null : $array[$i][29];

                if (isset($item->code)) {
                    // попробуем найти номенклатуру по коду поставщика
                    $item->refitmid = objextid::objid_by_extsysid_extid($rec->extsysid, 105, $item->code);
                }

                if (!isset($item->refitmid) and isset($item->unittypeid)) {
                    //Добавим позицию в спр-к Номенклатуры
                    $item->refitmid = refitem::newItemByExtID($rec->extsysid, $item->code, [
                        'name' => $item->itmname,
                        'unittypeid' => $item->unittypeid,
                        'unit' => $item->unit,
                        'price' => $item->price,
                        'created_by' => $userid,
                        'updated_by' => $userid,
                    ]);
                }

                $item->updated_by = $userid;
                //dd($item);
                $item->save();

                $docsum += $item->itmsum;
            } else
                break;

        }
        $result->msg .= "- позиций в документе: {$items_cnt}" . PHP_EOL;
        $result->msg .= "- сумма документа: {$docsum}" . PHP_EOL;

        //удалим незатронутые записи ---
        invoice_item::where(['invoiceid' => $invoice->id, 'updated_by' => 0])->delete();


        //извлечем нужные данные из блока после состава документа ------------------
//        for ($i = $i; $i < count($array); $i++) {

//            if (stripos($array[$i][1], 'Оплатить не позднее') !== false) {
//                $str = explode(' ', $array[$i][1]);
//                $enddate = date_create_from_format('d.m.Y', $str[3])->format('Y-m-d');
//                $invoice->enddate = $enddate;
//            }

//        }

        $invoice->docsum = $docsum;
        $invoice->updated_by = $userid;
        $invoice->save();

        $rec->invoiceid = $invoice->id;
        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }

    public static function import_003($file, $rec)
    {
        //Импорт счета на оплату из xlsx-файла в формате ООО "Торговый Дом Центр Снабжения"

        $userid = \Auth::user()->id;
        $result = new Result();

        //$fileuri = "/home/vagrant/code/basco/storage/app/public/files/879/1/Ведомость ресурсов материалы Отопление ИТП.xlsx";
        //Excel::import(new invoiceImport(), $fileuri, null, \Maatwebsite\Excel\Excel::XLSX);

        //Excel::import(new invoiceImport(), request()->file('doc'));
//            $collection = Excel::toCollection(new invoiceImport, request()->file('doc'));
//            dd($collection);

        //$array = Excel::toArray(new invoiceImport, request()->file('doc'));
        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);

        $doc_num_date = $array[13][1];
        //dd($doc_num_date,$array);


        if (!isset($doc_num_date) or !substr($doc_num_date, 1, 16) == 'Счет на оплату №') {
            $result->err = 1;
            $result->msg = 'Файл не соответствует указанному формату!';

            $rec->result = $result;
            return $rec;
        }

        $str = explode(' ', $doc_num_date);
        $docnum = $str[4];
        $docmonth_str = $str[7];
        $month_names = [
            'января' => 1,
            'февраля' => 2,
            'марта' => 3,
            'апреля' => 4,
            'мая' => 5,
            'июня' => 6,
            'июля' => 7,
            'августа' => 8,
            'сентября' => 9,
            'октября' => 10,
            'ноября' => 11,
            'декабря' => 12,
        ];
        $docmonth = $month_names[$str[7]] ?? '0';
        $docdate = date_create_from_format('Y-m-d', $str[8] . '-' . $docmonth . '-' . $str[6]);
        $doc_year = $docdate->format('Y');
        $docdate = $docdate->format('Y-m-d');
        //dd($docdate, $doc_year);

        //$result->msg .= "- со справочником Номенклатуры по Коду и ЕИ: {$cnt}" . PHP_EOL;

        //--- поиск организации по справочнику ----------------------------
        //dd($array);
        $str = explode(' ', $array[15][7]);

        $key = array_search('ИНН', $str);
        $org_inn = str_replace(',', '', $str[$key + 1] ?? '');

        $key = array_search('КПП', $str);
        $org_kpp = str_replace(',', '', $str[$key + 1] ?? '');

        if (isset($org_inn) and isset($org_kpp)) {
            $suporg = org::where(['inn' => $org_inn, 'kpp' => $org_kpp])->first();
        }
        //dd($str, $key, $org_inn, $org_kpp, $suporg);

        if (!isset($suporg)) {
            $suporg = org::find($rec->orgid); //271 - ООО ТД
        }
        //dd($str, $key, $org_inn, $org_kpp, $suporg);
        //$suporgname = ;
        //-----------------------------------------------------------------

        //--- поиск своей организации по справочнику ----------------------
        //-----------------------------------------------------------------

        //dd($array);

        //--- поиск записи об этом документе -------------
        $invoice = invoice::where([
            'orgid' => $suporg->id,
            'doctypeid' => 1,
            'docnum' => $docnum,
        ])
            ->whereRaw("year(docdate)={$doc_year}")
            ->first();

        if (!isset($invoice)) {
            $invoice = new invoice([
                'doctypeid' => 1,
                'orgid' => $suporg->id,
                'docnum' => $docnum,
                'docdate' => $docdate,
                'created_by' => $userid,
            ]);
        }
        $invoice->ownorgid = $rec->ownorgid;   //взяли из формы
        //$invoice->reason = $array[18][5] ?? ''; //Основание для счета (договор поставки)
        //dd($array);
        $invoice->save();
        //------------------------------------------------
        //dd($str, $docnum, $docdate, $suporg->name, $suporg->id, $invoice);

        //Состав документа ----------------------------------------------------------------

        //пометим существующие строки, для последующего удаления неиспользованных ---
        invoice_item::where('invoiceid', $invoice->id)->update(['updated_by' => 0]);

        //dd($array[24]);
        $items_cnt = 0;
        $docsum = 0;

        for ($i = 24; $i < count($array); $i++) {

            //ориентируемся на колонку с количеством. Если она не пуста - работаем
            if (isset($array[$i][24])) {

                $itmname = ($array[$i][7] == '#NULL!') ? null : $array[$i][7];
                //Название - обязательно
                if (is_null($itmname))
                    continue;

                $items_cnt++;

                $ordr = (($array[$i][1] == '#NULL!') ? null : $array[$i][1]) ?? $items_cnt;

                //попробуем найти прежнюю запись
                $item = invoice_item::where([
                    'invoiceid' => $invoice->id,
                    'ordr' => $ordr,
                ])->first();
                if (!isset($item)) {
                    $item = new invoice_item([
                        'invoiceid' => $invoice->id,
                        'ordr' => $ordr,
                        'created_by' => $userid,
                    ]);
                }

                $item->code = ($array[$i][3] == '#NULL!') ? null : $array[$i][3];
                $item->itmname = $itmname;
                $item->qty = ($array[$i][24] == '#NULL!') ? null : $array[$i][24];
                $item->unit = ($array[$i][27] == '#NULL!') ? null : $array[$i][27];
                $item->unittypeid = unittype::idByName($item->unit);
                $item->price = ($array[$i][37] == '#NULL!') ? null : $array[$i][37];
                $item->itmsum = ($array[$i][43] == '#NULL!') ? null : $array[$i][43];

                //$item->weight = ($array[$i][29] == '#NULL!') ? null : $array[$i][29]; //вес
                //$item->volume = ($array[$i][33] == '#NULL!') ? null : $array[$i][33]; //объем

                if (isset($item->code)) {
                    // попробуем найти номенклатуру по коду поставщика
                    $item->refitmid = objextid::objid_by_extsysid_extid($rec->extsysid, 105, $item->code);
                }

                if (!isset($item->refitmid) and isset($item->unittypeid)) {
                    //Добавим позицию в спр-к Номенклатуры
                    $item->refitmid = refitem::newItemByExtID($rec->extsysid, $item->code, [
                        'name' => $item->itmname,
                        'unittypeid' => $item->unittypeid,
                        'unit' => $item->unit,
                        'price' => $item->price,
                        'created_by' => $userid,
                        'updated_by' => $userid,
                    ]);
                }

                $item->updated_by = $userid;
                //dd($item);
                $item->save();

                $docsum += $item->itmsum;
            } else
                break;

        }
        $result->msg .= "- позиций в документе: {$items_cnt}" . PHP_EOL;
        $result->msg .= "- сумма документа: {$docsum}" . PHP_EOL;

        //удалим незатронутые записи ---
        invoice_item::where(['invoiceid' => $invoice->id, 'updated_by' => 0])->delete();


        //извлечем нужные данные из блока после состава документа ------------------
//        for ($i = $i; $i < count($array); $i++) {

//            if (stripos($array[$i][1], 'Оплатить не позднее') !== false) {
//                $str = explode(' ', $array[$i][1]);
//                $enddate = date_create_from_format('d.m.Y', $str[3])->format('Y-m-d');
//                $invoice->enddate = $enddate;
//            }

//        }

        $invoice->docsum = $docsum;
        $invoice->updated_by = $userid;
        $invoice->save();

        $rec->invoiceid = $invoice->id;
        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }

    public static function import_004($file, $rec)
    {
        //Импорт данных табеля рабочего времени за день

        $userid = \Auth::user()->id;
        $result = new Result();

        //$fileuri = "/home/vagrant/code/basco/storage/app/public/files/879/1/Ведомость ресурсов материалы Отопление ИТП.xlsx";
        //Excel::import(new invoiceImport(), $fileuri, null, \Maatwebsite\Excel\Excel::XLSX);

        //Excel::import(new invoiceImport(), request()->file('doc'));
//            $collection = Excel::toCollection(new invoiceImport, request()->file('doc'));
//            dd($collection);

        $sheets = Excel::toArray(new invoiceImport, $file);
        //$array = Excel::import(new tabelMultiImport, $file);

        //всегда обрабатываем только последний лист сборника
        $sheet = $sheets[count($sheets) - 1];

        $array = [];
        foreach ($sheet as $row) {
            if (isset($row[2])) {
                $array[] = $row;
            }
        }
        $monthes = Config::get('constants.monthes');

        $month = array_search(mb_strtolower($array[0][3]), $monthes);

        if (!$month) {
            //значение в ячейке не соответствует ни одному из названий месяца
            $result->err = 1;
            $result->msg = 'Файл не соответствует согласованному формату данных о табеле. Не найден месяц!';

            $rec->result = $result;
            return $rec;
        }

        //соглашение №1 - последнее слово в имени файла содержит год
        $file_name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $tmp = explode(' ', $file_name);
        $year = (int)$tmp[count($tmp) - 1] ?? null;

        if ($year == 0) {
            //значение в ячейке не соответствует ни одному из названий месяца
            $result->err = 1;
            $result->msg = 'В конце названия файла должен быть указан год в полном формате (4 цифры)!';

            $rec->result = $result;
            return $rec;
        }
        if ($year < 2020) {
            $result->err = 1;
            $result->msg = 'Год табеля, указанный в названии файла, слишком старый!';

            $rec->result = $result;
            return $rec;
        }

        if ($year > (int)(today()->format('Y'))) {
            $result->err = 1;
            $result->msg = 'Год табеля, указанный в названии файла, еще не наступил!';

            $rec->result = $result;
            return $rec;
        }

        $begtime = '08:00';   //Соглашение: начало работы - 08:00
        $begdate = date_create("{$year}-{$month}-1");
        $enddate = date_format($begdate, "Y-m-t");
        $d_max = date_format($begdate, "t");
        //dd($array, $array[0][3], $begdate, $enddate, $d_max);

        $cur_buildobjid = null;
        $cur_type = null;
        $no_staffid_lst = [];       //массив с фио у которых не указан id
        $bad_staffname_lst = [];    //массив с фио работников, у которых был указан ID, но он не верен
        $bad_staffid = [];      //массив с id работников, у которых был указан ID, но он не верен
        $good_staffid = [];     //массив с валидными id сотрудников

        $no_machineid_lst = [];      //массив с машинами у которых не указан id
        $good_machineid = [];
        $bad_machineid = [];
        $bad_mchnname_lst = [];

        foreach ($array as $row) {

            if (mb_strtolower($row[0]) == 'объект') {
                $cur_buildobjid = $row[1];
                //print('<hr>объект: ' . $cur_buildobjid);
                $cur_type = null;   //сбросим текущий тип, так как следующий раздел может содержать объект, но не содержать тип / ошибка структуры
                continue;
            }
            if ($row[2] == 'рабочие') {
                $cur_type = 'W';
                //print('<br>тип субъектов: ' . $cur_type);
                continue;
            }
            if ($row[2] == 'техника') {
                $cur_type = 'M';
                //print('<br>тип субъектов: ' . $cur_type);
                continue;
            }


            if (isset($row[2]) and isset($cur_buildobjid) and $cur_type == 'W') {
                //var_dump($cur_buildobjid, $row);
                //Работник
                $staffid = $row[1];
                if (!isset($staffid)) {
                    //табельный номер не указан
                    // - либо пропустить и сформировать список таких работников для отправки письмом
                    $no_staffid_lst[] = $row[2];
                    continue;

                    // - либо создать новую запись в orgstaff с orgid = "Неопределенный подрядчик"
                    // Так у СЕ нет стимула внести код сотрудника в excel
                    $orgstaff = new orgstaff([
                        'orgid' => 410,
                        'name' => $row[2]
                    ]);
                    $orgstaff->save();
                    $staffid = $orgstaff->id;
                }
                //dd($row, $staffid);jts_items

                //проверим в своем "Кэше"
                if (!isset($good_staffid[$staffid]) and !isset($bad_staffid[$staffid])) {
                    //такого ID нет ни в списке Хороших, ни в списке Плохих ID
                    // => нужно поискать в базе
                    $staff = orgstaff::find($staffid);
                    if (isset($staff))
                        $good_staffid[$staffid] = 1;
                    else {
                        $bad_staffid[$staffid] = 1;
                        $bad_staffname_lst[] = $row[2];
                        continue;
                    }
                }
                //print_r('<hr> staffid = ' . $staffid . ' <br>');

                //Пройдемся по датам - ячейки с 3 по 33
                for ($d = 0; $d < $d_max; $d++) {

                    $date = clone $begdate;
                    $date->modify("+{$d} day");
                    //print_r('<br>' . $date->format('d.m.Y') . '; ');

                    //Найдем запись о табеле текущего объекта на эту дату
                    $jts = jobtimesheet::where(['buildobjid' => $cur_buildobjid,
                        'docdate' => $date->format('Y-m-d')])->first();

                    $hrs = (int)$row[$d + 3];
                    //print_r(' часов: ' . $hrs);
                    //print_r(' $jts->id: ' . ($jts->id ?? '-'));
                    if ($hrs > 0) {
                        //нужно создать/обновить запись о работе сотрудника

                        // но сначала проверим наличие записи о табеле - и создадим
                        if (!isset($jts)) {
                            //var_dump($date->format('Y-m-d'));
                            $jts = new jobtimesheet([
                                'buildobjid' => $cur_buildobjid,
                                'docdate' => $date->format('Y-m-d'),
                                'inituserid' => $userid,
                                'notes' => 'импорт из файла ' . $file->getClientOriginalName(),
                                'created_at' => now(),
                                'created_by' => $userid,
                            ]);
                            $jts->save();
                        }

                        //поищем запись о сотруднике - выясним сколько рабочих часов записано сейчас
                        $cur_hrs = (int)jts_item::where([
                                'jts_id' => $jts->id,
                                'staffid' => $staffid,
                            ])->sum('wrkhrs') ?? -1;
                        //print_r(' пред. значение часов: ' . $cur_hrs);

                        if ($hrs == $cur_hrs) {
                            //print_r(' - обновление не требуется');
                            continue;
                        }

                        $itm = jts_item::where([
                            'jts_id' => $jts->id,
                            'staffid' => $staffid,
                        ])->first();
                        if (!isset($itm)) {
                            $itm = new jts_item([
                                'jts_id' => $jts->id,
                                'staffid' => $staffid,
                            ]);
                        }

                        $itm->begdt = date_create($jts->docdate)->format('Y-m-d') . ' ' . $begtime;

                        $enddt = DateTime::createFromFormat('Y-m-d H:i', $itm->begdt);
                        date_add($enddt, date_interval_create_from_date_string("{$hrs} hours"));
                        $itm->enddt = $enddt->format('Y-m-d H:i:s');

                        $itm->wrkhrs = $hrs;
                        $itm->notes = 'импорт из файла ' . $file->getClientOriginalName();
                        $itm->updated_at = now();
                        $itm->updated_by = $userid;
                        $itm->save();

                        //удалим другие записи по этому сотруднику/табелю (возможны при ручном(правильном) ведении табеля)
                        jts_item::where([
                            'jts_id' => $jts->id,
                            'staffid' => $staffid,
                        ])
                            ->where('id', '<>', $itm->id)
                            ->delete();

                    } else {
                        //Ячейка Экселя пуста - нужно удалить предыдущие записи на эту дату/сотрудника
                        if (isset($jts)) {
                            //удалить ранее внесенную запись
                            //print("<br>{$date->format('d.m.Y')} удаление лишних записей по сотруднику {$staffid} по табелю {$jts->id}<br>");

                            $ttt = jts_item::where([
                                'jts_id' => $jts->id,
                                'staffid' => $staffid,
                            ])
                                ->delete();
                            //print(' = ' . $ttt);

                        }
                    }
                    // dd($date, $hrs, $jts);
                }

                //var_dump($good_staffid);
                //var_dump($bad_staffid);

            }

            if (isset($row[2]) and isset($cur_buildobjid) and $cur_type == 'M') {
                //Машины и механизмы

                $machineid = $row[1];
                if (!isset($machineid)) {
                    //табельный номер не указан
                    // - пропустить и сформировать список таких машин для отправки письмом
                    $no_machineid_lst[] = $row[2];
                    continue;   //пропускаем
                }

                //такого ID нет ни в списке Хороших, ни в списке Плохих ID
                // => нужно поискать в базе
                $machine = machine::find($machineid);
                if (isset($machine))
                    $good_machineid[$machineid] = 1;
                else {
                    $bad_machineid[$machineid] = 1;
                    $bad_mchnname_lst[] = $row[2];
                    continue;
                }

                //выясним режимы эксплуатации техники
                $mots = mchn_opertype::where('machineid', $machineid)
                    ->select('id')
                    ->get()->pluck('id')->toArray();
                $mot_id = $mots[0] ?? null; //заляпуха. Нужно передавать код режима работы в экселе
                //dd($machineid, $mots, count($mots),$mot_id);


                //Пройдемся по датам - ячейки с 3 по 33
                for ($d = 0; $d < $d_max; $d++) {

                    $date = clone $begdate;
                    $date->modify("+{$d} day");
                    //print_r('<br>' . $date->format('d.m.Y') . '; ');

                    //Найдем запись о табеле текущего объекта на эту дату
                    $jts = jobtimesheet::where(['buildobjid' => $cur_buildobjid,
                        'docdate' => $date->format('Y-m-d')])->first();

                    $hrs = (int)$row[$d + 3];
                    //print_r(' часов: ' . $hrs);
                    //print_r(' $jts->id: ' . ($jts->id ?? '-'));
                    if ($hrs > 0) {
                        //нужно создать/обновить запись о работе техники

                        // но сначала проверим наличие записи о табеле - и создадим
                        if (!isset($jts)) {
                            //var_dump($date->format('Y-m-d'));
                            $jts = new jobtimesheet([
                                'buildobjid' => $cur_buildobjid,
                                'docdate' => $date->format('Y-m-d'),
                                'inituserid' => $userid,
                                'notes' => 'импорт из файла ' . $file->getClientOriginalName(),
                                'created_at' => now(),
                                'created_by' => $userid,
                            ]);
                            $jts->save();
                        }

                        //поищем запись о технике - выясним сколько рабочих часов записано сейчас
                        $cur_hrs = (int)jts_machine::where([
                                'jts_id' => $jts->id,
                                'machineid' => $machineid,
                                'mot_id' => $mot_id,
                            ])->sum('wrkhrs') ?? -1;
                        //print_r(' пред. значение часов: ' . $cur_hrs);

                        if ($hrs == $cur_hrs) {
                            //print_r(' - обновление не требуется');
                            continue;
                        }

                        $itm = jts_machine::where([
                            'jts_id' => $jts->id,
                            'machineid' => $machineid,
                            'mot_id' => $mot_id,
                        ])->first();
                        if (!isset($itm)) {
                            $itm = new jts_machine([
                                'jts_id' => $jts->id,
                                'machineid' => $machineid,
                                'mot_id' => $mot_id,
                            ]);
                        }

                        $itm->begdt = date_create($jts->docdate)->format('Y-m-d') . ' ' . $begtime;

                        $enddt = DateTime::createFromFormat('Y-m-d H:i', $itm->begdt);
                        date_add($enddt, date_interval_create_from_date_string("{$hrs} hours"));
                        $itm->enddt = $enddt->format('Y-m-d H:i:s');

                        $itm->wrkhrs = $hrs;
                        $itm->notes = 'импорт из файла ' . $file->getClientOriginalName();
                        $itm->updated_at = now();
                        $itm->updated_by = $userid;
                        $itm->save();

                        //удалим другие записи по этой машине (возможны при ручном(правильном) ведении табеля)
                        jts_machine::where([
                            'jts_id' => $jts->id,
                            'machineid' => $machineid,
                            'mot_id' => $mot_id,
                        ])
                            ->where('id', '<>', $itm->id)
                            ->delete();

                    } else {
                        //Ячейка Экселя пуста - нужно удалить предыдущие записи на эту дату/машине
                        if (isset($jts)) {
                            //удалить ранее внесенную запись
                            //print("<br>{$date->format('d.m.Y')} удаление лишних записей по машине {$machineid} по табелю {$jts->id}<br>");

                            $ttt = jts_machine::where([
                                'jts_id' => $jts->id,
                                'machineid' => $machineid,
                                'mot_id' => $mot_id,
                            ])
                                ->delete();
                            //print(' = ' . $ttt);

                        }
                    }

                }

            }

            //dd($machineid);

        }

        //dd(10000);
        //--------------------------------------------------------------------------

        if (isset($no_staffid_lst) and count($no_staffid_lst) > 0) {
            //event(new notifyEvent('jobtimesheets.import_xls', 1101, 0, $userid));
            //добавим в колокольчик
            $rcpts = [12, 64];
            $msg = mb_substr(implode(", ", $no_staffid_lst), 0, 300);
            foreach ($rcpts as $to_userid)
                user_notice::addOrUpdate(110111, route('orgstaff.index'), $to_userid
                    , 'Новые сотрудники (из импорта табеля xlsx)'
                    , $msg, now(), null, $userid);
        }

        if (isset($bad_staffname_lst) and count($bad_staffname_lst) > 0) {
            //добавим в колокольчик
            $rcpts = [12, 64];
            $msg = mb_substr(implode(", ", $bad_staffname_lst), 0, 300);
            foreach ($rcpts as $to_userid)
                user_notice::addOrUpdate(110112, route('orgstaff.index'), 12
                    , 'Сотрудники с не существующим ID (из импорта табеля xlsx)'
                    , $msg, now(), null, $userid);
        }


        if (isset($no_machineid_lst) and count($no_machineid_lst) > 0) {
            //event(new notifyEvent('jobtimesheets.import_xls', 1101, 0, $userid));
            //добавим в колокольчик
            $rcpts = [12, 64];
            $msg = mb_substr(implode(", ", $no_machineid_lst), 0, 300);
            foreach ($rcpts as $to_userid)
                user_notice::addOrUpdate(110113, route('machines.index'), $to_userid
                    , 'Новые машины/механизмы (из импорта табеля xlsx)'
                    , $msg, now(), null, $userid);
        }

        if (isset($bad_mchnname_lst) and count($bad_mchnname_lst) > 0) {
            //добавим в колокольчик
            $rcpts = [12, 64];
            $msg = mb_substr(implode(", ", $bad_mchnname_lst), 0, 300);
            foreach ($rcpts as $to_userid)
                user_notice::addOrUpdate(110112, route('machines.index'), 12
                    , 'Машины/механизмы с не существующим ID (из импорта табеля xlsx)'
                    , $msg, now(), null, $userid);
        }


        $result->err = 0;
        $result->msg = 'Импорт завершен.';
        if (count($no_staffid_lst) > 0)
            $result->msg .= ' <hr>Работники без табельного номера: <br>' . implode(", ", $no_staffid_lst) . '<br>';
        if (count($bad_staffname_lst) > 0)
            $result->msg .= ' <hr>Работники c несуществующим табельным номером: <br>' . implode(", ", $bad_staffname_lst) . '<br>';
        if (count($no_machineid_lst) > 0)
            $result->msg .= ' <hr>Машины/механизмы без табельного номера: <br>' . implode(", ", $no_machineid_lst) . '<br>';
        if (count($bad_mchnname_lst) > 0)
            $result->msg .= ' <hr>Машины/механизмы c несуществующим табельным номером: <br>' . implode(", ", $bad_mchnname_lst) . '<br>';

        $rec->result = $result;
        return $rec;

    }
}
