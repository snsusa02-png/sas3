<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class roletype extends Model
{

    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'roletypes';
    static public $sysobjid = 1151;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }


    static public function statuses()
    {
        return [
            0 => 'черновик',
            2 => 'проект',
            4 => 'подписан (действует)',
            6 => 'отменен',
            8 => 'завершен (исполнен)',
            10 => 'расторгнут',
        ];
    }

    public function linked_objs()
    {
        return $this->hasMany(obj_link::class, 'objid', 'id')
            ->where([
                'sysobjid' => self::$sysobjid,
            ]);
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('mustread', 'desc')
            ->orderby('firstread_at');
    }

    public function real_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at');
    }

    public function tags()
    {
        return $this->hasMany(objtag::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('tag');
    }

    public function comments()
    {
        return $this->hasMany(obj_comment::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }


    public function getInfoAttribute()
    {
        if (isset($this->id)) {
            $rslt = $this->name . ' (' . $this->descript . ')';
            return $rslt;
        } else
            return null;
    }


    static public function usedTags()
    {
        $cache_key = self::$prefix . '_usedTypes';
        Cache::forget($cache_key);
        $data = Cache::remember($cache_key, now()->addMinutes(8)
            , function () {
                $lst = objtag::from('objtags as t')
                    ->select('tag as tid', 'tag as tname')
                    ->where('sysobjid', self::$sysobjid)
                    ->orderBy('tag')
                    ->get()
                    ->pluck('tname', 'tid')->toArray();

                return $lst;
            }
        );
        return $data;
    }

    //
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

                    if ($key == 'sysobjid') {
                        $sc .= " and exists(select 1 from sysobj_roletypes as srt
                                    where srt.roletypeid=rt.id and srt.active=1 and srt.sysobjid={$val})";
//                        $sc .= " and srt.sysobjid={$val}";

                    } elseif ($key == 'objid') {
                        $sc .= " and exists(select 1 from sysobj_roletypes as srt
                                    where srt.roletypeid=rt.id and srt.active=1 and srt.objid={$val})";

                    } elseif ($key == 'tgt_sysobjid') {
                        $sc .= " and exists(select 1 from sysobj_roletypes as srt
                                    where srt.roletypeid=rt.id and srt.active=1 and srt.tgt_sysobjid={$val})";

                    } elseif ($key == 'active') {
                        $sc .= " and rt.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (rt.active=1 or rt.id={$val})";

                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-06-04 SNS. универсальный конструктор массива с id, name
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('roletypes as rt')
                ->whereRaw($sc)
                ->select('rt.id', 'rt.name')
                ->orderBy('rt.name', 'asc')
                ->get()->pluck('name', 'id')->toArray();
            //asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function getFor($s_params, $fields = null)
    {
        //2021-04-30 SNS. универсальный конструктор коллекции из записей справочника
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"
        // fields - массив со списком возвращаемых полей таблицы

        if (isset($s_params) and is_countable($s_params) and count($s_params) > 0) {

            $sc = self::search_cond($s_params);
            //Log::info($sc);
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'clp.*';
            //Log::info(json_encode($fields));

            $recs = self::from('roletypes as rt')
                ->whereRaw($sc)
                ->select($fields)
                ->orderBy('rt.name', 'asc')
                ->get();
            //dd($sc,$recs);
            return $recs;
        } else
            return null;
    }


}
