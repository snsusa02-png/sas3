<?php

namespace App\Http\Controllers;

use App\orgstaff;
use App\report;
use App\org;
use App\objlog;
use App\Traits\SearchDataTrait;
use App\User;
use App\usrsysright;
use Illuminate\Http\Request;
use DB;
use DateTime;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Date;
use App\Events\notifyEvent;
use Illuminate\Support\Facades\Auth;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Str;
use function GuzzleHttp\Promise\task;

class OrgStaffReportController extends Controller
{
    use SearchDataTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;  //reports
        $this->objcode = 'orgstaff';    // для получения прав
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


    public function rep63(Request $request)
    {
        //

        $report_id = 63;

        $userid = \Auth::user()->id;

        if (!usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.read'))
            return redirect(route('home'))
                ->with(['error' => 'У вас нет полномочий для работы с платежами для этой организации!']);

        // - параметры поиска: массив из имени и значения по-умолчанию -----------------------------------------------
        $fdom = new DateTime('first day of this month');
        $fdomc = $fdom->format('Y-m-d');
        $year = $fdom->format('Y');
        $ldom = new DateTime('last day of this month');
        $ldomc = $ldom->format('Y-m-d');
        $curdate = new DateTime();
        $cd = $curdate->format('Y-m-d');

        $month = date("n");
        $yearQuarter = ceil($month / 3);

        $param_names = [
            's_pageitmcnt' => 20
            , 's_ownorgid' => Auth::user()->curorgid
            , 's_showmode' => 1
            , 's_curatorid' => ''
            , 's_org_kind' => ''
        ];
        $param_names = [
            //'s_pageitmcnt' => 20,
            //'s_ym' => null,
            's_ownorgid' => null,
            's_dep_name' => null,
            's_stf_name' => null,
        ];
        $search_params = $this->search_params($request, $param_names, 'reports.' . $report_id);


        $ownorgid = $search_params['s_ownorgid'];

        //$s_ym = $search_params['s_ym'];
        $s_ownorgid = $search_params['s_ownorgid'];
        $s_stf_name = $search_params['s_stf_name'];

        $need_search = false;
        $sc = "1=1";
        $sc .= " and os.active = 1";
        $sc .= " and os.begdate is not null";
        $sc .= " and exists (select 1 from objflags as f
                                        where f.objid=o.id
                                        and f.sysobjid=111
                                        and f.flagtypeid=12)";
        $need_search = true;

        foreach ($search_params as $item => $val) {
            if (isset($val) and strlen($val) > 0) {

                //служебные поля не являются побудителями поиска
                if (!in_array($item, ['s_pageitmcnt']))
                    $need_search = true;

                if ($item == 's_ownorgid') {
                    $sc = $sc . " and o.id = {$val}";

                } elseif ($item == 's_dep_name') {
                    $sc = $sc . " and os.depname like '%{$val}%'";

                } elseif ($item == 's_stf_name') {
                    $sc = $sc . " and concat(' ', os.lname, ' ', os.fname, ' ', ifnull(os.mname, ' ')) like '%{$val}%'";
                }
            }
        }
        $recs = null;

        if ($need_search) {

            $recs = orgstaff::from('orgstaff as os')
                ->join('orgs as o', 'o.id', 'os.orgid')
                ->select('os.id as staffid'
                    , db::raw("concat(' ', os.lname, ' ', os.fname, ' ', ifnull(os.mname, ' ')) as fio")
                    , 'os.lname', 'os.fname', 'os.mname'
                    , 'os.postname'
                    , 'os.orgid', 'o.name as org_name'
                    , 'os.depname as dep_name'
                    , 'os.begdate')
                ->whereRaw($sc)
                ->orderby('fio', 'asc')
                ->get();
//            dd($recs);

            //обновим счетчик использования отчета
            report::updUseCnt($report_id, $userid, \Auth::user()->name);

            //занесем в журнал
            objlog::log_info(855, $report_id, 'запрошен отчет; ' . $sc);
        } else {
            $recs = null;
        }

        $data = new \stdClass();

        $data->ownorgs = org::lstFor_cached([
            'in_orgstaff' => 1,
            'flagtypeid' => 12,
        ]);

//        $data->showmodes = [1 => 'Должники', 3 => 'Переплата', 2 => 'Должники и Переплата', 4 => 'Все'];
//        $data->org_kinds = [1 => 'Поставщики', 3 => 'Не поставщики', 9 => 'Все'];

        $usrrights = [];
        $usrrights['link_tasks'] = usrsysright::isUserHasRightByCode_cached($userid, 'tasks.create');

        return view('orgstaff.rep' . $report_id, compact('recs', 'search_params', 'data', 'usrrights'));
    }


}
