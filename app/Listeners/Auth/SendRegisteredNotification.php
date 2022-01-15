<?php

namespace App\Listeners\Auth;

use App\Events\Auth\UserRegistered;
use App\usrsysright;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use App\Mail\Auth\RegistrationEMail;
use Illuminate\Support\Facades\Mail;

class SendRegisteredNotification
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
     * @param  UserRegistered  $event
     * @return void
     */
    public function handle(UserRegistered $event)
    {
        //Получим список адресов пользователей с правом привязки пользователя к представляемой организации
        $rcpts = usrsysright::from('usrsysrights as ur')
            ->join('users as u','u.id','ur.userid')
            ->where("u.active", 1)
            ->where("ur.sysfuncid", 216)  //право: userorgs.update
            ->where("ur.active", 1)
            ->whereRaw('now() between ur.begdt and ifnull(ur.enddt,now())')
            ->select('u.email')
            ->distinct()->get();

        foreach ($rcpts as $rcpt) {
            info('send user-registered ({$event->user->email}) notification to: ' . $rcpt->email);
            Mail::to($rcpt->email)->send(new RegistrationEMail($event->user));
        }

        //Mail::to($event->user->email)->send(new RegistrationEMail($event->user));
    }
}
