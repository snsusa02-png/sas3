<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class sysfiletype extends Model
{
    //

    static public function getValidMimeType($filetypeid)
    {

        $rq = static::from('sysfiletypes as sft')
            ->join('mimetypes as m', 'm.id', '=', 'sft.mimetype_id')
            ->where('sft.id', $filetypeid)
            ->select('m.extension')
            ->orderBy('m.extension')
            ->get()->pluck('extension')
            ->unique()->implode(',');

        //Наглый хак возникающий что если мы используем
        //Пробел в имени файла csv, то мы получаем ошибку
        //при загрузке
        $rq = \str_replace(",csv,", ",csv,txt,", ',' . $rq . ',');
        $rq = trim(str_replace(',,', ',', $rq), ',');
        print $rq;
        return $rq;
    }
}
