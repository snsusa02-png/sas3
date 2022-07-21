<?php

namespace App\Http\Controllers;

use App\objlog;
use App\order;
use App\orditem;
use App\refitem;
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

                } elseif ($item == 's_wrhid') {
                    $sc .= " and wd.wrhid={$val}";

                } elseif ($item == 's_statuscode') {
                    $sc .= " and wd.docsigned={$val}";

                } elseif ($item == 's_inpout') {
                    $sc .= " and dt.forstock={$val}";
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

        $data->s_wrhs = wrh::listUsed();
        $data->s_doctypes = wrhdoctype::listUsed();

        $data->timestatuses = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц', 5 => 'календарь'];
        $data->s_statuscodes = array('' => '-любой-', '0' => 'не утвержден', '1' => 'утвержден');
        $data->s_inpouts = array('' => '-любой-', '1' => 'приход', '-1' => 'расход', '0' => 'без изм.');

        // Пункты меню (сверху-справа) ---------------------------------
        $t_coll = collect();
        $t_coll->push((object)[
            'name' => 'Запас',
            'url' => route('reports.rep33'),
            'title' => 'Товарный запас на складах'
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
        if (usrsysright::isUserHasRightByCode($userid, 'admin-global')) {
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

        $items = wrhdoclst::from('wrhdoclst as dl')
            ->join('refitems as ri', 'ri.id', 'dl.refitmid')
            ->join('unittypes as ut', 'ut.id', 'ri.unittypeid')
            ->where('dl.docid', $id)
            ->select('dl.*', 'ri.name as ri_name', 'ut.name as ut_name', 'ut.decimal_dgts')
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
            $usrrights['save'] = false;
            $usrrights['delete'] = false;
        } else {
            $usrrights['docsign'] = false;
            $usrrights['docunsign'] = false;
        }

        //Потенциальное право на создание документа разногласий. Ниже (в blade) будет проверяться необходимость
        $usrrights['make_diffdoc'] = (isset($rec->predocid) and $rec->docsigned == 1);

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
        $doctype_params = wrhdoctype::select('need_relwrh', 'need_predoc', 'need_org')
            ->find($doctypeid);

        $rules = [
            "ownorgid" => "required",
//            "docnum" => "required",
            "docdate" => "required",
            "doctypeid" => "required",
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
        $rec->orgid = $request->get('orgid');
        $rec->respstaffid = $request->get('respstaffid');
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
        $res = wrhdoc::delete_by_id($id);
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

                $limit_stock = true; //todo: сделать преференцию "Не снижать запас ниже 0"

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

                        $stock = wrh_stock::where('refitmid', $itm->refitmid)
                            ->whereRaw($sc)
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

                        if ($force_stock_recalc){
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

    public function recalc_stock()
    {
        $userid = \Auth::user()->id;
        $sd = array();

        if (usrsysright::isUserHasRightByCode($userid, 'admin-global')) {
            DB::unprepared('CALL recalc_stock()');
            objlog::log_info($this->sysobjid, 0, 'Произведен пересчет остатков на складах.', 4);
            $sd['success'] = 'Произведен пересчет остатков на складах';

        } else {
            objlog::log_info($this->sysobjid, 0, 'Попытка пересчета остатков на складах. Нет права', 2);
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
        if ($rslt->err > 0)
            return redirect()->back()->with(['error' => $rslt->msg]);

        return redirect(route($this->sysobjcode . '.edit', $rslt->obj['id']))
            ->with(['success' => 'Вы находитесь в созданной копии']);
    }

    public
    function print($id)
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

        $view = "wrhdocs.print";
        return view($view,
            compact('rec', 'data'));
    }
}
