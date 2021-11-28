<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Cache;

class meet_stage extends Model
{
    //

    static public function lstUsed_cache()
    {
        if (1 == 1) {
            return Cache::remember('meet_stages_lstUsed', now()->addMinutes(5)
                , function () {
                    $arr = self::from('meet_stages as stg')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from meetings as m where m.stageid=stg.id)')
                        ->orderby('stg.ordr')
                        ->orderby('stg.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

}
