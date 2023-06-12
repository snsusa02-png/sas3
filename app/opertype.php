<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\Result;
use App\Traits\snsTrait;
use App\Traits\StringUtil;
use Goutte\Client;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class opertype extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use snsTrait;

    static public $prefix = 'opertypes';
    static public $sysobjid = 311;

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

    public function getInfoAttribute()
    {
        return "{$this->name}";;
    }

    public static function active()
    {
        return static::where('active', true)->get();
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
                        $sc .= " and ot.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (ot.active=1 or ot.id={$val})";

                    } elseif ($key == 'name') {
                        $search_flds = "ot.name";

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
                    } elseif ($key == 'in_machines') {
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mchn_opertypes as mot where mot.opertypeid=ot.id)";

                    } elseif ($key == 'in_mchn_raids') {
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from mchn_raids as mr where mr.opertypeid=ot.id)";

                    } elseif ($key == 'for_user') {
                        $sc .= " and (ot.need_rightid is null"
                                    . " or exists (select 1 from usrsysrights as ur"
                                                    . " where ur.userid={$val}"
                                                    . " and ur.sysfuncid = ot.need_rightid"
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

            $lst = self::from('opertypes as ot')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('ot.name', 'desc')
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

    static public function getFor($s_params, $fields = null, $sorts = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей opertypes
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'o.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['ot.name', 'asc']];

            $recs = self::from('opertypes as ot')
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


    static public function list_for_ac(Request $request)
    {
        //2021-12-04 SNS. Для автокомплита

        $result = "";
        try {

            $list = self::getFor([
                's_name' => $request->s_name,
                'active' => $request->active,
            ],
                ['ot.id', 'ot.name']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('opertype::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }

    public static function informer_opertypes_sums()
    {
        //Cache::forget('informer_opertypes_sums');
        return Cache::remember('informer_opertypes_sums', now()->addMinutes(5)
            , function () {

                $opertypes = self::getFor(['in_mchn_raids' => 1, 'active' => 1], ['ot.id', 'ot.name as opertype_name']);
                //dd($opertypes);

                foreach ($opertypes as $ot) {
                    $sc = "opertypeid={$ot->id} and wrkdate=curdate()";
                    $sums = mchn_raid::whereRaw($sc)->selectRaw("sum(load_sum) as load_sum, sum(unload_sum) as unload_sum")->first();
                    $ot->today_load_sum = $sums->load_sum??0;
                    $ot->today_unload_sum = $sums->unload_sum??0;

                    $sc = "opertypeid={$ot->id} and wrkdate=date_sub(curdate(), interval 1 day)";
                    $sums = mchn_raid::whereRaw($sc)->selectRaw("sum(load_sum) as load_sum, sum(unload_sum) as unload_sum")->first();
                    $ot->yesterday_load_sum = $sums->load_sum??0;
                    $ot->yesterday_unload_sum = $sums->unload_sum??0;

                    $sc = "opertypeid={$ot->id} and wrkdate between date_sub(curdate(), interval 7 day) and curdate()";
                    $sums = mchn_raid::whereRaw($sc)->selectRaw("sum(load_sum) as load_sum, sum(unload_sum) as unload_sum")->first();
                    $ot->d7_load_sum = $sums->load_sum??0;
                    $ot->d7_unload_sum = $sums->unload_sum??0;

                    $sc = "opertypeid={$ot->id} and wrkdate between date_sub(curdate(), interval 30 day) and curdate()";
                    $sums = mchn_raid::whereRaw($sc)->selectRaw("sum(load_sum) as load_sum, sum(unload_sum) as unload_sum")->first();
                    $ot->d30_load_sum = $sums->load_sum??0;
                    $ot->d30_unload_sum = $sums->unload_sum??0;

                }
                return $opertypes;
            }
        );
    }

}
