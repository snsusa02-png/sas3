<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\snsTrait;
use App\Traits\StringUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class payrolltype extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use snsTrait;

    static public $prefix = 'payrolltypes';
    static public $sysobjid = 1221;

    protected $fillable = ["name", "descript", "active", "order", "created_by", "updated_by", "created_at", "updated_at"];

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    //protected $guarded = [];

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
        $rslt = null;
        if (isset($this->id)) {
            $rslt = $this->name;
        }
        return $rslt;
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

                    if (1==0) {
                        null;
                    } elseif ($key == 'active') {
                        $sc .= " and prt.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (prt.active=1 or prt.id={$val})";

                    } elseif ($key == 'name' or $key == 's_name') {
                        $search_flds = "prt.name";

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

                    } elseif ($key == 'in_staff') {
                        // использовалась в
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from stf_payrolltypes as spt
                                    where spt.payrolltypeid=prt.id)";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;
    }


    static public function lstFor($params)
    {
        //2023-07-19 SNS. универсальный конструктор массива с id, name организаций
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $userid = \Auth::user()->id;

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = self::from('payrolltypes as prt')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('prt.ordr', 'asc')
                ->orderBy('prt.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2023-07-29 SNS. кэшируемый результат списка

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
        //2023-07-19 SNS. универсальный конструктор коллекции из записей payrolltypes
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'o.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['o.name', 'asc']];

            $recs = self::from('payrolltypes as prt')
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
        //2023-07-19 SNS. Для автокомплита

        $result = "";
        try {

            $list = self::getFor([
                's_name' => $request->s_name,
                'active' => $request->active,
            ],
                ['prt.id', 'prt.name']);

            $result = $list;

        } catch (\Exception $e) {
            Log::error('payrolltypes::list_for_ac:' . $e->getMessage());
        }
        return response()->json($result);
    }

}
