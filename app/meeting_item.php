<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class meeting_item extends Model
{
    static public $prefix = 'meeting_items';
    static public $sysobjid = 858;

    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


    public function meeting()
    {
        return $this->hasOne(meeting::class, 'id', 'protid')->withDefault();
    }

    public function exeorg()
    {
        return $this->hasOne(org::class, 'id', 'exeorgid')->withDefault();
    }

    public function inituser()
    {
        return $this->hasOne(user::class, 'id', 'inituserid')->withDefault();
    }

    public function spokestaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'spokestaffid')->withDefault();
    }

    public function exestaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'exestaffid')->withDefault();
    }

}
