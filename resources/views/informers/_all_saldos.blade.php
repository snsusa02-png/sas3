@if( isset($data->all_saldos) and count($data->all_saldos)>0)

    <style>
        .a-modest {
            box-shadow: inset 0 0 0 0 #54b3d6;
            color: #54b3d6;
            margin: 0 -.25rem;
            padding: 0 .25rem;
            transition: color .3s ease-in-out, box-shadow .3s ease-in-out;
        }
        .a-modest:hover {
            box-shadow: inset 100px 0 0 0 yellow;
            color: white;
        }
    </style>
    <div class="col-lg-6 col-md-6 col-sm-12">
        <div class="card card-stats mt-3">
            <div class="card-header">
                <i class="fa fa-balance-scale text-danger" aria-hidden="true"></i>
                Текущий Баланс
                <ul class="nav nav-tabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" data-toggle="tab" href="#neg_balance">нам должны</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" data-toggle="tab" href="#zero_balance">баланс</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link " data-toggle="tab" href="#pos_balance">мы должны</a>
                    </li>
                </ul>

            </div>
            <div class="card-body" style="">
            <?php
            $retURL = Request::url();

            $userid = \Auth::user()->id;
            $usrrights = [];
            $usrrights['link_tasks'] = \App\usrsysright::isUserHasRightByCode_cached($userid, 'tasks.create');;

            $cur_tab_id = '-1';
            ?>
            <!-- Tab panes -->
                <div class="tab-content">
                    @foreach($data->all_saldos as $itm)
                        <?php
                        $td_class = ($itm->saldo < 0) ? 'text-danger' : (($itm->saldo > 0) ? 'text-success' : '');

                        if ($itm->saldo < 0) {
                            $saldo_title = "Задолженность клиентов";
                            $tab_id = 'neg_balance';
                            $tab_class = 'active';
                        } elseif ($itm->saldo == 0) {
                            $saldo_title = "Баланс с клиентами";
                            $tab_id = 'zero_balance';
                            $tab_class = 'fade';
                        } else {
                            $saldo_title = "Авансирование от клиентов";
                            $tab_id = 'pos_balance';
                            $tab_class = 'fade';
                        }
                        ?>
                        @if($tab_id<>$cur_tab_id)
                            @if($cur_tab_id<>'-1')
                </div>
                @endif
                <div id="{{$tab_id}}" class="tab-pane {{$tab_class}}">
                    <?php
                    $cur_tab_id = $tab_id;
                    ?>
                    @endif
                    <div class="row mb-3">
                        <div class="col-md-5"><a
                                href="{{route('reports.rep48',[$itm->ownorgid,$itm->orgid])}}?returl={{$retURL}}"
                                class="text-decoration-none" title="показать детализацию по фин. транзакциям">{{$itm->orgname}}</a>
{{--                            <a href="{{route('reports.rep53',[$itm->ownorgid,$itm->orgid])}}?returl={{$retURL}}"--}}
{{--                               class="ml-1 text-decoration-none" title="по услугам и платежам">...</a>--}}
                            <span class="small text-right ml-1" title="Куратор">{{$itm->org_curators}}</span>
                        </div>
                        <div class="col-md-4 small">{{$itm->ownorgname}}
                            <div class="float-right">
                                @if( $usrrights['link_tasks']??false )
                                    <a href="{{ route('tasks.create')}}?srcsysobjid=111&srcobjid={{$itm->orgid}}&returl={{Request::url()}}"
                                       class=""
                                       title="Создать задачу">
                                        <i class="fa fa-plus-circle text-info text-right" aria-hidden="true"></i>
                                    </a>
                                @endif
                                @if(1==1 and isset($itm->tasks))
                                    <?php
                                    $tasks = explode(';', $itm->tasks);
                                    ?>
                                    <label class="small mb-0">Задачи:</label>
                                    <ul class="mb-1" style="border-top: 1px solid silver; ">
                                        @foreach($tasks as $task)
                                            <?php
                                            $titm = explode('|', $task);
                                            ?>
                                            <li><a href="{{route('tasks.edit',$titm[1]??0)}}?returl={{Request::url()}}"
                                                   style1="color: firebrick"
                                                   target="_blank">{{$titm[0]}}</a></li>
                                        @endforeach
                                    </ul>
                                @endif

                            </div>
                        </div>
                        <div class="col-md-3 text-right font-weight-bold text-nowrap {{$td_class}}"
                             title="{{$saldo_title}}"
                             style="font-size: 16px">
                            <a href="{{route('reports.rep53',[$itm->ownorgid,$itm->orgid])}}?returl={{$retURL}}"
                               class="ml-1 text-decoration-none a-modest" style="color: inherit"
                               title="показать детализацию по услугам и платежам">{{number_format($itm->saldo,0)}}</a>
                        </div>
                    </div>
                    @endforeach
                </div>
                <div id="zero_balance" class=" tab-pane fade">12312312</div>
            </div>
        </div>

    </div>
    </div>
@endif
