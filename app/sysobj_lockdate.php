<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class sysobj_lockdate extends Model
{
    //
    protected $primaryKey = 'sysobjid';

    protected $fillable = ['sysobjid'];

    public $timestamps = false;

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid');
    }

}
