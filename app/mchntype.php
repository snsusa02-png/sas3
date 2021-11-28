<?php

namespace App;

use App\Traits\DeleteTrait;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use DB;

class mchntype extends Model
{
    //
    static public $prefix = 'mchntype';

    use DeleteTrait;

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

    static public function usedTypes()
    {
        //Cache::forget(self::$prefix . '_usedTypes');
        $data = Cache::remember(self::$prefix . '_usedTypes', now()->addMinutes(8)
            , function () {
                $lst = self::from('mchntypes as t')
                    ->select('id', 'name')
                    ->where('active', 1)
                    ->whereRaw('exists (select 1 from machines as m where m.mchntypeid=t.id)')
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }
}
