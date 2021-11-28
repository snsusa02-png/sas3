<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class mchnrqsttype extends Model
{
    static public $prefix = 'mchnrqsttype';

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    static public function lstTypes()
    {
        //Cache::forget(self::$prefix . '_lstTypes');
        $data = Cache::remember(self::$prefix . '_lstTypes', now()->addMinutes(25)
            , function () {
                $lst = self::select('id', 'name')->where('active', 1)
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

}
