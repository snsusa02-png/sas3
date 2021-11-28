<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use DateTime;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class task_report extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'task_reports';
    static public $sysobjid = 965;


    public function task()
    {
        return $this->hasOne(task::class, 'id', 'taskid');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }


    public function tags()
    {
        return $this->hasMany(objtag::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('tag');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('mustread', 'desc')
            ->orderby('firstread_at');
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = 'Отчет по задаче: "' . $this->report . '", '
                . date_format(date_create($this->wrkbegdt), "d.m.Y H:i")
                . ' .. ' . date_format(date_create($this->wrkenddt), "d.m.Y H:i");
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

}
