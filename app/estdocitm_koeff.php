<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class estdocitm_koeff extends Model
{
  protected $guarded = [];

  public function estdocitem()
  {
      //return $this->belongsTo(\App\estDoc::class);
      return $this->hasOne(estdocItem::class, 'id', 'estdocitmid');
  }

}
