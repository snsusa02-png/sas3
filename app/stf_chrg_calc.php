<?php

namespace App;

use App\Imports\invoiceImport;
use App\Traits\DeleteTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Maatwebsite\Excel\Facades\Excel;

class stf_chrg_calc extends Model
{
    use DeleteTrait;

    static public $prefix = 'stf_chrg_calc';
    static public $sysobjid = 1213;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')
            ->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')
            ->withDefault();
    }

    public function ref_sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'ref_sysobjid')
            ->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();;
    }

    public function org_charge()
    {
        return $this->hasOne(org_charge::class, 'id', 'orgchargeid')
            ->withDefault()
            ->with('chargetype');
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lockdate = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lockdate)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->wrkbegdate < $lockdate);
            }
        }
        return false;
    }

    static public function search_cond($params)
    {
        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'stf_name') {
                        $sc = $sc . " and concat(os.lname,' ',ifnull(os.fname,''),' ',ifnull(os.mname,'')) like '%" . mb_strtoupper($val) . "%'";

                    } elseif ($key == 'chargetype_name') {
                        $sc = $sc . " and concat(ct.name,' ',ifnull(oc.notes,' ')) like '%" . mb_strtoupper($val) . "%'";

                    } elseif ($key == 'charge_dir') {
                        $sc = $sc . " and ct.dir=$val";

                    } elseif ($key == 'orgid' or $key == 's_orgid') {
                        $sc .= " and os.orgid={$val}";

                    } elseif ($key == 's_dir') {
                        $sc .= " and ct.dir={$val}";

                    } elseif ($key == 's_ym') {
                        $date = date_create($val . '-01')->format('Y-m-d');
                        //dd($date);

                        $date = $date ?? date_create()->format('d-m-Y');
                        $begdate = date_create($date)->format('Y-m-01');   //Первый день месяца
                        $enddate = date_create($date)->format('Y-m-t');    //Последний день месяца

                        $sc .= " and scc.forbegdate <= '" . date_create($enddate)->format('Y-m-d') . "'"
                            . " and scc.forEndDate >= '" . date_create($begdate)->format('Y-m-d') . "'";

                    } elseif ($key == 's_period') {
                        $sc = $sc . " and concat(scc.forbegdate, '..', scc.forEndDate) = '" . $val . "'";
                    }
                }

            }
        }
        //Log::info($sc);
        return $sc;
    }

    public static function import_001($file, $rec)
    {
        //Импорт сумм удержаний сотрудников из xlsx-файла в формате ___, идентификация сотрудника по номеру карты (IdCard)
        // Ожидаемые колонки:
        // Начало периода	Конец периода	Номер карты оплаты	Сумма


        $userid = \Auth::user()->id;
        $result = new Result();

        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);

        //Названия полей ожидаем в первой строке
        // Начало периода	Конец периода	Номер карты оплаты	Сумма
        $fields = $array[0];
//        dd($fields
//            , in_array('Начало периода', $fields)
//            and in_array('Конец периода', $fields)
//            and in_array('Номер карты оплаты', $fields)
//            and in_array('Сумма', $fields)
//        );
        if (!(
            in_array('Начало периода', $fields)
            and in_array('Конец периода', $fields)
            and in_array('Номер карты оплаты', $fields)
            and in_array('Сумма', $fields)
        )) {
            $result->err = 1;
            $result->msg = 'Файл должен содержать колонки "Начало периода",	"Конец периода", "Номер карты оплаты", "Сумма"!';
            $rec->result = $result;
            //dd($result);
            return $rec;
        }

        //$extsysid = $rec->extsysid;

        //перевернем колонки
        $fld_idx = array_flip($fields);
        //dd($fld_idx);

        $items_add_cnt = 0; //кол-во новых записей
        $items_upd_cnt = 0; //кол-во обновленных записей
        $items_skp_cnt = [0, 0, 0]; //кол-во пропущенных/не идентифицированных записей
        $skp_lst = ['', '', ''];  // массив с данными пропущенных записей

        for ($i = 1; $i < count($array); $i++) {

            $dshift = $array[$i][$fld_idx['Начало периода']];
            $begdate = date('Y-m-d', strtotime("1899-12-30 +{$dshift} days"));
            //dd($dshift, $begdate);
            //$begdate = date_format(date_create_from_format('d.m.Y', $date), 'Y-m-d');

            $dshift = $array[$i][$fld_idx['Конец периода']];
            //$enddate = date_format(date_create_from_format('d.m.Y', $date), 'Y-m-d');
            $enddate = date('Y-m-d', strtotime("1899-12-30 +{$dshift} days"));
            //dd($dshift, $enddate);


            $cardnum = $array[$i][$fld_idx['Номер карты оплаты']];
            $sum = $array[$i][$fld_idx['Сумма']];

            //$kpp = (isset($fld_idx['kpp'])) ? $array[$i][$fld_idx['kpp']] : null;

            $date = $begdate;
            //dd($begdate, $enddate, $cardnum, $sum);

            if (isset($cardnum)) {

                // определим идентификатор авто/спецтехники по коду внешней системы
                $staffid = idcard_staff::from('idcard_staffs as ics')
                        ->join('idcards as ic', function ($join) {
                            $join->on('ic.id', '=', 'ics.cardid')
                                ->whereRaw("curdate() between ics.begdate and ifnull(ics.enddate, curdate())");
                        })
                        ->where('ic.num', $cardnum)
                        ->first()->staffid ?? null;
                //dd($cardnum, $staffid);

                if (isset($staffid)) {

                    $orgstaff = orgstaff::where('id', $staffid)->first();
                    //dd($orgstaff->orgid);

                    $orgchargeid = org_charge::where([
                            'orgid' => $orgstaff->orgid
                            , 'chargetypeid' => 53
                        ])
                            ->first()->id ?? null;
                    //dd($orgchargeid);

                    if (isset($orgchargeid)) {

                        // может быть уже добавляли?
                        // локальный идентификатор порции данных, характеризующий источник, дату данных и положение порции в файле
                        $lineid = '01' . ':' . $staffid . ':' . $begdate . ':' . $enddate;
                        //. ':' . $i;
                        //dd($lineid);

                        $rec = stf_chrg_calc::where([
                            'staffid' => $staffid,
                            'orgchargeid' => $orgchargeid,
                            'forbegdate' => $begdate,
                            'forenddate' => $enddate,
                            'notes' => $lineid])
                            ->first();
//                        dd ($rec);

                        if (!isset($rec)) {

                            $rec = new self([
                                'staffid' => $staffid,
                                'orgchargeid' => $orgchargeid,
                                'forbegdate' => $begdate,
                                'forenddate' => $enddate,
                                'notes' => $lineid,
                                'docdate' => $begdate,
                                'charge_dir' => -1,
                                'charge_qty' => 1,
                                'charge_price' => $sum,
                                'charge_sum' => $sum,
                            ]);
                            ++$items_add_cnt;
                        } else
                            ++$items_upd_cnt;
                        //dd ($rec);

                        //$org->name = $array[$i][$fld_idx['name'] ?? ''] ?? '';
                        //необязательно-присутствующие поля. Обновляем только при наличии - чтобы не затереть предыдущее значение
//                if (isset($fld_idx['address']))
//                    $org->address = $array[$i][$fld_idx['address']];

                        //dd($rec);
                        $rec->save();
                    } else {
                        ++$items_skp_cnt[2];
                        $org = org::where('id', $orgstaff->orgid)->first();
                        $skp_lst[2] .= ', ' . $cardnum
                            . ' - ' . $orgstaff->id . ': "' . $orgstaff->lname . ' ' . $orgstaff->fname . ' ' . $orgstaff->mname .'"'
                            . ' - ' . $orgstaff->orgid . ': "' . $org->name . '"';
                    }
                } else {
                    ++$items_skp_cnt[1];
                    $skp_lst[1] .= ', ' . $cardnum;
                }
            } //else ++$items_skp_cnt[0];
        }

        $result->msg .= "- добавлено записей: {$items_add_cnt}" . PHP_EOL;
        $result->msg .= "- изменено записей: {$items_upd_cnt}" . PHP_EOL;
        $all_skp_cnt = array_sum($items_skp_cnt);
        $tclass = ($all_skp_cnt > 0) ? 'text-danger' : '';
        $result->msg .= "- пропущено записей: <span class='{$tclass}'>{$all_skp_cnt}, в том числе:" . PHP_EOL;;
        if ($items_skp_cnt[0] > 0)
            $result->msg .= "-- не указан номер карты: {$items_skp_cnt[0]}" . PHP_EOL;
        if ($items_skp_cnt[1] > 0) {
            $skp_lst[1] = mb_substr($skp_lst[1], 2);
            $result->msg .= "-- номер карты не сопоставлен с сотрудником: {$items_skp_cnt[1]}:"
                . PHP_EOL . "{$skp_lst[1]}" . PHP_EOL;
        }
        if ($items_skp_cnt[2] > 0) {
            $skp_lst[2] = PHP_EOL . str_replace(',', PHP_EOL, mb_substr($skp_lst[2], 2));
            $result->msg .= "-- тип удержания не задан в организации сотрудника: {$items_skp_cnt[2]} {$skp_lst[2]}" . PHP_EOL;
        }
        $result->msg .= "</span>" . PHP_EOL;

        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }

    public static function import_002($file, $rec)
    {
        //Импорт сумм удержаний сотрудников из xlsx-файла в формате ___,
        // идентификация сотрудника по коду 1С-Бухгалтерии (ExtSystemID=5)
        // Ожидаемые колонки:
        // [Работники организаций]	Код	Период начало	Период конец    [Авансы]	[Алименты] ...

        $userid = \Auth::user()->id;
        $result = new Result();

        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);

        //Названия полей ожидаем в первой строке
        // Работники организаций	Код	Авансы	Алименты	Период начало	Период конец
        $fields = $array[0];
//        dd($fields
//            , in_array('Работники организаций', $fields)
//            and in_array('Код', $fields)
//            and in_array('Авансы', $fields)
//            and in_array('Период начало', $fields)
//            and in_array('Период конец', $fields)
//        );
        //Проверка на наличие колонок с данными привязки ( к сотруднику, к периоду)
        if (!(1 == 1
            and in_array('Код', $fields)
            //and in_array('Авансы', $fields)
            and in_array('Период начало', $fields)
            and in_array('Период конец', $fields)
        )) {
            $result->err = 1;
            $result->msg = 'Файл должен содержать колонки "Работники организаций",	"Код"
            , "Период начало", "Период конец"!';
            $rec->result = $result;
            //dd($result);
            return $rec;
        }

        // из списка заголовков колонок удалим элементы с пустыми значениями
        //        dd($fields, array_diff($fields, ["", null]));
        $fields = array_diff($fields, ["", null]);

        //Настройка на колонки удержаний
        $chargetypes = []; // итоговый массив, импортируемых удержаний
        $all_chargetypes = chargetype::where([
            'active' => 1,
            'dir' => -1,
        ])
            ->select('id', 'name')
            ->orderby('ordr')
            ->orderby('name')
            ->get();
        //dd($all_chargetypes);

        //сформируем рабочий массив удержаний
        foreach ($all_chargetypes as $chrg) {
            // если название удержания встречается в заголовках колонок файла
            if (in_array($chrg->name, $fields))
                $chargetypes[] = [$chrg->id, $chrg->name];
        }
        //dd($chargetypes, count($chargetypes));
        // проверка, что нашли что-то знаакомое
        if (count($chargetypes) == 0) {
            $result->err = 1;
            $result->msg = 'Файл должен содержать колонки с известными удержаниями!';
            $rec->result = $result;
            //dd($result);
            return $rec;
        }

        //перевернем колонки
        $fld_idx = array_flip($fields);
        //dd($fields, $fld_idx);

        $items_add_cnt = 0; //кол-во новых записей
        $items_upd_cnt = 0; //кол-во обновленных записей
        $items_del_cnt = 0; //кол-во удаленных записей
        $items_skp_cnt = 0; //кол-во пропущенных/не идентифицированных записей
        $skp_extids = '';   //список идентификаторов пропущенных/не идентифицированных записей

        for ($i = 1; $i < count($array); $i++) {

            $dshift = $array[$i][$fld_idx['Период начало']];
            $begdate = date('Y-m-d', strtotime("1899-12-30 +{$dshift} days"));
            //dd($dshift, $begdate);
            //$begdate = date_format(date_create_from_format('d.m.Y', $date), 'Y-m-d');

            $dshift = $array[$i][$fld_idx['Период конец']];
            //$enddate = date_format(date_create_from_format('d.m.Y', $date), 'Y-m-d');
            $enddate = date('Y-m-d', strtotime("1899-12-30 +{$dshift} days"));
            //dd($dshift, $enddate);

            $extid = $array[$i][$fld_idx['Код']];

            if (isset($extid)) {

                // определим идентификатор сотрудника по коду внешней системы
                $staffid = objextid::where([
                        'sysobjid' => 121,  //orgstaff
                        'extsysid' => 5,    // 1с-Бухгалтерия
                        'extid' => $extid,
                    ])->first()->objid ?? null;
                //dd($extid, $staffid);

                if (isset($staffid)) {

                    $orgstaff = orgstaff::where('id', $staffid)->first();
                    //dd($orgstaff->orgid);

                    foreach ($chargetypes as $chargetype) {
                        //dd("Item=" . $chargetype[0] . ", Value=" . $chargetype[1], is_null( $chargetype[0]));

                        // Если id начисления задано и название начисления находится в списке заголовков
                        if (!is_null($chargetype[0])
                            and in_array($chargetype[1], $fields)) {

                            $orgchargeid = org_charge::where([
                                    'orgid' => $orgstaff->orgid
                                    , 'chargetypeid' => $chargetype[0]
                                ])
                                    ->first()->id ?? null;
                            //dd($orgstaff->orgid, $orgchargeid);

                            if (isset($orgchargeid)) {

                                $sum = $array[$i][$fld_idx[$chargetype[1]]];
                                //$sum2 = $array[$i][$fld_idx['Алименты']];
                                //dd($begdate, $enddate, $extid, $chargetype[1], $sum);

                                // локальный идентификатор порции данных, характеризующий источник, дату данных и положение порции в файле
                                $lineid = '02' . ':' . $staffid . ':' . $begdate . ':' . $enddate . ':' . $orgchargeid;
                                //. ':' . $i;
                                //dd($lineid);

                                if (!is_null($sum)) {

                                    // может быть уже добавляли?
                                    $rec = stf_chrg_calc::where([
                                        'staffid' => $staffid,
                                        'orgchargeid' => $orgchargeid,
                                        'forbegdate' => $begdate,
                                        'forenddate' => $enddate,
                                        'notes' => $lineid])
                                        ->first();
                                    //dd ($rec);

                                    if (!isset($rec)) {

                                        $rec = new self([
                                            'staffid' => $staffid,
                                            'orgchargeid' => $orgchargeid,
                                            'forbegdate' => $begdate,
                                            'forenddate' => $enddate,
                                            'notes' => $lineid,
                                            'docdate' => $begdate,
                                            'charge_dir' => -1,
                                            'charge_qty' => 1,
                                            'charge_price' => $sum,
                                            'charge_sum' => $sum,
                                        ]);
                                        ++$items_add_cnt;
                                    } else {
                                        $rec->charge_sum = $sum;
                                        ++$items_upd_cnt;
                                    }
                                    //dd ($rec);

                                    //$org->name = $array[$i][$fld_idx['name'] ?? ''] ?? '';
                                    //необязательно-присутствующие поля. Обновляем только при наличии - чтобы не затереть предыдущее значение
                                    //                if (isset($fld_idx['address']))
                                    //                    $org->address = $array[$i][$fld_idx['address']];
                                    //$kpp = (isset($fld_idx['kpp'])) ? $array[$i][$fld_idx['kpp']] : null;

                                    //dd($rec);
                                    $rec->save();
                                } else {
                                    //Удалим, если ранее создали
                                    $tmp = stf_chrg_calc::where([
                                        'staffid' => $staffid,
                                        'orgchargeid' => $orgchargeid,
                                        'forbegdate' => $begdate,
                                        'forenddate' => $enddate,
                                        'notes' => $lineid])
                                        ->delete();
                                    $items_del_cnt += $tmp;
                                }
                            } else ++$items_skp_cnt;
                        }
                    }
                } else {
                    ++$items_skp_cnt;
                    $skp_extids .= '; ' . $extid;
                }
            } else {
                ++$items_skp_cnt;
            }
        }

        $result->msg .= "Импорт сумм удержаний сотрудников:" . PHP_EOL . PHP_EOL;
        $result->msg .= "- добавлено записей: {$items_add_cnt}" . PHP_EOL;
        $result->msg .= "- изменено записей: {$items_upd_cnt}" . PHP_EOL;
        $result->msg .= "- удалено записей: {$items_del_cnt}" . PHP_EOL;
        $tclass = ($items_skp_cnt > 0) ? 'text-danger' : '';
        $result->msg .= "- пропущено записей: <span class='{$tclass}'>{$items_skp_cnt}</span>" . PHP_EOL;
        if ($items_skp_cnt) {
            $skp_extids = mb_substr($skp_extids, 2);
            $result->msg .= "-- коды пропущенных записей: <span class='text-secondary small'>{$skp_extids}</span>" . PHP_EOL;
        }

        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }

}
