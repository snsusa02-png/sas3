<?php

namespace App\Http\Controllers;

use App\idcard_staff;
use App\objflag;
use App\objlog;
use App\sysobj;
use App\Traits\Result;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class IdcardStaffController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 1960;
        $this->sysobjcode = 'idcards';
        $this->objcode = $this->sysobjcode;
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);
    }


    protected function setInterfaceRight($id)
    {
        /*
         * Формирует массив прав пользователя для текущего объекта
        */
        $userid = \Auth::user()->id;

        $usrrights = array();
        $usrrights['read'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.read');
        $usrrights['create'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.create');
        $usrrights['save'] = false;
        $usrrights['delete'] = false;
        $usrrights['admindelete'] = false;

        if ($id == -1) {
            $usrrights['save'] = $usrrights['create'];
            $usrrights['delete'] = false;
        } else {
            $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
            $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
            //$usrrights['mchn_opertypes.create'] = $usrrights['save'];
        }
        $usrrights['edit'] = $usrrights['save'];
//dd($usrrights);
        return $usrrights;
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create($cardid)
    {
        return $this->edit(-1, $cardid);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id, $cardid)
    {
        $userid = \Auth::user()->id;

        if ($id == -1) {
            //Значения "по-умолчанию" для новой записи
            $rec = new idcard_staff([
                'id' => -1,
                'cardid' => $cardid,
                'active' => 1,
//                'begdate' => date_create(now())->format('Y-m-01'),   //Первый день месяца
                'begdate' => date_create(now())->format('Y-m-d'),   //Текущий день
                'created_by' => \Auth::user()->id,
            ]);

        } else
            $rec = idcard_staff::findOrFail($id);

        $data = new \stdClass();

        //кандидаты организаций-владельцев техники. Отберем по признаку "12-ГК", или та организация которая указана сейчас
//        $data->orgs = org::lstFor(['flagtypeid_or_id' => [12, $rec->orgid]]);

//        $data->ObjFlags = objflag::getFlags4Obj($this->sysobjid, $id);

//        $data->places = DB::select("SELECT distinct src_placename as name FROM `idcard_staffs`
//                    UNION SELECT distinct tgt_placename as name FROM `idcard_staffs` order by 1");
        //dd($data);

        $usrrights = $this->setInterfaceRight($id);

        return view('idcard_staffs.edit', compact('rec', "data", "usrrights"));
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
        //
        $messages = [
            'staffid.required' => 'Укажите сотрудника',
            'begdate.required' => 'Укажите дату закрепления за сотрудником',
        ];

        $rules = [
            "staffid" => "required",
            "begdate" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {
            $rec = new idcard_staff([
                "cardid" => $request->get('cardid'),
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
        } else {
            $rec = idcard_staff::find($id);
        }
        $rec->staffid = $request->get('staffid');
        $rec->begdate = $request->get('begdate');
        $rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        //соберем строку с измененными полями -------------------------------------------------------------------
        $diffs = $this->field_diff_list($rec, ['id', 'created_by', 'updated_by', 'created_at', 'updated_at']);
        if ($diffs === '')
            $msg_simple = $rslt_msg = "Запись пересохранена без изменений";
        else {
            $msg_simple = 'Запись ' . (($id == -1) ? 'создана' : 'изменена');
            $rslt_msg = $msg_simple . ': ' . $diffs;
        }
        //-------------------------------------------------------------------------------------------------------

        $rec->save();

        objlog::log_info($this->sysobjid, $rec->id, $rslt_msg, 5);
        //connectify('success', $rec->name, $msg_simple);

        // Перерасчет даты окончания периода закрепления карты -----------------------------
        $recs = idcard_staff::where('active', 1)
            ->where('cardid', $rec->cardid)
            ->select('id', 'begdate', 'enddate')
            ->orderby('begdate', 'desc')
            ->get();
        //$enddate = '3000-01-01';
        $enddate = null;
        foreach ($recs as $r) {
            $r->enddate = $enddate;
            $r->save();
            $enddate = date_create($r->begdate);
            $enddate = $enddate->modify('-1 day')->format('Y-m-d');
        }
        //----------------------------------------------------------------------------------

        if (1 == 0 and $id == -1) {
            return redirect(route($this->sysobjcode . '.edit', $rec->id));
        } else {
//            $pageno = session($this->sysobjcode . '_pageno');
//            return redirect(route($this->sysobjcode . '.index') . '?page=' . $pageno . '#' . $rec->id);
            return redirect(route('idcards.edit', $rec->cardid));
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
        $res = idcard_staff::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('idcard_staffs.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['src_placename'].' - '.$res->obj['tgt_placename'], $res->msg);
        } else {
            $sd['success'] = 'Запись о держателе карты (' . $id . ': '
                . $res->obj['cardid'] . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);

//            $pageno = session($this->objcode . '_pageno');
            //$route = route($this->objcode . '.index') . '?page=' . $pageno;
            $route = route('idcards.edit', $res->obj['cardid']);
            //connectify('success', $res->obj['name'], 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }

    static public function list_for(Request $request)
    {
        //2021-04-05 SNS. Список для select-ов {id,name}
//todo: Переделать!
        $result = "";
        try {

            $list = idcard_staff::lstFor([
                's_src_placename' => $request->s_src_placename,
                's_tgt_placename' => $request->s_tgt_placename,
                'orgid' => $request->orgid,
                'active' => $request->active,
            ]);

            $result = array('list' => $list);

        } catch (\Exception $e) {
            Log::error('idcard_staff::list_for:' . $e->getMessage());
        }
        return response()->json($result);
    }


    public function load()
    {
        $userid = \Auth::user()->id;
        $usrrights = $this->setInterfaceRight(-1);

        $rec = new \stdClass();

        return view($this->sysobjcode . '.load', compact('rec', "usrrights"));
    }


    public function import(Request $request)
    {
        //Импорт без сохранения файла на диск. Только обработка
//todo: Переделать!
        $messages = [
            'doc.required' => 'Не указан файл с данными',
        ];

        $rules = [
            "doc" => "required",
        ];

        $request->validate($rules, $messages);

        $userid = \Auth::user()->id;
        //$returl = $request->get('retroute');

        $usrrights = $this->setInterfaceRight(-1);
        $result = new Result();
        $rec = new \stdClass();

        $rec->extsysid = 9;   // ? М.б. использовать для связывания по кодам во внешней системе
        //dd($rec);

        if ($request->hasfile('doc')) {

            $file = $request->doc;

            $filesize = $file->getSize();
            $name = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
            $extension = $file->getClientOriginalExtension();
            //dd($name, $extension, $filesize);

            if (1 == 1)
                $rec = idcard_staff::import_001($file, $rec);
            else {
                $result->err = 1;
                $result->msg = 'Не определена процедура импорта!';
            }
            //--------------------------------------------------------------------------------
            //dd($result->msg);


        } else {
            $result->err = 1;
            $result->msg = 'Файл с данными не загружен!';
        }

        return view($this->sysobjcode . '.load', compact('rec', "usrrights"));
    }

    static public function data_for_card(Request $request)
    {
        //todo: Переделать!
        //2023-09-24 SNS. Данные разные

        $result = "";
        try {

            $list = idcard_staff::from('idcard_staffs as rp')
                ->where([
                    'rp.id' => $request->rp_id,
//                'active' => 1,
                ])
                ->select(
                    'rp.orgid', 'o.name as org_name'
                    , 'rp.ref_machineid', db::raw("concat(m.regnum, ' (', m.name, ', ', mo.name, ')' ) as ref_machine_name")
                )
                ->first()->toArray();

//            dd($list);

            $result = array('data' => $list);
            //Log::info(implode('; ', $list));

        } catch (\Exception $e) {
            Log::error('idcard_staff::data_for_card:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
