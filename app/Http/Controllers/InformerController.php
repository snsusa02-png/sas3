<?php

namespace App\Http\Controllers;

use App\buildobj;
use App\buildopertype;
use App\contract;
use App\informer;
use App\machine;
use App\mchn_opertype;
use App\objlog;
use App\orgstaff;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InformerController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1801;
        $this->sysobjcode = 'informers';
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
            , 's_machineid' => ''
            , 's_staffid' => ''
            , 's_statusid' => ''
            , 's_date' => ''
            , 's_has_photo' => ''
            , 's_resporgid' => ''
            , 's_flagtypeid' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";

        //если пользователь может читать записи в buildobjs, то показываем все табели без ограничений
        if (1 == 0 and !usrsysright::isUserHasRightByCode_cached($userid, 'buildobjs.read')) {
            //доступ ограничен только объектами, где пользователь указан как ответственный сотрудник (входит в buildobj_staffs)
            $sc .= " and exists (select 1 from buildobj_staffs as bos
                join orgstaff as os on os.id=bos.staffid and os.userid={$userid}
                where bos.machineid=inf.machineid )";
        }


        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_name') {
                    $sc = $sc . " and inf.name like '%{$val}%'";

                } elseif ($item == 's_staffid') {
                    $sc = $sc . " and inf.staffid = {$val}";

                } elseif ($item == 's_active') {
                    $sc = $sc . " and inf.active = {$val}";

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


        $recs = informer::from('informers as inf')
            ->whereraw($sc)
            ->select('inf.id as id', 'inf.name'
                , 'inf.active', 'inf.descript'
            );


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
        $recs = $recs
            ->orderby('inf.id');
//        }
        //----------------------------------------------------------------

        $recs = $recs->paginate($search_params['s_pageitmcnt'] ?? 10);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->machines = machine::getFor(
            ['in_informers' => 1,], ['m.id', db::raw("concat(m.regnum,' - ',m.name) as name")]
        )->pluck('name', 'id')->toArray();

        $data->staffs = orgstaff::lstFor([
            'in_informers' => 1,
        ]);

        //$data->statuses = [0 => 'черновик', 2 => 'ожидает согласования', 4 => 'согласован'];
        //$data->dates = [1 => 'сегодня', 2 => 'вчера', 3 => 'за неделю', 4 => 'за месяц'];
        //$data->yes_no = [1 => 'есть', 0 => 'нет'];


        return view('informers.index', compact('recs', 'rec0'
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
        //$userid = \Auth::user()->id;

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

                $rec = new informer([
                    'id' => -1,
                    'active' => 1,
                    'created_by' => $userid,
                ]);
            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $sc = '1=1';

            $rec = informer::from('informers as inf')->whereRaw($sc)->where('id', $id)->first();


            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'));

        }


        $rec->status_name = 'черновик';
        $rec->status_style = 'background-color:silver';
        if ($rec->active == 1) {
            $rec->status_name = 'активно';
            $rec->status_style = 'background-color:#b7f192;';
        }

        $usrrights['edit'] = ($rec->created_by == $userid and $rec->statusid == 0);


        return view('informers.edit', compact('rec', "usrrights"));
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
        $statusid = ($id == -1) ? 0 : informer::find($id)->statusid ?? 0;

        $messages = [
            'name.required' => 'Укажите название информера',
        ];

        $rules = [
            'name' => 'required',
        ];

        $request->validate($rules, $messages);


        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new informer([
                "active" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = informer::find($id);
            $mess = "Запись обновлена";
        }


        $rec->name = mb_substr($request->get('name'), 0, 60);
        $rec->descript = mb_substr($request->get('descript'), 0, 360);

        $rec->public = $request->get('public', 0);
        $rec->active = $request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();
        //dd($rec);

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        if ($id == -1 or $rec->statusid <> $statusid)
            return redirect(route('informers.edit', $rec->id));
        else
            return redirect(route('informers.index'));
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
        $res = informer::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('informers.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

            $route = route('informers.index', ['machineid' => $res->obj['machineid'] ?? 0, 'parid' => $res->obj['planid'] ?? 0]);
            connectify('success', ($res->obj['name'] ?? '-'), 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


}
