<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use DB;
use Illuminate\Support\Facades\Cache;

class invoice extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'invoices';
    static public $sysobjid = 915;

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

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function for_org()
    {
        return $this->hasOne(org::class, 'id', 'for_orgid')->withDefault();
    }

    public function pay_category()
    {
        return $this->hasOne(pay_category::class, 'id', 'categoryid')->withDefault();
    }

    public function pardoc()
    {
        return $this->hasOne(invoice::class, 'id', 'pardocid')->withDefault();
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')->withDefault();
    }

    public function exe_org()
    {
        return $this->hasOne(org::class, 'id', 'exe_orgid')->withDefault();
    }

    public function exe_contract()
    {
        return $this->hasOne(contract::class, 'id', 'exe_contractid')->withDefault();
    }

    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')->withDefault();
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    static public function doctypes()
    {
        return [1 => 'счет', 2 => 'УПД'];
    }



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



    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = ' №' . $this->docnum
                . ' от ' . date_format(date_create($this->docdate), "d.m.Y")
                . ' (' . $this->notes . ')';
            if (isset($this->docsum))
                $rslt .= ' сумма: ' . number_format($this->docsum, 2);
            return $rslt;
        } else
            return null;
    }

    public static function lstForEquipRqsts($ownorgid, $doctypeid = null, $rqstnum = null)
    {
        //$rqstnum - номер заявки
        $data = invoice::from('invoices as inv')
            ->join('orgs as o', 'o.id', 'inv.orgid')
            ->whereraw("'" . $ownorgid . "' in (inv.ownorgid,inv.for_orgid)")
            ->where('inv.doctypeid', $doctypeid ?? 1)//1-Счета
            ->where('inv.categoryid', 7)//Материалы
            ->where('inv.active', 1)//Активные
            //->whereraw('datediff(curdate(),ifnull(enddate,curdate()))<91')
        ;

        if (isset($rqstnum))
            $data = $data->whereraw("inv.notes like '%{$rqstnum}%'");

        $data = $data->select('inv.id'
            , db::raw("concat(inv.docnum, ' от ', inv.docdate, ' / ', o.name, ' / ', inv.notes) as name"))
            ->orderby('inv.docdate', 'desc')
            ->orderby('inv.docnum')
            ->get()
            ->pluck('name', 'id')->toArray();

        return $data;
    }

    public static function lstCandidatsForEquipRqst($rqstid)
    {
        //$rqstid - ID заявки
        //софрмируем список счетов, которые "подходят" к заявке.
        // Соответствие: плательщик(покупатель) по счету должен быть ЦФО по одному из использованных бюджетов заявки
        $data = invoice::from('invoices as inv')
            ->join('orgs as o', 'o.id', 'inv.orgid')
            ->whereraw("inv.ownorgid in ( select b.orgid from budgets as b
                    join budget_items as bi on bi.budgetid=b.id
                    join budget_itmsums as bis on bis.itmid=bi.id
                    where exists (select 1 from equiprqst_items as eri where eri.bdgtitmsumid=bis.id and eri.rqstid={$rqstid})
                    )")
            ->where('inv.doctypeid', $doctypeid ?? 1)//1-Счета
            ->where('inv.categoryid', 7)//Материалы
            ->where('inv.active', 1)//Активные
            //->whereraw('datediff(curdate(),ifnull(enddate,curdate()))<91')
            ->whereraw("inv.notes like '%{$rqstid}%'")
            ->select('inv.id'
                , db::raw("concat(inv.docnum, ' от ', inv.docdate, ' / ', o.name, ' / ', inv.notes) as name"))
            ->orderby('inv.docdate', 'desc')
            ->orderby('inv.docnum')
            ->get()
            ->pluck('name', 'id')->toArray();

        return $data;
    }

    public static function lstCandidatsForEquipRqstOrg($rqstid, $bdgtorgid)
    {
        //$rqstid - ID заявки
        //софрмируем список счетов, которые "подходят" к заявке.
        // Соответствие: плательщик(покупатель) по счету должен быть ЦФО по одному из использованных бюджетов заявки
        $data = invoice::from('invoices as inv')
            ->join('orgs as o', 'o.id', 'inv.orgid')
            ->whereraw("inv.ownorgid in ( select b.orgid from budgets as b
                    join budget_items as bi on bi.budgetid=b.id
                    join budget_itmsums as bis on bis.itmid=bi.id
                    where exists (select 1 from equiprqst_items as eri
                        where eri.bdgtitmsumid=bis.id
                            and eri.rqstid={$rqstid}
                            and eri.bdgtorgid={$bdgtorgid}
                            )
                    )")
            ->where('inv.doctypeid', $doctypeid ?? 1)//1-Счета
            //->where('inv.categoryid', 7)//Материалы
            ->whereIn('inv.categoryid', [7,12])//Материалы, Материалы(Срочно)
            ->where('inv.active', 1)//Активные
            //->whereraw('datediff(curdate(),ifnull(enddate,curdate()))<91')
            ->whereraw("inv.notes like '%{$rqstid}%'")
            ->select('inv.id'
                , db::raw("concat(inv.docnum, ' от ', inv.docdate, ' / ', o.name, ' / ', inv.notes) as name"))
            ->orderby('inv.docdate', 'desc')
            ->orderby('inv.docnum')
            ->get()
            ->pluck('name', 'id')->toArray();

        return $data;
    }

    public static function lstUPDForEquipRqsts($ownorgid, $doctypeid = null)
    {
        return invoice::from('invoices as inv')
            ->join('orgs as o', 'o.id', 'inv.orgid')
            ->whereraw("'" . $ownorgid . "' in (inv.orgid)")
            ->where('inv.doctypeid', $doctypeid ?? 1)//1-Счета
            ->where('inv.categoryid', 7)//Материалы
            ->where('inv.active', 1)//Активные
            ->whereraw('ifnull(enddate,curdate())>=curdate()-90')
            ->select('inv.id'
                , db::raw("concat('№',inv.docnum, ' от ', inv.docdate, ' / ', o.name, ' / ', inv.notes) as name"))
            ->get()
            ->pluck('name', 'id')->toArray();
    }

    public static function make_contract_exes($upd_id)
    {
        //Создает/обновляет запись об исполнении контракта на основании текущего УПД ($upd_id)

        if (isset($upd_id)) {

            $upd = self::find($upd_id);
            if (isset($upd)) {

                $recs = eritm_supply::from('eritm_supplies as sup')
                    ->join('eritm_offers as ofr', 'ofr.id', 'sup.offerid')
                    ->join('equiprqst_items as eri', 'eri.id', 'sup.eritmid')
                    ->join('budget_itmsums as bis', 'bis.id', 'eri.bdgtitmsumid')
                    ->join('budget_items as bi', 'bi.id', 'bis.itmid')
                    ->join('budgets as b', 'b.id', 'bi.budgetid')
                    ->where('sup.invoiceid', $upd_id)
                    ->where('b.gen_exe_from_upd', 1)  //2021-02-19 SNS. Введем явный признак для генерации исполнения по УПД
                    //Чтобы избежать удвоения исполнения по материалам, так как материалы по подчиненным бюджетам и так должно попадать через КС2 (раздел Материалы)
                    ->select('b.par_contractid', 'b.buildobjid', 'bi.buildopertypeid'
                        , db::raw("sum(sup.get_qty*ofr.ord_price) as sup_sum"))
                    ->groupBy('buildobjid')
                    ->groupBy('buildopertypeid')
                    ->groupBy('par_contractid')
                    ->get();

                //dd($recs);
                foreach ($recs as $rec) {

                    if (isset($rec->par_contractid) and isset($rec->buildopertypeid)) {

                        $exe = contract_exe::where([
                            'contractid' => $rec->par_contractid,
                            'buildobjid' => $rec->buildobjid,
                            'buildopertypeid' => $rec->buildopertypeid,
                            'rsn_sysobjid' => 915,
                            'rsn_objid' => $upd_id,
                            'doctypeid' => 3,
                        ])->first();

                        if (!isset($exe)) {
                            $exe = new contract_exe([
                                'contractid' => $rec->par_contractid,
                                'buildobjid' => $rec->buildobjid,
                                'buildopertypeid' => $rec->buildopertypeid,
                                'rsn_sysobjid' => 915,
                                'rsn_objid' => $upd_id,
                                'doctypeid' => 3
                            ]);
                        }
                        $exe->docdate = date_create(now())->format('Y-m-d');
                        $exe->docsum = $rec->sup_sum;
                        $exe->docinfo = 'УПД №' . $upd->docnum . ' от ' . $upd->docdate;
                        //dd($exe);
                        $exe->save();

                    }
                }
            }
        }

    }

    public static function make_all_contract_exes()
    {
        //для всех УПД
        $upd_ids = eritm_supply::whereNotNull('invoiceid')
            ->select('invoiceid')
            ->distinct()
            ->get();
        //dd($upd_ids);
        foreach ($upd_ids as $upd_id) {
            self::make_contract_exes($upd_id->invoiceid);
        }
    }

    public static function chk_upd_fullpay($invoiceid)
    {
        //Для заданного счета ($invoiceid) проверяет - полная ли оплата
        // и рассчитывает план. даты поставки (eritm_offers.plngetdate)

        //Только для счетов
        $invoice = invoice::where(['id' => $invoiceid, 'doctypeid' => 1])->first();
        if (isset($invoice)) {

            //подсчитаем общую сумму оплаты этого счета
            $totpaysum = orgplnpay_item::where(['src_sysobjid' => self::$sysobjid, 'src_objid' => $invoiceid])
                ->sum('fctpaysum');
            //dd($totpaysum);

            //Подсчитаем сумму использования в заявках
            $totusesum = eritm_offer::where(['invoiceid' => $invoiceid])
                ->sum('doc_sum');

            //dd($totpaysum, $totusesum, $totpaysum >= $totusesum);
            $fullpaydate = null;
            if ($totpaysum >= $totusesum) {

                $tdate = orgplnpay_item::where(['src_sysobjid' => self::$sysobjid, 'src_objid' => $invoiceid])
                        ->max('fctpay_at') ?? null;
                //dd($invoiceid, $tdate);
                if (isset($tdate)) {

                    $fullpaydate = date_create($tdate)->format('Y-m-d'); //Дата полной(достаточной) оплаты


                    // ---------------------------------------------------------------------------------
                    $need_save = false;
                    if ($invoice->usedsum !== $totusesum) {
                        $invoice->usedsum = $totusesum;
                        $need_save = true;
                    }
                    if ($invoice->fullpaydate !== $fullpaydate) {
                        $invoice->fullpaydate = $fullpaydate;
                        $invoice->status = ($invoice->docsum > $totpaysum) ? 'Полностью оплачен (по использованию)' : 'Полностью оплачен';
                        $need_save = true;

                        objlog::log_info(self::$sysobjid, $invoiceid, "Рассчитана дата полной (достаточной) оплаты: {$fullpaydate}");
                    }
                    if ($need_save)
                        $invoice->save();
                    // ---------------------------------------------------------------------------------


                    //получим список использования счета в заявках.
                    // Для ускорения(?) последующего обновления сгруппируем по сроку поставки
                    $plngetwrkdays = eritm_offer::where(['invoiceid' => $invoiceid])
                        ->whereNotNull('plngetwrkdays')
                        ->select('plngetwrkdays')
                        ->groupBy('plngetwrkdays')
                        ->get()->pluck('plngetwrkdays')->toarray();
                    //dd($plngetwrkdays);

                    if (count($plngetwrkdays) > 0) {

                        $updCnt = 0;
                        foreach ($plngetwrkdays as $plngetwrkday) {

                            $rslt = DB::select("select add_workdays('{$fullpaydate}',{$plngetwrkday}) as enddate");
                            $plngetdate = $rslt[0]->enddate ?? null;
                            //dd($fullpaydate, $plngetwrkday, $plngetdate);

                            //обновим eritm_offers.plngetdate, связ. с нашим счетом и имеющим тот же срок поставки
                            if (isset($plngetdate)) {
                                $updCnt += eritm_offer::where(['invoiceid' => $invoiceid,
                                    'plngetwrkdays' => $plngetwrkday])
                                    ->whereRaw("(plngetdate is null or plngetdate<>'{$plngetdate}')")
                                    ->update(['plngetdate' => $plngetdate]);
                            }
                        }
                        if ($updCnt > 0)
                            objlog::log_info(self::$sysobjid, $invoiceid, "Рассчитаны даты планируемой поставки заказанных материалов ({$updCnt})");
                    }
                }
            }

        }
    }


    public static function informer_new_upds($userid)
    {
        //Cache::forget('informer_new_upds_' . $userid);

        return Cache::remember('informer_new_upds_' . $userid, now()->addMinutes(15)
            , function () use ($userid) {

                $sc = " inv.created_at >= date_sub(curdate(), INTERVAL 7 day)";
                $sc .= "and exists(select 1 from userorgs as uo where uo.orgid=inv.ownorgid and uo.userid={$userid}
                            and uo.active=1 and now() between uo.begdt and ifnull(uo.enddt,now()) )";

                $lst = invoice::from('invoices as inv')
                    ->join('orgs as oo', 'oo.id', 'inv.ownorgid')
                    ->join('orgs as o', 'o.id', 'inv.orgid')
                    ->where('inv.doctypeid', 2)
                    ->whereRaw($sc)
                    ->select('inv.id', 'inv.docnum', 'inv.docdate'
                        , 'inv.ownorgid', 'oo.name as ownorgname'
                        , 'inv.orgid', 'o.name as orgname'
                        , 'inv.docsum', 'inv.created_at'
                    )
                    ->orderby('oo.name', 'desc')
                    ->orderby('inv.created_at', 'desc')
                    ->orderby('inv.docnum')
                    ->get();

                return $lst;
            }
        );

    }


    static public function recalcUsedSum($upd_id)
    {
        //пересчет текущей суммы использования УПД - по составу и доп-затратам

        $upd = self::find($upd_id);
        if (isset($upd)) {
            $usedsum = 0;

            //сумма доп-затрат, связанных с этим УПД
            $usedsum += equiprqst_expense::from('equiprqst_expenses as exp')
                ->where('exp.upd_id', $upd_id)
                ->sum('expense_sum');
//        dd($usedsum);

            //сумма позиций, связанных с этим УПД
            $usedsum += eritm_supply::from('eritm_supplies as sup')
                    ->join('eritm_offers as ofr', 'ofr.id', 'sup.offerid')
                    ->where('sup.invoiceid', $upd_id)
                    ->selectraw("sum(sup.get_qty*ofr.ord_price) as sum")
                    ->first()->sum ?? 0;
            //dd($usedsum);

            //проверим - суоответствует ли сумма документа сумме позиций. И исправим, если нужно ---------

            if (round(floatval($upd->usedsum) - $usedsum, 2) != 0) {
                $msg = "Авто-коррекция суммы документа по сумме использования (" . $upd->usedsum . " -> {$usedsum})";
                $upd->usedsum = $usedsum;
                $upd->save();
                objlog::log_info(self::$sysobjid, $upd->id, $msg, 5);
            }
            return $usedsum;
        }

    }


    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name документов
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

                        if (1==0) {
                            //договор указан в заявках на материалы как договор с подрядчиком
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . " exists(select 1 from equiprqsts as er where er.contractid = c.id)";

                        } elseif ($key == 'ownorgid') {
                            $sc .= " and i.ownorgid={$val}";

                        } elseif ($key == 'orgid') {
                            //вторая сторона по договору
                            $sc .= " and i.orgid={$val}";

                        } elseif ($key == 'doctypeid') {
                            //категория: 1-счет, 2-УПД
                            $sc .= " and i.doctypeid={$val}";

                        } elseif ($key == 'contractid') {
                            $sc .= " and i.contractid={$val}";

                        } elseif ($key == 'actual') {
                            //действующий в настоящее время
                            $sc .= " and " . (($val == 0) ? "not" : "")
                                . "( c.active={$val}"
                                . " and 1=1"
                                . ")";

//                        } elseif ($key == 'for_userid') {
//                            if (!usrsysright::isUserHasRightByCode_cached($val, 'contracts.read')) {
//                                $sc .= " and exists (select 1 from obj_readers as r where r.sysobjid=151
//                                and r.objid = c.id
//                                and r.userid={$val})";
//                            }
//
//                        } elseif ($key == 'buildobjid') {
//                            //$sc .= " and c.buildobjid={$val}";
//                            $sc .= " and exists (select 1 from equiprqsts as er where er.contractid = c.id
//                                and er.buildobjid={$val})";
                        }
                    }

                }
            }
            //Log::info($sc);

            $lst = self::from('invoices as i')
                ->whereRaw($sc)
                ->select('id', db::raw("concat('№',docnum,' от ', docdate) as name"))
                ->orderBy('name')
                ->get()->pluck('name', 'id')->toArray();
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }



}
