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
//            , in_array('Конец периода', $fields)
//            , in_array('Номер карты оплаты', $fields)
//            , in_array('Сумма', $fields)
//            , '---'
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

        $extsysid = $rec->extsysid;

        //перевернем колонки
        $fld_idx = array_flip($fields);
        //dd($fld_idx);

        $items_add_cnt = 0; //кол-во новых записей
        $items_upd_cnt = 0; //кол-во обновленных записей
        $items_skp_cnt = 0; //кол-во пропущенных/не идентифицированных записей

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
                    } else ++$items_skp_cnt;
                } else ++$items_skp_cnt;
            } else ++$items_skp_cnt;
        }

        $result->msg .= "- добавлено записей: {$items_add_cnt}" . PHP_EOL;
        $result->msg .= "- изменено записей: {$items_upd_cnt}" . PHP_EOL;
        $tclass = ($items_skp_cnt > 0) ? 'text-danger' : '';
        $result->msg .= "- пропущено записей: <span class='{$tclass}'>{$items_skp_cnt}</span>" . PHP_EOL;

        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }

}
