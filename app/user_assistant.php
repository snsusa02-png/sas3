<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use DB;


class user_assistant extends Model
{
    //
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'user_assistants';
    static public $sysobjid = 466;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    static public function lstOwnUsers($userid)
    {
        //Cache::forget(self::$prefix . '_lstTypes_' . $mchntypeid);
        $data = Cache::remember(self::$prefix . '_lstOwnUsers_' . $userid, now()->addMinutes(5)
            , function () use ($userid) {
                $lst = User::from('users as u')
                    ->select('id', db::raw("concat(lname, ' ',fname) as name"))
                    ->where('active', 1)
                    ->whereRaw(" (u.id=" . $userid
                        . " or exists(select 1 from user_assistants as ua where ua.assistant_userid=" . $userid
                        . " and ua.userid=u.id and ua.active=1  and now() between ua.begdt and ifnull(ua.enddt,now()) ))")
                    ->orderby('lname')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function isAssistantFor($boss_userid, $assistant_userid, $for_sysobjid)
    {
        if (isset($boss_userid) and isset($assistant_userid) and isset($for_sysobjid)) {
            return (self::where('userid', $boss_userid)
                    ->where('assistant_userid', $assistant_userid)
                    ->where('active', 1)
                    ->whereraw("ifnull(for_sysobjid,$for_sysobjid)=$for_sysobjid
                and now() between (begdt) and ifnull(enddt,now())")
                    ->count() > 0);
        }
        return 0;
    }
}
