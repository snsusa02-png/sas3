<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Cache;
use Illuminate\Support\Facades\DB;

class contract_category extends Model
{
    static public $prefix = 'contract_categories';

    use DeleteTrait;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    static public function lstActive()
    {
        //Cache::forget(self::$prefix . '_lstTypes_' );
        $data = Cache::remember(self::$prefix . '_lstActive_' . 0, now()->addMinutes(15)
            , function ()  {
                $lst = self::select('id', 'name')
                    ->where('active', 1);
                $lst = $lst->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
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
                        $sc .= " and cc.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (cc.active=1 or cc.id={$val})";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-06-10 SNS. универсальный конструктор массива с id, name записей
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('contract_categories as cc')
                ->whereRaw($sc)
                ->select('cc.id', 'name')
                //->orderBy('cc.ordr', 'asc')
                ->orderBy('cc.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

}
