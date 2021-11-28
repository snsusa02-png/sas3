<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class cargoload_point extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'cargoload_point';
    static public $sysobjid = 1115;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid');
    }

    public function wrh()
    {
        return $this->hasOne(wrh::class, 'id', 'wrhid')->withDefault();
    }


    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('firstread_at');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
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

                    if ($key == 'buildobjid') {
                        $sc .= " and clp.buildobjid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and clp.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (clp.active=1 or clp.id={$val})";
                    }
                }

            }
        }
        //Log::info($sc);
        return $sc;
    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('cargoload_points as clp')
                ->whereRaw($sc)
                ->select('clp.id', 'clp.name')
                ->orderBy('name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей cargoload_points
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $recs = self::from('cargoload_points as clp')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('clp.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


}
