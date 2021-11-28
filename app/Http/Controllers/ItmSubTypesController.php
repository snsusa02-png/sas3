<?php

namespace App\Http\Controllers;

use App\objextid;
use Illuminate\Http\Request;
use App\ItmSubType;
use App\itmtype;
use App\usrsysright;
use Auth;
use Illuminate\Support\Str;


class ItmSubTypesController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 102;
        $this->syscode = 'itmsubtypes';
    }

    protected function setInterfaceRight()
    {
        $userid = Auth::user()->id;

        $usrrights = array();
        $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, "itmtypes.update");
        $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, "itmtypes.delete");

        return $usrrights;
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($itmtypeid)
    {
        $usrrights = $this->setInterfaceRight();

        if (!$usrrights['save']) {
            return redirect(route('itmtypes.edit', $itmtypeid))->with('error', 'У Вас нет прав на создание /изменение подкатегории');
        }
        //Значения "по-умолчанию" для новой записи
        $itmsubtype = new ItmSubType();
        $itmsubtype->id = -1;
        $itmsubtype->itmtypeid = $itmtypeid;
        $itmsubtype->active = 1;
        $itmsubtype->created_by = \Auth::user()->id;
        $itmsubtype->updated_by = \Auth::user()->id;
        $itmname = "";
        $itmname = itmtype::getName($itmtypeid);
        return view('itmsubtypes.edit', compact(['itmsubtype', 'itmname', 'usrrights']));
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
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
        $usrrights = $this->setInterfaceRight();
        $itmsubtype = ItmSubType::find($id);
        $itmname = "";
        if (is_object($itmsubtype)) {
            $itmname = itmtype::getName($itmsubtype->itmtypeid);

            $itmsubtype->extids = objextid::from('objextids as ei')
                ->join('extsystems as s', 's.id', 'ei.extsysid')
                ->where('sysobjid', $this->sysobjid)
                ->where('objid', $itmsubtype->id)
                ->select('ei.id', 's.name as extsysname', 'extid')
                ->orderby('s.name')
                ->get();
        }
        return view('itmsubtypes.edit', compact(['itmsubtype', 'itmname', 'usrrights']));
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
        $request->validate([
            "name" => "required",
            "itmtypeid" => "required",
        ]);
        $mess = "";
        $itmtypeid = $request->get('itmtypeid', 0);
        if ($id == -1) {
            $itsubmtype = new  ItmSubType([
                "itmtypeid" => $itmtypeid,
                "created_by" => \Auth::user()->id,
                "created_at" => now(),
                "updated_by" => \Auth::user()->id,
                "updated_at" => now()
            ]);
            $mess = "Подкатегория создана";
        } else {
            $itsubmtype = ItmSubType::find($id);
            $mess = "Подкатегория обновлена";
        }
        $itsubmtype->name = $request->get('name');
        $itsubmtype->slug = $request->get('slug') ?? Str::slug($itsubmtype->name);
        $itsubmtype->active = $request->get('active', 0);
        $itsubmtype->updated_by = \Auth::user()->id;
        $itsubmtype->updated_at = now();
        $itsubmtype->save();
        return redirect(route('itmtypes.edit', $itmtypeid))->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $res = ItmSubType::delete_by_id($id,$this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            $route = route('itmsubtypes.edit', $id);
            $sd["error"] = $res->msg;
        } else {
            $route = route('itmtypes.edit', $res->obj['itmtypeid']);
            $sd['success'] = 'Подкатегория была удалена';
        }
        return redirect($route)->with($sd);
    }
}
