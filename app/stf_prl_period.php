<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class stf_prl_period extends Model
{
    use DeleteTrait;

    static public $prefix = 'stf_prl_period';
    static public $sysobjid = 0;

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


//    static public function isLocked($id)
//    {
//        //Попадает ли нужная запись в заблокированный период?
//
//        $lockdate = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
//        if (isset($lockdate)) {
//            $rec = self::find($id);
//            if (isset($rec)) {
//                return ($rec->wrkbegdate < $lockdate);
//            }
//        }
//        return false;
//    }

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

                    } elseif ($key == 's_ym') {
                        $date = date_create($val . '-01')->format('Y-m-d');
                        //dd($date);

                        $date = $date ?? date_create()->format('d-m-Y');
                        $begdate = date_create($date)->format('Y-m-01');   //Первый день месяца
                        $enddate = date_create($date)->format('Y-m-t');    //Последний день месяца

                        $sc .= " and spp.begdate <= '" . date_create($enddate)->format('Y-m-d') . "'"
                            . " and spp.endDate >= '" . date_create($begdate)->format('Y-m-d') . "'";
                    }
                }

            }
        }
        //Log::info($sc);
        return $sc;
    }

    static public function lstFor($params)
    {
        //2021-11-19 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('stf_prl_periods as spp')
                ->whereRaw($sc)
                ->select('spp.id', DB::raw("concat(spp.begdate, ' - ', spp.enddate) as tname"))
                ->orderBy('tname', 'asc')
                ->get()->pluck('tname', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-11-19 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember(self::$prefix . '_lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-11-19 SNS. универсальный конструктор коллекции из записей estdocs
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'dw.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['spp.begdate', 'asc']];

            $recs = self::from('stf_prl_periods as spp');

            $recs = $recs->whereRaw($sc)
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

}
