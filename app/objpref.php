<?php

namespace App;

use App\Traits\DeleteTrait;
use Cache;
use DB;
use Illuminate\Database\Eloquent\Model;

class objpref extends Model
{
    use DeleteTrait;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function preftype()
    {
        return $this->hasOne(preftype::class, 'id', 'preftypeid')
            ->withDefault();
    }

    public function sysobjid()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid');
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    static public function getListPrefValue($sysobjid, $objid)
    {
        $rq = static::from('objprefs as oi')
            ->join('preftypes as pf', 'pf.id', '=', 'oi.preftypeid')
            ->where('oi.sysobjid', $sysobjid)
            ->where('oi.objid', $objid)
            ->orderBy('oi.id')
            ->select('oi.preftypeid', 'oi.prefvalue', 'pf.name');
        return $rq->get();
    }

    public static function getPref($sysobjid, $objid, $preftypeid)
    {
        //Приоритетно ищется преференция, которая непосредственно связана с указанным обэектом
        //но если ее нет, то берется преференция, назначенная
        // для любого обьекта (objis is null) заданного типа (sysobjid)
        //Если же нет и ее, то берется преференция для любого типа объекта (sysobjid is null)

        //$preftypeid должен быть обязательно не НУЛЛ
        // Допустимые варианты для $sysobjid и $objid:
        // 1. оба не НУЛЛ
        // 2. $sysobjid не НУЛЛ, $objid НУЛЛ
        // 3. оба НУЛЛ
        if (isset($preftypeid)
            and (($sysobjid and $objid)
                or ($sysobjid and !$objid)
                or (!$sysobjid and !$objid))
        ) {

            $rec = objpref::where('preftypeid', $preftypeid);
            if ($sysobjid) {
                $rec = $rec->whereraw('ifnull(sysobjid,' . $sysobjid . ')=' . $sysobjid);

                if ($objid)
                    $rec = $rec->whereraw('ifnull(objid,' . $objid . ')=' . $objid);
                else
                    $rec = $rec->whereNull('objid');
            } else {
                $rec = $rec->whereNull('sysobjid')->whereNull('objid');
            }

            $rec = $rec->orderbyraw('if(isnull(sysobjid), 0,1)+if(isnull(objid), 0,1) desc')
                ->first();

            if (isset($rec)) {
                $val = null;
                switch ($rec->preftype->valtype) {
                    case 'C':
                        $val = $rec->prefvalue;
                        break;
                    case 'N':
                        $val = $rec->n_val;
                        break;
                    case 'D':
                        $val = $rec->d_val;
                        break;
                    default:
                        $val = $rec->prefvalue;
                }
                $rec->value = $val;
            }
            //dd($val);
            return $rec;
        }
    }

    public static function getPrefVal_noCache($sysobjid, $objid, $preftypeid)
    {
        if (isset($preftypeid)) {

            $rec = self::getPref($sysobjid, $objid, $preftypeid);
            if (isset($rec)) {
                return $rec->value;
            }

        }
        return null;
    }

    public static function getPrefVal($sysobjid, $objid, $preftypeid)
    {
        if (isset($preftypeid)) {

            return Cache::remember('objpref:' . $sysobjid . ':' . $objid . ':' . $preftypeid, now()->addMinutes(10)
                , function () use ($sysobjid, $objid, $preftypeid) {

                    $rec = self::getPref($sysobjid, $objid, $preftypeid);
                    if (isset($rec)) {
                        return $rec->value;
                    }
                });

        }
        return null;
    }

    public static function setOrClrPrefVal($sysobjid, $objid, $preftypeid, $prefvalue, $userid = null)
    {
        //Устанавливает или удаляет значение преференции - в зависимости от пустоты в $prefvalue
        if (isset($preftypeid) and isset($sysobjid) and isset($objid)) {

            if (is_null($prefvalue)) {
                objpref::where([['preftypeid', $preftypeid], ['sysobjid', $sysobjid], ['objid', $objid]])
                    ->delete();

            } else {
                return self::setPrefVal($sysobjid, $objid, $preftypeid, $prefvalue, $userid, false);
            }
        }

    }

    public static function setPrefVal($sysobjid, $objid, $preftypeid, $prefvalue, $userid = null, $set_cache = true)
    {
        //Устанавливает значение преференции
        //Возвращает ID созданной или обновленной записи в ObjPrefs
        if (isset($preftypeid) and isset($prefvalue)) {
            //$userid = \Auth::user()->id ?? -1;

            $rec = objpref::where([['preftypeid', $preftypeid], ['sysobjid', $sysobjid], ['objid', $objid]])
                ->first();

            if (!isset($rec)) {
                $rec = new objpref([
                    "preftypeid" => $preftypeid,
                    "sysobjid" => $sysobjid,
                    "objid" => $objid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()
                ]);
            }

            $preftypeid = $rec->preftypeid;
            $preftype = Cache::remember('$preftypeid_' . $preftypeid, now()->addMinutes(10)
                , function () use ($preftypeid) {
                    return preftype::find($preftypeid);
                });;

            if (isset($preftype->valtype)) {
                $rec->prefvalue = $prefvalue;

                switch ($preftype->valtype) {
                    case 'C':
                        if (!is_null($preftype->valmaxlen))
                            $rec->prefvalue = mb_substr($prefvalue, 0, $preftype->valmaxlen);
                        break;

                    case 'N':
                        //Если заданы границы допустимых значений - нормируем переданное значение
                        $min = $preftype->nvalmin;
                        $max = $preftype->nvalmax;
//                    dd($prefvalue, $min, $max, $prefvalue < $min, $prefvalue > $max, $prefvalue == $max);
                        if ($min and $prefvalue < $min) $prefvalue = $min;
                        if ($max and $prefvalue > $max) $prefvalue = $max;

                        $rec->n_val = $prefvalue;
                        $rec->prefvalue = $prefvalue;
                        break;

                    case 'D':
                        $rec->d_val = date("Y-m-d", strtotime($prefvalue));
                        break;
//            default:
                }
                $rec->save();

                if ($set_cache)
                    Cache::put('objpref:' . $sysobjid . ':' . $objid . ':' . $preftypeid, $prefvalue, 10);

                return $rec->id;
            } else return null;
        } else return null;
    }

    public static function setPrefVal0($sysobjid, $objid, $preftypeid, $prefvalue, $userid = null)
    {
        //Устанавливает значение преференции
        //Возвращает ID созданной или обновленной записи в ObjPrefs
        if (isset($preftypeid) and isset($prefvalue)) {
            //$userid = \Auth::user()->id ?? -1;

            $rec = objpref::where([['preftypeid', $preftypeid], ['sysobjid', $sysobjid], ['objid', $objid]])
                ->first();

            if (!isset($rec)) {
                $rec = new objpref([
                    "preftypeid" => $preftypeid,
                    "sysobjid" => $sysobjid,
                    "objid" => $objid,
                    "created_by" => $userid,
                    "created_at" => now(),
                    "updated_by" => $userid,
                    "updated_at" => now()
                ]);
            }

            if (isset($rec->preftype->valtype)) {
                $rec->prefvalue = $prefvalue;

                switch ($rec->preftype->valtype) {
                    case 'C':
                        if (!is_null($rec->preftype->valmaxlen))
                            $rec->prefvalue = mb_substr($prefvalue, 0, $rec->preftype->valmaxlen);
                        break;

                    case 'N':
                        //Если заданы границы допустимых значений - нормируем переданное значение
                        $min = $rec->preftype->nvalmin;
                        $max = $rec->preftype->nvalmax;
//                    dd($prefvalue, $min, $max, $prefvalue < $min, $prefvalue > $max, $prefvalue == $max);
                        if ($min and $prefvalue < $min) $prefvalue = $min;
                        if ($max and $prefvalue > $max) $prefvalue = $max;

                        $rec->n_val = $prefvalue;
                        $rec->prefvalue = $prefvalue;
                        break;

                    case 'D':
                        $rec->d_val = date("Y-m-d", strtotime($prefvalue));
                        break;
//            default:
                }
                $rec->save();

                Cache::put('objpref:' . $sysobjid . ':' . $objid . ':' . $preftypeid, $prefvalue, 10);

                return $rec->id;
            } else return null;
        } else return null;
    }

    public static function tglPrefVal($sysobjid, $objid, $preftypeid)
    {
        $pref = self::getPref($sysobjid, $objid, $preftypeid);
        if (isset($pref)) {
            $prefval = $pref->prefvalue;
            if ($pref->preftype->valtype == "N") {
                $min = $pref->preftype->nvalmin;
                $max = $pref->preftype->nvalmax;
                if ($min and $max) {
                    //переключение по циклу возможно, только если определены границы изменения
                    $prefval = $pref->n_val + 1;
                    if ($prefval > $max) $prefval = $min;
                    self::setPrefVal($sysobjid, $objid, $preftypeid, $prefval);
                }
            }
            return $prefval;
        } else {
            //Похоже, что гет даже значения-поумолчанию, а не только значения
            // для текущего объекта(пользователя)
            //=>нужно создать запись о преференции для объекта
            self::setPrefVal($sysobjid, $objid, $preftypeid, 0);
        }
        return null;
    }

    public static function userPrefVal($userid, $preftypeid)
    {
        if (isset($preftypeid)) {
            return Cache::remember('objpref:' . 3 . ':' . ($userid ?? '_') . ':' . $preftypeid, now()->addMinutes(10)
                , function () use ($userid, $preftypeid) {

                    $rec = self::getPref(3, $userid, $preftypeid);
                    if (isset($rec)) {
                        return $rec->value;
                    }
                });
        }
        return null;
    }


}
