<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\StaffsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class checkrqst extends Model
{
    static public $prefix = 'checkrqsts';
    static public $sysobjid = 1761;

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

    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'inituserid')->withDefault();
    }

    public function pdfer()
    {
        return $this->hasOne(User::class, 'id', 'pdf_by');
    }

    public function sender()
    {
        return $this->hasOne(User::class, 'id', 'send_by');
    }

    static public function chktypes()
    {
        return [1 => 'геодезический контроль', 2 => 'входной контроль', 3 => 'приемочный строительный контроль'];
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function org1()
    {
        return $this->hasOne(org::class, 'id', 'org1_id')->withDefault();
    }

    public function org2()
    {
        return $this->hasOne(org::class, 'id', 'org2_id')->withDefault();
    }

    public function org3()
    {
        return $this->hasOne(org::class, 'id', 'org3_id')->withDefault();
    }

    public function org4()
    {
        return $this->hasOne(org::class, 'id', 'org4_id')->withDefault();
    }

    public function org5()
    {
        return $this->hasOne(org::class, 'id', 'org5_id')->withDefault();
    }

    public function bostf1()
    {
        return $this->hasOne(buildobj_staff::class, 'id', 'bostf1_id')->withDefault();
    }

    public function bostf2()
    {
        return $this->hasOne(buildobj_staff::class, 'id', 'bostf2_id')->withDefault();
    }

    public function bostf3()
    {
        return $this->hasOne(buildobj_staff::class, 'id', 'bostf3_id')->withDefault();
    }

    public function bostf4()
    {
        return $this->hasOne(buildobj_staff::class, 'id', 'bostf4_id')->withDefault();
    }

    public function bostf5()
    {
        return $this->hasOne(buildobj_staff::class, 'id', 'bostf5_id')->withDefault();
    }

    public function hash_base()
    {
        //Значения значимых полей. Для последующего вычисления хэша

        $doc = array_filter($this->makeHidden(
            ['id', 'created_at', 'created_by', 'updated_at', 'updated_by'
                , 'pdf_hash', 'pdf_at', 'pdf_by', 'send_hash', 'send_at', 'send_by'
                , 'active', 'contract', 'bostf1', 'bostf2', 'bostf3', 'bostf4', 'bostf5'
            ])
            ->toArray());

        $val = '';
        foreach ($doc as $itm)
            $val .= '|' . ((is_array($itm)) ? implode('|', $itm) : $itm);
        //dd($val);
        return $val;
    }

    public function hash()
    {
        //return Hash::make($this->hash_base());
        return md5($this->hash_base());
    }


//    public static function years()
//    {
//        return Cache::remember('mchn_raids.years', now()->addMinutes(15)
//            , function () {
//                return self::selectraw('year(wrkdate) as yr')->distinct()
//                    ->orderby('yr', 'desc')->get()
//                    ->pluck('yr', 'yr')->toArray();
//            });
//    }

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

    public function tags() //2021-80-10 Переименовал, так как конфликтует с другим содержанием $rec->tags
    {
        return $this->hasMany(objtag::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('tag');
    }
}
