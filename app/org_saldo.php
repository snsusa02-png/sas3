<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Cache;
use Illuminate\Support\Facades\DB;

class org_saldo extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')
            ->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')
            ->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function cache_clear($rec = null)
    {
        //Забудем связанный кэш -------------------------------------
        if (isset($rec)) {
            Cache::forget('lstSaldos_' . $rec->orgid);
        }
        Cache::forget('informer_saldos');
        Cache::forget('informer_ownorg_saldo_details');
        //-----------------------------------------------------------
    }


    static public function lstSaldos_cached($orgid)
    {
        //массив записей с организациями-продавцами (Владельцами)
        //Cache::forget('lstSaldos_' . $orgid);
        return Cache::remember('lstSaldos_' . $orgid, now()->addMinutes(15)
            , function () use ($orgid) {
                return self::from('org_saldos as s')
                    ->join('orgs as oo', 'oo.id', 's.ownorgid')
                    ->select('s.id', 's.ownorgid', 's.ondate', 's.saldo', 's.active', 'oo.name as ownorgname'
                    , 's.aligmentdate'
                    , db::raw("(select sum(opersum * if(fo.srcorgid=s.orgid,1,-1))
                        from obj_finopers as fo
                        where fo.operdate>=s.ondate
	                        and fo.srcorgid in (s.orgid,s.ownorgid)
                            and fo.tgtorgid in (s.orgid,s.ownorgid)
                        ) as opersum")
                        , db::raw("(select max(operdate)
                        from obj_finopers as fo
                        where fo.operdate>=s.ondate
	                        and fo.srcorgid in (s.orgid,s.ownorgid)
                            and fo.tgtorgid in (s.orgid,s.ownorgid)
                        ) as max_operdate")
                    )
                    ->where('s.orgid', $orgid)
                    ->orderby('oo.name', 'asc')
                    ->orderby('s.ownorgid', 'asc')
                    ->orderby('s.ondate', 'desc')
                    ->get();
            });
    }

    public static function informer_saldos()
    {
        //Cache::forget('informer_saldos');
        return Cache::remember('informer_saldos', now()->addMinutes(15)
            , function () {

                $ownorgs = org::getFor(['flagtypeid' => 12, 'active' => 1], ['o.id', 'o.name as ownorgname']);

                foreach ($ownorgs as $ownorg) {
                    $ownorg->saldo = org::from('orgs as o')
                        ->whereRaw("not exists (select 1 from mr_opers as mro where mro.suporgid=o.id)")
                        ->whereRaw("not exists (select 1 from objflags as f where f.flagtypeid=12 and f.sysobjid=111 and f.objid=o.id)")
                        ->sum(db::raw("orgSaldo_onDate(o.id, {$ownorg->id}, null)"));

                    $ownorg->saldo_sup = org::from('orgs as o')
                        ->whereRaw("exists (select 1 from mr_opers as mro where mro.suporgid=o.id)")
                        ->whereRaw("not exists (select 1 from objflags as f where f.flagtypeid=12 and f.sysobjid=111 and f.objid=o.id)")
                        ->sum(db::raw("-orgSaldo_onDate(o.id, {$ownorg->id}, null)"));
                }
                return $ownorgs;
            }
        );
    }

    public static function informer_ownorg_saldo_details()
    {
        $userid = \Auth::user()->id;

        //todo: нужно завязаться на более подходящее право
        if (!usrsysright::isUserHasRightByCode_cached($userid, 'paydocs.read'))
            return null;

        //Cache::forget('informer_ownorg_saldo_details');
        return Cache::remember('informer_ownorg_saldo_details', now()->addMinutes(15)
            , function () {
                //Сводка контрашентов с ненулевым балансом по всем организациям ГК

                $ownorgs = org::getFor(['flagtypeid' => 12], ['id', 'name'], [['name', 'asc']]);

                $max_cnt = -1;
                $max_cnt_id = null;
                foreach ($ownorgs as $ownorg) {

                    //Общее сальдо организации ГК
                    $ownorg->saldo = org::from('orgs as o')
                        ->sum(db::raw("orgSaldo_onDate(o.id, {$ownorg->id}, null)"));


                    $ownorgid = $ownorg->id;
                    $sc = "o.id <> '$ownorgid'";

                    //только должники и клиенты с переплатой
                    $sc .= " and orgSaldo_onDate(o.id, {$ownorgid}, null)<>0";

                    //не входят в ГК
                    $sc .= " and not exists (select 1 from objflags as f where f.flagtypeid=12 and f.sysobjid=111 and f.objid=o.id)";

                    //Только Не поставщики
                    //$sc .= " and not exists (select 1 from objflags as f where f.flagtypeid=13 and f.sysobjid=111 and f.objid=o.id)";
                    $sc .= " and not exists (select 1 from mr_opers as mro where mro.suporgid=o.id)";

                    $recs = org::from('orgs as o')
                        ->whereRaw($sc);

                    $recs = $recs->select(
                        'o.id as orgid', 'o.name as orgname'
                        , db::raw("orgSaldo_onDate(o.id, {$ownorgid}, null) as org_saldo")
                        , db::raw("(select group_concat( trim(concat(ifnull(u.fname,''),' ', u.lname)) SEPARATOR ', ')
                            from orgstaff as u join org_curators as oc
                            on oc.staffid=u.id and oc.active=1
                                and now() between oc.begdt and ifnull(oc.enddt,now())
                            where oc.orgid=o.id
                            ) as org_curators")

                    )
                        ->orderby('org_saldo', 'asc')
                        ->get();

                    $ownorg->recs = $recs;
                    if (count($recs) > $max_cnt) {
                        //Считаем, что нужно привлечь внимание к организации с наибольшим кол-вом контрагентов с ненулевым сальдо
                        $max_cnt = count($recs);
                        $max_cnt_id = $ownorg->id;
                    }

                    //Для поставщиков ---------------------------------------------------------------
                    $sc = "o.id <> '$ownorgid'";

                    //не входят в ГК
                    $sc .= " and not exists (select 1 from objflags as f where f.flagtypeid=12 and f.sysobjid=111 and f.objid=o.id)";

                    //только должники и клиенты с переплатой
                    $sc .= " and orgSaldo_onDate(o.id, {$ownorgid}, null)<>0";

                    //Только поставщики
                    //$sc .= " and exists (select 1 from objflags as f where f.flagtypeid=13 and f.sysobjid=111 and f.objid=o.id)";
                    $sc .= " and exists (select 1 from mr_opers as mro where mro.suporgid=o.id)";

                    $recs = org::from('orgs as o')
                        ->whereRaw($sc);

                    $recs = $recs->select(
                        'o.id as orgid', 'o.name as orgname'
                        , db::raw("-orgSaldo_onDate(o.id, {$ownorgid}, null) as org_saldo")
                        , db::raw("(select group_concat( trim(concat(ifnull(u.fname,''),' ', u.lname)) SEPARATOR ',')
                            from users as u join org_curators as oc
                            on oc.userid=u.id and oc.active=1
                                and now() between oc.begdt and ifnull(oc.enddt,now())
                            where oc.orgid=o.id
                            ) as org_curators")

                    )
                        ->orderby('org_saldo', 'asc')
                        ->get();

                    $ownorg->sup_recs = $recs;
                }

                //пометим самую "привлекательную" организацию из ГК
                foreach ($ownorgs as $ownorg) {
                    $ownorg->active = ($ownorg->id == $max_cnt_id) ? "active" : "";
                }
                //dd($ownorgs);

                return $ownorgs;
            }
        );
    }

    public static function informer_all_saldos()
    {
        $userid = \Auth::user()->id;

        //todo: нужно завязаться на более подходящее право
        if (!usrsysright::isUserHasRightByCode_cached($userid, 'paydocs.read'))
            return null;

        //Cache::forget('informer_all_saldos');
        return Cache::remember('informer_all_saldos', now()->addMinutes(15)
            , function () use ($userid) {
                //Сводка контрагентов с ненулевым балансом по всем организациям ГК

                $sc_task = "";
                //Если у пользователя нет права в Задачах, то показывать только публичные задачи и задачи в которых он участвует
                if ( !usrsysright::isUserHasRightByCode_cached($userid, 'tasks.read'))
                    $sc_task = " and ( tsk.public_lvl=2 or tsk.inituserid={$userid}
                                    or exists (select 1 from task_users r where r.taskid=tsk.id and userid={$userid}) )";

                return DB::select( DB::raw(
                    "select oo.name as ownorgname, o.name as orgname, a.*
                    , orgSaldo_onDate(o.id,oo.id,null) as saldo
                    , (select group_concat( trim(concat(ifnull(os.fname,''),' ', os.lname)) SEPARATOR ',')
                            from orgstaff as os
                            join org_curators as oc
                            on oc.staffid=os.id and oc.active=1 and now() between oc.begdt and ifnull(oc.enddt,now())
                            where oc.orgid=o.id
                            ) as org_curators
                    , (select group_concat( concat(tsk.name,'|',tsk.id)  SEPARATOR ';')
                            from tasks as tsk
                            where tsk.srcsysobjid=111 and tsk.srcobjid=o.id
                and tsk.statusid is null
                            {$sc_task}
                            ) as tasks
from (
    SELECT srcorgid as ownorgid, tgtorgid as orgid FROM `obj_finopers`
where exists (select 1 from objflags as f where f.sysobjid=111 and f.objid=srcorgid and f.flagtypeid=12)
   and not exists(select 1 from objflags as f where f.sysobjid=111 and f.objid=tgtorgid and f.flagtypeid=12)
union
SELECT tgtorgid as ownorgid, srcorgid as orgid FROM `obj_finopers`
where exists (select 1 from objflags as f where f.sysobjid=111 and f.objid=tgtorgid and f.flagtypeid=12)
  and not exists(select 1 from objflags as f where f.sysobjid=111 and f.objid=srcorgid and f.flagtypeid=12)
union
select s.ownorgid, s.orgid from org_saldos s
    ) as a
    join orgs as oo on oo.id=a.ownorgid
        join orgs as o on o.id=a.orgid
        where a.ownorgid<>a.orgid
order by saldo asc"));

            }
        );
    }

}
