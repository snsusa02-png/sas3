<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class task_reptype extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'task_reptypes';
    static public $sysobjid = 963;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    static public function lstActive()
    {
        if (1==1) {
            //Cache::forget(self::$prefix . '_lstTypes_' . $buildobjid);
            $data = Cache::remember(self::$prefix . '_lstActive_' , now()->addMinutes(15)
                , function () {
                    $lst = self::select('id', 'name')
                        ->where('active', 1);
                    $lst = $lst->orderby('ordr')->orderBy('name')
                        ->get()
                        ->pluck('name', 'id')->toArray();

                    return $lst;
                }
            );
        } else $data = [];
        return $data;
    }

}
