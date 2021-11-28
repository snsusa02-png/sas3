<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\StringUtil;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class doctype extends Model
{
    use DeleteTrait;
    use StringUtil;

    protected $guarded = [];

    static public $prefix = 'doctypes';
    static public $sysobjid = 920;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function subtypes()
    {
        return $this->hasMany(doctype::class, 'parent_id', 'id');
    }



    static public function lstActive()
    {
        //Cache::forget(self::$prefix . '_lstTypes_' . $mchntypeid);
        $data = Cache::remember(self::$prefix . '_lstActive', now()->addMinutes(15)
            , function () {
                //отбираем только корневые типы
                $lst = self::select('id', 'name')
                    ->whereNull('parent_id')
                    ->where('active', 1);

                $lst = $lst->orderby('name')
                    ->get()
                    ->pluck('name', 'id')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    static public function lstUsedForSysObj($sysobjid)
    {
        //Cache::forget(self::$prefix . '_lstUsedForSysObj_' . $sysobjid);
        $data = Cache::remember(self::$prefix . '_lstUsedForSysObj_' . $sysobjid, now()->addMinutes(5)
            , function () use ($sysobjid) {
                $lst = self::from('doctypes as t')
                    ->select('id', 'name')
                    ->where('active', 1)
                    ->whereRaw('exists (select 1 from objfiles as f where f.doctypeid=t.id and f.sysobjid=' . $sysobjid . ')')
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

        //пользователь ДОЛЖЕН иметь доступ к категории информации, указанной в записи о типе документа
        $userid = \Auth::user()->id;
        $sc = "exists (select 1 from user_acs as uac where uac.acsid=dt.acsid and uac.userid={$userid})";


        //для оптимизации запроса некоторые параметры обрабатываются группой.
        // Чтобы избежать повторного применения, используем добавление отработанных параметров
        // в массив $used_params
        $used_params = [];

        foreach ($params as $key => $val) {

            if (isset($val) and $val !== '') {

                if (array_search($key, $used_params) == 0) {
                    $used_params[] = $key;

                    if ($key == 'active') {
                        $sc .= " and dt.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (dt.active=1 or dt.id={$val})";

                    } elseif ($key == 'is_root') {
                        $sc .= " and dt.parent_id is " . (($val == 1) ? '' : 'not') . ' null';

                    } elseif ($key == 'parent_id') {
                        $sc .= " and parent_id={$val}";

                    } elseif ($key == 'in_documents') {
                        $sc .= " and exists (select 1 from documents as d where d.doctypeid=dt.id)";

                    } elseif ($key == 'name') {
                        $words = explode(" ", $val);
                        if (count($words) > 0) {
                            $sc .= ' and (';

                            //ищем "как ввел"
                            $sc .= ' (1=1';
                            foreach ($words as $word) {
                                $sc .= " and dt.name like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            //попробуем вариант с перекодировкой - если пользователь забыл переключить клавиатуру на русский язык
                            $words = explode(" ", StringUtil::switcher_ru($val));
                            $sc .= ' or (1=1';
                            foreach ($words as $word) {
                                $sc .= " and dt.name like '%" . $word . "%'";
                            }
                            $sc .= ')';

                            $sc .= ')';
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
        //2021-06-10 SNS. универсальный конструктор массива с id, name записей
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('doctypes as dt')
                ->whereRaw($sc)
                ->select('dt.id', 'dt.name')
                ->orderBy('dt.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);

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
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'dt.*';
            //Log::info(json_encode($fields));

            $recs = self::from('doctypes as dt')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('dt.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


}
