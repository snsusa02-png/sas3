{{--@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->obj_orgs) and count($rec->obj_orgs)>0)--}}
@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->staff_posts) )

    <div class="card mt-3">
        <div class="card-header" style="background-color: #d2f8ff;">
            <i class="fa fa-suitcase text-black-50" aria-hidden="true"></i>
            Должности сотрудника
            <div class="float-right">
                <button data-toggle="collapse" data-target="#staff_posts"
                        class="btn btn-light btn-sm">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                    <span class="badge badge-info">{{count($rec->staff_posts)}}</span>
                </button>
                @if( $usrrights['save']??false )
                    <a href="{{ route('staff_posts.create',['staffid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning ml-2"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->staff_posts)>0)
            <div class="card-body collapse" id="staff_posts">
                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-center">Период</td>
                        <td class="text-center">Должность</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $time_act_colors = [1 => 'lightyellow', 2 => '#c5ffc5', 3 => 'silver'];
                    ?>
                    @foreach($rec->staff_posts as $itm)
                        <?php
                        $npp++;

                        $td_period_style = "";
                        if (1 == 0 and $itm->active == 0) {
                            $td_period_style = "background-color:lightsalmon;";
                        }
                        $enddate = (isset($itm->enddate)) ? date_create($itm->enddate)->format('d.m.Y') : '...'
                        ?>
                        <tr class="align-top ">
                            <td class="small text-right">{{$loop->iteration}}</td>
                            <td class="text-center small"
                                style="background-color: {{$time_act_colors[$itm->action_status]??''}};">
                                {{date_create($itm->begdate)->format('d.m.Y')}} -
                                {{$enddate}}
                            </td>
                            <td class="text-left pl-2 small">
                                {{$itm->post_name}}
                            </td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('staff_posts.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
                                       class="btn btn-sm btn-primary"
                                       title="Просмотреть/Изменить запись">
                                        <i class="fa fa-pencil"></i>
                                    </a>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        @endif
    </div>
@endif
