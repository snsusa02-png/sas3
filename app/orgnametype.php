<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class orgnametype extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'orgnametypes';
    static public $sysobjid = 1621;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function subtypes()
    {
        return $this->hasMany(doctype::class, 'parent_id', 'id');
    }


    static public function search_cond($params)
    {

        //пользователь ДОЛЖЕН иметь доступ к категории информации, указанной в записи о типе документа
        $userid = \Auth::user()->id;
        //$sc = "exists (select 1 from user_acs as uac where uac.acsid=ont.acsid and uac.userid={$userid})";
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
                        $sc .= " and ont.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (ont.active=1 or ont.id={$val})";

                    } elseif ($key == 'name') {
                        $words = explode(" ", $val);
                        if (count($words) > 0) {
                            //$sc .= " and 1=1";
                            $sc .= ' and ((1=1';
                            foreach ($words as $word) {
                                $sc .= " and ont.name like '%" . $word . "%'";
                            }
                            $sc .= ') )';
                        }

                    }
                }

            }
        }
        //Log::info($sc);
        //dd($sc);
        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-06-10 SNS. универсальный конструктор массива с id, name записей
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('orgnametypes as ont')
                ->whereRaw($sc)
                ->select('ont.id', 'ont.name')
                ->orderBy('ont.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);

            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей orgnametypes
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'uac.*';
            //Log::info(json_encode($fields));

            $recs = self::from('orgnametypes as ont')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('ont.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


}
