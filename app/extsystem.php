<?php

namespace App;

use Cache;
use Illuminate\Database\Eloquent\Model;

use App\Traits\DeleteTrait;
use App\Traits\Result;

class extsystem extends Model
{
    use DeleteTrait;

    protected $table = 'extsystems';

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

    public static function listAll_cache($sysobjid = null)
    {
//        Cache::forget('extsystems.list.' . ($sysobjid ?? '_'));
        return Cache::remember('extsystems.list.' . ($sysobjid ?? '_'), now()->addMinutes(10)
            , function () use ($sysobjid) {

                $rslt = extsystem::from('extsystems as es')
                    ->select('es.id', 'es.name');
                if (isset($sysobjid)) {
                    $rslt = $rslt->join('extsys_sysobjs as so', 'so.extsysid', 'es.id')
                        ->where('so.sysobjid', $sysobjid);
                }
                $rslt = $rslt->get()
                    ->pluck("name", "id")
                    ->toArray();
                return $rslt;
            });
    }

}
