<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;


class grptype extends Model
{
    use DeleteTrait;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    //связь с использованием в группах
    public function groups()
    {
        return $this->hasMany(group::class, 'grptypreid', 'id');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')
            ->withDefault();
    }
    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')
            ->withDefault();
    }
}
