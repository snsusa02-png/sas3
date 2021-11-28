<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use DB;

class cwp_fact extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    //Факт данные исполнения для вида работ
    static public $prefix = 'cwp_facts';
    static public $sysobjid = 976;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function cwp_work()
    {
        return $this->hasOne(cwp_work::class, 'id', 'workid');
    }

}
