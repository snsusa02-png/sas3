<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class budget_itmsum extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'budget_itmsums';
    static public $sysobjid = 879;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function budget()
    {//Владелец бюджета
        return $this->hasOne(budget::class, 'id', 'budgetid')
            ->with('org')
            ->withDefault();
    }

    public function item()
    {//вид работ
        return $this->hasOne(budget_item::class, 'id', 'itmid');
    }

    public function acnttype()
    {//статья дохода/расходов
        return $this->hasOne(bdgtacnttype::class, 'id', 'acnttypeid')
            ->withDefault();
    }

    public function ri_lims()
    {
        return $this->hasMany(bot_ri_lim::class, 'bdgtitmsumid', 'id')
            ->with('refitem');
    }


    public static function fct_sum($itmsumid)
    {
        //расчет текущей суммы операций
        return budget_oper::where('itmsumid', $itmsumid)
                ->where('active', 1)
                ->selectRaw('sum(dir*opersum) as totsum')->first()->totsum ?? null;
    }

    public static function refr_opersum($itmsumid)
    {

        // -----------------------------------------------------------------------
        $sum = budget_oper::where(['itmsumid' => $itmsumid, 'stable' => 1, 'active' => 1])
            ->selectRaw('sum(dir*opersum) as totsum
                , sum(case when (dir=1) then 1 else 0 end*opersum) as inpsum
                , sum(case when (dir=-1) then 1 else 0 end*opersum) as outsum'
            )
            ->first();
        budget_itmsum::where(['id' => $itmsumid])
            ->update([
                'fctsum' => $sum->totsum ?? 0
                , 'fctinpsum' => $sum->inpsum ?? 0
                , 'fctoutsum' => $sum->outsum ?? 0
            ]);
        // -----------------------------------------------------------------------
        return $sum->totsum ?? 0;
    }

    public static function upd_plnsum($find_params, $add_params, $plnsum)
    {
        //Обновление плановой суммы раздела бюджета. При необходимости - создание записи
        // $find_params=['itmid' => $budget_item->id, 'acnttypeid' => 36];
        // $add_params=['budgetid' => $budgetid, 'created_by' => $userid, 'updated_by' => $userid];
        // $plnsum - decimal(12,2)

        $budget_itmsum = budget_itmsum::where($find_params)->first();
        if (!isset($budget_itmsum)) {
            $budget_itmsum = new budget_itmsum(array_merge($find_params, $add_params));
        }

        if (isset($budget_itmsum)) {
            $budget_itmsum->plnsum = round($plnsum, 2);
            $budget_itmsum->save();
        }
    }

    public static function findByContractID_BuildOperTypeID_AcntTypeID($contractid, $buildopertypeid, $acnttypeid)
    {
        //Поиск единственной записи статьи бюдджета для указанного договора-подряда, вида работ и типа статьи затрат
        if (isset($contractid) and isset($buildopertypeid) and isset($acnttypeid)) {
            //dd($contractid, $buildopertypeid, $acnttypeid);
            return self::from('budget_itmsums as bis')
                    ->join('budget_items as bi', 'bi.id', 'bis.itmid')
                    ->join('budgets as b', 'b.id', 'bi.budgetid')
                    ->where([
                        'b.par_contractid' => $contractid,
                        'bi.buildopertypeid' => $buildopertypeid,
                        'bis.acnttypeid' => $acnttypeid,
                    ])
                    ->select('bis.id')
                    ->first()->id ?? null;
        } else
            return null;
    }


    static public function rest_info_low($buildopertypeid, $orgid, $acnttypeid)
    {
        //записи с текущими суммами статьи раздела бюджета, определяемого через Вид работ, Владельца бюджета.
        $data = null;

        if (isset($buildopertypeid) and isset($orgid) and isset($acnttypeid)) {

            $budgetitmsumid = budget_item::from('budget_items as bi')
                    ->whereRaw("bi.buildopertypeid={$buildopertypeid}
                    and exists(select 1 from budgets as b where b.id=bi.budgetid and b.orgid={$orgid})")
                    ->select('id')->first()->id ?? null;


            $data = budget_itmsum::from('budget_itmsums as bis')
                ->where(['itmid' => $budgetitmsumid, 'acnttypeid' => $acnttypeid])
                ->select('bis.acnttypeid', 'bis.fctsum', 'bis.fctinpsum', 'bis.fctoutsum'
                    , db::raw("(select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=bis.id) as fctopersum")
                )
                ->get();
        }
        return $data;
    }



}
