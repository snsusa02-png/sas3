<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\sysobj;

class extsys_sysobj extends Model
{
    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function extsystem()
    {
        return $this->hasOne(extsystem::class, 'id', 'extsysid')->withDefault();
    }
    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid')->withDefault();
    }
}
