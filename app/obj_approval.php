<?php

namespace App;

use App\Events\notifyEvent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class obj_approval extends Model
{
    static public $prefix = 'obj_approvals';
    static public $sysobjid = 942;

    protected $guarded = [];

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'dcsn_userid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'dcsn_orgid')->withDefault();
    }

    public function obj_stage()
    {
        return $this->hasOne(obj_stage::class, 'id', 'stageid')->withDefault();
    }

    public static function chkAndCreate($params)
    {
        if (isset($params)) {

            $obj_approval = obj_approval::where($params)->first();
            if (!isset($obj_approval)) {
                $obj_approval = new obj_approval($params);
                $obj_approval->save();
            }
            return $obj_approval;
        }
    }

    public static function clrPrev($params)
    {
        if (isset($params)) {

            $obj_approval = obj_approval::where($params)
                ->update([
                    'dcsn_userid' => null,
                    'dcsn_staffid' => null,
                    'decision' => null,
                    'dcsn_at' => null,
                    'dcsn_at' => null,
                    'descript' => null,
                ]);
            return;
        }
    }


    public static function saveDecision($search_params, $set_params)
    {
//        $search_params = [
//            'sysobjid' => 870,
//            'objid' => $rec->id,
//            'dcsn_rightid' => $dcsn_rightid,
//            'dcsn_typecode' => $dcsn_rightcode,
//            'stageid' => $rec->stageid,
//        ];

        //todo: не понятно как искать записи с null-значениями?



        $dcsn = self::where($search_params)->first();
        if (isset($dcsn)) {

            if (isset($set_params)) {
                $userid = $userid = \Auth::user()->id;

                $sysobjid = $search_params['sysobjid'];
                $objid = $search_params['objid'];

                $dcsn->dcsn_userid = $set_params['dcsn_userid'] ?? $userid;
                $dcsn->decision = $set_params['decision'] ?? '';
                $dcsn->descript = mb_substr($set_params['descript'] ?? '', 0, 300);
                $dcsn->dcsn_at = now();
                $dcsn->active = 1;
                //dd($dcsn);
                $dcsn->save();

                $orgname = org::find($dcsn->dcsn_orgid)->name ?? $dcsn->dcsn_orgid;
                if ($dcsn->decision == 1)
                    $mess = "Заявка согласована от компании " . $orgname;
                else
                    $mess = "Отказ в согласовании заявки от компании " . $orgname;

                connectify('success', $dcsn->descript ?? ' ', $mess);
                objlog::log_info($sysobjid, $objid, $mess, 3);
                event(new notifyEvent('equiprqsts.mngr_decision', $sysobjid, $objid, $userid));

                // подсчитаем - все ли согласования проведены? -----------
                $cnt = obj_approval::where($search_params)
                    ->where(function ($q) {
                        $q->whereNull('decision')
                            ->orWhere('decision', 0);
                    })
                    ->count();

                //если нет несогласованных - передвинем на след этап
                if ($cnt == 0) {

                    //todo:: переделать
                    if ($sysobjid == 870) {

                        $rec = equiprqst::find($objid);

                        if (isset($rec)) {

                            //чтобы определить след этап нужно найти его
                            $nxtstage = obj_stage::where('sysobjid', 870)
                                ->where('ordr', '>', $rec->stage->ordr)
                                ->orderby('ordr')
                                ->first();
                            $nxt_stageid = $nxtstage->code;


                            equiprqst::Move2Stage($rec->id, $nxt_stageid);

                            $nxt_stagename = obj_stage::where(['sysobjid' => 870, 'code' => $nxt_stageid])->first()->name ?? '';
                            $mess = "Заявка переведена на этап '$nxt_stagename'";
                            connectify('success', ' ', $mess);

                            event(new notifyEvent('equiprqsts.set_stage_' . $nxt_stageid, $sysobjid, $rec->id, $userid));
                        }
                    } else {
                        $rec = null;
                    }


                } else {
                    // проверим, если есть хоть один отказ, то вернем на черновик -----------
                    $cnt = obj_approval::where($search_params)->where('decision', 0)->count();
                    if ($cnt > 0) {

                        if ($sysobjid == 870) {

                            $rec = equiprqst::find($objid);

                            if (isset($rec)) {

                                $nxt_stageid = 1;
                                //чтобы определить пред этап нужно найти его
                                $nxtstage = obj_stage::where('sysobjid', $sysobjid)
                                    ->where('ordr', '<', $rec->stage->ordr)
                                    ->orderby('ordr','desc')
                                    ->first();
                                $nxt_stageid = $nxtstage->code;


                                equiprqst::Move2Stage($rec->id, $nxt_stageid);

                                $nxt_stagename = obj_stage::where(['sysobjid' => $sysobjid, 'code' => $nxt_stageid])->first()->name ?? '';
                                $mess = "Заявка переведена на этап '$nxt_stagename'";

                                connectify('danger', ' ', $mess);
                                event(new notifyEvent('equiprqsts.set_stage_' . $nxt_stageid, $sysobjid, $rec->id, $userid));
                            }
                        }
                    }
                }
                // --------------------------------------------------------------------------
            }
        }else{
            Log::debug('Не найден слот для согласования', $search_params);
        }

    }

}
