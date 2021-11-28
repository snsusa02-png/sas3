<?php

namespace App\Traits;

use App\obj_staff;
use \Illuminate\Support\Facades\DB;

//Трайт для
trait StaffsTrait
{

    public function staffs()
    {
        return $this->hasMany(obj_staff::class, 'objid', 'id')
            ->Join('orgstaff as os', 'os.id', 'obj_staffs.staffid')
            ->Join('orgs as o', 'o.id', 'os.orgid')
            ->leftJoin('orgposts as op', 'op.id', 'os.postid')
            ->leftJoin('roletypes as rt', 'rt.id', 'obj_staffs.roletypeid')
            ->where('sysobjid', self::$sysobjid)
            ->select('obj_staffs.*'
                , 'o.name as org_name'
                , DB::raw("ifnull(op.name, os.postname) as post_name")
                , 'rt.name as roletype_name')
            ->orderby('staffname');
    }

}
