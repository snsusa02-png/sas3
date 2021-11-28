<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;
use Cache;

class wrkplan extends Model
{
    static public $prefix = 'wrkplans';
    static public $sysobjid = 867;

    use DeleteTrait;

    protected $guarded = [];

    static public $stages = [
        1 => 'черновик',
        2 => 'ожидание выполнения',
        3 => 'отчет о выполнении',
        4 => 'анализ выполнения',
        5 => 'отработан',
    ];

    static public $stage_css = [
        1 => 'background-color: silver;',
        2 => 'background-color: lightyellow;',
        3 => 'background-color: snow;',
        4 => 'background-color: salmon;',
        5 => 'background-color: #597cb0;',
    ];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }


    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'inituserid');
    }


    static public function lstUsedOrgs_cache()
    {
        if (1 == 1) {
            return Cache::remember('wrkplans_lstUsedOrgs', now()->addMinutes(5)
                , function () {
                    $arr = org::from('orgs as o')
                        ->select('id', 'name')
                        ->whereraw('exists (select 1 from wrkplans as wp where wp.orgid=o.id)')
                        ->orderby('o.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

    static public function lstUsedStages_cache()
    {
        if (1 == 1) {
            return Cache::remember('wrkplans_stages_lstUsed', now()->addMinutes(5)
                , function () {
                    $arr = self::from('wrkplans as wp')
                        ->selectraw("distinct stageid as id, id as  name")
                        ->orderby('wp.stageid')
                        ->get()->pluck('name', 'id')->toArray();
                    foreach ($arr as $key => $val) {
                        $arr[$key] = self::$stages[$key];
                    }
                    return $arr;
                });
        }
    }

}
