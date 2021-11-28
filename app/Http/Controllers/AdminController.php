<?php

namespace App\Http\Controllers;

use App\collector;
use App\contract;
use App\document;
use App\ac;
use App\buildobj;
use App\contract_category;
use App\contract_org;
use App\contract_regnum;
use App\doctype;
use App\eventtype;
use App\Jobs\SendNotify;
use App\obj_link;
use App\obj_reader;
use App\obj_staff;
use App\objfile;
use App\objflag;
use App\objlog;
use App\objtag;
use App\ocl_item;
use App\org;
use App\orgdep;
use App\orgstaff;
use App\report;
use App\User;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\user_notice;
use App\user_template;
use App\userorg;
use App\usrsysright;
use App\obj_org;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\buildopertype;
use Illuminate\Support\Facades\Route;

class AdminController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 855;
        $this->sysobjcode = 'reports';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        if (\Auth::user()->active == 0)
            return view('home');

        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['updregnum'] = false;
        $usrrights['admindelete'] = false;
        $usrrights['obj_staffs.create'] = false;
        $usrrights['link_docs'] = false;
        $usrrights['make_template'] = false;        //Право создать шаблон на основе данных теущей записи

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
            $usrrights['updregnum'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.updregnum');
            $usrrights['link_docs'] = $usrrights['save'];
            $usrrights['make_template'] = true;
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
        if (\Auth::user()->active == 0)
            return view('home');

        $userid = \Auth::user()->id;

        $usrrights = array(
            'read' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.read'),
            'create' => usrsysright::isUserHasRightByCode_cached($userid, $this->sysobjcode . '.create'),
            'edit_report' => usrsysright::isUserHasRightByCode_cached($userid, 'admin-global'),
        );

        $data = new \stdClass();

        $data->usrrights = $usrrights;


        return view('admin.index', compact(
            'data', 'usrrights'));
    }

}
