<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\snsTrait;
use App\Traits\StringUtil;
use Illuminate\Database\Eloquent\Model;

class ri_org_price extends Model
{
    use DeleteTrait;
    use FilesTrait;
    use snsTrait;

    static public $prefix = 'ri_org_prices';
    static public $sysobjid = 146;

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

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')->withDefault();
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')->withDefault();
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

                    if ($key == 'active_or_current') {
                        $sc .= " and (rop.active=1 or rop.id={$val})";

                    } elseif ($key == 'active') {
                        $sc .= " and rop.active={$val}";

                    } elseif ($key == 'on_date') {
                        $sc .= " and '{$val}' between rop.begdate and ifnull(rop.enddate,'{$val}')";

                    } elseif ($key == 'orgid') {
                        $sc .= " and rop.orgid={$val}";

                    } elseif ($key == 'name') {
                        $search_flds = "ri.name";

                        $words = explode(" ", $val);
                        if (count($words) > 0) {
                            $sc .= ' and exists (select 1 from refitems as ri where ri.id=rop.refitmid ';

                            //ищем "как ввел"
                            $sc .= ' and (1=1';
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

                            $sc .= ') )';
                        }


                    }
                }

            }
        }
        //Log::info($sc);
        //dd($sc);

        return $sc;

    }


    static public function lstFor($params)
    {
        //2021-11-08 SNS. универсальный конструктор массива с id, name организаций
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $userid = \Auth::user()->id;

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = self::from('ri_org_prices as rop')
                ->join('refitems as ri', 'ri.id', 'rop.refitmid')
                ->whereRaw($sc)
                ->select('id', 'ri.name')
                ->orderBy('ri.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-11-08 SNS. кэшируемый результат списка

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
        //2021-11-08 SNS. универсальный конструктор коллекции из записей ri_org_prices
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : ['rop.*', 'ri.name'];
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['ri.name', 'asc']];

            $recs = self::from('ri_org_prices as rop')
                ->join('refitems as ri', 'ri.id', 'rop.refitmid')
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
