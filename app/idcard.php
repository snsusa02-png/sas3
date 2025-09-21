<?php

namespace App;

use App\Imports\invoiceImport;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class idcard extends Model
{
    //
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'idcards';
    static public $sysobjid = 1960;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }


    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = '/ №' . $this->num . ' - ' . $this->name . '.  ' . $this->org->name;
            return $rslt;
        } else
            return null;
    }

    static public function search_cond($params)
    {

        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, для того, чтобы работать с ней
        $userid = \Auth::user()->id;
//        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
//            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=m.acsid and uac.userid={$userid})";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'orgid') {
                        $sc .= " and ic.orgid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and ic.active={$val}";

                    } elseif ($key == 's_name') {
                        $sc .= " and concat(m.num,' ',m.name) like '%{$val}%'";

                    } elseif ($key == 'in_idcard_staffs') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from idcard_staffs as ics where ics.cardid=ic.id)";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;
    }


    static public function lstFor($params)
    {
        //2021-04-08 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            if (1 == 1) {
                $lst = self::from('idcards as ic')
                    ->whereRaw($sc)
                    ->select('id', 'num as name')
                    ->orderby('num')
                    ->get()
                    ->pluck('name', 'id')->toArray();
            }
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-12-04 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . self::$prefix . $hash);
            return Cache::remember('lstFor_' . self::$prefix . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей idcards
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['ic.name', 'asc'], ['ic.num', 'asc']];

            $recs = self::from('idcards as ic')
                ->Join('orgs as o', 'o.id', 'ic.orgid')
                ->whereRaw($sc)
                ->select($fields);

            foreach ($sorts as $sort) {
                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
            }

            $recs = $recs->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

    //2025-09-21
    static public function addOrUpdate($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $rec = self::where($search_params)->first();

            if (!isset($rec)) {
                $rec = new self($search_params);
            }
            $rec->fill($set_params);
            $rec->save();

            return $rec;
        }
        return null;
    }

    //2025-09-21
    public static function import_001($file, $rec)
    {
        //Импорт Номеров IDCard и их текущих держателей
        // Колонки: "Номер карты", "ФИО"

        $orgid  = 31;   // Временное решение - привязываем все к САС ДВ
        $userid = \Auth::user()->id;
        $result = new Result();

        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);

        //Названия полей ожидаем в первой строке
        $fields = $array[0];
        // из списка заголовков колонок удалим элементы с пустыми значениями
        $fields = array_diff($fields, ["", null]);
        //dd($fields);

        if (!(
            in_array('ФИО', $fields)
            and in_array('Номер карты', $fields)
        )) {
            $result->err = 1;
            $result->msg = 'Файл должен содержать колонки "ФИО", "Номер карты"!';
            $rec->result = $result;
            return $rec;
        }

        //перевернем колонки
        $fld_idx = array_flip($fields);
        //dd($fld_idx);

        $items_add_cnt = 0; //кол-во новых записей
        $items_upd_cnt = 0; //кол-во обновленных записей
        $items_skp_cnt = 0; //кол-во пропущенных записей
        $skp_list = '';   //список ФИО пропущенных/не идентифицированных записей

        $holder_add_cnt = 0; //кол-во новых записей
        $holder_upd_cnt = 0; //кол-во обновленных записей
        $holder_skp_cnt = 0; //кол-во пропущенных записей

        for ($i = 1; $i < count($array); $i++) {

            //$orgid =
            $cardnum = $array[$i][$fld_idx['Номер карты']];
            $stfname = $array[$i][$fld_idx['ФИО']];
            //dd($cardnum, $stfname);   //Адушев Илья

            if (isset($stfname) and isset($cardnum)) {

                $idcard = idcard::addOrUpdate(
                    ['orgid' => $orgid, 'num' => $cardnum],
                    ['orgid' => $orgid, 'num' => $cardnum
                        , 'updated_by' => $userid
                        , 'updated_at' => now()
                    ]);
                if ($idcard->updated_at == $idcard->created_at)
                    ++$items_add_cnt;
                else
                    ++$items_upd_cnt;
                //dd($idcard);

                if (isset($idcard) and isset($stfname)) {

                    //Определим сотрудника - Ключем считаем полное ФИО
                    $orgstaff = orgstaff::where([
                        'name' => $stfname,
                    ])->first();
                    //dd($orgstaff);

                    //попробуем искать только по Фамилии и имени
                    if (!isset($orgstaff)) {
                        $orgstaff = orgstaff::whereRaw("concat(lname, ' ', fname) = '{$stfname}'")->first();
                        //dd(123, $orgstaff);
                    }

                    if (isset($orgstaff)) {
                        // узнаем, кто является текущим держателем этой карты сейчас
                        $idcard_staff = idcard_staff::where('cardid', $idcard->id)
                            ->whereRaw("curdate() between begdate and ifnull(enddate, curdate())")
                            ->first();
                        //dd($idcard->id, $idcard_staff);

                        if(!isset($idcard_staff)){
                            // никому не принадлежит сейчас - привяжем к $orgstaff->id
                            $idcard_staff = new idcard_staff(
                                [ 'cardid'=>$idcard->id
                                , 'staffid'=>$orgstaff->id
                                , 'begdate'=>today()
                                , 'enddate'=> null
                                ]);
                            $idcard_staff->save();
                            ++$holder_add_cnt;
                        }
                        elseif ($idcard_staff->staffid == $orgstaff->id){
                            // текущий держатель это сотрудник взятый из файла - ничего не делаем
                            null;
                            ++$holder_skp_cnt;
                        }else{
                            //Если текущий держатель не является сотрудником взятым из файла,
                            // то ограничим период текущего держателя и создадим запись о новом держателе этой карты
                            //dd(today()->modify('-1 day'));
                            $idcard_staff->enddate = today()->modify('-1 day');
                            $idcard_staff->save();
                            //dd($idcard_staff);
                            ++$holder_upd_cnt;

                            //
                            $idcard_staff = new idcard_staff(
                                [ 'cardid'=>$idcard->id
                                    , 'staffid'=>$orgstaff->id
                                    , 'begdate'=>today()
                                    , 'enddate'=> null
                                ]);
                            $idcard_staff->save();
                            ++$holder_add_cnt;
                        }
                        //dd($idcard_staff);
                    } else {
                        ++$items_skp_cnt;
                        $skp_list .= '; ' . $stfname;
                    }
                }
            } else{
                ++$items_skp_cnt;
                $skp_list .= '; ' . $stfname;
            }
        }

        $result->msg .= "- добавлено записей о картах: {$items_add_cnt}" . PHP_EOL;
        $result->msg .= "- изменено записей о картах: {$items_upd_cnt}" . PHP_EOL;
        $result->msg .= "- пропущено записей о держателях: {$items_skp_cnt}" . PHP_EOL;
        if ($items_skp_cnt) {
            $skp_list = mb_substr($skp_list, 1);
            $result->msg .= "-- пропущенные записи: <span class='text-secondary small'>{$skp_list}</span>" . PHP_EOL;
        }

        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }
}
