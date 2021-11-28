<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Cache;
use DB;

class org_acnt extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'org_acnts';
    static public $sysobjid = 115;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function getNameAttribute()
    {
        return $this->rs_num . ' ' . $this->bankname;
    }

    static public function lstActiveForOrg($orgid)
    {
        //Cache::forget(self::$prefix . '_lstActiveForOrg:' . $orgid);
        $data = Cache::remember(self::$prefix . '_lstActiveForOrg:' . $orgid, now()->addMinutes(15)
            , function () use ($orgid) {
                $lst = self::select('id', db::raw("concat(rs_num, ' - ', bankname) as tname"))
                    ->where('orgid', $orgid)
                    ->where('active', 1)
                    //->orderby('ordr')
                    ->orderby('tname')
                    ->get()
                    ->pluck('tname', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function lstActiveForPay($orgid)
    {
        //Cache::forget(self::$prefix . '_lstActiveForPay:' . $orgid);
        $data = Cache::remember(self::$prefix . '_lstActiveForPay:' . $orgid, now()->addMinutes(15)
            , function () use ($orgid) {
                $lst = self::select('id', db::raw("concat(rs_num, ' - ', bankname) as tname"))
                    ->where(['orgid' => $orgid, 'active' => 1, 'forpay' => 1])
                    //->orderby('ordr')
                    ->orderby('tname')
                    ->get()
                    ->pluck('tname', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }
}
