<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class jobtimesheet extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'jobtimesheets';
    static public $sysobjid = 1101;


    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'inituserid')->withDefault();
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')->withDefault();
    }

    public function items()
    {
        return $this->hasMany(jts_item::class, 'jts_id', 'id');
    }

    public function machines()
    {
        return $this->hasMany(jts_machine::class, 'jts_id', 'id');
    }

    public function violations()
    {
        return $this->hasMany(jts_violation::class, 'jts_id', 'id');
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
