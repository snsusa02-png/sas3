<?php

namespace App;

use DB;
use Illuminate\Database\Eloquent\Model;
use Log;

class objlog extends Model
{
    //уровень сообщения: 1- fatalerror, 2 - error, 3 info, 4 warning, 5 debug, 6 trace

    protected $table = 'objlogs';


    private static function write_low($in)
    {
        try {
            if (!isset($in['sysobjid'])
                || is_null($in['sysobjid'])
                || !isset($in['objid'])
                || is_null($in['objid'])
            ) return;

            $need_key = array('sysobjid' => 1, 'objid' => 1, 'info' => 1, 'errlvl' => 1,
                'write_by' => 1);

            $rec = array_intersect_key($in, $need_key);

            /*
             * errlvl : 1 fatalerror, 2 error , 3 info, 4 warning, 5 debug, 6 trace
             */
            if (!isset($rec['errlvl']) || is_null($rec['errlvl'])) $rec['errlvl'] = 3;
            if (!isset($rec['write_by']) || is_null($rec['write_by'])) $rec['write_by'] = \Auth::user()->id;
            //if (!isset($rec['evnt_code']) || is_null($rec['evnt_code'])) $rec['evnt_code'] = null;
            $rec['info'] = mb_substr($rec['info'], 0, 255);
            //$rec['evnt_code'] = mb_substr($rec['evnt_code'], 1, 30);


            DB::table('objlogs')->insert($rec);

        } catch (\Exception $e) {

            Log::error("Ошибка записи в журнал.\n"
                . "Сообщения" . var_export($in) . "\n"
                . $e->getMessage() . "\n" . $e->getTraceAsString()
            );
        }
    }

    public static function
    write($sysobjid, $objid, $info, $objpos, $errlvl = null /*, $evnt_id, $evnt_code*/, $write_by)
    {
        $rec = [
            'sysobjid' => $sysobjid
            , 'objid' => $objid
            , 'info' => $info
            , 'objpos' => $objpos
            , 'errlvl' => $errlvl
            , 'write_by' => $write_by
//            , 'evnt_id' => $evnt_id
//            , 'evnt_code' => $evnt_code
        ];

        self::write_low($rec);
    }

    public static function log_info($sysobjid, $objid, $info, $errlvl = null, $objpos = null
        /*, $evnt_id = null, $evnt_code = null*/)
    {
        /*
         * $errlvl = уровень сообщения: 1- fatalerror, 2 - error, 3 info, 4 warning, 5 debug, 6 trace
         * todo: 2021-03-17 SNS хочется поменять на:
         * 1- fatalerror, 2 - error, 3 warning, 4 info, 5 debug, 6 trace
         */
        $objid = ($objid < 0) ? 0 : $objid; //защита от передачи -1 для новой записи

        self::write($sysobjid, $objid, $info, $objpos, $errlvl
            /*, $evnt_id, $evnt_code*/
            , \Auth::user()->id ?? 1);
    }

    public static function log_info_admin($sysobjid, $objid, $info, $errlvl = null, $objpos = null
        /*, $evnt_id = null, $evnt_code = null*/)
    {
        self::write($sysobjid, $objid, $info, $objpos, $errlvl, 1);
    }
}
