<?php

namespace App\Services;

class ParserLogger
{
    public function __construct($file_id, $loglevel)
    {
        $this->loglevel = $loglevel;
        $this->file_id = $file_id;
    }

    public function SetLogger($log)
    {
        $this->log = $log;

    }

    public function setSysObj($sysobjid)
    {
        $this->log->setSysObj($sysobjid);
    }

    public function fatalerror($msg, $file_strno = null, $rec_id = null)
    {
        $this->log->write($this->file_id, 1, $msg, $file_strno, $rec_id);
    }

    public function error($msg, $file_strno = null, $rec_id = null)
    {
        $this->log->write($this->file_id, 2, $msg, $file_strno, $rec_id);
    }

    public function info($msg, $file_strno = null, $rec_id = null)
    {
        $this->log->write($this->file_id, 3, $msg, $file_strno, $rec_id);
    }

    public function warning($msg, $file_strno = null, $rec_id = null)
    {
        $this->log->write($this->file_id, 4, $msg, $file_strno, $rec_id);
    }

    public function debug($msg, $file_strno = null, $rec_id = null)
    {
        if ($this->loglevel < 5) return;
        $this->log->write($this->file_id, 5, $msg, $file_strno, $rec_id);
    }

    public function trace($msg, $file_strno = null, $rec_id = null)
    {
        if ($this->loglevel < 6) return;
        $this->log->write($this->file_id, 6, $msg, $file_strno, $rec_id);
    }

    public function flush()
    {
        $this->log->flush();
    }
}
