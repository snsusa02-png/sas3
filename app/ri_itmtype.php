<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class ri_itmtype extends Model
{
    use DeleteTrait;

    protected $guarded = [];

    public static function addRefItem($itmtypeid, $refitmid, $userid)
    {
        //Добавляем категорию к товару

        //перепроверим - вдруг уже есть такая запись:
        $itm = self::where('refitmid', $refitmid)
            ->where('itmtypeid', $itmtypeid)->first();

        if (!isset($itm)) {
            //добавим
            $itm = new self([
                'refitmid' => $refitmid,
                'itmtypeid' => $itmtypeid,
                'created_by' => $userid,
                'updated_by' => $userid,
            ]);
        }
        return;
    }

}
