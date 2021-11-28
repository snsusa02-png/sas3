<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class stforder_vacation extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'stforder_vacation';
    static public $sysobjid = 1203;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function stforder()
    {
        return $this->hasOne(stforder::class, 'id', 'id');
    }

}
