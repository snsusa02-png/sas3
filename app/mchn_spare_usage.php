<?php

namespace App;

use App\Imports\invoiceImport;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class mchn_spare_usage extends Model
{
    static public $prefix = 'mchn_spare_usages';
    static public $sysobjid = 489;

    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function machine()
    {
        return $this->hasOne(machine::class, 'id', 'machineid')->withDefault();
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->operdate . ' ' . $this->spare_name;
            return $rslt;
        } else
            return null;
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lock_before = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lock_before)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->wrkdate < $lock_before);
            }
        }
        return false;
    }

    public function admindelete()
    {
        $result = new Result;
        //Удаляем себя вместе с дочками
        try {
            DB::transaction(function () {
//                $this->mr_opers()->delete();
//                $this->files()->delete();  //TODO: ? ->deleteOne() ? Так как не удаляется файл с диска
//
//                //удалим записи из obj_finopers, для которых уже нет соответствующих записей в mr_opers
//                obj_finoper::from('obj_finopers as f')
//                    ->where('sysobjid', 1107)
//                    ->whereRaw("not exists (select 1 from mr_opers as mro where mro.id=f.objid)")
//                    ->delete();

                return parent::delete();
            });
        } catch (\Exception $e) {
            $result->err = 1;
            $result->msg = 'Ошибка удаления записи: ' . $e->getMessage();
        }
        return $result;
    }

    public static function min_wrkdate()
    {
        //определим минимально-допустимую дату для поля wrkdate
        $min_date = sysobj_lockdate::where('sysobjid', self::$sysobjid)->first()->lock_before ?? null;
        if (isset($min_date)) {
            return $min_date;
        }
        return null;
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи


        //Забудем связанный кэш -----------------
        self::cache_clear();
    }

    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function cache_clear($rec = null)
    {
        //для вызова при изменении / удалении записей
        if (isset($rec)) {
        }
        Cache::forget('mchn_raids.years');
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

                    if ($key == 'machineid') {
                        $sc .= " and msu.machineid={$val}";

                    } elseif ($key == 'operdate') {
                        $sc .= " and msu.operdate='{$val}'";

                    } elseif ($key == 'active') {
                        $sc .= " and msu.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (msu.active=1 or msu.id={$val})";

                    }
                }

            }
        }
        //Log::info($sc);
        return $sc;
    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name ключевых работ для вида работ строит. объекта
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('mchn_spare_usages as msu')
                ->whereRaw($sc)
                ->select('msu.id', 'msu.spare_name as name')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;

    }

//    static public function getFor($s_params, $fields = null, $sorts = null)
//    {
//        //2021-04-30 SNS. универсальный конструктор коллекции из записей mchn_opertypes
//        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
//        // fields - массив со списком возвращаемых полей таблицы
//
//        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {
//
//            $sc = self::search_cond($s_params);
//            //Log::info($sc);
//            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'mot.*';
//            //Log::info(json_encode($fields));
//
//            $recs = self::from('mchn_raids as mr')
//                ->whereRaw($sc)
//                ->select($fields);
//
//            $sorts = $sorts ?? [['mr.wrkdate', 'asc']];   //по умолчанию
//            foreach ($sorts as $sort) {
//                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
//            }
//
//            $recs = $recs->get();
//
//            //dd($sc,$recs);
//            return $recs;
//        } else
//            return null;
//    }


//    static public function rfr_finopers($id)
//    {
//
//        foreach (mr_oper::where('mr_id', $id)->get() as $oper) {
//            mr_oper::rfr_finopers($oper);
//        }
//    }

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

    public static function clone($id)
    {
        // Клонируем указанную запись mchn_spare_usages со всем содержимым

        $result = new Result;
        $userid = \Auth::user()->id;

        $src_msu = self::find($id);
        if (!isset($src_msu)) {
            $result->err = 1;
            $result->msg = 'Исходная запись не найдена!';
            return $result;
        }

        $new_msu = $src_msu->replicate();
        //дата записи не может быть ранее sysobj_lockdates.lock_before
        $new_msu->operdate = max($new_msu->operdate, sysobj_lockdate::mindate(self::$sysobjid));
        $new_msu->created_by = $userid;
        $new_msu->updated_by = $userid;
        $new_msu->save();
        mchn_spare_usage::on_update($new_msu);

        $result->obj = $new_msu->toArray();

        return $result;
    }

    public static function import_001($file, $rec)
    {
        //Импорт списка организаций из xlsx-файла в формате ___

        $userid = \Auth::user()->id;
        $result = new Result();

        $array = Excel::toArray(new invoiceImport, $file);
        $array = $array[0];
        //dd($array);

        //Названия полей ожидаем в первой строке
        $fields = $array[0];
        //dd($fields);
        if (!(
            in_array('Код', $fields)
            and in_array('Выручка, ', $fields)
            and in_array('ДатаДляВыгрузки', $fields)
        )) {
            $result->err = 1;
            $result->msg = 'Файл должен содержать колонки "Код", "Выручка,", "ДатаДляВыгрузки"!';
            $rec->result = $result;
            return $rec;
        }

        $extsysid = $rec->extsysid;

        //удалим пустые названия колонок
        foreach ($fields as $k => $v)
            if (empty($v)) unset($fields[$k]);

        //перевернем колонки
        $fld_idx = array_flip($fields);

        $items_add_cnt = 0; //кол-во новых записей
        $items_upd_cnt = 0; //кол-во обновленных записей
        $items_skp_cnt = 0; //кол-во пропущенных/не идентифицированных записей

        for ($i = 1; $i < count($array); $i++) {

            $code = $array[$i][$fld_idx['Код']];
            $machine_name = $array[$i][$fld_idx['Покупатель']];
            $sum = $array[$i][$fld_idx['Выручка, ']];
            $date = $array[$i][$fld_idx['ДатаДляВыгрузки']];
            $date = date_format(date_create_from_format('d.m.Y', $date), 'Y-m-d');
            //$kpp = (isset($fld_idx['kpp'])) ? $array[$i][$fld_idx['kpp']] : null;
            //dd($machine_name, $code, $sum, $date);

            if (isset($code)) {

                // определим идентификатор авто/спецтехники по коду внешней системы
                $machineid = objextid::where([
                        'sysobjid' => 482,  //machine
                        'extsysid' => $extsysid,
                        'extid' => $code,
                    ])->first()->objid ?? null;

                if (isset($machineid)) {

                    // может быть уже добавляли?
                    $lineid = $extsysid.':'.$date.':'.$i; // локальный идентификатор порыии данных, характеризующий источник, дату данных и положение порции в файле
                    $rec = mchn_spare_usage::where([
                        'machineid' => $machineid,
                        'operdate' => $date,
                        'notes' => $lineid])->first();
                    //dd ($rec);

                    if (!isset($rec)) {

                        $rec = new self([
                            'machineid' => $machineid,
                            'operdate' => $date,
                            'spare_sum' => $sum,
                            'qty' => 1,
                            'price' => $sum,
                            'notes' => $lineid
                        ]);
                        ++$items_add_cnt;
                    } else
                        ++$items_upd_cnt;

                    //$org->name = $array[$i][$fld_idx['name'] ?? ''] ?? '';
                    //необязательно-присутствующие поля. Обновляем только при наличии - чтобы не затереть предыдущее значение
//                if (isset($fld_idx['address']))
//                    $org->address = $array[$i][$fld_idx['address']];

                    //dd($rec);
                    $rec->save();

                }else ++$items_skp_cnt;
            }else ++$items_skp_cnt;
        }

        $result->msg .= "- добавлено записей: {$items_add_cnt}" . PHP_EOL;
        $result->msg .= "- изменено записей: {$items_upd_cnt}" . PHP_EOL;
        $tclass = ($items_skp_cnt > 0)?'text-danger':'';
        $result->msg .= "- пропущено записей: <span class='{$tclass}'>{$items_skp_cnt}</span>" . PHP_EOL;

        $rec->result = $result;
        //--------------------------------------------------------------------------

        return $rec;
    }

}


