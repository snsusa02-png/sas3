<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class obj_address extends Model
{
    use DeleteTrait;

    static public $prefix = 'obj_addresses';
    static public $sysobjid = 1923;

    protected $guarded = [];

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    static public function addOrUpdate($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $rec = self::where($search_params)->first();

            if (!isset($rec)) {
                $rec = new self($search_params);
            }
            $rec->fill($set_params);
            $rec->save();

            return $rec;
        }
        return null;
    }

}
