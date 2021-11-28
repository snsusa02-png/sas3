<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Cache;

class bdgtacnttype extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'bdgtacnttypes';
    static public $sysobjid = 875;


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
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    static public function lstActive()
    {
        //Cache::forget(self::$prefix . '_lstTypes_' );
        $data = Cache::remember(self::$prefix . '_lstActive_' . 0, now()->addMinutes(15)
            , function () /*use ($mchntypeid)*/ {
                $lst = self::select('id', 'name')
                    ->where('active', 1);
                $lst = $lst->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function lstVariantsForItem($itmid, $itmsumid)
    {
        Cache::forget(self::$prefix . '_lstVariantsForItem_');
        $data = Cache::remember(self::$prefix . '_lstVariantsForItem_', now()->addMinutes(1)
            , function () use ($itmid, $itmsumid) {
                $t_grps = self::from('bdgtacnttypes as t')
                    ->select('dir', 'id', 'name')
                    ->where('active', 1)
                    ->whereraw("not exists(select 1 from budget_itmsums as i where i.acnttypeid=t.id and i.itmid = ? and i.id<>?)", [$itmid, $itmsumid])
                    ->orderby('t.dir', 'desc')
                    ->orderby('t.ordr')
                    ->orderby('t.name')
                    ->get();


                if (isset($t_grps)) {
                    //Преобразуем в массив, нужный для отображения в виде выпадающего списка с группировкой -----------------------
                    $dirnames = [-1 => 'расходы', 1 => 'доходы'];
                    $arr = [];
                    $curTypeID = null;
                    $curTypeName = null;
                    $lst = [];
                    $i = 1;
                    foreach ($t_grps as $s) {
                        if ($s->dir <> $curTypeID) {
                            if (isset($curTypeID)) {
                                $arr += [$curTypeName => $lst];  //Нужная форма добавления элемента в массив!
                            }
                            $lst = [];
                            $curTypeID = $s->dir;
                            $curTypeName = $dirnames[$s->dir] ?? '-';
                        }
                        $lst += [$s->id => $s->name];
                        $i++;
                    }
                    if (isset($curTypeID)) {
                        $arr += [$curTypeName => $lst];
                    }
                    //------------------------------------------------------------------------------------------
                } else
                    $arr = null;
                $lst = $arr;
                //dd($lst);

                return $lst;
            }
        );
        return $data;
    }

    static public function lstVariants()
    {
        Cache::forget(self::$prefix . '_lstVariants_');
        $data = Cache::remember(self::$prefix . '_lstVariants_', now()->addMinutes(1)
            , function () {
                $t_grps = self::from('bdgtacnttypes as t')
                    ->select('dir', 'id', 'name')
                    ->where('active', 1)
                    ->orderby('t.dir', 'desc')
                    ->orderby('t.ordr')
                    ->orderby('t.name')
                    ->get();


                if (isset($t_grps)) {
                    //Преобразуем в массив, нужный для отображения в виде выпадающего списка с группировкой -----------------------
                    $dirnames = [-1 => 'расходы', 1 => 'доходы'];
                    $arr = [];
                    $curTypeID = null;
                    $curTypeName = null;
                    $lst = [];
                    $i = 1;
                    foreach ($t_grps as $s) {
                        if ($s->dir <> $curTypeID) {
                            if (isset($curTypeID)) {
                                $arr += [$curTypeName => $lst];  //Нужная форма добавления элемента в массив!
                            }
                            $lst = [];
                            $curTypeID = $s->dir;
                            $curTypeName = $dirnames[$s->dir] ?? '-';
                        }
                        $lst += [$s->id => $s->name];
                        $i++;
                    }
                    if (isset($curTypeID)) {
                        $arr += [$curTypeName => $lst];
                    }
                    //------------------------------------------------------------------------------------------
                } else
                    $arr = null;
                $lst = $arr;
                //dd($lst);

                return $lst;
            }
        );
        return $data;
    }

    static public function lst4equiprqsts()
    {
        //Cache::forget(self::$prefix . '_lst4equiprqsts_' );
        $data = Cache::remember(self::$prefix . '_lst4equiprqsts_', now()->addMinutes(15)
            , function () {
                $lst = self::select('id', 'name')
                    ->where(['active' => 1, 'for_equiprqst' => 1]);
                $lst = $lst->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name типов статей бюджета
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
                            //тип статьи бюджета связан с позицией в заявках на материалы
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . " exists(select 1 from equiprqst_items as eri
                                        join budget_itmsums as bis on bis.id=eri.bdgtitmsumid
                                        where bis.acnttypeid = bat.id)";

                        }elseif ($key == 'in_fot_staff') {
                            //тип статьи бюджета связан с источником ФОТ сотрудников
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . " exists(select 1 from orgstaff as os
                                        where os.fot_acnttypeid = bat.id)";

                        } elseif ($key == 'active') {
                            $sc .= " and bat.active={$val}";

                        } elseif ($key == 'dir') {
                            $sc .= " and bat.dir={$val}";

                        } elseif ($key == 'for_equiprqst') {
                            $sc .= " and bat.for_equiprqst={$val}";

                        } elseif ($key == 'budgetid') {
                            //$sc .= " and exists (select 1 from budget_items as bi where bi.buildopertypeid=bot.id and bi.budgetid={$val})";

                        } elseif ($key == 'jts_buildobjid') {
                            $sc .= " and exists (select 1 from jobtimesheets as jts
                            join jts_items as js on js.jts_id=jts.id
                            join orgstaff as os on os.id=js.staffid
                            where os.fot_acnttypeid=bat.id and jts.buildobjid={$val})";

                        } elseif ($key == '--budget_orgid') {
                            $sc .= " and exists (select 1 from budget_items as bi join budgets as b on b.id=bi.budgetid
                                        where bi.buildopertypeid=bot.id and b.orgid={$val} )";

                        } elseif ($key == '--exe_contractid') {
                            $sc .= " and exists (select 1 from contract_exes as ce where ce.buildopertypeid=bot.id and ce.contractid={$val})";

                        } elseif ($key == '--upd_ownorgid') {

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
                        } elseif ($key == '--upd_begdate') {
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

                        } elseif ($key == '--upd_enddate') {
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

            $lst = bdgtacnttype::from('bdgtacnttypes as bat')
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
