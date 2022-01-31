<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Services\Parsers\org1CInterface;
use Auth;
use App\User;

class Parse1COrgs implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $userid;
    public $filename;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 180;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($filename)
    {
        //todo:: разобраться с передачей $userid
        $this->user = Auth::user();
        $this->user = User::find(3);

        $this->userid = 0;

        $this->filename = $filename;
    }


    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        info('JOB: Parse1COrgs: for user: ' . $this->userid . ' file:' . $this->filename);
//        $res = org1CInterface::load_1sorg_xml_job($this->filename, $this->user);
        $res = org1CInterface::load_1sorg_xml_job($this->filename, $this->userid);

    }
}
