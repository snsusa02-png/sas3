<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class importfilelog extends Model
{
    //
    public $timestamps = false;
    static public function getlog4file($file_id){
        return static::where('importfile_id',$file_id)
                        ->orderBy('id','asc')->get();
    }
}
