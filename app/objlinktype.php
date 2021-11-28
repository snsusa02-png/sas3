<?php

namespace App;


use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class objlinktype extends Model
{
    use DeleteTrait;

    static public $prefix = 'objlinktypes';
    static public $sysobjid = 815;

    protected $guarded = [];

}
