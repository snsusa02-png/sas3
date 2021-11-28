<?php

namespace App;

use App\mychat_user;
use App\objlog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Http;
use \Exception;
use Illuminate\Support\Facades\Log;


class myChat extends Model
{
    //
    //static public $server_API_URL = 'http://192.168.33.112/API/';
    static public $server_API_URL = 'http://86.102.115.246:11280/API/';
    static public $server_API_key = 'test345test123';


    static public function UINbyUserID($userid)
    {
        if (isset($userid))
            return mychat_user::where(['userid' => $userid, 'active' => 1])->first()->uin ?? null;
        return null;
    }


    static public function sendMsg($src_sysobjid, $src_objid, $msg, $to_userid, $from_userid = null)
    {
        //$userid = \Auth::user()->id ?? null;

        if (1 == 1 and isset($msg) and isset($to_userid)) {

            $to_UIN = self::UINbyUserID($to_userid);
            if (isset($to_UIN)) {

                if (isset($from_userid))
                    $from_UIN = self::UINbyUserID($from_userid);
                else
                    $from_UIN = 0;

                $json = json_encode(array(
                        'cmd' => "0002",
                        'UserTo' => $to_UIN,
                        'UserFrom' => $from_UIN,
                        'Msg' => $msg,
                        'APIStype' => 'sendMsg',
                        'ServerKey' => self::$server_API_key,
                        'MsgType' => 1
                    )
                );
                //3 - MSG_TYPE_BEEP
                //$json = urlencode($json);

                //http://192.168.33.112/API/?data={%22cmd%22:%220002%22,%22ServerKey%22:%22test345test123%22,%22APIStype%22:%22mydata%22,%22UserFrom%22:%220%22,%22UserTo%22:%222%22,%22Msg%22:%22%D0%A2%D0%B5%D1%81%D1%82%20123%22,%22MsgType%22:1}
                //http://86.102.115.246:11280/API/?data={%22cmd%22:%220002%22,%22ServerKey%22:%22test345test123%22,%22APIStype%22:%22mydata%22,%22UserFrom%22:%220%22,%22UserTo%22:%222%22,%22Msg%22:%22%D0%A2%D0%B5%D1%81%D1%82%20123%22,%22MsgType%22:1}

                try {
                    $response = Http::timeout(2)->get(self::$server_API_URL, ['data' => $json]);
                    //$response = Http::get('http://192.168.33.112/API/?data=' . $json);
                    //echo($response->throw());
                    objlog::log_info($src_sysobjid, $src_objid, "MyChat-уведомление направлено на UIN {$to_UIN}", 3);
                    Log::debug("MyChat-уведомление направлено на UIN {$to_UIN}");
                    return $response->body();

                } catch (\Exception $e) {
//                    return $e->errorInfo[0];
                    $msg = "Ошибка отправки сообщения MyChat";
                    objlog::log_info($src_sysobjid, $src_objid, $msg, 2);
                    return $msg;
                }
            }
        }

    }
}
