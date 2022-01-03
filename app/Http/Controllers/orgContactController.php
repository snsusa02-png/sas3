<?php

namespace App\Http\Controllers;

use App;
use App\objflag;
use App\objlog;
use App\org;
use App\org_curator;
use App\orgdog;
use App\org_saldo;
use App\orgitmdiscount;
use App\userorg;
use App\orgstaff;
use App\orgtaxsys;
use App\objextid;
use App\User;
use App\usrsysright;
use App\grptype;
use App\group;
use App\grpitem;
use App\taxsystem;
use Auth;
use Cache;
use DB;
use Illuminate\Http\Request;
use App\Http\Middleware\IStock;


class orgContactController extends Controller
{

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 111;
        $this->objcode = 'orgs';
    }

//    public function __construct(IStock $stock)
//    {
//        $this->middleware('auth');
//        $this->sysobjid = 111;
//        $this->objcode = 'orgs';
//
//        $this->stock = $stock;
//    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['orgstaff.edit'] = false;

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
        $usrrights['orgstaff.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'orgstaff.read');
        $usrrights['orgstaff.edit'] = usrsysright::isUserHasRightByCode_cached($userid, 'orgstaff.edit');

        $usrrights['viewall'] = false;

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

        //Проверка прав пользователя.
        $usrrights = Array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.create'),
            'save' => usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.save'),
            'orgstaff.read' => usrsysright::isUserHasRightByCode_cached($userid, 'orgstaff.read'),
            'orgstaff.edit' => 1 == 0 and usrsysright::isUserHasRightByCode_cached($userid, 'orgstaff.edit'),
            'orgstaff.all_orgs' => usrsysright::isUserHasRightByCode_cached($userid, 'orgstaff.all_orgs'),
            'orgs.read' => usrsysright::isUserHasRightByCode_cached($userid, 'orgs.read'),
        );
        if (!$usrrights['orgstaff.read']) {
            return view('home');
        }


        session([$this->objcode . '_pageno' => $request->page]);

        // - параметры поиска -------------------------------------------------
        $s_stfname = "";
        $s_postname = "";
        $s_phone = "";
        $s_statuscode = "";
        $s_email = "";
        $s_orgname = "";
        $s_flagtypeid = "";
        $s_rating = "";

        if ($request->isMethod('post')) {
            $s_stfname = $request->get("s_stfname");
            $s_postname = $request->get("s_postname");
            $s_phone = $request->get("s_phone");
            $s_statuscode = $request->get("s_statuscode");
            $s_email = $request->get("s_email");
            $s_orgname = $request->get("s_orgname");
            $s_flagtypeid = $request->get("s_flagtypeid");
            $s_rating = $request->get("s_rating");


            //сохраним параметры поиска в сессии
            session(['search_setname' => $this->objcode]);
            session(['search_params' => [
                's_orgname' => $s_orgname,
                's_stfname' => $s_stfname,
                's_postname' => $s_postname,
                's_phone' => $s_phone,
                's_statuscode' => $s_statuscode,
                's_email' => $s_email,
                's_flagtypeid' => $s_flagtypeid,
                's_rating' => $s_rating,
            ]]);
        } else {
            if (session('search_setname') == $this->objcode) {
                if (!empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_stfname = $params['s_stfname'] ?? null;
                    $s_postname = $params['s_postname'] ?? null;
                    $s_phone = $params['s_phone'] ?? null;
                    $s_statuscode = $params['s_statuscode'] ?? null;
                    $s_email = $params['s_email'] ?? null;
                    $s_orgname = $params['s_orgname'] ?? null;
                    $s_flagtypeid = $params['s_flagtypeid'] ?? null;
                    $s_rating = $params['s_rating'] ?? null;
                }
            } else
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
        }

        $search_params = [
            "s_stfname" => $s_stfname,
            "s_postname" => $s_postname,
            "s_phone" => $s_phone,
            "s_statuscode" => $s_statuscode,
            "s_email" => $s_email,
            "s_orgname" => $s_orgname,
            "s_flagtypeid" => $s_flagtypeid,
            "s_rating" => $s_rating,
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

        //Браунагель: должны видеть всё
//        if (!($usrrights['orgstaff.all_orgs'] ?? false)) {
        //просмотр контактных данных сотрудников, входят только
//            $sc .= ' and o.id in (select objid from objflags as of where of.sysobjid=111 and of.flagtypeid=12)';
//        }

        if ($needSearch) {
            if (strlen($s_orgname) > 0) {
                $sc = $sc . " and o.name like '%" . mb_strtoupper($s_orgname) . "%'";
            }
            if (strlen($s_stfname) > 0) {
                $sc = $sc . " and concat(os.lname,' ',os.fname,' ',os.mname) like '%" . mb_strtoupper($s_stfname) . "%'";
            }
            if (strlen($s_postname) > 0) {
                $sc = $sc . " and os.postname like '%" . mb_strtoupper($s_postname) . "%'";
            }
            if (strlen($s_phone) > 0) {
                $sc = $sc . " and replace(replace(os.phone,' ',''),'-','') like '%"
                    . str_replace('-', '', str_replace(' ', '', $s_phone)) . "%'";
            }
            if (strlen($s_email) > 0)
                $sc .= " and os.email like '%" . $s_email . "%'";

            if (strlen($s_flagtypeid) > 0)
                $sc .= " and exists(select 1 from objflags f where f.sysobjid=111 and f.objid=o.id and f.flagtypeid=" . $s_flagtypeid . ')';

            if (strlen($s_rating) > 0)
                $sc .= " and o.rating=" . $s_rating;
        }
        // --------------------------------------------------------------------


        $recs = orgstaff::from('orgstaff as os')
            ->join('orgs as o', 'o.id', 'os.orgid')
            ->leftJoin('orgdeps as od', function ($j) {
                $j->on('od.id', 'os.depid');
            })
            ->where('os.active', 1)
            //->where('os.orgid', 1)
            ->whereraw($sc)
            ->select('os.id', 'os.orgid', 'os.userid'
                , 'o.name as orgname', 'o.inn', 'o.kpp', 'o.address', 'o.rating'
                , 'os.depid', DB::raw('ifnull(od.name,"- - -") as depname')
                , DB::raw('concat(os.lname,\' \',os.fname,\' \',os.mname) as stfname')
                , 'os.postname'
                // , 'os.phone', 'os.email'
                , db::raw("(select group_concat(oc.contact SEPARATOR ', ') from obj_contacts as oc where oc.sysobjid=111 and oc.objid=os.orgid and oc.contacttypeid=1) as org_phones")
                , db::raw("(select group_concat(oc.contact SEPARATOR ', ') from obj_contacts as oc where oc.sysobjid=111 and oc.objid=os.orgid and oc.contacttypeid=2) as org_emails")
                , db::raw("(select group_concat(oc.contact SEPARATOR ', ') from obj_contacts as oc where oc.sysobjid=111 and oc.objid=os.orgid and oc.contacttypeid=3) as org_sites")
                , db::raw("(select group_concat(oc.contact SEPARATOR ', ') from obj_contacts as oc where oc.sysobjid=121 and oc.objid=os.id and oc.contacttypeid=1) as phone")
                , db::raw("(select group_concat(oc.contact SEPARATOR ', ') from obj_contacts as oc where oc.sysobjid=121 and oc.objid=os.id and oc.contacttypeid=2) as email")
                , DB::raw("(select group_concat(ft.name SEPARATOR '; ') from objflags as f join flagtypes as ft on ft.id=f.flagtypeid where f.sysobjid=111 and f.objid=os.orgid) as lstflags")
            )->with('photo');


        //Сортировка пользователя ----------------------------------------
        $sort_params = session('sort_params_orgs.index');

        $recs = $recs->orderBy('o.name', 'asc');
        $recs = $recs->orderBy(DB::raw('ifnull(od.code,\'999999\')'), 'asc');
        $recs = $recs->orderBy('od.name', 'asc');
        $recs = $recs->orderBy('os.lname', 'asc');
//        if (isset($sort_params)) {
//            foreach ($sort_params as $prm)
//                $recs = $recs->orderBy($prm['field'], $prm['dir']);
//        } else {
//            $recs = $recs->orderBy('o.name', 'asc');
//        }
        //----------------------------------------------------------------

        //$recs = $recs->get();
        //dd($recs);
        $recs = $recs->paginate(120);
        //dd($recs);

        //номер первой записи на странице:
        $rec0 = $recs->currentPage() * $recs->perPage() - $recs->perPage() + 1;


        $usedflags = objflag::lstUsedFlagsForSysObj_cache(111);

        $ratings = [
            -1 => 'негативный',
            0 => 'нейтральный',
            +1 => 'позитивный',
        ];


        return view('orgcontacts.index', compact('recs', 'rec0'
            , 'search_params', 'sort_params', 'usrrights'
            , 'usedflags', 'ratings'));
    }

}
