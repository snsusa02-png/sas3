<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class jts_violation extends Model
{
    static public $prefix = 'jts_violations';
    static public $sysobjid = 1104;

    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $violation_types = [
        1 => 'Нарушение пожарной безопасности',
        2 => 'Нарушение промышленной безопасности',
        3 => 'Нарушение формы одежды',
        4 => 'Нарушение производственной санитарии',
        5 => 'Нарушение техники безопасности',
        6 => 'Нарушение регламента работы',
    ];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    public function jobtimesheet()
    {
        return $this->hasOne(jobtimesheet::class, 'id', 'jts_id');
    }

    public function staff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();
    }


    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
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
