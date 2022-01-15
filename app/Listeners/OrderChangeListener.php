<?php

namespace App\Listeners;

use App\Events\OrderChangeEvent;
use Log;
//use App\Handlers\OrderMailQueueHandler;
//use Illuminate\Queue\InteractsWithQueue;
//use Illuminate\Contracts\Queue\ShouldQueue;

class OrderChangeListener
{
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
     * @param  OrderChangeEvent  $event
     * @return void
     */
    public function handle(OrderChangeEvent $event)
    {
        try{
            if ($event instanceof OrderChangeEvent)
            {
                //  \App\Jobs\OrderMessages::dispatch($event->order);
                info('OrderChangeListener catch OrderChangeEvent '.$event->eventcode);
               // info('OrderChangeListener catch Order '.$event->order);
            }
        }
        catch(\Exception $e){
            Log::error("Ошибка обрабоки события для $event->order->id".
                            $e->getMessage());
        }
    }
}
