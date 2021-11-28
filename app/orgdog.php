<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class orgdog extends Model
{
    use \App\Traits\DeleteTrait;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];

    public function ownorg()
    {
        return $this->hasOne(org::class, 'id', 'ownorgid')
            ->withDefault();
    }

    public function org()
    {
        return $this->hasOne(org::class, 'id', 'orgid')
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

    static public function add_orgdog_low($ownorgid, $orgid, $begdate = null, $enddate = null,
                                          $dognum = null, $dogdate = null, $descript = null, $active = 1)
    {
        //Добавление без проверок на существование
        if (isset($ownorgid) and isset($orgid)) {

            $begdate = $begdate ?? date('Y-m-d');
            $rec = new orgdog([
                'ownorgid' => $ownorgid,
                'orgid' => $orgid,
                'dognum' => $dognum,
                'dogdate' => $dogdate,
                'descript' => $descript,
                'begdate' => $begdate,
                'enddate' => $enddate,
                'active' => $active,
            ]);
            $rec->save();
            return $rec->id;
        }

        return null;
    }

}
