<?php

namespace App\Http\Controllers;

use App\contract;
use App\driver_work;
use App\mchn_opertype;
use App\mchn_raid;
use App\opertype;
use App\paydoc;
use App\objlog;
use App\objtag;
use App\org;
use App\paytype;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\unittype;
use App\User;
use App\user_template;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PaydocController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 520;
        $this->sysobjcode = 'paydocs';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    /*
     * Установка прав пользователя
     */
    protected function setInterfaceRight($recid)
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

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['manager'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.manager');

        if ($recid > 0) {

            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');

            //для существующих записей проверим открытость периода
            if (paydoc::isLocked($recid)) {

                $usrrights['save'] = false;
                $usrrights['delete'] = false;
                $usrrights['admindelete'] = false;
            }
        }

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

        $usrrights = $this->setInterfaceRight(-1);
        if (!$usrrights['read']) {
            return view('home');
        }

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска: массив из имени и значенния по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_paydate' => ''
            , 's_ownorgid' => ''
            , 's_orgid' => ''
            , 's_paytypeid' => ''
            , 's_paydir' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";


        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_paydate') {
                    if ($val == 1) //сегодня
                        $sc = $sc . " and pd.paydate = curdate()";
                    elseif ($val == 2) //вчера
                        $sc = $sc . " and datediff(curdate(), pd.paydate) = 1";
                    elseif ($val == 3) //за неделю
                        $sc = $sc . " and datediff(curdate(), pd.paydate) <= 7";
                    elseif ($val == 4) //с начала текущего месяца
                        $sc = $sc . " and extract(year_month from pd.paydate) = extract(year_month from curdate())";
                    else
                        $sc = $sc . " and pd.paydate = '{$val}'";

                } elseif ($item == 's_ownorgid') {
                    $sc = $sc . " and pd.ownorgid = {$val}";

                } elseif ($item == 's_orgid') {
                    $sc = $sc . " and pd.orgid = {$val}";

                } elseif ($item == 's_paytypeid') {
                    $sc = $sc . " and pd.paytypeid = {$val}";

                } elseif ($item == 's_paydir') {
                    $sc = $sc . " and pd.paydir = {$val}";

                } elseif ($item == 's_statusid') {
                    $sc = $sc . " and pd.active = {$val}";

                }

            }
        }
        //var_dump($sc);
        //-------------------------------------------------------------------------------------------------------------

        $recs = paydoc::from('paydocs as pd')
            ->join('orgs as oo', function ($join) {
                $join->on('oo.id', '=', 'pd.ownorgid');
            })
            ->join('orgs as o', function ($join) {
                $join->on('o.id', '=', 'pd.orgid');
            })
            ->join('paytypes as pt', function ($join) {
                $join->on('pt.id', '=', 'pd.paytypeid');
            })
            ->whereraw($sc)
            ->select('pd.*'
                , 'oo.name as ownorgname'
                , 'o.name as orgname'
                , 'pt.name as paytype_name'
            );


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderBy('pd.paydate', 'desc')
            ->orderby('pd.id');
//        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 10);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data = new \stdClass();

        $data->sysobj = sysobj::find($this->sysobjid);

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->paytypes = paytype::lstFor([
            'in_paydocs' => 1,
        ]);

        $data->ownorgs = org::lstFor_cached([
            'in_paydocs_ownorgid' => 1,
        ], 5);

        $data->orgs = org::lstFor_cached([
            'in_paydocs_orgid' => 1,
        ], 5);


        $data->statuses = [0 => 'черновик', 2 => 'ожидает согласования', 4 => 'согласован'];
        $data->dates = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц'];
        $data->yes_no = [1 => 'есть', 0 => 'нет'];

        //Выясним - есть ли у пользователя шаблон для этого типа объектов ИС
        $data->template_id = user_template::where(['sysobjid' => $this->sysobjid, 'userid' => $userid])->first()->id ?? null;

        return view($this->sysobjcode . '.index', compact('recs', 'rec0'
            , 'data', 'search_params', 'sort_params'
            , 'usrrights'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request)
    {
        return $this->edit($request, -1);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit(Request $request, $id)
    {
        $userid = \Auth::user()->id;
        $userorgid = \Auth::user()->curorgid;

        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                //Значения "по-умолчанию" для новой записи ----------------

                $paydate = $request->get('paydate');
                $paydate = (isset($paydate)) ? strftime('%Y-%m-%d', strtotime($paydate)) : '';

                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                //dd($tmplt);
                if (isset($tmplt->paydoc)) {
                    $newData = (array)$tmplt->paydoc; //конверитруем в массив
                }

                //$curdate = strftime('%Y-%m-%d', strtotime(now()));

                //Добавим свои значения
                $newData['id'] = -1;
                $newData['paydate'] = $newData['paydate'] ?? $paydate;
                $newData['docdate'] = $newData['docdate'] ?? $paydate;
                $newData['ownorgid'] = $newData['ownorgid'] ?? $userorgid;
                $newData['active'] = 1;
                $newData['created_by'] = $userid;

                $rec = new paydoc($newData);
                //---------------------------------------------------------

            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $sc = '1=1';

            $rec = paydoc::from('paydocs as pd')->whereRaw($sc)->where('id', $id)->first();


            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'));

        }

        //ограничитель для даты - не в будущем
        $rec->maxdate = today()->format('Y-m-d');
        $rec->maxdate = strftime('%Y-%m-%d', strtotime($rec->maxdate));

        //dd($rec->begtime, $rec->endtime, $rec->maxtime, $max_dt, $rec->maxdate);

        //Типы оплат
        $rec->paytypes = paytype::lstFor_cached(['active' => 1]);
        $rec->paydirs = [1 => 'приход', -1 => 'расход'];

        $rec->ownorgs = org::lstFor_cached(['active_or_current' => $rec->ownorgid, 'flagtypeid' => 12]);
        $rec->opertypes = opertype::lstFor(['active_or_current' => 1]);
        $rec->contracts = contract::lstFor(['between_orgs' => [$rec->ownorgid, $rec->orgid]]);

        $rec->status_name = 'черновик';
        $rec->status_style = 'background-color:silver';
        if ($rec->active == 1) {
            $rec->status_name = 'активно';
            $rec->status_style = 'background-color:#b7f192;';
        }

        if ($usrrights['save']) {
            //установим минимально-допустимую дату для wrkdate
            $rec->paydate_min = paydoc::min_paydate();
        }


        return view('paydocs.edit', compact('rec', "usrrights"));
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
        $usrrights = $this->setInterfaceRight($id);
        if (!($usrrights['save']))
            return redirect()->back()->with('error', 'У вас нет права на изменение этих данных!');

        $messages = [
            'ownorgid.required' => 'Не указана компания ГК',
            'orgid.required' => 'Не указан контрагент',
            'driverid.required' => 'Не указан водитель',
            'paydate.required' => 'Укажите дату прихода/расхода денежных средств',
            'paysum.required' => 'Укажите сумму',
        ];

        $rules = [
            'ownorgid' => 'required',
            'orgid' => 'required',
            'paydate' => 'required|date',
            'paysum' => 'required',
        ];

        $request->validate($rules, $messages);

        if (1 == 0) {
            //проверка что запись не пересекается с другой открытой записью с этого объекта за эту дату

            $paydate = $request->get('paydate');
            $machineid = $request->get('machineid');

            $rules = [
                "items_count" => [
                    function ($attribute, $value, $fail) use ($id, $paydate, $machineid) {
                        //
                        $cnt = paydoc::where(['machineid' => $machineid, 'paydate' => $paydate, 'statusid' => 0])
                            ->where('id', '<>', $id)
                            ->count();
                        if ($cnt > 0) {
                            $fail("Есть другой открытый табель для этой технике/даты!");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);
        }

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new paydoc([
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = paydoc::find($id);
            $mess = "Запись обновлена";
        }

        $rec->paydate = $request->get('paydate');
        $rec->paydir = $request->get('paydir');
        $rec->paysum = $request->get('paysum');
        $rec->paytypeid = $request->get('paytypeid');
        $rec->ownorgid = $request->get('ownorgid');
        $rec->orgid = $request->get('orgid');
        $rec->contractid = $request->get('contractid');
        $rec->opertypeid = $request->get('opertypeid');
        $rec->docnum = $request->get('docnum');
        $rec->doсdate = $request->get('doсdate');
        $rec->reason = mb_substr($request->get('reason'), 0, 160);

        $rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        //сформируем/обновим фин. операции ------------------------------------------------------
        paydoc::rfr_finopers($rec);
        //---------------------------------------------------------------------------------------

        if (1 == 0 and $id == -1)
            return redirect(route('paydocs.edit', $rec->id));
        else
            return redirect(route('paydocs.index'));
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
        $res = paydoc::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('paydocs.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['docnum'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('paydocs.index');
            connectify('success', ($res->obj['docnum'] ?? '-'), 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    public function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = paydoc::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        $document['tags'] = objtag::lstTags($this->sysobjid, $id);
        //dd($document);

        $template_js = [
            'paydoc' => $document,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

}
