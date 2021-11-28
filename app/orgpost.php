<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class orgpost extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'orgposts';
    static public $sysobjid = 119;

    //protected $fillable = ["name", "active", "created_by", "updated_by"];
    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid');
    }

    public function dep()
    {
        return $this->hasOne(orgdep::class, 'id', 'depid');
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
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
                        $sc .= " and op.orgid={$val}";

                    } elseif ($key == 'depid') {
                        $sc .= " and op.depid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and op.active={$val}";

                    } elseif ($key == 'has_vacancy') {
                        $sc .= " and op.stdlimunits > op.stdusedunits";

                    } elseif ($key == 'has_vacancies_staff') {
                        $sc .= " and ( op.stdlimunits > op.stdusedunits
                                        or exists(select 1 from orgstaff as os where os.postid=op.id and os.id={$val}))";

                    }
                }

            }
        }
        Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name должностей
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('orgposts as op')
                ->whereRaw($sc)
                ->select('op.id', 'op.name')
                ->orderBy('op.ordr', 'asc')
                ->orderBy('name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
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

            $recs = self::from('orgposts as op')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('op.ordr', 'asc')
                ->orderBy('op.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

    static public function refresh_units($postid)
    {
        if (!isset($postid))
            return null;

        //подсчитаем общее кол-во использованных вакансий
        $units = orgstaff::where('postid', $postid)->where('active',1)->sum('stdpostunit');
        self::where('id', $postid)->update(['stdusedunits' => $units]);

        return $units;
    }

}
