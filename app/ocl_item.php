<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\Excludable;
use App\Traits\FilesTrait;
use App\Traits\StaffsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ocl_item extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use StaffsTrait;
    use Excludable;

    protected $guarded = [];

    static public $prefix = 'ocl_items';
    static public $sysobjid = 1712;

    protected $hidden = ['id', 'created_by', 'updated_by'];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function org_caselist()
    {
        return $this->hasOne(org_caselist::class, 'id', 'ocl_id');

    }

    public function orgdep()
    {//Подразделение организации
        return $this->hasOne(orgdep::class, 'id', 'orgdepid')
            ->withDefault();
    }

    public function ac()
    {
        return $this->hasOne(ac::class, 'id', 'acsid')->withDefault();
    }

    public function linked_objs()
    {
        return $this->hasMany(obj_link::class, 'objid', 'id')
            ->where([
                'sysobjid' => self::$sysobjid,
            ]);
    }


    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->leftJoin('roletypes as rt', 'rt.id', 'obj_readers.roletypeid')
            ->where('sysobjid', self::$sysobjid)
            ->select('obj_readers.*', 'rt.name as roletype_name')
            ->orderby('mustread', 'desc')
            ->orderby('firstread_at');
    }

    public function real_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at');
    }

    public function tags()
    {
        return $this->hasMany(objtag::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('tag');
    }

    public function comments()
    {
        return $this->hasMany(obj_comment::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }


    static public function usedTags()
    {
        $cache_key = self::$prefix . '_usedTypes';
        Cache::forget($cache_key);
        $data = Cache::remember($cache_key, now()->addMinutes(8)
            , function () {
                $lst = objtag::from('objtags as t')
                    ->select('tag as tid', 'tag as tname')
                    ->where('sysobjid', self::$sysobjid)
                    ->orderBy('tag')
                    ->get()
                    ->pluck('tname', 'tid')->toArray();

                return $lst;
            }
        );
        return $data;
    }


    static public function search_cond($params)
    {

        $userid = \Auth::user()->id;

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
                        $sc .= " and exists(select 1 from org_caselists as ocl where ocl.id = ocli.ocl_id and ocl.orgid={$val})";

                    } elseif ($key == 'in_documents') {
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from documents as d0 where d0.ocl_itmid = ocli.id)";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }


    static public function lstFor($params)
    {
        //2021-02-25 SNS. универсальный конструктор массива с id, name организаций
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $userid = \Auth::user()->id;

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = self::from('ocl_items as ocli')
                ->whereRaw($sc)
                ->select('ocli.id', db::raw("concat(ocli.code,' - ',ocli.name) as name"))
                ->orderBy('ocli.ordr', 'asc')
                ->orderBy('ocli.name', 'asc')
                ->get()->pluck('name', 'id')
                ->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-07-10 SNS. кэшируемый результат списка

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


}

