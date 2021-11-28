<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class task_user extends Model
{
    use \App\Traits\DeleteTrait;

    static public $prefix = 'task_users';
    static public $sysobjid = 963;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function task()
    {
        return $this->hasOne(task::class, 'id', 'taskid');
    }

    public function user()
    {
        return $this->hasOne(User::class, 'id', 'userid')->withDefault();
    }

    public static function roletypes()
    {
        //return [7 => 'исполнитель', 8 => 'куратор'];
        return roletype::from('roletypes as rt')
            ->join('sysobj_roletypes as so', 'so.roletypeid', 'rt.id')
            ->where('so.sysobjid', self::$sysobjid)
            ->orderby('so.ordr')
            ->orderby('rt.name')
            ->select('rt.id', 'rt.name')
            ->get()
            ->pluck('name', 'id')
            ->toArray();
    }

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    static public function addOrUpdate($search_params, $set_params)
    {
        if (isset($search_params) and isset($set_params)) {

            $rec = self::where($search_params)->first();

            if (!isset($rec)) {
                $rec = new self($search_params);
            }
            $rec->fill($set_params);
            $rec->save();

            return $rec;
        }
        return null;
    }

}
