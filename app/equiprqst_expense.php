<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class equiprqst_expense extends Model
{
    //
    static public $prefix = 'equiprqst_expenses';
    static public $sysobjid = 873;

    use DeleteTrait;

    protected $guarded = [];


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function rqst()
    {
        return $this->hasOne(equiprqst::class, 'id', 'rqstid')->withDefault();
    }
    public function bdgtitmsum()
    {
        return $this->hasOne(budget_itmsum::class, 'id', 'bdgtitmsumid')->withDefault();
    }
    public function exeorg()
    {
        return $this->hasOne(org::class, 'id', 'exeorgid')->withDefault();
    }
    public function invoice()
    {
        return $this->hasOne(invoice::class, 'id', 'invoiceid')->withDefault();
    }
    public function upd()
    {
        return $this->hasOne(invoice::class, 'id', 'upd_id')->withDefault();
    }

    public static function make_contract_exes($id)
    {
        //Создает/обновляет запись об исполнении контракта на основании текущей записи о доп. затратах ($id)

        if (isset($id)) {

            $doc = self::find($id);
            if (isset($doc)) {

                $recs = equiprqst_expense::from('equiprqst_expenses as exp')
                    ->join('budget_itmsums as bis', 'bis.id', 'exp.bdgtitmsumid')
                    ->join('budget_items as bi', 'bi.id', 'bis.itmid')
                    ->join('budgets as b', 'b.id', 'bi.budgetid')
                    ->where('exp.id', $id)
                    ->whereNotNull('b.par_contractid')
                    ->where('b.gen_exe_from_upd', 1)  //2021-02-19 SNS. Введем явный признак для генерации исполнения по УПД
                    ->select('b.par_contractid', 'b.buildobjid', 'bi.buildopertypeid'
                        , 'exp.expense_sum'
                        , 'exp.operdate'
                    )
                    ->get();

                foreach ($recs as $rec) {

                    if (isset($rec->par_contractid) and isset($rec->buildopertypeid)) {

                        $exe = contract_exe::where([
                            'contractid' => $rec->par_contractid,
                            'buildobjid' => $rec->buildobjid,
                            'buildopertypeid' => $rec->buildopertypeid,
                            'rsn_sysobjid' => 873,
                            'rsn_objid' => $id,
                            'doctypeid' => 3,
                        ])->first();

                        if (!isset($exe)) {
                            $exe = new contract_exe([
                                'contractid' => $rec->par_contractid,
                                'buildobjid' => $rec->buildobjid,
                                'buildopertypeid' => $rec->buildopertypeid,
                                'rsn_sysobjid' => 873,
                                'rsn_objid' => $id,
                                'doctypeid' => 3
                            ]);
                        }
                        $exe->docdate = $rec->operdate;
                        $exe->docsum = $rec->expense_sum;
                        $exe->docinfo = 'Доп. затраты ' . $doc->reason . ' / ' . $doc->notes;
                        //dd($exe);
                        $exe->save();

                    }
                }
            }
        }

    }


    public static function make_all_contract_exes()
    {
        //для всех доп. затрат
        $recs = equiprqst_expense::whereNotNull('bdgtitmsumid')
            ->select('id')
            ->get();
        //dd($upd_ids);
        foreach ($recs as $rec) {
            self::make_contract_exes($rec->id);
        }
    }



}
