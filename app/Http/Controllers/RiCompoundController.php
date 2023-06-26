<?php

namespace App\Http\Controllers;

use App\objlog;
use App\org;
use App\org_charge;
use App\orgstaff;
use App\refitem;
use App\ri_cmpnd_item;
use App\ri_compound;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;

use App\wrh;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class RiCompoundController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    protected $sysobjid;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 147;
        $this->sysobjcode = 'ri_compounds';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }

    protected function setInterfaceRight($docid)
    {

        $usrrights = array();
        $usrrights['save'] = false;
        $usrrights['safe_save'] = false;
        $usrrights['save_active'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['docsign'] = false;
        $usrrights['docunsign'] = false;
        $usrrights['doclst.create'] = false;
        $usrrights['doclst.update'] = false;

        $userid = \Auth::user()->id;

        if ($docid == -1) {
            //Новый документ - можно сохранять
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
            $usrrights['safe_save'] = $usrrights['save'];

        } else {

            $baseUpdateRight = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $baseDeleteRight = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');

            $usrrights['save_active'] = $baseUpdateRight;

            $rec = ri_compound::find($docid);

            $docsigned = ($rec->docsigned == 1);
            if (!$docsigned) {
                //Документ не утвержден

                if ($rec->items->count() == 0) {
                    //Еще не имеет состава - можно всё

                    $usrrights['save'] = $baseUpdateRight;
                    $usrrights['safe_save'] = $usrrights['save'];
                    $usrrights['delete'] = $baseDeleteRight;
                    $usrrights['docsign'] = false;

                } else {
                    // Состав уже есть. Но некоторые поля редактировать можно
                    $usrrights['safe_save'] = $baseUpdateRight;
                    $usrrights['docsign'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.approve');
                }

                $usrrights['doclst.create'] = ($baseUpdateRight and ri_compound::mayCreateLst($docid));
                $usrrights['doclst.update'] = ($baseUpdateRight);


                $usrrights['admindelete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.admindelete');

            } else {
                //Документ утвержден

                //проверим открытость периода
                $doc_locked = ri_compound::isLocked($docid);
                if ($doc_locked) {
                    $usrrights['docunsign'] = false;

                } else {
                    // период Открыт - все определяется правами
                    $usrrights['docunsign'] = true;
                    if (!ri_compound::mayUnsignDoc($docid)) $usrrights['docunsign'] = false;
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
            'create' => usrsysright::isUserHasRightByCode($userid, 'ri_compounds.create'),
        );

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorg' => ''
            , 's_ownorg' => ''
            , 's_statuscode' => ''
            , 's_ri_name' => ''
        ];

        $search_params = $this->search_params($request, $param_names);

        //сформируем условие запроса в БД -----
        $sc = "1=1";
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_ownorg') {
                    $sc .= " and ric.ownorgid ={$val}";

                } elseif ($item == 's_active') {
                    $sc .= " and ifnull(ric.active,0) = '{$val}'";

                } elseif ($item == 's_statuscode') {
                    $sc .= " and ric.docsigned={$val}";

                } elseif ($item == 's_ri_name') {
                    $sc .= " and ri.name like '%{$val}%'";
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
            $sort_by = 'ric.id';
            $sort_dir = 'desc';
        }

        $recs = ri_compound::from('ri_compounds as ric')
            ->join('refitems as ri', 'ri.id', '=', 'ric.refitmid')
            ->join('orgs as oo', 'oo.id', '=', 'ric.ownorgid')
            //->leftjoin('orgs as o', 'o.id', '=', 'wd.orgid')
            //->selectraw('wd.*, if(wd.docsigned=1,"утвержден","не утвержден") statusname, oo.name as ownorg_name');
            ->select('ric.*', db::raw("if(ric.docsigned=1,'утвержден','не утвержден') statusname")
                , 'oo.name as ownorg_name'
                , 'ri.name as ri_name'
            );
        if (isset($sc))
            $recs = $recs->whereRaw($sc);


        $recs = $recs->orderBy('oo.name', 'asc')->orderBy('ric.ownorgid', 'asc')
            ->orderBy($sort_by, $sort_dir)
            ->paginate($search_params['s_pageitmcnt'] ?? 20);

        $data = new \stdClass();

        //варианты кол-ва записей на страницу
        $data->pageitmcnts = $this->pageitmcnts;

        $data->sysobj = sysobj::find($this->sysobjid);

        //номер первой записи на странице:
        $data->rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;

        $data->search_params = $search_params;

        $data->s_ownorgs = org::listUsed(['in_ri_compounds_ownorgid' => 1]);
        //dd($data->s_ownorgs);

        $data->s_statuscodes = array('' => '-любой-', '0' => 'не утвержден', '1' => 'утвержден');

        // Пункты меню (сверху-справа) ---------------------------------
        $t_coll = collect();
        if (usrsysright::isUserHasRightByCode($userid, 'refitems.read')) {
            $t_coll->push((object)[
                'name' => 'Номенклатура',
                'url' => route('refitems.index'),
                'title' => 'Товары и услуги'
            ]);
        }
        if (usrsysright::isUserHasRightByCode($userid, 'orgs.read')) {
            $t_coll->push((object)[
                'name' => 'Контрагенты',
                'url' => route('orgs.index'),
                'title' => 'Справочник Контрагенты'
            ]);
        }
        $data->top_right_menu = $t_coll;
        //--------------------------------------------------------------


        return view($this->sysobjcode . '.index', compact('recs', 'usrrights', 'data'));
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

            $rec = new ri_compound([
                'id' => -1,
                'docsigned' => 0,
                'ownorgid' => \Auth::user()->curorgid,
                'refitmid' => $request->get('refitmid'),
                'begdate' => date("Y-m-d", strtotime(now())),
                'enddate' => null,
                'created_by' => $userid,
            ]);

        } else {
            $rec = ri_compound::find($id);
        }

        if (!isset($rec))
            return redirect($this->sysobjcode . '.index')->with(['error' => 'Документ не найден!']);

        /*$rec->ownorgs = org::lstFor([
            'flagtypeid' => 12,
            'in_userorgs' => $userid,
        ]);*/

        //Ответственный персонал
        if (!$rec->docsigned == 1) {
            if (1 == 1) {
                //Список пользователей, имеющих право утверждения состава комлектующих для производства
                $rec->respstafflst = usrsysright::from('usrsysrights as ur')
                    ->join('sysfuncs as sf', 'sf.id', '=', 'ur.sysfuncid')
                    ->where('sf.code', 'refitems.cmpnd_approve')
                    ->join('users as u', 'u.id', '=', 'ur.userid')
                    ->where('ur.active', '=', 1)
                    ->whereRaw('now() between ur.begdt and ifnull(ur.enddt,now())')
                    ->selectraw('u.id, concat(lname," ",fname," ",ifnull(mname," ")) as name')
                    ->get()
                    ->pluck("name", "id")->prepend("", "");
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

        $items = ri_cmpnd_item::from('ri_cmpnd_items as dl')
            ->join('refitems as ri', 'ri.id', 'dl.refitmid')
            ->join('unittypes as ut', 'ut.id', 'ri.unittypeid')
            ->where('dl.cmpndid', $id)
            ->select('dl.*', 'ri.name as ri_name', 'ut.name as ut_name', 'ut.decimal_dgts')
            ->get();

        $usrrights = $this->setInterfaceRight($id);

        if ($items->count() > 0) {
            $usrrights['save'] = false;
            $usrrights['delete'] = false;
        } else {
            $usrrights['docsign'] = false;
            $usrrights['docunsign'] = false;
        }

        if ($usrrights['safe_save']) {
            //установим минимально-допустимую дату для wrkdate
//            $rec->docdate_min = ri_compound::min_docdate();
        }

        //Cache::forget('wrhdoctypes');
        /*$rec->doctypes = Cache::remember('wrhdoctypes', now()->addMinutes(15)
            , function () use ($rec) {
                return wrhdoctype::lstFor(['active_or_current' => $rec->doctypeid]);
            });
    */

        $auxinfo = ri_compound::AuxInfo($id);

        //dd($rec);
//        dd($usrrights);

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
            "refitmid" => "required",
            "ownorgid" => "required",
            "begdate" => "required",
            //"wrhid" => "required|different:relwrhid",
            //"relwrhid" => "different:wrhid",
            //"boxid" => "required|different:relboxid",
            //"relboxid" => "different:boxid",
        ];

        $messages = [
            "refitmid.required" => "Укажите производимое изделие",
            "ownorgid.required" => "Укажите организацию-изготовителя",
            "begdate.required" => "Укажите начало применения данного состава для производства изделия",
            //"wrhid.different" => "Склады должны отличаться",
        ];

        Validator::make($request->all(), $rules, $messages)->validate();

        $userid = \Auth::user()->id;
        $msg = "";
        $refitmid = $request->get('refitmid');
        $ownorgid = $request->get('ownorgid');

        if ($id == -1) {

            $rec = new ri_compound([
                "refitmid" => $refitmid,
                "ownorgid" => $ownorgid,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $msg = "Создана запись о новом документе ";
        } else {
            $rec = ri_compound::find($id);
            $msg = "Обновлена запись о документе";
        }


        //Некоторые поля можно изменять, только если док-т еще не имеет состава
        if ($rec->items()->count() == 0) {

            // поля нельзя менять при сформированном составе
            $rec->refitmid = $refitmid;
            $rec->ownorgid = $ownorgid;
        }

        $rec->begdate = $request->get('begdate');
        $rec->enddate = $request->get('enddate');
        $rec->notes = $request->get('notes');

        $rec->active = $request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();


        if ($id == -1) {
            objlog::log_info($this->sysobjid, $rec->id, $msg, 5);
            return redirect(route('ri_compounds.edit', $rec->id))->with('success', $msg);
        } else {
            objlog::log_info($this->sysobjid, $rec->id, $msg, 5);
            $route = route('refitems.edit', $rec->refitmid);
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
        $res = ri_compound::delete_by_id($id);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route('ri_compounds.edit', $id);
            $sd["error"] = $res->msg;
            objlog::log_info($this->sysobjid, $id, $res->msg, 2);
        } else {
            $route = route('refitems.edit', $res->rec->refitmid);
            $sd['success'] = 'Запись о документе удалена';
        }
        return redirect($route)->with($sd);
    }

    public
    function admindelete($id)
    {
        $rec = ri_compound::find($id);
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

        $route = route('ri_compounds.edit', $id);
        $sd = array();

        $rec = ri_compound::find($id);
        if (!isset($rec)) {

            $route = route('ri_compounds.index');
            $sd["error"] = 'Документ не найден!';

        } else {

            if (usrsysright::isUserHasRightByCode($userid, 'ri_compounds.approve')) {

                $rec->docsigned = 1;
                $rec->signed_by = $userid;
                $rec->signed_at = now();
                $rec->save();

                //Выполним действия после утверждения записи ---------------------------------------------
                ri_compound::on_sign($rec);
                //----------------------------------------------------------------------------------------

                $sd['success'] = 'Документ утвержден';
                objlog::log_info($this->sysobjid, $rec->id, 'Документ утвержден', 3);


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

        $rec = ri_compound::find($id);
        if (isset($rec)) {

            $route = route('ri_compounds.edit', $id);

            if (usrsysright::isUserHasRightByCode($userid, 'ri_compounds.approve')) {

                //Проверим, безопасно ли переводить документ в черновик
                if (ri_compound::mayUnsignDoc($id)) {

                    $rec->docsigned = 0;
                    $rec->signed_by = null;
                    $rec->signed_at = null;
                    $rec->save();

                    $sd['success'] = 'С документа снят статус "Утвержден"';
                    objlog::log_info($this->sysobjid, $id, $sd['success'], 2);

                } else {

                    $sd["error"] = 'Данный документ уже нельзя вернуть в черновик, так как это нарушит целостность данных!';
                    objlog::log_info($this->sysobjid, $id
                        , 'Попытка отмены проведения документа: ' . $sd["error"], 2);
                }

            } else {
                $sd["error"] = 'У вас нет прав на отмену проведения документа!';
                objlog::log_info($this->sysobjid, $id, 'Попытка отмены проведения документа', 2);
            }
        } else {
            $route = route('ri_compounds.index');
            $sd["error"] = 'Документ не найден!';
        }

        return redirect($route)->with($sd);
    }

    public
    function set_active(Request $request, $id)
    {
        $userid = \Auth::user()->id;

        $route = route('ri_compounds.edit', $id);
        $sd = array();

        $rec = ri_compound::find($id);
        if (!isset($rec)) {

            $route = route('ri_compounds.index');
            $sd["error"] = 'Документ не найден!';

        } else {

            if (usrsysright::isUserHasRightByCode($userid, 'ri_compounds.update')) {

                $rec->active = $request->get('active', 0);
                $rec->updated_by = $userid;
                $rec->updated_at = now();
                $rec->save();

                //Выполним действия после утверждения записи ---------------------------------------------
                ri_compound::on_update($rec);
                //----------------------------------------------------------------------------------------

                $msg = 'Документ ' . (($rec->active == '1') ? 'активирован' : 'деактивирован');
                $sd['success'] = $msg;
                objlog::log_info($this->sysobjid, $rec->id, $msg, 3);


            } else {
                $sd["error"] = 'У вас нет прав на активацию/деактивацию документа!';
                objlog::log_info($this->sysobjid, $id, 'Попытка активации/деактивации документа', 2);
            }

        }
        //dd($route, $sd);
        return redirect($route)->with($sd);
    }

    public
    function trg_active(Request $request, $id)
    {
        $userid = \Auth::user()->id;

        $route = route('ri_compounds.edit', $id);
        $sd = array();

        $rec = ri_compound::find($id);
        if (!isset($rec)) {

            $route = route('ri_compounds.index');
            $sd["error"] = 'Документ не найден!';

        } else {

            if (usrsysright::isUserHasRightByCode($userid, 'ri_compounds.update')) {

                $rec->active = ($rec->active == 1) ? 0 : 1;
                $rec->updated_by = $userid;
                $rec->updated_at = now();
                $rec->save();

                //Выполним действия после утверждения записи ---------------------------------------------
                ri_compound::on_update($rec);
                //----------------------------------------------------------------------------------------

                $msg = 'Документ ' . (($rec->active == 1) ? 'активирован' : 'деактивирован');
                $sd['success'] = $msg;
                objlog::log_info($this->sysobjid, $rec->id, $msg, 3);

            } else {
                $sd["error"] = 'У вас нет прав на активацию/деактивацию документа!';
                objlog::log_info($this->sysobjid, $id, 'Попытка активации/деактивации документа', 2);
            }
        }
        //dd($route, $sd);
        return redirect($route)->with($sd);
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

        $rslt = ri_compound::clone($id);
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

        $rec = ri_compound::find($id);

        if (!isset($rec))
            return redirect()->back()->with('error', 'Не найдена указанная запись!');

        $rec->items = ri_cmpnd_item::from('ri_cmpnd_items as dl')
            ->join('refitems as ri', 'ri.id', 'dl.refitmid')
            ->join('unittypes as ut', 'ut.id', 'ri.unittypeid')
            ->where('dl.cmpndid', $id)
            ->select('dl.*', 'ri.name as ri_name', 'ut.name as ut_name', 'ut.decimal_dgts', 'ri.grossweight as ri_grossweight')
            ->get();

        $data = new \stdClass();

        $view = "ri_compounds.print";
        return view($view,
            compact('rec', 'data'));
    }


    static public function list_for_ac(Request $request)
    {
        //2023-05-21 SNS. Для автокомплита

        $result = "";
        try {

            $list = ri_compound::getFor([
                'name' => $request->name,
                'ownorgid' => $request->ownorgid,
                'refitmid' => $request->refitmid,
                'signed' => $request->signed,
                'active' => $request->active,
            ],
                ['ric.id', 'ri.name', 'ric.notes', 'ric.refitmid', 'ri.name as refitmname']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('ri_compound::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
