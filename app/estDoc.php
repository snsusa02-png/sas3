<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;
use DB;

class estDoc extends Model
{

    use DeleteTrait;
    use FilesTrait;


    protected $table = 'estdocs';
    protected $guarded = [];

    static public $prefix = 'estdocs';
    static public $sysobjid = 465;


    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by')->withDefault();
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by')->withDefault();
    }

    public function ownorg()
    {
        return $this->hasOne(contracttype::class, 'id', 'ownorgid')
            ->withDefault();
    }

    public function org()
    {
        return $this->hasOne(contracttype::class, 'id', 'orgid')
            ->withDefault();
    }

    public function buildobj()
    {
        return $this->hasOne(buildobj::class, 'id', 'buildobjid')
            ->withDefault();
    }

    public function buildopertypeid()
    {
        return $this->hasOne(buildopertype::class, 'id', 'buildopertypeid')
            ->withDefault();
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
//    protected $fillable = [
//        'projectid',
//        'name',
//        'srcfile',
//        'programVersion',
//        'generator',
//        'documentType',
//        'locnum',
//        'constr',
//        'comment',
//        'description',
//        'docsum',
//        'drct_sum',
//        'drct_sum',
//        'nacl_sum',
//        'sp_sum',
//    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];


    public function estdocItems()
    {
        return $this->hasMany(\App\estdocItem::class);
    }

    public function items000()
    {
        return $this->hasMany(estdocItem::class, 'estdocid', 'id')
            ->orderBy('chapter_sysid')
            //->orderBy('estdoc_sections.ordr')
            ->orderBy('id')
            ->with('section');
    }

    static public function listForObjID($buildobjid)
    {
        if (isset($buildobjid)) {
            $lst = self::from('estdocs as d')
                ->where('buildobjid', $buildobjid)
                ->select('d.id', 'd.name', 'd.active', 'd.docnum', 'd.docdate'
                    , 'd.docsum', 'd.drct_sum', 'd.nacl_sum', 'd.sp_sum')
                ->orderby('d.docdate')
                ->get();
            return $lst;
        } else
            return null;
    }

    static public function listForOperTypeID($buildopertypeid)
    {
        if (isset($buildopertypeid)) {
            $lst = self::from('estdocs as d')
                ->where('buildopertypeid', $buildopertypeid)
                ->select('d.id', 'd.name', 'd.active', 'd.docnum', 'd.docdate'
                    , 'd.docsum', 'd.drct_sum', 'd.nacl_sum', 'd.sp_sum')
                ->orderby('d.docdate')
                ->get();
            return $lst;
        } else
            return null;
    }

    static public function newNum($ownorgid, $docdate = null)
    {
        //номер извлекается из таблицы DocNums - источник номеров для документов (для смет doctypeid=20)
        $doctypeid = 20;
        // docnums (doctypeid, ownorgid, active, begdt, enddt, nxtnum, numfmt, numagain, whoupd,...)
        $docdate = $docdate ?? now();
        $ownorgid = $ownorgid ?? 0;
        $data = DB::select("select nxtdocnum($doctypeid, $ownorgid,'$docdate') as num");
        $num = $data[0]->num;
        return $num;
    }

}
