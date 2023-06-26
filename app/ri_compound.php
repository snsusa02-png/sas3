<?php

namespace App;

use App\Traits\Result;
use App\Traits\StringUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class ri_compound extends Model
{
    use \App\Traits\DeleteTrait;
    use \App\Traits\FilesTrait;

    static public $prefix = 'ri_compound';
    static public $sysobjid = 147;

    //protected $fillable = ['created_by'];

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', self::$sysobjid)
            ->withDefault();
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')
            ->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    public function items()
    {
        return $this->hasMany(ri_cmpnd_item::class, 'cmpndid', 'id');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function whosign()
    {
        return $this->hasOne(User::class, 'id', 'signed_by');
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->refitem->name
                . ' - ' . $this->notes
                . ' от ' . date_format(date_create($this->begdate), "d.m.Y");
            return $rslt;
        } else
            return null;
    }

    public static function min_begdate()
    {
        //определим минимально-допустимую дату для поля docdate
        $min_date = sysobj_lockdate::where('sysobjid', self::$sysobjid)->first()->lock_before ?? null;
        if (isset($min_date)) {
            return $min_date;
        }
        return null;
    }

    public static function auxInfo($wrhid)
    {
        $info = [];
        return $info;
    }

    public static function mayUnsignDoc($docid)
    {
        //Проверка на возможность рассогласования документа.

        $userid = \Auth::user()->id;
        $may = usrsysright::isUserHasRightByCode_cached($userid, 'ri_compounds.approve');

        if ($may) {
            //по правам - можно, но

            //проверим, есть ли товары, произведенные по этому рецепту?
            //if (ri_product_item::where('cmpndid', $docid)->count() > 0) $may = false;
        }
        return ($may);
    }

    public
    static function mayCreateLst($docid)
    {
        //TODO:
        //Проверка на возможность добавления позиции в состав документа
        $rec = ri_compound::find($docid);

        return ($rec->docsigned == 0);
    }

    public static function sign(wrhdoc $doc, $userid)
    {
        //подписывание документа /проведение / пересчет остатков на складе
        //$doc = self::find($docid);
        Log::debug(" ri_compound::sign ");

        // если док-т существует
        if (isset($doc)) {

            // если док-т еще не утвержден
            if (($doc->docsigned ?? 0) <> 1) {

                //Если дата документа не попадает в заблокирванный период
                if (!ri_compound::isLocked($doc->id)) {

//                    if (!isset($doc->docnum))
//                        $doc->docnum = wrhdocnum::NxtDocNum($doc->doctypeid, $doc->ownorgid, $doc->docdate);

                    $doc->docsigned = 1;
                    $doc->signed_at = now();
                    $doc->signed_by = $userid;
                    $doc->save();
                    //Log::debug(" ri_compound::docsigned = $doc->docsigned ");

                    //пересчитаем остатки
                    //DB::unprepared('CALL recalc_stock()');

                    return true;

                } else {
                    Log::debug(" ri_compound::Not signed! Doc`s Date in locked period!");
                    return false;
                }

            }
        }
    }

    public static function on_sign($rec)
    {
        // Доп. действия при подписании/утверждении документа
        //dd($rec);
        //сформируем/обновим фин. операции ------
//        self::rfr_finopers($rec);

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function on_unsign($rec)
    {
        // Доп. действия при разутверждении документа

        //сформируем/обновим фин. операции ------
//        self::clr_finopers($rec);

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    static public function rfr_finopers($rec)
    {
        if (!isset($rec))
            return;

        $userid = \Auth::user()->id;

        //dd($rec, self::$sysobjid, $rec->id, $rec->doctype->need_org);
        if ($rec->doctype->need_org == 1) {
            //сформируем фин. операцию --------------------------------------------------------------
            obj_finoper::addOrUpdate(
                ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 1],
                ['sysobjid' => self::$sysobjid, 'objid' => $rec->id, 'mark' => 1
                    , 'operdate' => $rec->docdate
                    , 'opersum' => $rec->docsum
                    , 'qty' => 1
                    , 'price' => $rec->docsum
                    , 'descript' => 'Отпуск товара' // $rec->refitem->name . ', ' . $rec->refitem->unittype->name
                    , 'sumtypeid' => 2  //1-деньги, 2-товар
                    , 'srcorgid' => $rec->ownorgid
                    , 'tgtorgid' => $rec->orgid
//                , 'contractid' => $rec->contractid
//                , 'opertypeid' => $rec->mchn_raid->opertypeid
                    , 'updated_by' => $userid
                    , 'updated_at' => now()
                ]);
        }
        //удалим записи из obj_finopers, для которых уже нет соответствующих записей в mr_opers
        obj_finoper::from('obj_finopers as f')
            ->where('sysobjid', self::$sysobjid)
            ->whereRaw("not exists (select 1 from wrhdocs as d where d.id=f.objid)")
            ->delete();
    }

    static public function clr_finopers($rec)
    {
        if (!isset($rec))
            return;

        $userid = \Auth::user()->id;

        //удалим записи из obj_finopers, связанные с текущей записью
        obj_finoper::from('obj_finopers as f')
            ->where('sysobjid', self::$sysobjid)
            ->where('objid', $rec->id)
            ->delete();

        //удалим записи из obj_finopers, для которых уже нет соответствующих записей в mr_opers
        obj_finoper::from('obj_finopers as f')
            ->where('sysobjid', self::$sysobjid)
            ->whereRaw("not exists (select 1 from wrhdocs as d where d.id=f.objid)")
            ->delete();
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lock_before = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lock_before)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->docdate < $lock_before);
            }
        }
        return false;
    }


    public function admindelete()
    {
        $result = new Result;
        //Удаляем себя вместе с дочками
        try {
            DB::transaction(function () {
                $this->items()->delete();
                $this->files()->delete();  //TODO: ? ->deleteOne() ? Так как не удаляется файл с диска

                //удалим записи из obj_finopers, для которых уже нет соответствующих записей в wrhdocs
                obj_finoper::from('obj_finopers as f')
                    ->where('sysobjid', self::$sysobjid)
                    ->whereRaw("not exists (select 1 from wrhdocs as d where d.id=f.objid)")
                    ->delete();

                return parent::delete();
            });
        } catch (\Exception $e) {
            $result->err = 1;
            $result->msg = 'Ошибка удаления записи: ' . $e->getMessage();
        }
        return $result;
    }


    public static function cache_clear($rec)
    {
        //Забудем связанный кэш -------------------------------------
        if (isset($rec)) {
        }
//        Cache::forget('informer_saldos');
        //-----------------------------------------------------------
    }

    public static function clone($id)
    {
        // Клонируем указанную запись wrhdocs со всем содержимым

        $result = new Result;
        $userid = \Auth::user()->id;

        $wrhdoc = self::find($id);
        if (!isset($wrhdoc)) {
            $result->err = 1;
            $result->msg = 'Исходная запись не найдена!';
            return $result;
        }

        $new_wrhdoc = $wrhdoc->replicate();
        //дата записи не может быть ранее sysobj_lockdates.lock_before
        $new_wrhdoc->docdate = max($new_wrhdoc->docdate, sysobj_lockdate::mindate(self::$sysobjid));
        $new_wrhdoc->docnum = wrhdocnum::NxtDocNum($new_wrhdoc->doctypeid, $new_wrhdoc->ownorgid, $new_wrhdoc->docdate);;
        $new_wrhdoc->docsigned = 0;
        $new_wrhdoc->created_by = $userid;
        $new_wrhdoc->updated_by = $userid;
        $new_wrhdoc->save();
        self::on_update($new_wrhdoc);

        //перенесем операции
        $items = wrhdoclst::where('docid', $wrhdoc->id)->get();
        foreach ($items as $item) {
            $new_item = $item->replicate();
            $new_item->docid = $new_wrhdoc->id;
            $new_item->created_by = $userid;
            $new_item->updated_by = $userid;
            $new_item->save();
            wrhdoclst::on_update($new_item);
        }

        $result->obj = $new_wrhdoc->toArray();

        return $result;
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи


        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи


        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    static public function search_cond($params)
    {

        $userid = \Auth::user()->id;

        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];
        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'active') {
                        $sc .= " and ric.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (ric.active=1 or ric.id={$val})";

                    } elseif ($key == 'signed') {
                        $sc .= " and ric.docsigned={$val}";

                    } elseif ($key == 'ownorgid') {
                        $sc .= " and ric.ownorgid={$val}";

                    } elseif ($key == 'refitmid') {
                        $sc .= " and ric.refitmid={$val}";

                    } elseif ($key == 'name' or $key == 's_name') {
                        $search_flds = "concat(ri.name, ' ', ric.notes)";

                        $words = explode(" ", $val);
                        if (count($words) > 0) {
                            $sc .= ' and (';

                            //ищем "как ввел"
                            $sc .= ' (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            //попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
                            $words = explode(" ", StringUtil::switcher_ru($val));
                            $sc .= ' or (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            $sc .= ')';
                        }
                    } elseif ($key == 'in_regnum_srcs') {
                        //организация имеет источник рег. номеров
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from regnum_srcs as rns where rns.ownorgid=o.id)";

                    } elseif ($key == 'user_has_right_for_org') {
                        // пользователь должен иметь указанное право на объекты, связанные с организацией

                        $sc .= " and exists( select 1 FROM usrsysrights AS usr
                                    where
                                        usr.userid = {$userid} and usr.sysfuncid = {$val}
                                                and usr.active = 1
                                                and now() between usr.begdt and IFNULL(usr.enddt, now())
                                                and IFNULL(usr.limsysobjid, 111) = 111
                                                and IFNULL(usr.limobjid, o.id) = o.id
                                        )";

                    } elseif ($key == 'orgplnpays_ownorgid_in') {
                        //orgplnpays.ownorgid должен быть в переданном списке
                        $sc .= " and exists (select 1 from orgplnpays as pp
                                    join orgplnpay_items as pi on pi.docid=pp.id
                                    where pi.orgid=o.id and pp.ownorgid in ({$val})
                                    )";

                    } elseif ($key == 'buildobj_sup_not_dlvrd') {
                        //список поставщиков на заданный объект имеющих долг по доставке материалов
                        $sc .= " and exists (select 1 from invoices as inv
                                    join eritm_offers as ofr on ofr.invoiceid=inv.id
                                    join equiprqst_items as eri on eri.id=ofr.eritmid
                                    join equiprqsts as er on er.id=eri.rqstid and er.buildobjid={$val}
                                    where o.id=inv.orgid )";

                    } elseif ($key == 'buildobj_ownorg_not_dlvrd') {
                        //список поставщиков на заданный объект имеющих долг по доставке материалов
                        $sc .= " and exists (select 1 from invoices as inv
                                    join eritm_offers as ofr on ofr.invoiceid=inv.id and ofr.ord_qty>ifnull(ofr.dlvrd_qty,0)
                                    join equiprqst_items as eri on eri.id=ofr.eritmid
                                    join equiprqsts as er on er.id=eri.rqstid and er.buildobjid={$val}
                                    where o.id=inv.ownorgid )";

                    } elseif ($key == 'org_ownorg_not_dlvrd') {
                        //список поставщиков на заданный объект имеющих долг по доставке материалов
                        $sc .= " and exists (select 1 from invoices as inv
                                    join eritm_offers as ofr on ofr.invoiceid=inv.id and ofr.ord_qty>ifnull(ofr.dlvrd_qty,0)
                                    where o.id=inv.ownorgid and inv.orgid={$val} )";

                    } elseif ($key == 'inv_ownorgid_by_orgid') {
                        //организация является получателем/плательщиком в счетах для заданного поставщика
                        $sc .= " and exists (select 1 from invoices as inv
                                    where o.id=inv.ownorgid
                                    and inv.doctypeid=1 and inv.orgid={$val} )";

                    } elseif ($key == 'upd_orgid_by_ownorgid') {

                        $sc .= " and exists (select 1 from invoices as inv
                                    where o.id=inv.orgid
                                    and inv.doctypeid=2 and inv.ownorgid={$val}";

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

                    } elseif ($key == 'inv_orgid_by_ownorgid') {

                        $sc .= " and exists (select 1 from invoices as inv
                                    where o.id=inv.orgid
                                    and inv.doctypeid=1 and inv.ownorgid={$val}";

                        if (isset($params['inv_begdate']) and array_search('inv_begdate', $used_params) == 0) {
                            $tdate = $params['inv_begdate'];
                            $sc .= " and inv.docdate>='{$tdate}'";
                            $used_params[] = 'inv_begdate';
                        }
                        if (isset($params['inv_enddate']) and array_search('inv_enddate', $used_params) == 0) {
                            $sc .= " and inv.docdate<='" . $params['inv_enddate'] . "'";
                            $used_params[] = 'inv_enddate';
                        }
                        $sc .= ")";

                    } elseif ($key == 'upd_orgid') {
                        //организация-поставщик по УПД
                        $sc .= " and exists (select 1 from invoices as inv
                                    where inv.doctypeid=2 and inv.orgid={$val})";

                    } elseif ($key == 'upd_begdate') {
                        //
                        $sc .= " and exists (select 1 from invoices as inv
                                where inv.doctypeid=2 and o.id in(inv.ownorgid,inv.orgid)
                                    and inv.docdate>='" . $params['upd_begdate'] . "'";

                        if (isset($params['upd_enddate']) and array_search('upd_enddate', $used_params) == 0) {
                            $sc .= " and inv.docdate<='" . $params['upd_enddate'] . "'";
                            $used_params[] = 'upd_enddate';
                        }
                        $sc .= ")";

                    } elseif ($key == 'upd_enddate') {
                        $sc .= " and exists (select 1 from invoices as inv
                                where inv.doctypeid=2 and o.id in(inv.ownorgid,inv.orgid)
                                    and inv.docdate<='" . $params['upd_enddate'] . "'";

                        if (isset($params['upd_begdate']) and array_search('upd_begdate', $used_params) == 0) {
                            $sc .= " and inv.docdate>='" . $params['upd_begdate'] . "'";
                            $used_params[] = 'upd_begdate';
                        }
                        $sc .= ")";


                    } elseif ($key == 'with_stocks') {
                        //организация имеет товарный запас на складе
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from wrh_stocks as ws
                                    where ws.ownorgid=o.id and ws.qty>0)";

                    } elseif ($key == 'in_mchn_raids_ownorgid') {
                        //организация указана в  mchn_raids.ownorgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mchn_raids as mr where mr.load_ownorgid=o.id)";

                    } elseif ($key == 'in_mchn_raids_orgid') {
                        //организация указана в  mchn_raids.ownorgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mchn_raids as mr where mr.orgid=o.id)";

                    } elseif ($key == 'in_mr_opers_suporgid') {
                        //организация указана в mr_opers.suporgid (поставщик)
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mr_opers as mro where mro.suporgid=o.id)";

                    } elseif ($key == 'in_mr_opers_orgid') {
                        //организация указана в  mr_opers.orgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mr_opers as mro where mro.orgid=o.id)";

                    } elseif ($key == 'in_mr_opers_orgid_sale') {
                        //организация указана в  mr_opers.orgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mr_opers as mro where mro.orgid=o.id and mro.sale_dir=1)";

                    } elseif ($key == 'in_mr_opers') {
                        //организация указана в mr_opers.orgid или в mr_opers.sup_orgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mr_opers as mro where o.id in (mro.suporgid, mro.orgid))";

                    } elseif ($key == 'flagtypeid') {
                        //у организации должен быть нужный признак
                        $sc .= " and exists (select 1 from objflags as f
                                        where f.objid=o.id
                                        and f.sysobjid=111
                                        and f.flagtypeid={$val}
                                    )";

                    } elseif ($key == 'not_flagtypeid') {
                        //у организации не должно быть заданного признака
                        $sc .= " and not exists (select 1 from objflags as f
                                        where f.objid=o.id
                                        and f.sysobjid=111
                                        and f.flagtypeid={$val}
                                    )";

                    } elseif ($key == 'flagtypeid_or_id') {
                        //у организации должен быть нужный признак или id организации задан явно
                        $flagtypeid = $val[0] ?? null;
                        $sc .= " and ( exists (select 1 from objflags as f
                                        where f.objid=o.id
                                        and f.sysobjid=111
                                        and f.flagtypeid={$flagtypeid}
                                    )";

                        if (isset($val[1])) {
                            $orgid = $val[1] ?? null;
                            $sc .= " or o.id={$orgid}";
                        }
                        $sc .= ")";

                    } elseif ($key == 'in_paydocs_ownorgid') {
                        // использовалась в платежных документах в собственной компании
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from paydocs as pd where pd.ownorgid=o.id)";

                    } elseif ($key == 'in_paydocs_orgid') {
                        // использовалась в платежных документах в контрагенте
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from paydocs as pd where pd.orgid=o.id)";

                    } elseif ($key == 's_in_contract_type') {
                        // использовалась в платежных документах в контрагенте
                        $sc .= " and exists (select 1 from contract_orgs as co
                            join contracts as c on c.id=co.contractid and c.contracttypeid={$val}
                        where co.orgid=o.id)";

                    } elseif ($key == 's_addresstypeid') {
                        // Контрагент имеет адрес указанного типа
                        $sc .= " and exists (select 1 from obj_addresses as oa
                            where oa.sysobjid=111 and oa.objid=o.id and  oa.addresstypeid={$val})";

                    } elseif ($key == 'in_ri_sup_prices') {
                        //организация указана в ri_sup_prices.orgid
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from ri_sup_prices as rsp where rsp.orgid=o.id)";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }


    static public function lstFor($params)
    {
        //2021-02-25 SNS. универсальный конструктор массива с id, name организаций
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $userid = \Auth::user()->id;

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = self::from('orgs as o')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('o.name', 'desc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-07-10 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember('lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2023-05-21 SNS. универсальный конструктор коллекции из записей ri_compounds
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'o.*';
            //Log::info(json_encode($fields));
            //dd($sc,$fields);
            $sorts = $sorts ?? [['ri.name', 'asc']];

            $recs = self::from('ri_compounds as ric')
                ->join('refitems as ri', 'ri.id', 'ric.refitmid')
                ->whereRaw($sc)
                ->select($fields);

            foreach ($sorts as $sort) {
                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
            }

            $recs = $recs->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

}
