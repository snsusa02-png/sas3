<?php

namespace App\Http\Controllers;

use App\group;
use App\grpitem;
use App\grptype;
use App\objextid;
use App\usrsysright;
use Illuminate\Http\Request;

class GroupController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 822;
        $this->objcode = 'groups';
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.read');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.create');
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $this->objcode . '.delete');
        }

        return $usrrights;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($grptypeid = null)
    {
        return $this->edit(-1, $grptypeid);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $grptypeid = null)
    {
        //
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new group();
            $rec->id = -1;
            $rec->grptypeid = $grptypeid;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = group::find($id);
        }
        if (isset($rec)) {

            $rec->extids = objextid::from('objextids as ei')
                ->join('extsystems as s','s.id','ei.extsysid')
                ->where('sysobjid', $this->sysobjid)
                ->where('objid', $rec->id)
                ->select('ei.id', 's.name as extsysname', 'extid')
                ->orderby('s.name')
                ->get();

            $rec->items = grpitem::from('grpitems as i')
                ->where('grpid', $rec->id)->select('id', 'sysobjid', 'objid', 'objname')
                ->orderby('sysobjid')
                ->orderby('objname')
                ->with('sysobj')
                ->get();

            $usrrights = $this->setInterfaceRight($id);

            return view($this->objcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\group $group
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id, $grptypeid = null)
    {

        $messages = [
            'name.required' => 'Укажите название группы',
        ];

        $request->validate([
            "name" => "required",
        ], $messages);

        $userid = \Auth::user()->id;

        $mess = "";
        if ($id == -1) {

            $rec = new group();
            $rec->grptypeid = $grptypeid;
            $rec->created_by = $userid;
            $rec->created_at = now();
            $mess = "Создана запись о группе";

        } else {

            $rec = group::find($id);
            $mess = "Изменена запись о группе";
        }

        $ordr = $request->get('ordr');
        $rec->ordr = $ordr ?? 255;
        $rec->name = $request->get('name');
        $rec->updated_by = $userid;
        $rec->save();


        if ($id == -1) {
            //останемся в созданной записи
            return redirect(route($this->objcode . '.edit', $rec->id))->with('success', $mess);

        } else {
            return redirect(route('grptypes.edit', $rec->grptypeid))->with('success', $mess);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = group::delete_by_id($id,$this->sysobjid);
        $sd = array();
        if ($res->err == 1) {
            $route = route($this->objcode . '.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $route = route( 'grptypes.edit',$res->obj['grptypeid']);
            $sd['success'] = 'Запись удалена';
        }
        return redirect($route)->with($sd);
    }

    public function admindelete($id)
    {
        $rec = group::find($id);
        if ($rec) {

            $parent_id = $rec->parent_id;

            $res = $rec->admindelete();

            $sd = array();
            if ($res->err == 1) {
                $route = route($this->objcode . '.edit', $id);
                $sd["error"] = $res->msg;
                objlog::log_info($this->sysobjid, $id, $res->msg, 2);

            } else {
                $route = route( 'grptypes.edit',$res->obj['grptypeid']);
                $sd['success'] = 'Запись удалена администратором';
                objlog::log_info($this->sysobjid, 0, "Административное удаление записи id=" . $id, 2);
            }
        } else {
            $sd['warning'] = 'Запись не найдена!';
            $route = route($this->objcode . '.index');
        }
        return redirect($route)->with($sd);
    }
}
