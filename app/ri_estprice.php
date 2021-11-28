<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ri_estprice extends Model
{
    static public $prefix = 'ri_estprices';
    static public $sysobjid = 145;

    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid');
    }

    public function suporg()
    {
        return $this->hasOne(org::class, 'id', 'suporgid')->withDefault();
    }


    public static function add_from_offers()
    {
        //добавление оценок из предложений по заявкам.

        // Заявки должны быть или на этапе подведения итогов, либо на этапе архива
        DB::unprepared('CALL add_ri_est_prices_from_offers()');

        objlog::log_info(self::$sysobjid, 0, "Добавление оценок из предложений поставщиков по заявкам на материалы", 3);

    }

}
