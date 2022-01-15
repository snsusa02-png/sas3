<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Logout;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;

use Illuminate\Support\Facades\Cache;

use App\objlog;

class UserLoggedOut
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
     * @param object $event
     * @return void
     */
    public function handle(Logout $event)
    {
        //сохраним запись в журнал. errlvl=3 - инфо
        //1- fatalerror, 2 - error, 3 info, 4 warning, 5 debug, 6 trace'
        if (isset($event->user->id)) {
            objlog::log_info(3, $event->user->id, ' выход из системы (' . env('APP_NAME') . ')', 3);

            //Забудем отметку о том что пользователь в системе
            Cache::forget('user-is-online-' . $event->user->id);
        }
    }
}
