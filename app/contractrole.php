<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Cache;

class contractrole extends Model
{
    //

    static public function list_for_contracttypeid($contracttypeid)
    {
        $list = [];
        if (isset($contracttypeid)) {
            $list = Cache::remember('contractroles_typeid_' . $contracttypeid, now()->addMinutes(15)
                , function () use ($contracttypeid) {
                    return contractrole::from('contractroles as cr')
                        ->where('contracttypeid', $contracttypeid)
                        ->select('id', 'name')
                        ->get()->pluck('name', 'id')->toArray();
                });
        }
        return $list;
    }
}
