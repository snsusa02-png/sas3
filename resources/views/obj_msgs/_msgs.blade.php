@if( 1==1 and isset($rec) and ($rec->id!=-1))

    <?php
    $userid = \Auth::user()->id;

    ?>

    <link href="{{ asset('css/_msgs.css') }}" rel="stylesheet">

    <div class="container000">
        <div class="row bootstrap snippets bootdeys">
            <div class="col-md-12">
                <!-- DIRECT CHAT PRIMARY -->
                <div class="box box-primary direct-chat direct-chat-primary mt-3">
                    <div class="box-header with-border">
                        <h3 class="box-title"><i class="fa fa-comments" aria-hidden="true"></i> Обсуждение</h3>

                        <div class="box-tools pull-right">

                            @if (isset($rec->msgs) and count($rec->msgs)>0)

                                <?php
                                // подсчитаем новые (для меня) сообщения ----------------------------------------------
                                $newMsgCnt = \App\objmsg_rcpt::from('objmsg_rcpts as r')
                                    ->whereNull('r.read_at')
                                    ->where('r.rcptuserid', $userid)
                                    ->whereRaw("exists(select 1 from obj_msgs as m where m.id=r.objmsgid and m.sysobjid={$sysobjid} and m.objid=" . $rec->id . ")")
                                    ->count();
                                //dd($newMsgCnt);

                                //dd($newMsgCnt,isset($rec->user_lastread_at));
                                if ($newMsgCnt == 0) {
                                    //попробуем определить новинки для безадресных сообщений - сравнив со временем предыдущего открытия
                                    $newMsgCnt = \App\obj_msg::from('obj_msgs as m')
                                        ->where('m.sysobjid', $sysobjid)
                                        ->where('m.objid', $rec->id);

                                    if (!is_null($rec->user_lastread_at))
                                        $newMsgCnt = $newMsgCnt->where('m.sent_at', '>=', $rec->user_lastread_at);

                                    $newMsgCnt = $newMsgCnt->count();
                                    //dd($newMsgCnt);
                                }

                                $show_class = ($newMsgCnt > 0) ? "show" : "";
                                $badge_cnt_class = ($newMsgCnt > 0) ? "badge-warning" : "badge-info";

                                // -------------------------------------------------------------------------------------
                                ?>


{{--                                <span data-toggle="tooltip" title="" class="badge bg-light-blue"--}}
{{--                                      data-original-title=""--}}
{{--                                      data-toggle="collapse" data-target="#_msgs"--}}
{{--                                >--}}
{{--									@if($newMsgCnt>0)--}}
{{--                                        <span class="text-danger"><i class="fa fa-bell" aria-hidden="true"></i> {{$newMsgCnt}}</span>--}}
{{--                                    @else--}}
{{--                                        {{$newMsgCnt}}--}}
{{--                                    @endif--}}
{{--									/{{count($rec->msgs)}}--}}
{{--                                </span>--}}
                                <button type="button" class="btn btn-box-tool000 btn-light btn-sm"
                                        data-toggle="collapse" data-target="#_msgs"
                                        class="btn btn-light btn-sm "><i class="fa fa-eye-slash" aria-hidden="true"></i>
                                    <span class="badge {{$badge_cnt_class}}">{{$newMsgCnt}}/{{count($rec->msgs)}}</span>
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
                            @if(isset($rec->msgs))
                                @foreach($rec->msgs as $msg)
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

                                    $newmsg_class = ($msg->sent_at >= $rec->user_lastread_at) ? 'new-msg' : '';
                                    ?>

                                    <!-- Single Comment -->
                                    @if(!isset($msg->replyto_id))
                                        <!-- Message. Default to the left -->
                                            <div class="direct-chat-msg">
                                                <div class="direct-chat-info clearfix">
                                                    <span class="direct-chat-name pull-left">{{ $msg->author->fullname }}</span>
                                                    <span class="direct-chat-timestamp pull-right">{{ date_create($msg->sent_at)->format('d.m.Y в H:i') }}</span>
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
                                                    @if($msg->sent_at>=$rec->user_lastread_at)
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
                                            <div class="direct-chat-msg right">
                                                <div class="direct-chat-info clearfix">
                                                    <span class="direct-chat-name pull-right">{{ $msg->author->fullname }}</span>
                                                    <span class="direct-chat-timestamp pull-left">{{ date_create($msg->sent_at)->format('d.m.Y в H:i') }}</span>
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
                                            ->whereRaw("exists(select 1 from obj_msgs as m where m.id=r.objmsgid and m.sysobjid={$sysobjid} and m.objid=" . $rec->id . ")")
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
                            <input type="hidden" name="sysobjid" value="{{$sysobjid}}">
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
            </div>

        </div>
    </div>



    <link href="{{ asset('css/jquery-ui.css') }}" rel="stylesheet">
    <script src="{{ asset('js/jquery-ui.js') }}" defer></script>
    {{--	<script src="{{ asset('js/callListStaff.js') }}" defer></script>--}}
    <script src="{{ asset('js/obj_msgs_edit.js') }}" defer></script>

@endif
