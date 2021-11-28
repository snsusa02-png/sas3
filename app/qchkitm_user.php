<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class qchkitm_user extends Model
{
    protected $guarded=[];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

}
