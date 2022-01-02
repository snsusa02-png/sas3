<?php

namespace App\Http\Controllers;

use App\ri_unit;
use App\contract;
use App\equiprqst_item;
use App\objlog;
use App\Post;
use App\qcheck;
use App\qcheck_item;
use App\refitem;
use App\sysobj;
use App\Traits\DeleteFileTrait;
use App\unittype;
use App\usrsysright;
use Illuminate\Http\Request;

class RiUnitController extends Controller
{
    use DeleteFileTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 144;
        $this->sysobjcode = 'ri_units';
    }

    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $sysobjcode = 'refitems';  //по RefItems

        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode($userid, $sysobjcode . '.read');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $sysobjcode . '.create');
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $sysobjcode . '.delete');
        }

        return $usrrights;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($refitmid)
    {
        return $this->edit(-1, $refitmid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\ri_unit $ri_unit
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $refitmid = null)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи

            $rec = new ri_unit();
            $rec->id = -1;
            $rec->refitmid = $refitmid;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = ri_unit::find($id);
        }

        if (isset($rec)) {

            $rec->retRoute = route('refitems.edit', $rec->refitmid);

            $unittypes = unittype::unittypes_cache();
            //исключим ЕИ уже использованные для этого товара ----------------------
            $used = ri_unit::from('ri_units as u')
                ->where('refitmid', $refitmid)
                ->where('id', '<>', $id)
                ->select('u.unittypeid')->get()->pluck('unittypeid')->toArray();
            foreach ($used as $elm) {
                unset($unittypes[$elm]);
            }
            unset($unittypes[$rec->refitem->unittypeid]);   //исключим родительскую ЕИ
            $rec->unittypes = $unittypes;
            //dd($unittypes);
            // ----------------------------------------------------------------------

            $usrrights = $this->setInterfaceRight($rec->id);

            return view($this->sysobjcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\ri_unit $ri_unit
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        $messages = [
            'refitmid.required' => 'Не задана связь с товаром',
            'unittypeid.required' => 'Укажите альтернативную ЕИ',
            'k2ref_unit.required' => 'Укажите коэффициент пересчета в базовую ЕИ',
            'k2ref_unit.gt' => 'Коэффициент должен быть больше 0',
        ];

        $rules = [
            "refitmid" => "required",
            "unittypeid" => "required",
            "k2ref_unit" => "required|gt:0",
        ];

        $request->validate($rules, $messages);


        $userid = \Auth::user()->id;
        $retURL = $request->get('retURL') ?? '/';

        $refitmid = $request->refitmid;
        $unittypeid = $request->unittypeid;

        //Проверим чтобы не было одинаковых ЕИ для одной refitmid
        $prm = new \stdClass();
        $prm->refitmid = $refitmid;
        $prm->unittypeid = $unittypeid;
        $prm->id = $id;

        $rules = [
            //В форме должно быть поле ttt
            "ttt" => [
                function ($attribute, $value, $fail) use ($prm) {

                    //поиск по альтернативным названиям других записей этого же системного типа
                    $cnt = ri_unit::where(['refitmid' => $prm->refitmid, 'unittypeid' => $prm->unittypeid])
                        ->where('id', '<>', $prm->id)->count();
                    if ($cnt > 0) {
                        $fail("Такая ЕИ уже использована для этого товара!");
                    }
                },
            ],
        ];

        $request->validate($rules, $messages);


        $mess = "";

        $mess = "Запись обновлена";
        if ($id == -1) {
            //попробуем поискать - возможно такое название для объекта уже есть?
            $rec = ri_unit::where([
                'refitmid' => $refitmid,
                'unittypeid' => $unittypeid,
            ])->first();

            if (!isset($rec)) {
                $rec = new ri_unit([
                    "refitmid" => $refitmid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $mess = "Запись создана";
            }
        } else {
            $rec = ri_unit::find($id);
        }
        $rec->unittypeid = $unittypeid;
        $rec->k2ref_unit = $request->k2ref_unit;
        $rec->active = 1;
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();
        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);

        return redirect($retURL)->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\ri_unit $ri_unit
     * @return \Illuminate\Http\Response
     */
    public
    function destroy(Request $request, $id)
    {
        $retURL = $request->get('retURL') ?? '/';

        //Возьмем права от родительской системы:
        $sysobjid = $request->get('sysobjid');
        $sysobj = sysobj::find($sysobjid);
        $usrrights = $this->setInterfaceRight($id);
        //dd($usrrights);

        if ($usrrights['delete']) {
            $res = ri_unit::delete_by_id($id, $this->sysobjid);

            $route = "";
            $sd = array();
            if ($res->err == 1) {
                $route = route('ri_units.edit', $id);
                $sd["error"] = $res->msg;
            } else {

                $route = $request->get('retURL') ?? '/';
                $sd['success'] = 'Запись удалена';
            }
        } else {
            $route = route('ri_units.edit', $id);
            $sd["error"] = "Нет прав на удаление";
        }
        return redirect($route)->with($sd);
    }

}
