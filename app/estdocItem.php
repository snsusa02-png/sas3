<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class estdocItem extends Model
{
    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        'outlayid' => 'integer',
        'quantity' => 'decimal:2',
        'quantity_Fx' => 'decimal:2',
        'quantity_Result' => 'decimal:2',
    ];


    public function estdoc()
    {
        //return $this->belongsTo(\App\estDoc::class);
        return $this->hasOne(estDoc::class, 'id', 'estdocid');
    }

    public function section()
    {
        return $this->hasOne(estdoc_section::class, 'id', 'sectionid')
            ->withDefault();
    }

    public function buildopertype()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')
            ->withDefault();
    }

    static public function listForOperTypeID($buildopertypeid)
    {
        if (isset($buildopertypeid)) {
            $lst = self::from('estdoc_items as i')
                ->where('buildopertypeid', $buildopertypeid)
                ->select('i.id', 'i.reasoncode as code', 'i.title as name', 'i.units', 'i.qty', 'i.itmsum')
                ->orderby('i.id')
                ->get();
            return $lst;
        } else
            return null;
    }
}
