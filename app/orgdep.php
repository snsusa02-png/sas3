<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class orgdep extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'orgdeps';
    static public $sysobjid = 118;

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
                        $sc .= " and od.orgid={$val}";

                    } elseif ($key == 'with_posts') {
                        $sc .= " and exists( select 1 from orgposts as op where op.depid=od.id)";

                    } elseif ($key == 'with_post_vacancies') {
                        $sc .= " and exists( select 1 from orgposts as op where op.depid=od.id
                                    and op.stdlimunits>op.stdusedunits)";

                    } elseif ($key == 'with_post_vacancies_staff') {
                        $sc .= " and exists( select 1 from orgposts as op where op.depid=od.id
                                    and (op.stdlimunits>op.stdusedunits
                                         or exists(select 1 from orgstaff as os where os.postid=op.id and os.id={$val})))";

                    } elseif ($key == 'in_oclitems') {
                        $sc .= " and exists( select 1 from ocl_items as ocli where ocli.orgdepid=od.id)";

                    } elseif ($key == 'in_ocl_items_with_documents') {
                        $sc .= " and exists( select 1 from ocl_items as ocli where ocli.orgdepid=od.id
                            and exists(select 1 from documents as d where d.ocl_itmid=ocli.id) )";

                    } elseif ($key == 'active') {
                        $sc .= " and od.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (od.active=1 or od.id={$val})";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name подразделений
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('orgdeps as od')
                ->whereRaw($sc)
                ->select('od.id', 'od.name')
                ->orderBy('od.ordr', 'asc')
                ->orderBy('name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-10-07 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget(self::$prefix . '_lstFor_' . $hash);
            return Cache::remember(self::$prefix . '_lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
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

            $recs = self::from('orgdeps as od')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('od.ordr', 'asc')
                ->orderBy('od.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


}
