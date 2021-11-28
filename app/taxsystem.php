<?php

namespace App;

use App\Traits\DeleteTrait;
use Cache;
use Illuminate\Database\Eloquent\Model;

class taxsystem extends Model
{
    use DeleteTrait;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    //

    static public function activeTaxSystems()
    {
        //Действительные на текущий момент системы налогового учета
        $recs = Cache::remember('activeTaxSystems.', now()->addMinutes(25)
            , function () {
                return taxsystem::select('id', 'code')
//                    ->where('active', 1)
                    ->orderby('code')
                    ->get()
                    ->pluck("code", "id")->toArray();
            });

        return $recs;
    }
}
