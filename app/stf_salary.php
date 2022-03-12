<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class stf_salary extends Model
{
    use DeleteTrait;

    static public $prefix = 'stf_salary';
    static public $sysobjid = 1208;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')
            ->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')
            ->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid');
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lockdate = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lockdate)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->wrkbegdate < $lockdate);
            }
        }
        return false;
    }

}
