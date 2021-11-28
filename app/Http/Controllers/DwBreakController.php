<?php

namespace App\Http\Controllers;

use App\driver_work;
use App\dw_break;
use App\mchn_opertype;
use App\objlog;
use App\objtag;
use App\sysobj;
use App\Traits\SearchDataTrait;
use App\Traits\snsTrait;
use App\User;
use App\user_template;
use App\usrsysright;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class DwBreakController extends Controller
{
    use SearchDataTrait;
    use snsTrait;

    public function __construct()
    {
        $this->middleware('auth');

        $this->sysobjid = 1109;
        $this->sysobjcode = 'dw_breaks';
        $this->acl_sysobjcode = sysobj::acl_sysobjcode($this->sysobjcode);

    }

    /*
     * Установка прав пользователя
     */
    protected function setInterfaceRight($recid)
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

        $usrrights['save'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.update');
        $usrrights['delete'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.delete');
        $usrrights['manager'] = usrsysright::isUserHasRightByCode_cached($userid, $this->acl_sysobjcode . '.manager');

        return $usrrights;
    }


    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public
    function create(Request $request, $dw_id = null)
    {
        return $this->edit($request, -1, $dw_id);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function edit(Request $request, $id, $dw_id = null)
    {
        $userid = \Auth::user()->id;

        $usrrights = $this->setInterfaceRight($id);

        if ($id == -1) {
            if ($usrrights['create'] ?? false) {

                //Значения "по-умолчанию" для новой записи ----------------
                $newData = [];

                $tmplt = user_template::getTemplate($userid, $this->sysobjid);
                if (isset($tmplt->dw_break)) {
                    $newData = (array)$tmplt->dw_break; //конвертируем в массив
                }

                //Добавим свои значения
                $newData['id'] = -1;
                $newData['dw_id'] = $dw_id;
                $newData['created_by'] = $userid;

                $rec = new dw_break($newData);
                //---------------------------------------------------------

            } else
                return redirect(route($this->sysobjcode . '.index'));
        } else {

            $rec = dw_break::find($id);

            if (!isset($rec))
                return redirect(route($this->sysobjcode . '.index'));
        }

        $rec->retURL = $request->get('returl');

        $rec->begtime = (isset($rec->breakbegdt)) ? strftime('%H:%M', strtotime($rec->breakbegdt)) : '';
        $rec->endtime = (isset($rec->breakenddt)) ? strftime('%H:%M', strtotime($rec->breakenddt)) : '';

        //ограничитель для времени - не в будущем
        $max_dt = date_create(date('Y-m-d H:i:s', strtotime('+1 day -1 second', strtotime(now()))));
        $max_dt = ($max_dt > now()) ? now() : $max_dt;
        //dd($max_dt, $max_dt->format('H:i'));
        $rec->maxtime = $max_dt->format('H:i');
        //dd($rec->begtime, $rec->endtime, $rec->maxtime);

        //Типы простоев
        $rec->breaktypes = dw_break::breaktypes();

        return view('dw_breaks.edit', compact('rec', "usrrights"));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function update(Request $request, $id)
    {
        //проверим текущий статус документа
        $statusid = ($id == -1) ? 0 : dw_break::find($id)->statusid ?? 0;

        $messages = [
            'dw_id.required' => 'Нет связи с отчетом по работе водителя',
            'begtime.required' => 'Укажите время начала простоя',
            'endtime.required' => 'Укажите время окончания простоя',
            'breaktypeid.required' => 'Укажите тип простоя',
            'reason.required' => 'Укажите причину(пояснение) простоя',
        ];

        $rules = [
            'dw_id' => 'required',
            'begtime' => 'required',
            'endtime' => 'required',
            'breaktypeid' => 'required',
            'reason' => 'required',
        ];

        $request->validate($rules, $messages);

        if (1 == 0) {
            //проверка что запись не пересекается с другой открытой записью с этого объекта за эту дату

            $wrkdate = $request->get('wrkdate');
            $machineid = $request->get('machineid');

            $rules = [
                "items_count" => [
                    function ($attribute, $value, $fail) use ($id, $wrkdate, $machineid) {
                        //
                        $cnt = dw_break::where(['machineid' => $machineid, 'wrkdate' => $wrkdate, 'statusid' => 0])
                            ->where('id', '<>', $id)
                            ->count();
                        if ($cnt > 0) {
                            $fail("Есть другой открытый табель для этой технике/даты!");
                        }
                    },
                ],
            ];

            $request->validate($rules, $messages);
        }

        $userid = \Auth::user()->id;
        $mess = "";
        if ($id == -1) {

            $rec = new dw_break([
                "dw_id" => $request->get('dw_id'),
                "created_by" => $userid,
                "created_at" => now(),
                "updated_by" => $userid,
                "updated_at" => now()]);
            $mess = "Запись создана";
        } else {
            $rec = dw_break::find($id);
            $mess = "Запись обновлена";
        }

        $wrkdate = $rec->driver_work->wrkdate;

        $rec->breaktypeid = $request->get('breaktypeid');
        $rec->reason = mb_substr($request->get('reason'), 0, 160);


        $rec->breakbegdt = date_create($wrkdate)->format('Y-m-d') . ' ' . $request->get('begtime');
        $rec->breakenddt = date_create($wrkdate)->format('Y-m-d') . ' ' . $request->get('endtime');
        //$rec->breakhrs = $request->get('breakhrs');
        $rec->breakhrs = (strtotime($rec->breakenddt) - strtotime($rec->breakbegdt)) / 60 / 60;

        //$rec->active = 1; //$request->get('active', 0);
        $rec->updated_by = $userid;
        $rec->updated_at = now();

        $rec->save();


        //пересчитаем ЗП водителя за простой --------------------------------------
        dw_break::recalc_dw_sums($rec->dw_id);
        //---------------------------------------------------------------------------------------

        objlog::log_info($this->sysobjid, $rec->id, $mess, 5);


        $retURL = $request->get('returl')
            ?? route('driver_works.edit', $rec->dw_id) . '#break' . $rec->id;

        return redirect($retURL)->with('success', $mess);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public
    function destroy($id)
    {
        $res = dw_break::delete_by_id($id, $this->sysobjid);
        $route = "";
        $sd = array();
        if ($res->err == 1) {
            objlog::log_info($this->sysobjid, $id, 'Попытка удаления записи', 2);
            $route = route('dw_breaks.edit', $id);
            $sd["error"] = $res->msg;
            connectify('error', $res->obj['name'] ?? 'id:' . $res->obj['id'], $res->msg);
        } else {
            $sd['success'] = 'Запись (' . $id . ': '
                . ($res->obj['name'] ?? '') . ') удалена';
            objlog::log_info($this->sysobjid, 0, $sd['success'], 5);


            //пересчитаем данные в driver_works ----------------------------------------------
            dw_break::recalc_dw_sums($res->obj['dw_id']);
            //--------------------------------------------------------------------------------

            $route = route('driver_works.edit', $res->obj['dw_id']);
            connectify('success', ($res->obj['name'] ?? '-'), 'Запись удалена.');
        }
        return redirect($route)->with($sd);
    }


    public function make_template($id)
    {

        if (!isset($id))
            return redirect(route('home'))->with(['error' => 'not id']);


        $userid = \Auth::user()->id;

        $rec = dw_break::find($id);
        if (!isset($rec))
            return redirect(route('home'))->with(['error' => 'record not found']);

        $document = array_filter($rec->makeHidden(['id', 'created_at', 'updated_at'])->toArray());

        $document['tags'] = objtag::lstTags($this->sysobjid, $id);
        //dd($document);

        $template_js = [
            'dw_break' => $document,
        ];
        $template_js = json_encode($template_js);

        user_template::addOrUpdate($userid, $this->sysobjid, $template_js);

        return redirect(route($this->sysobjcode . '.edit', $id))->with(['success' => 'Шаблон сохранен']);

    }

}
