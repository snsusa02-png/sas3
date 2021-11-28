<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class obj_staff extends Model
{
    use DeleteTrait;
    use FilesTrait;

    static public $prefix = 'obj_staffs';
    static public $sysobjid = 1601;

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

    public function orgstaff()
    {
        return $this->hasOne(orgstaff::class, 'id', 'staffid')->withDefault();
    }

    public function roletype()
    {
        return $this->hasOne(roletype::class, 'id', 'roletypeid')->withDefault();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    static public function addWithRole($sysobjid, $objid, $staffid, $roletypeid)
    {
        //Создать или обновить запись об сотруднике для инф. объекта

        if (!isset($sysobjid) or !isset($objid) or !isset($staffid) or !isset($roletypeid))
            return false;

        //считаем, что сотрудник в инф. объекте может быть только 1 раз
        $rec = self::from('obj_staffs')
            ->where([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'staffid' => $staffid,
            ])
            ->first();

        if (!isset($rec)) {
            $rec = new self([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'staffid' => $staffid,
                'staffname' => orgstaff::find($staffid)->name ?? null,
            ]);
        }
        $rec->roletypeid = $roletypeid;
        $rec->save();
    }

    static public function add($sysobjid, $objid, $srcobj)
    {
        //Создать или обновить запись об сотруднике для инф. объекта

        if (!isset($sysobjid) or !isset($objid) or !isset($srcobj) or !isset($srcobj->staffid))
            return false;

        //считаем, что сотрудник в инф. объекте может быть только 1 раз
        $rec = self::from('obj_staffs')
            ->where([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'staffid' => $srcobj->staffid,
            ])
            ->first();

        if (!isset($rec)) {
            $rec = new self([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'staffid' => $srcobj->staffid,
                'staffname' => orgstaff::find($srcobj->staffid)->name ?? null,
            ]);
        }
        $rec->roletypeid = $srcobj->roletypeid ?? null;
        $rec->rolename = $srcobj->rolename ?? null;
        $rec->reason = $srcobj->reason ?? null;
        $rec->descript = $srcobj->descript ?? null;
        $rec->signed = $srcobj->signed ?? null;
        $rec->ordr = $srcobj->ordr ?? 250;
        $rec->active = $srcobj->active ?? 1;
        $rec->save();
    }


}
