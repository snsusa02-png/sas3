<?php

namespace App\Helpers;

use Illuminate\Http\Request;
use DB;

class DBTools
{
    public static function keyExists($table,$column){
        $rq=DB::table('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
            ->select(DB::raw('1 as ex'))
            ->where('TABLE_NAME',$table)
            ->where('COLUMN_NAME',$column)
            ->where('CONSTRAINT_NAME','like','%foreign');
        $rq=$rq->first();
        $res=false;
        if(isset($rq)){
            $res= $rq->ex==1;
        }
        return $res;
    }

    public static function indexExists($table,$column){
        $rq=DB::table('INFORMATION_SCHEMA.KEY_COLUMN_USAGE')
            ->select(DB::raw('1 as ex'))
            ->where('TABLE_NAME',$table)
            ->where('COLUMN_NAME',$column)
            ->where('CONSTRAINT_NAME','like','%foreign');
        $rq=$rq->first();
        $res=false;
        if(isset($rq)){
            $res= $rq->ex==1;
        }
        return $res;
    }
}
