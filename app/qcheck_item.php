<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class qcheck_item extends Model
{
    static public $prefix = 'qcheck_items';
    static public $sysobjid = 863;

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


    public function qcheck()
    {
        return $this->hasOne(qcheck::class, 'id', 'qcheckid')->withDefault();
    }

    public function itmtype()
    {
        return $this->hasOne(qchkitm_itmtype::class, 'id', 'itmtypeid')->withDefault();
    }

    public function itmcategory()
    {
        return $this->hasOne(qchkitm_category::class, 'id', 'categoryid')->withDefault();
    }

    public function resporg()
    {
        return $this->hasOne(org::class, 'id', 'resporgid')->withDefault();
    }

    public function respstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'respstaffid')->withDefault();
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
            $rslt = Str::limit(strip_tags($this->chkreport), 200, '...');
            return $rslt;
        } else
            return null;
    }


}
