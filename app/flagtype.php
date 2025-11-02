<?php

namespace App;

use App\Imports\invoiceImport;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Maatwebsite\Excel\Facades\Excel;

class flagtype extends Model
{
    //
    use DeleteTrait;
    //use FilesTrait;

    //protected $fillable = ["orgid"];
    protected $guarded = [];

    protected $table = 'flagtypes';

    static public $prefix = 'flagtypes';
    static public $sysobjid = 9;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'forsysobjid')
            ->withDefault();
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


                    if ($key == 's_name' or $key == 'name') {
                        $sc = $sc . " and ft.mname like '%" . mb_strtoupper($val) . "%'";

                    } elseif ($key == 'active' or $key == 's_active') {
                        $sc .= " and ifnull(ft.active,0) = '{$val}'";

                    } elseif ($key == 'new_or_curr_for_121') {
                        // типы флагов, доступные для sysobjid=121 и еще не установленные для objid=$val.
                        // за исключением текущего значения для $val
                        $sc .= " and ft.forsysobjid=121";
                        $sc .= " and not exists( select 1 from objflags f where f.flagtypeid=ft.id
                            and f.sysobjid=ft.forsysobjid  and f.objid={$val})";

                    } elseif ($key == 'in_cursias') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from cursias as crs where crs.staffid=os.id)";

                    }
                }

            }
        }
        //dd($sc);
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name сотрудников
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('flagtypes as ft')
                ->whereRaw($sc)
                ->select('ft.id', 'ft.name as tname')
                ->orderBy('tname', 'asc')
                ->get()->pluck('tname', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-10-06 SNS. кэшируемый результат списка

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
        //2021-04-30 SNS. универсальный конструктор коллекции из записей orgdeps
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'os.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['os.lname', 'asc'], ['os.fname', 'asc']];

            $recs = self::from('orgstaff as os')
                ->Join('orgs as o', 'o.id', 'os.orgid')
                ->leftJoin('orgposts as op', 'op.id', 'os.postid')
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

}
