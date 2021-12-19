<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;

class obj_contact extends Model
{
    use DeleteTrait;

    static public $prefix = 'obj_contacts';
    static public $sysobjid = 1902;

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
