<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class acl_role_right extends Model
{
    public function role()
    {
        return $this->hasOne(acl_role::class, 'id', 'roleid');
    }

    public function sysfunc()
    {
        return $this->hasOne(sysfunc::class, 'id', 'sysfuncid');
    }


    public static function isRoleHasRight($RoleID, $SysFuncID)
    {

        $cnt = DB::table('acl_role_rights as r')
            ->join('acl_roles as ar', 'ar.id', '=', 'r.roleid')
            ->where('ar.active', 1)
            ->where('r.roleid', $RoleID)
            ->where('r.sysfuncid', $SysFuncID)
            ->where('r.active', '=', 1)
            //->select(DB::Raw('count(r.active) as cnt'))
            ->count();
        return ($cnt > 0);
    }

    public static function isUserHasRight_cached($RoleID, $SysFuncID)
    {
        return Cache::remember('Roleid_' . $RoleID . '_SysFuncID_' . $SysFuncID, now()->addMinutes(12)
            , function () use ($RoleID, $SysFuncID) {
                return self::isUserHasRight($RoleID, $SysFuncID);
            });
    }


    public static function RoleRightLst($RoleID)
    {
        $userid = Auth::user()->id;

        $lst = DB::table('acl_role_rights as r')
            ->join("sysfuncs as f", "f.id", "r.sysfuncid")
            ->join("sysobjs as o", "o.id", "f.sysobjid")
            ->join("users as su", "su.id", "r.created_by")
            ->where("r.roleid", $RoleID)
            ->where('r.active', '=', 1)
            ->where('su.active', 1)
            //текущий пользователь должен обладать правом администрирования на это право -----
            ->join('usrsysrights as ar', function ($join) use ($userid) {
                $join->on('ar.sysfuncid', '=', 'f.adminrightid')
                    ->where("ar.userid", $userid)
                    ->where("ar.active", 1)
                    ->whereRaw('now() between ar.begdt and ifnull(ar.enddt,now())');
            })
            //--------------------------------------------------------------------------------
            ->select("r.id", "r.created_at", "r.sysfuncid as funcid"
                , 'f.code as funccode', "f.name as funcname"
                , "o.id as objid", "o.name as objname", "o.code as objcode"
                , "su.name as userset"
            )
            ->orderby("o.ordr")
            ->orderby("o.id")
            ->orderby("f.ordr")
            ->orderby("f.name");
        /*dd($lst->toSql());*/
        $lst = $lst->get();
        //dd($lst);

        return $lst;
    }





    public static function UserRightLst4sysobj($UserID, $sysobjid)
    {
        $userid = Auth::user()->id;

        //Жесткое соответствие
        $sc = "now() between r.begdt and ifnull(r.enddt,now())";
        $sc .= " and f.sysobjid={$sysobjid}";

        $lst = DB::table('usrsysrights as r')
            ->join("sysfuncs as f", "f.id", "r.sysfuncid")
            ->join("sysobjs as o", "o.id", "f.sysobjid")
            ->join("users as su", "su.id", "r.created_by")
            ->where("r.userid", $UserID)
            ->where('r.active', '=', 1)
            ->where('su.active', 1)
            ->whereRaw($sc)
            //текущий пользователь должен обладать правом администрирования на это право -----
            ->/*left*/ join('usrsysrights as ar', function ($join) use ($userid) {
                $join->on('ar.sysfuncid', '=', 'f.adminrightid')
                    ->where("ar.userid", $userid)
                    ->where("ar.active", 1)
                    ->whereRaw('now() between ar.begdt and ifnull(ar.enddt,now())');
            })
            //--------------------------------------------------------------------------------
            ->select("r.id", "r.created_at", "r.sysfuncid as funcid", 'r.limsysobjid', 'r.limobjid'
                , 'f.code as funccode', "f.name as funcname", 'f.org_area as func_org_area'
                , "o.id as objid", "o.name as objname", "o.code as objcode"
                , "su.name as userset"
            )
            //->selectraw("userfiobyid(r.created_by) as userset")
            ->orderby("o.ordr")
            ->orderby("o.id")
            ->orderby("f.ordr")
            ->orderby("f.name");
        /*dd($lst->toSql());*/
        $lst = $lst->get();
        //dd($lst);

        return $lst;
    }

    //20190506 SNS.Список прав, которые может администрировать текущий пользователь
    public static function AdminRightLst()
    {
        $userid = Auth::user()->id;

        //если Join заменить на LeftJoin, то признаком наличия права на администрирование
        // будет не null в adminrightid
        $lst = DB::table('sysfuncs as f')
            ->join("sysobjs as o", "o.id", "f.sysobjid")
            ->/*left*/ join('usrsysrights as ar', function ($join) use ($userid) {
                $join->on('ar.sysfuncid', '=', 'f.adminrightid')
                    ->where("ar.userid", $userid)
                    ->where("ar.active", 1)
                    ->whereRaw('now() between ar.begdt and ifnull(ar.enddt,now())');
            })
            ->join('users as u', 'u.id', '=', 'ar.userid')
            ->where('u.active', 1)
            ->select("f.id as funcid", "f.name as funcname",
                "ar.sysfuncid as adminrightid", "o.id as objid", "o.name as objname")
            ->orderby("o.id")
            ->orderby("f.name");
        /*dd($lst->toSql());*/
        $lst = $lst->get();

        return $lst;
    }

    public static function setUsrSysRights($UserID, $SysFuncIDs, $limsysobjid, $limobjid, $initUserid = null)
    {
        // установка прав списком
        if (isset($UserID) and isset($SysFuncIDs) and count($SysFuncIDs) > 0) {
            $initUserid = $initUserid ?? Auth::user()->id ?? 1;
            foreach ($SysFuncIDs as $rightid) {
                self::setUsrSysRight($UserID, $rightid, $limsysobjid, $limobjid, $initUserid);
            }
        }
    }

    public static function setRoleRight($RoleID, $SysFuncID, $initUserid = null)
    {
        if (self::isRoleHasRight($RoleID, $SysFuncID) == 1) return;

        static::insert(
            [
                'roleid' => $RoleID,
                'sysfuncid' => $SysFuncID,
                'active' => 1,
                'created_at' => now(),
                'created_by' => $initUserid ?? Auth::user()->id ?? 1,
                'updated_at' => now(),
                'updated_by' => $initUserid ?? Auth::user()->id ?? 1,
            ]
        );
        Cache::forget('roleid_' . $RoleID . '_SysFuncID_' . $SysFuncID);
    }

    public static function delRoleRight($RoleID, $SysFuncID)
    {
        if (self::isRoleHasRight($RoleID, $SysFuncID) == 0) return;

        static::from('acl_role_rights as r')
            ->where('roleid', $RoleID)
            ->where('sysfuncid', $SysFuncID)
            ->where('r.active', 1)
            ->delete();
    }

    public static function clearRoleRightsCache($RoleID)
    {
        //зачистка кэша прав роли

        $clrRights = sysfunc::from('sysfuncs as sf')->select('sf.code')->get();
        foreach ($clrRights as $clrRight)
            Cache::forget('Roleid' . $RoleID . '_SysFuncCode_' . $clrRight->code);
    }

}
