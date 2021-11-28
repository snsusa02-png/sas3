<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class doc extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'docs';
    static public $sysobjid = 921;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')
            ->withDefault();
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
            $rslt = $this->name . ' №' . $this->docnum
                . ' от ' . date_format(date_create($this->docdate), "d.m.Y");
//            if (isset($this->docsum))
//                $rslt .= ' сумма: ' . number_format($this->docsum, 2);
            return $rslt;
        } else
            return null;
    }


}
