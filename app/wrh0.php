<?php

namespace Modules\Stock\Entities;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use DB;
use Cache;

use App\objextid;
use App\User;
use App\group;
use App\grpitem;
use App\usrsysright;

class wrh extends Model
{
    use DeleteTrait;

//    protected $fillable = ['created_by'];
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

    public static function rqListWrhByCond($s_name, $s_address)
    {
        $s_name = mb_ereg_replace("(^\s+)|(\s+$)/", "", $s_name);
        $s_address = mb_ereg_replace("(^\s+)|(\s+$)/", "", $s_address);

        $rq = wrh::
        select('w.id', 'w.name', 'w.address', 'w.descript', 'w.active')
            ->from('wrhs as w');

        if (mb_strlen($s_name) > 0) {
            $rq->where('w.name', 'like', '%' . mb_strtoupper($s_name) . '%');
        }
        if (mb_strlen($s_address) > 0) {
            $rq->where('w.address', 'like', '%' . mb_strtoupper($s_address) . '%');
        }
        return $rq;
    }

    public static function auxInfo0($wrhid)
    {
        $info = [];

        //Остатки на складе
        $data = Cache::remember('wrh_aux_stock_.' . $wrhid, now()->addMinutes(15)
            , function () use ($wrhid) {
                $recs = wrh_stock::from('wrh_stocks as ws')
                    ->join('refitems as ri', 'ri.id', 'ws.refitmid')
                    ->where('ws.wrhid', $wrhid)
                    ->selectraw('count(distinct refitmid) ri_cnt, sum(ws.qty) qty, sum(ws.qty*ri.price) itmsum')
                    ->first();

                $tstr = 'позиций: ' . $recs->ri_cnt
                    . ', количество: ' . number_format($recs->qty, 0)
                    . ', сумма: ' . number_format($recs->itmsum, 2);

                return $tstr;
            });
        if (isset($data)) {
            array_push($info,
                ['name' => 'Товарный запас', 'route' => '', 'sample' => $data, 'reccount' => null]
            );
        }
        return $info;
    }

    static public function AuxInfo($wrhid)
    {
        $info = [];
        $userid = \Auth::user()->id;

        if (isset($wrhid)) {


            //Остатки на складе
            $data = Cache::remember('wrh_aux_stock_.' . $wrhid, now()->addMinutes(15)
                , function () use ($wrhid) {
                    $recs = wrh_stock::from('wrh_stocks as ws')
                        ->join('refitems as ri', 'ri.id', 'ws.refitmid')
                        ->where('ws.wrhid', $wrhid)
                        ->selectraw('count(distinct refitmid) ri_cnt, sum(ws.qty) qty, sum(ws.qty*ri.price) itmsum')
                        ->first();

                    $tstr = 'позиций: ' . $recs->ri_cnt
                        . ', количество: ' . number_format($recs->qty, 0)
                        . ', сумма: ' . number_format($recs->itmsum, 2);

                    return $tstr;
                });
            if (isset($data)) {
                array_push($info,
                    ['name' => 'Товарный запас', 'route' => '', 'sample' => $data, 'reccount' => null]
                );
            }
            // -------------------------------------------------------------------------------

            //Группы
            $data = Cache::remember('org_aux_groups_.' . $wrhid, now()->addMinutes(25)
                , function () use ($wrhid) {

                    $recs = group::from('groups as g')
                        ->join('grptypes as t', 't.id', '=', 'g.grptypeid')
                        ->whereExists(function ($query) use ($wrhid) {
                            $query->select(DB::raw(1))
                                ->from('grpitems as i')
                                ->whereraw('i.grpid = g.id')
                                ->where('i.sysobjid', 202)
                                ->where('i.objid', $wrhid);
                        })
                        ->selectraw('concat(t.name,": <b>", g.name, "</b>") as name')
//                ->orderBy('t.ordr', 'asc')
//                ->orderBy('g.ordr', 'asc')
                        ->skip(0)->take(4)
                        ->get();

                    $tstr = "";
                    $i = 0;
                    foreach ($recs as $rec) {
                        $i++;
                        if ($i > 1) {
                            $tstr = $tstr . ", ";
                        }
                        if ($i > 3) {
                            $tstr = $tstr . " ...";
                            break;
                        }
                        $tstr = $tstr . $rec->name;
                    }
                    $cnt = grpitem::where('active', 1)
                        ->where('sysobjid', 202)
                        ->where('objid', $wrhid)
                        ->selectraw('count(*) as cnt')
                        ->first();

                    return ['sample' => $tstr, 'reccount' => $cnt ? $cnt->cnt : 0];
                });
            if (isset($data))
                array_push($info,
                    ['name' => "группы", 'route' => 'wrh_groups.edit', 'sample' => $data['sample'],
                        'reccount' => $data['reccount'],
                        'btn-class' => 'btn-warning'
                    ]
                );
            // -------------------------------------------------------------------------------

            //Связи с внешними системами
            if (usrsysright::isUserHasRightByCode($userid, 'objextids.read')) {
                $recs = objextid::from('objextids as oi')
                    ->join('extsystems as es', 'es.id', 'oi.extsysid')
                    ->where('oi.objid', $wrhid)
                    ->where('oi.sysobjid', 202)
                    ->selectraw('concat(es.name, ": ", oi.extid) as name')
                    ->orderBy('oi.created_at', 'asc')
                    ->skip(0)->take(4)
                    ->get();

                $tstr = "";
                $i = 0;
                foreach ($recs as $rec) {
                    //dd($rec->name);
                    $i++;
                    if ($i > 1) {
                        $tstr = $tstr . ", ";
                    }
                    if ($i > 3) {
                        $tstr = $tstr . " ...";
                        break;
                    }
                    $tstr = $tstr . $rec->name;
                }
                $cnt = objextid::where('objid', $wrhid)->where('sysobjid', 202)->count();

                array_push($info,
                    ['name' => 'внеш. системы', 'route' => 'wrh_extids.index', 'sample' => $tstr, 'reccount' => $cnt]
                );
            }

        }
        return $info;
    }

    static public function rqShortInfo($id)
    {
        $rq = static::where('id', $id)->select('id', 'name');
        return $rq;
    }

    static public function listUsed()
    {
        $wrhs = wrh::from('wrhs as w')
            ->select('name', 'id')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('wrhdocs as wd')
                    ->whereRaw('wd.wrhid = w.id');
            })
            ->orderBy('name')
            ->get()->pluck("name", "id")->prepend("-любой-", "");
        return $wrhs;
    }

    public static function newItemByExtID($extsysid, $itmextid, $itmdata)
    {
        $sysobjid = 202;
        //перепроверим - вдруг уже есть такой склад:
        $itmid = objextid::objid_by_extsysid_extid($extsysid, $sysobjid, $itmextid);
        if (!isset($itmid) and isset($itmextid)) {

            try {
                DB::beginTransaction();

                $userid = $itmdata['created_by'] ?? 0;
                $itmdata['updated_by'] = $itmdata['updated_by'] ?? $userid;

                //Добавить запись о складе
                $rec = new wrh($itmdata);
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

            } catch (\Exception $e) {
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
            } finally {
            }
        }
        return $itmid;
    }


    static public function lstAllWrhs_cache()
    {
        return Cache::remember('lstallwrhs', now()->addMinutes(15)
            , function () {
                return self::select('id', 'name')->get()->pluck('name', 'id')->toArray();
            });
    }

    public static function lock_by_user($wrhid, $userid)
    {
        $rslt = false;
        if ($wrhid and $userid) {
            self::where('id', $wrhid)
                ->whereRaw('ifnull(locked_by,' . $userid . ')=' . $userid)
                ->update(['locked_by' => $userid]);

            $rslt = (self::where('id', $wrhid)
                    ->where('locked_by', $userid)->count() == 1);
        }
        return $rslt;
    }


    public
    static function unlock_by_user($wrhid, $userid)
    {
        $rslt = false;
        if ($wrhid and $userid) {

            $cnt=self::where('id', $wrhid)
                ->whereRaw('ifnull(locked_by,' . $userid . ')=' . $userid)
//                ->where('locked_by', $userid)
                ->update(['locked_by' => null]);

            $rslt = (self::where('id', $wrhid)->wherenull('locked_by')->count() == 1);
        }
        return $rslt;

    }

}

