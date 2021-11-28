<?php

namespace App;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
/*use Illuminate\Support\Facades\Session;*/
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class SessionModel extends Model
{
    //

    protected $table='sessions';
    public $timestamps = false;
    public $incrementing = false;

    /**
     * Returns the user that belongs to this entry.

     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

}
