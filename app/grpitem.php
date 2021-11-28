<?php

namespace App;

use Cache;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;

class grpitem extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid')
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

    public static function addOrRmvItem($grptypeid, $grpid, $sysobjid, $objid, $objname)
    {
        //Если $grpid не пуст - добавляет элемент ($sysobjid + $objid) в группу $grpid
        //Если $grpid пуст -  удаляет элемент ($sysobjid + $objid) из групп типа $grptypeid

        if (isset($grpid)) {
            //поищем
            $rec = self::where('grpid', $grpid)
                ->where('sysobjid', $sysobjid)
                ->where('objid', $objid)->first();

            if (!isset($rec)) {
                //Будем добавлять, но сначала удалим этот элемент из других групп этого типа $grptypeid
                self::from('grpitems')
                    ->where('sysobjid', $sysobjid)
                    ->where('objid', $objid)
                    ->whereraw('exists (select 1 from groups as g where g.id=grpid and g.grptypeid=?)', [$grptypeid])
                    ->delete();

                //Добавим элемент в группу
                $rec = new grpitem([
                    'grpid' => $grpid,
                    'sysobjid' => $sysobjid,
                    'objid' => $objid,
                    'objname' => $objname,
                ]);
                $rec->save();

                Cache::forget('org_gt_groups_.' . $objid);
                Cache::forget('org_aux_groups_.' . $objid);
            }
        } else {
            //удалим элемент из групп данного типа $grptypeid
            self::from('grpitems')
                ->where('sysobjid', $sysobjid)
                ->where('objid', $objid)
                ->whereraw('exists (select 1 from groups as g where g.id=grpid and g.grptypeid=?)', [$grptypeid])
                ->delete();

            Cache::forget('org_gt_groups_.' . $objid);
            Cache::forget('org_aux_groups_.' . $objid);
        }

        return;
    }
}
