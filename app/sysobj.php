<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Cache;
use Illuminate\Support\Facades\DB;

class sysobj extends Model
{
    //
    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    static public function getIdByCode($code)
    {
        $rq = static::where('active', 1)
            ->where('code', $code)
            ->select('id')
            ->first();
        $res = null;
        if (isset($rq)) {
            $res = $rq->id;
        }
        return $res;
    }

    static public function acl_sysobjcode($code)
    {
        //2021-06-24 SNS. возвращает код объекта по которому определяются права пользователя - для заданной системы ($code)
        //Cache::forget('acl_sysobjcode_' . $code);
        return Cache::remember('acl_sysobjcode_' . $code, now()->addMinutes(35)
            , function () use ($code) {
                return self::where('code', $code)
                        ->select(db::raw("ifnull(acl_sysobjcode, code) as acl_sysobjcode"))
                        ->first()
                        ->acl_sysobjcode ?? $code;
            });
    }

    static public function isActiveByCode_cache($code)
    {
        //Cache::forget('isActiveByCode_' . $code);
        return Cache::remember('isActiveByCode_' . $code, now()->addMinutes(35)
            , function () use ($code) {
                return self::isActiveByCode($code);
            });
    }

    static public function isActiveByCode($code)
    {
        $rq = static::where([['code', $code], ['active', 1]])->count() ?? 0;
        return ($rq > 0);
    }

    static public function lst_sysobjs4extsys_cache()
    {
//        Cache::forget('lst_sysobjs4extsys');
        return Cache::remember('lst_sysobjs4extsys', now()->addMinutes(35)
            , function () {
                return self::select('id', 'name')
                    ->whereIn('id', [3, 105, 111, 822, 502, 202])
                    ->orderby('name')->get()->pluck('name', 'id')->toArray();
            });
    }

    static public function lst_sysobjs4grptypes_cache()
    {
//        Cache::forget('lst_sysobjs4grptypes');
        return Cache::remember('lst_sysobjs4grptypes', now()->addMinutes(35)
            , function () {
                return self::select('id', 'name')
                    ->whereIn('id', [105, 111, 202])
                    ->orderby('name')->get()->pluck('name', 'id')->toArray();
            });
    }

}
