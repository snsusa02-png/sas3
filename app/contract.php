<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\StaffsTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class contract extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use StaffsTrait;

    protected $guarded = [];

    static public $prefix = 'contracts';
    static public $sysobjid = 151;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function category()
    {
        return $this->hasOne(contract_category::class, 'id', 'categoryid')->withDefault();
    }

    public function contracttype()
    {
        return $this->hasOne(contracttype::class, 'id', 'contracttypeid')
            ->withDefault();
    }

    public function ownorg()
    {//Со стороны холдинга
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    public function org()
    {//Контрагент
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function ac()
    {
        return $this->hasOne(ac::class, 'id', 'acsid')->withDefault();
    }

    public function regnum_src()
    {
        return $this->hasOne(regnum_src::class, 'id', 'regnum_srcid')->withDefault();
    }

    static public function statuses()
    {
        return [
            0 => 'черновик',
            2 => 'проект',
            4 => 'подписан (действует)',
            6 => 'отменен',
            8 => 'завершен (исполнен)',
            10 => 'расторгнут',
        ];
    }

    public function contract_orgs()
    {
        return $this->hasMany(contract_org::class, 'contractid', 'id');
    }

    public function prices()
    {
        return $this->hasMany(contract_price::class, 'contractid', 'id');
    }

    public function linked_contracts()
    {
        return $this->hasMany(obj_link::class, 'objid', 'id')
            ->where([
                'sysobjid' => self::$sysobjid,
                'lnksysobjid' => self::$sysobjid,
            ]);
    }

    public function linked_objs()
    {
        return $this->hasMany(obj_link::class, 'objid', 'id')
            ->where([
                'sysobjid' => self::$sysobjid,
            ]);
    }

//    public function staffs()
//    {
//        return $this->hasMany(obj_staff::class, 'objid', 'id')
//            ->leftJoin('roletypes as rt', 'rt.id', 'obj_staffs.roletypeid')
//            ->where('sysobjid', self::$sysobjid)
//            ->select('obj_staffs.*', 'rt.name as roletype_name')
//            ->orderby('staffname');
//    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->leftJoin('roletypes as rt', 'rt.id', 'obj_readers.roletypeid')
            ->where('sysobjid', self::$sysobjid)
            ->select('obj_readers.*', 'rt.name as roletype_name')
            ->orderby('mustread', 'desc')
            ->orderby('firstread_at');
    }

    public function real_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at');
    }

    public function tags() //2021-80-10 Переименовал, так как конфликтует с другим содержанием $rec->tags
    {
        return $this->hasMany(objtag::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('tag');
    }

    public function comments()
    {
        return $this->hasMany(obj_comment::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }


    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = 'договор ' . $this->name . ' №' . $this->docnum
                . ' от ' . date_format(date_create($this->docdate), "d.m.Y");
            if (isset($this->docsum))
                $rslt .= ' сумма: ' . number_format($this->docsum, 2);
            return $rslt;
        } else
            return null;
    }

    public function getShortInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->name . ' №' . $this->docnum
                . ' от ' . date_format(date_create($this->docdate), "d.m.Y");
            return $rslt;
        } else
            return null;
    }

    public static function getInfo($contractid)
    {
        if (isset($contractid)) {
            $rq = self::select("docnum", "docdate", 'name')
                ->find($contractid);
            if (isset($rq)) {
                return $rq->name . ' №' . $rq->docnum
                    . ' от ' . date_format(date_create($rq->docdate), "d.m.Y");
            } else
                return null;
        }
        return null;
    }

    public static function subcontracts($ownorgid)
    {
        if (isset($ownorgid)) {
            $rq = self::from('contracts as c')
                ->join('orgs as o', 'o.id', 'c.orgid')
                ->select("c.id", DB::raw("concat('№',docnum,' от ', docdate, ' (', o.name,': ',c.name,')') as name"))
                ->where('c.ownorgid', $ownorgid)
                ->where('c.active', 1)
                ->get()->pluck('name', 'id');
            return $rq;
        } else return [];
    }

    public static function lstActiveBetween($ownorgid, $orgid)
    {
        if (isset($ownorgid) and (isset($orgid))) {
            $rq = self::from('contracts as c')
                ->join('orgs as o', 'o.id', 'c.orgid')
                ->select("c.id", DB::raw("concat('№',docnum,' от ', docdate, ' (', o.name,': ',c.name,')') as name"))
                ->where('c.ownorgid', $ownorgid)
                ->where('c.orgid', $orgid)
                ->where('c.active', 1)
                ->get()->pluck('name', 'id');
            return $rq;
        } else return [];

    }

    public static function lstActiveIncome($ownorgid)
    {
        if (isset($ownorgid)) {
            $rq = self::from('contracts as c')
                ->join('orgs as o', 'o.id', 'c.orgid')
                ->select("c.id", DB::raw("concat('№',docnum,' от ', docdate, ' (', o.name,': ',c.name,')') as name"))
                ->where('c.ownorgid', $ownorgid)
                ->where('c.categoryid', 1)
                ->where('c.active', 1)
                ->get()->pluck('name', 'id');
            return $rq;
        } else return [];

    }

    static public function usedTags()
    {
        $cache_key = self::$prefix . '_usedTypes';
        Cache::forget($cache_key);
        $data = Cache::remember($cache_key, now()->addMinutes(8)
            , function () {
                $lst = objtag::from('objtags as t')
                    ->select('tag as tid', 'tag as tname')
                    ->where('sysobjid', self::$sysobjid)
                    ->orderBy('tag')
                    ->get()
                    ->pluck('tname', 'tid')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function usedBuildObjs()
    {
        $cache_key = self::$prefix . '_' . 'usedBuildObjs';
        Cache::forget($cache_key);
        $data = Cache::remember($cache_key, now()->addMinutes(8)
            , function () {
                $lst = obj_link::from('obj_links as lnk')
                    ->join('buildobjs as bo', 'bo.id', 'lnk.lnkobjid')
                    ->where('sysobjid', self::$sysobjid)
                    ->where('lnksysobjid', 466)
                    ->select('bo.id', 'bo.name')
                    ->orderBy('bo.name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function buildopertypes($contractid)
    {
        //массив Видов работ, связанных с контрактом
        // - определяем через связь с видами работ разделов бюджета переданому в подряд по указанному контракту

        //Cache::forget('contract_buildopertypes_' . $contractid );
        return Cache::remember('contract_buildopertypes_' . $contractid
            , now()->addMinutes(15)
            , function () use ($contractid) {
                return buildopertype::from('buildopertypes as bot')
                    ->whereRaw(" bot.id in (select bi.buildopertypeid
                        from budget_items as bi
                        join budgets as b on b.id=bi.budgetid
                        and b.par_contractid={$contractid})")
                    ->select('bot.id', 'bot.name')
                    ->orderby('bot.ordr')
                    ->orderby('bot.name')
                    ->get()
                    ->pluck('name', 'id')
                    ->toArray();
            }
        );
    }

    static public function list_orgcontracts_for_buildopertypeid($buildopertypeid)
    {
        //массив контрактов, связанных с заданным видом работ
        // - определяем через связь контракта бюджета с видами работ (bi), связанных с видом работ объекта

        //Cache::forget('list_for_buildopertypeid_' . $buildopertypeid);
        return Cache::remember('list_for_buildopertypeid_' . $buildopertypeid, now()->addMinutes(5)
            , function () use ($buildopertypeid) {
                return budget_item::from('budget_items as bi')
                    ->join("budgets as b", 'b.id', "bi.budgetid")
                    ->join("orgs as o", 'o.id', "b.orgid")
//                    ->leftJoin('contracts as c', function ($j) {
//                        $j->on('c.id', 'b.par_contractid')
//                            ->where('c.statusid', 4);
//                    })
                    ->Join('contracts as c', 'c.id', 'b.par_contractid')
                    ->where('c.statusid', 4)
                    ->where('bi.buildopertypeid', $buildopertypeid)
                    ->select('b.par_contractid'
                        , db::raw("concat(o.id,':',ifnull(c.id,'')) as tid")
                        , db::raw("concat(o.name,': №', ifnull(c.docnum,'-'),' ', ifnull(c.docdate,'')) as tname"))
                    ->orderBy('c.docdate', 'desc')
                    ->orderBy('b.par_contractid')
                    ->get()->pluck('tname', 'tid')->toArray();
            }
        );
    }

    static public function search_cond($params)
    {
        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {
            //Log::info($key.' = '.$val);
            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'in_equiprsts') {
                        //договор указан в заявках на материалы как договор с подрядчиком
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from equiprqsts as er where er.contractid = c.id)";

                    } elseif ($key == 'ownorgid') {
                        $sc .= " and c.ownorgid={$val}";

                    } elseif ($key == 'orgid') {
                        //вторая сторона по договору
                        $sc .= " and c.orgid={$val}";

                    } elseif ($key == 'between_orgs') {
                        if (is_array($val)) {

                            $val = array_filter($val, function ($value) {
                                return !is_null($value) && $value !== '';
                            });

                            if (count($val) < 2)
                                $sc .= " and 1=0";
                            else {
                                $lst = implode(',', $val);
                                $sc .= " and c.ownorgid in ({$lst}) and c.orgid in ({$lst})";
                            }

                        } else
                            $sc .= " and 1=0";

                    } elseif ($key == 'budget_orgid') {
                        //договор указан в бюджете
                        $sc .= " and exists(select 1 from budgets as b where b.par_contractid=c.id and b.orgid={$val})";

                    } elseif ($key == 'categoryid') {
                        //категория: 1-доходный, 2-расходный, 3- , 4-
                        $sc .= " and c.categoryid={$val}";

                    } elseif ($key == 'actual') {
                        //действующий в настоящее время
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . "( c.active={$val}"
                            . " and 1=1"
                            . ")";

                    } elseif ($key == 'for_userid') {
                        if (!usrsysright::isUserHasRightByCode_cached($val, 'contracts.read')) {
                            $sc .= " and exists (select 1 from obj_readers as r where r.sysobjid=151
                                and r.objid = c.id
                                and r.userid={$val})";
                        }

                    } elseif ($key == 'buildobjid') {
                        //$sc .= " and c.buildobjid={$val}";
                        //$sc .= " and exists (select 1 from equiprqsts as er where er.contractid = c.id and er.buildobjid={$val})";
                        //по прямой привязке договора к объекту строительства
                        $sc .= " and exists (select 1 from obj_links as ol where ol.sysobjid=151 and objid = c.id
                            and ol.lnksysobjid=466 and ol.lnkobjid={$val})";

                    } elseif ($key == 'budget_buildobjid') {
                        $sc .= " and exists (select 1 from budgets as b where b.par_contractid = c.id
                                and b.buildobjid={$val} )";

                    } elseif ($key == 'buildopertypeid') {
                        //связанные с разделами бюджета, которые, в свою очередь, связаны с заданным видом работ
                        $sc .= " and exists (select 1 from budgets as b
                                join budget_items as bi on bi.budgetid=b.id and bi.buildopertypeid={$val}
                                where b.par_contractid=c.id)";

                    } elseif ($key == 'in_wrkrep_machines') {
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from wrkrep_machines as wrm where wrm.contractid = c.id)";

                    } elseif ($key == 'org_in') {
                        $sc .= " and exists (select 1 from contract_orgs as co where co.contractid = c.id and co.orgid={$val})";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name договоров
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = self::search_cond($params);

            if (1 == 0) {
                foreach ($params as $key => $val) {
                    //Log::info($key.' = '.$val);
                    if (isset($val) and $val !== '') {

                        if (array_search($key, $used_params) == 0) {
                            $used_params[] = $key;

                            if ($key == 'in_equiprsts') {
                                //договор указан в заявках на материалы как договор с подрядчиком
                                $sc .= " and " . (($val == 0) ? "not" : "")
                                    . " exists(select 1 from equiprqsts as er where er.contractid = c.id)";

                            } elseif ($key == 'ownorgid') {
                                $sc .= " and c.ownorgid={$val}";

                            } elseif ($key == 'orgid') {
                                //вторая сторона по договору
                                $sc .= " and c.orgid={$val}";

                            } elseif ($key == 'between_orgs') {
                                $lst = implode(',', $val);
                                $sc .= " and c.ownorgid in ({$lst}) and c.orgid in ({$lst})";

                            } elseif ($key == 'budget_orgid') {
                                //договор указан в бюджете
                                $sc .= " and exists(select 1 from budgets as b where b.par_contractid=c.id and b.orgid={$val})";

                            } elseif ($key == 'categoryid') {
                                //категория: 1-доходный, 2-расходный, 3- , 4-
                                $sc .= " and c.categoryid={$val}";

                            } elseif ($key == 'actual') {
                                //действующий в настоящее время
                                $sc .= " and " . (($val == 0) ? "not" : "")
                                    . "( c.active={$val}"
                                    . " and 1=1"
                                    . ")";

                            } elseif ($key == 'for_userid') {
                                if (!usrsysright::isUserHasRightByCode_cached($val, 'contracts.read')) {
                                    $sc .= " and exists (select 1 from obj_readers as r where r.sysobjid=151
                                and r.objid = c.id
                                and r.userid={$val})";
                                }

                            } elseif ($key == 'buildobjid') {
                                //$sc .= " and c.buildobjid={$val}";
                                //$sc .= " and exists (select 1 from equiprqsts as er where er.contractid = c.id and er.buildobjid={$val})";
                                //по прямой привязке договора к объекту строительства
                                $sc .= " and exists (select 1 from obj_links as ol where ol.sysobjid=151 and objid = c.id
                            and ol.lnksysobjid=466 and ol.lnkobjid={$val})";

                            } elseif ($key == 'budget_buildobjid') {
                                $sc .= " and exists (select 1 from budgets as b where b.par_contractid = c.id
                                and b.buildobjid={$val} )";

                            } elseif ($key == 'buildopertypeid') {
                                //связанные с разделами бюджета, которые, в свою очередь, связаны с заданным видом работ
                                $sc .= " and exists (select 1 from budgets as b
                                join budget_items as bi on bi.budgetid=b.id and bi.buildopertypeid={$val}
                                where b.par_contractid=c.id)";

                            } elseif ($key == 'in_wrkrep_machines') {
                                $sc .= " and " . (($val == 0) ? "not" : "")
                                    . " exists (select 1 from wrkrep_machines as wrm where wrm.contractid = c.id)";
                            }
                        }

                    }
                }
            }
            //Log::info($sc);

            $lst = self::from('contracts as c')
                ->whereRaw($sc)
                ->select('id', db::raw("concat('№',docnum,' от ', docdate,' ', descript) as name"))
                ->orderBy('name')
                ->get()->pluck('name', 'id')->toArray();
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

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей contracts
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'c.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['c.docdate', 'desc']];

            $recs = self::from('contracts as c')
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

    public static function informer_statistics()
    {
        //Cache::forget('informer_contract_stat');
        return Cache::remember('informer_contract_stat', now()->addMinutes(15)
            , function () {

                return [
                    'doc_cnt' => self::count()
                    , 'doc_file_cnt' => objfile::where(['sysobjid' => self::$sysobjid])->count()
                    , 'user_rqst_cnt' => obj_reader::where('sysobjid', self::$sysobjid)->whereNotIn('userid', [57])->sum('read_cnt')
                ];
            }
        );
    }

    public static function user_in_readers($userid)
    {
        return Cache::remember(self::$prefix . '_user_in_readers_' . $userid, now()->addMinutes(15)
            , function () use ($userid) {
                return (obj_reader::where(['sysobjid' => self::$sysobjid, 'userid' => $userid])->count() > 0);
            }
        );
    }

    public static function user_new_cnt($userid)
    {
        return Cache::remember(self::$prefix . '_user_new_cnt_' . $userid, now()->addMinutes(5)
            , function () use ($userid) {
                return obj_reader::where(['sysobjid' => self::$sysobjid, 'userid' => $userid, 'read_cnt' => 0])->count();
            }
        );
    }

    public static function user_has_access($userid)
    {
        return Cache::remember(self::$prefix . '_user_has_access_' . $userid, now()->addMinutes(5)
            , function () use ($userid) {

                return (usrsysright::isUserHasRightByCode_cached($userid, self::$prefix . '.read')
                    or self::user_in_readers($userid));
            }
        );
    }

}

