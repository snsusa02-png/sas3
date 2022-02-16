<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\FinOpersTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class mr_oper extends Model
{
    static public $prefix = 'mr_opers';
    static public $sysobjid = 1107;

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

    public function mchn_raid()
    {
        return $this->hasOne(mchn_raid::class, 'id', 'mr_id');
    }

    public function dispatcher()
    {
        //return $this->hasOne(User::class, 'id', 'disp_userid')->withDefault();
        return $this->hasOne(orgstaff::class, 'id', 'disp_staffid')->withDefault();
    }

    public function suporg()
    {
        return $this->hasOne(org::class, 'id', 'suporgid')->withDefault();
    }

    public function sup_place()
    {
        return $this->hasOne(org_place::class, 'id', 'sup_placeid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function org_place()
    {
        return $this->hasOne(org_place::class, 'id', 'org_placeid')->withDefault();
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')->withDefault();
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = 'операция "' . $this->saledirs()[$this->sale_dir] . '"';

            if ($this->sale_dir < 0) {
                $rslt .= ' ' . $this->org->name;
                $rslt .= ' у ' . $this->suporg->name;
            } elseif ($this->sale_dir > 0) {
                $rslt .= ' ' . $this->suporg->name;
                $rslt .= ' для ' . $this->org->name;

            }

            if (isset($this->itm_sum))
                $rslt .= ', сумма: ' . number_format($this->itm_sum, 2);
            return $rslt;
        } else
            return null;
    }


    public function linked_paydocs()
    {
        return $this->hasMany(obj_link::class, 'objid', 'id')
            ->join('paydocs as pd', 'pd.id', 'obj_links.lnkobjid')
            ->join('orgs as oo', 'oo.id', 'pd.ownorgid')
            ->join('orgs as o', 'o.id', 'pd.orgid')
            ->where([
                'sysobjid' => self::$sysobjid,
                'lnksysobjid' => 520,
            ])
            ->select('obj_links.*', 'pd.*', 'oo.name as ownorg_name', 'o.name as org_name');
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lockdate = sysobj_lockdate::where('sysobjid', 1106)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lockdate)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->mchn_raid->wrkdate < $lockdate);
            }
        }
        return false;
    }

    static public function saledirs()
    {
        return [-1 => 'Покупка', +1 => 'Продажа', 0 => 'Внутрен. операция'];
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи

        //сформируем/обновим фин. операции ------
        self::rfr_finopers($rec);

        //пересчитаем  итоговые поля ------------
        //  - кол-во рейсов для родительской записи в mchn_raids
        mchn_raid::where('id', $rec->mr_id)->update(['raid_qty' => mr_oper::where('mr_id', $rec->mr_id)->sum('raid_qty')]);

        mchn_raid::on_update($rec->mchn_raid);

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function on_delete($rec)
    {
        // Доп. действия при удалении записи -------------------------

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

        // -----------------------------------------------------------
    }

    public static function cache_clear($rec)
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

                    if ($key == 'machineid') {
                        $sc .= " and mr.machineid={$val}";

                    } elseif ($key == 'driverid') {
                        $sc .= " and mr.driverid={$val}";

                    } elseif ($key == 'wrkdate') {
                        $sc .= " and mr.wrkdate='{$val}'";

                    } elseif ($key == 'active') {
                        $sc .= " and mr.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (mr.active=1 or mr.id={$val})";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name ключевых работ для вида работ строит. объекта
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('mchn_raids as mr')
                ->join('machines as m', 'm.id', 'mr.machineid')
                ->whereRaw($sc)
                ->select('mr.id', 'mr.name')
                ->orderBy('mr.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;

    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей mchn_opertypes
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'mot.*';
            //Log::info(json_encode($fields));

            $recs = self::from('mchn_raids as mr')
                ->whereRaw($sc)
                ->select($fields);

            $sorts = $sorts ?? [['mr.wrkdate', 'asc']];   //по умолчанию
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

        //сформируем фин. операцию --------------------------------------------------------------
        obj_finoper::where(['sysobjid' => self::$sysobjid, 'objid' => $rec->id])->update(['updated_by' => 0]);
        //Покупка у поставщика:
        //$opername = (self::saledirs()[$rec->sale_dir] ?? ' ? ') . ': ';

        obj_finoper::addOrUpdate(
            ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 1],
            ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 1
                , 'operdate' => $rec->mchn_raid->wrkdate
                , 'opersum' => $rec->itm_sum
                , 'qty' => $rec->itm_qty
                , 'price' => $rec->itm_price
                //, 'descript' => $opername . $rec->refitem->name . ', ' . $rec->refitem->unittype->name
                , 'descript' => $rec->refitem->name . ', ' . $rec->refitem->unittype->name
                , 'sumtypeid' => 2  //1-деньги, 2-товар
                , 'srcorgid' => $rec->suporgid
                , 'tgtorgid' => $rec->orgid
                , 'contractid' => $rec->contractid
                , 'opertypeid' => $rec->mchn_raid->opertypeid
                , 'updated_by' => $userid
                , 'updated_at' => now()
            ]);

        if (1 == 0) {
            //если есть промежуточная перепродажа между компаниями ГК
            if ($rec->unload_ownorgid <> $rec->load_ownorgid) {
                obj_finoper::addOrUpdate(
                    ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 2],
                    ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 2
                        , 'operdate' => $rec->wrkdate
                        , 'opersum' => $rec->ownorg_sum
                        , 'qty' => 1
                        , 'price' => $rec->ownorg_sum
                        , 'descript' => 'поставка: ' . $rec->unload_refitem->name . ', партия'
                        , 'sumtypeid' => 2  //1-деньги, 2-товар
                        , 'srcorgid' => $rec->load_ownorgid
                        , 'tgtorgid' => $rec->unload_ownorgid
                        , 'updated_by' => $userid
                        , 'updated_at' => now()
                    ]);
            }
            //Продажа покупателю
            obj_finoper::addOrUpdate(
                ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 3],
                ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 3
                    , 'operdate' => $rec->wrkdate
                    , 'opersum' => $rec->unload_sum
                    , 'qty' => $rec->unload_qty
                    , 'price' => $rec->unload_price
                    , 'descript' => 'поставка: ' . $rec->unload_refitem->name . ', ' . $rec->unload_refitem->unittype->name
                    , 'sumtypeid' => 2  //1-деньги, 2-товар
                    , 'srcorgid' => $rec->unload_ownorgid
                    , 'tgtorgid' => $rec->orgid
                    , 'updated_by' => $userid
                    , 'updated_at' => now()
                ]);
            //удалим лишние записи
            obj_finoper::where(['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'updated_by' => 0])->delete();
            //---------------------------------------------------------------------------------------
        }
    }

    static public function addOrUpdate($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $rec = self::where($search_params)->first();

            if (!isset($rec)) {
                $rec = new self($search_params);
            }
            $rec->fill($set_params);
            $rec->save();

            return $rec;
        }
        return null;
    }

    static public function add($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $rec = new self($search_params);
            $rec->fill($set_params);
            $rec->save();

            self::on_update($rec);
            
            return $rec;
        }
        return null;
    }


}
