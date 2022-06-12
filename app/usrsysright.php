<?php

namespace App;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use App\User;

use Illuminate\Database\Eloquent\Model;

class usrsysright extends Model
{
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userid');
    }

    public function sysfunc()
    {
        return $this->hasOne(sysfunc::class, 'id', 'sysfuncid');
    }

    public function limsysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'limsysobjid');
    }


    // возвращает все ID всех прав заданного сотрудника
    public static function isUserHasRight0($UserID, $SysFuncID)
    {
        //$_SESSION['userid_' . $UserID . '_SysFuncID_' . $SysFuncID]=null;
        if (true && isset($_SESSION['userid_' . $UserID . '_SysFuncID_' . $SysFuncID])) {
//            debug('from session: '
//                .'userid_' . $UserID . '_SysFuncID_' . $SysFuncID
//                .$_SESSION['userid_' . $UserID . '_SysFuncID_' . $SysFuncID]);
            return $_SESSION['userid_' . $UserID . '_SysFuncID_' . $SysFuncID];
        }

        $rslt = DB::table('usrsysrights as r')
            ->join('users as u', 'u.id', '=', 'r.userid')
            ->where('u.active', 1)
            ->select(DB::Raw('count(r.active) as cnt'))
            ->where('r.userid', $UserID)
            ->where('r.sysfuncid', $SysFuncID)
            ->where('r.active', '=', 1)
            ->whereRaw('r.begdt <= now() and ifnull(r.enddt,now())>=now()')
            ->first();
        $res = false;
        if (isset($rslt)) {
            $res = ($rslt->cnt > 0);
            $_SESSION['userid_' . $UserID . '_SysFuncID_' . $SysFuncID] = $res;
            //debug('set: '.$_SESSION['userid_' . $UserID . '_SysFuncID_' . $SysFuncID]);
        }
        return $res;
    }

    public static function isUserHasExactRight($UserID, $SysFuncID, $limsysobjid = null, $limobjid = null)
    {
        //Нет наследования права от записей с limsysobjid/limobjid = null

        //ограничение на $limobjid имеет смысл только при определенном $limsysobjid
        $limobjid = (isset($limsysobjid)) ? $limobjid : null;

        //Жесткое соответствие
        $sc = "r.active=1 and now() between r.begdt and ifnull(r.enddt,now())";
        if (isset($limsysobjid))
            $sc .= " and r.limsysobjid={$limsysobjid}";
        else
            $sc .= " and r.limsysobjid is null";

        if (isset($limobjid))
            $sc .= " and r.limobjid={$limobjid}";
        else
            $sc .= " and r.limobjid is null";


        $rslt = DB::table('usrsysrights as r')
            ->join('users as u', 'u.id', '=', 'r.userid')
            ->where('u.active', 1)
            ->select(DB::Raw('count(r.active) as cnt'))
            ->where('r.userid', $UserID)
            ->where('r.sysfuncid', $SysFuncID)
            ->whereRaw($sc)
            ->first();
        return ($rslt->cnt > 0);
    }

    public static function isUserHasRight($UserID, $SysFuncID, $limsysobjid = null, $limobjid = null)
    {
        //ограничение на $limobjid имеет смысл только при определенном $limsysobjid
        $limobjid = (isset($limsysobjid)) ? $limobjid : null;
        $limsysobjid = $limsysobjid ?? -1;
        $limobjid = $limobjid ?? -1;

        $sc = "ifnull(r.limsysobjid,{$limsysobjid})={$limsysobjid}";
        $sc .= " and ifnull(r.limobjid,{$limobjid})={$limobjid}";


        $rslt = DB::table('usrsysrights as r')
            ->join('users as u', 'u.id', '=', 'r.userid')
            ->where('u.active', 1)
            ->where('r.userid', $UserID)
            ->where('r.sysfuncid', $SysFuncID)
            ->where('r.active', '=', 1)
            ->whereRaw('r.begdt <= now() and ifnull(r.enddt,now())>=now()')
            ->whereRaw($sc)
            ->select(DB::Raw('count(r.active) as cnt'))
            ->first();
        return ($rslt->cnt > 0);
    }

    public static function isUserHasRight_cached($UserID, $SysFuncID, $limsysobjid = null, $limobjid = null)
    {
        return Cache::remember('userid_' . $UserID . '_SysFuncID_' . $SysFuncID, now()->addMinutes(12)
            , function () use ($UserID, $SysFuncID, $limsysobjid, $limobjid) {
                return self::isUserHasRight($UserID, $SysFuncID, $limsysobjid, $limobjid);
            });
    }


//    public static function isUserHasRightByCode($UserID, $SysFuncCode)
//    {
//        //Если usrsysrights.UserID is null, то это право предоставлено ЛЮБОМУ пользователю
//        $rslt = DB::table('usrsysrights as r')
//            ->join('users as u', 'u.id', '=', 'r.userid')
//            ->where('u.active', 1)
//            ->join('sysfuncs as sf', 'sf.id', '=', 'r.sysfuncid')
//            ->where('sf.code', $SysFuncCode)
//            //->select(DB::Raw('count(r.active) as cnt'))
//            ->whereraw('ifnull(r.userid,' . $UserID . ')=' . $UserID)
//            ->where('r.active', 1)
//            ->whereRaw('now() between r.begdt and ifnull(r.enddt,now())')
//            ->count();
//        return ($rslt > 0);
//    }
//
//    public static function isUserHasRightByCode_cached($UserID, $SysFuncCode)
//    {
//        return Cache::remember('userid_' . $UserID . '_SysFuncCode_' . $SysFuncCode, now()->addMinutes(12)
//            , function () use ($UserID, $SysFuncCode) {
//                //Если usrsysrights.UserID is null, то это право предоставлено ЛЮБОМУ пользователю
//                return self::isUserHasRightByCode($UserID, $SysFuncCode);
//            });
//    }


    public static function isUserHasAnyRightByCode($userID, $SysFuncCode)
    {
        //функция для ГРУБОЙ/ПРЕДВАРИТЕЛЬНОЙ проверки наличия указанного права
        // - без учета ограничений области применения (usrsysrights.limsysobjid, limobjid)

        //Если usrsysrights.UserID is null, то это право предоставлено ЛЮБОМУ пользователю

        $sc = " sf.code='{$SysFuncCode}' and r.active=1 and now() between r.begdt and ifnull(r.enddt,now())";
        $sc .= " and ifnull(r.userid,{$userID})={$userID}";

        $rslt = DB::table('usrsysrights as r')
            ->join('users as u', 'u.id', '=', 'r.userid')
            ->where('u.active', 1)
            ->join('sysfuncs as sf', 'sf.id', '=', 'r.sysfuncid')
            ->whereraw($sc)
            ->count();
        return ($rslt > 0);
    }

    public static function isUserHasRightByCode($userID, $SysFuncCode, $limsysobjid = null, $limobjid = null)
    {
        //Если usrsysrights.UserID is null, то это право предоставлено ЛЮБОМУ пользователю
        //Если usrsysrights.limsysobjid is null, то это право предоставлено без ограничений применения к сист. объектам
        //Если usrsysrights.limobjid is null, то это право предоставлено без ограничений применения к конкретному объекту в пределах $limsysobjid

        //ограничение на $limobjid имеет смысл только при определенном $limsysobjid
        $limobjid = (isset($limsysobjid)) ? $limobjid : null;
        $limsysobjid = $limsysobjid ?? -1;
        $limobjid = $limobjid ?? -1;

        $sc = " sf.code='{$SysFuncCode}' and r.active=1 and now() between r.begdt and ifnull(r.enddt,now())";
        $sc .= " and ifnull(r.userid,{$userID})={$userID}";
        $sc .= " and ifnull(r.limsysobjid,{$limsysobjid})={$limsysobjid}";
        $sc .= " and ifnull(r.limobjid,{$limobjid})={$limobjid}";
//dd($sc);
        $rslt = DB::table('usrsysrights as r')
            ->join('users as u', 'u.id', '=', 'r.userid')
            ->where('u.active', 1)
            ->join('sysfuncs as sf', 'sf.id', '=', 'r.sysfuncid')
            ->whereraw($sc)
            ->count();
        return ($rslt > 0);
    }

    public static function isUserHasRightByCode_cached($userID, $SysFuncCode, $limsysobjid = null, $limobjid = null)
    {
        if (!isset($userID))
            return false;

        //ограничение на $limobjid имеет смысл только при определенном $limsysobjid
        $limobjid = (isset($limsysobjid)) ? $limobjid : -1;
        $limsysobjid = $limsysobjid ?? -1;
        $limobjid = $limobjid ?? -1;

        return Cache::remember('UserHasRightByCode_' . ($userID ?? -1) . '_' . $SysFuncCode . '_' . $limsysobjid . '_' . $limobjid, now()->addMinutes(5)
            , function () use ($userID, $SysFuncCode, $limsysobjid, $limobjid) {
                //Если usrsysrights.UserID is null, то это право предоставлено ЛЮБОМУ пользователю
                return self::isUserHasRightByCode($userID, $SysFuncCode, $limsysobjid, $limobjid);
            });
    }


    public static function isUserHasRightForOrgByCode($userid, $SysFuncCode, $orgid)
    {
        //Имеет ли пользователь право на объекты указанной организации

        //Если usrsysrights.UserID is null, то это право предоставлено ЛЮБОМУ пользователю
        //если usrsysright_orgs.orgid is null, то подходит для любой организации

        if (isset($orgid))
            $rslt = DB::table('usrsysrights as r')
                ->join('usrsysright_orgs as ro', function ($join) use ($orgid) {
                    $join->on('ro.usrsysrightid', 'r.id')
                        ->where('ro.active', 1)
                        ->whereRaw("ifnull(ro.orgid,$orgid) = $orgid");
                })
                ->join('users as u', 'u.id', '=', 'r.userid')
                ->where('u.active', 1)
                ->join('sysfuncs as sf', 'sf.id', '=', 'r.sysfuncid')
                ->where('sf.code', $SysFuncCode)
                ->where('r.active', 1)
                ->whereraw('ifnull(r.userid,' . $userid . ')=' . $userid)
                ->whereRaw('now() between r.begdt and ifnull(r.enddt,now())')
                ->count();
        else
            //так как $orgid null, то ищем по-простому
            $rslt = self::isUserHasRightByCode($userid, $SysFuncCode);

        return ($rslt > 0);
    }

    public static function isUserHasRightForOrgByCode_cached($userid, $SysFuncCode, $orgid)
    {
        return Cache::remember('userid_' . $userid . '_SysFuncCode_' . $SysFuncCode . '_orgid_' . $orgid, now()->addMinutes(12)
            , function () use ($userid, $SysFuncCode, $orgid) {
                //Если usrsysrights.UserID is null, то это право предоставлено ЛЮБОМУ пользователю
                return self::isUserHasRightForOrgByCode($userid, $SysFuncCode, $orgid);
            });
    }

    public static function getStfRightsWithName($StaffID)
    {
        return static::from('usrsysrights as r')
            ->join('users as u', 'u.id', '=', 'r.userid')
            ->leftjoin('sysfuncs as f', 'f.id', '=', 'r.sysfuncid')
            ->select('r.sysfuncid', 'f.name as rightname', 'r.begdt', 'r.enddt'
                , 'r.reason', 'r.active')
            ->where('u.staffid', $StaffID)
            ->orderby('r.begdt', 'desc')
            ->get();

    }


    public static function UserRightLst($UserID, $limsysobjid = null, $limobjid = null)
    {
        $userid = Auth::user()->id;

        //ограничение на $limobjid имеет смысл только при определенном $limsysobjid
        $limobjid = (isset($limsysobjid)) ? $limobjid : null;

        //Жесткое соответствие
        $sc = "now() between r.begdt and ifnull(r.enddt,now())";
        if (isset($limsysobjid))
            $sc .= " and r.limsysobjid={$limsysobjid}";
        else
            $sc .= " and r.limsysobjid is null";

        if (isset($limobjid))
            $sc .= " and r.limobjid={$limobjid}";
        else
            $sc .= " and r.limobjid is null";

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

    public static function setUsrSysRight($UserID, $SysFuncID, $limsysobjid, $limobjid, $initUserid = null)
    {
        if (self::isUserHasExactRight($UserID, $SysFuncID, $limsysobjid, $limobjid) == 1) return;

        static::insert(
            [
                'userid' => $UserID,
                'sysfuncid' => $SysFuncID,
                'limsysobjid' => $limsysobjid,
                'limobjid' => $limobjid,
                'begdt' => now(),
                'active' => 1,
                'created_at' => now(),
                'created_by' => $initUserid ?? Auth::user()->id ?? 1,
                'updated_at' => now(),
                'updated_by' => $initUserid ?? Auth::user()->id ?? 1,
            ]
        );
        Cache::forget('userid_' . $UserID . '_SysFuncID_' . $SysFuncID);
    }

    public static function delUsrSysRight($UserID, $SysFuncID, $limsysobjid = null, $limobjid = null)
    {
        if (self::isUserHasExactRight($UserID, $SysFuncID, $limsysobjid, $limobjid) == 0) return;

        //Жесткое соответствие
        $sc = "r.active=1 and now() between r.begdt and ifnull(r.enddt,now())";
        if (isset($limsysobjid))
            $sc .= " and r.limsysobjid={$limsysobjid}";
        else
            $sc .= " and r.limsysobjid is null";

        if (isset($limobjid))
            $sc .= " and r.limobjid={$limobjid}";
        else
            $sc .= " and r.limobjid is null";


        static::from('usrsysrights as r')
            ->where('userid', $UserID)
            ->where('sysfuncid', $SysFuncID)
            ->whereRaw($sc)
            ->update(['enddt' => now()]);

    }

    public static function clearUserRightsCache($userid)
    {
        //зачистка кэша прав пользователя

        $clrRights = sysfunc::from('sysfuncs as sf')->select('sf.code')->get();
        foreach ($clrRights as $clrRight)
            Cache::forget('userid_' . $userid . '_SysFuncCode_' . $clrRight->code);

    }


    public static function lstUserIDWithRightCode($rightcode)
    {
        //Массив users->id пользователей с заданным правом $rightcode

        if (!isset($rightcode))
            return null;

        return self::from('usrsysrights as usr')
            ->join('sysfuncs as sf', 'sf.id', 'usr.sysfuncid')
            ->where([
                ['sf.code', '=', $rightcode],
                ['usr.active', '=', 1],
            ])
            ->whereRaw('now() between usr.begdt and ifnull(usr.enddt, now())')
            ->select('usr.userid')
            ->get()->pluck('userid')->toArray();

    }

}
