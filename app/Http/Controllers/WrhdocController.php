<?php

namespace App\Http\Controllers;

use App\mol_stock;
use App\obj_expense;
use App\obj_finoper;
use App\objflag;
use App\objlog;
use App\order;
use App\orditem;
use App\org_place;
use App\refitem;
use App\ri_compound;
use App\sysobj_lockdate;
use App\usrsysright;
use App\org;
use App\orgstaff;
use App\sysobj;

use App\wrh;
use App\wrh_box;
use App\wrh_stock;
use App\wrhdoc;
use App\wrhdoctype;
use App\wrhdoclst;
use App\wrhdocnum;

use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use DateTime;

class WrhdocController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    protected $sysobjid;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 204;
        $this->sysobjcode = 'wrhdocs';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    protected function setInterfaceRight($docid)
    {
        $usrrights = array();
        $usrrights['save'] = false;
        $usrrights['safe_save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['ownorg.edit'] = false;
        $usrrights['doctype.edit'] = false;
        $usrrights['load.items.file'] = false;
        $usrrights['load.items.order'] = false;
        $usrrights['docsign'] = false;
        $usrrights['docunsign'] = false;
        $usrrights['doclst.create'] = false;
        $usrrights['doclst.update'] = false;
        $usrrights['make_diffdoc'] = false;
        $usrrights['make_doc5'] = false;

        $userid = \Auth::user()->id;

        if ($docid == -1) {
            //Новый документ - можно сохранять
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');;
            $usrrights['safe_save'] = $usrrights['save'];
            $usrrights['doctype.edit'] = $usrrights['save'];
            $usrrights['ownorg.edit'] = $usrrights['save'];;

        } else {

            $baseUpdateRight = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $baseDeleteRight = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');

            $rec = wrhdoc::find($docid);

            $docsigned = ($rec->docsigned == 1);
            if (!$docsigned) {
                //Документ не утвержден

                if ($rec->items->count() == 0) {
                    //Еще не имеет состава

                    $usrrights['save'] = $baseUpdateRight;
                    $usrrights['safe_save'] = $usrrights['save'];
                    $usrrights['delete'] = $baseDeleteRight;

                    $usrrights['load.items.file'] = ($rec->docsigned != 1 and $usrrights['save']);
                    $usrrights['load.items.file'] = false; //нужно сначала реализовать загрузку состава документа из файла

                    $usrrights['load.items.order'] = ($rec->ordid != "" and $usrrights['save']);

                    $usrrights['ownorg.edit'] = (!$rec->ordid and $usrrights['save']);
                } else {
                    // Состав уже есть. Но некоторые поля редактировать можно
                    $usrrights['safe_save'] = $baseUpdateRight;
                }

                $usrrights['doclst.create'] = ($baseUpdateRight and wrhdoc::mayCreateLst($docid));
                $usrrights['doclst.update'] = ($baseUpdateRight);

                $signrightid = $rec->doctype->signrightid ?: 103;
                $usrrights['docsign'] = usrsysright::isUserHasRight($userid, $signrightid);
                //dd($usrrights['docsign']);

                $usrrights['admindelete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.admindelete');

            } else {
                //Документ утвержден

                //проверим открытость периода
                $doc_locked = wrhdoc::isLocked($docid);
                //dd('doc_locked',$doc_locked);
                if ($doc_locked) {
                    $usrrights['docunsign'] = false;

                } else {
                    // период Открыт - все определяется правами
                    $usrrights['docunsign'] = true;
                    if (!wrhdoc::mayUnsignDoc($docid)) $usrrights['docunsign'] = false;
                }

            }

        }

        $usrrights['edit'] = $usrrights['save'];

        $usrrights['paydocs.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'paydocs.read');
        $usrrights['paydocs.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'paydocs.create');

//        dd($usrrights);
        return $usrrights;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    //Поиск
    public function index(Request $request)
    {

        $userid = \Auth::user()->id;

        $usrrights = array(
            'create' => usrsysright::isUserHasRightByCode($userid, 'wrhdocs.create'),
        );


        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 20
            , 's_timestatuscode' => 2   //вчера
            , 's_docdate' => ''
            , 's_doctypeid' => ''
            , 's_docnum' => ''
            , 's_wrhid' => ''
            , 's_statuscode' => ''
            , 's_inpout' => ''
            , 's_ownorgid' => ''
            , 's_orgid' => ''
            , 's_refitmid' => ''
            , 's_ri_name' => ''
            , 's_disp_staffid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_doctypeid') {
                    $sc .= " and wd.doctypeid ={$val}";

                } elseif ($item == 's_timestatuscode') {
                    if ($val == 1) //сегодня
                        $sc = $sc . " and wd.docdate = curdate()";
                    elseif ($val == 2) //вчера
                        $sc = $sc . " and datediff(curdate(), wd.docdate) = 1";
                    elseif ($val == 3) //за неделю
                        $sc = $sc . " and datediff(curdate(), wd.docdate) <= 7";
                    elseif ($val == 4) //с начала текущего месяца
                        $sc .= " and extract(year_month from wd.docdate) = extract(year_month from curdate())";
                    elseif ($val == 5
                        and DateTime::createFromFormat('Y-m-d', $search_params['s_docdate']) !== false) {
                        //конкретная дата
                        $sc .= " and wd.docdate = '" . $search_params['s_docdate'] . "'";
                    }

                } elseif ($item == 's_active') {
                    $sc .= " and ifnull(wd.active,0) = '{$val}'";

                } elseif ($item == 's_docnum') {
                    $sc .= " and wd.docnum like '" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_ownorgid') {
                    $sc .= " and wd.ownorgid={$val}";

                } elseif ($item == 's_orgid') {
                    $sc .= " and wd.orgid={$val}";

                } elseif ($item == 's_wrhid') {
                    $sc .= " and wd.wrhid={$val}";

                } elseif ($item == 's_statuscode') {
                    $sc .= " and wd.docsigned={$val}";

                } elseif ($item == 's_inpout') {
                    $sc .= " and dt.forstock={$val}";

                } elseif ($item == 's_refitmid') {
                    $sc .= " and exists( select 1 from wrhdoclst dl where dl.docid=wd.id and dl.refitmid={$val})";

                } elseif ($item == 's_ri_name') {
                    $sc .= " and exists( select 1 from wrhdoclst dl
                                            join refitems ri on ri.id=dl.refitmid
                                            where dl.docid=wd.id and ri.name like '%{$val}%')";

                } elseif ($item == 's_disp_staffid') {
                    $sc .= " and wd.disp_staffid={$val}";
                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------

        //Если параметры поиска не заданы, то уйдем на index
//        if (strlen($s_doctypeid . $s_docnum . $s_wrhid . $s_statuscode . $s_inpout) == 0)
//            return redirect()->route('wrhdocs.index');


        $sort_params = session('sort_params');
        if (isset($sort_params)) {
            $sort_by = $sort_params['field'];
            $sort_dir = $sort_params['dir'];
        } else {
            $sort_by = 'wd.id';
            $sort_dir = 'desc';
        }

        $recs = wrhdoc::from('wrhdocs as wd')
            ->join('wrhdoctypes as dt', 'dt.id', '=', 'wd.doctypeid')
            ->join('orgs as oo', 'oo.id', '=', 'wd.ownorgid')
            ->leftjoin('orgs as o', 'o.id', '=', 'wd.orgid')
            //->selectraw('wd.*, if(wd.docsigned=1,"утвержден","не утвержден") statusname, oo.name as ownorg_name');
            ->select('wd.*', db::raw("if(wd.docsigned=1,'утвержден','не утвержден') statusname")
                , 'oo.name as ownorg_name'
                , 'o.name as org_name'
            );
        if (isset($sc))
            $recs = $recs->whereRaw($sc);


        $recs = $recs->orderBy('wd.docdate', 'desc')
            ->orderBy($sort_by, $sort_dir)
            ->with('wrh')
            ->paginate($search_params['s_pageitmcnt'] ?? 20);

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid);

        //номер первой записи на странице:
        $data->rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data->search_params = $search_params;

        $data->ownorgs = org::lstFor_cached(['in_wrhdocs_ownorg' => 1]);  //Владельцы из документов склада
        $data->orgs = org::lstFor_cached(['in_wrhdocs_org' => 1]);  //Контрагенты из документов склада
        $data->s_wrhs = wrh::listUsed();
        $data->s_doctypes = wrhdoctype::listUsed();
        $data->s_disp_staffids = orgstaff::lstFor_cached(['dispatcher_in_wrhdocs' => 1]);

        $data->timestatuses = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц', 5 => 'календарь'];
        $data->s_statuscodes = array('' => '-любой-', '0' => 'не утвержден', '1' => 'утвержден');
        $data->s_inpouts = array('' => '-любой-', '1' => 'приход', '-1' => 'расход', '0' => 'без изм.');

        // Пункты меню (сверху-справа) ---------------------------------
        $t_coll = collect();
        $t_coll->push((object)[
            'name' => 'Заказы',
            'url' => route('orders.index'),
            'title' => 'Заказы клиентов на изделия'
        ]);
        $t_coll->push((object)[
            'name' => 'Запас',
            'url' => route('reports.rep33'),
            'title' => 'Товарный запас на складах'
        ]);
        $t_coll->push((object)[
            'name' => 'МОЛ',
            'url' => route('reports.rep34'),
            'title' => 'Товарный запас у материально-ответственных лицах'
        ]);
        $t_coll->push((object)[
            'name' => 'Номенклатура',
            'url' => route('refitems.index'),
            'title' => 'Товары и услуги'
        ]);
        if (usrsysright::isUserHasRightByCode($userid, 'dlvrydocs.read')) {
            $t_coll->push((object)[
                'name' => 'Получение на линии',
                'url' => route('dlvrydocs.index'),
                'title' => 'Получение материала на объекте'
            ]);
        }
        if (usrsysright::isUserHasRightByCode($userid, 'wrhdocs.sign')) {
            $t_coll->push((object)[
                'name' => 'Восполнение',
                'url' => route('wrhdocs.make_docs10'),
                'title' => 'Производство недостающей продукции'
            ]);
        }
//        if (usrsysright::isUserHasRightByCode($userid, 'admin-global')) {
        if (usrsysright::isUserHasRightByCode($userid, 'wrhdocs.sign')) {
            $t_coll->push((object)[
                'name' => 'recalc stock',
                'url' => route('recalc_stock'),
                'title' => 'Полный пересчет остатков на складах'
            ]);
        }
        $data->top_right_menu = $t_coll;
        //--------------------------------------------------------------


        return view($this->sysobjcode . '.index', compact('recs', 'usrrights', 'data'));
    }


    public function createFromOrd($ordid)
    {
        //Значения "по-умолчанию" для новой записи
        $rec = new wrhdoc();
        $rec->id = -1;
        $rec->ordid = $ordid;
        $rec->ownorgid = order::find($ordid)->ownorgid;
        $rec->doctypeid = 3;
        $rec->docdate = date("Y-m-d", strtotime(now()));
        $rec->created_by = \Auth::user()->id;

        $wrhs = wrh::where('active', 1)->orWhere('id', $rec->wrhid)->get()
            ->pluck("name", "id")->prepend("", "");
        $rec->wrhs = $wrhs;

        $usrrights = $this->setInterfaceRight($rec->id);

        $doctypes = wrhdoctype::lstActive()->pluck("name", "id")->prepend("", "");

        return view($this->sysobjcode . '.edit', compact(['rec', 'usrrights', 'doctypes']));
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
     * @param \App\wrh $rec
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи
            $docdate = $request->get('docdate');
            $docdate = (isset($docdate)) ? strftime('%Y-%m-%d', strtotime($docdate)) : date("Y-m-d", strtotime(now()));

            $rec = new wrhdoc([
                'id' => -1,
                'docsigned' => 0,
                'ownorgid' => \Auth::user()->curorgid,
                //2022-06-12 Пока упрощаем - так как скроем выбор отделения
                'wrhid' => 1,
                'boxid' => 1,
                'docdate' => $docdate,
                'created_by' => $userid,
            ]);

        } else {
            $rec = wrhdoc::find($id);
        }

        if (!isset($rec))
            return redirect($this->sysobjcode . '.index')->with(['error' => 'Документ не найден!']);

        $rec->ownorgs = org::lstFor([
            'flagtypeid' => 12,
            'in_userorgs' => $userid,
        ]);

        $rec->wrhs = wrh::lstFor([
            'with_boxes' => 1,
            'active_or_current' => $rec->wrhid,
        ]);
        //dd($rec->wrhs);

        $rec->places = org_place::lstFor([
            'orgid' => $rec->ownorgid,
            'active_or_current' => $rec->placeid,
        ]);
        //dd($rec->places);
        if (is_null($rec->placeid) and sizeof($rec->places) == 1)
            $rec->placeid = array_key_first($rec->places);
        //dd($rec->placeid);

        //Проверим, может данный документ является прародителем другого документа
        $rec->childdoc = wrhdoc::where('predocid', $id)
            ->select('id', 'docnum', 'docdate', 'doctypeid', 'docsigned')
            ->first();

        //Ответственный персонал
        if (!$rec->docsigned == 1) {
            if (1 == 1) {
                //Список пользователей, имеющих право подписи документов склада
                $rec->respstafflst = usrsysright::from('usrsysrights as ur')
                    ->join('sysfuncs as sf', 'sf.id', '=', 'ur.sysfuncid')
                    ->where('sf.code', 'wrh.storekeeper')
                    ->join('users as u', 'u.id', '=', 'ur.userid')
                    ->where('ur.active', '=', 1)
                    ->whereRaw('now() between ur.begdt and ifnull(ur.enddt,now())')
                    ->selectraw('u.id, concat(lname," ",fname," ",ifnull(mname," ")) as name')
                    ->get()
                    ->pluck("name", "id")->prepend("", "");
                //todo: добавить также подборку из stfsysrights - для конкретной организации(получателя)
                //dd($rec->respstafflst);

            } else {
                $rec->respstafflst = orgstaff::from('orgstaff as os')
                    ->where('os.orgid', $rec->ownorgid)
                    ->selectraw('id, concat(lname," ",fname," ",ifnull(mname," ")) as name')
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('stfduties as sd')
                            ->whereRaw('sd.staffid=os.id')
                            ->whereIn('dutytypeid', [21, 22])
                            ->where('active', 1)
                            ->whereRaw('now() between begdt and ifnull(enddt,now())');
                    })
                    ->get()
                    ->pluck("name", "id")->prepend("", "");
            }


        } else {
            $rec->respstafflst = ["", ""];
        }
        //dd( $rec->respstafflst);
        $rec->saleorg_gk = 1;

        $items = wrhdoclst::from('wrhdoclst as dl')
            ->join('refitems as ri', 'ri.id', 'dl.refitmid')
            ->join('unittypes as ut', 'ut.id', 'ri.unittypeid')
            ->leftjoin('itmtypes as it', 'it.id', 'ri.itmtypeid')
            ->leftjoin('ri_compounds as ric', 'ric.id', 'dl.cmpndid')
            ->where('dl.docid', $id)
            ->select('dl.*', 'ri.name as ri_name', 'ut.name as ut_name', 'ut.decimal_dgts'
                , 'it.name as it_name'
                , db::raw("concat(ric.notes, ' от ', date_format(begdate, '%d.%m.%Y'))  as cmpnd_name"))
            ->get();

        $restorditems = null;
        if (isset($rec->ordid)) {

            //неотгруженный и незарезервированный остаток по заказу и остаток на складе
            $restorditems = refitem::from('refitems as ri')
                ->join(
                    DB::raw('(SELECT refitmid, sum(iq.qty-ifnull(iqb.qty,0)) as restqty
                 FROM orditems as oi
                 join oi_qtys as iq on iq.oiid=oi.id and iq.stageid=1
                 left join oi_qtys as iqb on iqb.oiid=oi.id and iqb.stageid=3
                 where ordid = ' . $rec->ordid . ' and iq.qty-ifnull(iqb.qty,0) /*-plnshipqty-aprvshipqty*/>0
                 GROUP BY refitmid) as a'),

                    function ($join) {
                        $join->on('ri.id', '=', 'a.refitmid');
                    })
                ->leftJoin('wrh_stocks as ws', function ($j) use ($rec) {
                    $j->on('ws.refitmid', '=', 'a.refitmid')
                        ->where('ws.ownorgid', $rec->ownorgid)
                        ->where('ws.wrhid', $rec->wrhid);
                })
                ->where('ri.producttypeid', 2)
                ->select('a.*', 'ri.searchname as ri_name', 'ri.grossweight as ri_grossweight'
                    , 'ws.qty as ws_qty', 'ws.plnincqty as ws_plnincqty', 'ws.plnoutqty as ws_plnoutqty')
                ->get();
//            dd($restorditems);

            if (1 == 0) {
                //Тоже рабочий вариант, даже может быть более правильный, так как обходится без слепливания
                // ID заказа, но результат нельзя использовать в связях моделей
                $restorditems = DB::query()->fromSub(function ($query) use ($rec) {
                    $query->from('orditems')
                        ->selectraw('refitmid, sum(qty-plnshipqty-aprvshipqty) as restqty')
                        ->where('ordid', $rec->ordid)
                        ->whereraw('qty-plnshipqty-aprvshipqty>0')
                        ->groupBy('refitmid');;
                }, 'a')
                    ->join('refitems as ri', 'ri.id', '=', 'a.refitmid')
                    ->leftJoin('wrh_stocks as ws', function ($j) use ($rec) {
                        $j->on('ws.refitmid', '=', 'a.refitmid')
                            ->where('ws.ownorgid', $rec->ownorgid)
                            ->where('ws.wrhid', $rec->wrhid);
                    })
                    ->select('a.*', 'ri.name as ri_name', 'ri.grossweight as ri_grossweight'
                        , 'ws.qty as ws_qty', 'ws.plnincqty as ws_plnincqty', 'ws.plnoutqty as ws_plnoutqty')
                    ->get();
//            dd($restorditems);
            }
        }
        $rec->restorditems = $restorditems;

        $rec->boxes = wrh_box::lstFor([
            'wrhid' => $rec->wrhid,
            'active_or_current' => $rec->boxid,
        ]);

        $rec->relboxes = wrh_box::lstFor([
            'wrhid' => $rec->relwrhid,
            'active_or_current' => $rec->relboxid,
        ]);


        $usrrights = $this->setInterfaceRight($id);

        if ($items->count() > 0) {
            // уже есть состав - изменять или удалять "шапку" нельзя
            $usrrights['save'] = false;
            $usrrights['delete'] = false;
        } else {
            // нет состава - нечего согласовывать/разсогласовывать
            $usrrights['docsign'] = false;
            $usrrights['docunsign'] = false;
        }

        //Потенциальное право на создание документа разногласий. Ниже (в blade) будет проверяться необходимость
        $usrrights['make_diffdoc'] = (isset($rec->predocid) and $rec->docsigned == 1);

        //Потенциальное право на создание документа на списание материалов на производство.
        // Ниже (в blade) будет проверяться необходимость
        $usrrights['make_doc5'] = ($rec->doctypeid == 10 and $rec->docsigned == 1);

        if ($usrrights['safe_save']) {
            //установим минимально-допустимую дату для wrkdate
            $rec->docdate_min = wrhdoc::min_docdate();
        }

        //Cache::forget('wrhdoctypes');
        $rec->doctypes = Cache::remember('wrhdoctypes', now()->addMinutes(15)
            , function () use ($rec) {
                return wrhdoctype::lstFor(['active_or_current' => $rec->doctypeid]);
            });


        $auxinfo = wrhdoc::AuxInfo($id);

        if (1 == 1 or $userid == 12)
            $rec->finopers = obj_finoper::from('obj_finopers as fo')
                ->join('orgs as s_o', 's_o.id', 'fo.srcorgid')
                ->join('orgs as t_o', 't_o.id', 'fo.tgtorgid')
                ->leftjoin('opertypes as ot', 'ot.id', 'fo.opertypeid')
                ->leftjoin('contracts as c', 'c.id', 'fo.contractid')
                ->where('sysobjid', $this->sysobjid)
                ->where('fo.objid', $rec->id)
                ->select('fo.*'
                    , 's_o.name as srcorg_name'
                    , 't_o.name as tgtorg_name'
                    , 'ot.name as opertype_name'
                )
                ->orderBy('fo.operdate')
                ->get();

        //dd($rec->finopers);
        $usrrights['obj_expenses.create'] = true;
        $usrrights['obj_expenses.update'] = true;
        $rec->expenses = obj_expense::from('obj_expenses as oe')
            ->leftjoin('opertypes as ot', 'ot.id', 'oe.opertypeid')
            ->leftjoin('expensetypes as et', 'et.id', 'oe.expensetypeid')
            ->where('oe.sysobjid', $this->sysobjid)
            ->where('oe.objid', $rec->id)
            ->select('oe.*'
                , 'ot.name as opertype_name'
                , 'et.name as expensetype_name'
            )
            ->orderBy('oe.operdate')
            ->get();

        //dd($this->sysobjid, $rec->id, $rec->expenses);
//        dd($usrrights);
        // подсчитаем общие затраты: ------------------------------------
        $rec->tot_expense_sum = 0;
        foreach ($rec->items as $itm)
            $rec->tot_expense_sum += $itm->qty * $itm->price;
        foreach ($rec->expenses as $itm)
            $rec->tot_expense_sum += $itm->expense_sum;
//        dd($rec->tot_expense_sum);

        return view($this->sysobjcode . '.edit',
            compact('rec', 'items', 'auxinfo', 'usrrights'));
    }


    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\wrh $rec
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $rules = [
            "doctypeid" => "required",
        ];

        $messages = [
            "doctypeid.required" => "Укажите тип документа",
        ];
        Validator::make($request->all(), $rules, $messages)->validate();;

        $doctypeid = $request->get('doctypeid');
        $doctype_params = wrhdoctype::select('need_relwrh', 'need_predoc', 'need_org', 'formol')
            ->find($doctypeid);

        $rules = [
            "ownorgid" => "required",
//            "docnum" => "required",
            "docdate" => "required",
            "doctypeid" => "required",
            "disp_staffid" => "required",
            "wrhid" => "required|different:relwrhid",
            "relwrhid" => "different:wrhid",
            "boxid" => "required|different:relboxid",
            "relboxid" => "different:boxid",
        ];

        $messages = [
            "ownorgid.required" => "Укажите владельца товара",
//            "docnum.required" => "Укажите номер документа",
            "docdate.required" => "Укажите дату документа",
            "doctypeid.required" => "Укажите тип документа",
            "disp_staffid.required" => "Укажите диспетчера по документу",
            "wrhid.required" => "Укажите склад",
            "wrhid.different" => "Склады должны отличаться",
            "relwrhid.different" => "Склады должны отличаться",
            "boxid.required" => "Укажите отделение склада",
            "relboxid.different" => "Подразделения склада должны отличаться",
        ];

        if ($doctype_params->need_relwrh == 1) {
            $rules['relwrhid'] = 'required';
            $messages['relwrhid.required'] = 'Укажите связанный склад';
        }
        if ($doctype_params->need_org == 1) {
            $rules['orgid'] = 'required';
            $messages['orgid.required'] = 'Укажите Получателя';
        }
        if ($doctype_params->formol != 0) {
            $rules['mol_staffid'] = 'required';
            $messages['mol_staffid.required'] = 'Укажите сотрудника - Материально-ответственное лицо';
        }
        Validator::make($request->all(), $rules, $messages)->validate();

        $userid = \Auth::user()->id;
        $msg = "";
        $ownorgid = $request->get('ownorgid');
        $doctypeid = $request->get('doctypeid');
        $docdate = $request->get('docdate');

        $docnum = $request->get('docnum');
        if (!isset($docnum)) {
            $docnum = wrhdocnum::NxtDocNum($doctypeid, $ownorgid, $docdate);
        }

        if ($id == -1) {

            $rec = new wrhdoc([
                "ownorgid" => $ownorgid,
                "doctypeid" => $doctypeid,
                "ordid" => $request->get('ordid'),
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $msg = "Создана запись о новом документе ";
        } else {
            $rec = wrhdoc::find($id);
            $msg = "Обновлена запись о документе";
        }


        //Некоторые поля можно изменять, только если док-т еще не имеет состава
        if ($rec->items()->count() == 0) {

            // поля нельзя менять при сформированном составе
            $rec->wrhid = $request->get('wrhid');
            $rec->relwrhid = $request->get('relwrhid');

            $rec->boxid = $request->get('boxid');
            $rec->relboxid = $request->get('relboxid');
        }

        $rec->docnum = $docnum;
        $rec->docdate = $docdate;
        $rec->ownorgid = $request->get('ownorgid');
        $rec->placeid = $request->get('placeid');
        $rec->saleorgid = $request->get('saleorgid');
        $rec->orgid = $request->get('orgid');
        $rec->respstaffid = $request->get('respstaffid');
        $rec->disp_staffid = $request->get('disp_staffid');
        $rec->mol_staffid = $request->get('mol_staffid');
        $rec->remarks = $request->get('remarks');

        //$rec->active = $request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();


        if ($id == -1) {
            objlog::log_info($this->sysobjid, $rec->id, $msg, 5);
            if (isset($rec->ordid))
                return redirect(route('wrhdocs.edit', $rec->id))->with('success', $msg);
            else
                return redirect(route('wrhdoclst.create', $rec->id))->with('success', $msg);
        } else {
            objlog::log_info($this->sysobjid, $rec->id, $msg, 5);

            //            return redirect(route('wrhdocs.index'));
            if (isset($rec->ordid)) {
                $route = route('orders.edit', $rec->ordid);
            } else {
//                $route = route('wrhdocs.index')->with('success', $msg);
//                $route = route('wrhdocs.index');
                $route = route('wrhdocs.edit', $rec->id);
            }
            return redirect($route)->with('success', $msg);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\wrh $rec
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        $res = wrhdoc::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route('wrhdocs.edit', $id);
            $sd["error"] = $res->msg;
            objlog::log_info($this->sysobjid, $id, $res->msg, 2);
        } else {
            $route = route('wrhdocs.index');
            $sd['success'] = 'Запись о документе удалена';
        }
        return redirect($route)->with($sd);
    }

    public
    function admindelete($id)
    {
        $rec = wrhdoc::find($id);
        if ($rec) {


            $res = $rec->admindelete();
            $sd = array();
            if ($res->err == 1) {
                $route = route($this->sysobjcode . '.edit', $id);
                $sd["error"] = $res->msg;
                objlog::log_info($this->sysobjid, $id, $res->msg, 2);

            } else {
                // 2024-10-20 To-do ! переделать как в wrhdoc::delete_by_id !!!
                obj_finoper::from('obj_finopers as f')
                    ->where('sysobjid', $this->sysobjid)
                    ->whereRaw("not exists (select 1 from wrhdocs as d where d.id=f.objid)")
                    ->delete();
                //удалим записи из obj_expenses, для которых уже нет соответствующих записей в wrhdocs
                obj_expense::from('obj_expenses as t')
                    ->where('sysobjid', $this->sysobjid)
                    ->whereRaw("not exists (select 1 from wrhdocs as d where d.id=t.objid)")
                    ->delete();

                //2026-12-14 Пересчет остатков на складах и у МОЛ
                DB::unprepared('CALL recalc_stock()');

                $route = route($this->sysobjcode . '.index') . '?page=' . session('pageno');
                $sd['success'] = 'Запись удалена административно';
                objlog::log_info($this->sysobjid, 0, "Административное удаление записи id=" . $id, 2);

            }
            return redirect($route)->with($sd);
        }
        return redirect(route($this->sysobjcode . '.index') . '?page=' . session('pageno'));

    }

    public
    function sign(Request $request, $id)
    {
        $userid = \Auth::user()->id;

        $route = route('wrhdocs.edit', $id);
        $sd = array();

        $rec = wrhdoc::find($id);
        if (!isset($rec)) {

            $route = route('wrhdocs.index');
            $sd["error"] = 'Документ не найден!';

        } else {

            $update_respstaffid = false;
            if ($rec->doctype->need_respstaffid == 1) {
                $update_respstaffid = true;
                $rules = [
                    "respstaffid" => "required",
                ];

                $messages = [
                    "respstaffid.required" => "Не указан ответственный сотрудник",
                ];
                Validator::make($request->all(), $rules, $messages)->validate();
            }

            if (usrsysright::isUserHasRightByCode($userid, 'wrhdocs.sign')) {

                if (wrhdoc::isLocked($rec->id)) {
                    $sd["error"] = 'Дата документа находится в заблокированном периоде! Утвердить нельзя.';
                    $route = route('wrhdocs.edit', $id);
                    objlog::log_info($this->sysobjid, $id, 'Попытка утверждение(проведения) документа с датой в закрытом периоде', 2);

                    return redirect($route)->with($sd);
                }

                //$limit_stock = true; //todo: сделать преференцию "Не снижать запас ниже 0"
                // Преференция для владельца товара: FlagTypeID = 190: Если есть, то можно снижать товарный запас < 0
                $org_limit_stock = !objflag::IsSetObjFlag(111, $rec->ownorgid, 190);
                $limit_stock = $org_limit_stock;

                DB::beginTransaction();

//                try {

                //Захватим склад для проведения транзакции
                if (wrh::lock_by_user($rec->wrhid, $userid)) {

                    $rec->docsigned = 1;
                    $rec->updated_by = $userid;
                    $rec->updated_at = now();
                    if ($update_respstaffid) {
                        //respstaffid для этого типа документов можно редактировать и
                        // при наличии состава документа
                        $rec->respstaffid = $request->get('respstaffid');
                    }

                    if (!isset($rec->docnum) or empty($rec->docnum)) {
                        $rec->docnum = wrhdocnum::NxtDocNum($rec->doctypeid, $rec->ownorgid, $rec->docdate);
                    }

                    //DB::unprepared('CALL recalc_stock()');
                    //DB::unprepared('CALL recalc_wrhbox_stocks()');
                    //или так:

                    $forstock = $rec->doctype->forstock;

                    $rslt_good = true; //признак что удалось провести захват товаров для каждой позиции
                    $lst = wrhdoclst::where('docid', $rec->id)->get();

                    //базовое условие отбора товара из запаса на складе
                    $sc = "wrhid={$rec->wrhid} and boxid={$rec->boxid}";
                    if ($rec->doctype->any_ownorg == 0) {
                        //нельзя брать из запаса любой компании
                        $sc .= " and ownorgid={$rec->ownorgid}";
                    }
//dd($sc);
                    foreach ($lst as $itm) {

                        if ($itm->subtypeid)
                            //значение для индивидуального типа
                            $itm_forstock = $itm->subtype->forstock;
                        else
                            //значение для документа
                            $itm_forstock = $forstock;

                        //если можно снижать запас менее 0 по организации, то дальше не проверяем
                        if (!$org_limit_stock)
                            $limit_stock = $org_limit_stock;
                        else
                            //проверим, можно ли снижаться ниже 0 для конкретного товара
                            $limit_stock = !objflag::IsSetObjFlag(105, $itm->refitmid, 190);


                        $sc1 = '';
                        // если документ снижает товарный запас
                        // И нельзя снижать запас ниже 0
                        // И можно брать товар из запасов любой организации (СПОРНО!!!),
                        // то, отсечем записи с 0 кол-вом в wrh_stocks
                        if ($itm_forstock < 0 and $limit_stock and $rec->doctype->any_ownorg == 1) {
                            $sc1 .= ' and qty > 0';
                        }

                        $stock = wrh_stock::where('refitmid', $itm->refitmid)
                            ->whereRaw($sc . $sc1)
                            ->first();

                        if (!isset($stock)) {
                            $stock = new wrh_stock([
                                'refitmid' => $itm->refitmid,
                                'ownorgid' => $rec->ownorgid,
                                'wrhid' => $rec->wrhid,
                                'boxid' => $rec->boxid,
                                'qty' => 0,
                                'plnincqty' => 0,
                                'plnoutqty' => 0,
                            ]);
                        }

                        if ($itm_forstock < 0) {
                            //уменьшение остатка на складе
                            if ($limit_stock) {
                                //не можем опускаться ниже 0
                                $stock_qty = $stock->qty ?? 0;  //wrh_stock не содержит записи с нулевыми остатками
                                if ($stock_qty < $itm->qty) {
                                    $rslt_good = false;

                                    $itmname = $itm->refitem->searchname;
                                    throw new \Exception("Недостаточный запас для позиции: $itm->refitmid:'$itmname'.
                                        Требуется: $itm->qty Доступно: $stock_qty");
                                }
                            }
                            $stock->plnoutqty = $stock->plnoutqty - $itm->qty;
                            $stock->qty = $stock->qty - $itm->qty;
                        }
                        if ($itm_forstock > 0) {
                            //поступление на склад
                            $stock->plnincqty = $stock->plnincqty - $itm->qty;
                            $stock->qty = $stock->qty + $itm->qty;
                        }
                        $stock->save();

                        if (1 == 0 and isset($rec->ordid)) {
                            //!!! нужно капитально переделать!

                            // видимо ранее считалось, что связь заказа с документом склада может быть только
                            // отпуск со склада.
                            // Но сейчас с заказом связаны разные типы документов склада:
                            // 14 - резервирование товаров под отпуск и производство
                            // 5 - производство товаров, необходимых заказу
                            // 3 - расходная накладная (реализация товара) - то, что ранее было единственным
                            // и они по-разному влияют на заказ


                            // --- устаревший алгоритм ---
                            //обновим поля OrdItems.plnshipqty и aprvshipqty
                            //учитывая, что в заказе может быть несколько записей об одном товаре,
                            // придется пройти по всем кандидатам с "ненулевой вместимостью"
                            $orditems = orditem::where('ordid', $rec->ordid)
                                ->where('refitmid', $itm->refitmid)
                                ->where('plnshipqty', '>', 0)
                                ->whereraw('qty - aprvshipqty > 0')
                                ->get();

                            $orditems = orditem::from('orditems as oi')
                                ->leftJoin('oi_qtys as iq1', function ($j) {
                                    $j->on('iq1.oiid', '=', 'oi.id')
                                        ->where('iq1.stageid', 1); //лучше 2
                                })
                                ->leftJoin('oi_qtys as iq3', function ($j) {
                                    $j->on('iq3.oiid', '=', 'oi.id')
                                        ->where('iq3.stageid', 3); //обеспечение товарами/материалами
                                })
                                ->where('oi.ordid', $rec->ordid)
                                ->where('refitmid', $itm->refitmid)
                                ->select('oi.ordid', 'oi.refitmid', 'iq1.qty as aprvqty', 'iq3.qty as bookqty')
                                ->first();


                            $restQty = $itm->qty;
                            foreach ($orditems as $oi) {
                                if ($restQty == 0) break;

                                $q = $oi->qty - $oi->aprvshipqty; //вместимость строки заказа
                                if ($q > $restQty) {
                                    $setQty = $restQty;
                                    $restQty = 0;
                                } else {
                                    $setQty = $q;
                                    $restQty = $restQty - $q;
                                }
                                if ($setQty > 0) {
                                    //переведем из согласованного обратно в планируемое
                                    $oi->plnshipqty = $oi->plnshipqty - $setQty;
                                    $oi->aprvshipqty = $oi->aprvshipqty + $setQty;
                                    $oi->save();
                                }
                            }
                        }
                    }

                    // Проверка влияния на запасы МОЛ -------------------------------------------
                    $forstock = $rec->doctype->formol;
                    if ($rslt_good and $forstock <> 0) {

                        //базовое условие отбора товара из запаса МОЛ
                        $sc = "staffid={$rec->mol_staffid}";

                        // !!! для МОЛ пока все жестко - не снижаем ниже 0 , без исключений!
                        $limit_stock = true;

                        foreach ($lst as $itm) {

                            if ($itm->subtypeid)
                                //значение для индивидуального типа
                                $itm_forstock = $itm->subtype->formol;
                            else
                                //значение для документа
                                $itm_forstock = $forstock;

                            $sc1 = '';
                            // если документ снижает товарный запас
                            // И нельзя снижать запас ниже 0
                            // то, отсечем записи с 0 кол-вом в mol_stocks
                            if ($itm_forstock < 0 and $limit_stock) {
                                $sc1 .= ' and qty > 0';
                            }
                            $stock = mol_stock::where('refitmid', $itm->refitmid)
                                ->whereRaw($sc . $sc1)
                                ->first();

                            if (!isset($stock)) {
                                $stock = new mol_stock([
                                    'refitmid' => $itm->refitmid,
                                    'ownorgid' => $rec->ownorgid,
                                    'staffid' => $rec->mol_staffid,
                                    'qty' => 0,
                                    'plnincqty' => 0,
                                    'plnoutqty' => 0,
                                ]);
                            }

                            if ($itm_forstock < 0) {
                                //уменьшение остатка на складе
                                if ($limit_stock) {
                                    //не можем опускаться ниже 0
                                    $stock_qty = $stock->qty ?? 0;  //mol_stock не содержит записи с нулевыми остатками
                                    if ($stock_qty < $itm->qty) {
                                        $rslt_good = false;

                                        $itmname = $itm->refitem->searchname;
                                        throw new \Exception( "Источник: " .$rec->mol->name
                                            . ". Недостаточный запас для позиции: $itm->refitmid  $itmname: Требуется: $itm->qty Доступно: $stock_qty");
                                    }
                                }
                                // перенсим план в факт
                                //снижаем кол-во планируемого расхода
                                $stock->plnoutqty = $stock->plnoutqty - $itm->qty;
                                //снижаем фактический остаток
                                $stock->qty = $stock->qty - $itm->qty;
                            }
                            if ($itm_forstock > 0) {
                                //поступление на склад
                                $stock->plnincqty = $stock->plnincqty - $itm->qty;
                                $stock->qty = $stock->qty + $itm->qty;
                            }
                            $stock->save();

                            if (1 == 0 and isset($rec->ordid)) {
                                //!!! нужно капитально переделать!

                                // видимо ранее считалось, что связь заказа с документом склада может быть только
                                // отпуск со склада.
                                // Но сейчас с заказом связаны разные типы документов склада:
                                // 14 - резервирование товаров под отпуск и производство
                                // 5 - производство товаров, необходимых заказу
                                // 3 - расходная накладная (реализация товара) - то, что ранее было единственным
                                // и они по-разному влияют на заказ


                                // --- устаревший алгоритм ---
                                //обновим поля OrdItems.plnshipqty и aprvshipqty
                                //учитывая, что в заказе может быть несколько записей об одном товаре,
                                // придется пройти по всем кандидатам с "ненулевой вместимостью"
                                $orditems = orditem::where('ordid', $rec->ordid)
                                    ->where('refitmid', $itm->refitmid)
                                    ->where('plnshipqty', '>', 0)
                                    ->whereraw('qty - aprvshipqty > 0')
                                    ->get();

                                $orditems = orditem::from('orditems as oi')
                                    ->leftJoin('oi_qtys as iq1', function ($j) {
                                        $j->on('iq1.oiid', '=', 'oi.id')
                                            ->where('iq1.stageid', 1); //лучше 2
                                    })
                                    ->leftJoin('oi_qtys as iq3', function ($j) {
                                        $j->on('iq3.oiid', '=', 'oi.id')
                                            ->where('iq3.stageid', 3); //обеспечение товарами/материалами
                                    })
                                    ->where('oi.ordid', $rec->ordid)
                                    ->where('refitmid', $itm->refitmid)
                                    ->select('oi.ordid', 'oi.refitmid', 'iq1.qty as aprvqty', 'iq3.qty as bookqty')
                                    ->first();


                                $restQty = $itm->qty;
                                foreach ($orditems as $oi) {
                                    if ($restQty == 0) break;

                                    $q = $oi->qty - $oi->aprvshipqty; //вместимость строки заказа
                                    if ($q > $restQty) {
                                        $setQty = $restQty;
                                        $restQty = 0;
                                    } else {
                                        $setQty = $q;
                                        $restQty = $restQty - $q;
                                    }
                                    if ($setQty > 0) {
                                        //переведем из согласованного обратно в планируемое
                                        $oi->plnshipqty = $oi->plnshipqty - $setQty;
                                        $oi->aprvshipqty = $oi->aprvshipqty + $setQty;
                                        $oi->save();
                                    }
                                }
                            }
                        }

                    }


                    if ($rslt_good) {

                        // Пересчитаем сумму документа
                        $rec->docsum = wrhdoclst::where('docid', $rec->id)->sum(db::raw("qty*price"));

                        $rec->save();
                        objlog::log_info(206, 1, 'Произведен пересчет товарных запасов', 5);

                        if (isset($rec->relwrhid)) {
                            //Если документ оперирует вторым складом, то при его утверждении
                            // будем создавать связанный документ:
                            $reldocid = wrhdoc::createDocFromDoc($rec->id);
                        }
                        if ($rec->doctype->need_predoc == 1) {
                            //Если документ имеет предшественника, то при его утверждении
                            // возможно нужно создавать связанный документ, который будет
                            // содержать разность в составах исходного и текущего документов:
                            $reldocid = wrhdoc::createDiffDocFromDoc($rec->id);
                        }

                        //Выполним действия после утверждения записи ---------------------------------------------
                        wrhdoc::on_sign($rec);
                        //----------------------------------------------------------------------------------------

                    } else {

                    }

                    DB::commit();

                    //освободим склад для работы других пользователей --------
                    wrh::unlock_by_user($rec->wrhid, $userid);
                    //--------------------------------------------------------

                    $sd['success'] = 'Документ утвержден';
                    objlog::log_info($this->sysobjid, $rec->id, 'Документ утвержден', 3);

                } else {
                    //не удалось захватить нужный склад для проведения изъятия материалов
                    $sd["error"] = 'Склад занят операцией другого пользователя! Попробуйте провести документ немного позднее.';
                    $route = route('wrhdocs.edit', $rec->id);
                    objlog::log_info($this->sysobjid, $rec->id, 'Неудачная попытка захвата склада', 2);

                }

//                } catch (\Throwable $e) {
//                    DB::rollback();
//                    notify()->error($e->getMessage());
//                    //throw $e;
//                    $sd["error"] = $e->getMessage();
//                    //dd($sd);
//
//                    $route = route('wrhdocs.edit', $rec->id);
//                }

                //});  //от DB:begintransaction function


            } else {
                $sd["error"] = 'У вас нет прав на утверждение(проведение) документа!';
                $route = route('wrhdocs.edit', $id);
                objlog::log_info($this->sysobjid, $id, 'Попытка утверждение(проведения) документа', 2);
            }

        }
        //dd($route, $sd);
        return redirect($route)->with($sd);
    }

    public
    function unsign($id)
    {
        $userid = \Auth::user()->id;
        $route = "";
        $sd = array();

        $rec = wrhdoc::find($id);
        if (isset($rec)) {

            if (usrsysright::isUserHasRightByCode($userid, 'wrhdocs.unsign')) {

                //Проверим, безопасно ли переводить документ в черновик
                if (wrhdoc::mayUnsignDoc($id)) {

                    $rec->docsigned = 0;
                    $rec->updated_by = $userid;
                    $rec->updated_at = now();
                    //$rec->save();

                    $route = route('wrhdocs.edit', $id);
                    $sd['success'] = 'С документа снят статус "Утвержден"';
                    objlog::log_info($this->sysobjid, $id, $sd['success'], 2);

                    //DB::unprepared('CALL recalc_stock()');
                    //или так:
                    DB::transaction(function () use ($rec) {
                        $forstock = $rec->doctype->forstock;

                        $force_stock_recalc = false;    // признак необходимости полного пересчета товарного запаса

                        $lst = wrhdoclst::where('docid', $rec->id)->get();
                        foreach ($lst as $itm) {

                            $stock = wrh_stock::where('refitmid', $itm->refitmid)
                                ->where('ownorgid', $rec->ownorgid)
                                ->where('wrhid', $rec->wrhid)
                                ->first();

                            if (isset($stock)) {
                                if ($forstock < 0) {
                                    $stock->plnoutqty = $stock->plnoutqty + $itm->qty;
                                    $stock->qty = $stock->qty + $itm->qty;
                                }
                                if ($forstock > 0) {
                                    $stock->plnincqty = $stock->plnincqty + $itm->qty;
                                    $stock->qty = $stock->qty - $itm->qty;
                                }
                                $stock->save();
                            } else {
                                // если не смогли найти запись с нужным складом/владельцем/товаром,
                                // то это признак ошибочного состояния wrh_stocks => нужно полностью пересчитать товарный запас
                                // Установим признак полного пересчета
                                $force_stock_recalc = true;
                            }

                            //2025-12-13 Учтем влияние на учет по МОЛ -------------------------
                            $forstock = $rec->doctype->formol;
                            if ($forstock <> 0) {

                                foreach ($lst as $itm) {

                                    $stock = mol_stock::where('refitmid', $itm->refitmid)
                                        ->where('ownorgid', $rec->ownorgid)
                                        ->where('staffid', $rec->mol_staffid)
                                        ->first();

                                    if (isset($stock)) {
                                        if ($forstock < 0) {
                                            $stock->plnoutqty = $stock->plnoutqty + $itm->qty;
                                            $stock->qty = $stock->qty + $itm->qty;
                                        }
                                        if ($forstock > 0) {
                                            $stock->plnincqty = $stock->plnincqty + $itm->qty;
                                            $stock->qty = $stock->qty - $itm->qty;
                                        }
                                        $stock->save();

                                    } else {
                                        // если не смогли найти запись с нужным складом/владельцем/товаром,
                                        // то это признак ошибочного состояния wrh_stocks => нужно полностью пересчитать товарный запас
                                        // Установим признак полного пересчета
                                        $force_stock_recalc = true;
                                    }
                                }
                            }

                            if (isset($rec->ordid)) {
                                //обновим поля OrdItems.plnshipqty и aprvshipqty
                                //учитывая, что в заказе может быть несколько записей об одном товаре,
                                // придется пройти по всем кандидатам с "ненулевой вместимостью"
                                $orditems = orditem::where('ordid', $rec->ordid)
                                    ->where('refitmid', $itm->refitmid)
                                    ->where('aprvshipqty', '>', 0)
                                    ->get();
                                $restQty = $itm->qty;
                                //dd($orditems,$restQty);
                                foreach ($orditems as $oi) {
                                    if ($restQty == 0) break;

                                    $q = $oi->aprvshipqty; //вместимость строки заказа
                                    if ($q > $restQty) {
                                        $setQty = $restQty;
                                        $restQty = 0;
                                    } else {
                                        $setQty = $q;
                                        $restQty = $restQty - $q;
                                    }
                                    if ($setQty > 0) {
                                        $oi->aprvshipqty = $oi->aprvshipqty - $setQty;
                                        $oi->plnshipqty = $oi->plnshipqty + $setQty;
                                        $oi->save();
                                    }
                                }
                            }
                        }

                        if ($force_stock_recalc) {
                            DB::unprepared('CALL recalc_stock()');
                        }

                        $rec->save();

                        objlog::log_info(204, $rec->id, 'Документ разутвержден', 3);

                        //Выполним действия после разутверждения записи ------------------------------------------
                        wrhdoc::on_unsign($rec);
                        //----------------------------------------------------------------------------------------


                        if ($rec->doctype->need_predoc == 1) {
                            //Если документ имеет предшественника, то при его утверждении
                            // возможно был создан связанный документ, который содержит
                            // разность в составах исходного и текущего документов.
                            //Теперь его нужно удалить:
                            $reldocid = wrhdoc::deleteDiffDocByPreDoc($rec->id);
                        }

                    });
                } else {

                    $sd["error"] = 'Данный документ уже нельзя вернуть в черновик, так как это нарушит целостность данных!';
                    $route = route('wrhdocs.edit', $id);
                    objlog::log_info($this->sysobjid, $id
                        , 'Попытка отмены проведения документа: ' . $sd["error"], 2);
                }

            } else {
                $sd["error"] = 'У вас нет прав на отмену проведения документа!';
                $route = route('wrhdocs.edit', $id);
                objlog::log_info($this->sysobjid, $id, 'Попытка отмены проведения документа', 2);
            }
        } else {
            $route = route('wrhdocs.index');
            $sd["error"] = 'Документ не найден!';
        }

        return redirect($route)->with($sd);
    }

    static public function make_diffdoc($id)
    {
        //Создание документа-разности от передачи между складами (Расход со склада, Прием на склад)
        //Переданный id должен соответствовать утвержденному документу, у которого есть "родительский" документ (pardocid)

        $doc2 = wrhdoc::find($id);
        if (!isset($doc2))
            return false;

        if (!isset($doc2->predocid))
            return false;

        if ($doc2->docsigned <> 1)
            return false;

        $doc1 = wrhdoc::find($doc2->predocid);

        if (!isset($doc1))
            return false;

        //"Родительский" документ должен быть утвержден
        if ($doc1->docsigned <> 1)
            return false;

        //Определим есть ли отличающийся состав
        $items = wrhdoclst::from('wrhdoclst as l2')
            ->join('wrhdoclst as l1', 'l1.id', 'l2.prelstid')
            ->where('l2.docid', $id)
            ->whereColumn('l2.qty', '<', 'l1.qty')
            ->select('l2.refitmid', db::raw("l1.qty-l2.qty as qty"))
            ->get();

        if (isset($items) and count($items) > 0) {

            DB::beginTransaction();
//            try {
            //Создадим документ
            $doc = new wrhdoc([
                'predocid' => $id,
                'ownorgid' => $doc2->ownorgid,
                'doctypeid' => 8,     //'перемещение товара (приход)'
                'wrhid' => 999,       //склад для потерь
                'boxid' => 999,       //отделение для потерь ??? или создать в соответствии с данными отделения для получения
            ]);
            $doc->save();

            //Сформируем состав
            foreach ($items as $itm) {
                $item = new wrhdoclst([
                    'docid' => $doc->id,
                    'refitmid' => $itm->refitmid,
                    'qty' => $itm->qty,
                ]);
                $item->save();
            }

//            } catch (\Throwable $e) {
//                DB::rollback();
//                $msg = $e->getMessage();
//                Log::error($msg);
//                //notify()->error($e->getMessage());
//                //throw $e;
//                $sd["error"] = $e->getMessage();
//
//                $route = route('wrhdocs.edit', $id)->with($sd);
//            }

            DB::commit();

            return redirect(route('wrhdocs.edit', $doc->id));
        }

    }

    public function make_doc5($srcdocid)
    {
        //Создание документа "5 - Акт списания на производство" для списания материалов, истраченных на производство товаров по заданному документу

        $src_doctypeid = 10;    // "Родительский" документ должен быть типа 10 - Накладная на прием товара (от производства)
        $chld_doctypeid = 5;    // 'Акт списания на производство'

        $err_route = route('wrhdocs.edit', $srcdocid);
        $sd = array();

        if (!isset($chld_doctypeid))
            return false;

        $srcdoc = wrhdoc::find($srcdocid);
        if (!isset($srcdoc)) {
            $sd["error"] = 'Указанный документ не найден!';
            return redirect($err_route)->with($sd);
        }

        //"Родительский" документ должен быть утвержден
        if ($srcdoc->docsigned <> 1) {
            $sd["error"] = 'Исходный документ должен быть утвержден!';
            return redirect($err_route)->with($sd);
        }

        //"Родительский" документ должен быть типа 10 - Накладная на прием товара (от производства)
        if ($srcdoc->doctypeid <> $src_doctypeid)
            return false;

        // Попробуем найти документ нужного типа, который ссылается на заданный документ как на родительский(Исходный)
        $doc = wrhdoc::where('doctypeid', $chld_doctypeid)
            ->where('predocid', $srcdoc->id)
            ->first();

        // Если целевой документ уже утвержден, то выходим
        if (isset($doc) and $doc->docsigned == 1)
            return false;

        //Определим есть ли позиции с рецептами в составе исходного документа, и все ли рецепты утверждены
        $cnts = wrhdoclst::from('wrhdoclst as dl')
            ->join('ri_compounds as c', 'c.id', 'dl.cmpndid')
            ->where('dl.docid', $srcdocid)
            ->whereNotNull('dl.cmpndid')
            ->select(db::raw("count(1) as itm_cnt"), db::raw("sum(c.docsigned) as actv_cnt"))
            ->first();
        //dd($cnts, $srcdocid, $cnts->actv_cnt, $cnts->itm_cnt);

        if ($cnts->actv_cnt > 0 and $cnts->actv_cnt == $cnts->itm_cnt) {
            $userid = \Auth::user()->id;

            DB::beginTransaction();
//            try {


            if (!isset($doc)) {
                //Создадим документ
                $doc = new wrhdoc([
                    'predocid' => $srcdocid,
                    'ownorgid' => $srcdoc->ownorgid,
                    'orgid' => $srcdoc->ownorgid,
                    'doctypeid' => $chld_doctypeid,  //'Акт списания на производство'
                    'wrhid' => $srcdoc->wrhid,       //склад
                    'boxid' => $srcdoc->boxid,       //отделение
                    'docdate' => $srcdoc->docdate,   //
                ]);
                $doc->save();
            }
            //dd('doc=',$doc);
            //$srcdoc->placeid = 231; // !!! ВРЕМЕННАЯ ЗАПЛАТКА !!! 231 - для производства от СпецЖБИ. Нужно придумать где взять placeid, или отказаться от placeid в ri_sup_prices!
            // 2025-03-15 добавил поле wrhdocs.placeid
            // но пока, на всякий случай, для спецЖБИ, если пусто
            //$srcdoc->placeid = (is_null($srcdoc->placeid))?231:$srcdoc->placeid;

            // Получим список необходимых материалов по максимальной оценке
            $items = wrhdoclst::from('wrhdoclst as dl')
                ->join('ri_compounds as c', 'c.id', 'dl.cmpndid')
                ->join('ri_cmpnd_items as ci', 'ci.cmpndid', 'c.id')
                ->leftjoin('ri_sup_prices as sp', function ($j) use ($srcdoc) {
                    $docdate = $srcdoc->docdate;
                    //dd($srcdoc->ownorgid, $srcdoc->placeid);
                    $j->on('sp.refitmid', '=', 'ci.refitmid')
                        ->where('sp.orgid', $srcdoc->ownorgid)
                        ->where('sp.placeid', '=', $srcdoc->placeid)
                        ->whereRaw("'{$docdate}' between sp.begdate and ifnull(sp.enddate, '{$docdate}')");
                })
                ->where('dl.docid', $srcdocid)
                ->where('c.docsigned', 1)
                ->select('ci.refitmid', db::raw("sum(ci.max_qty * dl.qty) as qty")
//                    , db::raw("min(sp.price) as price"))
                    , db::raw("max(ifnull(sp.price, 0)) as price"))
                ->groupby('ci.refitmid')
//                ->toSql();
                ->get();
//            dd($items);

            //пометим текущие записи состава обновляемого документа через updated_by=0
            wrhdoclst::where('docid', $doc->id)->update(['updated_by' => 0]);

            //Сформируем или обновим состав документа на списание материалов
            foreach ($items as $itm) {
                $item = wrhdoclst::where(['docid' => $doc->id, 'refitmid' => $itm->refitmid])->first();
                if (!isset($item)) {
                    $item = new wrhdoclst([
                        'docid' => $doc->id,
                        'refitmid' => $itm->refitmid,
                    ]);
                }
                $item->qty = $itm->qty;
                $item->price = $itm->price;
                $item->updated_at = now();
                $item->updated_by = $userid;
                $item->save();
                //dd($item->price);
            }
            //удалим незатронутые записи (как лишние)
            wrhdoclst::where(['docid' => $doc->id, 'updated_by' => 0])->delete();

//            } catch (\Throwable $e) {
//                DB::rollback();
//                $msg = $e->getMessage();
//                Log::error($msg);
//                //notify()->error($e->getMessage());
//                //throw $e;
//                $sd["error"] = $e->getMessage();
//
//                $route = route('wrhdocs.edit', $id)->with($sd);
//            }

            DB::commit();

            return redirect(route('wrhdocs.edit', $doc->id));
        } else {
            $sd["error"] = 'Не все используемые рецепты утверждены! Создание документа списания на производство невозможно.';
            return redirect($err_route)->with($sd);
        }

    }

    //создание документов на производство недостающих товарных позиций
    public function make_docs10()
    {
        $tgt_doctypeid = 10;
        $ret_route = route('wrhdocs.index');
        $sd = array();

        $userid = \Auth::user()->id;
        //$userid=78;
        //dd($userid);
        $userorgid = \Auth::user()->curorgid;
//        dd($userorgid);


        $itm_cnt = wrh_stock::from('wrh_stocks as s')
            // ограничение по организации пользователя
//            ->join('orgstaff as os', function ($join) use ($userid) {
//                $join->on('os.orgid', '=', 's.ownorgid')
//                    ->where("os.userid", $userid);
//            })
            ->where('s.ownorgid', $userorgid)
            ->where('s.qty', '<', 0)
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('ri_compounds as ric')
                    ->whereRaw('ric.refitmid = s.refitmid')
                    ->where('ric.active', 1)
                    ->whereRaw('curdate() between ric.begdate and ifnull(ric.enddate, curdate())');
            })
            ->count();
        //->tosql();
        //dd($itm_cnt);

        if ($itm_cnt > 0) {
            $ditms = wrh_stock::from('wrh_stocks as s')
                // ограничение по организации пользователя
//                ->join('orgstaff as os', function ($join) use ($userid) {
//                    $join->on('os.orgid', '=', 's.ownorgid')
//                        ->where("os.userid", $userid);
//                })
                ->where('s.ownorgid', $userorgid)
                ->where('s.qty', '<', 0)
                ->whereExists(function ($query) {
                    $query->select(DB::raw(1))
                        ->from('ri_compounds as ric')
                        ->whereRaw('ric.refitmid = s.refitmid')
                        ->where('ric.active', 1)
                        ->whereRaw('curdate() between ric.begdate and ifnull(ric.enddate, curdate())');
                })
                ->select('s.ownorgid', 's.wrhid', 's.boxid', db::raw("count(distinct s.refitmid) as qty"))
                ->groupBy('s.ownorgid', 's.wrhid', 's.boxid')
                ->get();
            //dd($ditms);

            $doc_cnt = 0;

            DB::beginTransaction();

            foreach ($ditms as $src) {
                $doc_cnt++;

                //dd($userid, $src->ownorgid);
                $disp_staffid = orgstaff::where('userid', $userid)
                    ->where('orgid', $src->ownorgid)
                    ->first()->id;
                //dd($userid, $disp_staffid);

                //определим макс.№ док-та для этого типа и владельца
                $docnum = wrhdoc::where('doctypeid', 10)
                        ->where('ownorgid', $src->ownorgid)
                        ->max('docnum') + 1;

                //dd($doc_cnt, $docnum);
                $doc = new wrhdoc([
                    'ownorgid' => $src->ownorgid,
                    'orgid' => $src->ownorgid,
                    'doctypeid' => $tgt_doctypeid,  //'Поступление товара от производства'
                    'wrhid' => $src->wrhid,       //склад
                    'boxid' => $src->boxid,       //отделение
                    'docnum' => $docnum,   //
                    'docdate' => date_format(date_create(), 'Y-m-d'),   //
                    'disp_staffid' => $disp_staffid,
                    'remarks' => 'восполнение недостающих запасов',
                    'created_at' => now(),
                    'updated_by' => $userid,
                ]);
                //dd($doc);
                $doc->save();

                if ($doc_cnt == 1)
                    $ret_route = route('wrhdocs.edit', $doc->id);

                $items = wrh_stock::from('wrh_stocks as s')
                    ->where('s.qty', '<', 0)
                    ->where('s.ownorgid', $src->ownorgid)
                    ->where('s.wrhid', $src->wrhid)
                    ->where('s.boxid', $src->boxid)
                    ->whereExists(function ($query) {
                        $query->select(DB::raw(1))
                            ->from('ri_compounds as ric')
                            ->whereRaw('ric.refitmid = s.refitmid')
                            ->whereRaw('ric.ownorgid = s.ownorgid')
                            ->where('ric.active', 1)
                            ->whereRaw('curdate() between ric.begdate and ifnull(ric.enddate, curdate())');
                    })
                    ->select('s.refitmid', db::raw("sum(-s.qty) as qty"))
                    ->groupBy('s.refitmid')
                    ->get();
                //->toSQL();
                //dd($src->ownorgid, $src->wrhid, $src->boxid, $items);

                //Сформируем состав документа на производство
                foreach ($items as $itm) {

                    // определим рецептуру изготовления
                    $cmpnd = ri_compound::where(['active' => 1
                        , 'refitmid' => $itm->refitmid
                        , 'ownorgid' => $src->ownorgid])
                        ->whereRaw('curdate() between begdate and ifnull(enddate, curdate())')
                        ->first();
                    if (isset($cmpnd)) {
                        $cmpndid = $cmpnd->id;
                        //dd($cmpndid);
                        $item = wrhdoclst::where(['docid' => $doc->id, 'refitmid' => $itm->refitmid])->first();
                        if (!isset($item)) {
                            $item = new wrhdoclst([
                                'docid' => $doc->id,
                                'refitmid' => $itm->refitmid,
                            ]);
                        }
                        $item->cmpndid = $cmpndid;
                        $item->qty = $itm->qty;
                        //$item->price = $itm->price;
                        $item->updated_at = now();
                        $item->updated_by = $userid;
                        //dd($item);
                        $item->save();
                    }

                }
            }
            DB::commit();


            //Сформируекм список Владельцев/Складовв/Отделений с недостающими товарами


            $sd["success"] = "Создан документ(ы) для восполнения недостачи {$itm_cnt} товарных позиций.";
        } else
            $sd["success"] = "Восполнение не требуется!";

        return redirect($ret_route)->with($sd);
    }

    public function recalc_stock()
    {
        $userid = \Auth::user()->id;
        $sd = array();

        //if (usrsysright::isUserHasRightByCode($userid, 'admin-global')) {
        if (usrsysright::isUserHasRightByCode($userid, 'wrhdocs.sign')) {
            DB::unprepared('CALL recalc_stock()');
            objlog::log_info($this->sysobjid, 0, 'Произведен пересчет остатков на складах и МОЛ.', 4);
            $sd['success'] = 'Произведен пересчет остатков на складах';

        } else {
            objlog::log_info($this->sysobjid, 0, 'Попытка пересчета остатков на складах и МОЛ. Нет права', 2);
            $sd['error'] = 'У Вас нет прав на выполнение этого действия!';
        }
        return redirect(route('wrhdocs.index'))->with($sd);

    }

    public
    function clone($id)
    {

        if (!isset($id))
            return redirect()->back()->with('error', 'Не задана исходная запись!');

        $userid = \Auth::user()->id;
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');

        if (!$usrrights['create'])
            return redirect()->back()->with('error', 'У вас нет права на создание записей!');

        $rslt = wrhdoc::clone($id);
        //dd($rslt);
        if ($rslt->err > 0)
            return redirect()->back()->with(['error' => $rslt->msg]);

        return redirect(route($this->sysobjcode . '.edit', $rslt->obj['id']))
            ->with(['success' => 'Вы находитесь в созданной копии']);
    }

    public function print($id)
    {
        if (!isset($id))
            return redirect()->back()->with('error', 'Не задана исходная запись!');

        $userid = \Auth::user()->id;

        $rec = wrhdoc::find($id);

        if (!isset($rec))
            return redirect()->back()->with('error', 'Не найдена указанная запись!');

        $rec->items = wrhdoclst::from('wrhdoclst as dl')
            ->join('refitems as ri', 'ri.id', 'dl.refitmid')
            ->join('unittypes as ut', 'ut.id', 'ri.unittypeid')
            ->where('dl.docid', $id)
            ->select('dl.*', 'ri.name as ri_name', 'ut.name as ut_name', 'ut.decimal_dgts', 'ri.grossweight as ri_grossweight')
            ->get();

        $data = new \stdClass();

        if ($rec->doctype->forstock == -1) {
            $data->src_signer_label = 'Отпустил';
            $data->src_signer_name = \Auth::user()->short_fio;

            $data->tgt_signer_label = 'Получил';
            $data->tgt_signer_name = '';

        } else {
            $data->src_signer_label = '';
            $data->src_signer_name = '';

            $data->tgt_signer_label = 'Получил';
            $data->tgt_signer_name = \Auth::user()->short_fio;
        }

        $view = "wrhdocs.print";
        return view($view,
            compact('rec', 'data'));
    }

    public function print_form($id, $formid)
    {
        if (!isset($id))
            return redirect()->back()->with('error', 'Не задана исходная запись!');

        $formid = 2;    // условно, соответствует Паспорту ЖБИ

        $userid = \Auth::user()->id;


        $rec = wrhdoc::find($id);
        //dd($rec->saleorg);
        //dd($rec->saleorg, $rec->saleorg->boss_name??$rec->saleorg->boss_fullname,$rec->saleorg->boss_postname, $rec->saleorg->boss_fullname);

        if (!isset($rec))
            return redirect()->back()->with('error', 'Не найдена указанная запись!');

        $rec->items = wrhdoclst::from('wrhdoclst as dl')
            ->join('refitems as ri', 'ri.id', 'dl.refitmid')
            ->join('unittypes as ut', 'ut.id', 'ri.unittypeid')
            ->where('dl.docid', $id)
            ->whereNotNull('ri.specification')  //С не пустой спецификацией
            ->select('dl.*', 'ri.name as ri_name', 'ut.name as ut_name', 'ut.decimal_dgts', 'ri.grossweight as ri_grossweight')
            ->get();

        $data = new \stdClass();

        /*if ($rec->doctype->forstock == -1) {
            $data->src_signer_label = 'Отпустил';
            $data->src_signer_name = \Auth::user()->short_fio;

            $data->tgt_signer_label = 'Получил';
            $data->tgt_signer_name = '';

        } else {
            $data->src_signer_label = '';
            $data->src_signer_name = '';

            $data->tgt_signer_label = 'Получил';
            $data->tgt_signer_name = \Auth::user()->short_fio;
        }*/

        $view = "wrhdocs.print_passports";
        return view($view,
            compact('rec', 'data'));
    }
}
