<?php

namespace App\Http\Controllers;

use App\doctype;
use App\itmtype;
use App\org;
use App\org_place;
use App\orgstaff;
use App\ri_sup_price;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class RiSupPriceController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 146;
        $this->sysobjcode = 'ri_sup_prices';
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
        $usrrights['admindelete'] = ($recid <> -1 and usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.admindelete'));
        //$usrrights['private_acs'] = (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.private_acs'));

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

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.save'),
        );
        if (!$usrrights['read']) {
            return view('home');
        }


        session([$this->sysobjcode . '_pageno' => $request->page ?? 1]);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 20
            , 's_orgid' => ''
            , 's_org_name' => ''
            , 's_place_name' => ''
            , 's_itmtypeid' => ''
            , 's_itmtype_name' => ''
            , 's_refitm_name' => ''
            , 's_place_name' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = ri_sup_price::search_cond($search_params);
        //-------------------------------------------------------------------------------------------------------------

        $recs = ri_sup_price::from('ri_sup_prices as rop')
            ->join('refitems as ri', 'ri.id', 'rop.refitmid')
            ->join('itmtypes as it', 'it.id', 'ri.itmtypeid')
            ->join('orgs as o', 'o.id', 'rop.orgid')
            ->leftJoin('org_places as op', function ($j) {
                $j->on('op.id', 'rop.placeid');
            })
            ->whereraw($sc)
            ->select('rop.id', 'rop.price', 'rop.begdate', 'rop.enddate'
                , 'o.name as org_name', 'rop.orgid'
                , 'op.name as place_name', 'rop.placeid'
                , 'it.name as itmtype_name', 'ri.itmtypeid'
                , 'ri.name as refitm_name', 'ri.code'
                , 'ri.active'
                , 'ri.unit as unittypename'
                , db::raw("concat(date_format(rop.begdate,'%d.%m.%Y'),' ... ', ifnull(date_format(rop.enddate,'%d.%m.%Y'),'')) as active_period")
                , db::raw("case when rop.active and ifnull(rop.enddate, curdate())>=curdate() then 1 else 0 end as active")
            );

        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_' . $this->sysobjcode . '.index');

        $recs = $recs->orderBy('o.name', 'asc');
        $recs = $recs->orderBy('rop.orgid', 'asc');
        $recs = $recs->orderBy('op.name', 'asc');
        $recs = $recs->orderBy('op.id', 'asc');
        $recs = $recs->orderBy('it.name', 'asc');
        $recs = $recs->orderBy('it.id', 'asc');
        if (isset($sort_params)) {
            foreach ($sort_params as $prm)
                $recs = $recs->orderBy($prm['field'], $prm['dir']);
        } else {
            $recs = $recs->orderBy('ri.name', 'asc');
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

        $data->used_orgs = org::lstFor([
            'in_ri_sup_prices' => 1,
        ]);
        $data->itmtypes = itmtype::lstFor([
            'in_ri_sup_prices' => 1,
        ]);

        $data->statuses = [1 => 'актив', 0 => 'архив'];

        $data->file_doctypes = doctype::lstUsedForSysObj($this->sysobjid);


        return view('ri_sup_prices.index', compact(['recs', 'data', 'usrrights']));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create(Request $request, $orgid)
    {
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

        $userid = \Auth::user()->id;

        if ($id == -1) {
            $orgid = ($orgid == 0) ? null : $orgid;


            $rec = new ri_sup_price([
                'id' => -1,
                'orgid' => $orgid,
                'begdate' => today()->format('Y-m-d'),
                'placeid' => $request->get('placeid'),
                'active' => 1,
                'created_by' => $userid,
            ]);
        } else
            $rec = ri_sup_price::find($id);

        if (!isset($rec))
            return redirect(route('orgs.index'))->with(['error' => 'Запись не найдена!']);

        $rec->retURL = $request->get('returl');

        //$data = new \stdClass();

        $rec->places = org_place::lstFor([
            'orgid' => $rec->orgid,
        ]);

        $rec->userrights = [];
        $usrrights = $this->setInterfaceRight($id);

        return view('ri_sup_prices.edit', compact(['rec', 'usrrights']));
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
        $messages = [
            'refitmid.required' => 'Укажите товар',
            'orgid.required' => 'Укажите организацию-поставщика',
            'price.required' => 'Укажите цену',
            'begdate.required' => 'Укажите дату начала действия цены',
        ];

        $rules = [
            "refitmid" => "required",
            "orgid" => "required",
            "price" => "required",
            "begdate" => "required",
            'enddate' => 'nullable||date|after_or_equal:begdate',
        ];


        $request->validate($rules, $messages);
        //$request->validate($rules, $messages)->validateWithBag('post');

        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight($id);


        $mess = "";
        if ($id == -1) {
            $rec = new ri_sup_price();
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о цене на товар";
        } else {
            $rec = ri_sup_price::find($id);
            $mess = "Изменена запись о цене на товар";
        }
        $rec->orgid = $request->get('orgid');
        $rec->placeid = $request->get('placeid');
        $rec->refitmid = $request->get('refitmid');
        $rec->price = $request->get('price');
        $rec->begdate = $request->get('begdate');
        $rec->enddate = $request->get('enddate');
        $rec->notes = mb_substr($request->get('notes'), 0, 90);

        $rec->active = $request->get('active') ?? 0;
        $rec->updated_by = $userid;
        $rec->save();

        // Ограничим пересекающиеся периоды ---------------------
        //  Может быть 3 вида конфликтов:
        //    1 - предыдущая запись укладывается полностью в период текущей - тогда деактивируем предыдущую запись
        //    2 - предыдущая запись начинается ДО, но заканчивается ПОСЛЕ начала текущей - тогда ставим окончание предыдущей = предыдущему дню от начала текущей
        //    3 - предыдущая запись начинается ДО окончания текущей - тогда ставим начало предыдущей = следующему дню от окончания текущей

        $begdate = $rec->begdate;
        $enddate = $rec->enddate ?? today()->format('Y-m-d');

        //1-й вариант
        ri_sup_price::where(['refitmid' => $rec->refitmid, 'orgid' => $rec->orgid, 'active' => 1])
            ->where('id', '<>', $rec->id)
            ->whereRaw("begdate >= '{$begdate}' and enddate <= '{$enddate}'")
            ->update(['active' => 0]);

        $set_begdate = date_create($rec->enddate)->modify('+1 day')->format('Y-m-d');
        $set_enddate = date_create($begdate)->modify('-1 day')->format('Y-m-d');
        //dd($begdate, $enddate, $set_begdate, $set_enddate);

        //2-й вариант
        ri_sup_price::where(['refitmid' => $rec->refitmid, 'orgid' => $rec->orgid, 'active' => 1])
            ->where('id', '<>', $rec->id)
            ->whereRaw("begdate < '{$begdate}' and ifnull(enddate,'{$begdate}') >= '{$begdate}'")
            ->update(['enddate' => $set_enddate]);

        //3-й вариант
        ri_sup_price::where(['refitmid' => $rec->refitmid, 'orgid' => $rec->orgid, 'active' => 1])
            ->where('id', '<>', $rec->id)
            ->whereRaw("begdate > '{$begdate}' and ifnull(enddate,'{$begdate}') >= '{$begdate}'")
            ->update(['begdate' => $set_begdate]);

        //-------------------------------------------------------

        //Удалим устаревший кэш ---------------------------------
        Cache::forget('org_ri_prices_.' . $rec->orgid);
        //-------------------------------------------------------

        $retURL = $request->get('returl') ?? route('org_ri_prices.index', $rec->orgid)
            . '?page=' . session($this->sysobjcode . '_pageno') . '#' . $rec->id;

        //return redirect(route('org_staff.index', $rec->orgid))->with('success', $mess);
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

        $userid = \Auth::user()->id;

        $retURL = $request->get('returl') ?? route('orgs.edit', $id);

        if (usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete')) {

            $res = ri_sup_price::delete_by_id($id);
            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $sd["error"] = $res->msg;
            } else {

                $retURL = $request->get('returl') ?? route('org_ri_prices.index', $res->obj['orgid']);
                $sd['success'] = 'Запись о цене на товар удалена';
            }
        } else {
            $sd['success'] = 'У вас нет прав на удаление записей!';
        }
        return redirect($retURL)->with($sd);
    }


    static public function get_for(Request $request)
    {
        //2021-11-08 SNS. Обертка для вызова ri_sup_price::getFor

        $result = "";
        try {

            $list = ri_sup_price::getFor([
                'active' => $request->active,
                'active_or_current' => $request->active_or_current,
                'orgid' => $request->orgid,
                'name' => $request->name ?? $request->q,
            ], [
                'rop.id', db::raw("concat(ri.name, ', ', ri.unit) as name")
            ]);


            //$result = array('doctypes' => $list);
            $result = $list;

        } catch (\Exception $e) {
            Log::error('ri_sup_price::get_for:' . $e->getMessage());
        }
        return response()->json($result);
    }


}
