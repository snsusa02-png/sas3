<?php

namespace App;

use Auth;
use Cache;
use Illuminate\Database\Eloquent\Model;
use Log;

class objflag extends Model
{
    //created_at и updated_at атоматически обрабатываются Eloquent
    //мы же первое поле обрабатываем сами, а второго у нас нет
    public $timestamps = false;

    protected $fillable = ["sysobjid", "objid", "flagtypeid", "created_at", "created_by"];

    public function flagtype()
    {
        return $this->hasOne(flagtype::class, 'id', 'flagtypeid');
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
            ->where('ft.forsysobjid', $sysobjid)
            ->where('ft.active', 1)
            ->select('ft.id', 'ft.name', 'f.id as objflagid')
            ->orderby('ft.name')
            ->get();
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

}
