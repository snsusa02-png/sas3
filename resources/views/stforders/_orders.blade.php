{{--@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->obj_orgs) and count($rec->obj_orgs)>0)--}}
@if( 1==1 and isset($rec) and ($rec->id!=-1) and isset($rec->stforders) )

    <div class="card mt-3">
        <div class="card-header" style="background-color: #d1fff1;">
            <i class="fa fa-certificate text-primary" aria-hidden="true"></i>
            Приказы по сотруднику
            <div class="float-right">
                <a href="{{route('acslst.index',1202)}}" class="small mr-1" target="_blank">ACL</a>

                <button data-toggle="collapse" data-target="#stforders"
                        class="btn btn-light btn-sm">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                    <span class="badge badge-info">{{count($rec->stforders)}}</span>
                </button>
                @if( $usrrights['save']??false )
                    <a href="{{ route('stforders.create',['staffid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning ml-2"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->stforders)>0)
            <div class="card-body collapse" id="stforders">

                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-center">№, дата</td>
                        <td class="text-center">Тип</td>
                        {{--                        <td class="text-center">Период действия</td>--}}
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $time_act_colors = [1 => 'lightyellow', 2 => '#c5ffc5', 3 => 'silver'];
                    ?>
                    @foreach($rec->stforders as $itm)
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
                            <td class="text-center">
                                {{$itm->ordnum}}
                                <div class="small">от {{date_create($itm->orddate)->format('d.m.Y')}}</div>
                            </td>
                            <td class="text-left pl-2 small">
                                {{$itm->ordtype_name}} {{($itm->notes)?' /'.$itm->notes:''}}
                            </td>
                            {{--                            <td class="text-center small"--}}
                            {{--                                style="background-color: {{$time_act_colors[$itm->action_status]??''}};">--}}
                            {{--                                {{date_create($itm->begdate)->format('d.m.Y')}} ---}}
                            {{--                                {{$enddate}}--}}
                            {{--                            </td>--}}
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('stforders.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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
