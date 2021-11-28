<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class ri_unit extends Model
{
    use DeleteTrait;

    static public $prefix = 'ri_units';
    static public $sysobjid = 144;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid');
    }

    public function unittype()
    {
        return $this->hasOne(unittype::class, 'id', 'unittypeid')->withDefault();
    }

}
