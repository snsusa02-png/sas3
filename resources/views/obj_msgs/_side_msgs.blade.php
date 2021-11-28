<?php
if (!isset($thisSysObjId) and isset($sysobjid))
    $thisSysObjId = $sysobjid;
?>

@if( 1==1 and isset($rec) and isset($rec->id) and ($rec->id!=-1) and isset($thisSysObjId))
    <?php
    $userid = \Auth::user()->id;
    ?>

    <link href="{{ asset('css/_msgs.css') }}" rel="stylesheet">
    <style>
        .dimmer {
            position: fixed;
            top: 0;
            left: 0;
            width: 100vw;
            height: 100vh;
            transition: all 0.9s ease-in;
            /*background-color: rgba(0,0,0,0.0);*/
            animation: mymove 2s;
            animation-iteration-count: 1;
        }

        @keyframes mymove {
            from {
                background-color: rgba(0, 0, 0, 0.0);
            }
            to {
                background-color: rgba(0, 0, 0, 0.5);
            }
        }

        .new_senders {
            font-size: 24px;
            cursor: pointer;
            position: fixed;
            z-index: 1;
            bottom: 4.5em;
            left: 0.5em;
            background-color: rgba(250, 250, 250, 0.8);
            padding: 0.2em 0.5em;
            transition: all 0.4s ease-in;
        }

        .new_senders.badge {
            font-size: 12px;
        }

        .new_senders._hide {
            opacity: 0;
            left: -60px;
        }

        .menu_starter {
            font-size: 24px;
            cursor: pointer;
            position: fixed;
            z-index: 1;
            bottom: 1.5em;
            left: 0.5em;
            background-color: rgba(250, 250, 250, 0.8);
            padding: 0.2em 0.5em;
            transition: all 0.4s ease-in;
        }

        .menu_starter.badge {
            font-size: 12px;
        }

        .menu_starter._hide {
            opacity: 0;
            left: -60px;
        }

        .sidenav {
            /*height: 100%;*/
            /*width: 0;*/
            width: 500px;
            position: fixed;
            z-index: 10;
            bottom: -1em;
            left: -42em;
            /*opacity: 0;*/
            /*background-color: #d5f0f9;*/
            overflow-x: hidden;
            /*transition: 0.5s;*/
            /*padding-top: 60px;*/
            transition: all 0.5s ease-in;
            /*display: block;*/

        }

        .sidenav._show {
            bottom: 8em;
            left: 3em;
        }

        .sidenav a {
            /*padding: 8px 8px 8px 32px;*/
            /*text-decoration: none;*/
            /*font-size: 25px;*/
            /*color: #818181;*/
            /*display: block;*/
            /*transition: 0.3s;*/
        }

        .sidenav a:hover {
            /*color: steelblue;*/
        }

        .sidenav .closebtn {
            /*position: absolute;*/
            /*z-index: 12;*/
            /*top: 0;*/
            /*right: 25px;*/
            /*font-size: 24px;*/
            /*margin-left: 50px;*/
        }

        @media screen and (max-height: 450px) {
            .sidenav {
                padding-top: 15px;
            }

            .sidenav a {
                /*font-size: 18px;*/
            }
        }
    </style>

    <?php
    //Обновим статистику открытий и получим время предыдущего визита
    // - для последующих решений об акцентировании новинок

    $rec_user_lastread_at = \App\obj_reader::addOrUpdateStat($thisSysObjId, $rec->id, $userid);

    //кол-во всех новых сообщений для пользователя, не обязательно для данной формы
//    $allnewMsgCnt = \App\objmsg_rcpt::from('objmsg_rcpts as r')
//        ->whereNull('r.read_at')
//        ->where('r.rcptuserid', $userid)
//        ->count();

    $allnewMsgCnt = 0;

    // подсчитаем новые (для меня) сообщения ----------------------------------------------
    $newMsgCnt = \App\objmsg_rcpt::from('objmsg_rcpts as r')
        ->whereNull('r.read_at')
        ->where('r.rcptuserid', $userid)
        ->whereRaw("exists(select 1 from obj_msgs as m
            where m.id=r.objmsgid and m.sysobjid={$thisSysObjId} and m.objid=" . $rec->id . ")")
        ->count();
    //dd($newMsgCnt);
    //dd($newMsgCnt,isset($rec->user_lastread_at));
    if ($newMsgCnt == 0) {
        //попробуем определить новинки для безадресных сообщений - сравнив со временем предыдущего открытия
        $newMsgCnt = \App\obj_msg::from('obj_msgs as m')
            ->where('m.sysobjid', $thisSysObjId)
            ->where('m.objid', $rec->id);

        if (!is_null($rec_user_lastread_at))
            $newMsgCnt = $newMsgCnt->where('m.sent_at', '>=', $rec_user_lastread_at);

        $newMsgCnt = $newMsgCnt->count();
        //$newMsgCnt=0;
        //dd($newMsgCnt);
    }

    $rec_msgs = \App\obj_msg::where(['sysobjid' => $thisSysObjId, 'objid' => $rec->id])
        ->orderBy('sent_at')->get();

    $badge_cnt = ($newMsgCnt > 0) ? $newMsgCnt : count($rec_msgs);
    ?>
    @if($allnewMsgCnt>0)
        <div class="new_senders rounded shadow-sm {{($allnewMsgCnt>0)?'':'_hide'}} d-print-none"
             onclick="openNav()" id="myMsgSidenav_opn">
            <i class="fa fa-envelope {{($allnewMsgCnt>0)?'fa-spin text-success':'text-info'}} "
               aria-hidden="true"></i>
            <sup><span class="badge badge-pill {{($allnewMsgCnt>0)?'badge-warning':'badge-secondary'}}"
                       style="margin-left: -12px;">{{$allnewMsgCnt}}</span></sup>
        </div>
    @endif

    <div class="menu_starter rounded shadow-sm {{($newMsgCnt>0)?'_hide':''}} d-print-none"
         onclick="openNav()" id="myMsgSidenav_opn">
        <i class="fa fa-envelope {{($newMsgCnt>0)?'fa-spin text-success':'text-info'}} "
           aria-hidden="true"></i>
        <sup><span class="badge badge-pill {{($newMsgCnt>0)?'badge-warning':'badge-secondary'}}"
                   style="margin-left: -12px;">{{$badge_cnt}}</span></sup>
    </div>

    <div id="myMsgSidenav" class="sidenav shadow-lg {{($newMsgCnt>0)?'_show':''}}">

    {{--        <div class="container000">--}}
    {{--            <div class="row bootstrap snippets bootdeys">--}}
    {{--                <div class="col-md-12">--}}

    <!-- DIRECT CHAT PRIMARY -->
        <div class="box box-primary direct-chat direct-chat-primary">
            <div class="box-header with-border">
                <h3 class="box-title"><i class="fa fa-comments" aria-hidden="true"></i> Обсуждение</h3>

                <div class="box-tools pull-right float-right">

                    @if (isset($rec_msgs) and count($rec_msgs)>0)

                        <?php
                        $show_class = ($newMsgCnt > 0) ? "show" : "";
                        //dd($show_class);
                        $badge_cnt_class = ($newMsgCnt > 0) ? "badge-warning" : "badge-info";
                        // -------------------------------------------------------------------------------------
                        ?>

                        <button type="button" class="btn btn-box-tool000 btn-light btn-sm"
                                data-toggle="collapse" data-target="#_msgs"
                                class="btn btn-light btn-sm "><i class="fa fa-eye-slash"
                                                                 aria-hidden="true"></i>
                            <span
                                    class="badge {{$badge_cnt_class}}">{{$newMsgCnt}}/{{count($rec_msgs)}}</span>
                        </button>
                    @endif
                    {{--							<button type="button" class="btn btn-box-tool"--}}
                    {{--									data-widget="collapse" data-target="#_msgs"><i--}}
                    {{--										class="fa fa-minus"></i>--}}
                    {{--							</button>--}}

                    {{--							<button type="button" class="btn btn-box-tool" data-toggle="tooltip" title="Contacts"--}}
                    {{--									data-widget="chat-pane-toggle">--}}
                    {{--								<i class="fa fa-comments"></i></button>--}}

                    {{--							<button type="button" class="btn btn-box-tool" data-widget="remove"><i--}}
                    {{--										class="fa fa-times"></i></button>--}}

                    <a class="closebtn0 btn btn-close btn-light btn-sm"
                       href="javascript:void(0)" onclick="closeNav()">
                        <i class="fa fa-times" aria-hidden="true"></i>
                    </a>

                </div>
            </div>

            <form method="post" action="{{route('obj_msgs.add')}}">

                <!-- /.box-header -->
                <div class="box-body collapse {{$show_class??''}}" id="_msgs">

                    <!-- Conversations are loaded here -->
                    <div class="direct-chat-messages">

                    <?php
                    $msg_class = [
                        'author-0',
                        'author-4',
                        'author-5',
                        'author-1',
                        'author-2',
                        'author-3',
                    ];

                    $author_colors = [];
                    $author_colors[$userid] = 'author-0';
                    $nxt_class = 1;
                    ?>
                    @if(isset($rec_msgs))
                        @foreach($rec_msgs as $msg)
                            <?php
                            $avatar_img = $msg->author->image
                                ?? ((!$msg->author->sex)
                                    ? '/images/signs/user-no-photo.jpg'
                                    : (($msg->author->sex == 'M')
                                        ? '/images/signs/user_male.jpg'
                                        : '/images/signs/user_female.jpg'));

                            if (!isset($author_colors[$msg->from_userid])) {
                                $author_colors[$msg->from_userid] = $msg_class[$nxt_class];
                                if ($nxt_class < count($msg_class))
                                    $nxt_class++;
                                else
                                    $nxt_class = 1;
                            }

                            $newmsg_class = ($msg->sent_at >= $rec_user_lastread_at) ? 'new-msg' : '';
                            ?>

                            <!-- Single Comment -->
                            @if(!isset($msg->replyto_id))
                                <!-- Message. Default to the left -->
                                    <div class="direct-chat-msg" data-id='{{$msg->id}}'>
                                        <div class="direct-chat-info clearfix">
                                                        <span
                                                                class="direct-chat-name pull-left">{{ $msg->author->fullname }}</span>
                                            <span
                                                    class="direct-chat-timestamp pull-right">{{ date_create($msg->sent_at)->format('d.m.Y в H:i') }}</span>
                                            @if(count($msg->recipients)>0)
                                                <div class="ml-3 small"><br>для:
                                                    @foreach($msg->recipients as $rcpt)
                                                        {{($loop->index>0)?', ':''}}
                                                        {{$rcpt->user->fullname}}
                                                        @if(isset($rcpt->read_at))
                                                            <span class=" text-success"
                                                                  title="Прочитано">({{ date_create($rcpt->read_at)->format('H:i d.m.Y') }})</span>
                                                        @elseif($rcpt->rcptuserid==$userid)
                                                            <span class="text-danger font-weight-bold"><i
                                                                        class="fa fa-bell-o"
                                                                        aria-hidden="true"></i> новое!</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <!-- /.direct-chat-info -->
                                        <img class="direct-chat-img" src="{{$avatar_img}}"
                                             alt="Message User Image"><!-- /.direct-chat-img -->
                                        <div class="direct-chat-text {{$newmsg_class}}">
                                            {{ $msg->body }}
                                            @if($msg->sent_at>=$rec_user_lastread_at)
                                                <span class="new-msg font-weight-bold ml-3 float-right"
                                                      title="Новое для Вас"><i
                                                            class="fa fa-bell-o"
                                                            aria-hidden="true"></i></span>
                                            @endif
                                        </div>
                                        <!-- /.direct-chat-text -->
                                    </div>
                                    <!-- /.direct-chat-msg -->
                            @else
                                <!-- Message to the right -->
                                    <div class="direct-chat-msg right" data-id='{{$msg->id}}'>
                                        <div class="direct-chat-info clearfix">
                                                        <span
                                                                class="direct-chat-name pull-right">{{ $msg->author->fullname }}</span>
                                            <span
                                                    class="direct-chat-timestamp pull-left">{{ date_create($msg->sent_at)->format('d.m.Y в H:i') }}</span>
                                            @if(count($msg->recipients)>0)
                                                <div class="ml-3 small"><br>для:
                                                    @foreach($msg->recipients as $rcpt)
                                                        {{($loop->index>0)?', ':''}}
                                                        {{$rcpt->user->fullname}}
                                                        @if(isset($rcpt->read_at))
                                                            <span class=" text-success"
                                                                  title="Прочитано">({{ date_create($rcpt->read_at)->format('H:i d.m.Y') }})</span>
                                                        @elseif($rcpt->rcptuserid==$userid)
                                                            <span class="text-danger font-weight-bold"><i
                                                                        class="fa fa-bell-o"
                                                                        aria-hidden="true"></i> новое!</span>
                                                        @endif
                                                    @endforeach
                                                </div>
                                            @endif
                                        </div>
                                        <!-- /.direct-chat-info -->
                                        <img class="direct-chat-img" src="{{$avatar_img}}"
                                             alt="Message User Image"><!-- /.direct-chat-img -->

                                        <div class="direct-chat-text {{$author_colors[$msg->from_userid]}}">
                                            {{ $msg->body }}
                                        </div>
                                        <!-- /.direct-chat-text -->
                                    </div>
                                    <!-- /.direct-chat-msg -->
                                @endif
                                @if($msg->from_userid <> $userid)
                                    <label class="small float-right"><input type="radio" name="replyto_id"
                                                                            value="{{$msg->id}}"
                                                                            class="reply-to  "
                                                                            title="Отметить для ответа на это сообщение">
                                        Ответить</label>
                                    <br>
                                @endif
                            @endforeach

                            <?php
                            // отметим прочтение сообщений ---------------------------------------------------------
                            if (1 == 1) {
                                \App\objmsg_rcpt::from('objmsg_rcpts as r')
                                    ->whereNull('r.read_at')
                                    ->where('r.rcptuserid', $userid)
                                    ->whereRaw("exists(select 1 from obj_msgs as m where m.id=r.objmsgid and m.sysobjid={$thisSysObjId} and m.objid=" . $rec->id . ")")
                                    ->update(['read_at' => now()]);
                            }
                            // -------------------------------------------------------------------------------------
                            ?>
                        @endif
                    </div>
                    <!--/.direct-chat-messages-->

                    <!-- /.direct-chat-pane -->
                </div>
                <!-- /.box-body -->

                <div class="box-footer">
                    {{--						<form method="post" action="{{route('obj_msgs.add')}}">--}}
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <input type="hidden" name="sysobjid" value="{{$thisSysObjId}}">
                    <input type="hidden" name="objid" value="{{ $rec->id }}">

                    <div class="input-group mb-1">
                        <input type="text" name="body" placeholder="Пишите здесь ..." class="form-control"
                               required>
                        <span class="input-group-btn">
<button type="submit" class="btn btn-success btn-flat" title="Отправить сообщение"><i
            class="fa fa-paper-plane"
            aria-hidden="true"></i></button>
</span>
                    </div>

                    <div id="contact-list" class="offset-md-2 col-md-9">

                        <div class="input-group mb-1 input-group-sm">
                            <div class="input-group-prepend">
				<span class="input-group-text"><i class="fa fa-address-card-o"
                                                  aria-hidden="true"></i></span>
                            </div>
                            <input type="text" name="username[]"
                                   class="username form-control small"
                                   placeholder="уведомить пользователя ...">
                            <input type="text" class="form-control text-center small ac_status"
                                   style="display: none; border: #d7f3e3;" readonly>
                            <input type="hidden" name="userid[]" class="userid">
                            <div class="input-group-append">
				<span class="input-group-text"><i
                            class="fa fa-plus"
                            aria-hidden="true" id="add2contactlist" style="cursor: pointer"></i></span>
                            </div>
                        </div>

                    </div>


                </div>
                <!-- /.box-footer-->

            </form>
        </div>
        <!--/.direct-chat -->

        {{--                </div>--}}
        {{--            </div>--}}
        {{--        </div>--}}
    </div>



    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
    {{--	<script src="{{ asset('js/callListStaff.js') }}" defer></script>--}}
    <script src="{{ asset('js/obj_msgs_edit.js') }}" defer></script>

    <script>
        function dimBody() {
            var dimmer = document.createElement("DIV");
            dimmer.className = "dimmer";
            dimmer.setAttribute("id", "dimmer");
            dimmer.style.backgroundColor = 'rgba(0,0,0,0.5)';
            document.body.appendChild(dimmer);
        }

        function openNav() {

            dimBody();
            document.getElementById("myMsgSidenav_opn").style.opacity = "0";
            document.getElementById("myMsgSidenav_opn").style.left = "-60px";

            document.getElementById("myMsgSidenav").style.opacity = "1";
            document.getElementById("myMsgSidenav").style.left = "3em";
            document.getElementById("myMsgSidenav").style.bottom = "8em";
            document.getElementById("myMsgSidenav").style.width = "500px";
        }

        function closeNav() {
            document.getElementById("myMsgSidenav").style.opacity = "0";
            document.getElementById("myMsgSidenav").style.left = "-1em";
            document.getElementById("myMsgSidenav").style.bottom = "-1em";
            document.getElementById("myMsgSidenav").style.width = "0px";

            document.getElementById("myMsgSidenav_opn").style.opacity = "1";
            document.getElementById("myMsgSidenav_opn").style.left = "0.5em";
            document.body.removeChild(document.getElementById("dimmer"));
            ;
        }

        @if($newMsgCnt>0)
        dimBody();
        @endif

        // const refreshInterval = setInterval(function () {
        //     grabMessages()
        //     alert("Hello");
        // }, 1000)

        function grabMessages() {
            //id: , sender, message, sent_at
            fetch("https://fierce-wave-66462.herokuapp.com/messages")
                .then(resp => resp.json())
                .then(function (messages) {
                    renderMessages(messages)
                })
        }

        function postMessage(form) {
            fetch("https://fierce-wave-66462.herokuapp.com/messages", {
                method: "POST",
                headers: {
                    "Content-Type": "application/json"
                },
                body: JSON.stringify({
                    sender: form.sender.value,
                    message: form.message.value
                })
            })
                .then(resp => resp.json())
                .then(function (json) {
                    grabMessages()
                    form.message.value = ""
                })
        }

        function renderMessages(messages) {
            const length = messages.length
            //const mostRecent = messages.slice(length - 10)
            var mostRecent;
            const list = document.querySelector("#message-list")
            //  console.log(list.getElementsByTagName("li").length);
            if (list.getElementsByTagName("li").length == 0)
            //возьмем все сообщения
                mostRecent = messages;
            else
                mostRecent = messages.slice(length - 10);

            let newList = ""
            mostRecent.forEach(message => {
                if (!!!document.querySelector(`li[data-id='${message.id}']`)) {
                    newList += makeLi(message)
                }
            })
            if (newList != "") {
                list.innerHTML += newList
            }
        }

        function makeLi(message) {
            // Some logic to make your own messages say You instead of your name
            let sender = document.querySelector("#sender").value
            if (message.sender == sender) {
                sender = "You"
            } else {
                sender = message.sender
            }
            //prettier-ignore
            return `<li data-id='${message.id}'>[${message.created_at}] <strong>${sender}:</strong> ${message.message}</li>`;
        }
    </script>

@endif
