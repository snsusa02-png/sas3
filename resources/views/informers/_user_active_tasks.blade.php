@if( (isset($data->user_active_tasks) and count($data->user_active_tasks)>0)
or (isset($data->user_active_tasks) and count($data->user_active_tasks)) )

{{--    <div class="col-lg-6 col-md-6 col-sm-6">--}}
    <div class="col-lg-12 col-md-12 ">
        <div class="card card-stats mb-1 mt-3">
            <div class="card-header">
                <i class="fa fa-tasks text-danger" aria-hidden="true"></i>
                <a href="{{route('tasks.index')}}">Ваши задачи</a>
                {{--                <a href="{{route("home.refresh")}}"><i class="fa fa-refresh" aria-hidden="true"></i></a>--}}
                <span class="float-right">
                    <a href="{{route("tasks.create")}}?returl={{Request::url()}}" class="btn btn-sm btn-warning"
                       target="_blank" title="Новая задача"><i class="fa fa-plus" aria-hidden="true"></i></a>
    			</span>
            </div>
            <div class="card-body "
                 style=""
            >
                @if( isset($data->user_active_tasks) and count($data->user_active_tasks)>0)
                    <table class="table-striped1 m-2 table-sm w-100">
                        {{--                        <tr><td colspan="3" class="font-weight-bold">Ожидают выполнения</td></tr>--}}
                        <tr class="small">
                            <td></td>
                            <td>Задача, инициатор</td>
                            <td>Крайний срок</td>
                        </tr>
                        <?php
                        $cur_priority = -1;
                        $cur_days_before = -1;
                        $odd = false;
                        $priorities = [0 => 'нормальный', 1 => 'высокий']
                        ?>
                        @foreach($data->user_active_tasks as $itm)
                            @if($itm->priority<>$cur_priority)
                                <tr>
                                    <td colspan="3" class="clearfix text-right">
                                        {{--                                        <span class="font-weight-bold font-italic ">--}}
                                        {{--                        Приоритет: <b>{{$priorities[$itm->priority]??'?'}}</b>--}}
                                        {{--                        </span>--}}
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
                                        <span
                                            class="font-italic ">{{$days_before}} - {{date_create($itm->drctbegdt)->format('d.m.Y')}}</span>
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
                                    <span
                                        class="small mr-3"
                                        style="white-space: nowrap;">{{$itm->inituser_name}}:</span>
                                    <a href="{{route('tasks.edit',$itm->id)}}"
                                       target="_blank" class="text-decoration-none font-weight-bold">{{$itm->name}}</a>
                                    <div class="text-right">{{$itm->srcobjinfo}}</div>
                                </td>
                                <td class="small text-center">
                                    {{--                                    {{date_create($itm->plnbegdt)->format('d.m.Y')}}--}}
                                    {{date_create($itm->plnenddt)->format('d.m.Y')}}
                                </td>
                            </tr>
                        @endforeach
                    </table>

                @endif

            </div>

        </div>
    </div>
@endif
