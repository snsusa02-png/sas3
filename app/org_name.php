<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class org_name extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'org_names';
    static public $sysobjid = 1622;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function orgnametype()
    {
        return $this->hasOne(orgnametype::class, 'id', 'nametypeid');
    }

    public function scopeActive($query)
    {
        return $query->whereRaw('active and curdate() between begdate and ifnull(enddate)');
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

                    if ($key == 'orgid') {
                        $sc .= " and n.orgid={$val}";

                    } elseif ($key == 'nametypeid') {
                        $sc .= " and n.nametypeid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and n.active=1 and curdate() between n.begdate and ifnull(n.enddate)";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and ( (n.active=1 and curdate() between n.begdate and ifnull(n.enddate)) or n.id={$val})";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name названий организаций
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('org_names as n')
                ->whereRaw($sc)
                ->select('n.id', 'n.name')
                //->orderBy('n.ordr', 'asc')
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
        //2021-04-30 SNS. универсальный конструктор коллекции из записей orgdeps
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $recs = self::from('org_names as n')
                ->join('orgnametypes as ont', 'ont.id', 'n.nametypeid')
                ->whereRaw($sc)
                ->select($fields)
                //->orderBy('n.ordr', 'asc')
                ->orderBy('n.begdate', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


}
