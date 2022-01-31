<?php

namespace App\Jobs;

use Auth;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use Mail;
use App\Mail\Loaded1CDocsMail;

class Send1CDocsLoaded implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $msg;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($user, $msg)
    {
        $this->user = $user;
        $this->msg = $msg;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        $to = $this->user->email;
        $email = new Loaded1CDocsMail($this->msg);
        Mail::to($to)->send($email);

        //info( 'Send1CDocsLoaded: '. $to . ': '. $this->msg);
        info( 'jobDone: Send1CDocsLoaded');
    }
}
