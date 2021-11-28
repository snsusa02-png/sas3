<?php

namespace App;

use App\Traits\FilesTrait;
use App\Traits\snsTrait;
use DB;
use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class buildobj_staff extends Model
{

    use DeleteTrait;
    use FilesTrait;
    use snsTrait;

    protected $guarded = [];

    static public $prefix = 'buildobj_staffs';
    static public $sysobjid = 854;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')
            ->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')
            ->withDefault();
    }

    public function tags()
    {
        return $this->hasMany(objtag::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('tag');
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->orgstaff->name . ' (' . $this->rolename . ') ' . $this->orgstaff->org->name;
            return $rslt;
        } else
            return null;
    }

    static public function lstAllForBuildObj($buildobjid)
    {
        if (isset($buildobjid)) {
            $lst = self::from('buildobj_staffs as bs')
                ->leftJoin('orgstaff as os', function ($j) {
                    $j->on('os.id', 'bs.staffid');
                })
                ->leftJoin('orgs as o', function ($j) {
                    $j->on('o.id', 'bs.orgid');
                })
                ->leftJoin('orgs as o2', function ($j) {
                    $j->on('o2.id', 'os.orgid');
                })
                ->where('buildobjid', $buildobjid)
                ->select('bs.id', 'bs.rolename', 'bs.staffid', 'bs.orgid'
                    , DB::raw('concat(os.lname," ",os.fname," ",os.mname) as staffname')
                    , 'bs.reason', 'bs.active', 'bs.ordr'
                    , 'o.name as orgname', 'o.phone as orgphone'
                    , 'os.postname', 'os.phone', 'os.email', 'os.userid'
                    , 'o2.name as stafforgname')
                //->orderby(DB::raw('ifnull(bs.ordr,99999)'))
                //->orderBy('bs.rolename')->with('orgstaff')
                ->orderBy('o.name')
                ->orderBy('bs.orgid')
                ->with('orgstaff')
                ->with('tags')
                ->get();

            return $lst;
        } else
            return null;
    }


    public static function isUserInList($buildobjid, $userid)
    {
        // true если пользователь $userid включен в список buildobj_staffs объекта $buildobjid
        if (isset($buildobjid) and isset($userid)) {

            return self::from('buildobj_staffs as bos')
                    ->join('orgstaff as os', 'os.id', 'bos.staffid')
                    ->where(['bos.buildobjid' => $buildobjid, 'os.userid' => $userid])->count() > 0;

        }
    }

    public static function isUserInAnyObjList($userid)
    {
        // true если пользователь $userid включен в список для любого строительного объекта
        if (isset($userid)) {

            return self::from('buildobj_staffs as bos')
                    ->join('orgstaff as os', 'os.id', 'bos.staffid')
                    ->where(['os.userid' => $userid])->count() > 0;

        }
    }


    static public function search_cond($params)
    {

        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, для того, чтобы работать с ней
        $userid = \Auth::user()->id;
//        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
//            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=m.acsid and uac.userid={$userid})";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'orgid') {
                        $sc .= " and os.orgid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and bos.active={$val}";
                        $sc .= " and os.active={$val}";

                    } elseif ($key == 'buildobjid') {
                        $sc .= " and bos.buildobjid={$val}";

                    } elseif ($key == 's_name') {
                        $sc .= " and concat(bos.rolename,'|',os.name) like '%{$val}%'";

                    } elseif ($key == 'in_cursias') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from cursias as crs where crs.machineid=m.id)";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;
    }

    static public function lstFor($params)
    {
        //2021-11-02 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            if (1 == 1) {
                $lst = self::from('buildobj_staffs as bos')
                    ->join('orgstaff as os', 'os.id', 'bos.staffid')
                    ->whereRaw($sc)
                    ->select('bos.id', 'bos.name')
                    ->get()
                    ->pluck('name', 'id')->toArray();
            }
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-11-02 SNS. универсальный конструктор коллекции из записей machines
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['o.name', 'asc'], ['os.name', 'asc']];

            $recs = self::from('buildobj_staffs as bos')
                ->join('orgstaff as os', 'os.id', 'bos.staffid')
                ->Join('orgs as o', 'o.id', 'os.orgid')
                ->whereRaw($sc)
                ->select($fields);

            foreach ($sorts as $sort) {
                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
            }

            $recs = $recs->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

}
