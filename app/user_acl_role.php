<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class user_acl_role extends Model
{
    use DeleteTrait;
    use FilesTrait;

    //protected $fillable = ["orgid"];
    protected $guarded = [];

    protected $table = 'user_acl_roles';

    static public $prefix = 'user_acl_roles';
    static public $sysobjid = 1555;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function user()
    {
        return $this->hasOne(user::class, 'id', 'userid');
    }

    public function role()
    {
        return $this->hasOne(acl_role::class, 'id', 'roleid');
    }

    //Отзыв прав пользователя по списку прав заданной роли
    public static function delRoleRights($userid, $roleid)
    {
        if (!(isset($userid) and isset($roleid)))
            return;

        $roleRights = acl_role_right::where('roleid', $roleid)
            ->where('active', 1)
            ->select('sysfuncid')
            ->get();
        //dd($roleRights);
        foreach ($roleRights as $right) {
            if (usrsysright::isUserHasRight($userid, $right->sysfuncid)) {
                usrsysright::delUsrSysRight($userid, $right->sysfuncid, null, null, \Auth::user()->id);
            }
        }
    }

    //Назначение прав пользователю по списку прав заданной роли
    public static function addRoleRights($userid, $roleid)
    {
        if (!(isset($userid) and isset($roleid)))
            return;

        $roleRights = acl_role_right::where('roleid', $roleid)
            ->where('active', 1)
            ->select('sysfuncid')
            ->get();
        //dd($roleRights);
        foreach ($roleRights as $right) {
            if (!usrsysright::isUserHasRight($userid, $right->sysfuncid)) {
                usrsysright::setUsrSysRight($userid, $right->sysfuncid, null, null, \Auth::user()->id);
            }
        }
    }
}
