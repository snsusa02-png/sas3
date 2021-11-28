<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class obj_comment extends Model
{
    protected $guarded = [];

    public function author()
    {
        return $this->belongsTo('App\User', 'from_userid');
    }

    // returns post of any comment
    public function post()
    {
        return $this->belongsTo('Post', 'objid')->where('sysobjid', 895);
    }

}
