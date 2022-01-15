<?php

namespace App\Listeners;

use App\Events\docs1CLoadedEvent;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\Jobs\Send1CDocsLoaded;


class docs1CLoadedListener implements ShouldQueue
{
    public $queue = 'high';

    /**
     * Create the event listener.
     *
     * @return void
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     *
     * @param  docs1CLoadedEvent  $event
     * @return void
     */
    public function handle(docs1CLoadedEvent $event)
    {
        //info( 'EVENT: docs1CLoadedEvent '. $event->user->email . ': '. $event->msg);
        dispatch((new Send1CDocsLoaded($event->user, $event->msg))->onQueue('high'));
    }
}
