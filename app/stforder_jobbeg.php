<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;

class stforder_jobbeg extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'stforder_jobbeg';
    static public $sysobjid = 1202;

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

