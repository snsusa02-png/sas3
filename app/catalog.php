<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;

class catalog extends Model
{
    use DeleteTrait;

    protected $fillable = ["contractid", "vesselid", "rootitmtypeid",
        "created_at", "updated_at", "created_by", "updated_by",];

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function vessel()
    {
        return $this->hasOne(vessel::class, 'id', 'vesselid')->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(user::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(user::class, 'id', 'updated_by')->withDefault();
    }

}
