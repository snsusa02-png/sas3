<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class mr_oper extends Model
{
    static public $prefix = 'mr_opers';
    static public $sysobjid = 1107;

    use DeleteTrait;
    use FilesTrait;

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

    public function org_place()
    {
        return $this->hasOne(org_place::class, 'id', 'org_placeid')->withDefault();
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')->withDefault();
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
}
