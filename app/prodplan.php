<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class prodplan extends Model
{
    static public $prefix = 'prodplans';

    use DeleteTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function items()
    {
        return $this->hasMany(prodplan_item::class, 'docid', 'id')
            ->orderBy('plnbegdt');
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')->withDefault();
    }

    public function project()
    {
        return $this->hasOne(project::class, 'id', 'projid')->withDefault();
    }
    public function parent()
    {
        return $this->hasOne(prodplan::class, 'id', 'parid')->withDefault();
    }

    public static function breadcrumbs($project, $parplanid)
    {
        $class_active = "active";
        $bc = "";
        if (!isset($parplanid)) {
            //находимся в корне дерева планов работ
            $parplanid = "0";
            $class_active = "";
            $bc = '<a class="breadcrumb-item ' . $class_active . '" href="'.route('projects.edit',$project->id).'">' . $project->name
                . ' </a >';
        } else {
            $class_active = "active";
            $parid = $parplanid;
            do {
                $parent = prodplan::select('id', 'parid as parent_id', 'name')->find($parid);

                if (isset($parent))
                    $bc = '<a class="breadcrumb-item ' . $class_active . '" href="/prodplans/' . $project->id . '/' . $parent->id . '">'
                        . $parent->name . '</a>' . $bc;

                if (isset($parent->parent_id))
                    $parid = $parent->parent_id;
                $class_active = "";
            } while (isset($parent->parent_id));

            $bc = '<a class="breadcrumb-item ' . $class_active . '" href="/prodplans/' . $project->id . '/0">' . $project->name
                . ' </a > ' . $bc;
        }
        // -------------------------------------------------------------------------
        return $bc;
    }

    static public function lstAllForBuildObj($buildobjid)
    {
        if (isset($buildobjid)) {
            $lst = self::from('prodplans as pp')
//                ->leftJoin('orgs as o', function ($j) {
//                    $j->on('o.id', 'os.orgid');
//                })
                ->where('buildobjid', $buildobjid)
                ->select('pp.id', 'pp.name', 'pp.docnum', 'pp.docdate', 'pp.active'
//                    , 'o.name as orgname'
                    )
                ->orderBy('pp.name')
                ->get();

            return $lst;
        } else
            return null;
    }

}
