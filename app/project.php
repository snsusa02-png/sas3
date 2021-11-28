<?php

namespace App;

use App\Traits\DeleteTrait;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use DB;

class project extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    static public $prefix = 'projects';
    static public $sysobjid = 461;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function initorg()
    {
        return $this->hasOne(org::class, 'id', 'initorgid')->withDefault();
    }

    public function category()
    {
        return $this->hasOne(proj_category::class, 'id', 'categoryid')->withDefault();
    }

    public function status()
    {
        return $this->hasOne(proj_status::class, 'id', 'statusid')->withDefault();
    }

    public static function active()
    {
        return static::where('active', true)->get();
    }

    static public function getName($projectid)
    {
        return static::where("id", $projectid)->value("name");
    }

    static public function AuxInfo($project)
    {
        $info = [];
        $userid = \Auth::user()->id;

        if (isset($project->id)) {
            $projectid = $project->id;

            //--------------------------------------------------------------

            //Бюджет
            if (0 == 1) {
                //Cache::forget('proj_aux_budgetitems_.' . $projectid);
                $data = Cache::remember('proj_aux_budgetitems_.' . $projectid, now()->addMinutes(25)
                    , function () use ($projectid) {

                        $tsum = projbudgetitem::from('projbudgetitems as pbi')
                                ->where('pbi.projectid', $projectid)
                                ->where('pbi.active', 1)
                                ->selectRaw('sum(pbi.limsum) as limsum')
                                //->orderBy('pbi.ordr', 'asc')
                                ->first()->limsum ?? '';

                        $cnt = projbudgetitem::where('active', 1)
                            ->where('projectid', $projectid)
                            ->selectraw('count(*) as cnt')
                            ->first();

                        return ['sample' => $cnt ? $cnt->cnt : 0, 'reccount' => $tsum ?? 0];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "Бюджет",
                            'route' => 'proj_budgetitems.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'bg-success'
                        ]
                    );
            }
            //--------------------------------------------------------------

            //Риски
            if (1 == 1) {
                $data = Cache::remember('proj_risks_.' . $projectid, now()->addMinutes(25)
                    , function () use ($projectid) {

                        $recs = proj_risk::from('proj_risks as r')
                            ->where('r.projid', $projectid)
                            ->where('r.active', 1)
                            ->select('r.name')
                            ->orderBy('r.created_at', 'asc')
                            ->orderBy('r.id', 'asc')
                            ->skip(0)->take(4)
                            ->get();

                        $tstr = "";
                        $i = 0;
                        foreach ($recs as $rec) {
                            $i++;
                            if ($i > 1) {
                                $tstr = $tstr . ", ";
                            }
                            if ($i > 3) {
                                $tstr = $tstr . " ...";
                                break;
                            }
                            $tstr = $tstr . $rec->name;
                        }
                        $cnt = proj_risk::where('active', 1)
                            ->where('projid', $projectid)
                            ->selectraw('count(*) as cnt')
                            ->first();

                        return ['sample' => $tstr, 'reccount' => $cnt ? $cnt->cnt : 0];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "Риски",
                            'route' => 'proj_risks.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'bg-danger'
                        ]
                    );
            }

            //--------------------------------------------------------------

            //Этапы - Вехи - MileStones
            if (1 == 1) {
                $data = Cache::remember('proj_milestones_.' . $projectid, now()->addMinutes(25)
                    , function () use ($projectid) {

                        $recs = proj_milestone::
                            where('projid', $projectid)
                            ->where('active', 1)
                            ->select('name')
                            ->orderBy('created_at', 'asc')
                            ->orderBy('id', 'asc')
                            ->skip(0)->take(4)
                            ->get();

                        $tstr = "";
                        $i = 0;
                        foreach ($recs as $rec) {
                            $i++;
                            if ($i > 1) {
                                $tstr = $tstr . ", ";
                            }
                            if ($i > 3) {
                                $tstr = $tstr . " ...";
                                break;
                            }
                            $tstr = $tstr . $rec->name;
                        }
                        $cnt = proj_milestone::where('active', 1)
                            ->where('projid', $projectid)
                            ->selectraw('count(*) as cnt')
                            ->first();

                        return ['sample' => $tstr, 'reccount' => $cnt ? $cnt->cnt : 0];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "Дорожная карта",
                            'route' => 'proj_milestones.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'bg-info'
                        ]
                    );
            }
            //--------------------------------------------------------------

            //Представители клиента
            if (1 == 0) {
                $data = Cache::remember('org_aux_users_.' . $projectid, now()->addMinutes(25)
                    , function () use ($projectid) {

                        $recs = userorg::from('userorgs as uo')
                            ->join('users as u', 'u.id', 'uo.userid')
                            ->where('uo.orgid', $projectid)
                            ->where('uo.active', 1)
//                            ->where('u.active', 1)
//                            ->selectraw('concat(u.lname," ", left(u.fname,1),".", left(u.mname,1)) as name')
                            ->select('u.name')
                            ->orderBy('uo.created_at', 'asc')
                            ->orderBy('uo.id', 'asc')
                            ->skip(0)->take(4)
                            ->get();

                        $tstr = "";
                        $i = 0;
                        foreach ($recs as $rec) {
                            $i++;
                            if ($i > 1) {
                                $tstr = $tstr . ", ";
                            }
                            if ($i > 3) {
                                $tstr = $tstr . " ...";
                                break;
                            }
                            $tstr = $tstr . $rec->name;
                        }
                        $cnt = userorg::where('active', 1)
                            ->where('orgid', $projectid)
                            ->selectraw('count(*) as cnt')
                            ->first();

                        return ['sample' => $tstr, 'reccount' => $cnt ? $cnt->cnt : 0];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "Представители",
                            'route' => 'org_users.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'bg-light'
                        ]
                    );
            }
            //--------------------------------------------------------------

            //Сотрудники
            if (1 == 0) {
                $data = Cache::remember('org_aux_staff_.' . $projectid, now()->addMinutes(25)
                    , function () use ($projectid) {

                        $recs = orgstaff::where('orgid', $projectid)
                            ->where('active', 1)
                            ->selectraw('concat(lname," ", left(fname,1),".", left(mname,1), " (", IFNULL(post, "-"), ")") as name')
                            ->orderBy('created_at', 'asc')
                            ->orderBy('id', 'asc')
                            ->skip(0)->take(4)
                            ->get();

                        $tstr = "";
                        $i = 0;
                        foreach ($recs as $rec) {
                            $i++;
                            if ($i > 1) {
                                $tstr = $tstr . ", ";
                            }
                            if ($i > 3) {
                                $tstr = $tstr . " ...";
                                break;
                            }
                            $tstr = $tstr . $rec->name;
                        }
                        $cnt = orgstaff::where('active', 1)
                            ->where('orgid', $projectid)
                            ->selectraw('count(*) as cnt')
                            ->first();

                        return ['sample' => $tstr, 'reccount' => $cnt ? $cnt->cnt : 0];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "сотрудники",
                            'route' => 'org_staff.index',
                            'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'bg-light'
                        ]
                    );
            }
            //--------------------------------------------------------------


            //Группы ------------------------------------
            if (1 == 0) {
                $data = Cache::remember('proj_aux_groups_.' . $projectid, now()->addMinutes(25)
                    , function () use ($projectid) {

                        $recs = group::from('groups as g')
                            ->join('grptypes as t', 't.id', '=', 'g.grptypeid')
                            ->whereExists(function ($query) use ($projectid) {
                                $query->select(DB::raw(1))
                                    ->from('grpitems as i')
                                    ->whereraw('i.grpid = g.id')
                                    ->where('i.sysobjid', 461)
                                    ->where('i.objid', $projectid);
                            })
                            ->selectraw('concat(t.name,": <b>", g.name, "</b>") as name')
//                ->orderBy('t.ordr', 'asc')
//                ->orderBy('g.ordr', 'asc')
                            ->skip(0)->take(4)
                            ->get();

                        $tstr = "";
                        $i = 0;
                        foreach ($recs as $rec) {
                            $i++;
                            if ($i > 1) {
                                $tstr = $tstr . ", ";
                            }
                            if ($i > 3) {
                                $tstr = $tstr . " ...";
                                break;
                            }
                            $tstr = $tstr . $rec->name;
                        }
                        $cnt = grpitem::where('active', 1)
                            ->where('sysobjid', 461)
                            ->where('objid', $projectid)
                            ->selectraw('count(*) as cnt')
                            ->first();

                        return ['sample' => $tstr, 'reccount' => $cnt ? $cnt->cnt : 0];
                    });
                if (isset($data))
                    array_push($info,
                        ['name' => "группы", 'route' => 'org_groups.edit', 'sample' => $data['sample'],
                            'reccount' => $data['reccount'],
                            'btn-class' => 'btn-warning'
                        ]
                    );
            }
            // -------------------------------------------------------------------------------

            //Связи с внешними системами
            if (1 == 0) {
                if (usrsysright::isUserHasRightByCode($userid, 'objextids.read')) {
                    $recs = objextid::from('objextids as oi')
                        ->join('extsystems as es', 'es.id', 'oi.extsysid')
                        ->where('oi.objid', $projectid)
                        ->where('oi.sysobjid', 461)
                        ->selectraw('concat(es.name, ": ", oi.extid) as name')
                        ->orderBy('oi.created_at', 'asc')
                        ->skip(0)->take(4)
                        ->get();

                    $tstr = "";
                    $i = 0;
                    foreach ($recs as $rec) {
                        //dd($rec->name);
                        $i++;
                        if ($i > 1) {
                            $tstr = $tstr . ", ";
                        }
                        if ($i > 3) {
                            $tstr = $tstr . " ...";
                            break;
                        }
                        $tstr = $tstr . $rec->name;
                    }
                    $cnt = objextid::where('objid', $projectid)->where('sysobjid', 461)->count();

                    array_push($info,
                        ['name' => 'внеш. системы', 'route' => 'org_extids.index', 'sample' => $tstr, 'reccount' => $cnt]
                    );
                }
            }

        }
        return $info;
    }


    static public function lstActive()
    {
        $data = Cache::remember(self::$prefix . '_lstActive_' . 0, now()->addMinutes(15)
            , function () {
                $lst = self::select('id', 'name')
                    ->where('active', 1);

                $lst = $lst->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

}
