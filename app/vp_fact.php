<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;

class vp_fact extends Model
{
  use DeleteTrait;

  protected $guarded = [];

  public function param()
  {
      return $this->hasOne(viewparam::class, 'id', 'paramid');
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
