<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class mol_stock extends Model
{
    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function mol()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid');
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid');
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid');
    }

    static public function recalc_stock()
    {
        DB::unprepared('CALL recalc_stock()');
        objlog::write(206, 0, 'Произведен пересчет товарных запасов на складах', null, 3, 1);
    }

}
