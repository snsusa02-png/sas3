<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class orgacntperiod extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'orgacntperiods';
    static public $sysobjid = 117;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function ownorg()
    {//Плательщик
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    static public function isopen($ownorgid, $operdate)
    {
        //Признак открытости периода для организации

        if (!isset($ownorgid))
            return false;
        if (!isset($operdate))
            return false;

        return (self::where(['ownorgid' => $ownorgid, 'open' => 1])
                ->whereRaw("'{$operdate}' between begdt and enddt")
                ->count() > 0);
    }

}
