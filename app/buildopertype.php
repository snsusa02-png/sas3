<?php

namespace App;

use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;
use Illuminate\Support\Facades\Cache;
use DB;
use DateTime;
use MongoDB\Driver\Query;

class buildopertype extends Model
{
    use DeleteTrait;
    use FilesTrait;


    protected $guarded = [];

    static public $prefix = 'buildopertypes';
    static public $sysobjid = 467;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid');
    }

    public function baseworktype()
    {
        return $this->hasOne(baseworktype::class, 'id', 'baseworktypeid')
            ->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')
            ->withDefault();
    }

    public function budget_items()
    {
        return $this->hasMany(budget_item::class, 'buildopertypeid', 'id')
            ->with('budget');
    }

    public function ri_lims()
    {
        return $this->hasMany(bot_ri_lim::class, 'buildopertypeid', 'id')
            ->with('refitem');
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('firstread_at');
    }


    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }


    //

    static public function lstActive($buildobjid)
    {
        if (isset($buildobjid)) {
            //Cache::forget(self::$prefix . '_lstActive_' . $buildobjid);
            $data = Cache::remember(self::$prefix . '_lstActive_' . $buildobjid, now()->addMinutes(15)
                , function () use ($buildobjid) {
                    $lst = self::select('id', 'name')
                        ->active();
                    $lst = $lst->where('buildobjid', $buildobjid);

                    $lst = $lst->orderByRaw("ifnull(ordr,99999) asc")->orderBy('name')
                        ->get()
                        ->pluck('name', 'id')->toArray();

                    return $lst;
                }
            );
        } else $data = [];
        return $data;
    }

    static public function lstActiveForBudgetOrgID($buildobjid, $budget_orgid)
    {
        if (isset($buildobjid) and isset($budget_orgid)) {
            Cache::forget(self::$prefix . '_lstActiveForBudgetOrgID_' . $buildobjid . '_' . $budget_orgid);
            $data = Cache::remember(self::$prefix . '_lstActiveForBudgetOrgID_' . $buildobjid . '_' . $budget_orgid, now()->addMinutes(15)
                , function () use ($buildobjid, $budget_orgid) {
                    $lst = self::from('buildopertypes as bot')
                        ->select('id', 'name')
                        ->active();
                    $lst = $lst->where('buildobjid', $buildobjid)
                        ->whereRaw("exists(select 1 from budget_items as bi join budgets as b on b.id=bi.budgetid
                        where bi.buildopertypeid=bot.id
                        and b.orgid={$budget_orgid})");

                    $lst = $lst->orderByRaw("ifnull(ordr,99999) asc")->orderBy('name')
                        ->get()
                        ->pluck('name', 'id')->toArray();

                    return $lst;
                }
            );
        } else $data = [];
        return $data;
    }

    static public function lstAllForBuildObj($buildobjid)
    {
        if (isset($buildobjid)) {
            $lst = self::from('buildopertypes as bot')
                ->leftJoin('contracts', function ($j) {
                    $j->on('contracts.id', 'bot.contractid');
                })
                ->leftJoin('orgs as o', function ($j) {
                    $j->on('o.id', 'contracts.orgid');
                })
                ->where('bot.buildobjid', $buildobjid)
                //->where('bot.active', 1)
                ->select('bot.id', 'bot.name', 'bot.active', 'bot.ordr'
                    , 'bot.plnworkhrs', 'bot.plnbegdate', 'bot.plnbudgetsum'
                    , 'o.name as controrgname'
                    // выбираем из типов работ головного бюджета (bi.parid)
                    , DB::raw("(select sum(estdocsum) from budget_items as bi
                    where bi.buildopertypeid=bot.id
                    and bi.parid is null) as estdocsum")
                )
                ->orderby(DB::raw('ifnull(bot.ordr,99999)'))
                ->orderBy('bot.name')
                ->get();

            return $lst;
        } else
            return null;
    }


    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name видов работ
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = "1=1";

            foreach ($params as $key => $val) {

                if (isset($val) and $val !== '') {

                    if (array_search($key, $used_params) == 0) {
                        $used_params[] = $key;

                        if ($key == 'in_equiprsts') {
                            //вид работ присутствует в заявках на материалы
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . " exists(select 1 from equiprqsts as er where er.buildopertypeid = bot.id)";

                        } elseif ($key == 'buildobjid') {
                            $sc .= " and bot.buildobjid={$val}";

                        } elseif ($key == 'budgetid') {
                            $sc .= " and exists (select 1 from budget_items as bi where bi.buildopertypeid=bot.id and bi.budgetid={$val})";

                        } elseif ($key == 'budget_orgid') {
                            $sc .= " and exists (select 1 from budget_items as bi join budgets as b on b.id=bi.budgetid
                                        where bi.buildopertypeid=bot.id and b.orgid={$val} )";

                        } elseif ($key == 'budget_contractid') {
                            $sc .= " and exists (select 1 from budget_items as bi join budgets as b on b.id=bi.budgetid
                                        where bi.buildopertypeid=bot.id and b.par_contractid={$val} )";

                        } elseif ($key == 'exe_contractid') {
                            $sc .= " and exists (select 1 from contract_exes as ce where ce.buildopertypeid=bot.id and ce.contractid={$val})";

                        } elseif ($key == 'upd_ownorgid') {

                            $sc .= " and exists (select 1 from invoices as inv
                             join eritm_supplies as sup on sup.invoiceid=inv.id
                             join equiprqst_items as eri on eri.id=sup.eritmid
                             join equiprqsts as er on er.id=eri.rqstid
                                where inv.doctypeid=2 and er.buildopertypeid=bot.id and inv.ownorgid={$val}";

                            if (isset($params['upd_begdate']) and array_search('upd_begdate', $used_params) == 0) {
                                $upd_begdate = $params['upd_begdate'];
                                $sc .= " and inv.docdate>='{$upd_begdate}'";
                                $used_params[] = 'upd_begdate';
                            }
                            if (isset($params['upd_enddate']) and array_search('upd_enddate', $used_params) == 0) {
                                $sc .= " and inv.docdate<='" . $params['upd_enddate'] . "'";
                                $used_params[] = 'upd_enddate';
                            }

                            $sc .= ")";
                        } elseif ($key == 'upd_begdate') {
                            //
                            $sc .= " and exists (select 1 from invoices as inv
                             join eritm_supplies as sup on sup.invoiceid=inv.id
                             join equiprqst_items as eri on eri.id=sup.eritmid
                             join equiprqsts as er on er.id=eri.rqstid
                                where inv.doctypeid=2 and er.buildopertypeid=bot.id
                                    and inv.docdate>='" . $params['upd_begdate'] . "'";

                            if (isset($params['upd_enddate']) and array_search('upd_enddate', $used_params) == 0) {
                                $sc .= " and inv.docdate<='" . $params['upd_enddate'] . "'";
                                $used_params[] = 'upd_enddate';
                            }
                            $sc .= ")";

                        } elseif ($key == 'upd_enddate') {
                            $sc .= " and exists (select 1 from invoices as inv
                             join eritm_supplies as sup on sup.invoiceid=inv.id
                             join equiprqst_items as eri on eri.id=sup.eritmid
                             join equiprqsts as er on er.id=eri.rqstid
                                where inv.doctypeid=2 and er.buildopertypeid=bot.id
                                    and inv.docdate<='" . $params['upd_enddate'] . "'";

                            if (isset($params['upd_begdate']) and array_search('upd_begdate', $used_params) == 0) {
                                $sc .= " and inv.docdate>='" . $params['upd_begdate'] . "'";
                                $used_params[] = 'upd_begdate';
                            }
                            $sc .= ")";
                        }
                    }

                }
            }

            $lst = buildopertype::from('buildopertypes as bot')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderByRaw("ifnull(ordr,99999) asc")->orderBy('name')
                ->get()->pluck('name', 'id')->toArray();
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

}
