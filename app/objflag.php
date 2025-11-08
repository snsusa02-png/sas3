<?php

namespace App;

use App\Traits\DeleteTrait;
use Auth;
use Cache;
use Illuminate\Database\Eloquent\Model;
use Log;

class objflag extends Model
{
    use DeleteTrait;

    //created_at и updated_at атоматически обрабатываются Eloquent
    //мы же первое поле обрабатываем сами, а второго у нас нет
    public $timestamps = false;

    protected $fillable = ["sysobjid", "objid", "flagtypeid", "created_at", "created_by"];

    public function flagtype()
    {
        return $this->hasOne(flagtype::class, 'id', 'flagtypeid');
    }

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public static function getFlags4Obj0($sysobjid, $objid)
    {
        return static::from('objflags as o')
            ->where('sysobjid', $sysobjid)
            ->where('objid', $objid)
            ->join('flagtypes as t', 't.id', '=', 'o.flagtypeid')
            ->select("t.id", "t.name", "o.created_at", "t.css_style")
            ->selectraw("userfiobyid(o.created_by) as created_by")
            ->with('flagtype')
            ->get();
    }

    public static function getFlags4Obj($sysobjid, $objid)
    {
        //Cache::forget('getFlags4Obj_' . $sysobjid . '_' . $objid);
        return Cache::remember('getFlags4Obj_' . $sysobjid . '_' . $objid, now()->addMinutes(10)
            , function () use ($sysobjid, $objid) {
                return static::from('objflags as o')
                    ->where('sysobjid', $sysobjid)
                    ->where('objid', $objid)
                    ->with('flagtype')
                    ->with('whocrt')
                    ->get();
            });
    }

    public static function FlagTypesForOrg($orgid)
    {
        return flagtype::from('flagtypes as ft')
            ->leftJoin('objflags as f', function ($j) use ($orgid) {
                $j->on('f.flagtypeid', 'ft.id')
                    ->where('objid', $orgid);
            })
            ->where('ft.forsysobjid', 111)
            ->where('ft.active', 1)
            ->select('ft.id', 'ft.name', 'f.id as objflagid')
            ->orderby('ft.name')
            ->get();
    }

    public static function FlagTypesForObj($sysobjid, $objid)
    {
        return flagtype::from('flagtypes as ft')
            ->leftJoin('objflags as f', function ($j) use ($sysobjid, $objid) {
                $j->on('f.flagtypeid', 'ft.id')
                    ->where('objid', $objid);
            })
            //2025-06-15
            //->where('ft.forsysobjid', $sysobjid)
            //2025-07-05
            ->where('ft.forsysobjid', $sysobjid)
            ->where('ft.active', 1)
            ->select('ft.id', 'ft.name', 'ft.name as flagtype_name', 'f.id as objflagid')
            ->orderby('ft.name')
            ->get();
    }

    /*2025-06-15 Флаги, установленные для объекта $sysobjid, $objid */
    public static function FlagsForObj($sysobjid, $objid)
    {
        //return Cache::remember('FlagsForObj' . $sysobjid . '_' . $objid, now()->addMinutes(10)
        //    , function () use ($sysobjid, $objid) {
                return static::from('objflags as f')
                    ->join('flagtypes as ft', 'ft.id', 'f.flagtypeid')
                    ->where('f.sysobjid', $sysobjid)
                    ->where('f.objid', $objid)
                    ->select('f.id', 'ft.name', 'ft.name as flagtype_name', 'f.id as objflagid'
                        , 'f.created_at', 'f.created_by')
                    ->orderby('ft.name')
                    ->get();
        //    });
    }


    public static function IsSetObjFlag($sysobjid, $objid, $flagtypeid)
    {

//        $fl = static::where('sysobjid', $sysobjid)
//            ->where('objid', $objid)
//            ->where('flagtypeid', $flagtypeid)
//            ->select("id")->get();
//
//        $res = ($fl->count() == 0) ? 0 : 1;
//        return $res;

        return !(static::where(['sysobjid' => $sysobjid, 'objid' => $objid, 'flagtypeid' => $flagtypeid])->count() == 0);

    }

    public static function IsSetObjFlags($sysobjid, $objid, $lstflagtypeid)
    {

        $fl = static::where('sysobjid', $sysobjid)
            ->where('objid', $objid)
            ->whereIn('flagtypeid', $lstflagtypeid)
            ->count();

        $res = ($fl == 0) ? 0 : 1;

        return $res;

    }

    public static function AddObjFlag($sysobjid, $objid, $flagtypeid, $userid = null)
    {

        if (self::IsSetObjFlag($sysobjid, $objid, $flagtypeid) == 1) {
            return;
        }
        $flag = new objflag;
        $flag->sysobjid = $sysobjid;
        $flag->objid = $objid;
        $flag->flagtypeid = $flagtypeid;
        $flag->created_at = now();
        $flag->created_by = $userid ?? Auth::user()->id ?? 1;
        $flag->save();
    }

    public static function UpdObjFlag($sysobjid, $objid, $flagtypeid, $userid = null)
    {

        $flag = objflag::where('sysobjid', $sysobjid)
            ->where('objid', $objid)
            ->where('flagtypeid', $flagtypeid)->first();
        if (isset($flag)) {
            $userid = $userid ?? Auth::user()->id ?? 1;
            //Обновим данные флага
            $flag->created_at = now();
            $flag->created_by = $userid;
            $flag->save();
        } else
            //Создадим флаг
            self::AddObjFlag($sysobjid, $objid, $flagtypeid);
    }

    public static function DelObjFlag($sysobjid, $objid, $flagtypeid)
    {

        if (self::IsSetObjFlag($sysobjid, $objid, $flagtypeid) == 0) {
            return;
        }

        static::where('sysobjid', $sysobjid)
            ->where('objid', $objid)
            ->where('flagtypeid', $flagtypeid)
            ->select("id")->delete();
    }

    public static function boot()
    {
        static::created(function (objflag $instance) {
            //created - заглушка, для after insert
            //$instance - конкретный текущий экземпляр класса objflag
            $crtstaffid = null; //User::getStaffID($instance->created_by);
            //try {
            switch ($instance->sysobjid) {
                case 1:
                    break;
                case 3:
                    break;
                case 131://Флаги заказа
                    info('Set flag ' . $instance->flagtypeid . ' for object ' . $instance->sysobjid . '.' . $instance->objid);
                    switch ($instance->flagtypeid) {

                        case 1: //-- заказ размещен клиентом
                            \App\Jobs\OrderMessages::dispatch($instance->objid, 'ORD.PUTCSTMR', $crtstaffid);
                            break;
                        case 2: //-- заказ отозван клиентом
                            break;
                        case 3: //--заказ взят в работу
                            //Event::dispatch(new OrderChangeEvent($order,'ORD.TAKEMNGR'));
                            \App\Jobs\OrderMessages::dispatch($instance->objid, 'ORD.TAKEMNGR', $crtstaffid);
                            break;
                        case 4: //-- менеджер приостановил работу с заказом
                            break;
                        case 5: //-- заказ сформирован
                            \App\Jobs\OrderMessages::dispatch($instance->objid, 'ORD.OFFERCMPLT', $crtstaffid);
                            break;
                        case 6: //-- заказ отгружен со склада
                            break;
                        case 7: //-- заказ доставлен клиенту
                            break;
                        case 8: //-- заказ оплачен клиентом
                            break;
                        case 9: //-- Постоянный клиент:
                            break;
                        default:
                    }
                    break;
                default:
            }

            /*} catch (\Exception $e) {
                Log::error("Ошибка обрабоки события для $instance->order->id" .
                    $e->getMessage());
            }*/
        }
        );
        parent::boot();
    }


    static public function lstUsedFlagsForSysObj_cache($sysobjid)
    {
        if (isset($sysobjid)) {
            return Cache::remember('lstUsedFlagsForSysObj_' . $sysobjid, now()->addMinutes(15)
                , function () use ($sysobjid) {
                    $arr = flagtype::from('flagtypes as ft')
                        ->select('ft.id', 'ft.name')
                        ->where('uservisible', 1)
                        ->whereraw('exists (select 1 from objflags as f where f.flagtypeid=ft.id and f.sysobjid=' . $sysobjid . ')')
                        //->orderby('ft.ordr')
                        ->orderby('ft.name')
                        ->get()->pluck('name', 'id')->toArray();
                    return $arr;
                });
        }
    }

    //2025-11-02
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


                    if ($key == 's_name' or $key == 'name') {
                        $sc = $sc . " and concat(os.lname,' ',os.fname,' ',os.mname) like '%" . mb_strtoupper($val) . "%'";

                    } elseif ($key == 's_orgflagid') {
                        $sc .= " and exists(select 1 from objflags f where f.sysobjid=111 and f.objid=os.orgid and f.flagtypeid={$val})";


                    } elseif ($key == 's_postname') {
                        $sc = $sc . " and ( os.postname like '%{$val}%'
                    or exists (select 1 from orgposts as op where op.id=os.postid and op.name like '%{$val}%')
                    ) ";

                    } elseif ($key == 's_file_doctypeid') {
                        $tsysobjid = self::sysobjid;
                        $sc = $sc . " and exists (select 1 from objfiles as f where f.sysobjid={$tsysobjid}
                                        and f.objid=os.id and f.doctypeid={$val})";

                    } elseif ($key == 's_orgid') {
                        $sc .= " and os.orgid={$val}";

                    } elseif ($key == 'depid') {
                        $sc .= " and os.depid={$val}";

                    } elseif ($key == 'postid') {
                        $sc .= " and os.postid={$val}";

                    } elseif ($key == 'active' or $key == 's_active') {
                        $sc .= " and ifnull(os.active,0) = '{$val}'";

                    } elseif ($key == 'in_cursias') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from cursias as crs where crs.staffid=os.id)";

                    } elseif ($key == 'in_documents') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from obj_staffs as ojs where ojs.sysobjid=1701 and ojs.staffid=os.id)";

                    } elseif ($key == 'driver_in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from mchn_raids as mr where mr.driverid=os.id)";

                    } elseif ($key == 'dispatcher_in_mchn_raids') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from mchn_raids as mr where mr.disp_staffid=os.id)";

                    } elseif ($key == 'dispatcher_in_mr_opers') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from mr_opers as mro where mro.disp_staffid=os.id)";

                    } elseif ($key == 'dispatcher_in_wrhdocs') {
                        $sc .= " and " . (($val == 1) ? '' : 'not') . " exists(select 1 from wrhdocs as wd where wd.disp_staffid=os.id)";

                    } elseif ($key == 'no_signature_for_sysobj') {
                        //нет требующейся подписи на хранимом образе документа
                        $sc .= " and exists( select 1 from obj_staffs as ojs where ojs.sysobjid={$val} and ojs.staffid=os.id and ojs.signed=0 )";

                    } elseif ($key == 'in_driver_works') {
                        //
                        $sc .= " and exists( select 1 from driver_works as dw where dw.staffid=os.id)";

                    } elseif ($key == 'in_org_curators_now') {
                        //сотрудник должен быть куратором организации
                        $sc .= " and exists( select 1 from org_curators as oc where oc.staffid=os.id
                            and oc.active=1 and now() between oc.begdt and ifnull(oc.enddt,now()) )";

                    } elseif ($key == 'in_org_curators') {
                        //сотрудник должен быть куратором организации
                        $sc .= " and exists( select 1 from org_curators as oc where oc.staffid=os.id )";

                    } elseif ($key == 'staff_in_fuelcard_pays') {
                        $sc .= " and exists( select 1 from fuelcard_pays as fcp where fcp.driverid=os.id )";
                    }
                }

            }
        }
        //Log::info($sc);

        return $sc;

    }

    static public function lstFor($params)
    {
        //2021-04-29 SNS. универсальный конструктор массива с id, name сотрудников
        // params - массив, содержащий пару "имя параметра"=>"значение параметра"

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $sc = self::search_cond($params);

            $lst = self::from('objflags as f')
                ->join('flagtypes as ft','ft.id', 'f.flagtypeid')
                ->whereRaw($sc)
                ->select('f.id', 'ft.name as tname')
                ->orderBy('tname', 'asc')
                ->get()->pluck('tname', 'id')->toArray();
            asort($lst);
            //dd($sc,$lst);
            return $lst;
        } else
            return null;
    }

    static public function lstFor_cached($params, $cache_minutes = null)
    {
        //2021-10-06 SNS. кэшируемый результат списка

        if (isset($params) and is_countable($params) and count($params) > 0) {

            $hash = md5(serialize($params));

            //Cache::forget('lstFor_' . $hash);
            return \Illuminate\Support\Facades\Cache::remember(self::$prefix . '_lstFor_' . $hash, now()->addMinutes($cache_minutes ?? 5)
                , function () use ($params) {
                    return self::lstFor($params);
                });
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
            $fields = (isset($fields) and count($fields) > 0) ? $fields : 'os.*';
            //Log::info(json_encode($fields));

            $sorts = $sorts ?? [['os.lname', 'asc'], ['os.fname', 'asc']];

            $recs = self::from('orgstaff as os')
                ->Join('orgs as o', 'o.id', 'os.orgid')
                ->leftJoin('orgposts as op', 'op.id', 'os.postid')
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
