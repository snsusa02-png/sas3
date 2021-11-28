@if( 1==1 and isset($rec) and ($rec->id!=-1) and $rec->kindid==1 and isset($rec->org_names) )

    <div class="card mt-3">
        <div class="card-header" style="background-color: #d1fff1;">

            Названия организации
            <div class="float-right">
                <a href="{{route('acslst.index',1622)}}" class="small mr-1" target="_blank">ACL</a>

                <button data-toggle="collapse" data-target="#org_names"
                        class="btn btn-light btn-sm">
                    <i class="fa fa-eye-slash" aria-hidden="true"></i>
                    <span class="badge badge-info">{{count($rec->org_names)}}</span>
                </button>
                @if( $usrrights['save']??false )
                    <a href="{{ route('org_names.create',['orgid'=>$rec->id])}}?returl={{Request::url()}}"
                       class="btn btn-sm btn-warning ml-2"
                       title="Создать запись">
                        <i class="fa fa-plus"></i>
                    </a>
                @endif
            </div>
        </div>
        @if (count($rec->org_names)>0)
            <div class="card-body collapse" id="org_names">

                <table class="table-striped " style="width: 100%;">
                    <thead>
                    <tr class="text-center align-middle small">
                        <td>#</td>
                        <td class="text-center">Название</td>
                        <td class="text-center">Тип</td>
                        <td class="text-center">Период действия</td>
                        <td/>
                    </tr>
                    </thead>
                    <tbody>
                    <?php
                    $npp = 0;
                    $time_act_colors = [1 => 'lightyellow', 2 => '#c5ffc5', 3 => 'silver'];
                    ?>
                    @foreach($rec->org_names as $itm)
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
                                {{$itm->name}}

                            </td>
                            <td class="text-left pl-2 small">
                                {{$itm->type_name}}
                            </td>
                            <td class="text-center small"
                                style="background-color: {{$time_act_colors[$itm->action_status]??''}};">
                                {{date_create($itm->begdate)->format('d.m.Y')}} -
                                {{$enddate}}
                            </td>
                            <td class="text-right">
                                @if(1==1)
                                    <a href="{{ route('org_names.edit',['id'=>$itm->id])}}?returl={{Request::url()}}"
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
