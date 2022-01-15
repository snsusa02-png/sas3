<?php

namespace App\Events;

use App\User;
use Illuminate\Broadcasting\Channel;
use Illuminate\Queue\SerializesModels;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Broadcasting\PresenceChannel;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;

class notifyEvent
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * Create a new event instance.
     *
     * @return void
     */
    public $eventtypeid;
    public $src_sysobjid;
    public $src_objid;
    public $inituserid;
    public $subj;
    public $msg;

    public function __construct($eventtypeid, $src_sysobjid, $src_objid, $inituserid
        , string $subj = null, string $msg = null)
    {
        $this->eventtypeid = $eventtypeid;
        $this->src_sysobjid = $src_sysobjid;
        $this->src_objid = $src_objid;
        $this->inituserid = $inituserid;
        $this->subj = $subj;
        $this->msg = $msg;
        //dd($this);
    }

    /**
     * Get the channels the event should broadcast on.
     *
     * @return \Illuminate\Broadcasting\Channel|array
     */
    public function broadcastOn()
    {
        return new PrivateChannel('channel-name');
    }
}
