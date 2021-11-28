<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class budget_plan extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'budget_plans';
    static public $sysobjid = 879;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

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

}
