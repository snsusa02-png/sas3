<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class buildobj_work extends Model
{
    static public $prefix = 'buildobj_works';
    static public $sysobjid = 938;

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

    public function org()   //заказчик-подрядчик
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }


}
