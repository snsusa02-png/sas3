<?php

namespace App;

use App\Traits\FilesTrait;
use App\Traits\StringUtil;
use Illuminate\Database\Eloquent\Model;

use DB;
use App\Traits\DeleteTrait;
use Illuminate\Support\Facades\Cache;

class itmtype extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'itmtypes';
    static public $sysobjid = 101;


    protected $guarded = [];

    public function parent()
    {
        return $this->belongsTo(itmtype::class, 'parent_id', 'id')->withDefault();
    }

    //дочерние записи
    public function childs()
    {
        return $this->hasMany(itmtype::class, 'parent_id', 'id');
    }

    public function refitems()
    {
        return $this->hasMany(refitem::class, 'itmtypeid', 'id');
    }

    public function msgs()
    {
        return $this->hasMany(obj_msg::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }


    public static function getCategories()
    {
        // Получаем одним запросом все разделы
        $arr = self::orderBy('ordr')->orderBy('name')->get();

        // Запускаем рекурсивную постройку дерева и отдаем на выдачу
        return self::buildTree($arr, 0);
    }


    // Сама функция рекурсии
    public static function buildTree($arr, $pid = 0)
    {
        // Находим всех детей раздела
        $found = $arr->filter(function ($item) use ($pid) {
            return $item->parent_id == $pid;
        });

        // Каждому детю запускаем поиск его детей
        foreach ($found as $key => $cat) {
            $sub = self::buildTree($arr, $cat->id);
            $cat->sub = $sub;
        }

        return $found;
    }

    public static function it_name($id)
    {
        return Cache::remember('it_name_' . $id, now()->addMinutes(15)
            , function () use ($id) {
                $t = static::where('id', $id)->select('name')->first();
                return isset($t) ? $t->name : null;
            });
    }

    public static function ItmTypeslst()
    {
        //Cache::forget('ItmTypeslst');
        //to-do: сбрасывать кэш после загрузки файла с ценами и/или характеристиками
        // и после редактирования itmtypes
        //
        return Cache::remember('ItmTypeslst', 1500
            , function () {
                return DB::select('select id, name, parid, photourl, t.descript,
			              (select count(1) from itmsubtypes ri where ri.itmtypeid=t.id) sub_cnt
                            from itmtypes t
                            where active = 1
                            and exists (select 1 from refitems ri
                                where ri.itmtypeid=t.id and ri.active=1 and IFNULL(ri.saleenddate,CURDATE()) >= CURDATE() )
                            order by t.ordr, t.name asc');
            });
    }

    public static function getShortItmTypes()
    {
        return static::where('active', '=', DB::raw(1))
            ->orderBy('ordr')
            ->orderBy('name')
            ->select("name", "id");
    }

    public static function getName($itmtypeid)
    {
        return static::where("id", $itmtypeid)->value("name");
    }

    public static function newItmTypeByExtID($extsysid, $objextid, $objname, $userid = null)
    {
        //перепроверим - вдруг уже есть такая категория:
        $itmtypeid = objextid::objid_by_extsysid_extid($extsysid, 101, $objextid);
        if (!isset($itmtypeid)) {
            //$userid = \Auth::user()->id;

            try {
                DB::beginTransaction();

                //Добавить организацию
                $itmtype = new itmtype([
                    "name" => $objname,
                    "active" => 1,
                    "isservice" => 0,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $itmtype->save();
                $itmtypeid = $itmtype->id;

                //Добавить идентификатор организации во внешней системе $extsysid
                $ext = new objextid([
                    "extsysid" => $extsysid,
                    "sysobjid" => 101,
                    "objid" => $itmtypeid,
                    "extid" => $objextid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $ext->save();

                DB::commit();

            } catch (\Exception $e) {
                //DB::rollback();
                //$this->log->fatalerror($e->getMessage());
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
                //return null;
            } finally {
            }

        }
        return $itmtypeid;
    }

    public static function newItemByExtID($extsysid, $itmextid, $itmdata)
    {
        $sysobjid = 101;
        //перепроверим - вдруг уже есть такая запись:
        $itmid = objextid::objid_by_extsysid_extid($extsysid, $sysobjid, $itmextid);
        if (!isset($itmid) and isset($itmextid)) {

//            \Log::debug(var_dump($itmdata));

            try {
                DB::beginTransaction();

                $userid = $itmdata['created_by'] ?? 0;
                $itmdata['updated_by'] = $itmdata['updated_by'] ?? $userid;

                //Добавить запись о новой категории
                $rec = new self($itmdata);
                $rec->save();
                $itmid = $rec->id;


                //Добавить идентификатор товара во внешней системе $extsysid
                $ext = new objextid([
                    "extsysid" => $extsysid,
                    "sysobjid" => $sysobjid,
                    "objid" => $itmid,
                    "extid" => $itmextid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()]);
                $ext->save();

                DB::commit();

            } catch (\Exception $e) {
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
            } finally {
            }
        }
        return $itmid;
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
                        $sc .= " and it.active={$val}";

                    } elseif ($key == 'active_or_current') {
                        $sc .= " and (it.active=1 or it.id={$val})";

                    } elseif ($key == 'name') {
                        $search_flds = "it.name";

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
                    } elseif ($key == 'in_refitems') {
                        //использована с спр-ке номенклатуры
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from refitems as ri where ri.itmtypeid=it.id)";

                    } elseif ($key == 'in_ri_sup_prices') {
                        // товар с данной категорией есть в прайслисте поставщиков
                        $sc .= " and " . (($val == 0) ? "not" : "")
                            . " exists (select 1 from ri_sup_prices as rop join refitems as ri2 on ri2.id=rop.refitmid
                             where ri2.itmtypeid=it.id)";

                    }
                }

            }
        }
        //dd($sc);
        //Log::info($sc);

        return $sc;

    }


    static public function lstFor($params)
    {
        //2022-01-08 SNS. универсальный конструктор массива с id, name категорий товарной номенклатуры
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $userid = \Auth::user()->id;

            //для оптимизации запроса некоторые параметры обрабатываются группой.
            // Чтобы избежать повторного применения, используем добавление отработанных параметров
            // в массив $used_params
            $used_params = [];

            $sc = self::search_cond($params);
            //Log::info($sc);

            $lst = self::from('itmtypes as it')
                ->whereRaw($sc)
                ->select('id', 'name')
                ->orderBy('it.name', 'desc')
                ->get()->pluck('name', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }


    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2022-01-08 SNS. кэшируемый результат списка

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
