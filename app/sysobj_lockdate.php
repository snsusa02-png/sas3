<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class sysobj_lockdate extends Model
{
    //

    static public $prefix = 'sysobj_lockdates';
    static public $sysobjid = 22;

    protected $primaryKey = 'sysobjid';

    protected $fillable = ['sysobjid'];

    public $timestamps = false;

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }


}
