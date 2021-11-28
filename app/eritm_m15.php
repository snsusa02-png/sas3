<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class eritm_m15 extends Model
{
    static public $prefix = 'eritm_m15s';
    static public $sysobjid = 883;

    use DeleteTrait;

    protected $guarded = [];

    public function rqst_item()
    {
        return $this->belongsTo(equiprqst_item::class, 'eritmid', 'id');
    }

    public function m15doc()
    {
        return $this->hasOne(invoice::class, 'id', 'm15docid')->withDefault();
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
