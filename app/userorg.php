<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use App\objlog;

class userorg extends Model
{
    use \App\Traits\DeleteTrait;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userid');
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')
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

    public static function userActiveOrgs($userid)
    {
        $recs = userorg::from("userorgs as uo")
            ->where('uo.userid', $userid)
            ->where('uo.active', 1)
            ->whereRaw('now() between uo.begdt and ifnull(uo.enddt, now())')
            ->get();
        return $recs;
    }

    public static function userActiveOrgsCnt($userid)
    {
        //возвращает число организаций, представляемых пользователем в данный момент
        return userorg::from("userorgs as uo")
            ->where('uo.userid', $userid)
            ->where('uo.active', 1)
            ->whereRaw('now() between uo.begdt and ifnull(uo.enddt, now())')
            ->count();
    }

    public static function lstUserActiveOrgs($userid)
    {
        return Cache::remember('userorg.lstUserActiveOrgs.' . $userid, 600
            , function () use ($userid) {

                return userorg::from("userorgs as uo")
                    ->join('users as u', 'u.id', 'uo.userid')
                    ->where('u.active', 1)
                    ->join('orgs as o', 'o.id', 'uo.orgid')
                    ->where('uo.userid', $userid)
                    ->where('uo.active', 1)
                    ->whereRaw('now() between uo.begdt and ifnull(uo.enddt, now())')
                    ->select('o.name', 'o.id')
                    ->get()
                    ->pluck("name", "id");
            });

        return $lst;
    }

    static function addUserOrg($userid, $orgid, $begdt = null, $by_userid)
    {
        //проверяет и добавляет организацию $orgid к пользователю $userid
        $begdt = $begdt ?? now;
        $by_userid = $by_userid ?? 1;  //администратор

        $rec = self::where([['userid', $userid], ['orgid', $orgid]])
            ->whereRaw('now() between begdt and ifnull(enddt, now())')
            ->first();
        if (!isset($rec)) {
            $rec = new self([
                'userid' => $userid,
                'orgid' => $orgid,
                'begdt' => $begdt,
                'active' => 1,
                'created_by' => $by_userid,
                'updated_by' => $by_userid,
            ]);
            $rec->save();

            objlog::log_info(3, $userid, 'зарегистрирован представителем клиента (id:' . $orgid . ')', 3);
        }
        return $rec->id ?? null;
    }

    public static function isUserOrgCurator($userid, $orgid)
    {
        //Является ли пользователь куратором данной организации?
        //Cache::forget('userorg.isUser' . $userid . 'Org' . $orgid . 'Curator');
        return Cache::remember('userorg.isUser' . $userid . 'Org' . $orgid . 'Curator', 600
            , function () use ($userid, $orgid) {
                if (isset($userid) and isset($orgid))
                    return
                        (self::where([['userid', $userid], ['orgid', $orgid], ['curator', 1], ['active', 1]])
                                ->whereRaw('now() between begdt and ifnull(enddt,now())')
                                ->count() ?? 0 > 0);
                else
                    return false;
            });
    }

}
