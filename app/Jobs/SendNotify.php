<?php

namespace App\Jobs;

use Auth;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Mail;
use App\Mail\NotifyMail;
use Log;

class SendNotify implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $email;
    public $subj;
    public $msg;
    public $attach;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($email, $subj, $msg, $attachfilepath = null)
    {
        $this->email = $email;
        $this->subj = $subj;
        $this->msg = $msg;
        $this->attach = $attachfilepath;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        if (isset($this->email)) {
            $email = new NotifyMail($this->subj, $this->msg, $this->attach);
            try {
                //Mail::to($this->email)->send($email);
                Mail::to($this->email)->queue($email);
                info('JOB: SendNotify: ' . $this->email . ': ' . $this->subj);

            } catch (\Exception $e) {
                info('JOB: SendNotify: Error: ' . $e->getMessage());
            }

            //info( 'jobDone: SendNotify');
        }
    }
}
