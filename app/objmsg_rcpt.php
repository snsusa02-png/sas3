<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class objmsg_rcpt extends Model
{
    protected $guarded = [];

    public function user()
    {
        return $this->belongsTo(User::class, 'rcptuserid');
    }

}
