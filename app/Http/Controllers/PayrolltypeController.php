<?php

namespace App\Http\Controllers;

use App\chargetype;
use App\doctype;
use App\objflag;
use App\objlog;
use App\org;
use App\orgstaff;
use App\payrolltype;
use App\salary_rate_set;
use App\stf_chrg_calc;
use App\stforder;
use App\sysobj;
use App\Traits\Result;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use App\workertype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PayrolltypeController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1221;
        $this->sysobjcode = 'payrolltypes';
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
        $usrrights['load'] = usrsysright::isUserHasRightByCode_cached($userid, 'admin-global');

        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = ($recid <> -1 and usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.admindelete'));
//        $usrrights['private_acs'] = ($recid <> -1 and usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.private_acs'));
        $usrrights['private_acs'] = (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.private_acs'));

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');


        $tmp_sysobjcode = $this->sysobjcode;
        $this->sysobjcode = 'orgplnpays';

        $usrrights['agr1'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.agr1');
        $usrrights['agr2'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.agr2');
        //$usrrights['regpay'] = usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.regpay');

        $usrrights['stf_salaries.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'stf_salaries.read');

        $this->sysobjcode = $tmp_sysobjcode;

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


        session([$this->sysobjcode . '_pageno' => $request->page ?? 1]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_active' => '1'
            , 's_orgflagid' => 12
            , 's_orgid' => ''
            , 's_name' => ''
            , 's_dir' => ''
            , 's_file_doctypeid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);
        //сформируем условие запроса в БД -----------------------
        $sc = payrolltype::search_cond($search_params);
        //dd($sc);
        //-------------------------------------------------------

        $recs = payrolltype::from('payrolltypes as prt')
            ->whereraw($sc)
            ->select('prt.id', 'prt.name', 'prt.descript', 'prt.active');

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        $recs = $recs->orderBy('prt.ordr', 'asc');
        $recs = $recs->orderBy('prt.name', 'asc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('prt.name', 'asc');
        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 20);
        //--------------------------------------------------------------

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid);

        //номер первой записи на странице:
        $data->rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data->search_params = $search_params;

        //$data->dirs = [1 => 'начисление', -1 => 'удержание'];
        $data->statuses = [1 => 'актив', 0 => 'архив'];

        //$data->file_doctypes = doctype::lstUsedForSysObj($this->sysobjid);

        return view($this->sysobjcode . '.index', compact(['recs', 'data', 'usrrights']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $orgid)
    {
        $usrrights = $this->setInterfaceRight(-1);
        if (!$usrrights['create'])
            return redirect()->back()->with('error', 'У вас нет права на создание записей!');

        return $this->edit($request, -1, $orgid);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit(Request $request, $id, $orgid = null)
    {
        //
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');

        $userid = \Auth::user()->id;

        if ($id == -1) {
            $orgid = ($orgid == 0) ? null : $orgid;
            $rec = new payrolltype([
                'id' => -1,
                'orgid' => $orgid,
                'begdate' => date_create()->format('Y-m-d'),
                'active' => 1,
                'created_by' => $userid,
            ]);
            //dd($rec);
        } else
            $rec = payrolltype::find($id);

        if (!isset($rec))
            return redirect(route('orgs.index'))->with(['error' => 'Запись не найдена!']);

        $rec->retURL = $request->get('returl');

        //$data = new \stdClass();

        //$rec->isownorg = org::isOwnOrg($rec->orgid);

        $rec->orgs = org::lstFor([
            'flagtypeid_or_id' => [12, $rec->orgid],
        ]);

        $rec->rate_sets = null;

        /*       $rec->chargetypes = chargetype::lstFor([
                   'active_or_current' => $rec->chargetypeid,
               ]);
               $rec->charge_periods = chargetype::charge_periods();
       dd($rec->chargetypes, $rec->charge_periods);
       */
        $rec->userrights = [];
        $usrrights = $this->setInterfaceRight($id);


        $rec->flags = objflag::FlagTypesForObj($this->sysobjid, $rec->id);

        if ($id <> -1) {

            $rec->rate_sets = salary_rate_set::where('payrolltypeid', $rec->id)
                ->orderby('begdate', 'desc')->get();

            // Список сотрудников, использующих данную схему начисления в данный момент
            $rec->ref_staff = orgstaff::from('orgstaff as os')
                ->join('stf_payrolltypes as spr', 'spr.staffid', 'os.id')
                ->where('spr.payrolltypeid', $rec->id)
                ->wherenull('spr.enddate')
                ->select('os.id', 'os.name', 'spr.begdate')
                ->orderby('os.name')
                ->get();

            $acl_sysobjcode = sysobj::acl_sysobjcode('stforders');
            if (usrsysright::isUserHasRightByCode_cached($userid, $acl_sysobjcode . '.read')) {

            }

        }

        return view('payrolltypes.edit', compact(['rec', 'usrrights']));
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
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['read'])
            return redirect()->back()->with('error', 'У вас нет права на доступ к этой информации!');
        if ($id == -1 and !$usrrights['create'])
            return redirect()->back()->with('error', 'У вас нет права на создание записей!');
        if ($id <> -1 and !$usrrights['save'])
            return redirect()->back()->with('error', 'У вас нет права на изменение записей!');

        $messages = [
            'name.required' => 'Введите название схемы/способа расчета ЗП',
            //'stdpostunit.gt' => 'Ставка не может быть равна нулю',
            //'postname.required' => 'Укажите должность',
//            'inn.digits' => 'В ИНН должно быть 12 цифр',
//            'snils.size' => 'Длина СНИЛС должна быть 14 символов',
        ];

        $rules = [
            "name" => "required",
            //"postname" => "required",
            //'phone' => 'required|max:20',
//            'inn' => 'nullable|digits:12',
//            'snils' => 'nullable|size:14',
        ];

        $orgid = $request->get('orgid');

//        $strictDepPost = (orgdep::where('orgid', $orgid)->count() > 0);
//
//        if ($strictDepPost) {
//            $rules['depid'] = 'required';
//            $rules['postid'] = 'required';
//            $rules['stdpostunit'] = 'required|gt:0';
//        }
        //dd($rules);

        $request->validate($rules, $messages);
        //$request->validate($rules, $messages)->validateWithBag('post');

        $userid = \Auth::user()->id;

        $mess = "";
        if ($id == -1) {
            $os = new payrolltype();
            $os->created_by = $userid;
            $os->created_at = now();
            $mess = "Создана запись о применяемом начислении для сотрудника";
        } else {
            $os = payrolltype::find($id);
            $mess = "Изменена запись о применяемом начислении для сотрудника";
        }
        $os->name = $request->get('name');
        $os->descript = mb_substr($request->get('descript'), 0, 360);

        $os->ordr = $request->get('ordr');
        $os->active = $request->get('active') ?? 1;
        $os->updated_by = $userid;
        $os->save();

        //Cache::forget('org_aux_staff_.' . $os->orgid);

        $retURL = $request->get('returl') ?? route($this->sysobjcode . '.index')
            . '?page=' . session($this->sysobjcode . '_pageno') . '#' . $os->id;

        return redirect($retURL)->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy(Request $request, $id)
    {
        $usrrights = $this->setInterfaceRight($id);
        if (!$usrrights['delete'])
            return redirect()->back()->with('error', 'У вас нет права на удаление записей!');

        $userid = \Auth::user()->id;

        $retURL = $request->get('returl') ?? route($this->sysobjcode . '.edit', $id);

        if (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete')) {

            $res = payrolltype::delete_by_id($id);
            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $sd["error"] = $res->msg;
            } else {

                $retURL = $request->get('returl') ?? route($this->sysobjcode.'.index');
                $sd['success'] = 'Запись о сотруднике удалена';
            }
        } else {
            $sd['success'] = 'У вас нет прав на удаление записей!';
        }
        return redirect($retURL)->with($sd);
    }


    static public function listpayrolltype(Request $request)
    {
        //для AJAX-запросов

        $result = "";
        try {
            $orgid = $request->orgid;
            $list = payrolltype::where('orgid', $orgid)
                ->where('active', 1)
                //->select('id', DB::raw("concat(lname,' ', fname, ' ', mname, ', ', postname) as name"))
                ->select('id', DB::raw("concat(lname,' ', fname, ' ', mname, ', ', ifnull(postname,'-')) as name"))
                ->orderBy('lname')
                ->get()->pluck('name', 'id')->toArray();

            $result = array('staff' => $list);

        } catch (\Exception $e) {
        }
        return response()->json($result);

    }


    static public function get_for(Request $request)
    {
        //2021-07-03 SNS. Обертка для вызова payrolltype::getFor

        $result = "";
//        try {

        $list = payrolltype::getFor([
            'active' => $request->active,
            'active_or_current' => $request->active_or_current,
            'in_documents' => $request->in_documents,
            'name' => $request->name ?? $request->q,
            'q' => $request->q,
            'orgid' => $request->orgid,
        ], [
            'oc.id', db::raw("concat(oc.name,', ', ifnull(op.name,oc.postname), ' ', o.name) as name")
        ]);


        //$result = array('doctypes' => $list);
        $result = $list;

//        } catch (\Exception $e) {
//            Log::error('payrolltype::list_for:' . $e->getMessage());
//        }
        return response()->json($result);
    }


    public function load()
    {
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight(-1);
        $rec = new \stdClass();

        return view($this->sysobjcode . '.load', compact('rec', "usrrights"));
    }


    public function import(Request $request)
    {
        //Импорт без сохранения файла на диск. Только обработка

        $messages = [
            'doc.required' => 'Не указан файл с данными',
        ];

        $rules = [
            "doc" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        //$returl = $request->get('retroute');

        $usrrights = $this->setInterfaceRight(-1);
        $result = new Result();
        $rec = new \stdClass();

        $rec->extsysid = 9;   // ? М.б. использовать для связывания по кодам во внешней системе
        //dd($rec);

        if ($request->hasfile('doc')) {

            $file = $request->doc;

            $filesize = $file->getSize();
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            //dd($name, $extension, $filesize);

            if (1 == 1)
                $rec = payrolltype::import_001($file, $rec);
            else {
                $result->err = 1;
                $result->msg = 'Не определена процедура импорта!';
            }
            //--------------------------------------------------------------------------------
            //dd($result->msg);


        } else {
            $result->err = 1;
            $result->msg = 'Файл с данными не загружен!';
        }

        return view($this->sysobjcode . '.load', compact('rec', "usrrights"));
    }

    static public function list_for_ac(Request $request)
    {
        //2023-03-19 SNS. Для автокомплита

        $result = "";
        try {

            $list = payrolltype::getFor([
                'name' => $request->name,
                'orgid' => $request->orgid,
            ],
                ['oc.id', 'ct.name', 'ct.dir', 'oc.charge_sum']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('payrolltype::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }

    function rep56(Request $request)
    {
        //Детализация производства и отгрузки продукции за дату

        $report_id = 56;

        $returl = $request->get('returl') ?? route('home');
        $userid = Auth::user()->id;
        $export2xls = $request->get('xls') ?? 0;

        $param_names = [
            's_ym' => null,
        ];

        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);

        $begdate = today();

        $s_ym = $search_params['s_ym'];
        //$s_ym = null;

        $data = new \stdClass();

        $data->returl = $returl;

        if ($s_ym <> '') {
            //dd( $s_ym . '-01', date_create($s_ym . '-01' ) );
            $date = date_create($s_ym . '-01')->format('Y-m-d');
            //dd($date);

            $date = $date ?? date_create()->format('d-m-Y');
            $data->begdate = date_create($date)->format('Y-m-01');   //Первый день месяца
            $data->enddate = date_create($date)->format('Y-m-t');    //Последний день месяца


            // Какие виды начислений/Удержаний попали в рассматриваемый месяц
            $sql = "SELECT ct.id as id, ct.name, sum(scc.charge_sum) charge_sum
                    FROM stf_chrg_calcs as scc
                    join orgstaff os on os.id=scc.staffid
                    join payrolltypes as oc 	on oc.id=scc.orgchargeid
                    join chargetypes as ct on ct.id=oc.chargetypeid
                    where forbegdate <= '" . date_create($data->enddate)->format('Y-m-d') . "'"
                . " and forEndDate >= '" . date_create($data->begdate)->format('Y-m-d') . "'
                    group by ct.id
                    order by ct.dir desc, ct.ordr";
            $data->cols = DB::select(DB::raw($sql));
//dd($sql, $data->cols);

            $sql = "SELECT scc.staffid, os.lname, os.fname, os.mname
                    , os.orgid, o.name as org_name, ct.dir, oc.chargetypeid, ct.name as chargetype_name
                    , sum(scc.charge_sum) charge_sum
                    FROM stf_chrg_calcs as scc
                    join orgstaff os on os.id=scc.staffid
                    join orgs o on o.id=os.orgid
                    join payrolltypes as oc 	on oc.id=scc.orgchargeid
                    join chargetypes as ct on ct.id=oc.chargetypeid
                    where forbegdate <= '" . date_create($data->enddate)->format('Y-m-d') . "'"
                . " and forEndDate >= '" . date_create($data->begdate)->format('Y-m-d') . "'
                     group by scc.staffid, oc.chargetypeid
                    order by o.name, os.lname, os.fname, os.id, ct.dir desc, ct.ordr";

            $recs = DB::select(DB::raw($sql));
        } else {
            $recs = null;
        }

        // Заполним массив "Год.Месяц" уникальными значениями из первичных данных
        $month_names = Config::get('constants.monthes');
        Cache::forget('stf_chrg_calc_monthes');
        $data->yms = Cache::remember('stf_chrg_calc_monthes', now()->addMinutes(15)
            , function () {
                return stf_chrg_calc::selectRaw("date_format(forbegdate, '%Y-%m') as ym")->distinct()->orderby('ym', 'desc')
                    ->get()->pluck('ym', 'ym')->toArray();
            });
        //dd($data->monthes);
        foreach ($data->yms as $key => $val) {
            $y = substr($val, 0, 4);
            $m = 0 + substr($val, 5);

            $data->yms[$val] = $month_names[$m] . ' ' . $y;
            //dd($key,$val, $m, $y, $data->yms[$val]);
        }
        //dd($data->yms);
        //dd($data, $sql, $recs);

        //занесем в журнал
        objlog::log_info(855, $report_id, 'запрошен отчет;');
//        if ($export2xls == "1") {
//            $response = Excel::download(new rep54Export($recs, $data), "Платежи за " . Str::slug($data->$date) . ".xlsx", \Maatwebsite\Excel\Excel::XLSX);
//
//            //$response= Excel::download(new InvoicesExport, 'invoices.xls', \Maatwebsite\Excel\Excel::XLS);
//            //HERE IS THE MAGIC FOLKS
//            ob_end_clean();
//            return $response;
//        }

        return view('payrolltypes.rep' . $report_id, compact('search_params', 'data', 'recs'));
    }
}
