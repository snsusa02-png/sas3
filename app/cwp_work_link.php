<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class cwp_work_link extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    //Работы для вида работ
    static public $prefix = 'cwp_work_links';
    static public $sysobjid = 974;


}
