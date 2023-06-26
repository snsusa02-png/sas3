<?php

namespace App\Http\Controllers;

use App\objlog;
use App\ri_cmpnd_item;
use App\ri_compound;
use App\sysobj;
use App\usrsysright;
use App\wrhdoc;
use App\wrhdoclst;
use App\wrhdoctype;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RiCmpndItemController extends Controller
{
    protected $sysobjid;
    protected $parsysobjid;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 148;
        $this->parsysobjid = 147;
        $this->sysobjcode = 'ri_cmpnd_items';
    }

    protected function setInterfaceRight($id)
    {

        //по acl указанного объекта
        $acl_sysobjcode = sysobj::where('code', $this->sysobjcode)
                ->select(db::raw("ifnull(acl_sysobjcode, code) as acl_sysobjcode"))
                ->first()->acl_sysobjcode ?? $this->sysobjcode;

        $usrrights = array();
        $usrrights['create'] = false;
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['refitm.edit'] = false;
        $usrrights['price.edit'] = false;

        $userid = \Auth::user()->id;

        $usrrights['create'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.create');

        if ($id == -1) {
            //Новый документ - можно сохранять
            $usrrights['save'] = $usrrights['create'];
            $usrrights['refitm.edit'] = $usrrights['save'];
        } else {
            $rec = ri_cmpnd_item::find($id);
            if (isset($rec)) {
                $docsigned = $rec->ri_compound->docsigned;
                if (!$docsigned) {
                    $usrrights['save'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.update');;
                    $usrrights['delete'] = usrsysright::isUserHasRightByCode($userid, $acl_sysobjcode . '.delete');;
                }
                $usrrights['refitm.edit'] = ($usrrights['save']);
                $usrrights['delete'] = ($usrrights['delete']);
            }
        }
//dd($acl_sysobjcode, $usrrights);
        return $usrrights;
    }


    public function create($docid)
    {
        return $this->edit(-1, $docid);
    }


    public function edit($id, $docid = null)
    {
        //
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {

            //Значения "по-умолчанию" для новой записи

            $rec = new ri_cmpnd_item();
            $rec->id = -1;
            $rec->cmpndid = $docid;
            $rec->created_by = $userid;
            $rec->created_at = now();

        } else {
            $rec = ri_cmpnd_item::find($id);
        }
        if (isset($rec)) {

            /*$rec->subtypes = wrhdoctype::where('parent_id', $rec->wrhdoc->doctypeid)
                ->where('active', 1)
                ->where('lst_editable', 1)
                ->select('id', 'name')
                ->orderBy('ordr')
                ->orderBy('name')
                ->get()
                ->pluck('name', 'id')->toArray();

            if (!$rec->subtypeid) {
                $rec->subtypeid = (count($rec->subtypes) == 1) ? key($rec->subtypes) : null;
            }*/

            //$rec->sysobjs = sysobj::lst_sysobjs4grptypes_cache();
            //dd($rec->sysobjs);

            return view('' . $this->sysobjcode . '.edit', compact(['rec', 'usrrights']));
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\wrhdoclst $wrhdoclst
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {

        $rules = [
            "cmpndid" => "required",
            "refitmid" => "required",
            "min_qty" => "required|not_greater_than_field:max_qty",
            "max_qty" => "required|gte:min_qty",
        ];

        $messages = [
            "cmpndid.required" => "Потерялась привязка к докуенту. Вернитесь в документ",
            "refitmid.required" => "Укажите материал",
            "min_qty.required" => "Укажите минимально-допустимое количество",
            "max_qty.required" => "Укажите максимально-допустимое количество",
            'min_qty.not_greater_than_field' => 'Минимальное количество не может превышать максимальное количество!',
            'max_qty.gte' => 'Максимально количество должно быть больше или равно минимальному количеству!',
        ];

        //dd($rules);
        Validator::make($request->all(), $rules, $messages)->validate();

        $doc = ri_compound::find($request->get('cmpndid'));

        $userid = \Auth::user()->id;
        if ($id == -1) {
            $rec = new ri_cmpnd_item([
                "cmpndid" => $request->get('cmpndid'),
                "refitmid" => 0,
                "min_qty" => 0,
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);

            $msg = "Позиция создана: ";
        } else {
            $rec = ri_cmpnd_item::find($id);
            $msg = "Позиция обновлена: ";
        }

        $rec->refitmid = $request->get('refitmid');
        $rec->min_qty = $request->get('min_qty');
        $rec->max_qty = $request->get('max_qty');

        $rec->updated_by = $userid;
        $rec->updated_at = now();
        $rec->save();

        $msgType = "success";
        return redirect(route('ri_compounds.edit', $rec->cmpndid))->with($msgType, $msg);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\wrhdoclst $wrhdoclst
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        $rec = ri_cmpnd_item::find($id);

        if (isset($rec)) {
            if ($rec->ri_compound->docsigned == 1) {

                $sd = array();
                $sd["error"] = "Нельзя изменять состав утвержденного документа!";
                objlog::log_info($this->sysobjid, $rec->id, $sd["error"], 2);

                return redirect(route("ri_cmpnd_items.edit", $id))->with($sd);

            } else {

                $res = ri_cmpnd_item::delete_by_id($id);
                $route = "";
                $sd = array();
                if ($res->err == 1) {
                    $route = route('ri_cmpnd_items.edit', $id);
                    $sd["error"] = $res->msg;
                    objlog::log_info($this->sysobjid, $id, $res->msg, 2);
                } else {
                    $route = route('ri_compounds.edit', $res->rec->cmpndid);
                    $sd['success'] = 'Запись о документе удалена';
                }
                return redirect($route)->with($sd);
            }
        }
    }

}
