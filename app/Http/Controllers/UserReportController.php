<?php

namespace App\Http\Controllers;

use App\report;
use App\objlog;
use App\Traits\SearchDataTrait;
use App\User;
use App\usrsysright;
use http\Env\Response;
use Illuminate\Http\Request;
use DB;
use DateTime;
use App\Events\notifyEvent;
use Illuminate\Support\Facades\Auth;

class UserReportController extends Controller
{

    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;  //reports
        //$this->objcode = 'reports';
        $this->objcode = 'users';
    }


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
        $usrrights['registrate'] = false;
        $usrrights['unregistrate'] = false;
        $usrrights['approve'] = false;
        $usrrights['setfact'] = false;


        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
            $usrrights['approve'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.approve');
            $usrrights['setfact'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.setfact');
        }

        return $usrrights;
    }


    public function rep05(Request $request)
    {//Статистика входов в систему
        $report_id = 5;

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep05";
        $s_ownorgid = "";
        $s_buildobjid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_buildobjid = $request->get("s_buildobjid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_buildobjid' => $s_buildobjid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (1 == 1 and !empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //$d = new DateTime('last day of previous month');
                    $d = new DateTime('previous day');
                    $s_enddate = $d->format('Y-m-d');
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
            }
        }

        $search_params = [
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            "s_ownorgid" => $s_ownorgid,
            "s_buildobjid" => $s_buildobjid,
        ];

        //Если параметры поиска не заданы, то уйдем на index
        $sc = null;
        $needSearch = false;
        foreach ($search_params as $p) {
            if ($p <> "") {
                $needSearch = true;
                break;
            }
        }
        $needSearch = true; //так как нет обязательных параметров

        if ($needSearch) {
            $sc = " 1=1 ";
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and mr.ownorgid=" . $s_ownorgid;
            }
//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = objlog::from('objlogs as l')
                ->join('users as u', function ($j) {
                    $j->on('u.id', 'l.objid');
                })
                ->where('l.sysobjid', 3)//3 - users
                ->where('info', 'like', '%вход в систему (%');

            if (isset($s_begdate) and isset($s_enddate))
                $recs = $recs->wherebetween('l.write_at', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59']);
            elseif (isset($s_begdate))
                $recs = $recs->where('l.write_at', '>=', $s_begdate . ' 00:00:00');
            elseif (isset($s_enddate))
                $recs = $recs->where('l.write_at', '<=', $s_enddate . ' 23:59:59');


            $recs = $recs->select('u.id as userid',
                DB::raw("max(u.name) as username, min(date(write_at)) as mindate,max(date(write_at)) as maxdate
                , count(*) cnt"
                    , db::raw("(select count(*) from")
                )
            )
                ->groupBy('u.id')
                ->orderby('username')
                ->get();
            //dd($recs);

            //обновим счетчик использования отчета
            report::where('id', $report_id)->update([
                'use_cnt' => DB::raw('use_cnt + 1'),
                'lastuse_dt' => now(),
                'lastuse_userid' => \Auth::user()->id,
                'lastuse_username' => \Auth::user()->name,
            ]);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        return view('users.rep05', compact('recs', 'search_params'));
    }


    public function rep10(Request $request)
    {//Статистика операций пользователей
        $report_id = 10;

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep10";
        $s_ownorgid = "";
        $s_buildobjid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_buildobjid = $request->get("s_buildobjid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_buildobjid' => $s_buildobjid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (1 == 1 and !empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //$d = new DateTime('last day of previous month');
                    $d = new DateTime('previous day');
                    $s_enddate = $d->format('Y-m-d');
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
            }
        }

        $search_params = [
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            "s_ownorgid" => $s_ownorgid,
            "s_buildobjid" => $s_buildobjid,
        ];

        //Если параметры поиска не заданы, то уйдем на index
        $sc = null;
        $needSearch = false;
        foreach ($search_params as $p) {
            if ($p <> "") {
                $needSearch = true;
                break;
            }
        }
        $needSearch = true; //так как нет обязательных параметров

        if ($needSearch) {
            $sc = " 1=1 ";
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and mr.ownorgid=" . $s_ownorgid;
            }
//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = objlog::from('objlogs as l')
                ->join('users as u', function ($j) {
                    $j->on('u.id', 'l.write_by')
                        ->where('u.active', 1);
                });
//                ->where('l.sysobjid', 3); //3 - users
//                ->where('info', 'like', '%вход в систему (%');

            if (isset($s_begdate) and isset($s_enddate))
                $recs = $recs->wherebetween('l.write_at', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59']);
            elseif (isset($s_begdate))
                $recs = $recs->where('l.write_at', '>=', $s_begdate . ' 00:00:00');
            elseif (isset($s_enddate))
                $recs = $recs->where('l.write_at', '<=', $s_enddate . ' 23:59:59');


            $recs = $recs->select('u.id as userid',
                DB::raw("max(u.name) as username
                , min(date(write_at)) as mindate, max(date(write_at)) as maxdate
                , count(*) cnt
                , count(distinct date(write_at)) as daycnt
                , count(*) / count(distinct date(write_at)) as avgdayopers"
                )
            )
                ->groupBy('u.id')
                //->orderby('username')
                //->orderby('cnt', 'desc')
                ->orderby("avgdayopers", 'desc')
                ->get();
            //dd($recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, \Auth::user()->id, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        return view('users.rep10', compact('recs', 'search_params'));
    }

    public function rep11(Request $request)
    {//Статистика операций пользователей по часам суток
        $report_id = 11;

        // - параметры поиска -------------------------------------------------
        $search_setname = "reports.rep10";
        $s_ownorgid = "";
        $s_buildobjid = "";
        $s_begdate = "";
        $s_enddate = "";

        if ($request->isMethod('post')) {

            $s_begdate = $request->get("s_begdate");
            $s_enddate = $request->get("s_enddate");
            $s_ownorgid = $request->get("s_ownorgid");
            $s_buildobjid = $request->get("s_buildobjid");

            //сохраним параметры поиска в сессии
            session(['search_setname' => $search_setname]);
            session(['search_params' => [
                's_begdate' => $s_begdate,
                's_enddate' => $s_enddate,
                's_ownorgid' => $s_ownorgid,
                's_buildobjid' => $s_buildobjid,
            ]]);
        } else {
            if (session('search_setname') == $search_setname) {
                if (1 == 1 and !empty(session('search_params'))) {
                    //восстановим параметры поиска из сессии
                    $params = session('search_params');
                    $s_begdate = $params['s_begdate'];
                    $s_enddate = $params['s_enddate'];
                    $s_ownorgid = $params['s_ownorgid'];
                    $s_buildobjid = $params['s_buildobjid'] ?? null;
                } else {
                    // Значения по-умолчанию --------------------------
                    $d = new DateTime('first day of previous month');
                    $s_begdate = $d->format('Y-m-d');
                    //$d = new DateTime('last day of previous month');
                    $d = new DateTime('previous day');
                    $s_enddate = $d->format('Y-m-d');
                }

            } else {
                //зачистим чужие параметры поиска
                session(['search_params' => []]);
            }
        }

        $search_params = [
            "s_begdate" => $s_begdate,
            "s_enddate" => $s_enddate,
            "s_ownorgid" => $s_ownorgid,
            "s_buildobjid" => $s_buildobjid,
        ];

        //Если параметры поиска не заданы, то уйдем на index
        $sc = null;
        $needSearch = false;
        foreach ($search_params as $p) {
            if ($p <> "") {
                $needSearch = true;
                break;
            }
        }
        $needSearch = true; //так как нет обязательных параметров

        if ($needSearch) {
            $sc = " 1=1 ";
            if (strlen($s_ownorgid) > 0) {
                $sc .= " and mr.ownorgid=" . $s_ownorgid;
            }
//            $sc = (isset($sc)) ? " 1=1 " . $sc : null;
        }
        // --------------------------------------------------------------------

        if ($needSearch) {

            $recs = objlog::from('objlogs as l');

            if (isset($s_begdate) and isset($s_enddate))
                $recs = $recs->wherebetween('l.write_at', [$s_begdate . ' 00:00:00', $s_enddate . ' 23:59:59']);
            elseif (isset($s_begdate))
                $recs = $recs->where('l.write_at', '>=', $s_begdate . ' 00:00:00');
            elseif (isset($s_enddate))
                $recs = $recs->where('l.write_at', '<=', $s_enddate . ' 23:59:59');


            $recs = $recs->select(DB::raw("hour(write_at) as mark, count(*) cnt"))
                ->groupBy('mark')
                ->orderby('mark')
                ->get();
            //dd($recs);

            //обновим счетчик использования отчета
            report::where('id', $report_id)->update([
                'use_cnt' => DB::raw('use_cnt + 1'),
                'lastuse_dt' => now(),
                'lastuse_userid' => \Auth::user()->id,
                'lastuse_username' => \Auth::user()->name,
            ]);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        return view('users.rep11', compact('recs', 'search_params'));
    }


    public function rep52(Request $request)
    {//План поставок

        $report_id = 52;

        $userid = \Auth::user()->id;

        $retURL = $request->get('returl') ?? route('home');

        //Запросим запись об отчете с учетом прав доступа пользователя
        $report = report::getForUser($report_id, $userid);

        if (!isset($report)) {
            objlog::log_info(855, $report_id, 'Попытка доступа к отчету', 4);
            return redirect($retURL)
                ->with(['error' => 'Запрошенный отчет недоступен!']);
        }

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $param_names = [
            's_userid' => $userid
            , 's_begdate' => today()->format('Y-m-d')
            , 's_enddate' => ''
        ];

        //временно! todo: передавать в трэйт имя набора как параметр
        $search_params = $this->search_params($request, $param_names, 'rep' . $report_id);

        //сформируем условие запроса в БД -----
        $sc = "1=1";

        $bNeedSearch = false;
        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                if ($item == 's_userid') {
                    $bNeedSearch = true;
                    $sc .= " and l.write_by={$val}";

                } elseif ($item == 's_begdate') {
                    $bNeedSearch = true;
                    $sc .= " and l.write_at>='{$val}'";

                } elseif
                ($item == 's_enddate') {
                    $sc .= " and l.write_at<='{$val}'";

                }
            }
        }
        //-------------------------------------------------------------------------------------------------------------

        if ($bNeedSearch) {

            $recs = objlog::from('objlogs as l')
                ->join('sysobjs as so', 'so.id', 'l.sysobjid')
                ->join('users as u', 'u.id', 'l.objid')
                //->where('l.sysobjid', 3)//Пользователи
                ->whereRaw($sc)
                ->select('l.*', 'u.name as user_name','so.name as sysobj_name')
                ->orderBy('write_at')
                ->get();
            //dd($sc, $recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->users = User::lstFor([
            'in_objlogs' => 1,
        ]);


        //Право на доступ к описанию отчета
        $usrrights = [];
        $usrrights['reports.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'reports.read');
        $data->usrrights = $usrrights;

        return view('reports.rep' . $report_id, compact('recs', 'search_params', 'data'));
    }


}
