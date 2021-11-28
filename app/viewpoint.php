<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use DB;
use Cache;

class viewpoint extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'viewpoints';
    static public $sysobjid = 1111;


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
        return $this->hasOne(buildobj::class, 'id', 'buildobjid');
    }

    public function photos()
    {
        return $this->hasMany(vp_photo::class, 'viewpointid', 'id')->orderBy('infodt', 'desc');
    }


    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('firstread_at');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    static public function lstAllForBuildObj($buildobjid)
    {
        if (isset($buildobjid)) {
            $lst = self::from('viewpoints as vp')
                ->where('vp.buildobjid', $buildobjid)
                ->select('vp.id', 'vp.name', 'vp.active'
                    , db::raw("(select count(*) from vp_photos as p where p.viewpointid=vp.id) as photo_cnt")
                )
                ->orderBy('vp.name', 'asc')
                ->get();

            return $lst;
        } else
            return null;
    }


    static public function lstFor($params)
    {
        //2021-04-08 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = "1=1";

            foreach ($params as $key => $val) {

                if (isset($val) and $val !== '') {

                    if (array_search($key, $used_params) == 0) {
                        $used_params[] = $key;

                        if ($key == 'buildobjid') {
                            $sc .= " and vp.buildobjid={$val}";
                        }
                    }

                }
            }

            $lst = self::from('viewpoints as vp')
                ->whereRaw($sc)
                ->select('id', 'name')
                //->orderByRaw("ifnull(ordr,99999) asc")
                //->orderBy('name')
                ->get()->pluck('name', 'id')->toArray();
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }
}
