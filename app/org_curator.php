<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Cache;

class org_curator extends Model
{

    protected $fillable = ["id", "orgid", "userid", "roleid", "opertypeid", "begdt", "enddt", "active", "created_by",
        "created_at", "updated_by", "updated_at", "staffid"];

    protected $guarded = [];

    //связь с контрагентом
    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function staff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();
    }

    //связь с пользователем
    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userid')->withDefault();
    }

    public function opertype()
    {
        return $this->hasOne(opertype::class, 'id', 'operrtypeid')->withDefault();
    }

    //все доступные кураторы
    static public function AllCurators()
    {
        $rrr = static::from('users as u')
            ->join('usrsysrights as r', 'r.userid', '=', 'u.id')
            ->where('u.id', '<>', 1)
            ->where('r.sysfuncid', '=', 4)/*4=Право на курирование клиентов*/
            ->where('r.active', '=', 1)
            ->where('r.begdt', '<=', now())
            ->whereRaw('ifnull(r.enddt,now())>=now()')
            ->orderBy("u.lname", 'asc')
            ->orderBy('u.fname', 'asc')
            ->selectRaw('u.id, concat( u.lname, " ", u.fname," ", ifnull(u.mname," "), " (",u.email,")") as name')
            ->get();
        return $rrr;
    }

    static public function AllCurators_cache()
    {
        $rrr = Cache::remember('allcurators', now()->addMinutes(15)
            , function () {
                return self::AllCurators()->pluck('name', 'id')->toArray();
            });
        return $rrr;
    }

    //кураторы контрагента ($orgid)
//    static public function OrgCuratorList($orgid)
//    {
//        return static::from('org_curators as c')
//            ->join('orgstaff as os', 'os.id', '=', 'c.staffid')
//            ->join('orgs as o', 'o.id', '=', 'os.orgid')
//            ->where('c.orgid', '=', $orgid)
//            ->orderBy("os.lname", 'asc')
//            ->orderBy('os.fname', 'asc')
//            ->select('c.id', 'c.staffid', 'os.lname', 'os.fname', 'os.mname'
//                , 'os.post', 'o.name as orgname', 'c.active')
//            ->get();
//    }

    static public function OrgCuratorList($orgid)
    {
        //20220204 SNS. Опять изменение концепции - кураторы это сотрудники
        // (которые могут быть и пользователями, но необязательно)
        return static::from('org_curators as c')
            ->join('orgstaff as os', 'os.id', '=', 'c.staffid')
            ->leftjoin('opertypes as ot', 'ot.id', '=', 'c.opertypeid')
            ->leftjoin('orgs as o', 'o.id', '=', 'os.orgid')
            ->where('c.orgid', '=', $orgid)
            ->orderBy("os.lname", 'asc')
            ->orderBy('os.fname', 'asc')
            ->select('c.id', 'c.staffid', 'os.lname', 'os.fname', 'os.mname'
                , db::raw("ifnull(ot.name, '-все-') as opertype_name")
                , 'o.name as orgname', 'c.active'
                , 'c.begdt', 'c.enddt')
            ->get();

//        //20190703 SNS. Изменение концепции. Куратор теперь это пользователь (user)
//        // а не сотрудник (orgstaff)
//        return static::from('org_curators as c')
//            ->join('users as u', 'u.id', '=', 'c.userid')
//            ->leftjoin('opertypes as ot', 'ot.id', '=', 'c.opertypeid')
//            ->leftjoin('orgs as o', 'o.id', '=', 'u.curorgid')
//            ->where('c.orgid', '=', $orgid)
//            ->orderBy("u.lname", 'asc')
//            ->orderBy('u.fname', 'asc')
//            ->select('c.id', 'c.userid', 'u.lname', 'u.fname', 'u.mname'
//                , db::raw("ifnull(ot.name, '-все-') as opertype_name")
//                , 'o.name as orgname', 'c.active' /*, 'c.staffid'*/
//                , 'c.begdt', 'c.enddt')
//            ->get();
    }

    //действующие кураторы контрагента ($orgid)
//    static public function OrgActiveCuratorList($orgid)
//    {
//        //используется в Jobs/OrderMessages
//        $first = orgstaff::from('orgstaff as os')
//            ->join('org_curators as c', 'c.staffid', '=', 'os.id')
//            ->join('users as u', 'u.staffid', '=', 'os.id')
//            ->where('os.active', 1)
//            ->where('c.active', 1)
//            ->where('c.roleid', 1)
//            ->where('c.orgid', $orgid)
//            ->whereRaw('now() between c.begdt and ifnull(c.enddt,now())')
//            ->select('os.id as staffid', 'u.email', 'c.active');
//        //dd($first->toSql());
//
//        $lst = orgstaff::from('orgstaff as os')
//            ->join('users as u', 'u.staffid', '=', 'os.id')
//            ->join('usrsysrights as sr', 'sr.userid', '=', 'u.id')
//            ->where('os.active', 1)
//            ->where('sr.sysfuncid', 61)
//            ->where('sr.active', 1)
//            ->whereRaw('now() between sr.begdt and ifnull(sr.enddt,now())')
//            ->select('os.id as staffid', 'u.email',DB::raw('1 as active'))
//            ->union($first);
//
////    dd($lst->toSql());
//
//        return $lst->get();
//    }
    static public function OrgActiveCuratorList($orgid)
    {
        //используется в Jobs/OrderMessages
        $first = org_curator::from('org_curators as c')
            ->join('users as u', 'u.id', '=', 'c.userid')
            ->where('c.active', 1)
            ->where('c.roleid', 1)
            ->where('c.orgid', $orgid)
            ->whereRaw('now() between c.begdt and ifnull(c.enddt,now())')
            ->select('u.id as userid', 'u.email', 'c.active');
        //dd($first->toSql());

        $lst = User::from('users as u')
            ->join('usrsysrights as sr', 'sr.userid', '=', 'u.id')
            ->where('sr.sysfuncid', 61)
            ->where('sr.active', 1)
            ->whereRaw('now() between sr.begdt and ifnull(sr.enddt,now())')
            ->select('u.id as userid', 'u.email', DB::raw('1 as active'))
            ->union($first);

//    dd($lst->toSql());

        return $lst->get();
    }

    //Является ли сотрудник куратором клиента
    static public function isStfIsOrgCurator($staffid, $orgid)
    {
        $cnt = static::from('org_curators as c')
            ->join('users as os', 'os.id', '=', 'c.userid')
            ->where('c.orgid', '=', $orgid)
            ->where('c.userid', '=', $staffid)
            ->where('c.active', 1)
            ->where('os.active', 1)
            ->where('c.begdt', '<=', now())
            ->whereRaw('ifnull(c.enddt,now())>=now()')
            ->selectraw('1 as cnt')
            ->first();

        return isset($cnt);
    }

    static public function isUserIsOrgCurator($userid, $orgid)
    {
        //является ли пользователь куратором заданной организации?

        $cnt = static::from('org_curators as c')
                ->join('users as os', 'os.id', '=', 'c.userid')
                ->where('os.active', 1)
                ->where([['c.orgid', '=', $orgid], ['c.userid', '=', $userid], ['c.active', 1]])
                ->whereRaw('now() between c.begdt and ifnull(c.enddt,now())')
                ->count() ?? 0;

        return ($cnt > 0);
    }

    static public function curOrgCurator($orgid, $viewdt = null)
    {
        //Исходя из соглашения, что одновременно у организации может быть только один куратор

        $viewdt = (isset($viewdt)) ? $viewdt : now();

        $user = static::from('org_curators as c')
            ->join('users as u', 'u.id', 'c.userid')
            ->where('c.orgid', '=', $orgid)
            ->where('c.active', 1)
            ->whereRaw('? between c.begdt and ifnull(c.enddt,now())', [$viewdt])
            ->select('c.userid')
            ->first();

        return isset($user) ? $user->userid : null;
    }

    static public function defaultCuratorOnDate($begdate = null)
    {
        return Cache::remember('firstGlobalCurator.id', now()->addMinutes(3)
            , function () use ($begdate) {
                $begdate = $begdate ?? now();
                $rec = usrsysright::
                select('userid')
                    ->where('sysfuncid', 157)
                    ->where('active', 1)
                    ->whereraw("str_to_date('" . date_format($begdate, "Y-m-d") . "','%Y-%m-%d') between begdt and ifnull(enddt,now())")
                    ->orderBy('begdt')->first();
                return $rec->userid ?? null;
            });
    }

    static public function addDefaultCurator2Org($orgid, $begdate, $userid = null)
    {//Добавление первого-попавшегося Глобального куратора в качестве куратора для заданной организации

        if (!isset($orgid))
            return null;

        $begdate = $begdate ?? now();

        $curatorid = self::defaultCuratorOnDate($begdate);

        if (isset($curatorid)) {

            $rec = new org_curator([
                "orgid" => $orgid,
                "userid" => $curatorid,
                //"staffid" => 0,
                "roleid" => 1,
                "begdt" => $begdate,
                "active" => 1,
                "created_at" => now(),
                "created_by" => $userid,
                "updated_at" => now(),
                "updated_by" => $userid,
            ]);
            $rec->save();
            return $curatorid;
        }
        return null;
    }

    static public function addOrgCurator($orgid, $mngrid, $begdt = null, $userid = null)
    {
        //Добавляет, при необходимости пользователя $mngrid куратором организации $orgid, с момента $begdt
        //Предыдущего куратора ограничивает по дате
        $begdt = $begdt ?? now();

        $onlyOneOrgCurator = (env('ONE_CURATOR_FOR_ORG') == 1); //Только один куратор на организацию

        if ($onlyOneOrgCurator) {
            //Только один куратор в единицу времени.
            // Поэтому найдем текущего куратора и если это не нужный, сотрудник, то создадим запись для нужного,
            // а текущего ограничим
            $curMngrID = self::curOrgCurator($orgid, $begdt);
            if (!isset($curMngrID) or $curMngrID <> $mngrid) {

                //add
                $rec = new org_curator([
                    "orgid" => $orgid,
                    "userid" => $mngrid,
                    //"staffid" => 0,
                    "roleid" => 1,
                    "begdt" => $begdt,
                    "active" => 1,
                    "created_at" => now(),
                    "created_by" => $userid,
                    "updated_at" => now(),
                    "updated_by" => $userid,
                ]);
                $rec->save();

                //Завершим кураторство предыдущего менеджера (предыдущей секундой)
//            info(' $curMngrID =' . $curMngrID);
                if (isset($curMngrID))
                    self::endOrgCurator($orgid, $curMngrID, $begdt, $userid);
            }

            //TODO: !!! Если вызвать endOrgCurator перед(!) добавлением нового куратора, то в добавлении будет использовано
            // значение $begdt, модифицированное в endOrgCurator (?!!)
        } else {
            //Допускается несколько кураторов одновременно для одной организации
            // проверим - является ли указанный пользователь текущим куратором?
            if (!self::isUserIsOrgCurator($mngrid, $orgid)) {
                //add
                $rec = new org_curator([
                    "orgid" => $orgid,
                    "userid" => $mngrid,
                    "roleid" => 1,
                    "begdt" => $begdt,
                    "active" => 1,
                    "created_at" => now(),
                    "created_by" => $userid,
                    "updated_at" => now(),
                    "updated_by" => $userid,
                ]);
                $rec->save();

            }
        }
        return null;
    }

    static public function endOrgCurator($orgid, $mngrid, $enddt = null, $userid = null)
    {
        //Приостанавливает кураторство менеджера $mngrid над организацией $orgid, с момента $begdt - 1с
        if (isset($orgid) and isset($mngrid)) {
            $t_enddt = $enddt ?? now();
//            info($t_enddt);
            $t_enddt->modify("-1 second");
//            info($t_enddt);

            self::where('orgid', $orgid)
                ->where('userid', $mngrid)
                ->where('enddt', null)
                ->update([
                    'enddt' => $t_enddt,
                    "updated_at" => now(),
                    "updated_by" => $userid,
                ]);
        }
        return null;
    }

    static public function updOrgCurator($orgid, $mngrid, $isCurator, $begdt = null, $enddt = null, $userid = null)
    {
        //Добавляет/Убирает куратора $mngrid в организацию $orgid в зависимости от $isCurator=1/0
        if ($isCurator == 1) {
            $begdt = $begdt ?? now();
            self::addOrgCurator($orgid, $mngrid, $begdt, $userid);
        } else {
            $enddt = $enddt ?? now();
            self::endOrgCurator($orgid, $mngrid, $enddt, $userid);
        }

    }
}
