<?php

namespace App\Events;

use Illuminate\Queue\SerializesModels;

class NewOutlay
{
    use SerializesModels;

    public $Outlay;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public function __construct($Outlay)
    {
        $this->Outlay = $Outlay;
    }
}
