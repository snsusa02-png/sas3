<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class obj_reader extends Model
{
    use DeleteTrait;

    static public $prefix = 'obj_readers';
    static public $sysobjid = 896;

    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'read_cnt' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'firstread_at',
        'lastreaded_at',
    ];


    public function sysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'sysobjid');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userid')->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public static function addOrUpdate($sysobjid, $objid, $userid, $mustread = 0, $roletypeid = null)
    {
        //dd($sysobjid, $objid, $userid, $mustread);
        //Создает или обновляет существующую запись
        if (isset($sysobjid) and isset($objid) and $objid <> -1 and isset($userid)) {

            $rdr = self::where(['sysobjid' => $sysobjid, 'objid' => $objid, 'userid' => $userid])->first();
            if (!isset($rdr)) {
                $rdr = new obj_reader([
                    'sysobjid' => $sysobjid,
                    'objid' => $objid,
                    'userid' => $userid,
                    //'firstread_at' => now(),
                    'read_cnt' => 0,
                ]);
            }
            $rdr->mustread = $mustread ?? 0;
            $rdr->roletypeid = $roletypeid; //тип роли пользователя to-do выделить в подсправчоник objreader_roles
            $rdr->save();

            return $rdr;
        }
    }

    public static function addOrUpdateStat($sysobjid, $objid, $userid)
    {
        //Создает или обновляет существующую запись и наращивает статистику открытий
        // возвращает время предыдущего открытия
        if (isset($sysobjid) and isset($objid) and isset($userid) and $objid <> -1) {

            $rdr = self::where(['sysobjid' => $sysobjid, 'objid' => $objid, 'userid' => $userid])->first();
            if (!isset($rdr)) {
                $rdr = new obj_reader([
                    'sysobjid' => $sysobjid,
                    'objid' => $objid,
                    'userid' => $userid,
                    'firstread_at' => now(),
                    'read_cnt' => 0,
                ]);
                $user_lastread_at = null;
            } else {
                $user_lastread_at = $rdr->lastread_at;
            }
            $rdr->firstread_at = $rdr->firstread_at ?? now();   //для записей которые были созданы принудительно и у них firstread_at = null
            $rdr->lastread_at = now();
            $rdr->read_cnt++;

            $rdr->save();

            return $user_lastread_at;
        }
    }

    public static function isUserInList($sysobjid, $objid, $userid)
    {
        // true если пользователь $userid включен в список для объекта $objid типа $sysobjid
        if (isset($sysobjid) and isset($objid) and isset($userid) and $objid <> -1) {

            return self::where(['sysobjid' => $sysobjid, 'objid' => $objid, 'userid' => $userid])->count() > 0;

        }
    }

    public static function isUserInAnyObjList($sysobjid, $userid)
    {
        // true если пользователь $userid включен в список для любого объекта типа $sysobjid
        if (isset($sysobjid) and isset($userid)) {

            return self::where(['sysobjid' => $sysobjid, 'userid' => $userid])->count() > 0;

        }
    }

}
