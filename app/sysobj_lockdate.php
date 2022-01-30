<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class sysobj_lockdate extends Model
{
    //
    protected $primaryKey = 'sysobjid';

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid');
    }

}
