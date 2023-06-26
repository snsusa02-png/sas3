<?php

namespace App;

use App\Traits\Result;
use Illuminate\Database\Eloquent\Model;

class ri_cmpnd_item extends Model
{
    use \App\Traits\DeleteTrait;
    use \App\Traits\FilesTrait;

    static public $prefix = 'ri_cmpnd_item';
    static public $sysobjid = 148;

    //protected $fillable = ['created_by'];

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', self::$sysobjid)
            ->withDefault();
    }

    public function ri_compound()
    {
        return $this->belongsTo(ri_compound::class, 'cmpndid', 'id');
    }

    public function refitem()
    {
        return $this->hasOne(refitem::class, 'id', 'refitmid')
            ->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public static function auxInfo($wrhid)
    {
        $info = [];
        return $info;
    }

    public static function cache_clear($rec)
    {
        //Забудем связанный кэш -------------------------------------
        if (isset($rec)) {
        }
//        Cache::forget('informer_saldos');
        //-----------------------------------------------------------
    }

    public static function on_update($rec)
    {
        // Доп. действия при изменении записи


        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

    public static function on_delete($rec = null)
    {
        // Доп. действия при удалении записи


        //Забудем связанный кэш -----------------
        self::cache_clear($rec);

    }

}
