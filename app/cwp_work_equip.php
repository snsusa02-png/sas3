<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class cwp_work_equip extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    //Работы для вида работ
    static public $prefix = 'cwp_work_equips';
    static public $sysobjid = 975;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function work()
    {
        //return $this->belongsTo(cwp_work::class, 'id', 'workid');
        return $this->hasOne(cwp_work::class, 'id', 'workid');
    }

    public function ri_lim()
    {
        //return $this->belongsTo(cwp_work::class, 'id', 'workid');
        return $this->hasOne(bot_ri_lim::class, 'id', 'ri_limid')->withDefault();
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')->withDefault();
    }

    public function unittype()
    {
        return $this->hasOne(unittype::class, 'id', 'unittypeid')->withDefault();
    }

}
