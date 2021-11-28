<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class org_place extends Model
{
    use \App\Traits\DeleteTrait;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
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

                    } elseif ($key == 'orgid') {
                        $sc .= " and p.orgid={$val}";

                    } elseif ($key == 'placetypeid') {
                        $sc .= " and p.placetypeid={$val}";

                    } elseif ($key == 'name') {
                        $sc .= " and p.name like '%{$val}%'";

                    } elseif ($key == 'name_address') {
                        $sc .= " and concat(ifnull(p.address,' '),' ',p.name) like '%{$val}%'";

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

            $lst = self::from('org_places as p')
                ->whereRaw($sc)
                ->select('p.id', db::raw("concat(p.name,' ',ifnull(p.address,' ')) as tname"))
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
        //2021-04-30 SNS. универсальный конструктор коллекции из записей org_places
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'p.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['p.name', 'asc']];

            $recs = self::from('org_places as p')
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
