<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class mimetype extends Model
{
    //

    static public function getFileMime($FileExt){
        $id=null;
        $rq=static::where('extension',mb_strtoupper($FileExt))->
                select('id')->first();
        if(isset($rq)){
            $id=$rq->id;
        }
        return $id;
    }
}
