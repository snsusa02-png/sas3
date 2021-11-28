<?php

namespace App;

use App\Traits\DeleteTrait;
use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use Cache;
use DB;

class news extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'news';
    static public $sysobjid = 904;

    public function whocrt()
    {
        return $this->hasOne(User::class, 'id', 'created_by');
    }

    public function whoupd()
    {
        return $this->hasOne(User::class, 'id', 'updated_by');
    }

    public function inituser()
    {
        return $this->hasOne(User::class, 'id', 'inituserid')->withDefault();
    }

    public function band()
    {
        return $this->hasOne(newsband::class, 'id', 'bandid')->withDefault();
    }

    public function author()
    {
        return $this->hasOne(User::class, 'id', 'inituserid');
    }

    // news has many comments
    // returns all comments on that post
    public function comments()
    {
        return $this->hasMany('App\obj_comment', 'objid', 'id')
            ->where('sysobjid', self::$sysobjid);
    }

    public function readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->orderby('firstread_at');
    }

    public function real_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at');
    }


    public function scopePublished($query)
    {
        return $query->where('is_published', 1);
    }

    public function scopeRecent($query)
    {
        return $query->whereraw('date(published_at)>=curdate()-1')
            ->where('is_published', 1);
    }

    public static function used_bands()
    {
        if (1 == 1) {
            return Cache::remember('news_used_bands', now()->addMinutes(5)
                , function () {
//                    return newsband::from('newsbands as nb')
//                        ->whereraw("exists(select 1 from news as p where p.bandid=nb.id and p.is_published=1)")
//                        ->select('id', 'name')
//                        ->get();
                    return newsband::from('newsbands as nb')
                        ->join('news as n', 'nb.id', 'n.bandid')
                        ->where("n.is_published", 1)
                        ->select('nb.id', 'nb.name', db::raw("count(*) as cnt"))
                        ->groupby('nb.id')
                        ->get();
                });
        }

    }


}
