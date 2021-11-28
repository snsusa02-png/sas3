<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Result;
use App\Traits\DeleteTrait;
use App\Traits\DeleteFileTrait;


class importfile extends Model
{
    use DeleteTrait;
    use DeleteFileTrait;


    public $timestamps = false;

    public function whoimport()
    {
        return $this->hasOne(User::class, 'id', 'import_by')->withDefault();
    }

    //связь с использованием в группах
    public function logs()
    {
        return $this->hasMany(importfilelog::class, 'importfile_id', 'id');
    }

    public function admindelete()
    {
        $result = new Result;
        //Удаляем себя вместе с позициями
        try {
            DB::transaction(function () {
                $this->logs()->delete();

                $this->deleteOne($this->catalog . '/', $this->storage, $this->systemfilename);
                //dd($this->catalog, $this->storage, $this->systemfilename);

                return parent::delete();
            });
        } catch (\Exception $e) {
            $result->err = $e->errorInfo[0];
            $result->msg = 'Ошибка удаления записи: ' . $e->message;
        }
        return $result;
    }

}
