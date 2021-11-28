<?php

namespace Modules\Stock\Entities;

use Illuminate\Database\Eloquent\Model;

class ri_wrhbox extends Model
{
    protected $guarded = [];

    public function box()
    {
        return $this->hasOne(wrh_box::class, 'id', 'boxid')
            ->withDefault();
    }

    public function wrh()
    {
        return $this->hasOne(wrh::class, 'id', 'wrhid')
            ->withDefault();
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
