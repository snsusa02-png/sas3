<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use DB;
use Cache;

class unittype extends Model
{
    use \App\Traits\DeleteTrait;

    //это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    static public $prefix = 'unittypes';
    static public $sysobjid = 301;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function parent()
    {
        return $this->hasOne(self::class, 'id', 'parent_by')->withDefault();
    }

    //дочерние ЕИ
    public function childs()
    {
        return $this->hasMany(self::class, 'parent_by', 'id');
    }

    public function obj_names()
    {
        return $this->hasMany(obj_name::class, 'objid', 'id')
            ->where([
                'sysobjid' => self::$sysobjid,
            ]);
    }


    public static function newItmByExtID($extsysid, $itmextid, $itmdata, $userid = null)
    {
        $sysobjid = 301;
        //перепроверим - вдруг уже есть такая запись:
        $itmid = objextid::objid_by_extsysid_extid($extsysid, $sysobjid, $itmextid);
        if (!isset($itmid) and isset($itmextid)) {

//            \Log::debug(var_dump($itmdata));

            try {
                DB::beginTransaction();

                $userid = $itmdata['created_by'] ?? 0;
                $itmdata['updated_by'] = $itmdata['updated_by'] ?? $userid;

                //Добавить запись о единице измерения
                $rec = new unittype($itmdata);
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

            } catch
            (\Exception $e) {
                //var_dump($e->getTraceAsString());
                \Log::debug($e->getMessage());
                return $e->getMessage();
            } finally {
            }
        }
        return $itmid;
    }


    public static function unittypes_cache()
    {
        return Cache::remember('unittypes', 150, function () {
            return unittype::from('unittypes as ut')
                ->where('ut.active', 1)
                ->select('ut.id', db::raw("concat(name, if(descript is null,'', concat(' - ', descript))) as tname"))
                ->get()
                ->pluck("tname", "id")->toArray();
        });

    }

    public static function idByName($name)
    {
        //поиск Id ЕИ по ее названию - основному или альтернативному
        return self::where('name', $name)
                ->orWhereRaw("exists (select 1 from obj_names as n where n.sysobjid=301 and n.objid=unittypes.id and n.name='$name')")
                ->first()->id ?? null;
    }

    public static function nameById($unittypeid)
    {
        return Cache::remember('nameById_' . $unittypeid, 150, function () use ($unittypeid) {
            return unittype::find($unittypeid)->name ?? null;
        });

    }

    public static function unit2unit_koef($src_unittypeid, $tgt_unittypeid, $refitmid = null)
    {
        // Если может - возвращает коэффициент пересчета кол-ва в $src_unittypeid в $tgt_unittypeid,
        // с учетом физ. характеристик референсного материала

        if (isset($src_unittypeid) and isset($tgt_unittypeid)) {

            //сначала попробуем найти соответствие в простых зависимостях
            $src_unittype = self::find($src_unittypeid);
            if (isset($src_unittype)) {
                if ($src_unittype->parent_by == $tgt_unittypeid) {
                    //да исходная ЕИ является дочерней для целевой ЕИ
                    // => $src_unittype->k2prnt_unit - это то, что нужно
                    return $src_unittype->k2prnt_unit;
                }
            }

            //видимо не смогли найти простое соответствие.
            //Попробуем использовать зависимости от указанного материала (на основе физ. свойств)
            if (isset($refitmid)) {
                $ri_unit = ri_unit::where(['refitmid' => $refitmid, 'unittypeid' => $src_unittypeid])
                    ->first();
                if(isset($ri_unit)){
                    //нашли нужную исходную ЕИ в ЕИ материала

                    //теперь нужно чтобы ЕИ справочника номенклатуры совпала с итоговой ЕИ,
                    // тогда в k2refunit будет нужный коэффициент

                    $refitem=refitem::find($refitmid);
                    if(isset($refitem) and $refitem->unittypeid==$tgt_unittypeid)
                        return $ri_unit->k2ref_unit;
                }
            }
        }
        return null;
    }

}
