<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class budget_oper extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'budget_opers';
    static public $sysobjid = 878;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function itmsum()
    {//сумма по записи
        return $this->hasOne(budget_itmsum::class, 'id', 'itmsumid')
            ->with('item')
            ->withDefault();
    }

    public function lnk_oper()
    {
        return $this->hasOne(budget_oper::class, 'id', 'lnk_operid')->withDefault();
    }

    static public function addOrUpdate($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $oper = budget_oper::where($search_params)->first();

            if (!isset($oper)) {
                $oper = new budget_oper($search_params);
            }
            $oper->fill($set_params);
            $oper->save();

            return $oper;
        }
        return null;
    }

    static public function add($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $oper = new budget_oper($search_params);
            $oper->fill($set_params);
            $oper->save();

            return $oper;
        }
        return null;
    }
}
