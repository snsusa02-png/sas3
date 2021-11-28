<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use DB;

class bot_ri_lim extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    //Потребности в материалах для вида работ / Ограничительные
    static public $prefix = 'bot_ri_lims';
    static public $sysobjid = 971;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid');
    }

    public function budget_itmsum()
    {
        return $this->hasOne(budget_itmsum::class, 'id', 'bdgtitmsumid')->withDefault();
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')
            ->withDefault();
    }

    public static function rqListrefitem4Auto($request)
    {
        $search_str = $request->q;
        $cwp_id = $request->cwp;
        $buildopertypeid = $request->bot;
        $itID = $request->it;
        $recid = $request->rid;
        //$s_isservice = $request->get("svc");

        $search_str = strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
        $search_str = preg_replace('| +|', ' ', $search_str);
        $find = explode(" ", $search_str);

        $search = " 1=1";
        $sc2 = ' ';     //для поиска по obj_names

        if (count($find) > 0) {
            $search .= ' and ((1=1';
            $sс2 = ' and 1=1';
            foreach ($find as $f) {
                $search .= " and LCASE( CONCAT(";
                //$search .= "  ' ', ri.name, ' ', ifnull(ri.code,' '), ' ', ifnull(t.name,' ')";
                //без поиска по категории товара
                $search .= "  ' ', ri.name, ' ', ifnull(ri.code,' ')";
                $search .= ", ' ', ifnull(ri.searchname,' ')";
                $search .= ", ' ', ifnull(ri.altname,' ')";
                $search .= ") ) like '% " . $f . "%'";

                //для поиска по obj_names
                $sc2 .= " and n.name like '%$f%'";

            }
            $search .= ')';

            $sc2 = ' or exists(select 1 from obj_names as n where n.sysobjid=105 and n.objid=ri.id' . $sc2 . ')';

            $search = $search . $sc2 . ')';
        }
        if ($itID != NULL) {
            $search .= " and ri.ItmTypeID = " . $itID;
        }
        //dd($search);

        $rq = refitem::from('refitems as ri')
            ->join('bot_ri_lims as rl', function ($j) use ($cwp_id) {
                $j->on('rl.refitmid', 'ri.id')
                    ->whereRaw("rl.bdgtitmsumid in (
                    SELECT bis.id FROM budget_itmsums as bis
                    join budget_items as bi on bi.id=bis.itmid
                    join budgets as b on b.id=bi.budgetid
                    join contract_workplans as cwp on cwp.contractid=b.par_contractid
                    and cwp.buildopertypeid=bi.buildopertypeid and cwp.id={$cwp_id}
                    )");
            })
            ->leftjoin('itmtypes as t', 't.id', 'ri.itmtypeid')
            ->leftjoin('unittypes as ut', 'ut.id', 'ri.unittypeid')
            //->where('ri.active', 1)
            ->where('rl.lim_qty', '>', 0)
            ->whereRaw($search)
            ->select("ri.id"
                , "ri.code"
                , "ri.name", "t.name as itname", "ri.photourl"
                , 'ri.unittypeid'
                , DB::raw('ifnull(ut.name,ri.unit) as unittypename')
                , DB::raw('ifnull(ut.decimal_dgts,3) as decimal_dgts')
                , DB::raw("rl.lim_qty
                    - ifnull((select sum(qty) from cwp_work_equips as weq where weq.ri_limid=rl.id and weq.id<>{$recid}),0) as qty")
                //DB::raw('calc_refitmprice4org (' . $orgid_low . ',ri.id) as price'),
                //, db::raw("ifnull(ri.price,'н/з') as price")
                , 'ri.price'
                , 'rl.id as ri_limid'
            //DB::raw('refitm_specinfo (ri.id,"; ") as specinfo')
            )
            ->orderby('itname')
            //->orderby('ri.code')
            ->orderby('ri.name');

        $s_isservice = $request->get("svc");
        if (isset($s_isservice)) {
            $rq = $rq->where('t.isservice', $s_isservice);
        }

        $rq = $rq->orderBy('ri.name');
        //dd($rq);
        return $rq;
    }


}
