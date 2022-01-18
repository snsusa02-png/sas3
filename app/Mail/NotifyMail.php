<?php

namespace App\Mail;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Contracts\Queue\ShouldQueue;

class NotifyMail extends Mailable
{
    use Queueable, SerializesModels;

    public $msg;
    public $subj;
    public $attach;

    /**
     * Create a new message instance.
     *
     * @return void
     */
    public function __construct($subj, $msg, $attach = null)
    {
        $this->subj = $subj;
        $this->msg = $msg;
        $this->attach = $attach;
    }

    /**
     * Build the message.
     *
     * @return $this
     */
    public function build()
    {
        $subj = $this->subj;

        $msg = str_replace(chr(13) . chr(10), "<br>", $this->msg);

        $ttt = $this->subject( env('APP_NAME', 'Уведомление') .": " . $this->subj)
            ->view('mails.notify'
                , compact(['subj', 'msg']));
        if (isset($this->attach))
            $ttt = $ttt->attachFromStorage($this->attach);

        return $ttt;

    }
}
