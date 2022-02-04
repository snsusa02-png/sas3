<?php

namespace App\Traits;

use App\obj_comment;
use App\obj_link;
use App\obj_msg;
use App\objextid;
use App\objfile;
use App\objtag;
use App\obj_finoper;
use App\task_report;
use App\task_user;
use DB;
use App\Traits\Result;
use Illuminate\Support\Facades\Log;

//Трайт для удаления записей
//Транзакция и обработка ошибок
//Возвращает объект  Result:
//   err  наличие ошибок (0 - нет ошибок, >0 - есть ошибки)
//   msg - сообщение об ошибках
//   obj -  удаленная запись в виде ассоциативного массива соответствующего объекта
//если объект не существует то ошибка не возвращается
trait DeleteTrait
{
    static public function delete_by_id($id, $sysobjid = null)
    {
        $result = new Result;
        try {
            DB::transaction(function () use ($id, $sysobjid, &$result) {
                $obj = static::find($id);
                if (isset($obj)) {
                    $result->rec = $obj;    //2022-02-04 SNS. Для привычного обращения к аттрибутам записи
                    $result->obj = $obj->toArray();

                    //------------------------------------------
                    if (isset($sysobjid)) {

                        //удалим записи о связях между объектами (с дочками и с родителями)
                        obj_link::where(['sysobjid' => $sysobjid, 'objid' => $id])->delete();
                        obj_link::where(['lnksysobjid' => $sysobjid, 'lnkobjid' => $id])->delete();

                        //удалим внешние идентификаторы объекта
                        //удалим записи о читателях
                        //удалим записи о тэгах
                        //удалим записи о сообщениях
                        //удалим записи о коментариях

                        foreach ([
                                     'App\objextid',
                                     'App\obj_comment',
                                     'App\obj_msg',
                                     'App\objtag',
                                     'App\obj_reader',
                                     'App\obj_approval',
                                     'App\obj_finoper',
                                 ] as $model) {

                            $model::where('sysobjid', $sysobjid)
                                ->where('objid', $id)
                                ->delete();

                        }

                        //удалим связанные файлы ---------------------------------
                        foreach (objfile::where('sysobjid', $sysobjid)
                                     ->where('objid', $id)->get() as $file) {
                            $res = objfile::destroy($file->id);
                        }
                        objfile::where('sysobjid', $sysobjid)
                            ->where('objid', $id)
                            ->delete();
                        //--------------------------------------------------------


                        //специфика (заляпуха) ----------------------------
                        if ($sysobjid == 961) {
                            //если удаляем задачу, то

                            // нужно удалить и персонал по задаче
                            task_user::where('taskid', $id)->delete();

                            // нужно удалить и отчетность
                            task_report::where('taskid', $id)->delete();
                        }
                    }
                    //------------------------------------------------------


                    $obj->delete();
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $result->err = 1;
            $mesg = "";
            switch ($e->errorInfo[0]) {
                case 23000:
                    $mesg = "для этой записи существуют подчиненные записи";
                    break;
                default:
                    $mesg = 'код ' . $e->errorInfo[0];
                    break;
            }
            $result->msg = 'Ошибка удаления записи - ' . $mesg;
            if (config('app.debug') != false) {
                $result->msg .= "\nDebug info:\n" . $e->errorInfo[2];
            }
            Log::error($result->msg);
        } catch (\Exception $e) {
            $result->err = 1;
            $result->msg = 'Ошибка удаления записи: ' . $e->getMessage();
            Log::error($result->msg);
        }
        return $result;
    }

    static public function delete_by_obj(&$obj)
    {
        $result = new Result;
        try {
            DB::transaction(function () use (&$obj, &$result) {
                if (isset($obj)) {
                    $result->obj = $obj->toArray();
                    $obj->delete();
                }
            });
        } catch (\Illuminate\Database\QueryException $e) {
            $result->err = 1;
            $mesg = "";
            switch ($e->errorInfo[0]) {
                case 23000:
                    $mesg = "для этой записи существуют подчиненные записи";
                    break;
                default:
                    $mesg = 'код ' . $e->errorInfo[0];
                    break;
            }
            $result->msg = 'Ошибка удаления записи, ' . $mesg;
            if (config('app.debug') != false) {
                $result->msg .= "\nDebug info:\n" . $e->errorInfo[2];
            }
        } catch (\Exceprion $e) {
            $result->err = 1;
            $result->msg = 'Ошибка удаления записи: ' . $e->getMessage();
        }
        return $result;
    }
}
