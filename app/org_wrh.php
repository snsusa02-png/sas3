<?php

namespace Modules\Stock\Entities;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use App\org;
use App\User;
use Modules\Stock\Entities\wrh;

class org_wrh extends Model
{
    use DeleteTrait;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid');
    }
    public function wrh()
    {
        return $this->hasOne(wrh::class, 'id', 'wrhid');
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
