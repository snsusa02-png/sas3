<?php

namespace App;

use App\Traits\DeleteFileTrait;
use Illuminate\Database\Eloquent\Model;

class ri_image extends Model
{
    use DeleteFileTrait;

    public static function getArrPhotos($refitmid)
    {
        $rq = ri_image::
        select("id", "filename")
            ->where('refitmid', $refitmid)
            ->where('active', 1)
            ->orderby('ordr')->get()->toArray();
        return $rq;

    }

    public function delete()
    {//SNS. расширение метода класса для удаления файла с диска при удалении записи из таблицы

        $folder = substr($this->filename, 0, strrpos($this->filename, "/") + 1);
        $filename = substr(strrchr($this->filename, "/"), 1);
        //dd($filename);

        //Удалим файл с диска
        $this->deleteOne($folder, 'public', $filename);
        //и удалим запись
        return parent::delete();
    }


}
