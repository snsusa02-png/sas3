<?php

namespace App\Http\Controllers;

use App\contract;
use App\regnum_src;
use App\contract_category;
use App\org;
use App\objlog;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\user_template;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class RegnumSrcController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 158;
        $this->sysobjcode = 'regnum_srcs';
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
        $userid = Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');

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
            'view_all' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.view_all'),
        );
        if (!$usrrights['read']) {
            return view('home');
        }

        session([$this->sysobjcode . '_pageno' => $request->page]);

        // - параметры поиска: массив из имени и значенния по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 10
            , 's_name' => ''
            , 's_ownorgid' => ''
            , 's_categoryid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and rns.name like '%" . mb_strtoupper($val) . "%'";

                } elseif ($item == 's_ownorgid') {
                    $sc = $sc . " and rns.ownorgid = {$val}";

                } elseif ($item == 's_categoryid') {
                    $sc = $sc . " and rns.contract_categoryid = {$val}";

                } elseif ($item == 's_active') {
                    $sc = $sc . " and rns.active = {$val}";

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


        $recs = regnum_src::from('regnum_srcs as rns')
            ->join('orgs as oo', function ($join) {
                $join->on('oo.id', '=', 'rns.ownorgid');
            })
            ->join('contract_categories as cc', function ($join) {
                $join->on('cc.id', '=', 'rns.contract_categoryid');
            })
            ->whereraw($sc)
            ->select('rns.id', 'rns.name'
                , 'rns.active', 'rns.descript', 'rns.num_prefix', 'rns.nextnum', 'rns.num_suffix'
                , 'rns.ownorgid', 'rns.contract_categoryid'
                , 'oo.name as ownorg_name'
                , 'cc.name as category_name'
            );


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderBy('ownorg_name', 'desc')
            ->orderby('rns.ordr')
            ->orderby('rns.name');
//        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 10);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid) ?? null;

        $data->ownorgs = org::lstFor(
            ['in_regnum_srcs' => 1,]
        );

        $data->categories = contract_category::lstFor([
            'in_regnum_srcs' => 1,
        ]);

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

                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                if (isset($tmplt->regnum_src)) {
                    $newData = (array)$tmplt->regnum_src; //конверитруем в массив
                }

                //Добавим свои значения
                $newData['id'] = -1;
                $newData['active'] = 1;
                $newData['created_by'] = \Auth::user()->id;

                $rec = new regnum_src($newData);
                //---------------------------------------------------------

            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $sc = '1=1';

            $rec = regnum_src::from('regnum_srcs as crs')->whereRaw($sc)->where('id', $id)->first();


            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'));

        }

        $rec->sysobj = sysobj::find($this->sysobjid);

        $rec->ownorgs = org::lstFor([
            'flagtypeid' => 12,
            'active_or_current' => 1,
        ]);
        $rec->categories = contract_category::lstFor([
            'active_or_current' => 1,
        ]);

        $rec->min_nextnum = (contract::where('regnum_srcid', $id)->max('regnum_num') ?? 0) + 1;

        $rec->status_name = 'черновик';
        $rec->status_style = 'background-color:silver';
        if ($rec->active == 1) {
            $rec->status_name = 'доступно для использования';
            $rec->status_style = 'background-color:#b7f192;';
        }

        //Статистика использования в договорах
        $rec->usage_cnt = contract::where('regnum_srcid', $id)->count();


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
        $userid = Auth::user()->id;

        $messages = [
            'name.required' => 'Укажите название источника',
            'ownorgid.required' => 'Не указана организация-владелец/пользователь источника номеров',
            'contract_categoryid.required' => 'Не указана категория договора',
        ];

        $rules = [
            'name' => 'required',
            'ownorgid' => 'required',
            'contract_categoryid' => 'required',
        ];

        $request->validate($rules, $messages);

        if (1 == 1 and $id <> -1) {

            //проверка на то, что указанный nextnum не меньше чем максимальный использованный в contracts

            $prm = new \stdClass();
            $prm->id = $id;
            $prm->nextnum = $request->get('nextnum');

            $rules = [
                //В форме должно быть поле ttt
                "ttt" => [
                    function ($attribute, $value, $fail) use ($prm) {
                        //
                        $max = contract::where([
                            'regnum_srcid' => $prm->id,
                        ])->max('regnum_num');

                        if ($max >= $prm->nextnum) {
                            $fail("Следующий Номер должен быть не меньше, чем уже использованный в договорах: {$max}!");
                        }
                    },
                ],

            ];

            $request->validate($rules, $messages);
        }


        $mess = "";
        if ($id == -1) {
            $rec = new regnum_src([
                "active" => 1,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = regnum_src::find($id);
            $mess = "Запись обновлена";
        }

        $rec->name = mb_substr($request->get('name'), 0, 60);
        $rec->ownorgid = $request->get('ownorgid');
        $rec->contract_categoryid = $request->get('contract_categoryid');
        $rec->descript = mb_substr($request->get('descript'), 0, 160);

        $rec->num_prefix = mb_substr($request->get('num_prefix'), 0, 8);
        $rec->nextnum = $request->get('nextnum', 1) ?? 1;
        $rec->num_suffix = mb_substr($request->get('num_suffix'), 0, 8);

        $rec->active = $request->get('active', 1);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();
        //dd($rec);

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

//        if ($id == -1)
//            return redirect(route('regnum_srcs.edit', $rec->id));
//        else
        return redirect(route('regnum_srcs.index'));
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
        $res = regnum_src::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('regnum_srcs.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('regnum_srcs.index', ['machineid' => $res->obj['machineid'] ?? 0, 'parid' => $res->obj['planid'] ?? 0]);
            connectify('success', ($res->obj['name'] ?? '-'), 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    public function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = regnum_src::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        $template_js = [
            'regnum_src' => $document,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

    static public function list_for(Request $request)
    {
        //2021-02-20 SNS.

        $result = "";
        try {

            $list = regnum_src::lstFor([
                'categoryid' => $request->categoryid,
                'ownorgid' => $request->ownorgid,
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
            ]);


            $result = array('regnum_srcs' => $list);

        } catch (\Exception $e) {
            Log::error('regnum_srcs::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }


}
