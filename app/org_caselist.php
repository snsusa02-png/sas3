<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\Excludable;
use App\Traits\FilesTrait;
use App\Traits\StaffsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class org_caselist extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use StaffsTrait;
    use Excludable;

    protected $guarded = [];

    static public $prefix = 'org_caselists';
    static public $sysobjid = 1711;

    protected $hidden = ['id', 'created_by', 'updated_by'];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function doctype()
    {
        return $this->hasOne(doctype::class, 'id', 'doctypeid')
            ->withDefault();
    }

    public function org()
    {//Организация-Источник
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function ac()
    {
        return $this->hasOne(ac::class, 'id', 'acsid')->withDefault();
    }

    static public function statuses()
    {
        return [
            0 => 'черновик',
            2 => 'на согласовании',
            4 => 'подписан (действует)',
            8 => 'завершен (исполнен)',
        ];
    }

    public function items()
    {
        return $this->hasMany(ocl_item::class, 'ocl_id', 'id')
            ->orderBy('ordr','asc')
            ->orderBy('code','asc');
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


    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = ' №' . $this->docnum
                . ' от ' . date_format(date_create($this->docdate), "d.m.Y");
            return $rslt;
        } else
            return null;
    }

    public function getShortInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->name . ' №' . $this->docnum
                . ' от ' . date_format(date_create($this->docdate), "d.m.Y");
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
                        $sc .= " and ocl.statusid=4";

                    }

                }
            }
            //Log::info($sc);

            return $sc;
        }
    }


    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name документов
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('org_caselists as ocl')
                ->join('orgs as o', 'o.id', 'ocl.orgid')
                ->whereRaw($sc)
                ->select('ocl.id', 'o.name')
                ->orderBy('d.begdate', 'desc')
                ->orderBy('o.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


}

