<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Result;
use DB;

class budget_item extends Model
{
    use DeleteTrait;
    use FilesTrait;


    protected $guarded = [];

    static public $prefix = 'budget_items';
    static public $sysobjid = 877;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function budget()
    {//Владелец бюджета
        return $this->hasOne(budget::class, 'id', 'budgetid')
            ->withDefault();
    }

    public function parent()
    {//Родительская запись
        return $this->hasOne(budget_item::class, 'id', 'parid')
            ->withDefault();
    }

    public function buildopertype()
    {//Вид работ
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')
            ->withDefault();
    }

    public function contract()
    {//Договор-основание раздела бюджета
        return $this->hasOne(contract::class, 'id', 'contractid')
            ->withDefault();
    }

    //связь с позициями
    public function itmsums()
    {
        return $this->hasMany(budget_itmsum::class, 'itmid', 'id')
            ->with('acnttype');
    }

    public function childs()
    {
        return $this->hasMany(budget_item::class, 'parid', 'id')
            ->with('budget');
    }

    public function admindelete()
    {
        $result = new Result;
        //Удаляем себя вместе с позициями
        try {
            DB::transaction(function () {
//                $this->childs()->admindelete();
                $this->itmsums()->delete();
                return parent::delete();
            });
        } catch (\Exception $e) {
            $result->err = $e->errorInfo[0];
            $result->msg = 'Ошибка удаления записи: ' . $e->getMessage();
        }
        return $result;
    }


    static public function lstAllForBuildOperType($buildopertypeid)
    {
        if (isset($buildopertypeid)) {
            $lst = self::from('budget_items as bi')
//                ->leftJoin('orgs as oo', function ($j) {
//                    $j->on('oo.id', 'p.ownorgid');
//                })
                ->where('buildopertypeid', $buildopertypeid)
                ->wherenull('parid')//только прямые (без дочерних)
                ->select('bi.*')
                //->orderBy('p.meet_begdt', 'desc')
                ->with('budget')->get();

            return $lst;
        } else
            return null;
    }

    public static function create_by_buildobjid($budgetid)
    {
        //создание разделов бюджета на основании видов работ строительного объекта
        // в которых указан plnbudgetsum и baseworktypeid (базовый тип работ)
        // upd: baseworktypeid не обязателен. Если его нет - создадим только доходную статью

        $result = new Result();

        if (!isset($budgetid)) {
            $result->err = 1;
            $result->msg = 'Не задан бюджет! Невозможно создать разделы бюджета';
            return $result;
        }

        //найдем указанный бюджет
        $budget = budget::find($budgetid);
        if (!isset($budget)) {
            $result->err = 1;
            $result->msg = 'Задан несуществующий бюджет! Невозможно создать разделы бюджета';
            return $result;
        }

        if (!isset($budget->buildobjid)) {
            $result->err = 1;
            $result->msg = 'В бюджете не указан объект строительства! Невозможно создать разделы бюджета';
            return $result;
        }

        // из связанного строительного объектв сформируем список видов работ в которых задана оценка бюджета
        // наличие baseworktypeid - не обязательно, просто не создадим расходные статьи
        $buildopertypes = buildopertype::from('buildopertypes as bot')
            ->leftjoin('baseworktypes as bwt', 'bwt.id', 'bot.baseworktypeid')
            ->where('buildobjid', $budget->buildobjid)
            ->whereNotNull('plnbudgetsum')
            ->select('bot.id', 'bot.name', 'bot.plnbudgetsum', 'bot.baseworktypeid', 'bot.ordr'
                , 'bwt.material_smr_pcnt', 'bwt.machine_smr_pcnt', 'bwt.fot_smr_pcnt', 'bwt.nakl_sp_smr_pcnt')
            ->orderby('bot.ordr')
            ->get();
        if (count($buildopertypes) == 0) {
            $result->err = 1;
            $result->msg = 'В видах работ строительного объекта нет записей с оценкой бюджета и указанным базовым типом работ! Невозможно создать разделы бюджета';
            return $result;
        }

        $userid = \Auth::user()->id;

        foreach ($buildopertypes as $buildopertype) {
            //Проверим наличие в бюджете раздела с таким же видом работ
            $budget_item = budget_item::where(['budgetid' => $budgetid, 'buildopertypeid' => $buildopertype->id])
                ->first();
            if (!isset($budget_item)) {
                $budget_item = new budget_item(['budgetid' => $budgetid,
                    'buildopertypeid' => $buildopertype->id,
                    'name' => $buildopertype->name,
                    'created_by' => $userid,
                    'updated_by' => $userid,

                ]);
            }
            $budget_item->ordr = $buildopertype->ordr;
            $budget_item->save();

            //Проверка/создание доходной статьи - Проектное финансирование (1)
            budget_itmsum::upd_plnsum(['itmid' => $budget_item->id, 'acnttypeid' => 1],
                ['budgetid' => $budgetid, 'created_by' => $userid, 'updated_by' => $userid],
                $buildopertype->plnbudgetsum
            );

            if (isset($buildopertype->baseworktypeid)) {
                //если задан базовый тип работ - рассчитаем и создадим расходные статьи

                //Проверка/создание расходной статьи - материалы (21)
                budget_itmsum::upd_plnsum(['itmid' => $budget_item->id, 'acnttypeid' => 21],
                    ['budgetid' => $budgetid, 'created_by' => $userid, 'updated_by' => $userid],
                    round($buildopertype->material_smr_pcnt * $buildopertype->plnbudgetsum / 100, 2)
                );

                //Проверка/создание расходной статьи - машины и механизмы (23)
                budget_itmsum::upd_plnsum(['itmid' => $budget_item->id, 'acnttypeid' => 23],
                    ['budgetid' => $budgetid, 'created_by' => $userid, 'updated_by' => $userid],
                    round($buildopertype->machine_smr_pcnt * $buildopertype->plnbudgetsum / 100, 2)
                );

                //Проверка/создание расходной статьи - ФОТ осн. работ (24)
                budget_itmsum::upd_plnsum(['itmid' => $budget_item->id, 'acnttypeid' => 24],
                    ['budgetid' => $budgetid, 'created_by' => $userid, 'updated_by' => $userid],
                    round($buildopertype->fot_smr_pcnt * $buildopertype->plnbudgetsum / 100, 2)
                );

                //Проверка/создание расходной статьи - накладные расходы и прибыль (36)
                budget_itmsum::upd_plnsum(['itmid' => $budget_item->id, 'acnttypeid' => 36],
                    ['budgetid' => $budgetid, 'created_by' => $userid, 'updated_by' => $userid],
                    round($buildopertype->nakl_sp_smr_pcnt * $buildopertype->plnbudgetsum / 100, 2)
                );

            }
            //dd( $budget_item, $budget_itmsum);
        }


        $result->msg = 'Ok';
        return $result;
    }
}

