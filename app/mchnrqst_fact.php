<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class mchnrqst_fact extends Model
{
    static public $prefix = 'mchnrqst_facts';
    static public $sysobjid = 485;

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


    public function rqst()
    {
        return $this->hasOne(mchnrqst::class, 'id', 'rqstid');
    }

    public function machine()
    {
        return $this->hasOne(machine::class, 'id', 'machineid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('firstread_at');
    }

    public function real_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at');
    }

    public function comments()
    {
        return $this->hasMany('App\obj_comment', 'objid', 'id')
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
            $rslt = Str::limit(strip_tags($this->staff->name), 200, '...');
            return $rslt;
        } else
            return null;
    }


}
