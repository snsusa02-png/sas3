<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class prodplan_fact extends Model
{
    static public $prefix = 'prodplan_facts';

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

    public function prodplan()
    {
        return $this->hasOne(prodplan_item::class, 'id', 'planitmid');
    }

}
