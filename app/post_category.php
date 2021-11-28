<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class post_category extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'post_categories';
    static public $sysobjid = 891;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    //связь с пользователями-кураторами
//    public function CuratorUsers()
//    {
//        return $this->hasMany(org_curator::class, 'orgid', 'id')->with('refitem');
//    }

    public static function active()
    {
        return static::where('active', true)->get();
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
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
