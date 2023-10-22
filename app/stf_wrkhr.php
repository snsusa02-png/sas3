<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class stf_wrkhr extends Model
{
    use DeleteTrait;

    static public $prefix = 'stf_wrkhr';
    static public $sysobjid = 1227;

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

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();;
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

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи


        //Забудем связанный кэш -----------------
        self::cache_clear();
    }

    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи

        //удалим записи из stf_chrg_calcs, для которых уже нет соответствующих записей в stf_wrkhrs
        stf_chrg_calc::from('stf_chrg_calcs as scc')
            ->where('ref_sysobjid', self::$sysobjid)
            ->whereRaw("not exists (select 1 from stf_wrkhrs as swh where swh.id=scc.ref_objid)")
            ->delete();

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

                    if ($key == 'stf_name') {
                        $sc = $sc . " and concat(os.lname,' ',ifnull(os.fname,''),' ',ifnull(os.mname,'')) like '%" . mb_strtoupper($val) . "%'";

                    } elseif ($key == 'orgid' or $key == 's_orgid') {
                        $sc .= " and os.orgid={$val}";

                    } elseif ($key == 's_ym0') {
                        $date = date_create($val . '-01')->format('Y-m-d');
                        //dd($date);

                        $date = $date ?? date_create()->format('d-m-Y');
                        $begdate = date_create($date)->format('Y-m-01');   //Первый день месяца
                        $enddate = date_create($date)->format('Y-m-t');    //Последний день месяца

                        $sc .= " and scc.forbegdate <= '" . date_create($enddate)->format('Y-m-d') . "'"
                            . " and scc.forEndDate >= '" . date_create($begdate)->format('Y-m-d') . "'";

                    } elseif ($key == 's_ym') {
                        $prts = explode('-', $val, 2);
                        $sc .= " and swh.yr = '" . $prts[0] . "' and swh.mn ='" .$prts[1] . "'" ;
                    }
                }

            }
        }
        //Log::info($sc);
        return $sc;
    }

}
