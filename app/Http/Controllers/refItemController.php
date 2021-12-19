<?php

namespace App\Http\Controllers;

use App\bot_ri_lim;
use App\cwp_work_equip;
use App\equiprqst_item;
use App\eritm_offer;
use App\objextid;
use App\orditem;
use App\org_place;
use App\ri_detail;
use App\ri_estprice;
use App\ri_itmtype;
use App\ri_qtymarkup;
use App\ri_specinfo;
use App\ri_unit;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\User;
use Auth;
use DB;
use Illuminate\Support\Facades\Log;
use Modules\Stock\Entities\wrh;
use Modules\Stock\Entities\wrhdoc;
use Modules\Stock\Entities\wrhdoclst;
use PhpParser\Node\Expr\Array_;
use Response;
use Cache;
use Illuminate\Http\Request;

use App\itmtype;
use App\brand;
use App\objflag;
use App\objfile;
use App\objlog;
use App\refitem;
use App\ri_altname;
use App\sa_item;
use App\unittype;
use App\usrsysright;
use App\ri_image;
use App\objtag;

use App\Http\Middleware\IStock;
use Illuminate\Support\Facades\Validator;
use phpDocumentor\Reflection\Types\Integer;

use App\Traits\DeleteFileTrait;


class refItemController extends Controller
{
    use DeleteFileTrait;
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
//    public function __construct(IStock $stock)
    {
//        $this->stock = $stock;
        $this->middleware('auth');
        $this->sysobjid = 105;
        $this->sysobjcode = 'refitems';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

        $this->param_names = [
            's_pageitmcnt' => 10
            , 's_name' => ''
            , 's_photostatus' => ''
            , 's_itmtypeid' => ''
            , 's_active' => ''
        ];

    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['ri_units.create'] = false;
        $usrrights['ri_options.create'] = false;
        $usrrights['ri_specinfo.read'] = false;
        $usrrights['ri_specinfo.create'] = false;
        $usrrights['ri_details.read'] = false;
        $usrrights['ri_details.create'] = false;
        $usrrights['ri_qtymarkups.read'] = false;
        $usrrights['ri_qtymarkups.create'] = false;
        $usrrights['objextids.read'] = false;
        $usrrights['objextids.create'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');

            $usrrights['ri_options.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'ri_options.create');
            $usrrights['ri_units.create'] = $usrrights['create'];

            $usrrights['ri_details.read'] = $usrrights['read'];
            $usrrights['ri_details.create'] = $usrrights['save'];//свяжем с сохранением, а не с созданием refitems

            if (sysobj::isActiveByCode_cache('objextids')) {
                $usrrights['objextids.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'objextids.read');
                $usrrights['objextids.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'objextids.create');
            }
            if (sysobj::isActiveByCode_cache('ri_qtymarkups')) {
                $usrrights['ri_qtymarkups.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'ri_qtymarkups.read');
                $usrrights['ri_qtymarkups.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'ri_qtymarkups.create');
            }
            if (sysobj::isActiveByCode_cache('specinfotypes')) {
//                $usrrights['ri_specinfo.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'ri_specinfo.read');
//                $usrrights['ri_specinfo.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'ri_specinfo.create');
                //наследуем права от refitems
                $usrrights['ri_specinfo.read'] = $usrrights['read'];
                $usrrights['ri_specinfo.create'] = $usrrights['save']; //свяжем с сохранением, а не с созданием refitems
            }
        }
        //dd($usrrights);

        return $usrrights;
    }


    public function index(Request $request)
    {
        $userid = \Auth::user()->id;

        session(['pageno' => $request->page]);

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.save'),
        );
        if (!$usrrights['read']) {
            return view('home');
        }


        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_name' => ''
            , 's_photostatus' => ''
            , 's_itmtypeid' => ''
            , 's_active' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    //поиск по названию товара

                    $search_str = $val;

                    $search_str = mb_strtolower(preg_replace('[!|-|/| +]', ' ', $search_str));
                    $search_str = preg_replace('| +|', ' ', $search_str);
                    $find = explode(" ", $search_str);

                    $sc = ' 1=1';
                    $sc2 = ' ';     //для поиска по obj_names

                    if (count($find) > 0) {
                        $sc .= ' and ( (1=1';
                        foreach ($find as $f) {
                            $sc .= " and LCASE( CONCAT(ifnull(ri.code,' '), ' ', ri.name))";
                            $sc .= " like '%" . $f . "%'";

                            //для поиска по obj_names
                            $sc2 .= " and n.name like '%" . $f . "%'";
                        }
                        $sc .= ')';
                        $sc2 = ' or exists(select 1 from obj_names as n where n.sysobjid=105 and n.objid=ri.id' . $sc2 . ')';

                        $sc = $sc . $sc2 . ')';

                    }

                } elseif ($item == 's_regnum') {
                    $sc = $sc . " and m.regnum like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_itmtypeid') {
                    if ($val == '0')
                        $sc .= ' and ri.itmtypeid is null';
                    else
                        $sc .= ' and ri.itmtypeid="' . $val . '"';

                } elseif ($item == 's_active') {
                    //поиск по статусу доступности товара для продажи
                    if ($val == "1") {
                        //доступно к продаже
                        $sc = $sc . ' and ri.Active=1 and CURDATE() between salebegdate and ifnull(saleenddate, CURDATE())';
                    } elseif ($val == "-1") {
                        //планируется снять с продажи
                        $sc = $sc . ' and saleenddate >= CURDATE()';
                    } elseif ($val == "0") {
                        //снято с продажи (недоступно для продажи)

                        //!!! Нет отличия между $s_active==0 и $s_active=""
                        //поэтому важно задавать значение в кавычках
                        $sc = $sc . ' and not (ri.active and CURDATE() between salebegdate and ifnull(saleenddate,CURDATE()))';
                    }

                } elseif ($item == 's_photostatus') {
                    //поиск по статусу наличия фото товара
                    if ($val == 0)
                        $sc = $sc . ' and not exists (select 1 from ri_images img where img.refitmid=ri.id)';
                    elseif ($val == 1)
                        $sc = $sc . ' and exists (select 1 from ri_images img where img.refitmid=ri.id)';
                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------

        $sort_params = session('sort_params');
        if (isset($sort_params)) {
            $sort_by = $sort_params['field'];
            $sort_dir = $sort_params['dir'];
        } else {
            $sort_by = 'ri.name';
            $sort_dir = 'asc';
        }

        $parent_id = null;

        //Список товаров
        $recs = refitem::from('refitems as ri')
            ->leftJoin('unittypes as ut', 'ut.id', 'ri.unittypeid')
            //->leftJoin('itmtypes as it', 'it.id', 'ri.itmtypeid');
            ->leftJoin('v_itmtypes as it', 'it.id', 'ri.itmtypeid');

        $recs = $recs->select('ri.*'
            , 'it.name as itmtypename'
            , 'it.name_path as it_name_path'
            , 'ut.name as unittypename'
//            , db::raw("IT_PATH(it.id) AS it_name_path")
//            , db::raw("IT_ORDR(it.id) AS ordr_path")

        )
            ->whereNull('ri.parent_id')
            ->whereRaw($sc)
            ->orderBy('ordr_path', 'asc')
            ->orderBy($sort_by, $sort_dir);

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 10);


        //варианты для поиска по статусу фото
        $s_photostatuses = array('' => '-все-'
        , '0' => 'без фото'
        , '1' => 'с фото'
        );

        //варианты для поиска

        //$itmtypes = itmtype::from('itmtypes as it')
        //->where('active', 1)->select(db::raw("IT_PATH(it.id) as name"), 'id')
        //->orderByRaw("IT_ORDR(it.id)")
        //->get()->pluck("name", "id")->prepend("- без категории -", "0");

        $itmtypes = itmtype::from('v_itmtypes as it')
            ->where('active', 1)->select('name_path as name', 'id')
            ->orderBy('ordr_path')
            ->get()->pluck("name", "id")->prepend("- без категории -", "0");
        //$itmtypes[0] = ' ';
        //dd($itmtypes);

        $data = new \stdClass();

        $data->sysobj = sysobj::find($this->sysobjid);

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;


        return view($this->objcode . '.index', compact('recs', 'usrrights', 'data'
            , 'search_params', 's_photostatuses', 'itmtypes'));
    }


    //Поиск
    public function search_low(Request $request, $orgid, $blade_name, $skip_null)
    {
        $usrrights = $this->setInterfaceRight(-1);


        $search_name = "";
        $s_photostatus = "";
        $search_type = "";
        $search_brand = "";
        $search_brandid = "";
        $s_itmtypeid = "";
        $s_isservice = $request->get("svc");
        $s_active = "";
        $s_wrhboxid = "";

        if ($request->isMethod('post')) {

            //снесем концевые пробелы
            $search_name = mb_ereg_replace("(^\s+)|(\s+$)/", "",
                $request->get("search_name"));
            $search_type = mb_ereg_replace("(^\s+)|(\s+$)/", "",
                $request->get("search_type"));
            $search_brand = mb_ereg_replace("(^\s+)|(\s+$)/", "",
                $request->get("search_brand"));
            $search_brandid = mb_ereg_replace("(^\s+)|(\s+$)/", "",
                $request->get("search_brandid"));
            $s_itmtypeid = $request->get('s_itmtypeid');
            $s_active = $request->get('s_active');
            $s_photostatus = $request->get('s_photostatus');
            $s_wrhboxid = $request->get('boxid');

            //сохраним параметры поиска в сессии
            session(['search_setname' => "refitems"]);
            session(['search_params' => [
                'search_name' => $search_name,
                's_photostatus' => $s_photostatus,
                'search_type' => $search_type,
                'search_brand' => $search_brand,
                'search_brandid' => $search_brandid,
                's_itmtypeid' => $s_itmtypeid,
                's_active' => $s_active,
                's_wrhboxid' => $s_wrhboxid,
            ]]);

        } else {
            if (session('search_setname') == "refitems") {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $search_params = session('search_params');
                    $search_name = $search_params['search_name'] ?? '';
                    $s_photostatus = $search_params['s_photostatus'];
                    $search_type = $search_params['search_type'] ?? '';
                    $search_brand = $search_params['search_brand'] ?? '';
                    $search_brandid = $search_params['search_brandid'] ?? '';
                    $s_itmtypeid = $search_params['s_itmtypeid'];
                    $s_active = $search_params['s_active'];
                    $s_wrhboxid = $search_params['s_wrhboxid'] ?? '';
                }
            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);

                $s_wrhboxid = $request->get('boxid');
            }
        }

        //20190412 SNS. только те типы, которые есть в прайслисте
        $sc = "exists( select 1 from refitems as ri where ri.itmtypeid=it.id and ri.active=1)";
        if (isset($s_isservice) and $s_isservice <> '*') {
            $sc .= " and isservice={$s_isservice}";
        }

//        $itmtypes = itmtype::from('v_itmtypes as it')
//            ->select('name_path as tname', 'id')
//            ->whereRaw($sc)
//            //->orderBy('ordr')
//            ->orderBy('ordr_path')
//            ->orderBy('tname')
//            ->get()->pluck("tname", "id");
        $itmtypes = [];

        $data = new \stdClass();
        //иерархический список категорий материалов
        $data->categories = itmtype::getCategories();

        $data->s_itmtype_name = (isset($s_itmtypeid)) ? itmtype::it_name($s_itmtypeid) : '';

        //        $brands = brand::lst_RI_Brands_cache();
        $brands = [];

        //Если параметры поиска не заданы, то уйдем на index
        if ($skip_null and strlen($s_itmtypeid . $search_name
                . $search_type . $search_brandid . $s_active) == 0) return redirect()->route('refitems.index');

        $items = refitem::rqListRefitemByCond($orgid, $s_itmtypeid, null
            , null
            , $search_name
            , $search_brandid, $s_active
            , $s_photostatus
            , null //возраст товара в днях
            , null //
            , $s_wrhboxid
        );
        if (isset($s_isservice)) {
            $items = $items
                ->join('itmtypes as t', 't.id', '=', 'ri.itmtypeid')
                ->where('t.isservice', $s_isservice);
        }
        $items = $items->orderBy('ri.name', 'asc')
            ->paginate(10);

        $s_photostatuses = array('' => '-все-'
        , '0' => 'без фото'
        , '1' => 'с фото'
        );

        //номер первой записи на странице:
        $rec0 = $items->currentPage() * $items->perPage() - $items->perPage() + 1;
        return view($blade_name, compact('items', 'data', 'rec0', 'itmtypes'
            , 'search_name', 's_photostatuses', 's_photostatus', 'search_brand', 'search_brandid', 's_itmtypeid'
            , 's_active', 's_wrhboxid', 'orgid', 'brands', 'usrrights'));
    }

    public function list(Request $request)
    {
        //dd($request);

        //Организацию определим по переданному id организации
        //$orgid = $request->get("orgid");
        $orgid = \Auth::user()->curorgid;

        return $this->search_low($request, $orgid, 'refitems.list', false);
    }

    public function listgoods(Request $request)
    {
        //Организацию определим по переданному id организации
        //$orgid = $request->get("orgid");

        $orgid = \Auth::user()->curorgid;
        return $this->search_low($request, $orgid, 'refitems.list', false);
    }

    public function search(Request $request)
    {
        //Организацию определим по пользователю
        $orgid = \Auth::user()->curorgid;
        return $this->search_low($request, $orgid, 'refitems.index', true);
    }

    //получение информации о товаре (только id, имя и цена)
    public function getshortinfo(Request $request)
    {
        $id = $request->get('id');
        $orgid = $request->get('orgid');
        $ref = refitem::rqShortInfo($orgid, $id)->first();

        return (response()->json($ref->toArray()));

//        $result = array('id' => null, 'name' => null, 'price' => 0);
//        if (!is_null($ref)) {
//            $result['id'] = $ref->id;
//            $result['name'] = $ref->name;
//            $result['price'] = $ref->price;
//        }
//        return response()->json($result);
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request)
    {
        return $this->edit($request, -1);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
//        if ($id < 1) {
//            //защитимся от недопустимых id
        $pageno = session('pageno');
//            return redirect('/refitems/?page=' . $pageno)
//                ->with('warning', "Указанная запись не найдена!");
//        }
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight($id);

        if (!$usrrights['read']) {
            return view('home');
        }

        if ($id != -1) {
            $rec = refitem::find($id);
            if (!isset($rec)) {
                $pageno = session('pageno');
                return redirect('/refitems/?page=' . $pageno)
                    ->with('warning', "Указанная запись не найдена!");
            }
            $auxinfo = refitem::AuxInfo($id);

            //TODO: ? сделать разные типы тэгов, чтобы отличать helpTags от pageTags?
            $tags = 'refitems.edit,' . objtag::lstTags($this->sysobjid, $id);
//            $tags = objtag::lstTags($this->sysobjid, $id).',refitems.edit';
            $rec->helptags = $tags;

        } else {

            //----------------------------------------------------------------
            //восстановим параметры поиска из сессии, чтобы использовать что-нибудь для новой записи
            $search_params = $this->search_params($request, $this->param_names);
            //----------------------------------------------------------------

            $rec = new refitem([
                'id' => -1,
                'producttypeid' => 2,
                'active' => 1,
                'bdgtacnttypeid' => 21,
                'itmtypeid' => $search_params['s_itmtypeid'],
                'name' => $search_params['s_name'],
            ]);
            //$rec->id = -1;
            $auxinfo = [];

            $tags = objtag::lstTags($this->sysobjid, null);
            $tags = $tags . ';refitems.create';
            $rec->helptags = $tags;
        }

        if (isset($rec)) {

            if (1 == 1) {
                //Категории к которым принадлежит данная товарная позиция
                $ri_itmtypes = ri_itmtype::from('ri_itmtypes as rt')
                    ->join('itmtypes as it', 'it.id', 'rt.itmtypeid')
                    ->where('rt.refitmid', $rec->id)
                    ->select('rt.id', 'it.name as name', 'rt.active')
                    ->get()
//                    ->pluck('active', 'name', 'id');
                    ->toArray();
            }
//dd($ri_itmtypes);

            Cache::forget('itmtypes');
            if (1 == 0) {
                $rec->itmtypes = Cache::remember('itmtypes', 150, function () {
                    return itmtype::from('itmtypes as it')
                        ->where('it.active', 1)
                        ->selectraw("it.id, it.name, it.ordr")
                        ->orderby('ordr')->orderby('name')
                        ->pluck("name", "id");
                });
            } else {
                $itmtypes = Cache::remember('itmtypes', 150, function () {
                    //отберем сочетание активных типов(категорий) и подтипов
                    $first = itmtype::from('itmtypes as it')
                        ->join('itmtypes as st', 'st.parent_id', '=', 'it.id')
                        ->where('it.active', 1)
                        ->where('st.active', 1)
                        ->selectraw("st.id , concat(it.name, ' / ',st.name) name, it.ordr");
                    //дополним чистыми типами(категориями)
                    return itmtype::from('itmtypes as it')
                        ->where('it.active', 1)
                        ->selectraw("it.id, concat(it.name, '') name, it.ordr")
                        ->unionAll($first)
                        //->orderby('ordr')
                        ->orderby('name')
                        ->pluck("name", "id")->toArray();
                });
                asort($itmtypes);
                $rec->itmtypes = $itmtypes;
            }

            $rec->categories = itmtype::getCategories();
            //dd($rec->categories);

            $rec->producttypes = [
                1 => 'услуга',
                2 => 'материал',
                3 => 'оборудование',
            ];
            $rec->producttypename = $rec->producttypes[$rec->producttypeid] ?? null;

            $unittypeid = $rec->unittypeid;
            if ($rec->id <> -1) {
                //запретим изменять ЕИ если товар уже где-то использован

                $cnt = ri_unit::where('refitmid', $rec->id)->count();
                if ($cnt == 0)
                    $cnt = bot_ri_lim::where('refitmid', $rec->id)->count();
                if ($cnt == 0)
                    $cnt = equiprqst_item::where('refitmid', $rec->id)->count();
                if ($cnt == 0)
                    $cnt = eritm_offer::where('refitmid', $rec->id)->count();
                if ($cnt == 0)
                    $cnt = cwp_work_equip::where('refitmid', $rec->id)->count();

                if ($cnt == 0)
                    $unittypes = unittype::unittypes_cache();
                else
                    $unittypes = null;
            } else
                $unittypes = unittype::unittypes_cache();

            $rec->specinfos = null;
            if ($usrrights['ri_specinfo.read'] and $rec->has_child == 0) {
                $rec->specinfos = ri_specinfo::from("ri_specinfo as si")
                    ->join("specinfotypes as st", "st.id", "=", "si.specinfotypeid")
                    ->select("st.name as spectypename", "si.id", "si.specinfovalue")
                    ->where("si.refitmid", $id)
                    ->get();
            }
//dd($rec->specinfos);
            $rec->options = refitem::from('refitems as riopt')
                ->where('parent_id', $rec->id)
                ->select('riopt.id', 'riopt.name', 'riopt.unit', 'riopt.unittypeid', 'riopt.price'
                    , DB::raw('riopt.active * case when now() between riopt.salebegdate and ifnull(riopt.saleenddate,now())
                    then 1 else 0 end as active')
                )
                ->orderby('riopt.name')
                ->orderby('riopt.price')
                ->get();

            if ($usrrights['objextids.read'])
                $rec->extids = objextid::from('objextids as ei')
                    ->join('extsystems as s', 's.id', 'ei.extsysid')
                    ->where('sysobjid', $this->sysobjid)
                    ->where('objid', $rec->id)
                    ->select('ei.id', 's.name as extsysname', 'extid')
                    ->orderby('s.name')
                    ->get();
            else
                $rec->extids = null;
            //dd($rec->extids);

            $rec->brands = null;
//            $rec->brands = brand::lst_Brands_cache();

            $rec->details = null;
            if (1 == 0 and $usrrights['ri_details.read'] and $rec->has_child == 0) {
                $rec->details = ri_detail::ri_details($id);
            }

            $rec->qtymarkups = null;
            if (1 == 0 and $usrrights['ri_qtymarkups.read'] and $rec->has_child == 0) {
                $rec->qtymarkups = ri_qtymarkup::ri_qtymarkups($id);
            }

            //$rec->bot_ri_lims = bot_ri_lim::where('refitmid', $rec->id)->get();
            //dd($rec->bot_ri_lims);

            $rec->usage_stat = refitem::usage_stat($rec->id);
            //dd($rec->usage_stat);

            $ri_stocks = null;
            $ri_stock_docs = null;
            $ri_stocks_view = null;
            $ri_stockdocs_view = null;
            $stock_On = false;
            //Только для товаров
//            if (1 == 1 and $rec->itmtype->isservice == 0

            if (1 == 0 and $rec->isservice == 0
                and usrsysright::isUserHasRightByCode($userid, 'stock')) {

                $stock_On = $this->stock->active();
                $ri_stocks = $this->stock->ri_stocks($id);
                $ri_stocks_view = $this->stock->ri_stocks_view();

                $ri_stock_docs = $this->stock->ri_stock_docs($id);
                $ri_stockdocs_view = $this->stock->ri_stockdocs_view();
            }

            $ri_altnames = null;
            if (1 == 0 and usrsysright::isUserHasRightByCode($userid, 'ri_altnames.read'))
                $ri_altnames = ri_altname::select('id', 'name', 'translittypeid')
                    ->where('refitmid', $id)
                    ->orderby('name')
                    ->get();

            $saleactions = null;
            if (1 == 0) {
                $saleactions = sa_item::from('sa_items as i')
                    ->join('saleactions as sa', 'sa.id', '=', 'i.sa_id')
                    ->where('i.refitmid', $id)
                    ->select('sa.id', 'sa.name', 'sa.begdt', 'sa.enddt', 'i.price')
                    ->orderby('sa.name')
                    ->get();
            }

//            $rec->images = objfile::from('objfiles as f')
//                ->join('sysfiletypes as ft', 'ft.id', '=', 'f.sysfiletype_id')
//                ->where('sysobjid', 105)/*Refitems*/
//                ->where('sysfiletype_id', 4)/*Images*/
//                ->where('objid', $id)
//                ->select('f.id', 'ft.catalog', 'f.publicfilename', 'f.systemfilename')
//                ->get();

            $rec->units = ri_unit::from('ri_units as u')
                ->join('unittypes as ut', 'ut.id', 'u.unittypeid')
                ->where('u.refitmid', $rec->id)
                ->select('u.id', 'ut.name as unittypename', 'u.k2ref_unit')
                ->get();


            $rec->estprices = $rec->estprices()
                ->whereRaw("curdate() <= ifnull(enddate,curdate())")
                ->select('ri_estprices.*'
                    , db::raw("case when (begdate<=curdate()) then 1 else 2 end as act_status")
                    , db::raw("greatest(0,DATEDIFF(begdate, curdate())) as days2beg")
                    , db::raw("supwrkdays + greatest(0,DATEDIFF(begdate, curdate())) as actsupwrkdays")
                )
                ->orderBy('act_status')
                ->orderBy('price')
                ->with('suporg')->get();

            $rec->offers = equiprqst_item::from('equiprqst_items as eri')
                ->join('eritm_offers as ofr', 'ofr.eritmid', 'eri.id')
                ->join('orgs as o', 'o.id', 'ofr.suporgid')
                ->leftjoin('invoices as inv', 'inv.id', 'ofr.invoiceid')
                ->where('eri.refitmid', $rec->id)
                ->select('ofr.id', 'ofr.plngetdate', 'ofr.ord_qty', 'ofr.ord_sum'
                    , 'ofr.suporgid', 'o.name as suporgname'
                    , 'ofr.invoiceid', db::raw("concat('№', inv.docnum,' от ', inv.docdate) as invoice_info"))
                ->orderBy('inv.docdate', 'desc')
                ->get();
            //dd($rec->offers);

            //Последние 10 зявок с этим материалом
            $rec->last_equiprqsts = equiprqst_item::from('equiprqst_items as eri')
                ->where('refitmid', $rec->id)
                ->select('eri.rqstid', 'eri.rqst_qty', 'eri.maxreqdate')
                ->orderby('eri.maxreqdate', 'desc')
                ->orderby('eri.rqstid', 'desc')
                ->limit(10)
                ->get();
            //dd($rec->last_equiprqsts);

            //Последние 10 РВ с этим материалом
            $rec->last_bot_ri_lims = bot_ri_lim::from('bot_ri_lims as brl')
                ->join('buildopertypes as bot', 'bot.id', 'brl.buildopertypeid')
                ->where('brl.refitmid', $rec->id)
                ->select('brl.id', 'brl.buildopertypeid', 'bot.name as buildopertypename', 'brl.lim_qty', 'brl.smet_sum', 'brl.smet_price')
                ->orderby('brl.created_at', 'desc')
                ->limit(10)
                ->get();
            //dd($rec->last_bot_ri_lims);


            return view('refitems.edit',
                compact('rec', 'unittypes'
                    , 'auxinfo', 'usrrights'
                    , 'stock_On', 'ri_stocks', 'ri_stocks_view', 'ri_stock_docs', 'ri_stockdocs_view'
                    , 'ri_altnames', 'saleactions', 'ri_itmtypes'));
        } else {
            return "no data";
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
        $usrrights = $this->setInterfaceRight($id);
        if ($usrrights['save']) {

            //Validator::make($request->all(), $rules, $messages)->validate();
            $v = Validator::make($request->all(), [
                'name' => 'required',
                'producttypeid' => 'required',
//                'it_st_id' => 'required',
            ]);

            //Добавим правила, которые должны работать, только если значение поля не пусто
            //unique:refitems,upc,$id - исключаем из проверки на уникальность текущую запись
            $v->sometimes('upc', 'min:12|max:12|unique:refitems,upc,' . $id
                , function ($input) {
                    return isset($input->upc);
                });
            $v->sometimes('ean', 'min:12|max:13|unique:refitems,ean,' . $id
                , function ($input) {
                    return isset($input->ean);
                });
            $v->validate();

            $userid = \Auth::user()->id;
            $WarnMsg = "";
            $mess = "";
            if ($id == -1) {
                $refitem = new refitem([
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $mess = "Запись создана";
            } else {
                $refitem = refitem::find($id);
                $mess = "Запись обновлена";
            }
            $refitem->name = $request->get('name');
            $parent_name = $refitem->parent->name;
            $parent_name = ($parent_name) ? $parent_name . '|' : '';
            //$refitem->searchname = ri_altname::SearchNameByName($parent_name . $refitem->name . ' ' . $refitem->altname);

            //producttypeid: 1-услуга, 2-товар
            $refitem->producttypeid = $request->get('producttypeid') ?? 2;

            $refitem->qty_dec_digits = $request->get('qty_dec_digits');
            $refitem->qty_dec_digits = ($refitem->qty_dec_digits < 0) ? 0 : $refitem->qty_dec_digits;
            $refitem->qty_dec_digits = ($refitem->qty_dec_digits > 3) ? 3 : $refitem->qty_dec_digits;

            //$refitem->isservice = ($refitem->producttypeid == 1) ? 1 : 0; //todo: устарело

            $refitem->itmtypeid = $request->get('itmtypeid');
            $refitem->bdgtacnttypeid = $request->get('bdgtacnttypeid');

            //$it_st_id = $request->get('it_st_id');
            //$pos = strpos($it_st_id, "|");
            //if ($pos === false) {
            //    $refitem->itmtypeid = $it_st_id;
            //} else {
            //    $refitem->itmtypeid = substr($it_st_id, 0, $pos);
            //}

            $refitem->retailprice = $request->get('retailprice');
            $refitem->price = $request->get('price');
            $refitem->brandid = $request->get('brandid');
            //$refitem->brand = $refitem->brand_lnk->name;  //временно - для совместимости
            $refitem->manufacturer = $request->get('manufacturer');
            $refitem->partnumber = $request->get('partnumber');

            //$refitem->upc = $request->get('upc');
            //$refitem->ean = $request->get('ean');

            //SNS. Код 1С приходит извне. Не даем права на изменение
//	        $refitem->code = $request->get('code');

            $refitem->unittypeid = $request->get('unittypeid');
            $refitem->unit = $refitem->unittype->name;
            $refitem->grossweight = $request->get('grossweight');
            //$refitem->pkg_width = $request->get('pkg_width');
            //$refitem->pkg_height = $request->get('pkg_height');
            //$refitem->pkg_depth = $request->get('pkg_depth');

            //зачищаем несуществующие ссылки на фото
            $refitem->photourl = $request->get('photourl');
            if ($refitem->photourl != "") {
                $filename = $_SERVER['DOCUMENT_ROOT'] . '/storage/images/' . $refitem->photourl;
                if (!file_exists($filename)) {
                    $WarnMsg = 'Файл фото не найден: "' . $refitem->photourl . '". Ссылка на фото удалена!';
                    $refitem->photourl = null;
                }
            }
            if ($refitem->photourl == "" and isset($refitem->id)) {
                //попробуем подтянуть ближайшее фото
                $img = ri_image::where('refitmid', $refitem->id)
                    ->orderby('ordr')->first();
                if (isset($img)) {
                    $refitem->photourl = 'storage/' . $img->filename;
                }
            }

            $refitem->descript = $request->get('descript');

            $refitem->active = $request->get('active', 0);
            $refitem->saleenddate = $request->get('saleenddate');
            $refitem->updated_by = $userid;
            $refitem->updated_at = now();
            $refitem->save();

            //Установим флаг 21 (изменились характеристики товаров) для условно-общего товара id="0")
            objflag::UpdObjFlag(105, 0, 21);

            Cache::forget('lst_ri_brands'); //Бренды, использованные в прайслисте

            //Свяжем вакантные позиции заявок на материалы - по идентичному названию и ЕИ
            equiprqst_item::setRefItmIDByNameAndUnit($refitem->id, $refitem->name, $refitem->unittypeid);

            objlog::log_info($this->sysobjid, $refitem->id, $mess, 5);

        } else {
            $mess = "Отсутствуют права на сохранение";
        }


        if (isset($refitem->parent_id)) {
            return redirect(route('refitems.edit', $refitem->parent_id))
                ->with('success', $mess)
                ->with('warning', $WarnMsg);

        }
        $pageno = session('pageno');
        return redirect('/refitems/?page=' . $pageno . '#' . $refitem->id)
            ->with('success', $mess)
            ->with('warning', $WarnMsg);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $usrrights = $this->setInterfaceRight($id);
        if ($usrrights['delete']) {
            $res = refitem::delete_by_id($id, $this->sysobjid);

            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $route = route('refitems.edit', $id);
                $sd["error"] = $res->msg;
                objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            } else {
                //todo: удалить связанные записи из таблиц не в констрайне
                //ri_images - нужно также удалить связанные файлы

                $route = '/refitems/?page=' . session('pageno');

                $sd['success'] = 'Запись о товаре удалена';
                objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            }
        } else {
            $route = route('refitems.edit', $id);
            $sd["error"] = "Нет прав на удаление";
        }
        return redirect($route)->with($sd);
    }


    public function admindelete($id)
    {
        $rec = refitem::find($id);
        if ($rec) {
            $res = $rec->admindelete();
            $sd = array();
            if ($res->err == 1) {
                $route = route('refitems.edit', $id);
                $sd["error"] = $res->msg;
                objlog::log_info($this->sysobjid, $id, $res->msg, 2);

            } else {
                $route = '/refitems/?page=' . session('pageno');
                $sd['success'] = 'Запись о товаре удалена административно';
                objlog::log_info($this->sysobjid, 0, "Административное удаление товара id=" . $id, 2);
            }
            return redirect($route)->with($sd);
        }
        return redirect('/refitems/?page=' . session('pageno'));

    }

    public function assembleItem($orditemid)
    {
        //Создание товара, указанного в позиции заказа ($orditemid)
        //на основании его спецификации, и из материалов, зарезервированных документом склада (doctypeid=14)

        $retRoute = route('orditems.edit', $orditemid);
        $sd = array();

        $userid = \Auth::user()->id;


        $oi = orditem::from('orditems as oi')
            ->leftJoin('oi_qtys as iq1', function ($j) {
                $j->on('iq1.oiid', '=', 'oi.id')
                    ->where('iq1.stageid', 1); //лучше 2
            })
            ->leftJoin('oi_qtys as iq3', function ($j) {
                $j->on('iq3.oiid', '=', 'oi.id')
                    ->where('iq3.stageid', 3); //обеспечение товарами/материалами
            })
            ->where('oi.id', $orditemid)
            ->select('oi.ordid', 'oi.refitmid', 'iq1.qty as aprvqty', 'iq3.qty as bookqty')
            ->first();

        if (isset($oi)) {
            $bookqty = $oi->bookqty ?? 0;
            $aprvqty = ceil($oi->aprvqty); // округлим вверх до целого

            //проверим - может быть уже зарезервировано все количество, которое требовалось
            if ($bookqty < $aprvqty) {
                //товара зарезервировано недостаточно, продолжаем

                $needQty = $aprvqty - $bookqty; //Кол-во, которое нужно произвести

                // но сначала убедимся, что есть спецификация по кторой производится товар
                //спецификацию возьмем из состава заказа (записи, подчиненные заданной) и с isPublic=0
                //отберем из спецификации все товары
                $details = orditem::from('orditems as doid')
                    ->join('refitems as ri', 'ri.id', 'doid.refitmid')
                    ->where('ri.producttypeid', 2)//товар
                    ->join('oi_qtys as iq', 'iq.oiid', 'doid.id')
                    ->where('iq.stageid', 1)
                    ->where('doid.parent_id', $orditemid)
                    ->where('doid.ispublic', 0)
                    ->where('iq.qty', '>', 0)
                    ->select('doid.id as oiid', 'doid.refitmid', 'iq.qty')
                    ->get();
                //dd($details);
                if (count($details) > 0) {

                    // найдем документ с резервом материалов для этого заказа
                    $wrhdoc = wrhdoc::where('doctypeid', 14)
                        ->where('ordid', $oi->ordid)
                        ->first();
//                    dd($wrhdoc);


                    try {
                        DB::beginTransaction();

                        //установим семафор захвата склада
                        if (wrh::lock_by_user($wrhdoc->wrhid, $userid)) {

                            //найдем/создадим документ производства товара (doctypeid=5)
                            $wdoc5 = wrhdoc::where('doctypeid', 5)
                                ->where('ordid', $oi->ordid)
                                ->first();
                            if (!isset($wdoc5)) {
                                $wdoc5 = new wrhdoc([
                                    'doctypeid' => 5,
                                    'ordid' => $oi->ordid,
                                    'docsigned' => 0, //черновик. потом проведем вручную
                                    'ownorgid' => $wrhdoc->ownorgid,
                                    'orgid' => $wrhdoc->orgid,
                                    'docnum' => $wrhdoc->docnum,
                                    'docdate' => $date = date('Y-m-d'),
                                    'wrhid' => $wrhdoc->wrhid,
                                    'remarks' => 'автоматически создан из заказа',
                                    'created_by' => $userid,
                                ]);
                                $wdoc5->save();
                            }
                            //проверим/добавим создаваемую позицию
                            $wdi = wrhdoclst::where('docid', $wdoc5->id)
                                ->where('subtypeid', 10)//тип для создаваемых позиций
                                ->first();
                            if (!isset($wdi)) {
                                $wdi = new wrhdoclst([
                                    'docid' => $wdoc5->id,
                                    'refitmid' => $oi->refitmid,
                                    'subtypeid' => 10,
                                    'oiid' => $orditemid,
                                    'qty' => $oi->aprvqty,
                                ]);
                            }
                            $wdi->qty = $oi->aprvqty;
                            $wdi->save();


                            //пройдемся по спецификации, подтягивая резерв из складского документа ($wrhdoc)
                            foreach ($details as $detail) {

                                //источник товара
                                $wdi_src = wrhdoclst::where('docid', $wrhdoc->id)
                                    ->where('oiid', $detail->oiid)
                                    ->first();
                                if (!isset($wdi_src)) {
                                    throw new \Exception("Не найден источник резерва для производства товара!");
                                }

                                $wdi_tgt = wrhdoclst::where('docid', $wdoc5->id)
                                    ->where('oiid', $detail->oiid)
                                    ->where('subtypeid', 11)//тип для используемых материалов
                                    ->first();
                                if (!isset($wdi_tgt)) {
                                    $wdi_tgt = new wrhdoclst([
                                        'docid' => $wdoc5->id,
                                        'oiid' => $detail->oiid,
                                        'subtypeid' => 11,
                                        'refitmid' => $wdi_src->refitmid,
                                    ]);
                                }
                                //произведем перемещение товара между документами
                                $wdi_tgt->qty = $wdi_src->qty;
                                $wdi_src->qty = 0;
                                $wdi_src->save();
                                $wdi_tgt->save();

                            }

                            DB::commit(); //зафиксируем все

                            wrh::unlock_by_user($wrhdoc->wrhid, $userid);
                        }
                    } catch
                    (\Exception $e) {
                        DB::rollback();

                        //$rslt->err = 1;
                        //$rslt->msg = "Ошибка создания товара по спецификации";
                        \Log::error($e->getMessage() . "\n" . $e->getTraceAsString());

                    }

                    $sd["success"] = "ok: " . $needQty;
                    $retRoute = route('orditems.edit', $orditemid);

                } else {

                    $sd["error"] = "товар не имеет спецификации, и не может быть сформирован!";
                }
            } else {

                $sd["success"] = "Уже зарезервировано достаточное количество товара.";
            }
        } else {

            $sd["error"] = "Позиция заказа не найдена";
            $retRoute = route('orders.index');
        }

        return redirect($retRoute)->with($sd);
    }

    static function fillOrdr()
    {

        refitem::fill_ordr();

        return Redirect::back()->withErrors(['warning', 'Произведен расчет приоритета вывода номенклатуры.']);
    }

    static function join($srcid, $tgtid)
    {

        refitem::join($srcid, $tgtid);

        return redirect(route('refitems.edit', $tgtid));   //->with($sd);
    }


    static public function list_for(Request $request)
    {
        //2021-11-07 SNS. Обертка для вызова refitem::lstFor

        $result = "";
        try {
            $list = refitem::lstFor([
                'name' => $request->name,
                'suporgid' => $request->suporgid,
                'active' => $request->active,
                'itmtypeid' => $request->itmtypeid,
            ]);

            $result = array('refitems' => $list);

        } catch (\Exception $e) {
            Log::error('refitem::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }

    static public function list_for_ac(Request $request)
    {
        //2021-11-07 SNS. Для автокомплита

        $fields = ['ri.id', 'ri.name', 'ri.code', 'ri.unittypeid', 'ri.unit'];
        $suporgid = $request->get('suporgid');
        if (isset($suporgid)) {
            $fields[] = 'rop.price';
        }
        Log::info('refitem::list_for_ac:fields=' . implode(';', $fields));

        $result = "";
        try {

            $list = refitem::getFor([
                'name' => $request->name,
                'name_type' => $request->name_type,
                'suporgid' => $request->suporgid,
                'load_placeid' => $request->load_placeid,
                'active' => $request->active,
                'itmtypeid' => $request->itmtypeid,
                'price_on_date' => $request->price_on_date,
            ],
                //['ri.id', 'ri.name', 'ri.code', 'ri.unittypeid', 'ri.unit', 'rop.price']
                $fields
            );

            $result = $list;

        } catch (\Exception $e) {
            Log::error('refitem::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }


}
