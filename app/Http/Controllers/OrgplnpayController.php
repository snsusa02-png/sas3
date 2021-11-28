<?php

namespace App\Http\Controllers;

use App\Events\notifyEvent;
use App\objflag;
use App\orgplnpay;
use App\objlog;
use App\org;
use App\orgplnpay_item;
use App\sysobj;
use App\User;
use App\usrsysright;
use Illuminate\Http\Request;
use DB;
use Cache;

class OrgplnpayController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 901;
        $this->sysobjcode = 'orgplnpays';
        $this->objcode = 'orgplnpays';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

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
        $usrrights['agr1'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.agr1');
        $usrrights['agr2'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.agr2');
        $usrrights['regpay'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.regpay');
        $usrrights['fillfromprev'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
        }

        $usrrights['items.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'orgplnpay_items.create');

        //2021-10-18 SNS. Искуccтвенное ограничение - не показываем суммы, если у пользователя нет прав на чтение записей
        // Пользователь, не имеющий право на чтение записей, тем не менее увидит "свои" записи - те что он отправил на оплату (orgplnpay_items.created_by)
        $usrrights['showsums'] = $usrrights['read'];

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

        if (!$usrrights['read']
            and orgplnpay_item::where(['created_by' => $userid])->count() == 0) {

            return view('home')->with(['error' => 'У вас нет доступа к Плану платежей!']);
        }

        session([$this->objcode . '_pageno' => $request->page]);

        // - параметры поиска -------------------------------------------------
        $s_ownorgid = "";
        $s_timestatuscode = "";
        $s_plndate = "";
        $s_name = "";
        $s_type = "";
        $s_docnum = "";
        $s_statuscode = "";
        $s_orggrpid = "";
        $s_flagtypeid = "";

        if ($request->isMethod('post')) {
            $s_ownorgid = $request->get("s_ownorgid");
            $s_timestatuscode = $request->get("s_timestatuscode");
            $s_plndate = $request->get("s_plndate");
            $s_name = $request->get("s_name");
            $s_type = $request->get("s_type");
            $s_docnum = $request->get("s_docnum");
            $s_orggrpid = $request->get("s_orggrpid");
            $s_flagtypeid = $request->get("s_flagtypeid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $this->objcode]);
            session(['search_params' => [
                's_ownorgid' => $s_ownorgid,
                's_timestatuscode' => $s_timestatuscode,
                's_plndate' => $s_plndate,
                's_name' => $s_name,
                's_docnum' => $s_docnum,
                's_statuscode' => $s_statuscode,
                's_orggrpid' => $s_orggrpid,
                's_flagtypeid' => $s_flagtypeid,
            ]]);
        } else {
            if (session('search_setname') == $this->objcode) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_ownorgid = $params['s_ownorgid'] ?? null;
                    $s_timestatuscode = $params['s_timestatuscode'] ?? null;
                    $s_plndate = $params['s_plndate'] ?? null;
                    $s_name = $params['s_name'] ?? null;
                    $s_docnum = $params['s_docnum'] ?? null;
                    $s_statuscode = $params['s_statuscode'] ?? null;
                    $s_orggrpid = $params['s_orggrpid'] ?? null;
                    $s_flagtypeid = $params['s_flagtypeid'] ?? null;
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_ownorgid" => $s_ownorgid,
            "s_timestatuscode" => $s_timestatuscode,
            "s_plndate" => $s_plndate,
            "s_name" => $s_name,
            "s_type" => $s_type,
            "s_docnum" => $s_docnum,
            "s_statuscode" => $s_statuscode,
            "s_orggrpid" => $s_orggrpid,
            "s_flagtypeid" => $s_flagtypeid,
        ];

        $needSearch = false;
        foreach ($search_params as $p) {
            if (isset($p)) {
                $needSearch = true;
                break;
            }
        }

        //var_dump($search_params);
        $sc = "1=1 ";

        $bShowAllUserOrgs = true;   //показывать Все организации, или только текущую
        $bSomeAgreeRight = ($usrrights['agr1'] or $usrrights['agr2'] or $usrrights['regpay']);

        //if ($bSomeAgreeRight) {
        if ($bShowAllUserOrgs) {
            // --------------------------------------------------------------------
            // Ограничить доступ представительством пользователя -----------
            $sc .= " and pp.ownorgid in (SELECT orgid FROM userorgs
              where userid=" . $userid . " and active=1 and now() between begdt and ifnull(enddt, now()) )";
            // --------------------------------------------------------------------
        } else {
            //если у пользователя нет прав на согласование/оплату, то отображать только записи о представляемой им СЕЙЧАС организации
            $sc .= " and pp.ownorgid=" . $userorgid;
        }

        if ($bSomeAgreeRight) {
        } else
            //только текущая дата
            $sc .= " and pp.docdate=curdate()";


        if ($needSearch) {

            if (strlen($s_ownorgid) > 0) {
                $sc = $sc . " and pp.ownorgid =" . $s_ownorgid;

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

            if (strlen($s_name) > 0) {
                //$sc = $sc . " and pp.name like '%" . mb_strtoupper($s_name) . "%'";
                //$sc = $sc . " and exists( select 1 from orgs as o where o.id=pp.ownorgid and o.name like '%" . mb_strtoupper($s_name) . "%')";
            }

        }

        $recs = orgplnpay::from('orgplnpays as pp')
            ->Join('orgs as o', function ($j) {
                $j->on('o.id', 'pp.ownorgid');
            })
            ->whereraw($sc)
            ->select('pp.*'
                , 'o.name as orgname'
                , db::raw("(select sum(plnpaysum) from orgplnpay_items as i where i.docid=pp.id) as plnpaysum")
                , db::raw("(select count(*) from orgplnpay_items as i where i.docid=pp.id) as tot_cnt")
                , db::raw("(select count(*) from orgplnpay_items as i where i.docid=pp.id and ifnull(i.agr1_sum,-1) = ifnull(i.agr2_sum,-2)) as agr_cnt")
                , db::raw("(select sum(fctpaysum) from orgplnpay_items as i where i.docid=pp.id) as fctpaysum")
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

        $recs = $recs->paginate(50);
        //dd($sc,$recs);

        $data = new \stdClass();
        $data->sysobj = sysobj::find($this->sysobjid);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;


        if ($bShowAllUserOrgs) {
            $usedorgs = org::from('orgs as o')
                ->whereraw(' o.id in (select distinct ownorgid from orgplnpays)')
                ->select('o.id', 'o.name')
                ->get()
                ->pluck('name', 'id')
                ->toArray();
        } else
            $usedorgs = null;

        if ($bSomeAgreeRight) {
            $timestatuses = [-1 => 'вчера', 1 => 'сегодня', 2 => 'завтра', 3 => 'в  будущем', 4 => 'в прошлом', 5 => 'календарь'];
        } else {
            $timestatuses = null;
        }

//dd($orgs);
//dd($usrrights);

        return view('orgplnpays.index', compact(
            'recs', 'data', 'rec0'
            , 'search_params', 'sort_params'
            , 'usrrights'
            , 'usedorgs', 'timestatuses'));
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create()
    {
        return $this->edit(-1);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit($id)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи
            $rec = new orgplnpay([
                'id' => -1,
                'ownorgid' => \Auth::user()->curorgid,
                'docdate' => today(),
                'active' => 1,
                'created_by' => \Auth::user()->id,
            ]);
        } else
            $rec = orgplnpay::find($id);


        if (!isset($rec))
            return redirect(route($this->objcode . '.index'));


        //Отберем заявки на оплату (orgplnpay_items)
        $sc = "pi.docid={$rec->id}";

        //Проверим есть ли у пользователя право чтения на головную таблицу (OrgPlnPays) и представление текущей организации
        if (!User::hasRightCodeInOrg($userid, 'orgplnpays.read', $rec->ownorgid)) {

            //2021-06-03 SNS. Проверим, что в этом плане у пользователя есть его записи
            if (orgplnpay_item::where(['docid' => $rec->id, 'created_by' => $userid])->count() == 0)
                return redirect(route($this->objcode . '.index'))
                    ->with(['error' => 'У вас нет прав для работы с этой записью плана платежей этой организации!']);

            $sc .= " and pi.created_by={$userid}";
        }
        //dd($sc);

        $usrrights = $this->setInterfaceRight($rec->id);

        //преобразуем для нормальной работы <INPUT TYPE="DATE"...
        //$orgplnpay->begdate = strftime('%Y-%m-%dT%H:%M:%S', strtotime($orgplnpay->begdate));
        if (isset($rec->docdate))
            $rec->docdate = strftime('%Y-%m-%d', strtotime($rec->docdate));

        // Наши организации ограничены представительством пользователя --------
        $rec->ownorgs = org::lstFor_cached([
            'flagtypeid' => 12,
            'in_userorgs' => $userid,
        ], 15);
        // --------------------------------------------------------------------


        $rec->items = orgplnpay_item::from('orgplnpay_items as pi')
            ->join('orgs as o', 'o.id', 'pi.orgid')
            ->join('pay_categories as pc', 'pc.id', 'pi.categoryid')
            //->where('pi.docid', $rec->id)
            ->whereRaw($sc)
            ->select('pi.*'
                , 'o.name as org_name'
                , 'pc.name as category_name'
                , DB::raw(" (SELECT GROUP_CONCAT(distinct eri.rqstid  SEPARATOR '; ')
    FROM eritm_offers AS ofr
    join equiprqst_items as eri on eri.id=ofr.eritmid
    WHERE pi.src_sysobjid = 915 and ofr.invoiceid = pi.src_objid
    ) AS er_ids")
            )
            ->orderby('pc.ordr')
            ->orderby('pc.name')
            ->orderby('pi.id')
            ->get();


        //готовы к оплате, так как имеют два согласования, пусть даже и разных
        //2021-10-18 Рассчитываем на то, что $sc содержит ограничение пользователя на доступ к записям
        $rec->forpay_items = orgplnpay_item::from('orgplnpay_items as pi')
            ->join('orgs as o', 'o.id', 'pi.orgid')
            //->where('docid', $rec->id)
            ->whereRaw($sc)
            //->whereColumn('pi.agr1_sum', '=', 'pi.agr2_sum')
            ->whereNotNull('pi.agr1_sum')
            ->where('pi.agr2_sum', '>', 0)
            ->select('pi.*', 'o.name as orgname')
            ->orderby('pi.fctpaysum')
            ->get();
//        dd($sc, $rec->forpay_items);


        $usrrights = $this->setInterfaceRight($id);
        if (count($rec->items) > 0)
            $usrrights['save'] = $usrrights['delete'] = false;

        //Запретим добавлять позиции в старые планы платежей
        $dd = (int)date_diff(today(), date_create($rec->docdate))->format('%R%a');
        if ($dd < 0) {
            $usrrights['items.create'] = false;
        }

        //проверка на возможность заливки из предыдущего документа
        if ($usrrights['items.create'] and $rec->id <> -1 and $rec->docdate == date('Y-m-d')) {

            $pre = orgplnpay::where('ownorgid', $rec->ownorgid)
                ->where('docdate', '<', $rec->docdate)
                ->orderby('docdate', 'desc')
                ->first();

            if (isset($pre)) {
                $cnt = orgplnpay_item::where('docid', $pre->id)
                    ->wherenull('fctpay_by')
                    ->where('active', 1)
                    ->count();
                //dd($cnt);
                if ($cnt > 0)
                    $usrrights['fillfromprev'] = true;
            }
        }

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

        //Проверим права пользователя - может ли он что-то делать в плане этой организации?
        if (1 == 1 and User::hasRightCodeInOrg($userid, 'orgplnpays.update', $ownorgid)) {


            $messages = [
                'ownorgid.required' => 'Укажите компанию со стороны холдинга',
                'docdate.required' => 'Укажите дату ',
//                'restbegsum.required' => 'Укажите сумму начального остатка',
            ];

            $rules = [
                "ownorgid" => "required",
                "docdate" => "required",
//                "restbegsum" => "required",
            ];

            $request->validate($rules, $messages);

            $userid = \Auth::user()->id;
            $mess = "";
            if ($id == -1) {
                $rec = new orgplnpay([
                    "ownorgid" => $ownorgid,
                    "docdate" => $request->get('docdate'),
                    "restbegsum" => -1,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $mess = "Запись создана";
            } else {
                $rec = orgplnpay::find($id);
                $mess = "Запись обновлена";
            }

            //проверка на дубль Плательщик/Дата
            $rules = [
                "ttt" => [
                    function ($attribute, $value, $fail) use ($rec) {
                        //проверим на существование плана этой компании на этот же день
                        $cnt = orgplnpay::where('ownorgid', $rec->ownorgid)
                            ->where('docdate', $rec->docdate)
                            ->where('id', '<>', ($rec->id ?? -1))
                            ->count();
                        //dd($cnt);
                        if ($cnt > 0) {
                            $fail("План на эту дату уже существует!");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);

//            $pre = new \stdClass();
//            $pre->docdate = $rec->docdate;
//            $pre->restbegsum = $rec->restbegsum;

            $rec->ownorgid = $ownorgid;
            $rec->docdate = $request->get('docdate');

            //$rec->restbegsum = $request->get('restbegsum');

            $rec->active = $request->get('active', 1);
            $rec->updated_by = $userid;
            $rec->updated_at = now();
            $rec->save();
            $id = $rec->id;

            objlog::log_info($this->sysobjid, $rec->id, $mess, 5);
            connectify('success', '-', $mess);

//            if ($rec->docdate == date('Y-m-d')
//                and ($rec->restbegsum <> $pre->restbegsum or $rec->docdate <> $pre->docdate)) {
//                event(new notifyEvent('orgplnpays.restsum_changed', $this->sysobjid, $rec->id, $userid, 'Введен/изменен остаток на р/с ' . $rec->ownorg->name, '-'));
//            }
            //нужно перенести в orgacnt_sums.update!

        } else {
            //не имеет прав
            objlog::log_info($this->sysobjid, $id, "Попытка изменения записи без права на это!", 4);
        }

//        if ($id == -1) {
        return redirect(route($this->objcode . '.edit', $id));
//        } else {
//            $pageno = session($this->objcode . '_pageno');
//            return redirect(route($this->objcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
//        }
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

        $rec = orgplnpay::find($id);
        if (isset($rec)) {

            //Проверим права пользователя - может ли он что-то делать в плане этой организации?
            if (1 == 1 and User::hasRightCodeInOrg($userid, 'orgplnpays.delete', $rec->ownorgid)) {

                $res = orgplnpay::delete_by_id($id, $this->sysobjid);
                $obj_name = $res->obj['docdate'];
                $route = "";
                $sd = array();
                if ($res->err == 1) {
                    objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
                    $route = route('orgplnpays.edit', $id);
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
                return redirect(route('orgplnpays.edit', $id))->with(['error' => 'Нет прав на удаление записи!']);
            }
        } else
            return redirect(route('orgplnpays.index'))->with(['error' => 'Указанная запись не найдена!']);
    }


    function notify()
    {
        orgplnpay::make_notifies();

        return redirect(route("orgplnpays.index"))->with(['success' => 'ok']);
    }


    public
    function fillItemsFromPrev($id)
    {//Заполняет состав из предыдущего документа плана этой организации

        $userid = \Auth::user()->id;
        $rsltStatus = orgplnpay::fillItemsFromPrev($id, $userid);

        return redirect(route("orgplnpays.edit", $id))->with($rsltStatus);

    }

    public
    function removeExpired($id)
    {//Удаляет просроченные заявки

        $userid = \Auth::user()->id;
        //$rsltStatus=['error'=>'123'];
        $rsltStatus = orgplnpay::removeExpiredItems($id);

        return redirect(route("orgplnpays.edit", $id))->with($rsltStatus);

    }

}
