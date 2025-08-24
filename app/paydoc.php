<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\FinOpersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class paydoc extends Model
{
    static public $prefix = 'paydocs';
    static public $sysobjid = 520;

    use DeleteTrait;
    use FilesTrait;
    use FinOpersTrait;


    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function opertype()
    {
        return $this->hasOne(opertype::class, 'id', 'opertypeid')->withDefault();
    }

    public function paytype()
    {
        return $this->hasOne(paytype::class, 'id', 'paytypeid');
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lock_before = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lock_before)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->paydate < $lock_before);
            }
        }
        return false;
    }

    public static function min_paydate()
    {
        //определим минимально-допустимую дату для поля paydate
        return sysobj_lockdate::where('sysobjid', self::$sysobjid)->first()->lock_before ?? null;
    }

    public static function on_open($rec)
    {
        // Доп. действия при открытии существующей

        if ($rec->id <> 1) {

            //сформируем/обновим фин. операции ------
            //self::rfr_finopers($rec);
        }
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи

        //сформируем/обновим фин. операции ------
        self::rfr_finopers($rec);

        //Забудем связанный кэш -----------------
        self::cache_clear();

    }

    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи

        //удалим записи из obj_finopers, для которых нет соответствующих записей в paydocs
        obj_finoper::from('obj_finopers as f')
            ->where('sysobjid', self::$sysobjid)
            ->whereRaw("not exists (select 1 from paydocs as pd where pd.id=f.objid)")
            ->delete();

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function cache_clear($rec = null)
    {
        //Забудем связанный кэш -------------------------------------
        if (isset($rec)) {
        }
        Cache::forget('informer_saldos');
        Cache::forget('informer_ownorg_saldo_details');
        //-----------------------------------------------------------
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

                    if ($key == 'active') {
                        $sc .= " and p.active={$val}";

                    } elseif ($key == 'for_load') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " p.for_load=1";

                    } elseif ($key == 'for_unload') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " p.for_unload=1";

                    } elseif ($key == 's_name') {
                        $sc .= " and concat(ifnull(p.code,' '),' ',p.name) like '%{$val}%'";

                    } elseif ($key == 'loadplace_in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') .
                            " exists (select 1 from mchn_raids as mr where mr.load_placeid=p.id)";

                    } elseif ($key == 'unloadplace_in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') .
                            " exists (select 1 from mchn_raids as mr where mr.unload_placeid=p.id)";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-10-27 SNS. универсальный конструктор массива с id, name мест
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('places as p')
                ->whereRaw($sc)
                ->select('p.id', db::raw("concat(ifnull(p.code,' '),' ',p.name) as tname"))
                ->orderBy('tname', 'asc')
                ->get()->pluck('tname', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-10-27 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember(self::$prefix . '_lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей orgdeps
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'p.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['pd.name', 'asc']];

            $recs = self::from('paydocs as pd')
                ->whereRaw($sc)
                ->select($fields);

            foreach ($sorts as $sort) {
                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
            }

            $recs = $recs->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

    static public function rfr_finopers($rec)
    {
        if (!isset($rec))
            return;

        $userid = \Auth::user()->id;

        //сформируем фин. операции --------------------------------------------------------------
        obj_finoper::where(['sysobjid' => self::$sysobjid, 'objid' => $rec->id])->update(['updated_by' => 0]);

        //Оплата:
        if ($rec->paydir == -1) {
            $srcorgid = $rec->ownorgid;
            $tgtorgid = $rec->orgid;

        } elseif ($rec->paydir == 1) {
            $srcorgid = $rec->orgid;
            $tgtorgid = $rec->ownorgid;
        }
        if (isset($srcorgid) and isset($tgtorgid)) {
            obj_finoper::addOrUpdate(
                ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 1],
                ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 1
                    , 'operdate' => $rec->paydate
                    , 'opersum' => $rec->paysum
                    , 'qty' => null
                    , 'price' => null
                    //, 'descript' => 'платеж - ' . $rec->reason
                    , 'descript' => $rec->reason
                    , 'sumtypeid' => 1  //1-платеж, 2-поставка
                    , 'srcorgid' => $srcorgid
                    , 'tgtorgid' => $tgtorgid
                    , 'contractid' => $rec->contractid
                    , 'opertypeid' => $rec->opertypeid
                    , 'updated_by' => $userid
                    , 'updated_at' => now()
                ]);
        }
        //удалим лишние (прежние) записи
        obj_finoper::where(['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'updated_by' => 0])->delete();
        //---------------------------------------------------------------------------------------

        //удалим записи из obj_finopers, для которых уже нет соответствующих записей в paydocs
        obj_finoper::from('obj_finopers as f')
            ->where('sysobjid', self::$sysobjid)
            ->whereRaw("not exists (select 1 from paydocs as t where t.id=f.objid)")
            ->delete();

    }

    public function linked_mr_opers()
    {
        return $this->hasMany(obj_link::class, 'objid', 'id')
            ->join('mr_opers as mro', 'mro.id', 'obj_links.lnkobjid')
            ->join('mchn_raids as mr', 'mr.id', 'mro.mr_id')
            ->join('refitems as ri', 'ri.id', 'mro.refitmid')
            ->join('orgs as oo', 'oo.id', 'mro.suporgid')
            ->join('orgs as o', 'o.id', 'mro.orgid')
            ->where([
                'sysobjid' => self::$sysobjid,
                'lnksysobjid' => 1107,
            ])
            ->select('obj_links.*', 'mr.wrkdate', 'mro.*', 'ri.name as ri_name', 'oo.name as suporg_name', 'o.name as org_name');
    }

}
