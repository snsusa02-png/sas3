<?php

namespace App\Jobs;

use App\Mail\SimpleMail;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Support\Facades\Mail;

use Illuminate\Http\Request;

class LoginNotification implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    protected $loggeduser;
    protected $addrto;

    public function __construct($to, $loggeduser)
    {
        //
        $this->addrto = $to;
        $this->loggeduser = $loggeduser;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        //Mail::to($this->addrto)->send(new SimpleMail( $this->loggeduser));

        try {

            $msgBody = now() . ': ' . $this->loggeduser->name . ' (' . $this->loggeduser->email
                . ') зашел в систему (' . env('APP_NAME') . ')';

            $m = new SimpleMail($msgBody);
            $m->subject('Пользователь ' . $this->loggeduser->name . ' вошел в систему "' . env('APP_NAME') . '"');

            Mail::to($this->addrto)->send($m);
        } catch (\Exception $e) {
            \Log::debug($e->getMessage());
            //return $e->getMessage();
            return null;
        }


    }
}
