<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class budget_fact extends Model
{
    //

    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'budget_facts';
    static public $sysobjid = 881;


    public function budget()
    {//Владелец бюджета
        return $this->hasOne(budget::class, 'id', 'budgetid')
            ->withDefault();
    }

    public function item()
    {//вид работ
        return $this->hasOne(budget_item::class, 'id', 'itmid');
    }

    public function acnttype()
    {//статья дохода/расходв
        return $this->hasOne(bdgtacnttype::class, 'id', 'acnttypeid');
    }

    public function opers()
    {
        return $this->hasMany(budget_oper::class, 'fctid', 'id')
            ->with('acnttype');
    }


}
