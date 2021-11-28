<?php

namespace App\Http\Controllers;

use App\obj_msg;
use App\objlog;
use App\objmsg_rcpt;
use App\Services\Readers\impFileReader;
use App\sysobj;
use Illuminate\Http\Request;
use App\Events\notifyEvent;

class ObjMsgController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->sysobjid = 810;
        $this->sysobjcode = 'obj_msgs';
    }


    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //привязка сообщения к родительскому объекту
        $sysobjid = $request->input('sysobjid');
        $objid = $request->input('objid');
        $body = $request->input('body');

        if (isset($sysobjid) and isset($objid)) {


            $retURL = '';
            $sysobj = sysobj::find($sysobjid);
            if (isset($sysobj)) {
                $retURL = route($sysobj->code . ".edit", $objid);
            } else
                if ($sysobjid == 870)
                    $retURL = route("equiprqsts.edit", $objid);
                elseif ($sysobjid == 151)
                    $retURL = route("contracts.edit", $objid);
                elseif ($sysobjid == 856)
                    $retURL = route("meetings.edit", $objid);
                elseif ($sysobjid == 863)
                    $retURL = route("qcheck_items.edit", $objid);
                elseif ($sysobjid == 915)
                    $retURL = route("invoices.edit", $objid);


            if (!isset($body)) {
                return redirect($retURL)->with('error', 'Сообщение не отправлено! Укажите текст');
            }

            $userid = \Auth::user()->id;

            $input['sysobjid'] = $sysobjid;   //
            $input['objid'] = $objid;
            $input['from_userid'] = $userid;
            $input['subj'] = $request->input('subj') ?? ' ';
            $input['body'] = $body;
            $input['sent_at'] = now();
            $input['replyto_id'] = $request->input('replyto_id');
            $recipients = $request->input('userid');
            //dd($request,$recipients);

            //Сохранение сообщения
            $msg = obj_msg::create($input);
            objlog::log_info($this->sysobjid, $msg->id, "отправлено сообщение", 5);

            //сохранение получателей

            //если это ответ на чужое сообщение, то нужно проверить и включить в получатели его автора
            if (isset($input['replyto_id'])) {
                $reply = obj_msg::find($input['replyto_id']);
                if (isset($reply) and !in_array($reply->from_userid, $recipients))
                    $recipients[] = $reply->from_userid;
            }
            //dd($recipients);
            foreach ($recipients as $recipient_id) {
                if (isset($recipient_id)) {
                    //dd($msg, $recipient_id);
                    $rec = objmsg_rcpt::create(['objmsgid' => $msg->id, 'rcptuserid' => $recipient_id]);

                    //отправим уведомление о сообщении на почту участника
                    event(new notifyEvent('user.new_message', $this->sysobjid, $msg->id, $recipient_id));
                }
            }

            return redirect($retURL)->with('message', 'Сообщение добавлено');
        }
        return redirect("/home")->with('error', 'Сообщение не добавлено!');
    }

    /**
     * Display the specified resource.
     *
     * @param \App\obj_msg $obj_msg
     * @return \Illuminate\Http\Response
     */
    public function show(obj_msg $obj_msg)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\obj_msg $obj_msg
     * @return \Illuminate\Http\Response
     */
    public function edit(obj_msg $obj_msg)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param \App\obj_msg $obj_msg
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, obj_msg $obj_msg)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param \App\obj_msg $obj_msg
     * @return \Illuminate\Http\Response
     */
    public function destroy(obj_msg $obj_msg)
    {
        //
    }
}
