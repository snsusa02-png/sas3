<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class buildobj_wrh extends Model
{
    static public $prefix = 'buildobj_wrh';
    static public $sysobjid = 939;

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

    public function buildobj()   //объект
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid');
    }

    public function wrh()   //объект
    {
        return $this->hasOne(wrh::class, 'id', 'wrhid');
    }


}
