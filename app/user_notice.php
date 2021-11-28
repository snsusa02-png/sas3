<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use App\Traits\DeleteTrait;
use App\Traits\Result;
use Cache;

class user_notice extends Model
{
    use \App\Traits\DeleteTrait;

    static public $prefix = 'user_notices';
    static public $sysobjid = 880;

    //'это обратное к $fillable. то есть все поля становятся заполняемыми
    protected $guarded = [];


    public static function addOrUpdate($eventtypeid, $ref_url, $to_userid, $subj, $msg
        , $begdt = null, $enddt = null, $userid = null)
    {
        //Создает или обновляет существующую запись

        if (isset($eventtypeid) and isset($ref_url) and isset($to_userid)) {

            $userid = ($userid ?? \Auth::user()->id ?? 1);

            $notice = self::where([
                'eventtypeid' => $eventtypeid,
                'ref_url' => $ref_url,
                'to_userid' => $to_userid,
            ])
                ->first();

            if (!isset($notice)) {
                $notice = new user_notice([
                    'eventtypeid' => $eventtypeid,
                    'ref_url' => $ref_url,
                    'to_userid' => $to_userid,
                    'created_by' => $userid,
                ]);
            }
            $notice->subj = $subj;
            $notice->msg = mb_substr($msg,0,300);
            $notice->begdt = $begdt ?? now();
            $notice->enddt = $enddt;
            $updated_by = $userid;
            $notice->save();
        }

    }

    public static function Remove($eventtypeid, $ref_url, $to_userid = null)
    {
        //Удаляет заданную запись
        // 2021-03-04 SNS. Если получатель($to_userid) не задан, то для всех получателей

        //if (isset($eventtypeid) and isset($ref_url) and isset($to_userid)) {
        if (isset($eventtypeid) and isset($ref_url)) {
            $sc = (isset($to_userid)) ? "to_userid={$to_userid}" : "1=1";
            $cnt = self::where([
                'eventtypeid' => $eventtypeid,
                'ref_url' => $ref_url,
                //'to_userid' => $to_userid,
            ])
                ->whereRaw($sc)
                ->delete();
        }
    }

    public static function removeByEventID($eventtypeid)
    {
        //Удаляет все записи с указанным eventid

        if (isset($eventtypeid)) {
            self::where([
                'eventtypeid' => $eventtypeid,
            ])->delete();
        }
    }

    public static function removeEnded()
    {
        //Удаляет все просроченные записи
        self::where('enddt', '<', now())->delete();
    }


}
