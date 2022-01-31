<?php

namespace App\Jobs;

use Auth;
use Config;
use Illuminate\Bus\Queueable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;

use App\Services\MainParserFile;

class ParseFile implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $user;
    public $fileid;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public $tries = 1;

    /**
     * The number of seconds the job can run before timing out.
     *
     * @var int
     */
    public $timeout = 6600;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($fileid, $user)
    {
        $this->fileid = $fileid;
        $this->user = $user;
        //dd('++++', $this->user->id);
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        info('JOB: ParseFile: user: ' . $this->user->name . ' fileID:' . $this->fileid);
        $MainParserFile = new MainParserFile(
            $this->fileid
            , Config::get('importfiles.loglevel', 4)
            , $this->user);
        $res1 = $MainParserFile->parseFile();
    }
}
