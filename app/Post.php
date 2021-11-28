<?php

namespace App;

use App\Traits\FilesTrait;
use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;
use Cache;
use Illuminate\Support\Carbon;


class Post extends Model
{
    use DeleteTrait;
    use FilesTrait;

    protected $guarded = [];

    static public $prefix = 'posts';
    static public $sysobjid = 895;

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
//    protected $fillable = [
//        'title',
//        'content',
//        'published_at',
//    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
    ];

    /**
     * The attributes that should be mutated to dates.
     *
     * @var array
     */
    protected $dates = [
        'published_at',
    ];


    /**
     * Get the user that owns the post.
     */
    public function user()
    {
        return $this->belongsTo('App\User');
    }

    public function author()
    {
        return $this->hasOne(User::class, 'id', 'user_id');
    }

    public function category()
    {
        return $this->hasOne(post_category::class, 'id', 'category_id')->withDefault();
    }

    // posts has many comments
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

    public function curversion_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('lastread_at', '>=', $this->published_at ?? Carbon::tomorrow())
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at');
        //->with('user');
    }

    public function preversion_readers()
    {
        return $this->hasMany(obj_reader::class, 'objid', 'id')
            ->where('sysobjid', self::$sysobjid)
            ->where('lastread_at', '<', $this->published_at ?? Carbon::tomorrow())
            ->where('read_cnt', '>', 0)
            ->orderby('firstread_at')
            ->with('user');
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

    public static function used_categories()
    {
        if (1 == 1) {
            return Cache::remember('post_used_categories', now()->addMinutes(5)
                , function () {
                    return post_category::from('post_categories as c')
                        ->whereraw("exists(select 1 from posts as p where p.category_id=c.id and p.is_published=1)")
                        ->select('id', 'name')
                        ->get();
                });
        }

    }

}
