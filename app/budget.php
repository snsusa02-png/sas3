<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Cache;
use DB;
use Log;

class budget extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'budgets';
    static public $sysobjid = 876;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function org()
    {//ЦФО
        return $this->hasOne(org::class, 'id', 'orgid')
            ->withDefault();
    }

    public function parent()
    {//родительский бюджет
        return $this->hasOne(budget::class, 'id', 'parid')
            ->withDefault();
    }

    public function project()
    {//связь с проектом
        return $this->hasOne(project::class, 'id', 'projid')
            ->withDefault();
    }

    public function buildobj()
    {//связь с объектом
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')
            ->withDefault();
    }

    public function par_contract()
    {//связь с договором
        return $this->hasOne(contract::class, 'id', 'par_contractid')
            ->withDefault();
    }

    //связь с позициями
    public function items()
    {
        return $this->hasMany(budget_item::class, 'budgetid', 'id')
            ->with('contract');
    }

    //дочерние бюджеты
    public function childs()
    {
        return $this->hasMany(budget::class, 'parid', 'id')
            ->with('org')
            ->with('par_contract');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }


    static public function lstUsedOwnOrgs_cache()
    {
        if (1 == 1) {
            return Cache::remember('budgets_lstUsedOwnOrgs', now()->addMinutes(5)
                , function () {
                    $arr = org::from('orgs as o')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from budgets as b where b.orgid=o.id)')
                        ->orderby('o.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

    static public function lstUsedChildOrgs_cache()
    {
        if (1 == 1) {
            //Cache::forget('budgets_lstUsedChildOrgs');
            return Cache::remember('budgets_lstUsedChildOrgs', now()->addMinutes(5)
                , function () {
                    $arr = org::from('orgs as o')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from budgets as b where b.orgid=o.id and b.id in ( select distinct parid from budgets where parid is not null))')
                        ->orderby('o.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

    static public function lstForProject($projectid)
    {
        if (isset($projectid)) {
            $lst = self::from('budgets as b')
                ->leftJoin('orgs as o', function ($j) { //ЦФО
                    $j->on('o.id', 'b.orgid');
                })
                ->where('projid', $projectid)
                ->select('b.*'
                    , 'o.name as orgname'
                    , db::raw("(select sum(estdocsum) from budget_items as i where i.budgetid=b.id) as estdocsum")
                )
                ->orderBy('b.name')
                ->get();

            return $lst;
        } else
            return null;
    }

    static public function lstAllForBuildObj($buildobjid)
    {
        if (isset($buildobjid)) {
            $lst = self::from('budgets as b')
//                ->leftJoin('contracts as c', function ($j) {
//                    $j->on('c.id', 'b.par_contractid');
//                })
                ->leftJoin('orgs as o', function ($j) {
                    $j->on('o.id', 'b.orgid');
                })
                ->where('b.buildobjid', $buildobjid)
                ->whereNull('b.parid')
                ->select('b.id', 'b.name', 'b.active'
                    , 'o.name as orgname'
                    // выбираем из типов работ головного бюджета (bi.parid)
                    , DB::raw("(select sum(estdocsum) from budget_items as bi
                    where bi.budgetid=b.id) as estdocsum")
                )
                ->orderBy('b.name')
                ->get();

            return $lst;
        } else
            return null;
    }

    static public function lstBudgetIDtoRootForContractId0($contractid, $lim_budgetownerid = null)
    {
        //возвращает массив с идентификаторами бюджетов,
        // начиная с бюджета по контракту $contractid, проходя по иерархии вверх
        // и заканчивая корневым бюджетом

        if (isset($contractid)) {
            //Cache::forget('lstBudgetIDtoRootForContractId_' . $contractid);
            return Cache::remember('lstBudgetIDtoRootForContractId_' . $contractid, now()->addMinutes(30)
                , function () use ($contractid, $lim_budgetownerid) {

                    $bdgt_id = budget::from('budgets as b')
                            ->where('par_contractid', '=', $contractid)
                            //->where('buildobjid', '=', $rec->buildobjid)
                            //->where('orgid', '=', $rec->orgid)
                            ->select('b.id')
                            ->first()->id ?? null;

                    $budgets = [];
                    while (isset($bdgt_id)) {

                        $budgets[] = $bdgt_id;

                        //найдем id родительского бюджета
                        $bdgt_id = budget::where('id', $bdgt_id)
                                ->select('parid')
                                ->first()->parid ?? null;
                    }
                    return $budgets;
                });
        }
        return [];
    }

    static public function lstBudgetIDtoRootForContractId($contractid, $lim_budgetownerid = null, $lim_buildopertypeid = null)
    {
        //возвращает массив с идентификаторами бюджетов,
        // начиная с бюджета по контракту $contractid, проходя по иерархии вверх
        // и заканчивая корневым бюджетом

        //2021-06-28 SNS $lim_budgetownerid - если задано, то отбираем только бюджету указанной организации

        if (isset($contractid)) {
            Cache::forget('lstBudgetIDtoRootForContractId_' . $contractid);
            return Cache::remember('lstBudgetIDtoRootForContractId_' . $contractid, now()->addMinutes(30)
                , function () use ($contractid, $lim_budgetownerid, $lim_buildopertypeid) {

//                    $sc = "1=1";
//                    if (isset($lim_buildopertypeid))
//                        $sc .= " and exists (select 1 from budget_items as bi where bi.budgetid=b.id and bi.buildopertypeid={$lim_buildopertypeid})";

                    $bdgt = budget::from('budgets as b')
                        ->where('par_contractid', '=', $contractid)
//                        ->whereRaw($sc)
                        ->select('b.id', 'b.orgid')
                        ->first();

                    $budgets = [];
                    while (isset($bdgt->id)) {

                        //если определен ограничитель владельца бюджета, то возьмем в массив только при совпадении
                        if (isset($lim_budgetownerid)) {
                            if ($bdgt->orgid == $lim_budgetownerid)
                                $budgets[] = $bdgt->id;
                        } else $budgets[] = $bdgt->id;

                        //найдем id родительского бюджета
                        $bdgt_id = budget::where('id', $bdgt->id)
                                ->select('parid')
                                ->first()->parid ?? null;

                        $bdgt = budget::where('id', $bdgt_id)
                            ->select('id', 'orgid')
                            ->first();
                    }
                    return $budgets;
                });
        }
        return [];
    }


    static public function lstFor($params)
    {
        //2021-04-05 SNS. универсальный конструктор массива с id, name бюджетов
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = "1=1";

            foreach ($params as $key => $val) {
                //Log::info($key.' = '.$val);
                if (isset($val) and $val !== '') {

                    if (array_search($key, $used_params) == 0) {
                        $used_params[] = $key;

                        if ($key == 'in_equiprsts') {
                            //бюджет указан в заявках на материалы через статью в позиции состава заявки equiprqst_items.bdgtitmsumid
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . " exists (select 1 from budget_items as bi
                                join budget_itmsums as bis on bis.itmid=bi.id
                                join equiprqst_items as eri on eri.bdgtitmsumid=bis.id
                                where bi.budgetid=b.id)";

                        } elseif ($key == 'buildobjid') {
                            $sc .= " and b.buildobjid={$val}";
                        }
                    }

                }
            }
            //Log::info($sc);

            $lst = budget::from('budgets as b')
                ->leftJoin('contracts as c', 'c.id', 'b.par_contractid')
                ->whereRaw($sc)
                ->select('b.id', db::raw("concat(b.name,': №', ifnull(c.docnum,'-'),' ', ifnull(c.docdate,'')) as tname"))
                ->orderBy('tname')
                ->get()->pluck('tname', 'id')->toArray();
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

}
