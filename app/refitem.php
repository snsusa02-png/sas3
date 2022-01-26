<?php

namespace App;

use App\Helpers\UserActivity;
use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use App\Traits\StringUtil;
use Auth;
use DB;
use Illuminate\Database\Eloquent\Model;

use App\unittype;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class refitem extends Model
{
    //
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'refitems';
    static public $sysobjid = 105;

    protected $table = 'refitems';
    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];


    public function brand_lnk()
    {
        return $this->hasOne(brand::class, 'id', 'brandid')
            ->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function parent()
    {
        return $this->hasOne(refitem::class, 'id', 'parent_id')->withDefault();
    }

    public function itmtype()
    {
        return $this->hasOne(itmtype::class, 'id', 'itmtypeid')->withDefault();
    }

    public function itmsubtype()
    {
        return $this->hasOne(ItmSubType::class, 'id', 'ItmSubTypeID')->withDefault();
    }

    public function unittype()
    {
        return $this->hasOne(unittype::class, 'id', 'unittypeid')->withDefault();
    }

    //связь с позициями заказа

    public function ri_specinfo_lst()
    {
        return $this->hasMany(ri_specinfo::class, 'refitmid', 'id')->with('specinfotype');
    }

    public function obj_names()
    {
        return $this->hasMany(obj_name::class, 'objid', 'id')
            ->where([
                'sysobjid' => self::$sysobjid,
            ]);
    }

    public function estprices()
    {//доступные оценки стоимости(цены) от поставщиков
        return $this->hasMany(ri_estprice::class, 'refitmid', 'id')
            ->with('suporg');
    }

    public function items()
    {//? не нужно?
        return $this->hasMany(orditem::class, 'refitmid', 'id');
    }

    public function images()
    {
        return $this->hasMany(ri_image::class, 'refitmid', 'id');
    }

    public function extids()
    {
        return $this->hasMany(objextid::class, 'objid', 'id')
            ->where('sysobjid', 105);
    }

    public function specinfos()
    {
        return $this->hasMany(ri_specinfo::class, 'refitmid', 'id');
    }

    public function altnames()
    {
        return $this->hasMany(ri_altname::class, 'refitmid', 'id');
    }

//    public function orgprices()
//    {
//        return $this->hasMany(ri_sup_price::class, 'refitmid', 'id');
//    }

    public function RI_specinfo($div = ';')
    {
        //Возвращает строку с перечнем специфических характеристик заданного товара,
        // $div - разделитель между характеристиками

        $info = null;
        if (!isset($div)) $div = ";";

        $lst = ri_specinfo::getSpecInfo4RefItm($this->id);
        foreach ($lst as $itm) {
            $info = $info . $div . $itm->stname . ': ' . $itm->specinfovalue;
        }
        if (isset($this->grossweight))
            $info = $info . $div . 'Вес брутто, кг: ' . $this->grossweight;

        if (isset($info)) $info = mb_substr($info, mb_strlen($div));
        return $info;
    }


    public static function getrefitemsByType0($itID)
    {
        $orgid = User::getOrgID(Auth::id());

        $lst = static::from('refitems as r')
            ->leftJoin('itmsubtypes as ist', 'ist.id', '=', 'r.ItmSubTypeID')
//            ->leftJoin('orgitmdiscounts as d', function ($j) use ($orgid) {
//                $j->on(DB::raw('IFNULL(d.itmtypeid, r.ItmTypeID)'), '=', 'r.ItmTypeID')
//                    ->on(DB::raw('IFNULL(d.refitmid, r.id)'), '=', 'r.id')
//                    ->where('d.orgid', '=', $orgid);
//            })
            ->leftJoin('brands as b', function ($j) {
                $j->on('b.code', '=', 'r.brand');
            })
            ->where('r.ItmTypeID', '=', $itID)
            ->where('r.active', '=', 1)
            ->where('r.salebegdate', '<=', today())
            ->where(DB::raw('IFNULL(r.saleenddate, CURDATE() )'), '>=', today())
            ->orderBy('ist_ordr', 'asc')
            ->orderBy('brand_ordr', 'asc')
            ->orderBy('r.id', 'asc')
            ->select([
                'r.id', 'r.name', 'r.retailprice', 'r.salebegdate', 'r.price',
                'r.brand', 'r.manufacturer', 'r.grossweight'
                , DB::raw('ifnull(r.photourl, b.photourl) as photourl')
                //, 'd.dscntpcnt'
                //, DB::raw('round(r.price*(100 - IFNULL(d.dscntpcnt,0))/100,2 ) as cost')
                , DB::raw('calc_refitmprice4org (' . $orgid . ',r.id) as cost')
                , DB::raw('ri_actionname (r.id) as actionname')
                , 'r.ItmSubTypeID', 'ist.name as  istname'
                , DB::raw('ifnull(ist.ordr,999) as ist_ordr')
                , DB::raw('ifnull(b.ordr,999) as brand_ordr')
            ]);
        //dd($lst->toSql());
        $lst = $lst->get();
        return $lst;
        //return DB::select($sql);
    }

    public static function getrefitemsByType($itID)
    {
        //20190508 SNS. Цену по скидке не вычисляем, а берем из ri_sup_prices
        $orgid = User::getOrgID(Auth::id());
//        session_start();
        if (isset($_SESSION['userorgid']))
            $orgid = $_SESSION['userorgid'];

        $lst = static::from('refitems as ri')
            ->leftJoin('ri_sup_prices as op', function ($j) use ($orgid) {
                $j->on('op.refitmid', '=', 'ri.id')
                    ->where('op.orgid', '=', $orgid);
            })
            ->leftJoin('itmsubtypes as ist', 'ist.id', '=', 'ri.ItmSubTypeID')
            ->leftJoin('brands as b', function ($j) {
                $j->on('b.code', '=', 'ri.brand');
            })
            ->where('ri.ItmTypeID', '=', $itID)
            ->where('ri.active', '=', 1)
            ->where('ri.salebegdate', '<=', today())
            ->where(DB::raw('IFNULL(ri.saleenddate, CURDATE() )'), '>=', today())
            ->whereNotNull('ri.price')
            ->where('ri.price', '>', 0);

        if (1 == 0) {
            $lst = $lst
                //->orderBy(DB::raw('ifnull(r.ordr,999999999)'), 'asc')  //20200505 SNS. сначала сортировка по приоритету
                ->orderBy('brand_ordr', 'asc')
                ->orderBy('ist_ordr', 'asc')
                ->orderBy('ri.id', 'asc');
        } else {
            $orderBy_set = collect([
                ['ordr' => 1, 'expr' => DB::raw('ifnull(b.ordr,999)'), 'dir' => 'asc'],
                ['ordr' => 2, 'expr' => DB::raw('ifnull(ist.ordr,999)'), 'dir' => 'asc'],
                ['ordr' => 3, 'expr' => DB::raw('ifnull(ri.ordr,999999999)'), 'dir' => 'asc'],
                ['ordr' => 0, 'expr' => 'ri.id', 'dir' => 'asc'],
                ['ordr' => 0, 'expr' => 'ri.name', 'dir' => 'asc'],
            ]);
            //отсеем элементы сортировки с ordr<1, упорядочим по ordr и сформируем критерий сортировки запроса
            foreach ($orderBy_set->where('ordr', '>', 0)->sortBy('ordr') as $ord) {
                $lst = $lst->orderBy($ord['expr'], $ord['dir'] ?? 'asc');
            }
        }

        $lst = $lst->select([
            'ri.id', 'ri.name', 'ri.partnumber', 'ri.retailprice', 'ri.salebegdate', 'ri.price',
            'ri.brand', 'ri.manufacturer', 'ri.grossweight', 'ri.descript'
            , DB::raw('ifnull(ri.photourl,b.photourl) as photourl')
            , DB::raw('IFNULL(ri_actionprice(ri.id), ifnull(op.price, ri.price)) as cost')
            , DB::raw('ri_actionname (ri.id) as actionname')
            , 'ri.ItmSubTypeID', 'ist.name as  istname'
            //, DB::raw('ifnull(ist.ordr,999) as ist_ordr')
            //, DB::raw('ifnull(b.ordr,999) as brand_ordr')
        ]);
        //dd($lst->toSql());
        $lst = $lst->get();
        return $lst;
        //return DB::select($sql);
    }

    static public function getShortrefitems($itmtypeid, $itmsubtypeid)
    {
        $sql = static::where('active', '=', DB::raw(1))
            ->where('itmtypeid', '=', $itmtypeid);
        if (!is_null($itmsubtypeid)) {
            $sql->where('itmsubtypeid', '=', $itmsubtypeid);
        }
        $data = $sql->orderBy('name')
            ->select("name", "id");
        return $data;
    }

    public static function getrefitemCategory($reitmid)
    {

        $rq = static::from('refitems as r')
            ->where('r.id', $reitmid)
            ->select('r.ItmTypeID as itmtypeid', 'r.ItmSubTypeID as itmsubtypeid')
            ->first();
        return $rq;
    }

    public static function RefItmByBarCode($barcode)
    {
        //20190622 osetsky. Возвращает элемент каталога по штрих-коду.
        return static::whereEanOrUpc($barcode, $barcode)
            ->first();

    }

    public static function RefItmIDByBarCode($barcode)
    {
        //20190622 osetsky. Возвращает ID элемента каталога по штрих-коду.
        $ri = static::RefItmByBarCode($barcode);
        if (!$ri) {
            return null;
        } else {
            return $ri->id;
        }

    }

    public static function rqListrefitemByCond($orgid,
                                               $itmtypeid,
                                               $itmsubtypeid,
                                               $search_itmname,
                                               $search_name,
                                               $search_brand,
                                               $s_active,
                                               $s_photostatus,
                                               $s_max_age,  //возраст товара в днях
                                               $s_lim_price = null,
                                               $s_wrhboxid = null
    )
    {
        $search_itmname = mb_ereg_replace("(^\s+)|(\s+$)/", "", $search_itmname);
        $search_name = mb_ereg_replace("(^\s+)|(\s+$)/", "", $search_name);
        $search_brand = mb_ereg_replace("(^\s+)|(\s+$)/", "", $search_brand);
        //Т.к. не работает привязка к пареметру
        //$rq->setBindings([$orgid]);
        // то пройдем путем проверки переменной и подстановке ее
        $orgid_low = "null";
        if (is_numeric($orgid)) {
            $orgid_low = $orgid;
        }
        $rq = refitem::
        select('ri.id as id', 'ri.code', 'ri.name as name', 'ri.unit', 'ri.partnumber'
            , DB::raw('(ri.active and case when IFNULL(ri.saleenddate, CURDATE()) >= CURDATE() then 1 else 0 end) as active')
            //, DB::raw('calc_refitmprice4org (' . $orgid_low . ',ri.id) as price')
            , 'ri.price as price'
            , 'ri.brand as brand'
            , 'ri.descript as descript'
            , 'ri.photourl'
            , 'ri.itmtypeid'
//            , 'it.name as itmtypename'
            , db::raw("it_path(ri.itmtypeid) as itmtypename")

            //, 'its.name as itmsubtypename'
            //, DB::raw('refitm_specinfo(ri.id, "; ") as specinfo')
            //, DB::raw('ifnull(b.ordr,999) as brand_ordr')
            , 'ri.ordr'
        )
            ->from('refitems as ri')
            ->join('itmtypes as it', 'it.id', '=', 'ri.itmtypeid');
        //->leftjoin('itmsubtypes as its', 'its.id', '=', 'ri.itmsubtypeid')
        //->leftjoin('brands as b', 'b.id', '=', 'ri.brandid');

        if (mb_strlen($search_name) > 0) {
            //$rq->whereraw('concat(ifnull(ri.code," ")," ", ri.name) like "%' . mb_strtoupper($search_name) . '%"');
        }
        if (strlen($search_name) > 0) {
            $search_str = $search_name;

            $search_str = mb_strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
            $search_str = preg_replace('| +|', ' ', $search_str);
            $find = explode(" ", $search_str);

            $sc = ' 1=1';
            if (count($find) > 0) {
                $sc .= ' and (1=1';
                foreach ($find as $f) {
                    $sc .= " and LCASE( CONCAT(ifnull(ri.code,' '), ' ', ri.name))";
                    $sc .= " like '%" . $f . "%'";
                }
                $sc .= ')';
            }
            $rq->whereraw($sc);
        }


//        if (mb_strlen($search_brand) > 0) {
//            $rq->where('ri.brandid', '=', $search_brand);
//        }

        if (mb_strlen($search_itmname) > 0) {
            $rq->where('it.name', 'like', '%' . mb_strtoupper($search_itmname) . '%');
        }

        if (isset($itmtypeid) && !empty($itmtypeid)) {
            $types = explode("|", $itmtypeid);
            if (isset($types[0]))
                $rq->where('ri.itmtypeid', $types[0]);
            if (isset($types[1]))
                $rq->where('ri.itmsubtypeid', $types[1]);
        }

        if (isset($s_active)) {
            if ($s_active == "1") {
                //доступно к продаже
                $rq = $rq
                    ->where('ri.active', 1)
                    ->whereRaw('CURDATE() between salebegdate and ifnull(saleenddate, CURDATE())');
            } elseif ($s_active == "-1") {
                //планируется снять с продажи
                $rq = $rq
                    ->whereRaw('saleenddate >= CURDATE()');
            } elseif ($s_active == "0") {
                //!!! Нет отличия между $s_active==0 и $s_active=""
                //поэтому важно задавать значение в кавычках

                //снято с продажи (недоступно для продажи)

                $rq = $rq
                    ->whereRaw('not (ri.active and CURDATE() between salebegdate and ifnull(saleenddate,CURDATE()))');
            }
        }

        if (isset($s_max_age) and $s_max_age <> '') {
            //возраст в днях
            $rq = $rq->whereRaw('(now()-ri.created_at)<=86400*' . $s_max_age);
        }

        if (isset($s_lim_price) and $s_lim_price <> '') {
            //Порядок цен
            //$rq = $rq->whereRaw('ri.price' . $s_lim_price);
        }
        if (isset($s_wrhboxid) and $s_wrhboxid > 0) {
            $rq = $rq->whereraw(" exists (select 1 from wrh_stocks as ws where ws.refitmid=ri.id and ws.qty>0 and ws.boxid={$s_wrhboxid})");
        }

        if (mb_strlen($s_photostatus) > 0) {
            if ($s_photostatus == 0)
                $rq = $rq->whereraw(' not exists (select 1 from ri_images img where img.refitmid=ri.id)');
            elseif ($s_photostatus == 1)
                $rq = $rq->whereraw('exists (select 1 from ri_images img where img.refitmid=ri.id)');
        }
        //dd($rq);
        return $rq;
    }

    public static function rqListrefitem4Auto0($orgid, $request)
    {
        $search_str = $request->q;
        $itID = $request->it;

        //Т.к. не работает привязка к пареметру
        //$rq->setBindings([$orgid]);
        // то пройдем путем проверки переменной и подстановке ее
        $orgid_low = "null";
        if (is_numeric($orgid)) {
            $orgid_low = $orgid;
        }

//        $find = strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
//        $find = strtolower(preg_replace('| +|', ' ', $search_str));
        $search_str = strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
        $search_str = preg_replace('| +|', ' ', $search_str);
//        dd($search_str);
        $find = explode(" ", $search_str);

        $search = " 1=1";
        if (count($find) > 0) {
            $search .= ' and ((1=1';
            foreach ($find as $f) {
                $search .= " and";
                $search .= " LCASE( CONCAT(' ',replace(ri.name,'-',' '), ' ', ri.code, ' ', t.name) )";
                $search .= " like '% " . $f . "%'";
            }
            $search .= ') or (2=2';
            foreach ($find as $f) {
                $search .= " and ";
                $search .= " LCASE( CONCAT(' ',replace(ri.name,'-',''), ' ', ri.code, ' ', t.name) )";
                $search .= " like '% " . $f . "%'";
            }
            $search .= ')';
            $search .= ' or exists (select 1 from ri_altnames as n';
            $search .= ' where n.refitmid=ri.id and ((1=1';
            foreach ($find as $f) {
                $search .= " and lcase(CONCAT(' ',replace(n.name,'-',' '))) like '% " . $f . "%'";
            }
            $search .= ') or (2=2';
            foreach ($find as $f) {
                $search .= " and lcase(CONCAT(' ',replace(n.name,'-',''))) like '% " . $f . "%'";
            }
            $search .= '))))';

        }
        if ($itID != NULL) {
            $search .= " and ri.ItmTypeID = " . $itID;
        }
        //dd($search);

        $rq = refitem::
        select("ri.id", "ri.code", "ri.name", "t.name as itname",
            DB::raw('calc_refitmprice4org (' . $orgid_low . ',ri.id) as price'))
            ->from('refitems as ri')
            ->join('itmtypes as t', 't.id', 'ri.ItmTypeID')
            ->where('ri.Active', 1)
            ->whereRaw('now() between salebegdate and ifnull(saleenddate,now())')
            ->whereRaw($search)
            ->orderBy('ri.name');
        return $rq;
    }

    public static function rqListrefitem4Auto1($orgid, $request)
    {
        $search_str = $request->q;
        $itID = $request->it;

        //Т.к. не работает привязка к пареметру
        //$rq->setBindings([$orgid]);
        // то пройдем путем проверки переменной и подстановке ее
        $orgid_low = "null";
        if (is_numeric($orgid)) {
            $orgid_low = $orgid;
        }

//        $find = strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
//        $find = strtolower(preg_replace('| +|', ' ', $search_str));
        $search_str = strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
        $search_str = preg_replace('| +|', ' ', $search_str);
//        dd($search_str);
        $find = explode(" ", $search_str);

        $search = " 1=1";
        if (count($find) > 0) {
            $search .= ' and ((1=1';
            foreach ($find as $f) {
                $search .= " and LCASE( CONCAT(";
                $search .= "  ' ',replace(ri.altname,'-',' ')";
                $search .= ", ' ', ri.code, ' ', t.name";
                $search .= ") ) like '% " . $f . "%'";
            }
            $search .= ') or (2=2';
            foreach ($find as $f) {
                $search .= " and LCASE( CONCAT(";
                $search .= "  ' ', replace(ri.altname,'-','')";
                $search .= ", ' ', ri.code, ' ', t.name";
                $search .= ") ) like '% " . $f . "%'";
            }
            $search .= '))';
        }
        if ($itID != NULL) {
            $search .= " and ri.ItmTypeID = " . $itID;
        }
        //dd($search);

        $rq = refitem::
        select("ri.id", "ri.code", "ri.name", "t.name as itname",
            'ut.name as unittypename',
            DB::raw('calc_refitmprice4org (' . $orgid_low . ',ri.id) as price'))
            ->from('refitems as ri')
            ->join('itmtypes as t', 't.id', 'ri.ItmTypeID')
            ->leftjoin('unittypes as ut', 'ut.id', 'ri.unittypeid')
            ->where('ri.Active', 1)
            ->whereRaw('now() between salebegdate and ifnull(saleenddate,now())')
            ->whereRaw($search)
            ->orderBy('ri.name');
        return $rq;
    }

    public static function rqListrefitem4Auto($orgid, $request)
    {
        $search_str = $request->q;
        $itID = $request->it;
        $s_isservice = $request->get("svc");
        $lim_refitmid = $request->get("lrid");
        $buildopertypeid = $request->get("bot");
        $lim_by_rv = $request->get("lim_rv");    //признак ограничения по ресурсной ведомости
        $nakl_only = $request->get("nakl");    //признак ограничения материалами с признаком "Накладые расходы"
        $wrhdocid = $request->get("wdid");    //ID складского документа


        //Т.к. не работает привязка к параметру
        //$rq->setBindings([$orgid]);
        // то пройдем путем проверки переменной и подстановке ее
        $orgid_low = "null";
        if (is_numeric($orgid)) {
            $orgid_low = $orgid;
        }

        $search_str = strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
        $search_str = preg_replace('| +|', ' ', $search_str);


        $sc = "1=1";
        $words = explode(" ", $search_str);
        if (count($words) > 0) {
            $srch_flds = "lcase(concat(ri.name,' ',ifnull(ri.code,' '),' ',ifnull(ri.searchname,' '),' ',ifnull(ri.altname,' ')))";
            $sc .= ' and (';
            $sc2 = '';  //для поиска по obj_names


            //ищем "как ввел пользователь"
            $sc .= ' (1=1';
            foreach ($words as $word) {
                $sc .= " and {$srch_flds} like '%{$word}%'";

                //для поиска по obj_names
                $sc2 .= " and n.name like '%{$word}%'";
            }
            $sc .= ')';
            if (isset($sc2))
                $sc .= " or exists(select 1 from obj_names as n where n.sysobjid=105 and n.objid=ri.id {$sc2})";

            $enc_search_str = StringUtil::switcher_ru($search_str);
            //еще попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
            if ($enc_search_str != $search_str) {
                $words = explode(" ", $enc_search_str);
                $sc .= ' or (1=1';
                $sc2 = '';
                foreach ($words as $word) {
                    $sc .= " and {$srch_flds} like '%{$word}%'";
                    //для поиска по obj_names
                    $sc2 .= " and n.name like '%{$word}%'";
                }
                $sc .= ')';
                if (isset($sc2))
                    $sc .= " or exists(select 1 from obj_names as n where n.sysobjid=105 and n.objid=ri.id {$sc2})";
            }
            $sc .= ')';
        }
        //dd($sc);

        //Поиск в пределах Категории товаров
        if ($itID != NULL) {
            $sc .= " and ri.ItmTypeID = " . $itID;
        }

        if (isset($lim_refitmid)) {
            //ограничительная позиция. Возбмем некоторые параметры из нее
            $lim_refitem = refitem::find($lim_refitmid);
            if (isset($lim_refitem)) {
                if (isset($lim_refitem->unittypeid))
                    $sc .= " and ri.unittypeid = " . $lim_refitem->unittypeid;
            }
        }

        //ограничены материалами с признаком "Накладные расходы"
        if ($nakl_only) {
            $sc .= " and ifnull(ri.bdgtacnttypeid,21) = 25";

        } else {

            if (isset($buildopertypeid)) {
                //проверим наличие РВ
                //Cache::forget('lim_refitems_' . $buildopertypeid);
                $lim_by_rv = Cache::remember('lim_refitems_' . $buildopertypeid, now()->addMinutes(5)
                    , function () use ($buildopertypeid) {
                        return (bot_ri_lim::where('buildopertypeid', $buildopertypeid)->count() > 0) ? 1 : 0;
                    });

                if ($lim_by_rv) {
                    if ($lim_by_rv == 1)
                        //берем только в пределах
                        $sc .= " and exists( select 1 from bot_ri_lims as brl where brl.refitmid=ri.id and brl.buildopertypeid=$buildopertypeid) ";
                    else
                        $sc .= " and exists( select 1 from bot_ri_lims as brl where brl.refitmid=ri.id and brl.buildopertypeid=$buildopertypeid) ";
                }
            }
        }
        //dd($buildopertypeid, $search);

        //Ограничение от документа склада ----------------------------------------------------
        if (isset($wrhdocid)) {
            $wrhdoc = wrhdoc::find($wrhdocid);

            if (isset($wrhdoc)) {

                //узнаем параметры документа
                $wrhdoctype = wrhdoctype::find($wrhdoc->doctypeid);

                if (isset($wrhdoctype)) {
                    if ($wrhdoctype->forstock == -1) {
                        //ограничены перечнем товаров в остатке склада/ячейки
                        $sc .= " and exists( select 1 from wrh_stocks as ws
                        where ws.ownorgid={$wrhdoc->ownorgid}
                            and ws.wrhid={$wrhdoc->wrhid}
                            and ws.boxid={$wrhdoc->boxid}
                            and ws.refitmid=ri.id)";
                        //dd($search);
                    }
                }
            }
        }
        //------------------------------------------------------------------------------------

        $rq = refitem::from('refitems as ri')
            ->leftjoin('itmtypes as t', 't.id', 'ri.itmtypeid')
            ->leftjoin('unittypes as ut', 'ut.id', 'ri.unittypeid')
            ->where('ri.active', 1)
            //->whereRaw('curdate() between salebegdate and ifnull(saleenddate, curdate())')
            ->whereRaw($sc)
            ->select("ri.id"
                , "ri.code"
                , "ri.name", "t.name as itname", "ri.photourl"
                , 'ri.unittypeid'

                //список допустимых ЕИ - для возможности формирования заявки в альтернативных ЕИ
//                , db::raw("(select group_concat( a.unittypeid SEPARATOR ';')
//from ( select unittypeid from refitems as ri1 where ri1.id=ri.id
//		union select unittypeid from ri_units as riu where riu.refitmid=ri.id
//        order by 1) as a
//) as lst_unittypeid")

                , DB::raw("ifnull(ri.bdgtacnttypeid,21) as bdgtacnttypeid")
                , DB::raw('ifnull(ut.name,ri.unit) as unittypename')
                , DB::raw('ifnull(ut.decimal_dgts,3) as decimal_dgts')
                //DB::raw('calc_refitmprice4org (' . $orgid_low . ',ri.id) as price'),
                //, db::raw("ifnull(ri.price,'н/з') as price")
                , 'ri.price'
            //DB::raw('refitm_specinfo (ri.id,"; ") as specinfo')
//            , DB::raw("(select ifnull(sum(rqst_qty),0) from equiprqst_items as eri
//                    join equiprqsts as er on er.id=eri.rqstid
//                    ) as max_qty"   )
            )
            ->orderby('itname')
            ->orderby('ri.name');

        $s_isservice = $request->get("svc");
        if (isset($s_isservice)) {
            $rq = $rq->where('t.isservice', $s_isservice);
        }

        $rq = $rq->orderBy('ri.name');
        //dd($rq);
        return $rq;
    }

    static public function rqShortInfo($orgid, $id)
    {
        $orgid_low = "null";
        if (is_numeric($orgid)) {
            $orgid_low = $orgid;
        }
        $rq = static::from('refitems as r')->where('id', $id)
            ->select('id', 'name', 'r.price', 'unit', 'unittypeid', 'descript'
            //, DB::raw('calc_refitmprice4org (' . $orgid_low . ',r.id) as price')
            );
        return $rq;
    }

    public static function getPrice4Org($orgid, $id)
    {
        $orgid_low = "null";
        if (is_numeric($orgid)) {
            $orgid_low = $orgid;
        }
        $req = static::from('refitems as r')->where('id', $id)->select(
            DB::raw('calc_refitmprice4org (' . $orgid_low . ',r.id) as price'))->first();

        $res = null;
        if (isset($req)) {
            $res = $req->price;
        }
        return $res;
    }

    public static function auxInfo($ItmID)
    {
        return [];
    }

    public static function newItems()
    {
        //Список товаров, потсупивших в продажк после предыдущего визита клиента в ЛК

        $orgid = Auth::user()->curorgid; //для правильной цены
        if (isset($orgid)) {

            $lastdate = Helpers\UserActivity::LastVisitedBefore(7);

            //Cache::forget('new_refitems_' . $orgid);
            $newitems = Cache::remember('new_refitems_' . $orgid, now()->addMinutes(55)
                , function () use ($orgid, $lastdate) {

                    return refitem::from('refitems as ri')
                        ->select('id', 'name', 'brand', 'photourl')
                        ->selectraw('refitm_typesubtypename(ri.id) as typesubtypename')
                        ->selectraw('refitm_specinfo(ri.id,"<br>") as specinfo')
                        ->selectraw('calc_refitmprice4org(' . $orgid . ', id) as price')
                        ->where('ri.active', 1)
                        ->whereNotNull('ri.price')
                        ->where('salebegdate', '>', date('Y-m-d', $lastdate))
                        /*->whereraw('photourl is not null')*/

                        ->orderby('photourl', 'desc')
                        ->selectraw('rand()*100 as t_ord')
                        ->orderby('t_ord')
                        /*->InRandomOrder()*/
                        ->limit(16)
                        ->get();
                });

        } else {
            $newitems = null;
        }
        return $newitems;
    }

    public static function getRiPrice4Org($ri_id, $orgid)
    {

        $Itm = refitem::getrefitemVals($ri_id);
        $Itm = $Itm[0];
//		$SpecInfoLst = ri_specinfo::getSpecInfo4RefItm($ri_id);

        $dscnts = DB::table("orgitmdiscounts as d")
            ->where("d.itmtypeid", $Itm->ItmTypeID)
            ->where("d.orgid", $orgid)
            ->where("d.active", 1)
            ->get();
//		$SpecInfoLst = $SpecInfoLst->groupBy('specinfotypeid')->toArray();
        if ($dscnts->count() == 0) return 0;

        foreach ($dscnts as $dscnt) {
            $dSpecInfo = \App\dscnt_speccond::getDscntSpecConds($dscnt->id);
            $find = 1;
            foreach ($dSpecInfo as $info) {
                $itmSpecInfo = DB::table("ri_specinfo")
                    ->where("refitmid", $ri_id)
                    ->where("specinfotypeid", $info->specinfotypeid);

                if ($info->operator == "=") {
                    $itmSpecInfo->where("specinfovalue", $info->strvalue);
                } else {
                    $itmSpecInfo->where("numvalue", $info->operator, $info->numvalue);
                }
//				dump($itmSpecInfo);
                $itmSpecInfo->get();
//				dump($itmSpecInfo->count());
                if ($itmSpecInfo->count() == 0) {
                    $find = 0;
                    break;
                }
            }
            if ($find == 1) break;
        }
        $dscntVal = $find == 1 ? $dscnt->dscntpcnt : 0;
//		dd($dscntVal);
        return ($dscntVal);
    }

    public static function getrefitemVals($ItmID)
    {
        $orgid = User::getOrgID(Auth::id());

        return static::from('refitems as r')
            ->leftJoin('itmtypes as st', 'st.id', '=', 'r.ItmTypeID')
            ->leftJoin('itmsubtypes as ist', 'ist.id', '=', 'r.ItmSubTypeID')
            ->leftJoin('brands as b', function ($j) {
                $j->on('b.code', '=', 'r.brand');
            })
            ->leftJoin('ri_sup_prices as op', function ($j) use ($orgid) {
                $j->on('op.refitmid', '=', 'r.id')
                    ->where('op.orgid', '=', $orgid);
            })
            ->where('r.id', '=', $ItmID)
            ->select(["r.id", "r.name as ItmName", "r.price", "r.ItmTypeID",
                    "r.manufacturer", "r.brand", "r.descript", "r.partnumber",
                    DB::raw('ifnull(r.photourl,b.photourl) as photourl'),
                    DB::raw('IFNULL(ri_actionprice(r.id), ifnull(op.price, r.price)) as cost'),
                    "st.name as ItName", "ist.name as IstName"]
            )->get();
    }

    public function admindelete()
    {
        $result = new Result;
        //Удаляем себя вместе с дочками
        try {
            DB::transaction(function () {
//                $this->orgprices()->delete();
//                $this->images()->delete();
                $this->extids()->delete();
                $this->specinfos()->delete();
                $this->altnames()->delete();
                return parent::delete();
            });
        } catch (\Exception $e) {
            $result->err = $e->errorInfo[0];
            $result->msg = 'Ошибка удаления записи: ' . $e->message;
        }
        return $result;
    }

    public static function newItemByExtID($extsysid, $itmextid, $itmdata)
    {
        $sysobjid = 105;
        //перепроверим - вдруг уже есть такая запись:
        $itmid = objextid::objid_by_extsysid_extid($extsysid, $sysobjid, $itmextid);
        if (!isset($itmid) and isset($itmextid)) {

//            \Log::debug(var_dump($itmdata));

            try {
                DB::beginTransaction();

                $userid = $itmdata['created_by'] ?? 0;
                $itmdata['code'] = $itmextid;     //временно. Нужно ориентироваться на то, что есть в objextids
                $itmdata['updated_by'] = $itmdata['updated_by'] ?? $userid;

                //Добавить товар
                $rec = new refitem($itmdata);
                $rec->save();
                $itmid = $rec->id;


                //Добавить идентификатор товара во внешней системе $extsysid
                $ext = new objextid([
                    "extsysid" => $extsysid,
                    "sysobjid" => $sysobjid,
                    "objid" => $itmid,
                    "extid" => $itmextid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $ext->save();

                DB::commit();

            } catch (\Exception $e) {
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
            } finally {
            }
        }
        return $itmid;
    }

    public static function newItem($itmdata)
    {
        $sysobjid = 105;
        //перепроверим по коду и названию - вдруг уже есть такая запись:
        $code = $itmdata['code'] ?? null;
        $name = $itmdata['name'] ?? null;
        $itmid = self::idByCode($code);
        if (!isset($itmid))
            $itmid = self::idByName($name);

        if (isset($itmid))
            return $itmid;

        //dd($itmdata);
        if (isset($name)) {

//            \Log::debug(var_dump($itmdata));

            try {
                DB::beginTransaction();

                $userid = $itmdata['created_by'] ?? 0;
                $itmdata['updated_by'] = $itmdata['updated_by'] ?? $userid;

                //Добавить товар
                $rec = new refitem($itmdata);
                if (isset($rec->unittypeid)) {
                    $rec->unit = unittype::nameByID($rec->unittypeid);
                }
                $rec->save();
                $itmid = $rec->id;


                //Добавить идентификатор товара во внешней системе $extsysid
//                if (isset($code)) {
//                    $ext = new objextid([
//                        "extsysid" => 11, //GRAND
//                        "sysobjid" => $sysobjid,
//                        "objid" => $itmid,
//                        "extid" => $code,
//                        "created_by" => $userid,
//                        "created_at" => now(),
//                        "updated_by" => $userid,
//                        "updated_at" => now()]);
//                    $ext->save();
//                }

                //добавим название в obj_names
                obj_name::addOrUpdate($sysobjid, $itmid, ['name' => $name]);

                DB::commit();

            } catch (\Exception $e) {
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
            } finally {
            }
        }
        return $itmid;
    }

    // this is a recommended way to declare event handlers
    public static function boot()
    {
        parent::boot();

        static::deleting(function ($refitem) { // before delete() method call this
//            $refitem->orgprices()->delete();
//            $refitem->images()->delete();
            foreach ($refitem->images as $image) {
                $image->delete();
            }
            // do the rest of the cleanup...
        });
    }


    public static function newItemByExtID000($extsysid, $itmextid, $itmname, $userid = null)
    {
        //перепроверим - вдруг уже есть такой товар/услуга:
        $refitmid = objextid::objid_by_extsysid_extid($extsysid, 105, $itmextid);
        if (!isset($refitmid)) {
            //$userid = \Auth::user()->id;

            try {
                DB::beginTransaction();

                //Добавить товар/услугу
                $rec = new refitem([
                    "name" => $itmname,
                    "active" => 1,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $rec->save();
                $refitmid = $rec->id;

                //Добавить идентификатор организации во внешней системе $extsysid
                $ext = new objextid([
                    "extsysid" => $extsysid,
                    "sysobjid" => 105,
                    "objid" => $refitmid,
                    "extid" => $itmextid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $ext->save();

                DB::commit();

            } catch (\Exception $e) {
                //DB::rollback();
                //$this->log->fatalerror($e->getMessage());
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
                //return null;
            } finally {
            }

        }
        return $refitmid;
    }

    public static function updCode()
    {
        $codes = DB::table('temp_newcodes')->get();
        $cnt = 0;
        foreach ($codes as $itm) {
            //dd($itm->code, $itm->newcode);
            //self::where('code', $itm->code)->update(['code', $itm->newcode]);
            $rec = self::where('code', $itm->code)->first();
            if (isset($rec)) {
                $cnt++;
                $rec->code = $itm->newcode;
                $rec->save();
            }
        }
        dd($cnt);

    }

    public static function category_search_variants($id)
    {
        //Cache::forget('cat_variations_itmtype_' . $id);
        $search_variants = Cache::remember('cat_variations_itmtype_' . $id, now()->addMinutes(55)
            , function () use ($id) {

                $variants = [];

                $lst0 = DB::table('refitems as ri')
                    ->select('manufacturer as id', 'manufacturer as name')
                    ->whereNotNull('ri.manufacturer')
                    ->where('ri.ItmTypeID', '=', $id)
                    ->where('ri.active', '=', 1)
                    ->whereraw('ifnull(ri.saleenddate, now()) >= now()')
                    ->distinct()
                    ->orderBy('manufacturer')
                    ->get()->toArray();
                if (count($lst0) > 0) {
                    $lst = [];
                    foreach ($lst0 as $titm)
                        $lst[] = ['id' => $titm->id, 'name' => $titm->name];
                    $variants[] = [
                        'type' => 'manufacturer',
                        'title' => 'Производитель',
                        'variants' => $lst
                    ];
                }

                $lst0 = DB::table('refitems as ri')
                    ->join('brands as b', 'b.id', 'ri.brandid')
                    ->select('brand as id', 'b.name', DB::raw('ifnull(b.ordr,9999)'))
                    ->whereNotNull('ri.brand')
                    ->where('ri.ItmTypeID', '=', $id)
                    ->where('ri.active', '=', 1)
                    ->whereraw('ifnull(ri.saleenddate, now()) >= now()')
                    ->distinct()
                    ->orderBy(DB::raw('ifnull(b.ordr,9999)'))
                    ->orderBy('b.name')
                    ->get()->toArray();
                if (count($lst0) > 0) {
                    $lst = [];
                    foreach ($lst0 as $titm)
                        $lst[] = ['id' => $titm->id, 'name' => $titm->name];
                    $variants[] = [
                        'type' => 'brand',
                        'title' => 'Брэнд',
                        'variants' => $lst
                    ];
                }

                $lst0 = DB::table('refitems as ri')
                    ->join('itmsubtypes as st', 'st.id', 'ri.itmsubtypeid')
                    ->select('st.id', 'st.name')
                    ->whereNotNull('ri.itmsubtypeid')
                    ->where('ri.ItmTypeID', '=', $id)
                    ->where('ri.active', '=', 1)
                    ->whereraw('ifnull(ri.saleenddate, now()) >= now()')
                    ->distinct()
                    ->orderBy('st.name')
                    ->get()->toArray();
                if (count($lst0) > 0) {
                    $lst = [];
                    foreach ($lst0 as $titm)
                        $lst[] = ['id' => $titm->id, 'name' => $titm->name];

                    $variants[] = [
                        'type' => 'itmsubtypeid',
                        'title' => 'Тип',
                        'variants' => $lst
                    ];
                }

                $lst0 = DB::table('ri_specinfo as si')
                    ->join('refitems as ri', 'ri.id', 'si.refitmid')
                    ->join('specinfotypes as sit', 'sit.id', 'si.specinfotypeid')
                    ->where('sit.active', 1)
                    ->where('sit.searchtype', 1)
                    ->select('sit.id', 'sit.name', 'si.specinfovalue as value', 'si.numvalue')
                    ->where('ri.ItmTypeID', '=', $id)
                    ->where('ri.active', '=', 1)
                    ->whereraw('ifnull(ri.saleenddate, now()) >= now()')
                    ->distinct()
                    ->orderBy('sit.name')
                    ->orderBy('sit.id')
                    ->orderBy('si.numvalue')
                    ->orderBy('si.specinfovalue')
                    ->get()->toArray();

                $lst = [];
                $curid = -1;
                $curname = '';
                foreach ($lst0 as $titm) {
                    if ($titm->id <> $curid) {
                        if (count($lst) > 0) {
                            $variants[] = [
//                                'type' => 'specinfo_' . $curid,
                                //'type' => 'specinfo[' . $curid . ']',
                                'type' => 'specinfo_' . $curid,
                                'title' => $curname,
                                'variants' => $lst
                            ];
                            $lst = [];
                        }
                        $curid = $titm->id;
                        $curname = $titm->name;
                    }
                    $lst[] = ['id' => $titm->value, 'name' => $titm->value];
                }
                if (count($lst) > 0) {
                    $variants[] = [
//                        'type' => 'specinfo[' . $curid . ']',
                        'type' => 'specinfo_' . $curid,
                        'title' => $curname,
                        'variants' => $lst
                    ];
                }
                return $variants;
            });

        // установим отметку выбора в вариантах поиска --------------------------------------------------------
        $sv_selected = session('pubcatalog_search_arr_' . $id);
        //dd($search_variants, $sv_selected);

        if (isset($sv_selected) and count($sv_selected) > 0)
            foreach ($search_variants as &$sv) {

                foreach ($sv_selected as $slct) {
                    foreach ($slct as $key => $selvalues) {

                        if ($key == $sv['type']) {
                            //что-то выбрано в этом разделе
                            foreach ($selvalues as $selectedval) {
                                //переберем все выбранные варианты, чтобы установить отметку о "выбранности"
                                foreach ($sv['variants'] as &$v) {
                                    if ($v['id'] == $selectedval) {
                                        $v['selected'] = 1;
                                        break;
                                    }
                                }
                            }
                            break;
                        }
                    }
                }
            }
        //$search_variants[0]['variants'][0]['selected'] = 1;
        //dd($search_variants, $sv_selected);

        return $search_variants;
    }

    public static function fill_ordr()
    {
        //Заполнение поля ordr в соответствии с результатами продаж за последние 6 месяцев
        // если товару еще не присвовоен рейтинг из ручного диапазона (1-100)
        DB::statement('call fill_refitems_ordr();');
    }

    public static function idByCode($code)
    {
        //поиск Id товара по Коду Товара
        if (isset($code))
            return self::where('code', $code)->first()->id ?? null;
        else
            return null;
    }

    public static function idByCodeAndUnitTypeId($code, $unittypeid)
    {
        //поиск Id товара по Коду Товара
        if (isset($code) and isset($unittypeid))
            return self::where(['code' => $code, 'unittypeid' => $unittypeid])->first()->id ?? null;
        else
            return null;
    }

    public static function idByName($name)
    {
        //поиск Id товара по Коду Товара
        if (isset($name))
            return self::from('refitems as ri')
                    ->where('name', $name)
                    ->orWhereRaw("exists (select 1 from obj_names as n where n.sysobjid=105 and n.objid=ri.id and n.name='$name')")
                    ->first()->id ?? null;
        else
            return null;

    }

    public static function join($from_id, $to_id)
    {
        if (isset($from_id) and isset($to_id)) {

            //(!) ЕИ товаров должны совпадать
            $from = self::find($from_id);
            $to = self::find($to_id);

            if (isset($from) and isset($to) and $from->unittypeid == $to->unittypeid) {

                $rels = systblrel::where(['srctbl' => 'refitems'])->get();

                try {
                    foreach ($rels as $rel) {

                        $sc = $rel->tgtfld . ' = ' . $from_id;
                        if (isset($rel->fltcond))
                            $sc .= ' and ' . $rel->fltcond;

                        $cnt = DB::table($rel->tgttbl)
                            ->whereraw($sc)
                            ->update([$rel->tgtfld => DB::raw($to_id)]);

                        if ($cnt > 0) {
                            $msg = "замена {$cnt} значений поля " . $rel->tgttbl . '.' . $rel->tgtfld
                                . ' с ' . $from_id . ' на ' . $to_id;
                            Log::debug($msg);
                            objlog::log_info(self::$sysobjid, $to_id, $msg, 5);
                            //dd($cmd, $cnt);
                        }

                    }

                    //название поглощаемой записи попробуем добавить в альтернативные названия оставляемой записи
                    //obj_name::addOrUpdate(self::$sysobjid, $to_id, ['name'=>$from->name]);
                    //obj_name::addOrUpdateIfNew(self::$sysobjid, $to_id, $from->name,[]);

                    //прошли все и без ошибки - попробуем удалить запись
                    refitem::where('id', $from_id)->delete();

                    $msg = "поглощенная запись (id={$from_id}) удалена";
                    Log::debug($msg);
                    objlog::log_info(self::$sysobjid, $to_id, $msg, 5);

                } catch (\Exception $e) {
                    DB::rollback();
                    Log::error('ошибка замены идентификаторов refitems: ' . $e->getMessage());
                }
                //dd($rels);
            }
        }
    }

    public static function usage_stat($refitmid)
    {
        $result = '';
        if (isset($refitmid) and $refitmid <> -1) {
            $rels = systblrel::where(['srctbl' => 'refitems', 'active' => 1])->get();
            foreach ($rels as $rel) {

                $sc = $rel->tgtfld . ' = ' . $refitmid;
                if (isset($rel->fltcond))
                    $sc .= ' and ' . $rel->fltcond;

                $cnt = DB::table($rel->tgttbl)
                    ->whereraw($sc)
                    ->count();

                if ($cnt > 0) {
                    $result .= "; " . $rel->tgttbl . ": {$cnt}";
                }
            }
            $result = ltrim($result, '; ');
        }
        return $result;
    }


    static public function search_cond($params)
    {
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
                        $sc .= " and ri.active={$val}";

                    } elseif ($key == 's_name' or $key == 'name' or $key == 'name_type') {
                        //$sc .= " and concat(ifnull(ri.code,' '),' ',ri.name) like '%{$val}%'";

                        $search_flds = "ri.name";
                        if ($key == 'name_type')
                            $search_flds = "concat(ri.name,' ',it.name)";

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


                    } elseif ($key == 'in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') .
                            " exists (select 1 from mchn_raids as mr where ri.id in (mr.load_refitmid, mr.unload_refitmid))";

                    } elseif ($key == 'suporgid') {

                        Log::info('params: ' . $params['load_placeid']);
                        //если задано место загрузки, то
                        if (isset($params['load_placeid']) and $params['load_placeid'] <> '') {

                            //цена должна быть актуальна на дату
                            if (isset($params['price_on_date']))
                                $on_date = "'{$params['price_on_date']}'";
                            else
                                $on_date = 'curdate()';

                            $sc .= " and exists (select 1 from ri_sup_prices as rop2 where rop2.refitmid=ri.id
                        and rop2.orgid={$val}
                        and rop2.active=1
                        and {$on_date} between rop2.begdate and ifnull(rop2.enddate,{$on_date}) )";
                        }

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-10-27 SNS. универсальный конструктор массива с id, name мест
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('refitems as ri')
                ->whereRaw($sc)
                //->select('ri.id', DB::raw("concat(ifnull(ri.code,' '),' ',ri.name) as tname"))
                ->select('ri.id', 'ri.name as tname')
                ->orderBy('tname', 'asc')
                ->get()->pluck('tname', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-10-27 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember(self::$prefix . '_lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-10-27 SNS. универсальный конструктор коллекции из записей refitems
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'ri.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['ri.name', 'asc']];

            $recs = self::from('refitems as ri')
                ->leftJoin('itmtypes as it', 'it.id', 'ri.itmtypeid')
                ->leftJoin('unittypes as ut', 'ut.id', 'ri.unittypeid');

            //if (strpos($fields, 'rop.price') > 0) {
            if (is_array($fields)
                and in_array('rop.price', $fields)
                and isset($s_params['suporgid'])
            ) {
                $suporgid = $s_params['suporgid'];
                $load_placeid = $s_params['load_placeid'];
                Log::info('load_placeid' . $load_placeid);

                if (1==1 or isset($load_placeid)) {

                    //цена должна быть актуальна на дату
                    if (isset($s_params['price_on_date']))
                        $on_date = "'{$s_params['price_on_date']}'";
                    else
                        $on_date = 'curdate()';

                    $sc2 = " orgid = {$suporgid}
                        and active = 1
                        and {$on_date} BETWEEN begdate and ifnull(enddate, {$on_date})";
                    if (isset($load_placeid))
                        $sc2 .= " and placeid={$load_placeid}";
                    Log::info('****** ' . $sc2);

                    $recs = $recs->join(DB::raw("(select refitmid, price  from ri_sup_prices  where {$sc2} ) as rop"),
                        function ($join) {
                            $join->on('rop.refitmid', '=', 'ri.id');
                        });
                }
            }

            $recs = $recs->whereRaw($sc)
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
