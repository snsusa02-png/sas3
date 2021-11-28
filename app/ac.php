<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class ac extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'acs';
    static public $sysobjid = 1501;

    //protected $fillable = ["name", "active", "created_by", "updated_by"];
    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function users()
    {
        return $this->hasMany(user_ac::class, 'acsid', 'id');
    }

    public function scopeActive($query)
    {
        return $query->where('active', 1);
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

    static public function search_cond($params)
    {

        $sc = "1=1";

        //пользователь ДОЛЖЕН иметь доступ к категории информации, для того, чтобы работать с ней --------------
        //2 варианта: - Если пользователь имеет доступ к спр-ку ACS, в режиме чтения, то считается,
        //   что он имеет доступ к любой категории информации
        //  - Либо пользователь должен входить в список user_acs для нужной категории информации
        $userid = \Auth::user()->id;
        if (!usrsysright::isUserHasRightByCode_cached($userid, 'acs.admin'))
            $sc .= " and exists (select 1 from user_acs as uac where uac.acsid=ac.id and uac.userid={$userid})";
        // -----------------------------------------------------------------------------------------------------

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'active') {
                        $sc .= " and ac.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (ac.active=1 or ac.id={$val})";

                    } elseif ($key == 'new_for_user_or_current') {
                        $t_userid = $val[0] ?? 0;
                        $t_acsid = $val[1] ?? 0;
                        $sc .= " and ac.active=1
                            and not exists (select 1 from user_acs as uac where uac.acsid=ac.id
                            and uac.userid={$t_userid} and uac.acsid<>{$t_acsid})";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-06-10 SNS. универсальный конструктор массива с id, name записей
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('acs as ac')
                ->whereRaw($sc)
                ->select('ac.id', 'ac.name')
                ->orderBy('ac.ordr', 'asc')
                ->orderBy('ac.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей orgdeps
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'uac.*';
            //Log::info(json_encode($fields));

            $recs = self::from('acs as ac')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('ac.ordr', 'asc')
                ->orderBy('ac.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

    static public function userHasAcs($userid, $acsid)
    {
        //проверка на наличие у пользователя права доступа к информациии заданной категории
        return (user_ac::where(['userid' => $userid, 'acsid' => $acsid])->count() > 0);
    }

}
