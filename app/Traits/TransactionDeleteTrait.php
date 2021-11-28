<?php

namespace App\Traits;

use DB;
use App\Traits\Result;

//Трайт для удаления записей
//Транзакция и обработка ошибок
//Возвращает объект  Result:
//   err  наличие ошибок (0 - нет ошибок, >0 - есть ошибки)
//   msg - сообщение об ошибках
//   obj -  удаленная запись в виде ассоциативного массива соответствующего объекта
//если объект не существует то ошибка не возвращается
trait TransactionDeleteTrait
{
    static public function t_delete_by_id($id)
    {
        $level = DB::transactionLevel();
        if ($level == 0) throw new \Exception("Транзакция снаружи не была начата");
        DB::beginTransaction();
        $result = new Result;
        try {
            $obj = static::find($id);
            if (isset($obj)) {
                $result->obj = $obj->toArray();
                $obj->delete();
            };
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
        } catch (\Exception $e) {
            $result->err = 1;
            $result->msg = 'Ошибка удаления записи: ' . $e->message;
        }
        if ($result->err == 1) {
            DB:
            rollback(); //Откат производится на начало транзакции в этой функции
        } else {
            DB::commit(); // Это не реальный коммит, он просто уменщает уровень транзакции (и когда уровень достигнет 0 тогда и будет коммит)
        }
        return $result;
    }

    static public function t_delete_by_obj(&$obj)
    {
        $level = DB::transactionLevel();
        if ($level == 0) throw new \Exception("Транзакция снаружи не была начата");
        DB::beginTransaction();
        $result = new Result;
        try {
            if (isset($obj)) {
                $result->obj = $obj->toArray();
                $obj->delete();
            };
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
            $result->msg = 'Ошибка удаления записи: ' . $e->message;
        }
        if ($result->err == 1) {
            DB:
            rollback(); //Откат производится на начало транзакции в этой функции
        } else {
            DB::commit(); // Это не реальный коммит, он просто уменщает уровень транзакции (и когда уровень достигнет 0 тогда и будет коммит)
        }
        return $result;
    }
}
