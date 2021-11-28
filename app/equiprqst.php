<?php

namespace App;

use App\equiprqst_expense;
use App\Events\notifyEvent;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;
use Cache;
use DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;


class equiprqst extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'equiprqsts';
    static public $sysobjcode = 'equiprqst';
    static public $sysobjid = 870;

    protected $guarded = [];

    static public $stages = [
        1 => 'черновик',
        2 => 'размещена, ожидание проверки',
        3 => 'проверена, ожидание обработки',
        4 => 'в обработке',
        5 => 'заказ поставщику',
        6 => 'получен',
        7 => 'передан заказчику',
        8 => 'отработан',
    ];

    static public $stage_css = [
        1 => 'background-color: silver;',
        2 => 'background-color: lightyellow;',
        3 => 'background-color: snow;',
        4 => 'background-color: salmon;',
        5 => 'background-color: #597cb0;',
        6 => 'background-color: silver;',
        7 => 'background-color: silver;',
        8 => 'background-color: silver;',
        9 => 'background-color: silver;',
    ];


    public function stage()
    {
        return $this->hasOne(obj_stage::class, 'code', 'stageid')
            ->where('sysobjid', 870)->withDefault();
    }


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'inituserid')->withDefault();
    }

    public function initstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'initstaffid')->withDefault();
    }

    public function initorg()
    {
        return $this->hasOne(org::class, 'id', 'initorgid')->withDefault();
    }

    public function org()   //заказчик-подрядчик
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function contract()   //договор с подрядчиком
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function pln_suporg()
    {
        return $this->hasOne(org::class, 'id', 'pln_suporgid')->withDefault();
    }

    public function finuser()
    {
        return $this->hasOne(User::class, 'id', 'finuserid')->withDefault();
    }

    public function exeuser()
    {
        return $this->hasOne(User::class, 'id', 'exeuserid')->withDefault();
    }

    public function exeorg()
    {
        return $this->hasOne(org::class, 'id', 'exeorgid')->withDefault();
    }

    public function exestaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'exestaffid')->withDefault();
    }


    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')->withDefault();
    }

    public function budgetitm()
    {
        return $this->hasOne(budget_item::class, 'id', 'budgetitmid')->withDefault();
    }

    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')->withDefault();
    }

    //связь с позициями
    public function items()
    {
        return $this->hasMany(equiprqst_item::class, 'rqstid', 'id')
            ->with('bdgtitmsum')
            ->with('suporg');
    }

    //связь с принятыми решениями
    public function decisions()
    {
        return $this->hasMany(obj_approval::class, 'objid', 'id')
            ->join('obj_stages', 'obj_stages.id', 'obj_approvals.stageid')
            ->where('obj_approvals.sysobjid', 870)
            ->orderBy('obj_stages.ordr')
            ->with('user');
    }

    //связь с доп. затратами
    public function expenses()
    {
        return $this->hasMany(equiprqst_expense::class, 'rqstid', 'id');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function hash_base()
    {
        //Значения значимых полей. Для последующего вычисления хэша

        $val = '';
        $val .= '|' . $this->buildopertypeid;
        $val .= '|' . $this->orgid;
        $val .= '|' . $this->contractid;
        $val .= '|';

        foreach ($this->items()->get() as $itm) {
            $val .= '{' . ($itm->refitmid ?? $itm->itmname);
            $val .= '|' . ($itm->unittypeid ?? $itm->unit);
            $val .= '|' . ($itm->rqst_qty ?? '-');
            $val .= '}';
        }
        return $val;
    }

    public function hash()
    {
        return Hash::make($this->hash_base());
    }

    static public function lstUsedOrgs_cache()
    {
        if (1 == 1) {
            return Cache::remember('equiprqsts_lstUsedOrgs', now()->addMinutes(5)
                , function () {
                    $arr = org::from('orgs as o')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from equiprqsts as er where er.payorgid=o.id)')
                        ->orderby('o.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

    static public function lstUsedInitOrgs_cache()
    {
        if (1 == 1) {
            return Cache::remember('equiprqsts_lstUsedInitOrgs', now()->addMinutes(5)
                , function () {
                    $arr = org::from('orgs as o')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from equiprqsts as er where er.initorgid=o.id)')
                        ->orderby('o.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

    static public function lstUsedPayOrgs_cache()
    {
        if (1 == 1) {
            return Cache::remember('equiprqsts_lstUsedPayOrgs', now()->addMinutes(5)
                , function () {
                    $arr = org::from('orgs as o')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from equiprqsts as er where er.payorgid=o.id)')
                        ->orderby('o.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

    static public function lstUsedBuildObjs_cache()
    {
        if (1 == 1) {
            return Cache::remember('equiprqsts_lstUsedBuildObjs', now()->addMinutes(5)
                , function () {
                    $arr = buildobj::from('buildobjs as bo')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from equiprqsts as er where er.buildobjid=bo.id)')
                        ->orderby('bo.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

    static public function lstUsedStages_cache()
    {
        if (1 == 1) {
            return Cache::remember('equiprqsts_stages_lstUsed', now()->addMinutes(5)
                , function () {
                    $arr = self::from('equiprqsts as wp')
                        ->join('obj_stages as stg', 'stg.code', 'wp.stageid')
                        ->where('stg.sysobjid', 870)
                        ->selectraw("distinct stageid as id, stg.name, stg.ordr")
                        ->orderby('stg.ordr')
                        ->get()->pluck('name', 'id')->toArray();
//                    foreach ($arr as $key => $val) {
//                        $arr[$key] = self::$stages[$key];
//                    }
                    return $arr;
                });
        }
    }

    static public function lstUsedBuildopertypes_cache()
    {
        if (1 == 1) {
            Cache::forget('equiprqsts_buildopertypes_lstUsed');
            return Cache::remember('equiprqsts_buildopertypes_lstUsed', now()->addMinutes(5)
                , function () {
                    $arr = buildopertype::from('buildopertypes as bot')
                        ->join("buildobjs as bo", 'bo.id', 'bot.buildobjid')
                        ->whereraw('exists (select 1 from equiprqsts as er where er.buildopertypeid=bot.id)')
                        ->select("bot.id", db::raw("concat(bot.name, ' / ', bo.name) as tname"))
                        ->orderby('bot.ordr')
                        ->orderby('bot.name')
                        ->get()->pluck('tname', 'id')->toArray();
                    return $arr;
                });
        }
    }

    public static function Move2Stage($rqstid, $nxt_stageid)
    {
        //перевод заказа $ordid на указаный этап

        $rslt = new Result;
        $userid = \Auth::User()->id;

        $equiprqst = equiprqst::find($rqstid);
        if (isset($equiprqst)) {

            //проверим полномочия
            if ($equiprqst->stageid == 1) {
                //пользователь должен быть Инициатором
                if ($equiprqst->inituserid != $userid
                    and !usrsysright::isUserHasRightByCode_cached($userid, self::$prefix . '.like_initiator')
                ) {
                    $rslt->err = 1;
                    $rslt->msg = 'Вы не являетесь инициатором!';
                    return $rslt;
                }
            }


            //определим направление движения - вперед или назад
            $curStage = obj_stage::where(['sysobjid' => 870, 'code' => $equiprqst->stageid])
                ->first();
            $nxtstage = obj_stage::where(['sysobjid' => 870, 'code' => $nxt_stageid])
                ->select('id', 'code', 'ordr', 'name', 'css', 'nextstage_code')
                ->first();
            //dd($curStage->ordr, $nxtstage->ordr);
            //dd($curStage, $nxtstage);

            if (1 == 1) {

                if ($curStage->ordr > $nxtstage->ordr) {
                    //двигаемся НАЗАД

                    //сначала отработаем возможность ухода с текущего этапа -----------------------
                    if ($equiprqst->stageid == 9) {
                        //уходим с этапа ОБРАБОТАНО
                        // просто нельзя уйти
                        $rslt->err = 1;
                        $rslt->msg = 'Заявка уже отработана!';
                        return $rslt;
                    } elseif ($equiprqst->stageid == 5) {
                        //уходим с этапа ОБРАБОТКА

                        //нельзя уйти, если заявка взята в обработку
                        if (isset($equiprqst->exeuserid)) {
                            $rslt->err = 1;
                            $rslt->msg = 'Заявка уже взята в обработку!';
                            return $rslt;
                        }
                        //нельзя уйти, если уже есть заказанное кол-во
                        $cnt = equiprqst_item::where('rqstid', $equiprqst->id)
                            ->whereNotNull('ord_qty')
                            ->count();
                        if ($cnt > 0) {
                            $rslt->err = 1;
                            $rslt->msg = 'По заявке уже заказаны материалы!';
                            return $rslt;
                        }
                    } elseif ($equiprqst->stageid == 11) {
                        //уходим назад с этапа "ПОДВЕДЕНИЕ ИТОГОВ"

                    }


                    //при возврате на любой этап зачищаем предыдущие решения этого этапа
                    $params = [
                        'sysobjid' => 870,
                        'objid' => $equiprqst->id,
                        'stageid' => $nxtstage->code,
                        //'dcsn_rightid' => 400,
                        //'dcsn_typecode' => 'equiprqsts.set_estprices',
                        //'dcsn_orgid' => $equiprqst->initorgid,
                    ];
                    //зачистить предыдущие решения
                    obj_approval::clrPrev($params);


                    if ($nxtstage->code == 1) {
                        //Возврат на этап ЧЕРНОВИК

                        //возврат на этап Черновик может перепрыгивать через несколько этапов,
                        // поэтому зачищаем ВСЕ решения

                        // - Плохое решение, так как при отказе в согласовании теряется Обоснование отказа
                        // сначала продублируем решения в журнале

                        $params = [
                            'sysobjid' => 870,
                            'objid' => $equiprqst->id,
                            //'stageid' => $nxtstage->code,
                        ];
                        //зачистить предыдущие решения
                        obj_approval::clrPrev($params);


                        //уберем текущих исполнителей
                        equiprqst::where('id', $equiprqst->id)
                            ->update([
                                'exeuserid' => null,
                                'exestaffid' => null,
                            ]);

                        // для совместимости, уберем активность
                        $equiprqst->active = 0;

                    } elseif ($nxtstage->code == 2) {
                        //Возврат на этап Согласование СОСТАВА заявки

                    } elseif ($nxtstage->code == 10) {
                        //Возврат на этап ОЦЕНКА Стоимости заявки

                    }


                } elseif ($curStage->ordr < $nxtstage->ordr) {
                    //Двигаемся ВПЕРЕД ================================================================================

                    //сначала отработаем возможность ухода с текущего этапа -----------------------
                    if ($equiprqst->stageid == 1) {
                        //уходим с этапа ЧЕРНОВИК
                        // => Должен быть состав !

                        if ($equiprqst->items->count() == 0) {
                            $rslt->err = 1;
                            $rslt->msg = 'Должен быть определен состав заявки!';
                            return $rslt;
                        }

                        if (!isset($equiprqst->buildopertypeid)) {
                            $rslt->err = 1;
                            $rslt->msg = 'Должен быть указан вид работ!';
                            return $rslt;
                        }

                        // для совместимости, установим активность
                        $equiprqst->active = 1;

                        //нужно убрать все ранее сделанные согласования начиная с устанавливаемого этапа
                        $baseparams = [
                            'sysobjid' => 870,
                            'objid' => $equiprqst->id,
                        ];
                        obj_approval::where($baseparams)->where('stageid', '>=', $nxt_stageid)->delete();

                    } elseif ($equiprqst->stageid == 2) {
                        //уходим с этапа Проверка правильности состава заявки Технологом
                        // => Должно быть согласование

                        $cnt = obj_approval::where('sysobjid', 870)
                            ->where('objid', $equiprqst->id)
                            ->where('stageid', $equiprqst->stageid)
                            ->whereraw("ifnull(decision,0)=0")
                            ->count();

                        if ($cnt > 0) {
                            $rslt->err = 1;
                            $rslt->msg = 'Правильность состава заявки должна быть подтверждена!';
                            return $rslt;
                        }

                    } elseif ($equiprqst->stageid == 10) {
                        //уходим с этапа Оценка стоимости состава заявки
                        // => Не должно быть строк без est_price

                        $cnt = equiprqst_item::where('rqstid', $equiprqst->id)
                            ->wherenull("est_price")
                            ->count();
                        if ($cnt > 0) {
                            $rslt->err = 1;
                            $rslt->msg = 'Для каждой позиции заявки должна быть определена примерная цена!';
                            return $rslt;
                        }


                        //Автоматически отметим согласование в obj_approvals: ------------------------------
                        if ($cnt == 0) {
                            //Нет позиций без оценки стоимости => можно зафиксировать согласование

                            $search_params = [
                                'sysobjid' => self::$sysobjid,
                                'objid' => $equiprqst->id,
                                'stageid' => $equiprqst->stageid,
                                'dcsn_rightid' => 400,
                                //'dcsn_typecode' => $dcsn_rightcode,
                            ];
                            $set_params = [
                                'dcsn_userid' => $userid,
                                'decision' => 1,
                                'dcsn_at' => now(),
                                'descript' => '',
                                'obj_hash' => $equiprqst->hash(),
                            ];

                            $rslt_cnt = obj_approval::where($search_params)->update($set_params);
                            if ($rslt_cnt > 0) {
                                $msg = \Auth::user()->name . ": Заявка предварительно оценена по стоимости.";
                                objlog::log_info(self::$sysobjid, $rqstid, $msg, 5);
                                $sd = ['success' => $msg];
                            }

                        }
                        // ---------------------------------------------------------------------------------


                    } elseif ($equiprqst->stageid == 4) {
                        //уходим вперед с этапа 4: Определение источников финансирования (бюджета / статей расхода)

                        //=> для каждой позиции должен быть определен источник финансирования
                        $cnt = equiprqst_item::where('rqstid', $equiprqst->id)
                            ->wherenull("bdgtitmsumid")
                            ->count();
                        if ($cnt > 0) {
                            $rslt->err = 1;
                            $rslt->msg = 'Для каждой позиции заявки должен быть определен источник финансирования!';
                            return $rslt;
                        }


                        // => Должно быть согласование
                        $cnt = obj_approval::where('sysobjid', 870)
                            ->where('objid', $equiprqst->id)
                            ->where('stageid', $equiprqst->stageid)
                            ->whereraw("ifnull(decision,0)=0")
                            ->count();

                        if ($cnt > 0) {
                            $rslt->err = 1;
                            $rslt->msg = 'В заявке должны быть согласованы статьи расхода бюджета!';
                            return $rslt;
                        }


                        //Пользователь должен быть текущим исполнителем
                        if ($equiprqst->finuserid != $userid) {
                            $rslt->err = 1;
                            $rslt->msg = 'Вы не являетесь пользователем, установившим статьи расхода бюджета!';
                            return $rslt;
                        }

                        //Теперь выполним действия при уходе с этапа определения статей бюджета (4) -----------------
                        equiprqst::updM15Data($equiprqst);  //заполним m15srcorgid, m15tgtorgid в соответствии с указанными бюджетами
                        //-------------------------------------------------------------------------------------------

                    } elseif ($equiprqst->stageid == 3) {
                        //уходим с этапа Утверждения руководителем

                        //2020-12-30 добавим контроль остатка бюджета, пусть решение принимает руководитель
                        if (1 == 1) {
                            $bdgt_sums = equiprqst_item::from('equiprqst_items as ri')
                                ->join('budget_itmsums as s', 's.id', 'ri.bdgtitmsumid')
                                ->join('budgets as b', 'b.id', 's.budgetid')
                                ->where('ri.rqstid', $equiprqst->id)
                                ->select('s.id', DB::raw("sum(ri.rqst_qty*ri.est_price) as est_sum")
                                    , DB::raw("ifnull(s.fctsum,0) as lim_sum")
                                )
                                ->groupby('s.id')
                                ->get();

                            foreach ($bdgt_sums as $itm) {
                                if ($itm->est_sum > $itm->lim_sum) {
                                    $rslt->err = 1;
                                    $rslt->msg = 'Превышен лимит бюджета!';
                                    return $rslt;
                                }
                            }
                        }


                        // => Должно быть согласование
                        $cnt = obj_approval::where('sysobjid', 870)
                            ->where('objid', $equiprqst->id)
                            ->where('stageid', $equiprqst->stageid)
                            ->whereraw("ifnull(decision,0)=0")
                            ->count();

                        if ($cnt > 0) {
                            $rslt->err = 1;
                            $rslt->msg = 'Заявка должна быть утверждена руководителем!';
                            return $rslt;
                        }


                        //Раз все хорошо, зарезервируем бюджет на сумму оценки ----------------
                        equiprqst::budget_reg_est($equiprqst->id);
                        //---------------------------------------------------------------------


                    } elseif ($equiprqst->stageid == 5) {
                        //уходим с этапа Обработки отделом снабжения ------------------------------------------------

                        //на всякий случай - пересчитаем equiprqst_items.ord_qty,  так как были случаи расхождения
                        self::updOrdQty($equiprqst->id);

                        // => все неотказанные позиции должны быть иметь ord_qty >= get_qty
                        $cnt = equiprqst_item::where('rqstid', $equiprqst->id)
                            ->where('ord_decision', 1)
                            ->whereRaw('(ord_qty is null or get_qty is null or (get_qty+ifnull(noreq_qty,0)) < ord_qty)')
                            //->get();
                            ->count();

                        //dd($cnt, $curStage->code, $equiprqst->stageid, $nxtstage->code);
                        if ($cnt > 0) {
                            $rslt->err = 1;
                            $rslt->msg = 'Заявка должна быть отработана по всем позициям!';
                            return $rslt;
                        }


                        // => использование каждого счета не должно превышать его сумму -----------
                        $invoices = invoice::from('invoices as inv')
                            ->join('eritm_offers as ofr', 'ofr.invoiceid', 'inv.id')
                            ->join('equiprqst_items as eri', 'eri.id', 'ofr.eritmid')
                            ->where('eri.rqstid', $equiprqst->id)
                            //->whereraw('ifnull(inv.enddate,curdate()) >= curdate()-14')
                            ->select('inv.id', 'inv.docsum', db::raw("sum(ofr.ord_sum) as ord_sum")
                            )
                            ->groupby("ofr.invoiceid")
                            ->get();
                        //dd($invoices);
                        $cnt = 0;
                        foreach ($invoices as $invoice) {
                            if ($invoice->docsum < $invoice->ord_sum)
                                $cnt++;
                        }
                        if ($cnt > 0) {
                            $rslt->err = 1;
                            $rslt->msg = 'Превышена сумма счета!';
                            return $rslt;
                        }

                    } elseif ($equiprqst->stageid == 11) {
                        //уходим вперед с этапа "ПОДВЕДЕНИЕ ИТОГОВ"

                        //зарегистрируем окончательное использование бюджетов
                        $sd = equiprqst::budget_reg_use($equiprqst->id);

                        if (isset($sd['error'])) {
                            $rslt->err = 1;
                            $rslt->msg = $sd['error'];
                            return $rslt;
                        }
                    }
                    //-----------------------------------------------------------------------------


                    // Теперь выполним действия при входе на соотв. этап
                    if ($nxtstage->code == 2) {
                        //ВХОД на этап 2 - Проверка состава заявки Технологом

                        //создадим заготовки для утверждения:
                        $params = [
                            'sysobjid' => 870,
                            'objid' => $equiprqst->id,
                            'dcsn_rightid' => 382,
                            'dcsn_typecode' => 'equiprqsts.confirm',
                            'stageid' => $nxtstage->code,
                            'dcsn_orgid' => $equiprqst->initorgid,
                        ];
                        $dcsn = obj_approval::chkAndCreate($params);
                        //----------------------------------------------------------------------------------

                    } elseif ($nxtstage->code == 10) {
                        //ВХОД на этап ОЦЕНКИ СТОИМОСТИ

                        $params = [
                            'sysobjid' => 870,
                            'objid' => $equiprqst->id,
                            'dcsn_rightid' => 400,
                            'dcsn_typecode' => 'equiprqsts.set_estprices',
                            'stageid' => $nxtstage->code,
                            'dcsn_orgid' => $equiprqst->initorgid,
                        ];
                        //зачистить предыдущие решения
                        obj_approval::clrPrev($params);

                        //найдем ранее созданную или создадим заготовку для фиксации решения
                        $dcsn = obj_approval::chkAndCreate($params);


                    } elseif ($nxtstage->code == 4) {
                        //ВХОД на этап согласования БЮДЖЕТА и статей расхода

                        //нужно зачистить все ранее принятые решения для 4-го этапа
                        $baseparams = [
                            'sysobjid' => 870,
                            'objid' => $equiprqst->id,
                            'dcsn_rightid' => 399,
                            'dcsn_typecode' => 'equiprqsts.setbdgtacnts',
                            'stageid' => $nxtstage->code,
                        ];
                        obj_approval::where($baseparams)->delete();
                        //todo: переделать на update

                        //создадим заготовки для утверждения:
                        $params = [
                            'sysobjid' => 870,
                            'objid' => $equiprqst->id,
                            'dcsn_rightid' => 399,
                            'dcsn_typecode' => 'equiprqsts.setbdgtacnts',
                            'stageid' => $nxt_stageid,
                            'dcsn_orgid' => $equiprqst->initorgid,   //подойдет пользователь из любой организации, главное чтобы было право
                        ];
                        obj_approval::chkAndCreate($params);


                    } elseif ($nxtstage->code == 3) {
                        //ВХОД на этап утверждения руководством

                        //Определим - бюджеты каких организаций задействованы в заявке
                        $bdgt_orgs = equiprqst_item::from('equiprqst_items as ri')
                            ->join('budget_itmsums as s', 's.id', 'ri.bdgtitmsumid')
                            ->join('budgets as b', 'b.id', 's.budgetid')
                            ->where('ri.rqstid', $equiprqst->id)
                            ->select('b.orgid')
                            ->groupby('b.orgid')
                            ->get()->pluck('orgid')->toArray();

                        if (1 == 0) {
                            //нужно убрать все ранее принятые решения для 3-го этапа
                            // - удаляем, а не зачищаем, из-за того, что заявка могла изменить значения initorgid и payorgid
                            // => кол-во записей в obj_approvals могло измениться
                            $baseparams = [
                                'sysobjid' => 870,
                                'objid' => $equiprqst->id,
                                'dcsn_rightid' => 383,
                                'dcsn_typecode' => 'equiprqsts.approve',
                                'stageid' => $nxtstage->code,
                            ];
                            obj_approval::where($baseparams)->delete();
                        }


                        $baseparams = [
                            'sysobjid' => 870,
                            'objid' => $equiprqst->id,
                            'dcsn_rightid' => 383,
                            'dcsn_typecode' => 'equiprqsts.approve',
                            'stageid' => $nxtstage->code,
                        ];

                        //удалим согласования от уже не нужных организаций
                        obj_approval::where($baseparams)->whereNotIn('dcsn_orgid', $bdgt_orgs)->delete();


                        //2021-06-23 --- Изменение - Удаляем согласования с хэшэм, который не соответствует текущему состоянию Заявки

                        $hash_base = $equiprqst->hash_base();

                        $itms = obj_approval::where($baseparams)
                            ->select('id', 'obj_hash')
                            ->get();
                        foreach ($itms as $itm) {
                            if (!Hash::check($hash_base, $itm->obj_hash))
                                $itm->delete();
                        }
                        //------------------------------------------------------------------


                        //создадим заготовки для утверждения:
                        $params = [
                            'sysobjid' => 870,
                            'objid' => $equiprqst->id,
                            'dcsn_rightid' => 383,
                            'dcsn_typecode' => 'equiprqsts.approve',
                            'stageid' => $nxt_stageid,
                            'dcsn_orgid' => null,
                        ];

                        foreach ($bdgt_orgs as $orgid) {
                            $params['dcsn_orgid'] = $orgid;
                            obj_approval::chkAndCreate($params);
                        }
                        //-----------------------------------------------------------------------------------

                        //2021-06-23 Проверим - может быть все согласования руководителей уже есть (если заявка делается из Счета на "Материалы(Срочно)"
                        $cnt = obj_approval::where($baseparams)->whereRaw("ifnull(decision,0)=0")->count();
                        if ($cnt == 0) {
                            //Перепрыгнем через этап (утверждения руководителем) сразу на Обработку/закуп
                            $nxtstage = obj_stage::where('code', $nxtstage->nextstage_code)->first();

                            //Раз все хорошо, зарезервируем бюджет на сумму оценки ----------------
                            equiprqst::budget_reg_est($equiprqst->id);
                            //---------------------------------------------------------------------

                        }
                        //dd($nxtstage);


                    } elseif ($nxtstage->code == 5) {
                        //ВХОД на этап работы отдела Снабжения

                    } elseif ($nxtstage->code == 11) {
                        //ВХОД на этап подведения итогов

                    } elseif ($nxtstage->code == 9) {
                        //ВХОД на этап Архив/Отработано
                    }

                }

            }


            //Если что-то изменилось. Защита от повторного нажатия кнопки изменения этапа
            if ($curStage->id <> $nxtstage->id) {

                //видимо претензий нет - изменим этап и уведомим соответствующих пользователей
                $pre_stageid = $equiprqst->stageid;
                $pre_stagename = $equiprqst->stage->name;

                $equiprqst->stageid = $nxtstage->id; //$nxt_stageid;
                $equiprqst->stage_begdt = now();
                $equiprqst->save();

                $equiprqst = equiprqst::find($rqstid);
                $stagename = $equiprqst->stage->name;

                $rslt->msg = "заявка c этапа '$pre_stageid: $pre_stagename' переведена на этап '$equiprqst->stageid: $stagename'";
                objlog::log_info(self::$sysobjid, $equiprqst->id, $rslt->msg);
                connectify('success', ' ', $rslt->msg);

                //Cache::forget('meet_stages_lstUsed');

                event(new notifyEvent('equiprqsts.set_stage_' . $nxt_stageid, 870, $equiprqst->id, $userid));

            }

        } else {
            $rslt->err = 1;
            $rslt->msg = 'заявка не найдена!';
            return $rslt;
        }
        return $rslt;

    }


    public static function budget_reg_est0($rqstid)
    {
        //регистрация / резервирование суммы бюджета по оценке суммы заявки

        $userid = \Auth::user()->id;

        if (1 == 1 or usrsysright::isUserHasRightByCode_cached($userid, self::sysobjcode . '.set_estprices')) {


            $equiprqst = equiprqst::find($rqstid);

            if (isset($equiprqst)) {

                $bdgt_sums = DB::select(DB::raw("select a.itmsumid, sum(need_sum)
                , (select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=a.itmsumid) as lim_sum
                , (select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=a.itmsumid
                    and rsn_sysobjid=870 and rsn_objid={$rqstid} and os.stable=0) as used_sum
                from (
                    select ex.bdgtitmsumid as `itmsumid` , sum(ex.expense_sum) as need_sum
                        from equiprqst_expenses as ex
                        where `ex`.`rqstid` = {$rqstid}
                        and ex.bdgtitmsumid is not null
                        group by ex.bdgtitmsumid
                    union all
                    select eri.bdgtitmsumid as `itmsumid`, sum(eri.rqst_qty* eri.est_price) as need_sum
                        from equiprqst_items as eri
                        where `eri`.`rqstid` = {$rqstid}
                        and eri.bdgtitmsumid is not null
                        group by eri.bdgtitmsumid
                    ) as a
                    group by itmsumid"));

                foreach ($bdgt_sums as $sum) {
                    dd($sum->itmsumid);
                }
                dd($bdgt_sums);


                $result = new Result;
                $addedSum = 0;
                try {
                    DB::transaction(function () use ($equiprqst) {

                        //
                        $bdgt_sums = equiprqst_item::from('equiprqst_items as ri')
                            ->join('budget_itmsums as s', 's.id', 'ri.bdgtitmsumid')
                            //->join('bdgtacnttypes as bat', 'bat.id', 's.acnttypeid')
                            //->join('budgets as b', 'b.id', 's.budgetid')
                            //->join('orgs as o', 'o.id', 'b.orgid')
                            ->where('ri.rqstid', $equiprqst->id)
                            ->select('s.id as itmsumid'
                                , DB::raw("sum(ri.rqst_qty*ri.est_price) as need_sum")
                                , DB::raw("(select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=s.id) as lim_sum")
                                , DB::raw("(select sum(os.dir*os.opersum) from budget_opers as os
                                 where os.itmsumid=s.id
                                and rsn_sysobjid=870 and rsn_objid=" . $equiprqst->id . " and os.stable=0) as used_sum")
                            )
                            ->groupby('s.id')
                            ->get();
                        //dd($bdgt_sums);

                        foreach ($bdgt_sums as $itm) {
                            //учтем разнонаправленные знаки
                            $need_sum = $itm->need_sum - -$itm->used_sum;
                            //dd($need_sum);
                            if ($need_sum > 0) {
                                if ($need_sum <= $itm->lim_sum) {
                                    $oper = new budget_oper([
                                        'itmsumid' => $itm->itmsumid,
                                        'operdate' => now(),
                                        'dir' => -1,
                                        'opersum' => $need_sum,
                                        'reason' => "Заявка на материалы №" . $equiprqst->id,
                                        'rsn_sysobjid' => 870,
                                        'rsn_objid' => $equiprqst->id,
                                        'stable' => 0,
                                        'active' => 1,
                                    ]);
                                    $oper->save();
                                    //$addedSum += $need_sum;

                                    //Перебдим - если текущее значение остатка бюдета < 0, то отменяем все!
                                    $cur_sum = budget_oper::where('itmsumid', $itm->itmsumid)
                                        ->selectraw("sum(dir*opersum) as cur_sum")
                                        ->first()->cur_sum;
                                    if ($cur_sum < 0) {
                                        throw new \Exception ('Превышение бюджета!');
                                    }

                                } else {
                                    throw new \Exception ('Недостаточно бюджета!');
                                }
                            }
                        }
                        //return $addedSum;

                        $bdgt_sums = equiprqst_expense::from('equiprqst_expenses as exp')
                            ->join('budget_itmsums as s', 's.id', 'exp.bdgtitmsumid')
                            ->where('exp.rqstid', $equiprqst->id)
                            ->select('s.id as itmsumid'
                                , DB::raw("sum(exp.expense_sum) as need_sum")
                                , DB::raw("(select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=s.id) as lim_sum")
                                , DB::raw("(select sum(os.dir*os.opersum) from budget_opers as os
                                    where os.itmsumid=s.id
                                    and rsn_sysobjid=873 and rsn_objid=exp.id and os.stable=0) as used_sum")
                            )
                            ->groupby('s.id')
                            ->get();
                        dd(1112);
                        dd($bdgt_sums);

                    });
                } catch (\Exception $e) {
                    //} catch (\Illuminate\Database\QueryException $e) {

                    DB::rollback();
                    //$result->err = $e->errorInfo[0];
                    $result->err = 1;
                    $result->msg = 'Ошибка регистрации резервирования бюджета: ' . $e->getMessage();
                }

                if ($result->err == 0) {
                    $msg = "Бюджет зарезервирован";
                    objlog::log_info(self::$sysobjid, $rqstid, $msg, 5);
                    $sd = ['success' => $msg];
                } else {
                    $msg = $result->msg;
                    objlog::log_info(self::$sysobjid, $rqstid, $msg, 5);
                    $sd = ['error' => $msg];
                }
            }


        } else {
            $msg = "Попытка ";
            objlog::log_info(870, $rqstid, $msg, 5);
            $sd = ['error' => $msg];
        }

        //return redirect(route($this->sysobjcode . ".edit", $rqstid))->with($sd);
        return;
    }

    public static function budget_reg_est($rqstid)
    {
        //регистрация / резервирование суммы бюджета по оценке суммы заявки

        $userid = \Auth::user()->id;

        if (1 == 1 or usrsysright::isUserHasRightByCode_cached($userid, self::sysobjcode . '.set_estprices')) {


            $equiprqst = equiprqst::find($rqstid);

            if (isset($equiprqst)) {


                $result = new Result;
                $addedSum = 0;
                try {
                    DB::transaction(function () use ($equiprqst) {

                        //
                        $rqstid = $equiprqst->id;

                        $bdgt_sums = DB::select(DB::raw("select a.itmsumid, sum(need_sum) as need_sum
                , (select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=a.itmsumid) as lim_sum
                , (select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=a.itmsumid
                    and rsn_sysobjid=870 and rsn_objid={$rqstid} and os.stable=0) as used_sum
                from (
                    select ex.bdgtitmsumid as `itmsumid` , sum(ex.expense_sum) as need_sum
                        from equiprqst_expenses as ex
                        where `ex`.`rqstid` = {$rqstid}
                        and ex.bdgtitmsumid is not null
                        group by ex.bdgtitmsumid
                    union all
                    select eri.bdgtitmsumid as `itmsumid`, sum(eri.rqst_qty* eri.est_price) as need_sum
                        from equiprqst_items as eri
                        where `eri`.`rqstid` = {$rqstid}
                        and eri.bdgtitmsumid is not null
                        group by eri.bdgtitmsumid
                    ) as a
                    group by itmsumid"));

                        //dd($bdgt_sums);

                        foreach ($bdgt_sums as $itm) {

                            //учтем разнонаправленные знаки
                            $need_sum = $itm->need_sum - -$itm->used_sum;
                            //dd($need_sum);
                            if ($need_sum > 0) {
                                //нужно добавить
                                if ($need_sum <= $itm->lim_sum) {
                                    $oper = new budget_oper([
                                        'itmsumid' => $itm->itmsumid,
                                        'operdate' => now(),
                                        'dir' => -1,
                                        'opersum' => $need_sum,
                                        'reason' => "Заявка на материалы №" . $equiprqst->id,
                                        'rsn_sysobjid' => 870,
                                        'rsn_objid' => $equiprqst->id,
                                        'stable' => 0,
                                        'active' => 1,
                                    ]);
                                    $oper->save();
                                    //$addedSum += $need_sum;

                                    //Перебдим - если текущее значение остатка бюдета < 0, то отменяем все!
                                    $cur_sum = budget_oper::where('itmsumid', $itm->itmsumid)
                                        ->selectraw("sum(dir*opersum) as cur_sum")
                                        ->first()->cur_sum;
                                    if ($cur_sum < 0) {
                                        throw new \Exception ('Превышение бюджета!');
                                    }

                                } else {
                                    throw new \Exception ('Недостаточно бюджета!');
                                }
                            } elseif ($need_sum < 0) {
                                //нужно убавить
                                $oper = budget_oper::where([
                                    'itmsumid' => $itm->itmsumid,
                                    'dir' => -1,
                                    'rsn_sysobjid' => 870,
                                    'rsn_objid' => $equiprqst->id,
                                    'stable' => 0,
                                ])->first();
                                if (isset($oper)) {
                                    if ($oper->opersum > (-$need_sum)) {
                                        $oper->opersum -= (-$need_sum);
                                        $oper->save();
                                    } else {
                                        $oper->delete();

                                        //? как-то умалчиваем случай когда было зарезервировано БОЛЬШЕ, чем мы сейчас высвобождаем
                                        // но такого быть не должно :)
                                    }
                                }
                            }
                        }
                        //return $addedSum;

                    });
                } catch (\Exception $e) {
                    //} catch (\Illuminate\Database\QueryException $e) {

                    DB::rollback();
                    //$result->err = $e->errorInfo[0];
                    $result->err = 1;
                    $result->msg = 'Ошибка регистрации резервирования бюджета: ' . $e->getMessage();
                }

                if ($result->err == 0) {
                    $msg = "Бюджет зарезервирован";
                    objlog::log_info(self::$sysobjid, $rqstid, $msg, 5);
                    $sd = ['success' => $msg];
                } else {
                    $msg = $result->msg;
                    objlog::log_info(self::$sysobjid, $rqstid, $msg, 5);
                    $sd = ['error' => $msg];
                }
            }


        } else {
            $msg = "Попытка ";
            objlog::log_info(870, $rqstid, $msg, 5);
            $sd = ['error' => $msg];
        }

        //return redirect(route($this->sysobjcode . ".edit", $rqstid))->with($sd);
        return;
    }


    static public function budget_reg_use($rqstid)
    {

        $userid = \Auth::user()->id;

        if (1 == 1 or usrsysright::isUserHasRightByCode_cached($userid, 'equiprqsts.set_estprices')) {

            $equiprqst = equiprqst::find($rqstid);
            if (isset($equiprqst)) {

                $result = new Result;
                $addedSum = 0;
                try {
                    DB::transaction(function () use ($equiprqst) {

                        //
                        $rqstid = $equiprqst->id;
                        $bdgt_sums = DB::select(DB::raw("select a.itmsumid, sum(need_sum) as need_sum
                , (select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=a.itmsumid) as lim_sum
                , (select sum(os.dir*os.opersum) from budget_opers as os where os.itmsumid=a.itmsumid
                    and rsn_sysobjid=870 and rsn_objid={$rqstid} and os.stable=0) as used_sum
                from (
                    select ex.bdgtitmsumid as `itmsumid` , sum(ex.expense_sum) as need_sum
                        from equiprqst_expenses as ex
                        where `ex`.`rqstid` = {$rqstid}
                        and ex.bdgtitmsumid is not null
                        group by ex.bdgtitmsumid
                    union all
                    select eri.bdgtitmsumid as `itmsumid`, sum(eri.ord_qty*ifnull(eri.out_price,eri.ord_price)) as need_sum
                        from equiprqst_items as eri
                        where `eri`.`rqstid` = {$rqstid}
                        and eri.bdgtitmsumid is not null
                        group by eri.bdgtitmsumid
                    ) as a
                    group by itmsumid"));

                        //dd($bdgt_sums);

                        //need_sum - потребность
                        //lim_sum - остаток по статье
                        //used_sum - уже использованно данной заявкой
                        //dd($bdgt_sums);

                        foreach ($bdgt_sums as $itm) {

                            //Потребность:
                            //$need_sum = $itm->need_sum - -$itm->used_sum; //учтем разнонаправленные знаки
                            $need_sum = $itm->need_sum;
                            //dd($itm->need_sum,  -$itm->used_sum, $need_sum, $itm);

                            if ($need_sum > 0) {

                                //попробуем взять что-то/все из резерва
                                $rsrv = budget_oper::where([
                                    'rsn_sysobjid' => 870,
                                    'rsn_objid' => $equiprqst->id,
                                    //'stable' => 0,
                                    'itmsumid' => $itm->itmsumid
                                ])
                                    ->first();
                                //dd($need_sum, $rsrv, $itm->lim_sum, $itm->limsum += $rsrv->opersum);
                                //dd(isset($rsrv));

                                if (isset($rsrv)) {
                                    //увеличим ограничение на сумму резерва и уберем запись о резерве целиком
                                    $itm->lim_sum += $rsrv->opersum;

                                } else {
                                    $rsrv = new budget_oper([
                                        'itmsumid' => $itm->itmsumid,
                                        'operdate' => now(),
                                        'dir' => -1,
                                        'reason' => "Заявка на материалы №" . $equiprqst->id,
                                        'rsn_sysobjid' => 870,
                                        'rsn_objid' => $equiprqst->id,
                                        'active' => 1,
                                    ]);
                                }

                                if ($need_sum <= $itm->lim_sum) {

                                    $rsrv->opersum = $need_sum;
                                    $rsrv->stable = 1;
                                    $rsrv->save();

                                    //Перебдим - если текущее значение остатка бюдета < 0, то отменяем все!
                                    $cur_sum = budget_oper::where('itmsumid', $itm->itmsumid)
                                        ->selectraw("sum(dir*opersum) as cur_sum")
                                        ->first()->cur_sum;
                                    if ($cur_sum < 0) {
                                        throw new \Exception ('Превышение бюджета!');
                                    }

                                    //пересчитаем считалки:
                                    $budget_item = budget_item::find($itm->itmsumid);
                                    if (isset($budget_item)) {
                                        DB::unprepared('CALL upd_budget_itmsums(' . $budget_item->budgetid . ')');
                                        objlog::log_info(736, $budget_item->budgetid, "Произведен расчет оценки завершения по фактическим данным", 5);
                                    }

                                } else {
                                    Log::debug("Недостаточно бюджета (itmsumid=" . $itm->itmsumid . "): нужно {$need_sum}, лимит " . $itm->lim_sum);
                                    throw new \Exception ('Недостаточно бюджета!');
                                }
                            } elseif ($need_sum < 0) {
                                //Нужно меньше, чем захватили - можно просто отказаться от лишнего

                                //найдем запись о резерве для этой заявки
                                $rsrv = budget_oper::where([
                                    'rsn_sysobjid' => 870,
                                    'rsn_objid' => $equiprqst->id,
                                    'stable' => 0,
                                    'itmsumid' => $itm->itmsumid
                                ])->first();
                                //dd($need_sum, $rsrv, $itm->lim_sum);
                                if (isset($rsrv)) {
                                    $rsrv->opersum = $itm->need_sum;
                                    $rsrv->stable = 1;
                                    //dd($rsrv);
                                    $rsrv->save();

                                    //пересчитаем считалки:
                                    $budget_item = budget_item::find($itm->itmsumid);
                                    if (isset($budget_item)) {
                                        DB::unprepared('CALL upd_budget_itmsums(' . $budget_item->budgetid . ')');
                                        objlog::log_info(736, $budget_item->budgetid, "Произведен расчет оценки завершения по фактическим данным", 5);
                                    }
                                }
                            }
                        }

                        //return $addedSum;

                    });
                } catch (\Exception $e) {
                    //} catch (\Illuminate\Database\QueryException $e) {

                    DB::rollback();
                    //$result->err = $e->errorInfo[0];
                    $result->err = 1;
                    $result->msg = 'Ошибка регистрации использования бюджета: ' . $e->getMessage();
                }

                if ($result->err == 0) {
                    $msg = "Использование бюджета зарегистрировано";
                    objlog::log_info(self::$sysobjid, $rqstid, $msg, 5);
                    $sd = ['success' => $msg];
                } else {
                    $msg = $result->msg;
                    objlog::log_info(self::$sysobjid, $rqstid, $msg, 5);
                    $sd = ['error' => $msg];
                }
            }

        } else {
            $msg = "Попытка ";
            objlog::log_info(self::$sysobjid, $rqstid, $msg, 5);
            $sd = ['error' => $msg];
        }

        return $sd;
    }

    public static function updM15Data($equiprqst)
    {
        //заполнение полей equiprqst_items.m15srcorgid и .m15tgtorgid в соответствии с выбранными бюджетами и подрядчиком по заявке

        if (!isset($equiprqst))
            return;

        $allupdated_cnt = 0;

        //найдем собственный бюджет подрядчика по заявке на этот вид работ
        // и сформируем массив $org_childs, содержащий иерархию владельцев бюджетов
        $bdgt = budget::from('budgets as b')
            ->join('budget_items as bi', 'bi.budgetid', 'b.id')
            ->where([
                'b.orgid' => $equiprqst->orgid,
                'bi.buildopertypeid' => $equiprqst->buildopertypeid,
                'b.par_contractid' => $equiprqst->contractid,
            ])
            ->select('b.id', 'b.parid', 'b.orgid')
            ->first();
        //dd($equiprqst->id, $equiprqst->orgid, $bdgt);
        if (!isset($bdgt))
            return;

        $org_childs = [];  //будет содержать id организации дочернего бюджета для текущей организации
        if (isset($bdgt)) {

            $preorgid = $bdgt->orgid;
            while (isset($bdgt)) {

                $org_childs[$bdgt->orgid] = $preorgid;

                $preorgid = $bdgt->orgid;

                //предыдущий бюджет
                $bdgt = budget::select('id', 'parid', 'orgid')->find($bdgt->parid);
            }
        }
//        dd($org_childs);

        //сформируем список уникальных ЦФО (владельцев бюджета) для данной заявки
        $bdgtorgs = \App\equiprqst_item::from('equiprqst_items as eri')
            ->join('budget_itmsums as bis', 'bis.id', 'eri.bdgtitmsumid')
            ->join('budgets as b', 'b.id', 'bis.budgetid')
            ->where('eri.rqstid', $equiprqst->id)
            ->wherenotNull('eri.bdgtitmsumid')
            ->select('b.orgid as bdgtorgid')
            ->distinct()
            ->get();
        //dd($bdgtorgs);

        foreach ($bdgtorgs as $bdgtorg) {
            //dd($bdgtorg);
            $m15srcorgid = $bdgtorg->bdgtorgid; //всегда источником материалов для м-15 является владелец бюджета

            if ($bdgtorg->bdgtorgid == $equiprqst->orgid) {
                //подрядчик по заявке == источнику денег, => передачи по М-15 не будет
                $m15tgtorgid = $bdgtorg->bdgtorgid;

            } else {
                //нужно найти связь между указанным бюджетом и подрядчиком (*: НВС->СКБ->СУ7 )
                // и ближайшую "дочку" (получателя материалов) в направлении от владельца бюджета к подрядчику
                // (*: Если использован бюджет НВС, а подрядчик СУ7, то передача будет НВС->СКБ

                //восспользуемся ранее сформированным массивом зависимостей
                $m15tgtorgid = $org_childs[$bdgtorg->bdgtorgid] ?? null;
            }
            //print('<br> m15srcorgid= ' . $m15srcorgid . ', m15tgtorgid= ' . $m15tgtorgid);
            //dd('<br> m15srcorgid= ' . $m15srcorgid . ', m15tgtorgid= ' . $m15tgtorgid);

            if (isset($m15srcorgid) and isset($m15tgtorgid)) {
                $tcnt = equiprqst_item::where(['rqstid' => $equiprqst->id, 'bdgtorgid' => $bdgtorg->bdgtorgid])
                    ->whereRaw("ifnull(m15srcorgid,-1)<>{$m15srcorgid} and ifnull(m15tgtorgid,-1)<>{$m15tgtorgid}")
                    ->update([
                        'm15srcorgid' => $m15srcorgid,
                        'm15tgtorgid' => $m15tgtorgid]);
                $allupdated_cnt += $tcnt;
            } else {
                objlog::log_info(self::$sysobjid, $equiprqst->id
                    , "Ошибка установления источника и получателя для М-15: src={$m15srcorgid}, tgt={$m15tgtorgid}"
                    , 2);
            }
        }

        objlog::log_info(self::$sysobjid, $equiprqst->id, "Установлены источники и получатели для М-15: {$allupdated_cnt}", 3);
        return $allupdated_cnt;
    }

    public static function updOrdQty($rqstid)
    {
        //2021-03-26 SNS. Пересчет equiprqst_items.ord_qty по первичным данным - для указанной заявки
        if (isset($rqstid)) {

            self::from('equiprqst_items as eri')
                ->where('rqstid', $rqstid)
                ->update(['ord_qty' => DB::raw("(SELECT SUM(ord_qty) FROM eritm_offers AS ofr WHERE ofr.eritmid = eri.id )")]);
        }
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
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

    public function tags()
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

}
