<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class contract_workplan extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'contract_workplan';
    static public $sysobjid = 155;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')
            ->withDefault();
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('mustread', 'desc')
            ->orderby('firstread_at');
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
            if (isset($this->docsum))
                $rslt .= ' сумма: ' . number_format($this->docsum, 2);
            return $rslt;
        } else
            return null;
    }


}
