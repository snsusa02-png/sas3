<?php
namespace App\Services;

use DB;

class impFileLogger
{
    private $sysobjid = null;

    public function setSysObj($sysobjid)
    {
        $this->sysobjid = $sysobjid;
    }

    public function write($file_id, $errorlvl, $msg, $file_strno, $ref_id)
    {
        $sysobjid = null;
        if (isset($ref_id)) {
            $sysobjid = $this->sysobjid;
        }
        DB::table('importfilelogs')->insert([
            'write_at' => now(),
            'importfile_id' => $file_id,
            'errlvl' => $errorlvl,
            'file_strpos' => $file_strno,
            'sysobjid' => $sysobjid,
            'ref_id' => $ref_id,
            'info' => mb_substr($msg, 0, 255)
        ]);
    }

    public function flush()
    {

    }

}


?>
