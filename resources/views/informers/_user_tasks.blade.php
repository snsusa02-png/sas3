@if( (isset($data->user_wait_tasks) and count($data->user_wait_tasks)>0)
or (isset($data->user_exec_tasks) and count($data->user_exec_tasks)) )

    <div class="col-lg-6 col-md-6 col-sm-6">
        <div class="card card-stats mb-1 mt-3">
            <div class="card-header">
                <i class="fa fa-tasks text-danger" aria-hidden="true"></i>
                Ваши задачи
                {{--                <a href="{{route("home.refresh")}}"><i class="fa fa-refresh" aria-hidden="true"></i></a>--}}
                <span class="float-right">
{{--				<a href="{{route("orgacnt_sums.today_print")}}" class="btn btn-sm btn-warning" target="_blank" title="Печать сводки остатков на р/счетах в сжатой форме"><i class="fa fa-print" aria-hidden="true"></i></a>--}}
                    {{--				<a href="{{route("orgacnt_sums.today_print_all")}}" class="btn btn-sm btn-warning" target="_blank" title="Печать сводки остатков на р/счетах в развернутой форме"><i class="fa fa-print" aria-hidden="true"></i></a>--}}
			</span>
            </div>
            <div class="card-body "
                 style=""
            >
                @if( isset($data->user_wait_tasks) and count($data->user_wait_tasks)>0)
                    <table class="table-striped1 m-2 table-sm w-100">
                        <tr>
                            <td colspan="3" class="font-weight-bold">Ожидают выполнения</td>
                        </tr>
                        <?php
                        $cur_priority = -1;
                        $cur_days_before = -1;
                        $odd = false;
                        $priorities = [0 => 'нормальный', 1 => 'высокий']
                        ?>
                        @foreach($data->user_wait_tasks as $itm)
                            @if($itm->priority<>$cur_priority)
                                <tr>
                                    <td colspan="3" class="clearfix text-right">
                                        <span class="font-weight-bold font-italic ">
                        Приоритет: <b>{{$priorities[$itm->priority]??'?'}}</b>
                        </span>
                                    </td>
                                </tr>
                                <?php
                                $cur_priority = $itm->priority;
                                $cur_days_before = -1;
                                ?>
                            @endif
                            @if(1==0 and $itm->days_before <> $cur_days_before)
                                <?php
                                $days_before = $itm->days_before;
                                $tr_css = '';
                                if ($days_before == 0) {
                                    $days_before = 'сегодня';
                                    $tr_css = "color:red; font-weight:bold";
                                } elseif ($days_before == 1)
                                    $days_before = 'завтра';
                                elseif ($days_before == 2)
                                    $days_before = 'послезавтра';
                                elseif ($days_before < 0) {
                                    $days_before = -$days_before;
                                    $days_before = "прошло {$days_before} дн";
                                } else
                                    $days_before = "через {$days_before} дн";
                                ?>
                                <tr style="height1: 56px; background-color: #ffffc8; border-bottom:1px solid silver;">
                                    <td></td>
                                    <td colspan="3" class="clearfix" style="{{$tr_css}}">
                            <span class="font-italic ">
                        {{$days_before}} - {{date_create($itm->drctbegdt)->format('d.m.Y')}}</span>
                                    </td>
                                </tr>
                                <?php
                                //$cur_days_before = $itm->days_before;
                                $odd = true;
                                ?>
                            @endif
                            <?php
                            $odd = !$odd;
                            $tr_css = ($odd) ? 'background-color: rgba(0, 0, 0, 0.05);' : '';
                            ?>
                            <tr valign="top" style="{{$tr_css}}">
                                <td class="small"></td>
                                <td class="small">&nbsp;&nbsp;&nbsp;
                                    <a href="{{route('tasks.edit',$itm->id)}}"
                                       target="_blank" class="text-decoration-none font-weight-bold">{{$itm->name}}</a>
                                    <span
                                            class="small ml-3"
                                            style="white-space: nowrap;">{{$itm->inituser_name}}</span>
                                </td>
                                <td class="small">
                                    {{date_create($itm->plnbegdt)->format('d.m.Y')}}
                                    - {{date_create($itm->plnenddt)->format('d.m.Y')}}
                                </td>
                            </tr>
                        @endforeach
                    </table>

                @endif

                @if( isset($data->user_exec_tasks) and count($data->user_exec_tasks)>0)

                    <style>
                        .bar {
                            /*fill: white; !* changes the background *!*/
                            height: 21px;
                            transition: fill .3s ease;
                            cursor: pointer;
                            font-family: Helvetica, sans-serif;
                            font-size: 0.7em;
                            color: black;
                        }

                        .rest {
                            fill: #ddd1aa; /* changes the background */
                        }

                        .done {
                            fill: #15b51f; /* changes the background */
                        }

                        .done_pcnt {
                            color: green;
                        }

                        .rest_pcnt {
                            color: red;
                        }

                        .bar text {
                            color: black;
                        }

                        .bar.done:hover,
                        .bar:focus {
                            fill: #a6f5aa;
                        }

                        .bar.rest:hover,
                        .bar:focus {
                            fill: #fff4ce;
                        }

                        .bar:hover text,
                        .bar:focus text {
                            fill: black;
                        }
                    </style>

                    <table class="table-striped1 m-2 table-sm w-100">
                        <tr>
                            <td colspan="3" class="font-weight-bold">Выполняемые</td>
                        </tr>
                        <?php
                        $cur_priority = -1;
                        $cur_days_before = -1;
                        $odd = false;
                        $priorities = [0 => 'нормальный', 1 => 'высокий']
                        ?>
                        @foreach($data->user_exec_tasks as $itm)
                            @if(1==0 and $itm->priority<>$cur_priority)
                                <tr>
                                    <td colspan="3" class="clearfix text-right">
                                        <span class="font-weight-bold font-italic ">
                        Приоритет: <b>{{$priorities[$itm->priority]??'?'}}</b>
                        </span>
                                    </td>
                                </tr>
                                <?php
                                $cur_priority = $itm->priority;
                                $cur_days_before = -1;
                                ?>
                            @endif
                            <?php
                            $odd = !$odd;
                            $tr_css = ($odd) ? 'background-color: rgba(0, 0, 0, 0.05);' : '';
                            ?>
                            <tr valign="top" style="{{$tr_css}}">
                                <td class="small"></td>
                                <td class="small">&nbsp;&nbsp;&nbsp;
                                    <a href="{{route('tasks.edit',$itm->id)}}"
                                       target="_blank" class="text-decoration-none font-weight-bold">{{$itm->name}}</a>
                                    <span
                                            class="small ml-3"
                                            style="white-space: nowrap;">{{$itm->inituser_name}}</span>
                                </td>
                                <td class="small">
                                    {{date_create($itm->fcbegdt)->format('d.m.Y')}}
                                    <div class="text-right">
                                        <?php
                                        $done_pcnt = $itm->progress;
                                        $rest_pcnt = 100 - $done_pcnt;
                                        $k = 150 / 100; //приведение к ширине графика
                                        ?>
                                        <svg class="chart" width="{{100*$k}}" height="21" aria-labelledby="title desc"
                                             role="img">
                                            {{--												<title id="title">% выполнения</title>--}}
                                            <desc id="desc">выполнено; осталось;</desc>
                                            @if($done_pcnt>0)
                                                <g class="bar done">
                                                    <rect width="{{1.2*$done_pcnt*$k}}" height="19"
                                                          title="выполнено"></rect>
                                                    <title id="title1">выполнено</title>
                                                    <text x="5" y="9.5" dy=".35em">{{$done_pcnt}}%</text>
                                                </g>
                                            @endif
                                            <g class="bar rest">
                                                <rect width="{{1.2*$rest_pcnt*$k}}" height="19" x="{{$done_pcnt*$k}}"
                                                      y="0"></rect>
                                                <title id="title2">осталось</title>
                                                <text x="{{($done_pcnt*$k)+5}}" y="9.5" dy=".35em">{{$rest_pcnt}}%
                                                </text>
                                            </g>
                                        </svg>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </table>

                @endif
            </div>

        </div>
    </div>
@endif
