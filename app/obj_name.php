<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class obj_name extends Model
{
    use DeleteTrait;

    static public $prefix = 'obj_names';
    static public $sysobjid = 971;

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

    public static function addOrUpdate($sysobjid, $objid, $params)
    {
        //dd($sysobjid, $objid, ['name'=>'alternative name']);
        //$userid = \Auth::user()->id ?? 1;

        //Создает или обновляет существующую запись
        if (isset($sysobjid) and isset($objid) and $objid <> -1) {

            $obj = self::where(['sysobjid' => $sysobjid, 'objid' => $objid])->first();
            if (!isset($rdr)) {
                $obj = new obj_name([
                    'sysobjid' => $sysobjid,
                    'objid' => $objid,
                ]);
            }
            $obj->fill($params);
            $obj->save();
        }
    }

}
