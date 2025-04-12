<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use App\Traits\FinOpersTrait;
use Illuminate\Database\Eloquent\Model;

class route_point extends Model
{
    static public $prefix = 'route_points';
    static public $sysobjid = 1128;

    use DeleteTrait;
    use FilesTrait;
    use FinOpersTrait;

    protected $guarded = [];

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    static public function points_for_route($src_placename, $tgt_placename, $view_date)
    {
        //Ставка в баллах за маршрут $src_placename - $tgt_placename

        $points = self::where('active', 1)
                ->where('src_placename', mb_strtoupper($src_placename))
                ->where('tgt_placename', mb_strtoupper($tgt_placename))
                //->wherebetween($view_date, ['begdate', 'enddate'])
                ->whereDate('begdate','<=',$view_date)
                ->whereDate('enddate','>=',$view_date)
                ->select('points')
                //->toSql();
                ->first()->points ?? null;
        //dd(mb_strtoupper($src_placename), mb_strtoupper($tgt_placename), $view_date, $points);
        return $points;
    }

}
