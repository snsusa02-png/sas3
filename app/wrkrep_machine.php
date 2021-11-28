<?php

namespace App;
use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class wrkrep_machine extends Model
{
    static public $prefix = 'wrkrep_machines';
    static public $sysobjid = 1132;

    use DeleteTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    public function wrkrep()
    {
        return $this->hasOne(wrkrep::class, 'id', 'wrkrep_id');
    }

    public function machine()
    {
        return $this->hasOne(machine::class, 'id', 'machineid')->withDefault();
    }

    public function mchn_opertype()
    {
        return $this->hasOne(mchn_opertype::class, 'id', 'mot_id')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

}
