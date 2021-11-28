<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class qcheck extends Model
{
    static public $prefix = 'qchecks';
    static public $sysobjid = 862;

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

    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'inituserid')
            ->withDefault();
    }


    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')->withDefault();
    }
}
