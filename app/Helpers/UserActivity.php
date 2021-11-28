<?php
/**
 * Created by PhpStorm.
 * User: osetsky
 * Date: 06.04.2019
 * Time: 16:59
 */


namespace App\Helpers;

use App\mchnrqst;
use App\order as OrdersModel;
use App\orgstaff;
use App\refitem;
use App\SessionModel as UserActivityModel;
use App\User;
use App\usrsysright;
use Illuminate\Support\Facades\Auth;
use Request;


class UserActivity
{
    public static function LastVisitedBefore4UID($dayinterval, $uid)
    {
        return UserActivityModel::where('user_id', '=', $uid)
                ->where('last_activity', '<', today()->subDays($dayinterval)->getTimestamp())
                ->max('last_activity') ?? today()->getTimestamp();
    }

    public static function LastVisitedBefore($dayinterval)
    {
        return static::LastVisitedBefore4UID($dayinterval, optional(Auth::user())->id);
    }

    public static function cntAllGuests()
    {
        return UserActivityModel::whereNull('user_id')->count();
    }

    public static function cntAllUser()
    {
        return UserActivityModel::whereNotNull('user_id')
            ->distinct('user_id')
            ->count('user_id');
    }

    public static function cntAllVisitorsToday()
    {
        return UserActivityModel::whereNotNull('user_id')
                ->where('last_activity', '>', today()->getTimestamp())
                ->distinct('user_id')
                ->count('user_id') +
            UserActivityModel::whereNull('user_id')
                ->where('last_activity', '>', today()->getTimestamp())
                ->count();
    }

    public static function cntManagerOnline()
    {
        return UserActivityModel::whereNotNull('user_id')
            ->join('users', 'users.id', '=', 'user_id')
            ->join('usrsysrights', 'users.id', '=', 'usrsysrights.userid')
//            ->wherein('usrsysrights.sysfuncid', [4,9])
            ->where('usrsysrights.sysfuncid', 4)
            ->where('usrsysrights.active', 1)
            ->whereRaw('usrsysrights.begdt <= now()')
            ->where('last_activity', '>', now()->subMinutes(10)->getTimestamp())
            ->distinct('user_id')
            ->count('user_id');
    }

    public static function cntCustomerOnline()
    {
        //todo: скорее всего запрос неправильный, так как основан на (not sysfuncid=1)
        // а это право Admin - которого нет у менеджеров организации-продавца
        // нужно переписать на users.curorgid и то что у этой организации нет флага-признака организации-продавца

        return UserActivityModel::whereNotNull('user_id')
            ->join('users', 'users.id', '=', 'user_id')
            ->whereNotExists(function ($query) {
                $query->selectRaw(1)
                    ->from('usrsysrights as sr')
                    ->where('sr.sysfuncid', 1)
                    ->where('sr.active', 1)
                    ->where('sr.begdt', '<=', now())
                    ->whereColumn('sr.userid', 'users.id');
            })
            ->where('last_activity', '>', now()->subMinutes(10)->getTimestamp())
            ->distinct('user_id')
            ->count('user_id');

    }

    public static function cntOrdersToday()
    {
//        return OrdersModel::where('updated_at', '>', today()->getTimestamp())->count();
        //предыдущий вариант работает неверно
        return OrdersModel::where('created_at', '>=', today())->count();
    }

    public static function cntCatalogItms()
    {
        return refitem::where('active', 1)
            ->whereraw('ifnull(saleenddate, curdate()) >=curdate()')
            ->count();
    }

    public static function cntActiveMchnRqsts()
    {
        //Число заявок на спецтехнику ожидающих согласования
        $userid = \Auth::user()->id ?? null;
        if (isset($userid)) {
            $userOrgid = \Auth::user()->curorgid;
            $isOurStaff = User::isWorkInOwnOrg($userid);

            if (usrsysright::isUserHasRightByCode($userid, 'mchnrqsts.approve')) {
                //Пользователь имеет право на согласование заявок - покажем ему все несогласованные заявки
                return mchnrqst::where('active', 1)->wherenull('decision')->count();
            } else {
                //Подсчитываем число заявок, ожидающих согласования только для  компании пользователя
                return mchnrqst::where('active', 1)->wherenull('decision')->where('orgid', $userOrgid)->count();
            }
        }
        return null;
    }


    public static function cntNewUsers()
    {
        //Подсчитывает кол-во новых пользователей, не привязанных к организации
        $userid = \Auth::user()->id;
        if (1 == 0 or usrsysright::isUserHasRightByCode($userid, 'users.update')) {
            return user::whereNull('curorgid')->where('id', '!=', 1)->count();
        }
        return null;
    }
}
