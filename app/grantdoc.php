<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use DB;

class grantdoc extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'grantdocs';
    static public $sysobjid = 991;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function grantstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'grantstaffid')->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid');
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
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

    public static function NxtDocNum($ownorgid, $docdate)
    {   //возвращает очередной номер для заданного типа документа, продавца и даты документа
        $num = -1;
        $doctypeid = 228;
        $rslt = DB::select("select nxtdocnum({$doctypeid},{$ownorgid},'{$docdate}') as num");

        return $rslt[0]->num ?? null;
    }


}
