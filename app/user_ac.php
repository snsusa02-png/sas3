<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class user_ac extends Model
{
    use DeleteTrait;

    static public $prefix = 'user_acs';
    static public $sysobjid = 1502;

    //protected $fillable = ["name", "active", "created_by", "updated_by"];
    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function user()
    {
        return $this->hasOne(user::class, 'id', 'userid');
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

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'userid') {
                        $sc .= " and uac.userid={$val}";

                    } elseif ($key == 'acsid') {
                        $sc .= " and uac.acsid={$val}";

                    } elseif ($key == 'active') {
                        $sc .= " and uac.active={$val}";

                    } elseif ($key == 'user_active') {
                        $sc .= " and u.active={$val}";

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

            $lst = self::from('user_acs as uac')
                ->join('acs', 'acs.id', 'uac.acsid')
                ->join('users as u', 'u.id', 'uac.userid')
                ->whereRaw($sc)
                ->select('uac.id', 'acs.name')
                ->orderBy('u.name', 'asc')
                ->orderBy('u.id', 'asc')
                ->orderBy('acs.ordr', 'asc')
                ->orderBy('acs.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей orgdeps
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'uac.*';
            //Log::info(json_encode($fields));

            $recs = self::from('user_acs as uac')
                ->join('acs', 'acs.id', 'uac.acsid')
                ->join('users as u', 'u.id', 'uac.userid')
                ->whereRaw($sc)
                ->select($fields);

            $sorts = $sorts ?? [['u.name', 'asc']];   //по умолчанию
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
