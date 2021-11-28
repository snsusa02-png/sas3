<?php

namespace App;

use App\Events\notifyEvent;
use Illuminate\Database\Eloquent\Model;


class obj_msg extends Model
{
    static public $prefix = 'obj_msgs';
    static public $sysobjid = 810;

    protected $guarded = [];

    public function author()
    {
        return $this->belongsTo(User::class, 'from_userid');
    }

    public function recipients()
    {
        return $this->hasMany(objmsg_rcpt::class, 'objmsgid', 'id')
            ->with('user');
    }

    static public function add($input, $recipients)
    {

        if (isset($input['sysobjid']) and isset($input['objid'])) {

            $userid = \Auth::user()->id;

            $input['from_userid'] = $userid;
            //$input['subj'] = $request->input('subj') ?? ' ';
            //$input['body'] = $body;
            $input['sent_at'] = now();
            //$input['replyto_id'] = $request->input('replyto_id');

            //Сохранение сообщения
            $msg = obj_msg::create($input);

            objlog::log_info(self::$sysobjid, $msg->id, "отправлено сообщение", 5);

            //сохранение получателей
            foreach ($recipients as $recipient_id) {
                if (isset($recipient_id)) {
                    //dd($msg, $recipient_id);
                    $rec = objmsg_rcpt::create(['objmsgid' => $msg->id, 'rcptuserid' => $recipient_id]);

                    //отправим уведомление о сообщении на почту участника
                    event(new notifyEvent('user.new_message', self::$sysobjid, $msg->id, $recipient_id));
                }
            }
        }
    }


    static public function unread_msgs($userid)
    {
        if (isset($userid)) {
            $msgs = self::from('obj_msgs as m')
                ->join('objmsg_rcpts as mr', 'mr.objmsgid', 'm.id')
                ->join('sysobjs as so', 'so.id', 'm.sysobjid')
                ->whereNull('mr.read_at')
                ->where('m.active', 1)
                ->where('mr.rcptuserid', $userid)
                ->select('m.*', 'so.code')
                ->orderby('m.sent_at','desc')
                ->with('author')
                ->get();
            foreach ($msgs as $itm) {
                $itm->ref_url = route($itm->code . '.edit', $itm->objid);
            }
            //dd($msgs);
            return $msgs;
        }
        return null;
    }
}
