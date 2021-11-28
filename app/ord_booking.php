<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ord_booking extends Model
{
    //

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }



    static public function rest_info_low($id)
    {
        //записи с текущими суммами статьи раздела бюджета, определяемого через Вид работ, Владельца бюджета.
        $data = null;

        if (isset($id)) {

            $data = self::from('ord_bookings as b')
                ->where(['id' => $id])
                ->select('b.*'                )
                ->first();

            $data->order = Order::where('id', $data->ordid)->first();
            $data->resobj = resobj::where('id', $data->resobjid)->first();
            $data->creator = $data->whocrt;
        }
        return $data;
    }

}
