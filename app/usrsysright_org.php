<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use Cache;
use App\Traits\DeleteTrait;

class usrsysright_org extends Model
{

    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'usrsysright_orgs';
    static public $sysobjid = 34;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function usrsysright()
    {
        return $this->hasOne(usrsysright::class, 'id', 'usrsysrightid');
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    static public function lstOrgsByUsersWithRight($sysfuncid)
    {

        $data = Cache::remember(self::$prefix . 'lstOrgsByUsersWithRight_' . $sysfuncid, now()->addMinutes(15)
            , function () use ($sysfuncid) {
                $lst = org::from('orgs as o')
                    ->whereRaw(" o.id in (
                SELECT distinct uro.orgid
                FROM usrsysright_orgs as uro
                join usrsysrights as usr on usr.id=uro.usrsysrightid
                and usr.sysfuncid=$sysfuncid
                and usr.active=1
                and now() between usr.begdt and ifnull(usr.enddt,now())
                join users as u on u.id=usr.userid and u.active=1
                where uro.active=1)")
                    ->select('o.id', 'o.name')
                    ->orderBy('o.name')
                    ->get()
                    ->pluck('name', 'id')
                    ->toArray();
                return $lst;
            }
        );
        return $data;
    }


}
