<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use DB;
use App\Traits\Result;
use Illuminate\Support\Facades\Cache;

class equiprqst_item extends Model
{
    static public $prefix = 'equiprqst_items';
    static public $sysobjid = 872;

    use DeleteTrait;

    protected $guarded = [];

    public function rqst()
    {
        return $this->belongsTo(equiprqst::class, 'rqstid', 'id');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')->withDefault();
    }

    public function lim_refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'lim_refitmid')->withDefault();
    }

    public function bdgtorg()
    {
        return $this->hasOne(org::class, 'id', 'bdgtorgid')->withDefault();
    }

    public function suporg()
    {
        return $this->hasOne(org::class, 'id', 'suporgid')->withDefault();
    }

    public function invoice()
    {
        return $this->hasOne(invoice::class, 'id', 'invoiceid')->withDefault();
    }

    public function offers()
    {// предложения для позиции
        return $this->hasMany(eritm_offer::class, 'eritmid', 'id')
            ->with('suporg');
    }

    public function bdgtitmsum()
    {
        return $this->hasOne(budget_itmsum::class, 'id', 'bdgtitmsumid')->withDefault()
            ->with('budget')
            ->with('acnttype');
    }

    public function unittype()
    {
        return $this->hasOne(unittype::class, 'id', 'unittypeid')->withDefault();
    }


    static public function lstUsedSupOrgs_cache()
    {
        if (1 == 1) {
            return Cache::remember('equiprqsts_lstUsedSupOrgs', now()->addMinutes(5)
                , function () {
                    $arr = org::from('orgs as o')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from equiprqst_items as ri where ri.suporgid=o.id)')
                        ->orderby('o.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

    static public function lstUsedExeOrgs_cache()
    {
        if (1 == 1) {
            return Cache::remember('lstUsedExeOrgs_cache', now()->addMinutes(5)
                , function () {
                    $arr = org::from('orgs as o')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from equiprqsts as r where r.exeorgid=o.id)')
                        ->orderby('o.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }


    static public function setRefItmIDByNameAndUnit($refitmid, $ri_name, $ri_unittypeid)
    {
        if (isset($refitmid) and isset($ri_name) and isset($ri_unittypeid)) {

            //$unit = unittype::find($ri_unittypeid)->name ?? '';

            //if ($unit != '') {
            if (1 == 1) {
                self::from('equiprqst_items as eri')
                    ->whereNull('refitmid')
                    //->where(['itmname' => $ri_name, 'unit' => $unit])
                    ->where(['itmname' => $ri_name, 'unittypeid' => $ri_unittypeid])
                    ->update(['refitmid' => $refitmid]);

                Cache::forget('informer_eri_ri_stat');
            }
        }
    }

    static public function convert2tRefItmID($eritmid, $refitmid)
    {
        $result = new Result;

        if (isset($eritmid) and isset($refitmid)) {

            $equiprqst_item = equiprqst_item::find($eritmid);

            //Не будем ничего делать, если позиция уже привязана к номенклатуре
            if (isset($equiprqst_item) and !isset($equiprqst_item->refitmid)) {

                $refitem = refitem::find($refitmid);

                //если ЕИ совпадают,
                if ($refitem->unittypeid == $equiprqst_item->unittypeid) {
                    // ... то просто присвоим refitmid. И переименуем позицию заявки, чтобы не возиться с уникальностью псевдонимов
                    $equiprqst_item->refitmid = $refitem->id;
                    $equiprqst_item->itmname = $refitem->name;
                    $equiprqst_item->save();
                } else {
                    // ЕИ не совпадают. Проверим - есть ли связь через RI_Units
                    $ri_unit = ri_unit::where(
                        ['refitmid' => $refitem->id
                            , 'unittypeid' => $equiprqst_item->unittypeid
                            , 'active' => 1
                        ])
                        ->select('k2ref_unit')
                        ->first();

                    if (isset($ri_unit) and isset($ri_unit->k2ref_unit) and $ri_unit->k2ref_unit > 0) {
                        //есть коэффиуиент пересчета ЕИ заявки в ЕИ номенклатуры
                        // 1 ЕИ заявки = k2ref_unit * ЕИ номенклатуры
                        $k2ref_unit = $ri_unit->k2ref_unit;
                        //dd($k2ref_unit);

                        try {
                            DB::transaction(function () use ($equiprqst_item, $refitem, $k2ref_unit) {

                                $qty_dec_digits = $refitem->unittype->decimal_dgts ?? 3;

                                $pre_unit = $equiprqst_item->unittype->name;
                                $pre_rqst_qty = $equiprqst_item->rqst_qty;

                                $equiprqst_item->refitmid = $refitem->id;
                                $equiprqst_item->itmname = $refitem->name;
                                $equiprqst_item->unittypeid = $refitem->unittypeid;
                                $equiprqst_item->unit = $refitem->unittype->name;
                                $equiprqst_item->rqst_qty = round($equiprqst_item->rqst_qty * $k2ref_unit, $qty_dec_digits);
                                $equiprqst_item->est_price = round($equiprqst_item->est_price / $k2ref_unit, 2);
                                $equiprqst_item->smet_price = round($equiprqst_item->smet_price / $k2ref_unit, 2);

                                //не обязательно. лучше пересчитать по итогам преобразования eritm_offers / eritm_supplies
                                $equiprqst_item->ord_qty = round($equiprqst_item->ord_qty * $k2ref_unit, $qty_dec_digits);
                                //dd($equiprqst_item->ord_qty);
                                //dd($equiprqst_item);
                                $equiprqst_item->save();

                                objlog::log_info(872, $equiprqst_item->id, "Преобразование ЕИ ({$pre_rqst_qty} {$pre_unit} -> "
                                    . $equiprqst_item->rqst_qty . " " . $equiprqst_item->unit . ")");

                                $offers = eritm_offer::where('eritmid', $equiprqst_item->id)->get();
                                foreach ($offers as $eri_ofr) {

                                    $eri_ofr->ord_qty = round($eri_ofr->ord_qty * $k2ref_unit, $qty_dec_digits);
                                    $eri_ofr->ord_price = round($eri_ofr->ord_sum / $eri_ofr->ord_qty, 6);
                                    //dd($eri_ofr);
                                    $eri_ofr->save();

                                    //пересчитаем общее кол-во полученное по УПД поставщиков
                                    eritm_supply::recalc_getqty($eri_ofr->id, $equiprqst_item->id);

                                    $eri_sups = eritm_supply::where('offerid', $eri_ofr->id)->get();
                                    foreach ($eri_sups as $eri_sup) {
                                        $eri_sup->get_qty = round($eri_sup->get_qty * $k2ref_unit, $qty_dec_digits);
                                        $eri_sup->save();

                                        $m15s = ersup_m15::where('ersupid', $eri_sup->id)->get();
                                        foreach ($m15s as $m15) {
                                            $m15->m15_qty = round($m15->m15_qty * $k2ref_unit, $qty_dec_digits);
                                            $m15->save();
                                        }
                                        //пересчитаем общее кол-во переданное по формам м-15
                                        ersup_m15::updQty($eri_sup->id);
                                    }
                                }

                            });
                        } catch (\Exception $e) {
                            //} catch (\Illuminate\Database\QueryException $e) {

                            DB::rollback();
                            //$result->err = $e->errorInfo[0];
                            $result->err = 1;
                            $result->msg = 'Ошибка преобразования ЕИ: ' . $e->getMessage();
                        }


                    }
                }

            }
        }
    }

    static public function lstItemsFor($params)
    {
        //2021-03-26 SNS. универсальный конструктор массива с id, itmname позиций заявок
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
                            $sc .= " and exists(select 1 from equiprqsts as er where er.id=eri.rqstid and er.buildobjid={$val})";

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


    static public function suporg_est_diff_sums($userid)
    {
        $informerid = 101;

        Cache::forget('suporg_est_diff_sums_' . $userid);
        return Cache::remember('suporg_est_diff_sums_' . $userid, now()->addMinutes(5)
            , function () use ($informerid, $userid) {

                //проверим существоване информера и его доступность
                $informer = informer::find($informerid);
                if (!isset($informer) or $informer->active == 0)
                    return null;
                //dd($informer);

                //Проверим наличие свежих данных - менее 40 дней
                if (equiprqst_item::whereRaw("datediff(now(),est_price_at)<40")->count() == 0)
                    return null; //если нет - выходим

                if ($informer->public == 0) {
                    //информер с ограничением доступа
                    // => пользователь должен быть в списке читателей
                    if (!obj_reader::isUserInList(1801, $informerid, $userid))
                        return null;
                }

                $sql = "select ofr.suporgid
                        , sum((ofr.ord_price - eri.est_price)*ofr.ord_qty) as diff_sum
                        , max(o.name) as suporg_name
                        , count(*) as cnt
                        , min(inv.docdate) as min_date
                        , max(inv.docdate) as max_date
                        from `equiprqst_items` as eri
                        join eritm_offers as ofr on ofr.eritmid=eri.id
                        join invoices as inv on inv.id=ofr.invoiceid
                        join orgs as o on o.id=ofr.suporgid
                        WHERE est_price_at is not null
                        group by suporgid
                        order by diff_sum desc";

                $lst = DB::select(DB::raw($sql));
                return ($lst);

            });

    }
}
