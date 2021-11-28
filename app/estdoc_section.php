<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class estdoc_section extends Model
{
    protected $guarded=[];

    public function estdoc()
    {
        return $this->hasOne(estdoc::class, 'id', 'estdocid');
    }
    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')
            ->withDefault();
    }

}
