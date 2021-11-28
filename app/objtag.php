<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Cache;

class objtag extends Model
{
    //
    protected $guarded = [];

    public static function getTags($sysobjid, $objid = null)
    {
        $cache_key = 'objtag.getTags_' . $sysobjid . '_' . (isset($objid) ? $objid : '*');
        //echo($cache_key);
        Cache::forget($cache_key);

        $tags = Cache::remember($cache_key, now()->addMinutes(10)
            , function () use ($sysobjid, $objid) {
                return objtag::where(['sysobjid' => $sysobjid, 'objid' => $objid])
                    ->select('tag')
                    //->selectraw("concat(if(isnull(type),'',concat(type,': ')), tag) as tag")
                    ->get()->pluck('tag')->toArray();
            });
        return $tags;
    }

    public static function lstTags($sysobjid, $objid = null)
    {
        $cache_key = 'objtag.lstTags_' . $sysobjid . '_' . (isset($objid) ? $objid : '*');
        //echo($cache_key);
        $lsttags = implode(', ', self::getTags($sysobjid, $objid));
        return $lsttags;
    }

    public static function AddOrUpdate($sysobjid, $objid, $tag)
    {
//        $rec = self::firstOrCreate(
//            ['sysobjid' => $sysobjid],
//            ['objid' => $objid],
//            ['tag' => $tag]
//        );

        $tag = trim($tag);
        if ($tag <> '') {

            $parts = explode(':', $tag);
            if (count($parts) > 1) {
                $type = trim($parts[0]);
                $val = trim($parts[1]);
                $tag = $type . ': ' . $val;
            } else {
                $type = null;
                $val = trim($parts[0]);
                $tag = $val;
            }

            $rec = self::where([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'type' => $type,
                'val' => $val,
                'tag' => $tag,
            ])->first();

            if (!isset($rec))
                $rec = new self([
                    'sysobjid' => $sysobjid,
                    'objid' => $objid,
                    'type' => $type,
                    'val' => $val,
                    'tag' => $tag,
                    'created_by' => \Auth::user()->id,
                    'created_at' => now(),
                ]);
            //dd($rec);
            $rec->updated_at = now();
            $rec->updated_by = \Auth::user()->id;
            $rec->save();
        }
    }

    public static function attach($sysobjid, $objid, $tag_list)
    {
        // Сохранение тэгов -----------------------------------------------------------------
        if (isset($sysobjid) and isset($objid)) {
            //пометим текущие тэги через updated_by=0
            objtag::where(['sysobjid' => $sysobjid, 'objid' => $objid])
                ->update(['updated_by' => 0]);

            $tags = explode(',', $tag_list);
            //dd($tags);
            foreach ($tags as $tag) {

                objtag::AddOrUpdate($sysobjid, $objid, $tag);
            }
            //удалим незатронутые теги
            objtag::where(['sysobjid' => $sysobjid, 'objid' => $objid,
                'updated_by' => 0])->delete();
        }
        //-----------------------------------------------------------------------------------

    }
}
