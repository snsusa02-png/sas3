<?php

namespace App;

use App\Traits\DeleteTrait;
use Illuminate\Database\Eloquent\Model;

class obj_link extends Model
{
    use DeleteTrait;

    static public $prefix = 'obj_links';
    static public $sysobjid = 816;

    protected $guarded = [];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'id' => 'integer',
        //'read_cnt' => 'integer',
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

    public function lnksysobj()
    {
        return $this->hasOne(sysobj::class, 'id', 'lnksysobjid');
    }

    public function lnkobj()
    {
        $model = $this->lnksysobj->model_class;

        return $this->hasOne('App\\' . $model, 'id', 'lnkobjid');
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


    public static function addOrUpdate($sysobjid, $objid, $objrolename,
                                       $lnksysobjid, $lnkobjid, $lnkobjrolename, $linktypeid = null)
    {
        //Обновление связи между парой объектов

        $userid = \Auth::user()->id;
        //dd($sysobjid, $objid, $lnksysobjid, $lnkobjid, $userid);

        //Создает или обновляет существующую запись
        if (isset($sysobjid) and isset($objid)
            and isset($lnksysobjid) and isset($lnkobjid)
            and $objid <> -1 and $lnkobjid <> -1) {

            $lnk = self::where([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'lnksysobjid' => $lnksysobjid,
                'lnkobjid' => $lnkobjid
            ])->first();

            if (!isset($lnk)) {
                $lnk = new obj_link([
                    'sysobjid' => $sysobjid,
                    'objid' => $objid,
                    'lnksysobjid' => $lnksysobjid,
                    'created_by' => $userid,
                    'created_at' => now(),
                ]);
            }
            $lnk->objrolename = $objrolename;
            $lnk->lnkobjid = $lnkobjid;
            $lnk->linktypeid = $linktypeid;
            $lnk->lnkobjrolename = $lnkobjrolename;
            $lnk->updated_by = $userid;
            $lnk->updated_at = now();
            $lnk->save();

            //обратная связь
            $lnk = self::where([
                'sysobjid' => $lnksysobjid,
                'objid' => $lnkobjid,
                'lnksysobjid' => $sysobjid,
                'lnkobjid' => $objid
            ])->first();

            if (!isset($lnk)) {
                $lnk = new obj_link([
                    'sysobjid' => $lnksysobjid,
                    'objid' => $lnkobjid,
                    'lnksysobjid' => $sysobjid,
                    'created_by' => $userid,
                    'created_at' => now(),
                ]);
            }
            $lnk->objrolename = $lnkobjrolename;    //с перекрестом
            $lnk->lnkobjid = $objid;
            $lnk->lnkobjrolename = $objrolename;
            $lnk->linktypeid = $linktypeid;
            $lnk->updated_by = $userid;
            $lnk->updated_at = now();
            $lnk->save();

            return 1;
        }
        return 0;
    }


    public
    static function addOrUpdateSingle($sysobjid, $objid, $lnksysobjid, $lnkobjid, $linktypeid = null)
    {
        //Обновление ЕДИНСТВЕННОЙ связи между парой системных объектов

        $userid = \Auth::user()->id;
        //dd($sysobjid, $objid, $lnksysobjid, $lnkobjid, $userid);

        //Создает или обновляет существующую запись
        if (isset($sysobjid) and isset($objid)
            and isset($lnksysobjid)
            and $objid <> -1 and $lnkobjid <> -1) {

            $lnk = self::where([
                'sysobjid' => $sysobjid,
                'objid' => $objid,
                'lnksysobjid' => $lnksysobjid
                //, 'lnkobjid' => $lnkobjid,
            ])->first();

            if (!isset($lnkobjid)) {
                // нужно удалить связь
                if (isset($lnk))
                    $lnk->delete();

            } else {

                if (!isset($lnk)) {
                    $lnk = new obj_link([
                        'sysobjid' => $sysobjid,
                        'objid' => $objid,
                        'lnksysobjid' => $lnksysobjid,
                        'created_by' => $userid,
                        'created_at' => now(),
                    ]);
                }
                $lnk->lnkobjid = $lnkobjid;
                $lnk->linktypeid = $linktypeid;
                $lnk->updated_by = $userid;
                $lnk->updated_at = now();
                $lnk->save();
            }

        }
    }

    public
    static function addOrUpdateSingleWithParams($sysobjid, $objid, $lnksysobjid, $lnkobjid, $params = [])
    {
        //Обновление ЕДИНСТВЕННОЙ связи между парой системных объектов с установкой доп. значений

        $userid = \Auth::user()->id;
        //dd($sysobjid, $objid, $lnksysobjid, $lnkobjid, $userid);
        //Создает или обновляет существующую запись
        if (isset($sysobjid) and isset($objid)
            and isset($lnksysobjid)
            and $objid <> -1 and $lnkobjid <> -1) {

            $lnk = self::where([
                'sysobjid' => $sysobjid, 'objid' => $objid,
                'lnksysobjid' => $lnksysobjid
                //, 'lnkobjid' => $lnkobjid,
            ])->first();

            //dd($lnk,$lnkobjid);
            if (!isset($lnkobjid)) {
                // нужно удалить связь
                if (isset($lnk))
                    $lnk->delete();

            } else {

                //нормируем длину значения
                if(isset($params['name']))
                    $params['name'] = mb_substr($params['name'],0,90);


                if (!isset($lnk)) {
                    $lnk = new obj_link([
                        'sysobjid' => $sysobjid,
                        'objid' => $objid,
                        'lnksysobjid' => $lnksysobjid,
                        'created_by' => $userid,
                        'created_at' => now(),
                    ]);
                }
                $lnk->lnkobjid = $lnkobjid;
                $lnk->updated_by = $userid;
                $lnk->fill($params);
                $lnk->updated_at = now();
                $lnk->save();
            }

        }
    }

    public
    static function getFirstLnkId($sysobjid, $objid, $lnksysobjid)
    {
        //возвращает значение objid для связанной записи указанного типа

        if (isset($sysobjid) and isset($objid) and isset($lnksysobjid) and $objid <> -1) {

            $lnk = self::where([
                'sysobjid' => $sysobjid, 'objid' => $objid,
                'lnksysobjid' => $lnksysobjid,
            ])->first();
            if (isset($lnk))
                return $lnk->lnkobjid;

        }
        return null;
    }

}
