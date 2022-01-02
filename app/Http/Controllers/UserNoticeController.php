<?php

namespace App\Http\Controllers;

use App\objlog;
use App\user_notice;
use App\usrsysright;
use Illuminate\Http\Request;

class UserNoticeController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 880;
        $this->objcode = 'user_notices';
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

        $usrrights['org_saldos.read'] = false;
        $usrrights['org_saldos.create'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->objcode . '.delete');
            $usrrights['org_saldos.read'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_saldos.read');
            $usrrights['org_saldos.create'] = usrsysright::isUserHasRightByCode_cached($userid, 'org_saldos.create');
        }

        return $usrrights;
    }


    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function destroy( $id, $route)
    {
        //dd($route);
        $res = user_notice::delete_by_id($id, $this->sysobjid);
        //$ret_url = isset($route) ? route($route) : "home";
        $ret_url = isset($route) ? $route : "home";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'], $res->msg);
        } else {

        }
        return redirect($ret_url);
    }


}
