<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class wrh_box extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'wrh_boxes';
    static public $sysobjid = 210;

    protected $guarded = [];

    public function wrh()
    {
        return $this->hasOne(wrh::class, 'id', 'wrhid');
    }

//    public function items()
//    {
//        return $this->hasMany(ri_wrhbox::class, 'boxid', 'id');
//    }

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
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')->withDefault();
    }

    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }


    static public function search_cond($params)
    {

        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, для того, чтобы работать с ней
        $userid = \Auth::user()->id;
//        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
//            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=wb.acsid and uac.userid={$userid})";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'wrhid') {
                        $sc .= " and wb.wrhid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (active=1 or wb.id={$val})";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }


    static public function lstFor($params)
    {
        //2021-02-18 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"


        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = wrh_box::from('wrh_boxes as wb')
                ->whereRaw($sc)
                ->select('id', db::raw("concat(name,' / ',descript) as name") )
                ->orderBy('name')
                ->get()->pluck('name', 'id')->toArray();
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


}
