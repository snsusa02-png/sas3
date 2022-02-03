<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class mchn_raid extends Model
{
    static public $prefix = 'mchn_raids';
    static public $sysobjid = 1106;

    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function dispatcher()
    {
        //return $this->hasOne(User::class, 'id', 'disp_userid')->withDefault();
        return $this->hasOne(orgstaff::class, 'id', 'disp_staffid')->withDefault();
    }

    public function machine()
    {
        return $this->hasOne(machine::class, 'id', 'machineid')->withDefault();
    }

    public function suporg()
    {
        return $this->hasOne(org::class, 'id', 'suporgid')->withDefault();
    }

    public function driver_work()
    {
        return $this->hasOne(driver_work::class, 'id', 'dw_id')->withDefault();
    }

    public function mr_opers()
    {
        return $this->hasMany(mr_oper::class, 'id', 'mr_id');
    }

    public function opertype()
    {
        return $this->hasOne(opertype::class, 'id', 'opertypeid')->withDefault();
    }

    static public function paytypes()
    {
        return [1 => 'нал', 2 => 'б/н'];
    }

    public function mchn_opertype()
    {
        return $this->hasOne(mchn_opertype::class, 'id', 'mot_id')->withDefault();
    }

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();
    }

    public function driver()
    {
        return $this->hasOne(orgstaff::class, 'id', 'driverid')->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')->withDefault();
    }

    public function load_ownorg()
    {
        return $this->hasOne(org::class, 'id', 'load_ownorgid')->withDefault();
    }

    public function unload_ownorg()
    {
        return $this->hasOne(org::class, 'id', 'unload_ownorgid')->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function load_place()
    {
        return $this->hasOne(org_place::class, 'id', 'load_placeid')->withDefault();
    }

    public function load_refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'load_refitmid')->withDefault();
    }

    public function unload_refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'unload_refitmid')->withDefault();
    }

    public function contract()
    {
        return $this->hasOne(contract::class, 'id', 'contractid')->withDefault();
    }

    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            //$rslt = $this->orgstaff->name . ' (' . $this->rolename . ') ' . $this->orgstaff->org->name;
            $rslt = $this->wrkdate . ' ' . $this->drivername;
            return $rslt;
        } else
            return null;
    }

    static public function isLocked($id)
    {
        //Попадает ли нужная запись в заблокированный период?

        $lock_before = sysobj_lockdate::where('sysobjid', self::$sysobjid)->select('lock_before')->first()->lock_before ?? null;
        if (isset($lock_before)) {
            $rec = self::find($id);
            if (isset($rec)) {
                return ($rec->wrkdate < $lock_before);
            }
        }
        return false;
    }


    public static function min_wrkdate()
    {
        //определим минимально-допустимую дату для поля wrkdate
        $min_date = sysobj_lockdate::where('sysobjid', self::$sysobjid)->first()->lock_before ?? null;
        if (isset($min_date)) {
            return $min_date;
        }
        return null;
    }

    public static function years()
    {
        return Cache::remember('mchn_raids.years', now()->addMinutes(15)
            , function () {
                return self::selectraw('year(wrkdate) as yr')->distinct()
                    ->orderby('yr', 'desc')->get()
                    ->pluck('yr', 'yr')->toArray();
            });
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

                    if ($key == 'machineid') {
                        $sc .= " and mr.machineid={$val}";

                    } elseif ($key == 'driverid') {
                        $sc .= " and mr.driverid={$val}";

                    } elseif ($key == 'wrkdate') {
                        $sc .= " and mr.wrkdate='{$val}'";

                    } elseif ($key == 'active') {
                        $sc .= " and mr.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (mr.active=1 or mr.id={$val})";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name ключевых работ для вида работ строит. объекта
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('mchn_raids as mr')
                ->join('machines as m', 'm.id', 'mr.machineid')
                ->whereRaw($sc)
                ->select('mr.id', 'mr.name')
                ->orderBy('mr.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;

    }

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей mchn_opertypes
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'mot.*';
            //Log::info(json_encode($fields));

            $recs = self::from('mchn_raids as mr')
                ->whereRaw($sc)
                ->select($fields);

            $sorts = $sorts ?? [['mr.wrkdate', 'asc']];   //по умолчанию
            foreach ($sorts as $sort) {
                $recs = $recs->orderBy($sort[0], $sort[1] ?? 'asc');
            }

            $recs = $recs->get();

            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }

    static public function rfr_finopers($id)
    {

        foreach (mr_oper::where('mr_id', $id)->get() as $oper) {
            mr_oper::rfr_finopers($oper);
        }
    }

}
