<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class place extends Model
{
    //
    use DeleteTrait;
    use FilesTrait;

    //protected $fillable = ["orgid"];
    protected $guarded = [];

    protected $table = 'places';

    static public $prefix = 'places';
    static public $sysobjid = 109;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
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

                    if ($key == 'active') {
                        $sc .= " and p.active={$val}";

                    } elseif ($key == 'for_load') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " p.for_load=1";

                    } elseif ($key == 'for_unload') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " p.for_unload=1";

                    } elseif ($key == 's_name') {
                        $sc .= " and concat(ifnull(p.code,' '),' ',p.name) like '%{$val}%'";

                    } elseif ($key == 'loadplace_in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') .
                            " exists (select 1 from mchn_raids as mr where mr.load_placeid=p.id)";

                    } elseif ($key == 'unloadplace_in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') .
                            " exists (select 1 from mchn_raids as mr where mr.unload_placeid=p.id)";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-10-27 SNS. универсальный конструктор массива с id, name мест
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('places as p')
                ->whereRaw($sc)
                ->select('p.id', db::raw("concat(ifnull(p.code,' '),' ',p.name) as tname"))
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
        //2021-10-27 SNS. кэшируемый результат списка

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
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'p.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['p.name', 'asc']];

            $recs = self::from('places as p')
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
