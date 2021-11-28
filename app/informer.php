<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\StaffsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class informer extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use StaffsTrait;

    protected $guarded = [];

    static public $prefix = 'informers';
    static public $sysobjid = 1801;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function ac()
    {
        return $this->hasOne(ac::class, 'id', 'acsid')->withDefault();
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


    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->name;
            return $rslt;
        } else
            return null;
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


    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name договоров
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = "1=1";

            foreach ($params as $key => $val) {
                //Log::info($key.' = '.$val);
                if (isset($val) and $val !== '') {

                    if (array_search($key, $used_params) == 0) {
                        $used_params[] = $key;

                        if ($key == 'name') {
                            $sc .= " and c.name like '%{$val}%'";

                        } elseif ($key == 'active') {
                            //действующий в настоящее время
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . " c.active={$val}";
                        }
                    }

                }
            }
            //Log::info($sc);

            $lst = self::from('informers as c')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('name')
                ->get()->pluck('name', 'id')->toArray();
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


}

