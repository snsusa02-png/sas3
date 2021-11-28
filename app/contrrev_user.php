<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class contrrev_user extends Model
{
    use DeleteTrait;

    static public $prefix = 'contrrev_users';
    static public $sysobjid = 157;

    protected $guarded = [];

    public function contract_review()
    {
        return $this->hasOne(contract_review::class, 'id', 'contrrevid');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userid')->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }


}
