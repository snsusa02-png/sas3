<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\Model;

class regnum_src extends Model
{
    static public $prefix = 'regnum_srcs';
    static public $sysobjid = 158;

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

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function category()
    {
        return $this->hasOne(contract_category::class, 'id', 'categoryid')->withDefault();
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
                        $sc .= " and rns.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (rns.active=1 or rns.id={$val})";

                    } elseif ($key == 'categoryid') {
                        $sc .= " and rns.contract_categoryid={$val}";

                    } elseif ($key == 'ownorgid') {
                        $sc .= " and rns.ownorgid={$val}";

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

            $lst = self::from('regnum_srcs as rns')
                ->join('orgs as oo','oo.id','rns.ownorgid')
                ->whereRaw($sc)
                ->select('rns.id', db::raw("concat(rns.name,' / ',oo.name) as name"))
                ->orderBy('rns.ordr', 'asc')
                ->orderBy('rns.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

}
