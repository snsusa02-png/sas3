<?php

namespace App\Http\Controllers;

use App\budget_itmsum;
use App\buildopertype;
use App\contract;
use App\contract_exe;
use App\docimpformat;
use App\equiprqst;
use App\equiprqst_expense;
use App\equiprqst_item;
use App\eritm_offer;
use App\eritm_supply;
use App\Events\notifyEvent;
use App\Exports\InvoicesExport;
use App\Imports\invoiceImport;
use App\invoice;
use App\invoice_item;
use App\mimetype;
use App\obj_approval;
use App\obj_link;
use App\obj_reader;
use App\objfile;
use App\objflag;
use App\objlog;
use App\opertype;
use App\org;
use App\orgacntperiod;
use App\orgplnpay;
use App\orgplnpay_item;
use App\pay_category;
use App\sysobj;
use App\Traits\Result;
use App\Traits\SearchDataTrait;
use App\Traits\UploadFileTrait;
use App\unittype;
use App\User;
use App\user_notice;
use App\userorg;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;

class InvoiceController extends Controller
{
    use UploadFileTrait;
    use SearchDataTrait;


    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 915;
        $this->sysobjcode = 'invoices';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
        $this->objcode = $this->sysobjcode;
    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['send2pay'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.send2pay');
        $usrrights['allorgs'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.allorgs');
        $usrrights['make_equiprqst'] = false;   //Возможность создать заявку по счету (с позициями)

        //право на просмотр записей для любых компаний, если они связаны с заявками на материалы
        $usrrights['allorgs_for_er'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.allorgs_for_er');

        //Перевыставление счета ---
        //$usrrights['create_child'] = false;
        $usrrights['make_child_bill'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.make_child_bill');
        $usrrights['make_child_upd'] = $usrrights['make_child_bill'];

        //право на добавление позиций в спр-к Номенклатуры
        $usrrights['add2refitems'] = usrsysright::isUserHasRightByCode_cached($userid, 'equiprqsts.add2refitems');

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
            $usrrights['send2pay'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
        }

        $usrrights['items.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'invoice_items.create');

        return $usrrights;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        $userid = \Auth::user()->id;
        $userorgid = \Auth::user()->curorgid;

        $usrrights = $this->setInterfaceRight(-1);

        if (!$usrrights['read']) {
            return view('home');
        }

        session([$this->objcode . '_pageno' => $request->page]);


        // - параметры поиска: массив из имени и значенния по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => ''
            , 's_timestatuscode' => ''
            , 's_plndate' => ''
            , 's_orgname' => ''
            , 's_doctypeid' => ''
            , 's_docnum' => ''
            , 's_categoryid' => ''
            , 's_usedsum_balance' => ''
            , 's_status' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if (1 == 0) {

                } elseif ($item == 's_status') {
                    $sc = $sc . " and pp.status ='" . mb_strtoupper($val) . "'";
                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------

        //по-старому ---------------
        //для совместимости со старым методом формированя условия отбора - инициализируем переменные поиска
        foreach ($search_params as $item => $val) {
            $$item = $val;
        }
        // --------------------------------------------------------------------


        $needSearch = false;
        foreach ($search_params as $p) {
            if (isset($p)) {
                $needSearch = true;
                break;
            }
        }

        //var_dump($search_params);
        $bSomeAgreeRight = true;

        if (!$usrrights['allorgs']) {
            //нет прав доступа ко всем счетам. Будем проверять полномочия

            $bAllOrgsOnlyER = $usrrights['allorgs_for_er'];


            if ($bSomeAgreeRight) {
                // --------------------------------------------------------------------
                // Ограничить доступ только представительством пользователя -----------
                $sc .= " and ( pp.ownorgid in (SELECT orgid FROM userorgs
                where userid=" . $userid . " and active=1 and now() between begdt and ifnull(enddt, now()) )";

                //2020-10-26 покажем также счета полученные для организации пользователя
//                $sc .= " or pp.for_orgid in (SELECT orgid FROM userorgs
//                where userid=" . $userid . " and active=1 and now() between begdt and ifnull(enddt, now()) )";

                if ($bAllOrgsOnlyER) {
                    //на материалы
                    $sc .= " or pp.categoryid=7";
                }
                $sc .= ")";
                // --------------------------------------------------------------------
            } else {
                //если у пользователя нет прав на ???, то отображать только записи о представляемой им СЕЙЧАС организации
                $sc .= " and pp.ownorgid=" . $userorgid;
            }
        }

        if ($needSearch) {

            if (strlen($s_doctypeid) > 0) {
                $sc = $sc . " and pp.doctypeid =" . $s_doctypeid;
            }
            if (strlen($s_ownorgid) > 0) {
                $sc = $sc . " and pp.src_orgid =" . $s_ownorgid;
            }

            if (strlen($s_timestatuscode) <> '') {
                if ($s_timestatuscode == -1) {
                    //заявки на вчера
                    $sc .= " and date(docdate)=date(date_add(now(), interval -1 day))";
                }
                if ($s_timestatuscode == 1) {
                    //заявки на сегодня
                    $sc .= " and date(docdate)=date(now())";
                }
                if ($s_timestatuscode == 2) {
                    //заявки на завтра
                    $sc .= " and date(docdate)=date(date_add(now(), interval 1 day))";
                }
                if ($s_timestatuscode == 3) {
                    //заявки на будущее (сегодня и далее)
                    $sc .= " and date(docdate)>=date(now())";
                }
                if ($s_timestatuscode == 4) {
                    //заявки в прошлом
                    $sc .= " and date(docdate)<date(now())";
                }
                if ($s_timestatuscode == 4) {
                    //заявки в прошлом
                    $sc .= " and date(docdate)<date(now())";
                }
                if ($s_timestatuscode == 5) {
                    //Точно по дате из календаря заявки в прошлом
                    if (strlen($s_plndate) > 0) {
                        $sc .= " and date(docdate)='" . $s_plndate . "'";
                    }
                }
            }

            if (strlen($s_orgname) > 0) {
                $sc = $sc . " and exists( select 1 from orgs as o where o.id=pp.orgid
                and o.name like '%" . mb_strtoupper($s_orgname) . "%')";
            }

            if (strlen($s_docnum) > 0) {
                $sc = $sc . " and pp.docnum like '" . mb_strtoupper($s_docnum) . "%'";
            }

            if (strlen($s_categoryid) > 0)
                $sc .= " and pp.categoryid={$s_categoryid}";

            if (strlen($s_usedsum_balance) > 0)
                if ($s_usedsum_balance == 0)
                    $sc .= " and pp.docsum = (pp.usedsum+ifnull(pp.aux_sum,0))";
                elseif ($s_usedsum_balance == 1)
                    $sc .= " and pp.docsum <> (pp.usedsum+ifnull(pp.aux_sum,0))";
                elseif ($s_usedsum_balance == 2)
                    $sc .= " and pp.docsum < (pp.usedsum+ifnull(pp.aux_sum,0))";
                elseif ($s_usedsum_balance == 3)
                    $sc .= " and pp.docsum > (pp.usedsum+ifnull(pp.aux_sum,0))";
        }

        $recs = invoice::from('invoices as pp')
            ->Join('orgs as so', function ($j) {
                $j->on('so.id', 'pp.src_orgid');
            })
            ->Join('orgs as to', function ($j) {
                $j->on('to.id', 'pp.tgt_orgid');
            })
            ->LeftJoin('orgs as oo', function ($j) {
                $j->on('oo.id', 'pp.ownorgid');
            })
            ->leftJoin('pay_categories as pc', function ($j) {
                $j->on('pc.id', 'pp.categoryid');
            })
            ->whereraw($sc)
            ->select('pp.*'
                , 'oo.name as ownorgname'
                , 'so.name as src_orgname'
                , 'to.name as tgt_orgname'
                //, 'fo.name as fororgname'
                , 'pc.name as category_name'
            //, db::raw("(select sum(fctpaysum) from orgplnpay_items as pi where pi.src_sysobjid=915 and pi.src_objid=pp.id) as fctpaysum")
            //, db::raw("(select max(fctpay_at) from orgplnpay_items as pi where pi.src_sysobjid=915 and pi.src_objid=pp.id) as fctpay_at")
            );

        //Сортировка пользователя ----------------------------------------
        //session()->put('sort_params_' . $this->objcode . '.index', []);
        $sort_params = session('sort_params_' . $this->objcode . '.index');

        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('pp.docdate', 'desc');
        }
//----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);
        //dd($recs);

//номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;


        if ($bSomeAgreeRight) {
            $usedorgs = org::from('orgs as o')
                ->whereraw(' o.id in (select distinct src_orgid from invoices)')
                ->select('o.id', 'o.name')
                ->get()
                ->pluck('name', 'id')
                ->toArray();

            $timestatuses = [-1 => 'вчера', 1 => 'сегодня', 2 => 'завтра', 3 => 'в  будущем', 4 => 'в прошлом', 5 => 'календарь'];

        } else {
            $usedorgs = null;
            $timestatuses = null;
        }

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->usedorgs = $usedorgs;
        $data->timestatuses = $timestatuses;
        //$data->doctypes = [1 => 'счет', 2 => 'УПД'];
        $data->categories = pay_category::usedAtInvoices();
        $data->usedsum_balances = [0 => '=', 1 => '<>', 2 => '<', 3 => '>'];

        //Варианты статусов. todo: Перейти на statusid
        $tmps = invoice::whereRaw("ifnull(status,'')<>''")
            ->select('status')
            ->distinct()->orderBy('status')
            ->get()
            ->pluck('status');
        $data->statuses = [];
        foreach ($tmps as $itm)
            $data->statuses[$itm] = $itm;

//dd($data);
//dd($usrrights);

        return view('invoices.index', compact(
            'recs', 'rec0'
            , 'search_params', 'sort_params'
            , 'usrrights', 'data'
            , 'usedorgs', 'timestatuses'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request, $pardocid = null)
    {
        return $this->edit($request, -1, $pardocid);
    }

    public
    function create_child(Request $request, $pardocid = null)
    {
        return $this->edit($request, -1, $pardocid, 'linked_bill');
    }

    public
    function create_child_upd(Request $request, $pardocid = null)
    {
        return $this->edit($request, -1, $pardocid, 'linked_upd');
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit(Request $request, $id, $pardocid = null, $pardocaction = null)
    {
        $userid = \Auth::user()->id;

        $pardocid = ($pardocid == 0) ? null : $pardocid;
        $pardocaction = ($pardocaction) ?? 'child_upd';

        if ($id == -1) {

            $pardoc = invoice::find($pardocid);
            if (!isset($pardoc)) {
                $pardoc = new invoice();
                $pardoc->ownorgid = \Auth::user()->curorgid;

                $doctypeid = 1;
                $docsum = null;

            } else {
                if ($pardocaction == 'child_upd') {
                    $doctypeid = 2; //УПД

                    //определим сумму остатка получения:
                    $docsum = $pardoc->docsum - invoice::where('pardocid', $pardocid)->sum('docsum');

                } elseif ($pardocaction == 'linked_bill') {
                    //перевыставленный счет

                    $doctypeid = 1; //счет
                    $pardoc->orgid = $pardoc->ownorgid;
                    $pardoc->ownorgid = null;
                    $pardoc->for_orgid = null;
                    $docsum = $pardoc->docsum;
                    //$pardoc->notes = $pardoc->notes . ' (перевыставлен)';

                } elseif ($pardocaction == 'linked_upd') {
                    //перевыставление УПД
                    //$pardoc - исходный УПД, который мы должны перевыставить
                    //$par_bill - переваставленный счет для счета, который является родительским для $pardoc :)))

                    // часть данных можем взять из перевыставленного счета, для счета, который является Родительским для текущего УПД
//                    dd($pardocid, $pardoc);
                    $par_bill = invoice::where(['pardocid' => $pardoc->pardocid, 'doctypeid' => 1])->first();
                    $pardoc = invoice::where(['pardocid' => $pardoc->pardocid, 'doctypeid' => 1])->first();
                    //dd($pardocid, $pardoc, $par_bill);

                    $doctypeid = 2; //УПД
                    $pardoc->orgid = $pardoc->orgid;
                    $pardoc->ownorgid = $pardoc->ownorgid;
                    $pardoc->for_orgid = null;
                    $pardoc->lnk_pardocid = $pardocid;
                    $docsum = $pardoc->docsum;
                    //$pardoc->notes = $pardoc->notes . ' (перевыставлен)';
                    //dd($pardoc);
                }
            }

            //Значения "по-умолчанию" для новой записи
            $tnotes = ($request->er_id) ? 'заявка ' . $request->er_id : null;
            $rec = new invoice([
                'id' => -1,
                'pardocid' => $pardoc->id,
                'doctypeid' => $doctypeid,   //1=счет, 2-УПД
                'ownorgid' => $pardoc->ownorgid,
                'orgid' => $pardoc->orgid,
                'for_orgid' => $pardoc->for_orgid,
                'docsum' => $docsum,
                'categoryid' => 7,  //Материалы
                'notes' => $pardoc->notes ?? $tnotes,
                'docdate' => $pardoc->docdate, //today(),
                'active' => 1,
                'created_by' => \Auth::user()->id,
                'lnk_pardocid' => $pardoc->lnk_pardocid,
            ]);
            //dd($pardocaction, $rec);
        } else
            $rec = invoice::find($id);


        if (!isset($rec))
            return redirect(route($this->objcode . '.index'));

        $rec->retURL = $request->get('returl');

        $usrrights = $this->setInterfaceRight($rec->id);
        $usrrights['files.create'] = true;  //право добавления образов файлов

//        if (!$usrrights['allorgs'] and !User::hasRightCodeInOrg($userid, 'invoices.read', $rec->ownorgid)) {
//            return redirect(route($this->objcode . '.index'))
//                ->with(['error' => 'У вас нет полномочий для работы со счетами для этой организации!']);
//        }

        //Проверим есть ли у пользователя право чтения и представление этой организации
        $user_orgs = userorg::lstUserActiveOrgs($userid);
        $mayWorkWithOrg = false;
        foreach ($user_orgs as $orgid => $orgname) {
            if ($rec->ownorgid == $orgid or $rec->for_orgid == $orgid) {
                $mayWorkWithOrg = true;
                break;
            }
        }
        //dd($usrrights['allorgs_for_er'], $rec );
        if (!$mayWorkWithOrg) {
            //нет права работать с счетами этой организации, проверим доп. права

            if ($usrrights['allorgs']) {
                //Все виды платежей
                $rec->pay_categories = pay_category::lstActive();

            } elseif ($usrrights['allorgs_for_er']) {
                //Ограничено только материалами
                $rec->pay_categories = [7 => 'материалы'];
            } else
                $rec->pay_categories = [];
            //$rec->pay_categories = pay_category::lstActive();

        } else {
            //Все виды платежей
            $rec->pay_categories = pay_category::lstActive();
        }

        $rec->opertypes = opertype::lstFor_cached(['active' => 1]);

        //2021-06-21 SNS переход на общие принципы
        $rec->pay_categories = pay_category::lstFor(['for_user' => $userid]);

        //преобразуем для нормальной работы <INPUT TYPE="DATE"...
        //$invoice->begdate = strftime('%Y-%m-%dT%H:%M:%S', strtotime($invoice->begdate));
        if (isset($rec->docdate))
            $rec->docdate = strftime('%Y-%m-%d', strtotime($rec->docdate));

        //сформируем комплексный идентификатор организации/контракта субподряда
        $rec->orgcontractid = ($rec->exe_orgid ?? '') . ':' . ($rec->exe_contractid ?? '');
        //dd($rec,$rec->orgcontractid);

        // Наши организации ограничены представительством пользователя --------
        //Cache::forget('user_ownorgs_' . $userid);
        if (1 == 1)
            $rec->ownorgs = Cache::remember('user_ownorgs_' . $userid, now()->addMinutes(5)
                , function () use ($userid, $usrrights) {
                    $list = objflag::from('objflags as f')
                        ->join('orgs as o', 'o.id', '=', 'f.objid')
                        ->select('o.id', 'o.name')
                        ->where('f.flagtypeid', 12)
                        ->where('f.sysobjid', 111)
                        ->where('o.active', 1);

                    if (!($usrrights['allorgs'] ?? false)) {
                        $list = $list->whereraw("o.id in (SELECT orgid FROM userorgs
                        where userid={$userid}  and active=1
                        and now() between begdt and ifnull(enddt, now()) )");
                    }

                    $list = $list->orderby('o.name')
                        ->get()->pluck("name", "id");

                    return $list;
                });

        //$rec->ownorgs = org::lstFor(['in_userorgs' => $userid, 'flagtypeid' => 12]);
        //dd($rec->ownorgs);

        //$rec->doctypes = [1 => 'счет', 2 => 'УПД'];
        $rec->doctypes = invoice::doctypes();

        //сформируем комплексный идентификатор организации/контракта субподряда
        $rec->orgcontractid = ($rec->exe_orgid ?? '') . ':' . ($rec->exe_contractid ?? '');
        //dd($rec,$rec->orgcontractid);

        // --------------------------------------------------------------------

        // Дочерние документы (УПД) -------------------------------------------
        $rec->child_docs = invoice::from('invoices as inv')
            ->where('inv.pardocid', $rec->id)
            ->where('inv.doctypeid', 2)//Только УПД
            ->select('inv.*')
            ->get();
        //dd($rec->child_docs);
        // --------------------------------------------------------------------

        // Дочерние документы (Счета) -------------------------------------------
        $rec->child_bills = invoice::from('invoices as inv')
            ->join('orgs as oo', 'oo.id', 'inv.ownorgid')
            ->where('inv.pardocid', $rec->id)
            ->where('inv.doctypeid', 1)//Только счета
            ->select('inv.*', 'oo.name as ownorg_name')
            ->get();
        //dd($rec->child_docs);

        // Связанные (дочерние) документы  ------------------------------------------
//        $rec->linked_parent_docs = obj_link::from('obj_links as ol')
//            ->where(['sysobjid' => 915, 'lnksysobjid' => 915, 'lnkobjid' => $rec->id])
//            ->select('ol.lnksysobjid as sysobjid', 'ol.lnkobjid as id', db::raw("'parent' as name"), db::raw("'родитель' as linktypename"))
//            ->get();

        // Связанные (дочерние) документы  ------------------------------------------
        $rec->linked_docs = obj_link::from('obj_links as ol')
            ->leftJoin('objlinktypes as olt', 'olt.id', 'ol.linktypeid')
            ->where(['sysobjid' => 915, 'objid' => $rec->id, 'lnksysobjid' => 915])
            ->select('ol.sysobjid', 'ol.lnkobjid as id', 'ol.name', 'olt.name as linktypename')
            ->get();
        //dd( $rec->linked_child_docs);


        // --------------------------------------------------------------------

        //$usrrights = $this->setInterfaceRight($id);
        $rec->lock_reason = '';

        //информация о состоянии оплаты
        $rec->orgplnpay_items = orgplnpay_item::from('orgplnpay_items as opi')
            ->leftjoin('users as u1', 'u1.id', 'opi.agr1_by')
            ->leftjoin('users as u2', 'u2.id', 'opi.agr2_by')
            ->where([
                'src_sysobjid' => $this->sysobjid,
                'src_objid' => $rec->id,
            ])
            ->select('opi.*'
                , 'u1.name as agr1_username'
                , 'u2.name as agr2_username'
            )
            ->orderBy('id', 'desc')
            ->get();
        //dd($rec->orgplnpay_items);


        // Оплачиваем только Счета, и если они на материалы
        //if ($rec->doctypeid <> 1 or $rec->categoryid == 7)
        // Оплачиваем только Счета (2021-03-24 SNS - любой категории, так как сейчас счет ВСЕГДА оплачивается ЦЕЛИКОМ)
        if ($rec->doctypeid <> 1)
            $usrrights['send2pay'] = false;


        //если существует связ. запись в плане платежей, то блокировать изменение, удаление
        if (isset($rec->orgplnpay_items) and count($rec->orgplnpay_items) > 0) {
            $usrrights['save'] = false;
            $usrrights['delete'] = false;

            //Проверим возможность отправки в план платежей
            // - нельзя - если есть активная запись в плане платежей (неоплаченная)
            //          - если нет такой записи, то проверим сумму оплаты - можно если оплата меньше суммы документа
            $active_orgplnpay_items = $rec->orgplnpay_items->filter(function ($item, $key) {
                return !isset($item->fctpaysum);
            });

            if (count($active_orgplnpay_items) > 0) {
                //нельзя отправить если счет уже находится в плане платежей и еще не оплачен
                $usrrights['send2pay'] = false;

            } else {
                // можно - если находится в плане платежей, но уже указана сумма оплаты
                // и общая сумма оплаты меньше чем сумма счета

                $totfctpaysum = $rec->orgplnpay_items->sum('fctpaysum');
                $usrrights['send2pay'] = ($totfctpaysum < $rec->docsum);
                //dd($totfctpaysum, $usrrights['send2pay']);
            }


            $rec->lock_reason = 'Заблокирован, так как отправлен на оплату';
        }

        //Запретим изменение/удаление/оплату если учетный период закрыт:
        if ($rec->id !== -1 and !orgacntperiod::isopen($rec->ownorgid, $rec->docdate)) {
            $usrrights['save'] = false;
            $usrrights['delete'] = false;
            $usrrights['send2pay'] = false;
            $rec->lock_reason = 'Заблокирован, так как учетный период закрыт';
        }


        //dd($rec->orgplnpay_items);

        if ($rec->doctypeid == 1) {
            //Счет ---------

            // Собственный состав документа - от импорта счета в формате Excel -------------------------
            $rec->self_items = invoice_item::from('invoice_items as ii')
                ->leftjoin('unittypes as ut', 'ut.id', 'ii.unittypeid')
                ->leftjoin('refitems as ri', 'ri.id', 'ii.refitmid')
                ->where('invoiceid', $rec->id)
                ->select('ii.*', 'ri.name as refitmname'
                    , db::raw("ifnull(ut.decimal_dgts,3) as ut_dec_dgts")
                    , DB::raw("(select group_concat(eritmid SEPARATOR ';') from eritm_offers as ofr
                        where ofr.invitmid=ii.id) as lst_eritmid")
                )
                ->orderBy('ordr')
                ->get();
            //dd( $rec->self_items);
            //------------------------------------------------------------------------------------------

            // Использование счета в заявках на материалы -------------------------
            $rec->equiprqst_items = eritm_offer::from('eritm_offers as ofr')
                ->join('equiprqst_items as eri', 'eri.id', 'ofr.eritmid')
                ->join('equiprqsts as er', 'er.id', 'eri.rqstid')
                ->where('ofr.invoiceid', $rec->id)
                ->select('ofr.*', 'eri.rqstid', 'eri.unit', 'er.stageid as rqst_stageid'
                    , db::raw("ifnull(ofr.itmname,eri.itmname) as itmname"))
                ->get();
            //dd($rec->equiprqst_items);
            // --------------------------------------------------------------------


            // Доступные договоры (поставки)---------------------------------------------
            $rec->contracts = contract::lstFor([
                'ownorgid' => $rec->ownorgid,
                'orgid' => $rec->orgid,
                'categoryid' => 2,  //расходный
                'for_userid' => $userid,
                'actual' => 1,
            ]);
            //dd($rec->contracts);
            //--------------------------------------------------------------------------

            //список возможных договоров в зависимости от вида работ -------------------
            $rec->orgcontracts = contract::list_orgcontracts_for_buildopertypeid($rec->buildopertypeid);
            //dd($rec->contracts);
            //--------------------------------------------------------------------------


            //проверим - соответствует ли сумма документа сумме позиций. И исправим, если нужно ---------
            $usedsum = 0;
            foreach ($rec->equiprqst_items as $item) {
                $usedsum += $item->ord_sum;
            }

            if (round(floatval($rec->usedsum) - $usedsum, 2) != 0) {
                invoice::where('id', $rec->id)->update(['usedsum' => $usedsum]);
                objlog::log_info($this->sysobjid, $rec->id,
                    "Авто-коррекция суммы документа по сумме использования (" . $rec->usedsum . " -> {$usedsum})", 5);
                $rec->usedsum = $usedsum; //для интерфейса
            }
            //--------------------------------------------------------------------------------------------

            //право на перевыставление счета -------------------------------------------------------------
            $usrrights['make_child_upd'] = false;
            // помимо базового права счет(!) должен быть создан, иметь категорию "Материалы для реализации"

//            if ($userid == 12)
//                var_dump($usrrights['make_child_bill'], $rec->doctypeid, $rec->categoryid);

            $usrrights['make_child_bill'] = ($usrrights['make_child_bill']
                and $rec->id <> -1
                and $rec->doctypeid == 1
                and $rec->categoryid == 11
            );
            // и не должно быть уже перевыставленного счета
            if (1 == 0) {
                //2021-05-25 Нужно 1 счет перевыставлять на разные компании! (по разным позициям)
                $usrrights['make_child_bill'] = ($usrrights['make_child_bill']
                    and ((invoice::where([
                                'pardocid' => $rec->id,
                                'doctypeid' => 1,
                            ])->count() ?? 0) == 0));
            }

            //dd($usrrights['make_child_bill'] );

            //так как пользователь открыл счет, то уберем колокольчик уведомляющий о том, что этот счет перевыставлен
            // (если он есть)
            user_notice::Remove(91502, route('invoices.edit', $rec->id), $userid);


            //Право/Возможность создать заявку на основе счета /Категория 12 - Материалы(срочно)
            // - если есть состав счета и в нем не все позиции связаны с позициями заявки на материалы
            // Остальные проверки сделаем внутри обработчика
            if ($rec->categoryid == 12) {

                $cnt = invoice_item::where(['invoiceid' => $rec->id])->whereNull('eritmid')->count();

                $usrrights['make_equiprqst'] = ($cnt != 0);


                //Текущий остаток бюджета на выбранный вид работ по статье материалы
                $ttt = budget_itmsum::rest_info_low($rec->buildopertypeid, $rec->ownorgid, 21);
                $rec->budget_rest = $ttt[0]->fctopersum ?? '';
            }

        } elseif ($rec->doctypeid == 2) {
            //УПД ---

            if ($rec->id <> -1) {
                //сформируем список позиций заявок, связанных с этим УПД --------------
                $rec->items = eritm_offer::from('eritm_offers as ofr')
                    ->join('equiprqst_items as eri', 'eri.id', 'ofr.eritmid')
                    ->join('equiprqsts as er', 'er.id', 'eri.rqstid')
                    ->leftjoin('unittypes as ut', 'ut.id', 'eri.unittypeid')
                    ->where('ofr.invoiceid', $rec->pardocid)
                    ->select('ofr.*', 'eri.rqstid', 'eri.unit', 'er.stageid as rqst_stageid'
                        , db::raw("ifnull(ofr.itmname,eri.itmname) as itmname")
                        , 'eri.unittypeid'
                        , db::raw("ifnull(ut.decimal_dgts,3) as ut_decimal_dgts")
                        , db::raw("(select sum(get_qty) from eritm_supplies as sup
                        where sup.eritmid=ofr.eritmid and sup.offerid=ofr.id
                        and sup.invoiceid != " . $rec->id . ") as otherget_qty")
                        , db::raw("(select ifnull(sum(get_qty),0) from eritm_supplies as sup
                        where sup.eritmid=ofr.eritmid and sup.offerid=ofr.id
                        and sup.invoiceid=" . $rec->id . ") as preget_qty")
                        , db::raw("(select sum(doc_qty) from eritm_supplies as sup
                        where sup.eritmid=ofr.eritmid and sup.offerid=ofr.id
                        and sup.invoiceid=" . $rec->id . ") as predoc_qty")
                    )
                    ->orderby('eri.rqstid')
                    ->get();
                //dd($rec->items);

                //список доп-затрат, связанных с этим УПД
                $rec->expenses = equiprqst_expense::from('equiprqst_expenses as exp')
                    ->where('exp.upd_id', $rec->id)
                    ->get();
                $rec->totExtraExpSum = $rec->expenses->sum('expense_sum');


                //проверим - соответствует ли сумма документа сумме позиций. И исправим, если нужно ---------
                $usedsum = 0;
                foreach ($rec->items as $item) {
                    $usedsum += round($item->ord_price * $item->preget_qty, 2);
                }

                //учтем доп затраты
                $usedsum += $rec->totExtraExpSum;

                if (round(floatval($rec->usedsum) - $usedsum, 2) != 0) {
                    invoice::where('id', $rec->id)->update(['usedsum' => $usedsum]);
                    objlog::log_info($this->sysobjid, $rec->id,
                        "Авто-коррекция суммы документа по сумме использования (" . $rec->usedsum . " -> {$usedsum})", 5);
                    $rec->usedsum = $usedsum; //для интерфейса
                }
                //--------------------------------------------------------------------------------------------

                //список исполнения контракта, связанных с этим УПД
                $rec->contract_exes = contract_exe::from('contract_exes as exe')
                    ->where('exe.rsn_sysobjid', $this->sysobjid)
                    ->where('exe.rsn_objid', $rec->id)
                    ->get();
                //dd($rec->contract_exes );
            }

            //право на перевыставление УПД --------------------------------------------------------------------
            // помимо базового права УПД должен быть создан, и его родительский Счет должен иметь категорию "Материалы для реализации"
            $usrrights['make_child_bill'] = false;
            $usrrights['make_child_upd'] = ($usrrights['make_child_upd'] and $rec->id <> -1);

            //если право осталось, проверим характеристики родительского счета
            if ($usrrights['make_child_upd']) {
                $bill = invoice::where(['id' => $rec->pardocid, 'doctypeid' => 1])->first();
                if (isset($bill)) {
                    //он должен быть на категорию "Материалы" для реализации
                    if ($bill->categoryid <> 11)
                        $usrrights['make_child_upd'] = false;
                } else
                    $usrrights['make_child_upd'] = false;
            }


            // и не должно быть уже перевыставленного УПД
            //? как хранить связь между УПД - в УПД pardocid уже занят ссылкой на счет
            if ($usrrights['make_child_upd']) {
                if (obj_link::where([
                        'sysobjid' => 915,
                        'objid' => $rec->id,
                        'lnksysobjid' => 915,
                        'linktypeid' => 91511,    //тип сязи = перевыставленный документ
                    ])->count() > 0)
                    $usrrights['make_child_upd'] = false;

            }
            //dd($usrrights['make_child_upd'] );

            //так как пользователь открыл УПД, то уберем его колокольчик уведомляющий о том, что этот УПД перевыставлен
            // (если он есть)
            user_notice::Remove(91504, route('invoices.edit', $rec->id), $userid);
            //-------------------------------------------------------------------------------------------------

        }

        //objlog::log_info($this->sysobjid, $rec->id, "Открытие документа", 5);
        obj_reader::addOrUpdateStat($this->sysobjid, $rec->id, $userid);

        return view($this->objcode . '.edit', compact('rec', "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        //
        $userid = \Auth::user()->id;

        $ownorgid = $request->get('ownorgid');

        $bNewRec = false;

        //Проверим права пользователя - может ли он что-то делать в плане этой организации?
        if (User::hasRightCodeInOrg($userid, 'invoices.update', $ownorgid)
            or usrsysright::isUserHasRightByCode($userid, 'invoices.allorgs_for_er')) {

            $doctypeid = $request->get('doctypeid');
            $t_docname = ($doctypeid == 1) ? 'счета' : 'УПД';
            $categoryid = $request->get('categoryid');

            $messages = [
                'doctypeid.required' => 'Укажите вид документа',
                'orgid.required' => 'Укажите поставщика',
                'ownorgid.required' => 'Укажите покупателя со стороны холдинга',
                'for_orgid.required' => 'Укажите заказчика со стороны холдинга',
                'docnum.required' => 'Укажите № ' . $t_docname,
                'docdate.required' => 'Укажите дату ' . $t_docname,
                'docdate.after' => "Дата {$t_docname} не может быть позднее 30 дней назад",
                'docdate.before' => "Дата {$t_docname} не может быть в будущем",
                'docsum.required' => 'Укажите сумму ' . $t_docname,
                'categoryid.required' => 'Укажите категорию ' . $t_docname,
                'notes.required' => 'Укажите основание ' . $t_docname,
                //'buildopertypeid.required' => 'Укажите вид работ',
                'orgcontractid.required' => 'Укажите подрядчика и договор',
            ];

            $rules = [
                "doctypeid" => "required",
                "orgid" => "required",
                "ownorgid" => "required",
                //"for_orgid" => "required",
                "docnum" => "required",
//                "docdate" => "required"
//                    . "|before:" . Carbon::tomorrow()->format('Y-m-d')
//                    . "|after:" . today()->modify('-31 day')->format('Y-m-d'),
                "docdate" => "required"
                    . "|before:" . Carbon::tomorrow()->format('Y-m-d'),
                "docsum" => "required",
                "categoryid" => "required",
                "notes" => "required",
                'docs' => 'max:10240'
            ];

            //Категория = 12 - Материалы(Срочно), 8 - Транспорт
            if (in_array($request->get('categoryid'), [12, 8])) {
                //$rules['buildobjid'] = "required";
                //$rules['buildopertypeid'] = "required";
                $rules['orgcontractid'] = "required";
            }

            $request->validate($rules, $messages);

            $mess = "";
            if ($id == -1) {
                $bNewRec = true;
                $rec = new invoice([
                    "ownorgid" => $ownorgid,
                    "docdate" => $request->get('docdate'),
                    "docsum" => -1,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $mess = "Запись создана";
            } else {
                $bNewRec = false;
                $rec = invoice::find($id);
                $mess = "Запись обновлена";
            }

            //проверка на дубль Поставщик/Плательщик/Тип/Дата/Номер
            $doctypeid = $request->get('doctypeid');
            $orgid = $request->get('orgid');
            $docdate = $request->get('docdate');
            $docnum = $request->get('docnum');

            $prm = new \stdClass();
            $prm->id = $rec->id;
            $prm->doctypeid = $doctypeid;
            $prm->ownorgid = $ownorgid;
            $prm->orgid = $orgid;
            $prm->docnum = $docnum;
            $prm->docdate = $docdate;

            $rules = [
                "ttt" => [
                    function ($attribute, $value, $fail) use ($prm) {
                        //
                        $cnt = invoice::where([
                            'ownorgid' => $prm->ownorgid,
                            'orgid' => $prm->orgid,
                            'doctypeid' => $prm->doctypeid,
                            'docnum' => $prm->docnum,
                            'docdate' => $prm->docdate,
                        ])
                            ->where('id', '<>', ($prm->id ?? -1))
                            ->count();
                        //dd($cnt);
                        if ($cnt > 0) {
                            $fail("Документ с такими реквизитами уже существует (Поставщик/Плательщик/Тип/Дата/Номер)!");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);

            $pre = new \stdClass();
            $pre->docdate = $rec->docdate;
            $pre->docsum = $rec->docsum;

            $rec->pardocid = $request->get('pardocid');
            $rec->doctypeid = $doctypeid;
            $rec->ownorgid = $ownorgid;
            $rec->orgid = $orgid;
            $rec->docdate = $docdate;
            $rec->docnum = $docnum;

            $rec->contractid = $request->get('contractid');

//            $rec->buildobjid = $request->get('buildobjid');
//            $rec->buildopertypeid = $request->get('buildopertypeid');

            $orgcontractid = $request->get('orgcontractid');
            $tids = explode(':', $orgcontractid);
            $rec->exe_orgid = $tids[0];   //исходный заказчик (подрядчик)
            $rec->exe_orgid = ($rec->exe_orgid == '') ? null : $rec->exe_orgid;

            $rec->exe_contractid = $tids[1] ?? null;   //договор подряда/бюджета
            $rec->exe_contractid = ($rec->exe_contractid == '') ? null : $rec->exe_contractid;
//dd($rec->exe_orgid,$rec->exe_contractid);

            $rec->for_orgid = $request->get('for_orgid');
            $rec->enddate = $request->get('enddate');
            $rec->docsum = $request->get('docsum');
            //$rec->usedsum = $request->get('usedsum');
            $rec->aux_sum = $request->get('aux_sum');   //сумма доп затрат (для счета)
            $rec->aux_descript = mb_substr($request->get('aux_descript'), 0, 45);

            $rec->categoryid = $request->get('categoryid');
            $rec->plngetwrkdays = $request->get('plngetwrkdays');   //Примерный срок получения в рабочих днях от даты оплаты

            $rec->notes = mb_substr($request->get('notes'), 0, 160);

            $rec->active = $request->get('active', 0);
            $rec->updated_by = $userid;
            $rec->updated_at = now();
            $rec->save();
//
            $returl = $request->get('returl') ?? route('invoices.index');

            objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
            connectify('success', '-', $mess);

            //сохраним связь между документами
            $lnk_pardocid = $request->get('lnk_pardocid');
            if (isset($lnk_pardocid)) {
                //связь Родитель - Наследник
                obj_link::addOrUpdateSingleWithParams(915, $lnk_pardocid, 915, $rec->id,
                    [
                        'name' => 'УПД №' . $rec->docnum,
                        'linktypeid' => 91511,
                    ]);
                //связь Родитель - Наследник
                obj_link::addOrUpdateSingleWithParams(915, $rec->id, 915, $lnk_pardocid,
                    [
                        'name' => 'УПД №' . invoice::find($lnk_pardocid)->docnum ?? '',
                        'linktypeid' => 91510,
                    ]);
            }

            if ($id == -1 and isset($rec) and $rec->doctypeid == 1) {
                event(new notifyEvent('invoices.new_record', $this->sysobjid, $rec->id, $userid, 'Зарегистрирован новый счет ' . $rec->ownorg->name, '-'));
            }

            if ($request->hasfile('docs')) {

                $maxFileSize = 10 * 1024 * 1024;    //10MB
                $sysobjid = $this->sysobjid;
                $objid = $rec->id;
                $folder = 'files/' . $sysobjid . '/' . $objid . '/';


//            $refitem = machine::find($refitmid);
//            $product_slug = Str::slug($refitem->name);


                foreach ($request->docs as $file) {

                    $filesize = $file->getSize();
                    if ($filesize < $maxFileSize) {

                        // Make a image name based on user name and current timestamp
                        //$name = str_slug($request->input('name')).'_'.time();

                        //имя файла без расширения
                        // - либо по оригинальному имени файла
                        $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);

                        // - либо имя файла делаем производным от названия продукта
                        //$name = $product_slug;

                        // - либо комбинируем
                        //$name = $product_slug . '_' . str_slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME));

                        $extension = $file->getClientOriginalExtension();
                        $fullname = $name . '.' . $extension;

                        $mimetypeid = mimetype::where('extension', $extension)->select('id')->first()->id ?? null;

                        if (isset($mimetypeid)) {
                            // Make a file path where image will be stored [ folder path + file name + file extension]
                            $filePath = $folder . $fullname;

                            $fileuri = Storage::disk('public')->getAdapter()
                                ->applyPathPrefix($filePath);

                            if (file_exists($fileuri)) {
                                $name = $name . '_' . time();
                                $fullname = $name . '.' . $extension;
                                $filePath = $folder . $fullname;
                            }

                            // Загружаем файл на сервер
                            $this->uploadOne($file, $folder, 'public', $fullname);

                            $fileuri = Storage::disk('public')->getAdapter()
                                ->applyPathPrefix($filePath);

                            if (file_exists($fileuri)) {
                                // Save to table
                                $rec = new objfile();
                                $rec->sysobjid = $sysobjid;
                                $rec->objid = $objid;
                                $rec->sysfiletype_id = 4;   //todo: заплатка. Нужно разобраться почему этот параметр обязателен
                                $rec->mimetypeid = $mimetypeid;
                                $rec->publicfilename = $fullname;
                                $rec->systemfilename = $filePath;
                                $rec->filesize = $filesize;
                                $rec->notes = '';
                                $rec->doctypeid = ($doctypeid == 1) ? 217 : 218;
                                //$rec->ordr = ++$ordr;
                                $rec->save();
                            }
                        } else {
                            //неизвестный тип файла
                        }
                    } else {
                        //превыщен допустимый размер файла
                    }
                }
                //echo "Upload Successfully";
            }

            if ($id == -1) {
                //если создаем новую запись, то добавим текущего пользователя в инициаторы
                obj_reader::addOrUpdate($this->sysobjid, $rec->id, $userid, 0, 1);  //1-инициатор
            }
            //----------------------------------------------------------------------------------------------------------

            // распределим доп.затраты по позициям счета, использованным в заявках ------------------------------------
            $k = ($rec->usedsum > 0)
                ? $rec->aux_sum / $rec->usedsum
                : 0;

            eritm_offer::where('invoiceid', $rec->id)
                ->update(['ord_auxsum' => db::raw("round(ord_sum * {$k},6)")]);

            //рассчитаем фактическую цену позиции ( с учетом доп. затрат)
            eritm_offer::where('invoiceid', $rec->id)
                ->where('ord_qty', '>', 0)
                ->update(['ord_fctprice' => db::raw("round((ord_sum+ord_auxsum) / ord_qty,6)")]);
            //---------------------------------------------------------------------------------------------------------


            $id = $rec->id; //для совместимости в возврате

            //Сгенерим события -----------------------------------------------------------------------------------------

            //если Счет, и на "Материалы для реализации" и дочерний счет еще не сделан, то создадим событие ----------
            if ($rec->doctypeid == 1
                and $rec->categoryid == 11
                and (invoice::where([
                        'pardocid' => $rec->id,
                        'doctypeid' => 1,
                    ])->count() == 0))
                event(new notifyEvent('invoices.need_make_child_bill', $this->sysobjid, $rec->id, $userid));
            //--------------------------------------------------------------------------------------------------------

            //если счет (doctypeid==1) на категорию "7-Материалы" и имеет родителя, то видимо это перевыставленный счет
            if ($rec->doctypeid == 1
                and $rec->categoryid == 7
                and isset($rec->pardocid)
            ) {
                //уведомим регистратора родительского счета о создании перевыставленного счета
                event(new notifyEvent('invoices.child_bill_maked', $this->sysobjid, $rec->pardocid, $userid));
            }
            //--------------------------------------------------------------------------------------------------------

            //если УПД, и к счету на "Материалы для реализации", то создадим событие ---------------------------------
            if ($rec->doctypeid == 2
                and $rec->pardoc->categoryid == 11) {
                event(new notifyEvent('invoices.need_make_child_upd', $this->sysobjid, $rec->id, $userid));
            }
            //--------------------------------------------------------------------------------------------------------


            //зачистка связанного кэша -----------------------------
            Cache::forget('pay_categories' . '_usedAtInvoices');
            //------------------------------------------------------

        } else {
            //не имеет прав
            objlog::log_info($this->sysobjid, $id, "Попытка изменения записи без права на это!", 4);
            return redirect(route($this->objcode . '.index'));
        }

        if ($bNewRec) {
            //if (isset($returl))
            //return redirect($returl);
            return redirect(route($this->objcode . '.edit', $id));
        } elseif ($rec->doctypeid == 2) {
            return redirect(route($this->objcode . '.edit', $rec->pardocid));
        } else {
            if (isset($returl))
                return redirect($returl);

            $pageno = session($this->objcode . '_pageno');
            return redirect(route($this->objcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
        }
    }


    public
    function update_enddate(Request $request, $id)
    {
        //изменение даты окончания действия счета
        if (!isset($id))
            return redirect(route('invoices.index'))->with(['error' => 'Не задан счет!']);

        $rec = invoice::find($id);
        if (!isset($rec))
            return redirect(route('invoices.index'))->with(['error' => 'Не задан счет!']);

        if ($rec->doctypeid <> 1)
            return redirect(route('invoices.edit', $id))->with(['error' => 'Изменение срока действия счета доступно только для счета!']);


        $rec->enddate = $request->get('enddate');
        $rec->save();

        return redirect(route('invoices.edit', $id))->with(['success' => 'Срок действия счета изменен']);

    }

    public
    function update_upd_items(Request $request, $id)
    {
        //Массовое сохранение состава УПД (использованного в заявках)

        $userid = \Auth::user()->id;

        //$docid = $request->get('docid');
        $docid = $id;

        $doctypeid = $request->get('doctypeid');
        $eritmids = $request->get('eritmid');
        $offerids = $request->get('offerid');
        $doc_qtys = $request->get('doc_qty');
        $get_qtys = $request->get('get_qty');
        //dd($docid, $doctypeid, $offerids, $get_qtys);

        $upddoc = invoice::find($docid);
        if (isset($upddoc)) {

            //dd($upddoc->ownorgid,User::hasRightCodeInOrg($userid, 'invoices.update', $upddoc->ownorgid));
            //у пользователя должно быть право именно на документы этой организации (холдинга)
            if (1 == 1 and User::hasRightCodeInOrg($userid, 'invoices.update', $upddoc->ownorgid)) {


                foreach ($get_qtys as $key => $get_qty) {

                    if ($get_qty > 0) {
                        $rec = eritm_supply::where('eritmid', $eritmids[$key])
                            ->where('offerid', $offerids[$key])
                            ->where('invoiceid', $docid)
                            ->first();

                        if (!isset($rec)) {
                            $rec = new eritm_supply([
                                "eritmid" => $eritmids[$key],
                                "offerid" => $offerids[$key],
                                "invoiceid" => $docid,
                                "created_by" => $userid,
                                "created_at" => now(),
                            ]);
                        }

                        $rec->get_qty = $get_qty;
                        $rec->doc_qty = $doc_qtys[$key];
                        $rec->fctgetdate = $upddoc->docdate;
                        $rec->updated_by = $userid;
                        $rec->updated_at = now();
                        $rec->save();

                    } else {

                        //зачистим предыдущую запись
//                        $rec = eritm_supply::where('eritmid', $eritmids[$key])
//                            ->where('offerid', $offerids[$key])
//                            ->where('invoiceid', $docid)
//                            ->update(['doc_qty' => null, 'get_qty' => 0, 'updated_by' => $userid, 'updated_at' => now()]);

                        //2021-02-04 SNS. будем удалять ненужные записи
                        $rec = eritm_supply::where('eritmid', $eritmids[$key])
                            ->where('offerid', $offerids[$key])
                            ->where('invoiceid', $docid)
                            ->delete();


                        //dd($eritmids[$key], $offerids[$key], $docid, $rec);
                    }

                    //Пересчитать общее полученное кол-во ------------------------------
                    eritm_supply::recalc_getqty($offerids[$key], $eritmids[$key]);
                    //------------------------------------------------------------------
                }

                // Обновим общую сумму использования УПД --------------------------------
                $usedsum = eritm_supply::from("eritm_supplies as sup")
                    ->join('eritm_offers as ofr', 'ofr.id', 'sup.offerid')
                    ->where('sup.invoiceid', $docid)
                    ->selectraw("sum(sup.get_qty*ofr.ord_price) as get_sum")
                    ->first()
                    ->get_sum;
                $upddoc->usedsum = $usedsum;
                $upddoc->save();
                //dd($docsum);
                //----------------------------------------------------------------------

                //пересчитаем исполнение по контракту от заданного УПД -----------------
                invoice::make_contract_exes($docid);
                //----------------------------------------------------------------------


                $mess = "Внесены данные по УПД";
                objlog::log_info($this->sysobjid, $docid, $mess, 5);
                connectify('success', '-', $mess);

                //Зачистим кэшированные данные -----------------------------------------
                Cache::forget('informer_new_upds_' . $userid);
                //----------------------------------------------------------------------

            } else {
                //не имеет прав
                objlog::log_info($this->sysobjid, $docid, "Попытка изменения записи без права на это!", 4);
            }

        }

        return redirect(route($this->objcode . '.edit', $docid));
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        $userid = \Auth::user()->id;

        $rec = invoice::find($id);
        if (isset($rec)) {

            //Проверим права пользователя - может ли он что-то делать в плане этой организации?
            if (1 == 1 and User::hasRightCodeInOrg($userid, 'invoices.delete', $rec->ownorgid)) {

                $res = invoice::delete_by_id($id, $this->sysobjid);
                $obj_name = $res->obj['docdate'];
                $route = "";
                $sd = array();
                if ($res->err == 1) {
                    objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
                    $route = route('invoices.edit', $id);
                    $sd["error"] = $res->msg;
                    connectify('error', $obj_name, $res->msg);
                } else {
                    $sd['success'] = 'Запись (' . $id . ': ' . $obj_name . ') удалена';
                    objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

                    $pageno = session($this->objcode . '_pageno');
                    $route = route($this->objcode . '.index') . '?page=' . $pageno;
                    connectify('success', $obj_name, 'Запись удалена.');
                }
                return redirect($route)->with($sd);
            } else {
                //не имеет прав
                return redirect(route('invoices.edit', $id))->with(['error' => 'Нет прав на удаление записи!']);
            }
        } else
            return redirect(route('invoices.index'))->with(['error' => 'Указанная запись не найдена!']);
    }


    function notify()
    {
        invoice::make_notifies();

        return redirect(route("invoices.index"))->with(['success' => 'ok']);
    }


    public
    function fillItemsFromPrev000($id)
    {//Заполняет состав из предыдущего документа плана этой организации

        $userid = \Auth::user()->id;
        $rsltStatus = ['success' => 'ok'];

        if (usrsysright::isUserHasRightByCode_cached($userid, 'invoice_items.create')) {

            $rec = invoice::find($id);
            if (isset($rec)) {
                //найдем ближайший пред. план для этой эе организации
                $pre = invoice::where('ownorgid', $rec->ownorgid)
                    ->where('docdate', '<', $rec->docdate)
                    ->orderby('docdate', 'desc')
                    ->first();

                if (isset($pre)) {
                    $items = invoice_item::where('docid', $pre->id)
                        ->wherenull('fctpay_by')
                        ->where('active', 1)
                        ->get();

                    $ttt = invoice_item::where('docid', $pre->id)
                        ->wherenull('fctpay_by')
                        ->where('active', 1)
                        ->update(['docid' => $rec->id]);


                    $msg = $ttt . " записей перенесены из предыдущего плана оплат";
                    connectify('success', $msg, 'ok');
                    objlog::log_info($this->sysobjid, $id, $msg, 5);

                    //сформируем уведомления
                    invoice::make_notifies();
                }
            }
        } else
            $rsltStatus = ['error' => 'У вас нет прав на это действие!'];

        return redirect(route("invoices.edit", $id))->with($rsltStatus);

    }

    public
    function send2pay($id)
    {//Передача счета на оплату

        $userid = \Auth::user()->id;
        $rsltStatus = ['success' => 'ok'];

        if (usrsysright::isUserHasRightByCode_cached($userid, 'invoices.send2pay')) {

            $rec = invoice::find($id);
            if (isset($rec)) {

                $sendErrMsg = '';
                //проверим, разрешена ли прямая передача счета на оплату для указанной категории
                //если категория не предусматривает прямую передачу на оплату,
                if (!pay_category::is_invoice_drctpay($rec->categoryid)) {
                    // то нужно проверить, что Сумма использования и доп. затрат == сумме счета
                    $maySend = ($rec->usedsum + $rec->aux_sum == $rec->docsum);
                    $sendErrMsg = "Сумма использования счета и доп. затрат должны быть равны сумме счета! ({$rec->usedsum}+{$rec->aux_sum} <> {$rec->docsum})!";

                } else $maySend = true;


                if ($maySend and $rec->categoryid == 12) {
                    //счет на "Материалы(Срочно)"

                    //должны выполниться несколько условий:
                    // - указаны Объект/Вид работ/Договор с подрядчиком
                    // - есть состав
                    // - сумма состава == сумме счета

//                    if (!isset($rec->buildobjid))
//                        return redirect(route("invoices.edit", $id))->with(['error' => "Не указан строительный объект!"]);

                    if (!isset($rec->buildopertypeid))
                        return redirect(route("invoices.edit", $id))->with(['error' => "Не указан вид работ!"]);

                    if (!isset($rec->exe_contractid))
                        return redirect(route("invoices.edit", $id))->with(['error' => "Не указан договор с подрядчиком!"]);

                    $ttt = invoice_item::where('invoiceid', $rec->id)
                        ->selectRaw("count(*) as cnt, sum(itmsum) as itmsum")
                        ->first();

                    if ($ttt->cnt == 0)
                        return redirect(route("invoices.edit", $id))->with(['error' => 'В счете категории "Материалы(Срочно)" должен быть внесен состав!']);

                    if ($ttt->itmsum != $rec->docsum)
                        return redirect(route("invoices.edit", $id))->with(['error' => "Сумма состава должна быть равна сумме счета!"]);

                    //dd($ttt);

                }

                //Срок действия счета не должен истечь
                if (date_create($rec->enddate) < now())
                    return redirect(route("invoices.edit", $id))->with(['error' => "Превышен срок действия счета!"]);

                //рассчитаем текущую сумму оплаты этого счета
                $fctpaysum = orgplnpay_item::where([
                    'src_sysobjid' => $this->sysobjid,
                    'src_objid' => $rec->id,
                ])->sum('fctpaysum');

                //Сумма уже оплаченного должна быть менее суммы счета
                if ($fctpaysum >= $rec->docsum) {
                    return redirect(route("invoices.edit", $id))->with(['error' => "Счет уже полностью оплачен!"]);
                }


                if ($maySend) {

                    //попробуем найти запись плана платежей на сегодня для данной организации
                    $orgplnpay = orgplnpay::where([
                        'ownorgid' => $rec->ownorgid,
                        'docdate' => now()->format('Y-m-d')
                    ])
                        ->first();

                    if (!isset($orgplnpay)) {

                        $orgplnpay = new orgplnpay([
                            'ownorgid' => $rec->ownorgid,
                            'docdate' => now()->format('Y-m-d'),
                            'restbegsum' => -1
                        ]);
                        $orgplnpay->save();
                        //dd($orgplnpay);
                    }

                    if (isset($orgplnpay)) {

                        // ищем - может этот счет уже привязан к этому остатку/очереди/плану

                        $orgplnpay_item = orgplnpay_item::where([
                            'docid' => $orgplnpay->id,
                            'src_sysobjid' => $this->sysobjid,
                            'src_objid' => $rec->id,
                        ])->first();
                        //dd(333,$orgplnpay_item);

                        if (!isset($orgplnpay_item)) {
                            $orgplnpay_item = new orgplnpay_item([
                                'docid' => $orgplnpay->id,
                                'src_sysobjid' => $this->sysobjid,
                                'src_objid' => $rec->id,
                                'limpaydate' => $rec->enddate,
                                'created_by' => $userid,
                                'updated_by' => $userid,
                            ]);
                        }
                        //Если платеж еще не сделан
                        if (!isset($orgplnpay_item->fctpaysum)) {

                            //Обновим значения в очереди -----------------------------

                            $orgplnpay_item->orgid = $rec->orgid;
                            $orgplnpay_item->contractid = $rec->contractid;
                            $orgplnpay_item->categoryid = $rec->categoryid;
                            $orgplnpay_item->reason = mb_substr('счет № ' . $rec->docnum
                                . ' от ' . $rec->docdate
                                . ' / ' . $rec->notes, 0, 160);
                            $orgplnpay_item->plnpaysum = ($rec->docsum - $fctpaysum);
                            $orgplnpay_item->active = 1;
                            $orgplnpay_item->save();


                            $rec->locked = 1;
                            $rec->status = 'передан на оплату';
                            $rec->save();

                            $msg = " счет передан на оплату";
                            connectify('success', $msg, 'ok');
                            objlog::log_info($this->sysobjid, $id, $msg, 3);

                            //сформируем уведомления
                            //invoice::make_notifies();

                            //добавим пользователя инициатором заявки
                            obj_reader::addOrUpdate(902, $orgplnpay_item->id, $userid, 0, 1);


                            //если счет (doctypeid==1) на категорию "12-Материалы(Срочно)" и в нем не все позиции связаны с Номенклатурой
                            if ($rec->doctypeid == 1 and $rec->categoryid == 12) {
                                //подсчитаем кол-во позиций счета которые не связаны с номенклатурой
                                $cnt = invoice_item::where('invoiceid', $rec->id)->whereNull('refitmid')->count();
                                if ($cnt > 0) {
                                    //уведомим сотрудников, с правом "Оператор спр-ка Номенклатура" о необходимости добавления новых позиций или связывания с существующими
                                    event(new notifyEvent('invoices.items_no_refitmid', $this->sysobjid, $rec->id, $userid));
                                }
                            }
                            //--------------------------------------------------------------------------------------------------------


                        } else {
                            $rsltStatus = ['error' => 'Счет уже оплачен!'];
                        }
                    } else {
                        $rsltStatus = ['error' => 'Нет плана платежей на сегодня для указанной компании!'];
                    }
                } else {
                    $rsltStatus = ['error' => $sendErrMsg];
                }

            }
        } else
            $rsltStatus = ['error' => 'У вас нет прав на это действие!'];

        return redirect(route("invoices.edit", $id))->with($rsltStatus);

    }

    public function export()
    {
        $response = Excel::download(new InvoicesExport, 'invoices.xlsx', \Maatwebsite\Excel\Excel::XLSX);
        //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
        //HERE IS THE MAGIC FOLKS
        ob_end_clean();
        return $response;


        //return Excel::download(new InvoicesExport, 'invoices.ods', \Maatwebsite\Excel\Excel::ODS);
        //return Excel::download(new InvoicesExport, 'invoices.html', \Maatwebsite\Excel\Excel::HTML);
        //return Excel::download(new InvoicesExport, 'invoices.csv', \Maatwebsite\Excel\Excel::CSV);
//        return Excel::download(new InvoicesExport, 'invoices.csv', \Maatwebsite\Excel\Excel::CSV
//            , ['Content-Type' => 'text/csv',]);
    }


    static public function list_child_docs(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {

            $pardocid = $request->pdid;
            //Log::debug("pardocid=$pardocid");
            if (isset($pardocid)) {
                $list = invoice::from('invoices as inv')
                    ->join('orgs as o', 'o.id', 'inv.orgid')
                    ->where('pardocid', $pardocid)
                    ->selectraw("inv.id, concat('№ ', inv.docnum,' от ',inv.docdate,' / ', o.name, ' (',inv.notes,')') as tname")
                    ->orderBy('tname')
                    ->get()->pluck('tname', 'id')->toArray();

                $result = array('child_docs' => $list);
            }

        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }
        return response()->json($result);
    }

    static public function list_bdgtitmsums_by_er_upd(Request $request)
    {
        //для AJAX-запросов - отбираем bdgtitmsumid
        $result = "";
        try {

            $rqstid = $request->rqid;
            $upd_id = $request->did;
            //Log::debug("upd_id=$upd_id");

            if (isset($rqstid) and isset($upd_id)) {

                $list = Cache::remember('list_bdgtitmsums_' . $rqstid . '_' . $upd_id, now()->addMinutes(5)
                    , function () use ($rqstid, $upd_id) {
                        return budget_itmsum::from('budget_itmsums as bis')
                            ->join('bdgtacnttypes as bat', 'bat.id', 'bis.acnttypeid')
                            ->join('budget_items as bi', 'bi.id', 'bis.itmid')
                            ->join('budgets as b', 'b.id', 'bi.budgetid')
                            ->join('orgs as o', 'o.id', 'b.orgid')
                            ->whereRaw("bis.id in (select distinct eri.bdgtitmsumid
                                    FROM equiprqst_items as eri
                                    join eritm_supplies as sup on sup.eritmid=eri.id
                                        and sup.invoiceid={$upd_id}
                                    where eri.rqstid={$rqstid} )
                                    ")
                            ->selectraw("bis.id, concat(' ', o.name,' / ',bat.name,' / ', bi.name) as tname")
                            ->orderBy('tname')
                            ->get()->pluck('tname', 'id')->toArray();
                    });

                $result = array('list' => $list);
            }

        } catch (\Exception $e) {
            Log::debug($e->getMessage());
        }
        return response()->json($result);
    }


    static public function make_contract_exe(Request $request, $id)
    {
        $ret_url = $request->input('returl');

        //создание исполнения по контракту от заданного УПД
        invoice::make_contract_exes($id);

        if (isset($ret_url))
            return redirect($ret_url);

    }

    static public function make_all_contract_exes(Request $request)
    {
        $ret_url = $request->input('returl');

        //создание исполнения по контракту от заданного УПД
        invoice::make_all_contract_exes();

        if (isset($ret_url))
            return redirect($ret_url);

    }


    public function load()
    {
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight(-1);

        $rec = new \stdClass();
        //известные форматы импорта счетов (217)
        $rec->docimpformats = docimpformat::where('doctypeid', 217)
            ->active()
            ->select('id', 'name')
            ->orderby('name')
            ->get()->pluck('name', 'id')->toArray();

        $rec->ownorgs = org::lstOwnOrgs();
        //dd($rec);

        return view('invoices.load', compact('rec', "usrrights"));
    }


    public function import(Request $request)
    {
        //Импорт без сохранения файла на диск. Только обработка

        $messages = [
            'doc.required' => 'Не указан файл со счетом на оплату',
            'docimpformatid.required' => 'Не указан формат данных файла',
            'ownorgid.required' => 'Укажите покупателя (со стороны холдинга)',
        ];

        $rules = [
            "doc" => "required",
            "docimpformatid" => "required",
            "ownorgid" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        //$returl = $request->get('retroute');

        $usrrights = $this->setInterfaceRight(-1);
        $result = new Result();
        $rec = new \stdClass();
        $rec->docimpformatid = $request->get('docimpformatid');
        $rec->ownorgid = $request->get('ownorgid');

        $docimpformat = docimpformat::find($rec->docimpformatid);
        $rec->orgid = $docimpformat->orgid;
        $rec->extsysid = $docimpformat->extsysid;   // 101 - Либерти
        //dd($rec);

        if ($request->hasfile('doc')) {

            $file = $request->doc;

            $filesize = $file->getSize();
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            //dd($name, $extension, $filesize);

            if ($docimpformat->id == 1)
                $rec = docimpformat::import_001($file, $rec);
            elseif ($docimpformat->id == 2)
                $rec = docimpformat::import_002($file, $rec);
            elseif ($docimpformat->id == 3)
                $rec = docimpformat::import_003($file, $rec);
            else {
                $result->err = 1;
                $result->msg = 'Не определена процедура импорта!';
            }
            //--------------------------------------------------------------------------------
            //dd($result->msg);


        } else {
            $result->err = 1;
            $result->msg = 'Файл со счетом не загружен!';
        }

//        $rec->invoiceid = $invoice->id;
//        $rec->result = $result;


        return view('invoices.load', compact('rec', "usrrights"));
    }


    static public function make_equiprqst($invoiceid)
    {

//        $er = equiprqst::find(578);
//
//        $val = $er->hash_base();
//        $h1 = $er->hash();
//        $h2 = $er->hash();
//        dd($val, $h1, $h2, Hash::check($val, $h1), Hash::check($val, $h2));


        //Создание заявки на матералы на основе указанного счета
        //Требования:
        // - счет должен быть
        $invoice = invoice::find($invoiceid);
        if (!isset($invoice))
            return redirect(route("invoices.edit", $invoiceid))->with(['error' => "Документ не найден!"]);

        // - категория счета должна быть "12-Материалы(Срочно)"
        if ($invoice->categoryid != 12)
            return redirect(route("invoices.edit", $invoiceid))->with(['error' => 'Не подходящая категория! Требуется категрия "Материалы(Срочно)"']);

        // - счет должен быть с составом и в нем не должно быть позиций не из номенклатуры
        $tmp = invoice_item::where(['invoiceid' => $invoiceid])
            ->selectraw("count(*) as cnt, sum(if(refitmid is null, 0,1)) as ri_cnt")
            ->whereNull('eritmid')
            ->first();
        //dd($tmp);
        if ($tmp->cnt == 0)
            return redirect(route("invoices.edit", $invoiceid))->with(['error' => 'В счете должен состав еще не переданный в заявки!"']);

        if ($tmp->cnt != $tmp->ri_cnt)
            return redirect(route("invoices.edit", $invoiceid))->with(['error' => 'Все позиции счета должны быть связаны со справочником номенклатуры!']);

        // В счете должны быть позиции, которые еще не связаны с позициями заявки
        //$cnt = invoice_item::where(['invoiceid' => $invoiceid])->whereNull('eritmid')->count();

        // счет должен быть отправлен на оплату и уже согласован Руководителем организации.
        // Согласован = сумма к оплате > 0
        $orgplnpay_item = orgplnpay_item::where(['src_sysobjid' => 915, 'src_objid' => $invoiceid])
            ->where('agr1_sum', '>', 0)
            ->whereNotNull('agr1_by')
            ->first();
        if (!isset($orgplnpay_item))
            return redirect(route("invoices.edit", $invoiceid))->with(['error' => 'Счет должен быть направлен на оплату, и согласован от Руководителя организации!']);


        try {

            DB::beginTransaction();

            $equiprqst = new equiprqst([
                'stageid' => 4,   //выбор бюджета
                'stage_begdt' => now(),
                'statusid' => 1,    //?? не используется?
                'buildobjid' => $invoice->buildobjid,           //объект
                'buildopertypeid' => $invoice->buildopertypeid, //вид работ
                'pln_suporgid' => 1, //?? СК-Баско todo:: заляпуха
                'inituserid' => $invoice->created_by,   //
                'initstaffid' => null,                  //?? не используется?
                'initorgid' => $invoice->ownorgid,      //
                'orgid' => $invoice->exe_orgid,                 // Организация-подрядчик
                'contractid' => $invoice->exe_contractid,       // Договор с подрядчиком
                'lim_budgetownerid' => $invoice->ownorgid,      // Ограничение владельца бюджета
                'active' => 1,                                  //
            ]);
            $equiprqst->save();


            //Перенесем товары и оборудование (2,3) из состава счета в заявку (без услуг (1)) -------------------------
            $items = invoice_item::from('invoice_items as ii')
                ->join('refitems as ri', 'ri.id', 'ii.refitmid')
                ->where('invoiceid', $invoiceid)
                ->whereIn('ri.producttypeid', [2, 3])
                ->select('ii.*')
                ->get();

            $ordr = 0;
            foreach ($items as $itm) {

                $eri = new equiprqst_item([
                    'rqstid' => $equiprqst->id,
                    'ordr' => ++$ordr,
                    'refitmid' => $itm->refitmid,
                    'itmname' => $itm->itmname,
                    'unittypeid' => $itm->unittypeid,
                    'unit' => $itm->unit,
                    'rqst_qty' => $itm->qty,
                    //'est_price' => $itm->price,   //заполнит ревизор (Глухова)
                    'maxreqdate' => today(),        //todo: согласовать - какое значение ставить
                    'bdgtorgid' => $invoice->ownorgid,
                    'm15srcorgid' => $invoice->ownorgid,

                    'invoiceid' => $invoiceid,
                    'suporgid' => $invoice->orgid,      //Поставщик
                    'ord_qty' => $itm->qty,
                    'ord_price' => $itm->price,
                    'ord_sum' => $itm->itmsum,
                ]);
                $eri->save();

                //свяжем с позицией счета
                $itm->eritmid = $eri->id;
                $itm->save();

                $ofr = new eritm_offer([
                    'eritmid' => $eri->id,
                ]);
                $ofr->invoiceid = $invoiceid;
                $ofr->invitmid = $itm->id;

                $ofr->itmname = $itm->itmname;
                $ofr->doc_qty = $itm->qty;
                $ofr->doc_unit = $itm->unit;
                $ofr->ord_qty = $itm->qty;
                $ofr->ord_sum = $itm->itmsum;
                $ofr->ord_price = $itm->price;

                //поставщика берем из счета.
                $ofr->suporgid = $invoice->orgid;
                $ofr->plngetwrkdays = $invoice->plngetwrkdays;  //Срок поставки от даты оплаты, раб. дней

                $ofr->save();

            }
            //---------------------------------------------------------------------------------------------------------

            //Перенесем  услуги (1) из состава счета в доп. затраты по заявке -----------------------------------------
            $items = invoice_item::from('invoice_items as ii')
                ->join('refitems as ri', 'ri.id', 'ii.refitmid')
                ->where('invoiceid', $invoiceid)
                ->whereIn('ri.producttypeid', [1])
                ->select('ii.*')
                ->get();

            foreach ($items as $itm) {

                $ere = new equiprqst_expense([
                    'rqstid' => $equiprqst->id,
                    //'refitmid' => $itm->refitmid,
                    'reason' => $itm->itmname,
                    //'unittypeid' => $itm->unittypeid,
                    //'unit' => $itm->unit,
                    'expense_sum' => $itm->itmsum,
                    'bdgtitmsumid' => null,
                    //'m15srcorgid' => $invoice->ownorgid,

                    'invoiceid' => $invoiceid,
                    //'suporgid' => $invoice->orgid,      //Поставщик
                    //'ord_qty' => $itm->qty,
                    //'ord_price' => $itm->price,
                    //'ord_sum' => $itm->itmsum,
                    'operdate' => $invoice->docdate,    //?
                    'notes' => $invoice->org->name,     //
                ]);
                $ere->save();

                //свяжем с позицией счета
                $itm->eritmid = $ere->id;   //todo: Натяжка - поле invoice_items.eritmid предполагает ссылку на equiprqst_items.id,
                // а здесь - ссылка на equiprqst_expenses.id.
                $itm->save();
            }
            //---------------------------------------------------------------------------------------------------------


            //Создадим согласования заявки ----------------------------------------------------------------------------
            $obj_hash = $equiprqst->hash();
            // - согласование технолога -----------------------------------
            $params = [
                'sysobjid' => 870,
                'objid' => $equiprqst->id,
                'dcsn_rightid' => 382,
                'dcsn_typecode' => 'equiprqsts.confirm',
                'stageid' => 2,
                'dcsn_orgid' => $equiprqst->initorgid,
            ];
            $dcsn = obj_approval::chkAndCreate($params);

            $dcsn->decision = 1;
            $dcsn->dcsn_userid = $invoice->updated_by;
            $dcsn->dcsn_at = $orgplnpay_item->created_at; //по времени отправки счета на оплату
            $dcsn->active = 1;
            $dcsn->obj_hash = $obj_hash;
            $dcsn->save();
            //-------------------------------------------------------------

            // - согласование предв. цен ----------------------------------
            $params = [
                'sysobjid' => 870,
                'objid' => $equiprqst->id,
                'dcsn_rightid' => 400,
                'dcsn_typecode' => 'equiprqsts.set_estprices',
                'stageid' => 10,
                'dcsn_orgid' => $equiprqst->initorgid,
            ];
            $dcsn = obj_approval::chkAndCreate($params);

            $dcsn->decision = 1;
            $dcsn->dcsn_userid = $invoice->updated_by;
            $dcsn->dcsn_at = $orgplnpay_item->created_at; //по времени отправки счета на оплату
            $dcsn->active = 1;
            $dcsn->obj_hash = $obj_hash;
            $dcsn->save();
            //-------------------------------------------------------------

            // - согласование Руководителя --------------------------------
            $params = [
                'sysobjid' => 870,
                'objid' => $equiprqst->id,
                'dcsn_rightid' => 383,
                'dcsn_typecode' => 'equiprqsts.approve',
                'stageid' => 3,
                'dcsn_orgid' => $equiprqst->initorgid,
            ];
            $dcsn = obj_approval::chkAndCreate($params);

            $dcsn->decision = 1;
            $dcsn->dcsn_userid = $orgplnpay_item->agr1_by;
            $dcsn->dcsn_at = $orgplnpay_item->agr1_at; //по времени согласования олаты счета
            $dcsn->active = 1;
            $dcsn->obj_hash = $obj_hash;
            $dcsn->save();
            //-------------------------------------------------------------


            //Раз все хорошо, зарезервируем бюджет на сумму оценки ----------------
            equiprqst::budget_reg_est($equiprqst->id);

            //equiprqst::budget_reg_use($equiprqst->id);
            //---------------------------------------------------------------------

            //--------------------------------------------------------------------------------------------------------------
            //dd($dcsn);
            DB::commit();


        } catch (\Exception $e) {

            DB::rollback();
            $msg = $e->getMessage();
            return redirect(route("invoices.edit", $invoiceid))->with(['error' => $msg]);
        }


        return redirect(route("equiprqsts.edit", $equiprqst->id))->with(['success' => 'Заявка создана из счета']);

    }
}
