<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class mychat_user extends Model
{

    static public $prefix = 'mychat_users';
    static public $sysobjcode = 'mychat_users';
    static public $sysobjid = 886;

    use DeleteTrait;

    protected $guarded = [];

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
