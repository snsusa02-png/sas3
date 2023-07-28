<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class srs_hr_item extends Model
{
    use DeleteTrait;

    static public $prefix = 'srs_hr_items';
    static public $sysobjid = 1223;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')
            ->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')
            ->withDefault();
    }

    public function salary_rate_set()
    {
        return $this->hasOne(salary_rate_set::class, 'id', 'srs_id')
            ->withDefault();
    }


    // Пересчет максимальных значений стажа для srs_id/wrktypeid
    public static function recalc_max_wrkexp($srs_id, $wrktypeid)
    {
        if (isset($srs_id) and isset($wrktypeid)) {
            $recs = srs_hr_item::where('srs_id', $srs_id)
                ->where('wrktypeid', $wrktypeid)
                ->select('id', 'min_wrkexp', 'max_wrkexp')
                ->orderby('min_wrkexp', 'desc')
                ->get();
            $max_wrkexp = 99.9;
            foreach ($recs as $r) {
                $r->max_wrkexp = $max_wrkexp;
                $r->save();
                $max_wrkexp = $r->min_wrkexp;
            }
        }
    }


    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи

        //пересчитаем максимальные значения для рабочего стажа у оставшихся записей
        // Перерасчет максимального значения стажа ----------------------------
        srs_hr_item::recalc_max_wrkexp($rec->srs_id, $rec->wrktypeid);
        //---------------------------------------------------------------------

        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function cache_clear($rec = null)
    {
        //для вызова при изменении / удалении записей
        if (isset($rec)) {
        }
        //Cache::forget('mchn_raids.years');
    }

}
