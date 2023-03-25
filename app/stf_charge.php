<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class stf_charge extends Model
{
    //
    use DeleteTrait;
    use FilesTrait;

    //protected $fillable = ["orgid"];
    protected $guarded = [];

    protected $table = 'stf_charges';

    static public $prefix = 'stf_charges';
    static public $sysobjid = 1212;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')
            ->withDefault();
    }

    public function org_charge()
    {
        return $this->hasOne(org_charge::class, 'id', 'orgchargeid')
            ->withDefault()
            ->with('chargetype');
    }

    public function features()
    {
        return $this->hasMany(obj_feature::class, 'objid', 'id')
            ->where('obj_features.sysobjid', self::$sysobjid);
    }
}
