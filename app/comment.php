<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Post;

class comment extends Model
{
//comments table in database
    protected $guarded = [];
    // user who has commented

    public function author()
    {
        return $this->belongsTo('App\User', 'from_user');
    }

    // returns post of any comment
    public function post()
    {
        return $this->belongsTo('Post', 'on_post');
    }
}
