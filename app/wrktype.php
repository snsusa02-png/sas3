<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;

class wrktype extends Model
{
    static public $prefix = 'wrktypes';
    static public $sysobjid = -1106;

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

    //
    public static function main_wrktypes()
    {
        return static::from('wrktypes as t')
            ->select('t.id', 't.name')
            ->where('t.active', 1)
            ->where('t.main', 1)
            ->orderby('t.ordr')
            ->orderby('t.name')
            ->get()->pluck('name', 'id')->toArray();;
    }

    public static function aux_wrktypes()
    {
        return static::from('wrktypes as t')
            ->select('t.id', 't.name')
            ->where('t.active', 1)
            ->where('t.main', '!=', 1)
            ->orderby('t.ordr')
            ->orderby('t.name')
            ->get()->pluck('name', 'id')->toArray();;
    }
}
