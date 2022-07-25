<?php

namespace App\Listeners;

use Illuminate\Auth\Events\Login;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Contracts\Queue\ShouldQueue;
use App\User;
use App\userorg;
use App\objlog;
use DB;
use Illuminate\Support\Facades\Cache;

class UserLoggedIn
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
     * @param Login $event
     * @return void
     */
    public function handle(Login $event)
    {
        info('User logged in: ' . $event->user->id . ': ' . $event->user->name);

        //20190613 osetsky. Уведомим кого-то о логине
        $simplenotify_to = env('LOGIN_NOTIFICATION');
        //$simplenotify_to = 'sns@itqua.ru';
        if ($simplenotify_to) {

            \App\Jobs\LoginNotification::dispatch($simplenotify_to, $event->user);

            //Получим список адресов кураторов, которые не прочь получить уведомление о входе пользователя в ЛК
            $curators = userorg::from('userorgs as uo')
                ->where('uo.userid', $event->user->id)
                ->join('org_curators as oc', 'oc.orgid', 'uo.orgid')
                ->where('oc.active', 1)
                ->whereraw('now() between oc.begdt and ifnull(oc.enddt,now())')
                ->join('users as u', 'u.id', 'oc.userid')
                ->where('u.active', 1)
                ->where('uo.active', 1)
                ->whereNotExists(function ($q1) {
                    $q1->select(DB::raw(1))
                        ->from('objprefs as op')
                        ->whereRaw('op.objid = u.id')
                        ->where([
                            ['op.sysobjid', '=', 3],
                            ['op.preftypeid', '=', 34],     //1-Получать уведомление о входе в ЛК курируемого пользователя
                            ['op.n_val', '=', 0],
                        ]);
                })
                ->select('u.email')
                ->distinct()->get();

            foreach ($curators as $curator) {
                info('send login notification to: ' . $curator->email);
                \App\Jobs\LoginNotification::dispatch($curator->email, $event->user);
            }

        } else {
            //
        }


        $userid = $event->user->id;
        $curorgid = $event->user->curorgid;
        //20190521 osetsky. Перепропишем поля в связи с внезапной сменой концепции
        if ($curorgid) {
            if (!userorg::userActiveOrgs($userid)
                ->where('orgid', $curorgid)->first()//проверяем связь на актуальность
            ) {
                $curorgid = null;//не актуальна
            }
        }
        if (!$curorgid) {
            $event->user->curorgid = optional(userorg::userActiveOrgs($event->user->id)->first())->orgid;
            $event->user->save();
        }

        //сохраним запись в журнал. errlvl=3 - инфо
        //1- fatalerror, 2 - error, 3 info, 4 warning, 5 debug, 6 trace'
        objlog::log_info(3, $event->user->id, ' вход в систему ('
            . env('APP_NAME') . ')', 3);

        //Очистка кэша для информера по сегодняшним посетителям
        Cache::forget('informer_today_users');
    }
}
