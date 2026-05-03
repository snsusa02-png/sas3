<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\StringUtil;
use App\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use DB;

class mchntype extends Model
{
    //
    static public $prefix = 'mchntype';
    static public $sysobjid = 481;

    use DeleteTrait;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function flags()
    {
        return $this->hasMany(objflag::class, 'objid', 'id')
            ->join('flagtypes as ft', 'ft.id', '=', 'objflags.flagtypeid')
            ->where('objflags.sysobjid', self::$sysobjid)
            ->select('objflags.*', 'ft.name as flagtype_name');
    }

    static public function lstTypes()
    {
        //Cache::forget(self::$prefix . '_lstTypes');
        $data = Cache::remember(self::$prefix . '_lstTypes', now()->addMinutes(25)
            , function () {
                $lst = self::select('id', 'name')->where('active', 1)
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function usedTypes()
    {
        //Cache::forget(self::$prefix . '_usedTypes');
        $data = Cache::remember(self::$prefix . '_usedTypes', now()->addMinutes(8)
            , function () {
                $lst = self::from('mchntypes as t')
                    ->select('id', 'name')
                    ->where('active', 1)
                    ->whereRaw('exists (select 1 from machines as m where m.mchntypeid=t.id)')
                    ->orderBy('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function search_cond($params)
    {
        $userid = \Auth::user()->id;

        $sc = "1=1";

        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];
        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'active') {
                        $sc .= " and mt.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (mt.active=1 or mt.id={$val})";

                    } elseif ($key == 'name') {
                        $search_flds = "mt.name";

                        $words = explode(" ", $val);
                        if (count($words) > 0) {
                            $sc .= ' and (';

                            //ищем "как ввел"
                            $sc .= ' (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            //попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
                            $words = explode(" ", StringUtil::switcher_ru($val));
                            $sc .= ' or (1=1';
                            foreach ($words as $word) {
                                $sc .= " and {$search_flds} like '%" . $word . "%'";
                            }
                            $sc .= ')';
                            $sc .= ')';
                        }
                    } elseif ($key == 'in_fuelcard_pays') {
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from fuelcard_pays as fcp
                             join machines m on m.id=fcp.machineid and m.mchntypeid=mt.id)";

                    } elseif ($key == 'mchntype_in_machines_with_mileage') {
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists(select 1 from machines m where m.mchntypeid=mt.id
					            and exists(select 1 from driver_works dw where dw.machineid=m.id and dw.meter_qty is not null))";

                    } elseif ($key == 'for_user') {
                        $sc .= " and (mt.need_rightid is null"
                            . " or exists (select 1 from usrsysrights as ur"
                            . " where ur.userid={$val}"
                            . " and ur.sysfuncid = mt.need_rightid"
                            . " and ur.active=1 and ur.enddt is null)"
                            .")";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }


    static public function lstFor($params)
    {
        //2021-02-25 SNS. универсальный конструктор массива с id, name opertypes
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $userid = \Auth::user()->id;

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = self::from('mchntypes as mt')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('mt.name', 'desc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-12-04 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return Cache::remember('lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
        } else
            return null;
    }
}
