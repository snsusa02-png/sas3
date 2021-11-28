<?php

namespace App;

use Cache;
use DB;
use Illuminate\Database\Eloquent\Model;

use App\Traits\DeleteTrait;


class group extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    public function grptype()
    {
        return $this->hasOne(grptype::class, 'id', 'grptypeid')
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

    static public function addGrpItem($grpid, $sysobjid, $objid, $objname, $by_userid)
    {
        //Добавляет ($sysobjid + $objid) в группу $grpid
        //Если группа имеет grptypeid, то исключаем ($sysobjid + $objid) из других групп такого же типа
        $itmid = null;

        if (isset($grpid)) {

            //проверим, возможно ($sysobjid + $objid) уже находится в этой группе?
            $itm = grpitem::where('grpid', $grpid)
                ->where('sysobjid', $sysobjid)
                ->where('objid', $objid)
                ->first();

            if (!isset($itm)) {
                //Проверим существование группы, заодно возьмем ее тип
                $grp = self::select('grptypeid')->find($grpid);
                if (isset($grp)) {
                    $grptypeid = $grp->grptypeid;

                    if (isset($grptypeid))
                        //Удалим этот объект ($sysobjid + $objid) из других групп данного типа $grptypeid:
                        grpitem::
                        where('sysobjid', $sysobjid)
                            ->where('objid', $objid)
                            ->whereraw('exists(select 1 from groups as g where g.id=grpid and g.grptypeid=?)', [$grptypeid])
                            ->delete();

                    //add item 2 group
                    $itm = new grpitem([
                        'grpid' => $grpid,
                        'sysobjid' => $sysobjid,
                        'objid' => $objid,
                        'objname' => mb_substr($objname, 0, 36)
                    ]);
                    $itm->save();
                    $itmid = $itm->id;
                }
            } else
                $itmid = $itm->id;

        }
        return $itmid;
    }

    static public function lstOrgGroups_cache()
    {
        return Cache::remember('lstOrgGroups.', now()->addMinutes(15)
            , function () {
                $t_grps = DB::table('groups as g')
                    ->select('g.grptypeid', 'g.id', 'g.name', 'gt.name as grptypename')
                    ->join('grptypes as gt', 'gt.id', '=', 'g.grptypeid')
                    ->where('gt.active', 1)
                    ->whereraw('exists (select 1 from grpitems as gl where gl.grpid=g.id and gl.sysobjid=111)')
                    ->orderby('gt.ordr')
                    ->orderby('gt.name')
                    ->orderby('g.ordr')
                    ->orderby('g.name')
                    ->get();
                if (isset($t_grps)) {
                    //Преобразуем в массив, нужный для отображения в виде выпадающего списка с группировкой -----------------------
                    $arr = ["" => ""];
                    $curTypeID = null;
                    $curTypeName = null;
                    $lst = [];
                    foreach ($t_grps as $s) {
                        if ($s->grptypeid <> $curTypeID) {
                            if (isset($curTypeID)) {
                                $arr += [$curTypeName => $lst];  //Нужная форма добавления элемента в массив!
                            }
                            $lst = [];
                            $curTypeID = $s->grptypeid;
                            $curTypeName = $s->grptypename;
                        }
                        $lst += [$s->id => $s->name];
                    }
                    if (isset($curTypeID)) {
                        $arr += [$curTypeName => $lst];
                    }
                    //------------------------------------------------------------------------------------------
                } else
                    $arr = null;
                return $arr;
            });
    }

    static public function lstAllGroups_cache()
    {
        return Cache::remember('lstAllGroups.', now()->addMinutes(15)
            , function () {
                $arr = self::from('groups as g')
                    ->select('g.id', 'g.name')
                    ->orderby('g.ordr')
                    ->orderby('g.name')
                    ->get()
                    ->pluck('name', 'id')->toArray();
                return $arr;
            });
    }

    public static function newGroupByExtID($extsysid, $grpextid, $forsysobjid, $grptypeid, $grpname, $userid = null)
    {
        //перепроверим - вдруг уже есть такая группа:
        //!!! нужно проверять уникальность в пределах типа группы (821)
        //$grpid = objextid::objid_by_extsysid_extid($extsysid, 822, $grpextid);
        //используем знания специфики связи между типом группы и группой
        $grpid = objextid::from('objextids as ei')
                ->join('groups as g', 'g.id', 'ei.objid')
                ->join('grptypes as gt', 'gt.id', 'g.grptypeid')
                ->where('gt.forsysobjid', $forsysobjid)
                ->where('ei.extsysid', $extsysid)
                ->where('ei.sysobjid', 822)
                ->where('ei.extid', $grpextid)
                ->select('ei.objid')
                ->first()->objid ?? null;
        if (!isset($grpid)) {

            try {
                DB::beginTransaction();

                //Добавить организацию
                $group = new group([
                    "grptypeid" => $grptypeid,
                    "name" => $grpname,
                    "active" => 1,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $group->save();
                $grpid = $group->id;

                //Добавить идентификатор группы во внешней системе $extsysid
                $ext = new objextid([
                    "extsysid" => $extsysid,
                    "sysobjid" => 822,
                    "objid" => $grpid,
                    "extid" => $grpextid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $ext->save();

                DB::commit();

            } catch (\Exception $e) {
                //DB::rollback();
                //$this->log->fatalerror($e->getMessage());
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
                //return null;
            } finally {
            }

        }
        return $grpid;
    }


}
