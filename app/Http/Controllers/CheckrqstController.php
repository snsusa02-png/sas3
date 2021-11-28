<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\checkrqst;
use App\contract;
use App\obj_reader;
use App\objfile;
use App\objlog;
use App\objtag;
use App\org;
use App\orgstaff;
use App\place;
use App\refitem;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\unittype;
use App\user_template;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use stdClass;
use Barryvdh\DomPDF\Facade as PDF;
use Illuminate\Support\Carbon;

class CheckrqstController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1761;
        $this->sysobjcode = 'checkrqsts';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    /*
     * Установка прав пользователя
     */
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
        $usrrights['print_rqst'] = false;
        $usrrights['send_rqst'] = false;

        $usrrights['manager'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.manager');

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
            $usrrights['print_rqst'] = true;
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

        //$usrrights = $this->setInterfaceRight(-1);
        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.save'),
            'manager' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.manager'),
        );

        if (!$usrrights['read']) {
            return view('home');
        }

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска: массив из имени и значенния по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_date' => ''
            , 's_name' => ''
            , 's_chktypeid' => ''
            , 's_inituserid' => ''
//            , 's_driverid' => ''
//            , 's_paytypeid' => ''
//            , 's_load_placeid' => ''
//            , 's_unload_placeid' => ''
//            , 's_orgid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";


        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and concat(ifnull(cr.site,''),'|',ifnull(cr.location,''),'|',ifnull(cr.drawing,''),'|',ifnull(cr.chk_descript,'')) like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_chktypeid') {
                    $sc = $sc . " and cr.chktypeid = {$val}";

                } elseif ($item == 's_inituserid') {
                    $sc = $sc . " and cr.inituserid = {$val}";

                } elseif ($item == 's_date') {
                    if ($val == 1) //сегодня
                        $sc = $sc . " and cr.docdate = curdate()";
                    elseif ($val == 2) //вчера
                        $sc = $sc . " and datediff(curdate(), cr.docdate) = 1";
                    elseif ($val == 3) //за неделю
                        $sc = $sc . " and datediff(curdate(), cr.docdate) <= 7";
                    elseif ($val == 4) //с начала текущего месяца
                        $sc = $sc . " and extract(year_month from cr.docdate) = extract(year_month from curdate())";

                } elseif ($item == 's_load_placeid') {
                    $sc = $sc . " and cr.load_placeid = {$val}";

                } elseif ($item == 's_unload_placeid') {
                    $sc = $sc . " and cr.unload_placeid = {$val}";

                } elseif ($item == 's_orgid') {
                    $sc = $sc . " and cr.orgid = {$val}";

                } elseif ($item == 's_paytypeid') {
                    $sc = $sc . " and cr.paytypeid = {$val}";

                } elseif ($item == 's_inituserrid') {
                    $sc = $sc . " and cr.inituserrid = {$val}";

                } elseif ($item == 's_statusid') {
                    $sc = $sc . " and cr.statusid = {$val}";

                }

            }
        }
        //var_dump($sc);
        //-------------------------------------------------------------------------------------------------------------

        //по-старому ---------------
        //для совместимости со старым методом формированя условия отбора - инициализируем переменные поиска
//        foreach ($search_params as $item => $val) {
//            $$item = $val;
//        }
        // --------------------------------------------------------------------


        $recs = checkrqst::from('checkrqsts as cr')
            ->leftjoin('users as iu', function ($join) {
                $join->on('iu.id', '=', 'cr.inituserid');
            })
            ->leftjoin('buildobj_staffs as bos1', function ($join) {
                $join->on('bos1.id', '=', 'cr.bostf1_id');
            })
            ->leftjoin('orgstaff as os1', function ($join) {
                $join->on('os1.id', '=', 'bos1.staffid');
            })
            ->leftjoin('buildobj_staffs as bos2', function ($join) {
                $join->on('bos2.id', '=', 'cr.bostf2_id');
            })
            ->leftjoin('buildobj_staffs as bos3', function ($join) {
                $join->on('bos3.id', '=', 'cr.bostf3_id');
            })
            ->leftjoin('orgstaff as os3', function ($join) {
                $join->on('os3.id', '=', 'bos3.staffid');
            })
            ->leftjoin('buildobj_staffs as bos4', function ($join) {
                $join->on('bos4.id', '=', 'cr.bostf4_id');
            })
            ->leftjoin('orgstaff as os4', function ($join) {
                $join->on('os4.id', '=', 'bos4.staffid');
            })
            ->leftjoin('buildobj_staffs as bos5', function ($join) {
                $join->on('bos5.id', '=', 'cr.bostf5_id');
            })
            ->whereraw($sc)
            ->select('cr.*'
                , 'iu.name as inituser_name'
                , 'os1.name as bostf1_name'
                , 'bos2.staffname as bostf2_name'
                , 'os3.name as bostf3_name'
                , 'os4.name as bostf4_name'
                , 'bos5.staffname as bostf5_name'
            );


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderBy('cr.docdate', 'desc')
            ->orderby('cr.id');
//        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 10);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data = new \stdClass();

        $data->sysobj = $this->sysobjid;

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->orgs = org::lstFor_cached([
            'in_checkrqsts_orgid' => 1,
        ], 5);

        $data->initusers = User::lstFor_cached([
            'in_checkrasts_inituserid' => 1,
        ], 5);

        $data->chktypes = checkrqst::chktypes();

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

        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                //Значения "по-умолчанию" для новой записи ----------------

                $wrkdate = $request->get('wrkdate');
                $wrkdate = (isset($wrkdate)) ? strftime('%Y-%m-%d', strtotime($wrkdate)) : '';

                //                $rec = new checkrqst([
//                    'id' => -1,
//                    //'machineid' => $machineid,
//                    'wrkdate' => $wrkdate,
//                    'begtime' => '08:00',
//                    'endtime' => '18:00',
//                    'break_hrs' => '1',
//                    'statusid' => 0,
//                    'active' => 1,
//                    'created_by' => $userid,
//                ]);


                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                //dd($tmplt);
                if (isset($tmplt->checkrqst)) {
                    $newData = (array)$tmplt->checkrqst; //конверитруем в массив
                }

                //$curdate = strftime('%Y-%m-%d', strtotime(now()));

                //Добавим свои значения
                $newData['id'] = -1;

                $newData['buildobjid'] = 34;
                $newData['org5_id'] = 321;
                $newData['ownorgid'] = 1;
                $newData['contractid'] = 395;
                $newData['bostf3_id'] = 40;
                $newData['bostf4_id'] = 136;
                $newData['bostf5_id'] = 138;

                $newData['docdate'] = $newData['docdate'] ?? $wrkdate;
                $newData['inituserrid'] = $userid;
                //$newData['statusid'] = 0;
                $newData['active'] = 1;
                $newData['created_by'] = $userid;

                $rec = new checkrqst($newData);
                //---------------------------------------------------------

            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $sc = '1=1';

            $rec = checkrqst::from('checkrqsts as crs')->whereRaw($sc)->where('id', $id)->first();

            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'));

        }

        //отправлять можно, если хэш для pdf соответствует текущему хэшу основных данных записи
        $usrrights['send_rqst'] = (
            file_exists(storage_path('app/files/1761/rqst_') . $rec->id . '.pdf')
            and $rec->hash() == $rec->pdf_hash);


        if (isset($rec->plnbegdt))
            $rec->plnbegdt = strftime('%Y-%m-%dT%H:%M', strtotime($rec->plnbegdt));
        if (isset($rec->plnenddt))
            $rec->plnenddt = strftime('%Y-%m-%dT%H:%M', strtotime($rec->plnenddt));
        if (isset($rec->fctbegdt))
            $rec->fctbegdt = strftime('%Y-%m-%dT%H:%M', strtotime($rec->fctbegdt));
        if (isset($rec->fctenddt))
            $rec->fctenddt = strftime('%Y-%m-%dT%H:%M', strtotime($rec->fctenddt));

        $works = explode(',', $rec->works);
        $tmp = [];
        foreach ($works as $key => $value) {
            $tmp[$value] = 1;
        }
        $rec->works = $tmp;


        //ограничитель для времени - не в прошлом, не ранее чем через 24 часа
        //$min_dt = date_create(date('Y-m-d H:i:s', strtotime('+6 hour -1 second', strtotime(now()))));
        $min_dt = Carbon::now()->addHour(24);
        $min_dt->second = 0;
        //если время получилось до начала рабочего дня 9-18, то установим минимальное время
        if ($min_dt->hour < 9) {
            $min_dt->hour = 9;
            $min_dt->minute = 0;
        }
        if ($min_dt->hour >= 18) {
            $min_dt->addDay(1);
            $min_dt->hour = 9;
            $min_dt->minute = 0;
        }
        $rec->minbegdt = strftime('%Y-%m-%dT%H:%M', strtotime($min_dt->format('Y-m-d H:i:s')));


        //доступные виды инспекций
        $rec->chktypes = checkrqst::chktypes();

        $rec->buildobjs = buildobj::lstFor(['in_buildobj_staff' => $userid]);
        $rec->contracts = contract::lstFor(['between_orgs' => [$rec->ownorgid, $rec->org5_id]]);


//        if (!isset($rec->mot_id) and count($rec->mots) == 1)
//            $rec->mot_id = array_key_first($rec->mots);


        $rec->status_name = 'черновик';
        $rec->status_style = 'background-color:silver';
        if ($rec->active == 1) {
            $rec->status_name = 'активно';
            $rec->status_style = 'background-color:#b7f192;';
        }

//        $usrrights['delete'] = ($usrrights['delete'] and ($rec->created_by == $userid or $usrrights['manager']) and $rec->statusid == 0);
//        $usrrights['save'] = ($usrrights['save'] and ($rec->created_by == $userid or $usrrights['manager']) and $rec->statusid == 0);
//        $usrrights['edit'] = (($rec->created_by == $userid or $usrrights['manager']) and $rec->statusid == 0);
        $usrrights['edit'] = ($usrrights['save']);
//        $usrrights['change_status'] = (($rec->created_by == $userid or $usrrights['manager']));

        //корректировка прав с учетом статуса -------------------------------------------

//        if ($rec->statusid != 0) {
//            $usrrights['create'] = $usrrights['delete'] = false;
//        }
        //-------------------------------------------------------------------------------

//        //сконструируем права для внутренних списков
//        $usrrights['checkrqst_items.create'] = $usrrights['create'];
//        $usrrights['checkrqst_items.save'] = $usrrights['save'];

        return view($this->sysobjcode . '.edit', compact('rec', "usrrights"));
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
        //проверим текущий статус документа
        $statusid = ($id == -1) ? 0 : checkrqst::find($id)->statusid ?? 0;

        $statusid = 0;    //временно
        if ($statusid == 0) {
            //черновик
            $messages = [
                'ownorgid.required' => 'Не указан строительный подрядчик',
                'buildobjid.required' => 'Не указан объект строительства',
                'chktypeid.required' => 'Укажите тип инспекции',
                'plnbegdt.required' => 'Укажите дату/время планируемого проведения инспекции',
                'statusid.required' => 'Укажите статус готовности документа',
            ];

            $rules = [
                'ownorgid' => 'required',
                'buildobjid' => 'required',
                'chktypeid' => 'required',
                'plnbegdt' => 'required',
            ];
        } else {
            $messages = [
                'statusid.required' => 'Укажите статус готовности документа',
            ];

            $rules = [
                "statusid" => "required",
            ];
        }

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new checkrqst([
                "active" => 0,
                //"statusid" => 0,
                "inituserid" => $userid,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = checkrqst::find($id);
            $mess = "Запись обновлена";
        }

        if ($statusid == 0) {

            $rec->buildobjid = $request->get('buildobjid');
            $rec->docdate = $request->get('docdate');
            $rec->docnum = $request->get('docnum');
            $rec->plnbegdt = $request->get('plnbegdt');
            $rec->plnenddt = $request->get('plnenddt');
            $rec->ownorgid = $request->get('ownorgid');
            $rec->contractid = $request->get('contractid');
            $rec->chktypeid = $request->get('chktypeid');

            $rec->site = mb_substr($request->get('site'), 0, 90);
            $rec->location = mb_substr($request->get('location'), 0, 90);
            $rec->drawing = mb_substr($request->get('drawing'), 0, 90);
            $rec->chk_descript = mb_substr($request->get('chk_descript'), 0, 300);
            $rec->aux_docs = mb_substr($request->get('aux_docs'), 0, 300);

            //$rec->notes = mb_substr($request->get('notes'), 0, 300);

            $rec->org1_id = $request->get('org1_id');
            $rec->org2_id = $request->get('org2_id');
            $rec->org3_id = $request->get('org3_id');
            $rec->org4_id = $request->get('org4_id');
            $rec->org5_id = $request->get('org5_id');

            $rec->bostf1_id = $request->get('bostf1_id');
            $rec->bostf2_id = $request->get('bostf2_id');
            $rec->bostf3_id = $request->get('bostf3_id');
            $rec->bostf4_id = $request->get('bostf4_id');
            $rec->bostf5_id = $request->get('bostf5_id');

            $works = $request->get('works') ?? [];
            $rec->works = implode(',', $works);
            //$rec->inituserrid = $request->get('inituserrid');

            $rec->auxwork1_name = $request->get('auxwork1_name');
            $rec->auxwork2_name = $request->get('auxwork1_name');


        } elseif ($statusid == 2) {
            //согласование

            if ($request->get('statusid') == 4) {
                //переводим на "Согласовано"
                $rec->mngr_signed = 1;
                $rec->mngr_signed_by = $userid;
                $rec->mngr_signed_at = now();

            } elseif ($request->get('statusid') == 0) {
                //переводим на "Редактирование"
                $rec->stf_signed = null;
                $rec->stf_signed_at = null;

                $rec->mngr_signed = null;
                $rec->mngr_signed_by = null;
                $rec->mngr_signed_at = null;
            }

        }

        //$rec->statusid = $request->get('statusid', 0);
        $rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        //dd($rec);
        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        if ($id == -1 or $rec->statusid <> $statusid)
            return redirect(route($this->sysobjcode . '.edit', $rec->id));
        else
            return redirect(route($this->sysobjcode . '.index'));
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
        $res = checkrqst::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('checkrqsts.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('checkrqsts.index', ['machineid' => $res->obj['machineid'] ?? 0, 'parid' => $res->obj['planid'] ?? 0]);
            connectify('success', ($res->obj['name'] ?? '-'), 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    public
    function print_rqst(Request $request, $id)
    {
        //печать в форме заявки

        $userid = \Auth::user()->id;

        $rec = checkrqst::find($id);

        //dd( $rec->hash_base(), $rec->hash(), $rec->pdf_hash);

        $filename = "rqst_{$rec->id}.pdf";
        $systemfilename = 'files/1761/' . $filename;
        $disk = 'local';
        if (1 == 0 and Storage::disk($disk)->exists($systemfilename)
            and $rec->hash() == $rec->pdf_hash) {
            //откроем без пересоздания, так как основное содержание документа соответствует pdf-файлу
            $type = 'application/pdf';

            return Storage::disk($disk)->download($systemfilename, $filename
                , [
                    "Content-Type" => $type,
                    "Content-Disposition" => "inline;filename=" . $filename
                ]);
        }


        $data = new stdClass();
        $data->buildobj = buildobj::find($rec->buildobjid);
        $data->contract_num = $rec->contract->docnum;//"СКБ/2021-4";
        $data->plnbegdt = date_create($rec->plnbegdt)->format('d.m.Y H:i');
        $data->fctbegdt = '';
        $data->chk_descript = $rec->chk_descript;//"Армирование в осях 12-15 на с отм.-10.500 до отм. -7.300";
        $data->aux_docs = $rec->aux_docs;//"Акты скрытых работ";
        $data->results = "";

        $data->orgs = [1 => '', 2 => '', 3 => '', 4 => ''];
        $data->orgs[1] = ($rec->bostf1->orgid) ? 'X' : '';
        $data->orgs[2] = ($rec->bostf2->orgid) ? 'X' : '';
        $data->orgs[3] = ($rec->bostf3->orgid) ? 'X' : '';
        $data->orgs[4] = ($rec->bostf4->orgid) ? 'X' : '';

        $data->other_orgs = 'никого';


        $works = explode(',', $rec->works);
        $tmp = [];
        foreach ($works as $key => $value) {
            $tmp[$value] = 1;
        }
        $tmp2 = $tmp;

        $tmp = [1 => '', 2 => '', 3 => '', 4 => '', 5 => '', 6 => '', 7 => '', 8 => '', 9 => '', 10 => ''
            , 11 => '', 12 => '', 13 => '', 14 => '', 15 => '', 16 => ''];
        foreach ($tmp2 as $key => $item) {
            $tmp[$key] = 'X';
        }
        $data->works = $tmp;
        //dd($data->works);


        $data->auxwork1_name = '';
        $data->auxwork2_name = '';


        //обновим отметку о подготовке образа заявки (pdf-файла) -----------------------------
        //dd( $rec->hash_base(), $rec->hash(), $rec->pdf_hash);

        checkrqst::where('id', $rec->id)->update([
            'pdf_at' => now(), 'pdf_by' => $userid, 'pdf_hash' => $rec->hash()
            , 'updated_at' => DB::raw('updated_at')
            , 'updated_by' => DB::raw('updated_by')
        ]);
        //------------------------------------------------------------------------------------


        $pdf = PDF::loadView('checkrqsts.prnt_rqst', compact('rec', 'data'))
            ->setPaper('a4', 'landscape')
            ->setWarnings(false)
            ->save(storage_path('app/files/1761/') . $filename);

        //objfile::addFile2Obj(storage_path('app/files/1761/') . $filename,

        //return $pdf->download('Заявка.pdf');
        //return $pdf->save(storage_path('app/files/1761/') . $filename)->stream($filename);
        return $pdf->stream($filename);

        return view('checkrqsts.prnt_rqst', compact('rec', 'data'));

    }

    public function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = checkrqst::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        $document['tags'] = objtag::lstTags($this->sysobjid, $id);
        //dd($document);

        $template_js = [
            'checkrqst' => $document,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

}
