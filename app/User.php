<?php

namespace App;

use DB;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Cache;

class User extends Authenticatable
{
    use Notifiable;
    use \App\Traits\DeleteTrait;

    static public $prefix = 'users';
    static public $sysobjid = 3;


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
        'lname', 'fname', 'mname', 'note', 'phone',
        'profile_image',
        'curorgid', 'active',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function curorg()
    {
        return $this->hasOne(org::class, 'id', 'curorgid')->withDefault();
    }

//    public function mychat_user()
//    {
//        return $this->hasOne(mychat_user::class, 'userid', 'id')->withDefault();
//    }

    public function short_fio()
    {
        $rslt = null;
        if (isset($this->lname)) {
            $rslt = $this->lname;
            if (isset($this->fname)) {
                $rslt = $rslt . ' ' . mb_substr($this->fname, 0, 1) . '.';
                if (isset($this->mname))
                    $rslt = $rslt . mb_substr($this->mname, 0, 1) . '.';
            }
        }
        return $rslt;
    }

    public function getshortfioAttribute()
    {
        $rslt = null;
        if (isset($this->lname)) {
            $rslt = $this->lname;
            if (isset($this->fname)) {
                $rslt = $rslt . ' ' . mb_substr($this->fname, 0, 1) . '.';
                if (isset($this->mname))
                    $rslt = $rslt . mb_substr($this->mname, 0, 1) . '.';
            }
        }
        return $rslt;
    }

    public function getflNameAttribute()
    {
        $rslt = null;
        if (isset($this->fname)) {
            $rslt = $this->fname;
            if (isset($this->lname)) {
                $rslt = $rslt . ' ' . $this->lname;
            }
        }
        return $rslt;
    }

    public function getInfoAttribute()
    {
        $rslt = null;
        if (isset($this->id)) {
            $rslt = $this->name . ' / ' . $this->email;
        }
        return $rslt;
    }

    public static function setCurOrgID($userid, $orgid)
    {
        $rec = self::find($userid);
        if (isset($rec)) {
            $rec->curorgid = $orgid;
            $rec->save();
        }
    }


    public static function getCurOrgID($userid)
    {
        //возвращает текущую организацию пользователя
        return self::find($userid)->curorgid;
    }

    public static function getOrgID($userid)
    {
//        return Cache::remember('User.getOrgID.' . $userid, 200
//            , function () use ($userid) {

        if (isset($userid))
            $orgid = self::from('users as u')
                ->where('u.id', $userid)
                ->where('u.active', 1)
                ->selectraw('u.curorgid as orgid')
                ->first()->orgid;
        else
            $orgid = null;

        if ($orgid === null)
            $orgid = 0; //чтобы не тянуть нул дальше, и запрос не сломался, а выдал пустоту
        return $orgid;
//            });
    }

    public static function isWorkInOwnOrg($userid)
    {
//        return Cache::remember('isWorkInOwnOrg' . $userid, now()->addMinutes(5)
//            , function () use ($userid) {
        return objflag::IsSetObjFlag(111, self::getCurOrgID($userid), 12);
//            });

    }

    public static function getStaffID_($userid)
    {

        $Staff = DB::table('users as u')
            ->join('orgstaff as s', 's.id', '=', 'u.StaffID')
            ->where('u.id', $userid)
            ->select('s.id')->get()->first();
        if ($Staff === null) {
            $StaffID = 0; //чтобы не тянуть нул дальше, и запрос не сломался, а выдал пустоту
        } else {
            $StaffID = $Staff->id;
        }

        return $StaffID;

    }


    static public function getUserInfo4Mail($userid)
    {
        return \App\User::
        select(\DB::raw("trim(ifnull(concat(lname,' ',fname,' ',mname),name)) as fiofull,
                                trim(ifnull(concat(lname,' ',substring(fname,1,1),'. ',substring(mname,1,1),'.'),name)) as fioshort,
                                trim(email) as email,
                                trim(ifnull(concat(fname,' ',mname),name)) as greeting"))
            ->find($userid);
    }

    public static function getUserInfo($userid)
    {
        if (!is_null($userid) and $userid > 0) {

            $info = Cache::remember('UserInfo' . $userid, now()->addMinutes(15)
                , function () use ($userid) {

                    $info = user::from('users as u')
                        ->leftjoin('orgs as o', 'o.id', '=', 'u.curorgid')
                        ->where('u.id', $userid)
                        //->where('u.active', 1)
                        ->select('u.curorgid', 'u.email', 'o.name as orgname'
                        )
                        ->selectraw("ifnull(concat(u.lname,' ',u.fname,' ',u.mname), u.name) as userName")
                        ->orderby('u.created_at', 'desc')
                        ->first();

                    $lst = userorg::lstUserActiveOrgs($userid);
                    if (isset($lst))
                        $info->userorgs = $lst;
                    return $info;
                });

        } else $info = null;
        return $info;
    }

    static public function getFIO($userid)
    {
        return \App\User::select(\DB::raw("trim(ifnull(concat(lname,' ',fname,' ',mname),name)) as fio"))
                ->find($userid)->fio ?? null;
    }

    static public function getFIOshort($userid)
    {
        return \App\User::
            select(\DB::raw("trim(ifnull(concat(lname,' ',substring(fname,1,1),'. ',substring(mname,1,1),'.'),name)) as fio"))
                ->find($userid)->fio ?? null;
    }


    static public function rqGetListUser($id, $noStaffid, $orgid)
    {
        $userid = \Auth::User()->id;

        $rq = static::from('users as u')
            ->select(DB::raw('u.*')
                , DB::raw('date(u.created_at) as regdate')
            //, DB::raw('userfiobyid(u.id) as staffio')
            //, DB::raw('userfiobyid(u.created_by) as created_by_name')
            //, DB::raw('userfiobyid(u.updated_by) as updated_by_name')
            )
            ->selectraw('(SELECT GROUP_CONCAT(o.name SEPARATOR "; ")
                FROM userorgs AS uo
                JOIN orgs AS o ON o.id = uo.orgid
                WHERE uo.active = 1 and uo.userid=u.id
                ORDER BY uo.begdt) as lstUserOrgs');

        if ($userid != 1)
            $rq = $rq->where('u.id', '!=', 1);

        if (!is_null($id)) {
            $rq->where('u.id', $id);
        } elseif ($noStaffid) {
            $rq->whereIsNull('u.staffid');
        } elseif (!is_null($orgid)) {
            $rq->where('os.orgid', $orgid);
        }
        //dd($rq->toSql());
        return $rq;
    }

    public function getImageAttribute()
    {
        return $this->profile_image;
    }

    /**
     * Get the user's full name.
     *
     * @return string
     */
    public function getFullNameAttribute()
    {
        return "{$this->fname} {$this->lname}";
    }

    public function getFirstLastAttribute()
    {
        return "{$this->fname} {$this->lname}";
    }

    public function getAvatarAttribute()
    {
        $rslt = $this->profile_image;
        if ($rslt)
            $rslt = "<img src='$rslt' style='max-width: 32px; border-radius: 50%'>";
        return $rslt;
    }

    public function isOnline()
    {//Определение в системе ли пользователь по наличию значения в кэше добавляемого через middleware IsUserOnline
        //кэш обновляется при каждом переходе
        return Cache::has('user-is-online-' . $this->id);
//        return $this->id;
    }

    public function getOnlineAttribute()
    {//Определение в системе ли пользователь по запросу к таблице sessions
        //вроде бы там появляются только записи при логине, поэтому - менее достоверно
        $activity = DB::table('sessions')
            ->where('user_id', $this->id)
            ->where('last_activity', '>', strtotime("-5 minutes"))
            ->count();
        return $activity ? trans('user.online') : trans('user.offline');
    }

    static public function saleUsers()
    {
        //Массив продавцов/менеджеров по продажам компании
        // используется для:
        // - распределения Плана продаж (SalePlans)

        //todo:: добавить связь с Org_Curators - что бы взять не номинальных, а менеджеров реально закрепленных за клиентами

        //            Cache::forget('saleusers.list');
        return
            Cache::remember('saleusers.list', now()->addMinutes(10)
                , function () {
                    return User::from('users as u')
                        ->select('id', 'name')
                        ->whereRaw('exists(SELECT 1 FROM usrsysrights r
                                where sysfuncid=4
                                and now() between begdt and ifnull(enddt,now())
                                and r.active=1
                                and r.userid=u.id)')
                        ->where('u.id', '<>', 1)//исключим администратора
                        ->get();
//                        ->pluck("name", "id");
                });
    }

    public static function newItmByExtID($extsysid, $itmextid, $itmdata, $userid = null)
    {
        $sysobjid = 3;
        //перепроверим - вдруг уже есть такая запись:
        $itmid = objextid::objid_by_extsysid_extid($extsysid, $sysobjid, $itmextid);
        if (!isset($itmid) and isset($itmextid)) {

//            \Log::debug(var_dump($itmdata));

            try {
                DB::beginTransaction();

                $userid = $itmdata['created_by'] ?? 0;
                $itmdata['updated_by'] = $itmdata['updated_by'] ?? $userid;
                $itmdata['email'] = $itmdata['email'] ?? uniqid();//если вдруг не передан
                $itmdata['password'] = $itmdata['password'] ?? bcrypt(uniqid());//если вдруг не передан

                //Добавить запись о единице измерения
                $rec = new User($itmdata);
                $rec->save();
                $itmid = $rec->id;


                //Добавить идентификатор товара во внешней системе $extsysid
                $ext = new objextid([
                    "extsysid" => $extsysid,
                    "sysobjid" => $sysobjid,
                    "objid" => $itmid,
                    "extid" => $itmextid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $ext->save();

                DB::commit();

            } catch
            (\Exception $e) {
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
            } finally {
            }
        }
        return $itmid;
    }

    public static function UsersWithRightID($rightid)
    {
        //Заготовка списка пользователей с заданным правом
        Cache::forget('UsersWithRight_' . $rightid);
        return Cache::remember('UsersWithRight_' . $rightid, now()->addMinutes(15)
            , function () use ($rightid) {

                $recs = self::from('users as u')
                    ->where('u.active', 1)
                    ->whereExists(function ($q1) use ($rightid) {
                        $q1->select(DB::raw(1))
                            ->from('usrsysrights as ur')
                            ->whereRaw('ur.userid=u.id')
                            ->where([
                                ['ur.sysfuncid', '=', $rightid],
                                ['ur.active', '=', 1],
                            ])
                            ->whereRaw('now() between ur.begdt and ifnull(ur.enddt, now())');
                    })
                    ->get();
                return $recs;
            });
    }

    public static function UsersWithRightCode($rightcode)
    {
        //Заготовка списка пользователей с заданным правом
        Cache::forget('UsersWithRight_' . $rightcode);
        return Cache::remember('UsersWithRight_' . $rightcode, now()->addMinutes(15)
            , function () use ($rightcode) {

                $recs = self::from('users as u')
                    ->where('u.active', 1)
                    ->whereExists(function ($q1) use ($rightcode) {
                        $q1->select(DB::raw(1))
                            ->from('usrsysrights as ur')
                            ->join('sysfuncs as sf', 'sf.id', 'ur.sysfuncid')
                            ->whereRaw('ur.userid=u.id')
                            ->where([
                                ['sf.code', '=', $rightcode],
                                ['ur.active', '=', 1],
                            ])
                            ->whereRaw('now() between ur.begdt and ifnull(ur.enddt, now())');
                    })
                    ->get();
                return $recs;
            });
    }

    public static function UsersWithRightForOrgByCode($rightcode, $orgid)
    {
        //Заготовка списка пользователей с заданным правом

        if (isset($orgid)) {

            //Cache::forget('UsersWithRight_' . $rightcode . '_' . $orgid);
            return Cache::remember('UsersWithRight_' . $rightcode . '_' . $orgid, now()->addMinutes(5)
                , function () use ($rightcode, $orgid) {

                    $recs = self::from('users as u')
                        ->where('u.active', 1)
                        ->whereExists(function ($q1) use ($rightcode, $orgid) {
                            $q1->select(DB::raw(1))
                                ->from('usrsysrights as ur')
                                ->join('usrsysright_orgs as ro', function ($join) use ($orgid) {
                                    $join->on('ro.usrsysrightid', 'ur.id')
                                        ->where('ro.active', 1)
                                        ->whereRaw("ifnull(ro.orgid, $orgid) = $orgid");
                                })
                                ->join('sysfuncs as sf', 'sf.id', 'ur.sysfuncid')
                                ->whereRaw('ur.userid=u.id')
                                ->where([
                                    ['sf.code', '=', $rightcode],
                                    ['ur.active', '=', 1],
                                ])
                                ->whereRaw('now() between ur.begdt and ifnull(ur.enddt, now())');
                        })
                        ->get();
                    return $recs;
                });
        } else {
            //сведем задачу к предыдущей
            return self::UsersWithRightCode($rightcode);
        }
    }

    public static function hasRightCodeInOrg($userid, $rightcode, $orgid)
    {
        //Имеет ли пользователь $userid право $rightcode и представляет ли организацию $orgid?

        if (isset($userid) and isset($rightcode) and isset($orgid)) {
            //Cache::forget('UsersWithRight_' . $rightcode . '_Org_' . $orgid);
            return Cache::remember('User:' . $userid . '_RightCode:' . $rightcode . '_Org:' . $orgid, now()->addMinutes(5)
                , function () use ($userid, $rightcode, $orgid) {

                    $cnt = self::from('users as u')
                        ->where('u.id', $userid)
                        ->whereExists(function ($q1) use ($rightcode) {
                            $q1->select(DB::raw(1))
                                ->from('usrsysrights as ur')
                                ->join('sysfuncs as sf', 'sf.id', 'ur.sysfuncid')
                                ->whereRaw('ur.userid=u.id')
                                ->where([
                                    ['sf.code', '=', $rightcode],
                                    ['ur.active', '=', 1],
                                ])
                                ->whereRaw('now() between ur.begdt and ifnull(ur.enddt, now())');
                        })
                        ->whereRaw("exists (select 1 from userorgs as uo where uo.userid=u.id and uo.orgid=" . $orgid
                            . " and uo.active=1 and now() between uo.begdt and ifnull(uo.enddt,now()))")
                        ->count();
                    return ($cnt > 0);
                });
        }
        return null;
    }


    public static function UsersWithRightCodeInOrg($rightcode, $orgid)
    {
        //Заготовка списка пользователей с заданным правом,
        // представляющих(!) данную организацию $orgid

        //dd($rightcode, $orgid);
        if (isset($rightcode) and isset($orgid)) {
            //Cache::forget('UsersWithRight_' . $rightcode . '_Org_' . $orgid);
            return Cache::remember('UsersWithRight_' . $rightcode . '_Org_' . $orgid, now()->addMinutes(5)
                , function () use ($rightcode, $orgid) {

                    $recs = self::from('users as u')
                        ->where('u.active', 1)
                        ->whereExists(function ($q1) use ($rightcode) {
                            $q1->select(DB::raw(1))
                                ->from('usrsysrights as ur')
                                ->join('sysfuncs as sf', 'sf.id', 'ur.sysfuncid')
                                ->whereRaw('ur.userid=u.id')
//                            ->where([
//                                ['sf.code', '=', $rightcode],
//                                ['ur.active', '=', 1],
//                            ])
                                ->where('sf.code', $rightcode)
                                ->where('ur.active', 1)
                                ->whereRaw('now() between ur.begdt and ifnull(ur.enddt, now())');
                        })
                        ->whereRaw("exists (select 1 from userorgs as uo where uo.userid=u.id and uo.orgid=" . $orgid
                            . " and uo.active=1 and now() between uo.begdt and ifnull(uo.enddt,now()))")
                        ->get();
                    return $recs;
                });
        }
        return null;
    }

    public static function UsersWithRightIdOrgId($rightid, $orgid)
    {
        //Заготовка списка пользователей с заданным правом, работающих в организации $orgid
        Cache::forget('UsersWithRight_' . $rightid . '_Org_' . $orgid);
        return Cache::remember('UsersWithRight_' . $rightid . '_Org_' . $orgid, now()->addMinutes(15)
            , function () use ($rightid, $orgid) {

                $recs = self::from('users as u')
                    ->join('orgstaff as os', 'os.userid', 'u.id')
                    ->where('os.orgid', $orgid)
                    ->where('os.active', 1)
                    ->where('u.active', 1)
                    ->whereExists(function ($q1) use ($rightid) {
                        $q1->select(DB::raw(1))
                            ->from('usrsysrights as ur')
                            ->whereRaw('ur.userid=u.id')
                            ->where([
                                ['ur.sysfuncid', '=', $rightid],
                                ['ur.active', '=', 1],
                            ])
                            ->whereRaw('now() between ur.begdt and ifnull(ur.enddt, now())');
                    })
                    ->get();
                return $recs;
            });
    }


    public static function ClientCuratorsForOrgID($orgid)
    {
        //Заготовка списка пользователей, курирующих заказы данной организации со стороны клиента
        Cache::forget('ClientCuratorsForOrgID' . $orgid);
        return Cache::remember('ClientCuratorsForOrgID' . $orgid, now()->addMinutes(15)
            , function () use ($orgid) {

//                $recs = self::from('users as u')
//                    ->whereExists(function ($q1) use ($orgid) {
//                        $q1->select(DB::raw(1))
//                            ->from('userorgs as uo')
//                            ->whereRaw('uo.userid=u.id')
//                            ->where([
//                                ['uo.orgid', '=', $orgid],
//                                ['uo.curator', '=', 1],
//                                ['uo.active', '=', 1],
//                            ])
//                            ->whereRaw('now() between uo.begdt and ifnull(uo.enddt, now())');
//                    })->get();

                //Признак кураторства над организацией хранится в спр-ке org_curators
                // записи имеют период действия (begdt-enddt) и активацию (active)
                $recs = self::from('users as u')
                    ->whereExists(function ($q1) use ($orgid) {
                        $q1->select(DB::raw(1))
                            ->from('org_curators as oc')
                            ->whereRaw('oc.userid=u.id')
                            ->where([
                                ['oc.orgid', '=', $orgid],
                                ['oc.active', '=', 1],
//                                ['oc.roleid', '=', 1],
                            ])
                            ->whereRaw('now() between oc.begdt and ifnull(oc.enddt, now())');
                    })
                    ->where('u.active', 1)
                    ->get();
                return $recs;
            });
    }


    public function posts()
    {
        return $this->hasMany('App\Post');
    }

    public function is_admin()
    {
        $this->role = 'admin';
        $role = $this->role;
        if ($role == 'admin') {
            return true;
        }
        return false;
    }

    static public function search_cond($params)
    {

        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'userid') {
                        $sc .= " and u.id={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and u.active={$val}";

                    } elseif ($key == 'right_id') {
                        $sc .= " and exists (select 1 from usrsysrights as usr where usr.userid=u.id and sysfuncid={$val}
                                and usr.active=1 and usr.begdt <= now() and ifnull(usr.enddt,now())>=now() )";

                    } elseif ($key == 'right_code') {
                        $sc .= " and exists (select 1 from usrsysrights as usr
                            join sysfuncs as sf on sf.id=usr.sysfuncid
                            where usr.userid=u.id and sf.code='{$val}'
                                and usr.active=1 and usr.begdt <= now() and ifnull(usr.enddt,now())>=now() )";

                    } elseif ($key == 'in_buildobj_staff') {
                        //пользователь должен входить в перечень отв. сотрудников по указанному строит. обЪекту
                        $sc .= " and exists( select 1 from buildobj_staffs as bos
                                join orgstaff os on os.id=bos.staffid and os.active=1
                                where os.userid=u.id and bos.buildobjid={$val} and bos.active=1)";

                    } elseif ($key == 'in_document_initiators') {
                        //пользователь должен быть создателем записей в Архиве документов
                        //$sc .= " and exists( select 1 from documents as d where d.created_by=u.id )";

                    } elseif ($key == 'in_org_curators_now') {
                        //пользователь должен быть куратором организации
                        $sc .= " and exists( select 1 from org_curators as oc where oc.userid=u.id
                            and oc.active=1 and now() between oc.begdt and ifnull(oc.enddt,now()) )";

                    } elseif ($key == 'in_mchn_raids_dispuserid') {
                        //пользователь должен быть Диспетчером в учете рейсов
                        $sc .= " and exists( select 1 from mchn_raids as mr where mr.disp_userid=u.id )";

                    } elseif ($key == 'in_checkrasts_inituserid') {
                        //пользователь должен быть Инициатором в запросах на инспекцию СК
                        $sc .= " and exists( select 1 from checkrqsts as cr where cr.inituserid=u.id )";

                    } elseif ($key == 'mchn_raids_created_by') {
                        //пользователь - создатель записей в mchn_raids
                        $sc .= " and exists( select 1 from mchn_raids as mr where mr.created_by=u.id )";

                    } elseif ($key == 'in_equiprqst_estimator') {
                        //пользователь должен быть оценщиком в заявках на материалы
                        $sc .= " and exists( select 1 from equiprqst_items as eri where eri.est_price_by=u.id )";

                    } elseif ($key == 'in_equiprqst_estimator_for_ctg12') {
                        //пользователь должен быть оценщиком в заявках на материалы по категории "Материалы (срочно)"
                        $sc .= " and exists( select 1 from equiprqst_items as eri
                            join eritm_offers as ofr on ofr.eritmid=eri.id
                            join invoices as inv on inv.id=ofr.invoiceid and inv.categoryid=12
                            where eri.est_price_by=u.id)";

                    } elseif ($key == 'obj_readers_roletypeid') {
                        //пользователь должен входить в список доступа заданого типа объектов $val[0]
                        // с нужным типом роли $val[1]
                        $p1 = $val[0] ?? -1;
                        $p2 = $val[1] ?? -1;
                        $sc .= " and exists( select 1 from obj_readers as r
                                where r.userid=u.id
                                and r.sysobjid={$p1}
                                and r.roletypeid={$p2})";

                    } elseif ($key == 'document_registrator') {
                        //пользователь должен быть создателем записи в Архиве документов
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists( select 1 from documents as d where d.created_by=u.id )";

                    } elseif ($key == 'document_readers') {
                        //пользователь должен быть читателем записи в Архиве документов
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists( select 1 from obj_readers as ojr where ojr.sysobjid=1701 and ojr.userid=u.id )";

                    } elseif ($key == 'in_tasks') {
                        //пользователи - участники задач
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists( select 1 from task_users as tu where tu.userid=u.id )";

                    } elseif ($key == 'in_tasks_for_user') {
                        //пользователи - участники задач, доступных указанному пользователю
                        $sc .= " and exists(select 1 from task_users as tu where tu.userid=u.id
				                    and (exists (select 1 from task_users as utu where utu.taskid=tu.taskid and utu.userid = {$val})
					                    or exists (select 1 from tasks as t where t.id=tu.taskid and t.inituserid = {$val})	) )";
                    }

                }
            }
            //Log::info($sc);
        }

        return $sc;
    }

    static public function lstFor($params)
    {
        //2021-04-23 SNS. универсальный конструктор массива с id, name пользователей
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('users as u')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('u.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-10-14 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember('lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }


    static public function getFor($s_params, $fields = null)
    {
        //2021-04-23 SNS. универсальный конструктор коллекции из записей users
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'u.*';
            //Log::info(json_encode($fields));

            $recs = self::from('users as u')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('u.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

    static public function user_has_acs($userid, $acsid)
    {
        //Истина, если пользователь обладает ключом к заданной категории информации
        return (user_ac::where(['userid' => $userid, 'acsid' => $acsid])->count() > 0);
    }

    static public function user_has_acs_cached($userid, $acsid)
    {
        //Истина, если пользователь обладает ключом к заданной категории информации
        return Cache::remember("user_{$userid}_has_acs_{$acsid}", now()->addMinutes(5)
            , function () use ($userid, $acsid) {
                return self::user_has_acs($userid, $acsid);
            });
    }

}
